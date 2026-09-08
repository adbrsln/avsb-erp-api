<?php

namespace App\Services\Payroll;

use App\Models\Attendance;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\PayslipGenerator;
use Illuminate\Support\Facades\DB;

/**
 * Reverses payroll items/periods back to an unprocessed state.
 *
 * Accounting-aware: paid items get a reversing journal entry (audit trail)
 * before the item is deleted, payslip PDFs are removed, part-time attendance
 * links are cleared, and the affected staff get a "payslip revoked" notice.
 * Later periods in the same tax year are recalculated so YTD PCB stays correct.
 */
class PayrollReversalService
{
    public function reverseItem(PayrollRunItem $item, ?StaffProfile $actor = null): array
    {
        return DB::transaction(function () use ($item) {
            $reversedJe = false;
            $pdfDeleted = false;

            if ($item->paid) {
                (new PayrollJournalService)->reverse($item);
                $reversedJe = true;
            }

            $pdfPath = 'payslips/'.$item->period_id.'/'.$item->id.'.pdf';
            try {
                (new FileStorageService)->delete($pdfPath);
                $pdfDeleted = true;
            } catch (\Throwable $e) {
                logger()->warning('Payslip PDF delete failed during reversal', ['item_id' => $item->id, 'error' => $e->getMessage()]);
            }

            // Part-time items link attendance rows — clear so they can be reprocessed.
            Attendance::where('payroll_run_item_id', $item->id)
                ->update(['payroll_run_item_id' => null]);

            $employeeId = $item->employee_id;
            $period = $item->period;
            $employee = StaffProfile::find($employeeId);
            if ($employee?->email) {
                NotificationService::queue(
                    NotificationEvent::PAYSLIP_REVOKED,
                    $employee->email,
                    $employee->name,
                    ['period' => $period?->code ?? ''],
                    'App\\Models\\PayrollRunItem',
                    $item->id
                );
            }

            $item->delete();

            return [
                'item_id' => $item->id,
                'reversed_je' => $reversedJe,
                'pdf_deleted' => $pdfDeleted,
                'notified' => $employee?->email !== null,
            ];
        });
    }

    public function reversePeriod(PayrollPeriod $period, ?StaffProfile $actor = null): array
    {
        $results = [];
        foreach ($period->items as $item) {
            $results[] = $this->reverseItem($item, $actor);
        }

        $period->update(['status' => 'open']);

        return [
            'period_id' => $period->id,
            'reversed_count' => count($results),
            'items' => $results,
        ];
    }

    /**
     * Recalculate later periods in the same tax year so YTD-based PCB stays
     * correct after an earlier period is reversed. Paid items get their JE
     * re-posted (idempotent) and payslip PDF regenerated.
     */
    public function recalculateLater(PayrollPeriod $reversed): array
    {
        $affected = [];
        $later = PayrollPeriod::where('year', $reversed->year)
            ->where('month', '>', $reversed->month)
            ->orderBy('month')
            ->get();

        foreach ($later as $period) {
            $periodAffected = [];
            foreach ($period->items as $item) {
                $before = [
                    'pcb' => (float) $item->pcb_employee,
                    'epf' => (float) $item->epf_employee,
                    'socso' => (float) $item->socso_employee,
                    'eis' => (float) $item->eis_employee,
                ];

                (new PayrollStatutoryService)->recalculate($item);
                $item->refresh();

                $changed = $before['pcb'] != $item->pcb_employee
                    || $before['epf'] != $item->epf_employee
                    || $before['socso'] != $item->socso_employee
                    || $before['eis'] != $item->eis_employee;

                if ($changed && $item->paid) {
                    try {
                        (new PayrollJournalService)->post($item);
                    } catch (\Throwable $e) {
                        logger()->error('JE re-post failed during reversal cascade', ['item_id' => $item->id, 'error' => $e->getMessage()]);
                    }
                    try {
                        (new PayslipGenerator)->generate($item->id);
                    } catch (\Throwable $e) {
                        logger()->error('Payslip regen failed during reversal cascade', ['item_id' => $item->id, 'error' => $e->getMessage()]);
                    }
                }

                if ($changed) {
                    $periodAffected[] = $item->id;
                }
            }

            if (! empty($periodAffected)) {
                $affected[] = [
                    'period_id' => $period->id,
                    'period_code' => $period->code,
                    'item_ids' => $periodAffected,
                ];
            }
        }

        return $affected;
    }
}
