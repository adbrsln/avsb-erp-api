<?php

use App\Models\Attendance;
use App\Models\JournalEntry;
use App\Models\NotificationQueue;
use App\Models\NotificationTemplate;
use App\Models\PayrollAdjustment;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\Payroll\PayrollJournalService;
use App\Services\Payroll\PayrollProcessor;
use App\Services\Payroll\PayrollReversalService;

use function Pest\Laravel\postJson;

beforeEach(function () {
    NotificationTemplate::firstOrCreate(
        ['event_type' => 'payslip.revoked'],
        [
            'category' => 'alert',
            'subject_template' => 'Payslip Revoked — {{period}}',
            'body_template' => '<p>Your payslip for <strong>{{period}}</strong> has been revoked.</p>',
        ]
    );

    $this->period = PayrollPeriod::factory()->open()->create();
    $this->staff = StaffProfile::factory()->create(['basic_salary' => 4000]);
    $this->admin = User::factory()->create();
    $this->admin->syncRoles(['admin']);
    $this->adminToken = $this->admin->createToken('test')->plainTextToken;
    $this->adminHeaders = ['Authorization' => 'Bearer '.$this->adminToken];
});

function reversalPaidItem(PayrollPeriod $period, StaffProfile $staff, array $overrides = []): PayrollRunItem
{
    return PayrollRunItem::factory()->create(array_merge([
        'period_id' => $period->id,
        'employee_id' => $staff->id,
        'salary' => 4000,
        'epf_employer' => 480,
        'epf_employee' => 440,
        'epf_schedule_code' => 'FLAT',
        'socso_employer' => 84,
        'socso_employee' => 37,
        'eis_employer' => 21,
        'eis_employee' => 8,
        'socso_24h_employee' => 0,
        'pcb_employee' => 46.80,
        'zakat' => 50,
        'paid' => true,
        'confirmed' => true,
    ], $overrides));
}

describe('payroll reversal', function () {
    it('posts a reversing journal, deletes the item and payslip PDF, and notifies the employee', function () {
        $item = reversalPaidItem($this->period, $this->staff);
        (new PayrollJournalService)->post($item);
        $originalJe = JournalEntry::where('reference_type', 'payroll')->where('reference_id', $item->id)->first();
        expect($originalJe)->not->toBeNull();

        $result = (new PayrollReversalService)->reverseItem($item);

        // Original JE kept + reversing JE posted with swapped DR/CR.
        expect($originalJe->fresh())->not->toBeNull();
        $revJe = JournalEntry::where('reference_type', 'payroll_reversal')->where('reference_id', $item->id)->first();
        expect($revJe)->not->toBeNull();
        expect($revJe->entry_number)->toBe('PAY-'.$item->id.'-REV');
        expect($revJe->lines->sum('debit'))->toBe(round($originalJe->lines->sum('credit'), 2));
        expect($revJe->lines->sum('credit'))->toBe(round($originalJe->lines->sum('debit'), 2));

        // Item deleted; payslip revoked notification queued.
        expect(PayrollRunItem::find($item->id))->toBeNull();
        expect(NotificationQueue::where('event_type', 'payslip.revoked')->count())->toBe(1);
        expect($result['reversed_je'])->toBeTrue();
    });

    it('does not post a reversing journal for unpaid items', function () {
        $item = reversalPaidItem($this->period, $this->staff, ['paid' => false, 'confirmed' => true]);

        (new PayrollReversalService)->reverseItem($item);

        expect(JournalEntry::where('reference_type', 'payroll_reversal')->where('reference_id', $item->id)->count())->toBe(0);
        expect(PayrollRunItem::find($item->id))->toBeNull();
    });

    it('clears part-time attendance links and cascades adjustments deletion', function () {
        $item = reversalPaidItem($this->period, $this->staff, ['wage_type' => 'hourly_timesheet']);
        PayrollAdjustment::create([
            'payroll_run_item_id' => $item->id,
            'type' => 'earnings',
            'statutory_type' => 'wages',
            'label' => 'Extra',
            'amount' => 100,
        ]);
        $attendance = Attendance::create([
            'staff_id' => $this->staff->id,
            'date' => '2026-01-15',
            'clock_in' => '2026-01-15 08:00:00',
            'clock_out' => '2026-01-15 17:00:00',
            'payroll_run_item_id' => $item->id,
        ]);

        (new PayrollReversalService)->reverseItem($item);

        expect($attendance->fresh()->payroll_run_item_id)->toBeNull();
        expect(PayrollAdjustment::where('payroll_run_item_id', $item->id)->count())->toBe(0);
    });

    it('reverses a whole period and reopens it', function () {
        $itemA = reversalPaidItem($this->period, $this->staff);
        $other = StaffProfile::factory()->create(['basic_salary' => 3000]);
        $itemB = reversalPaidItem($this->period, $other);

        $result = (new PayrollReversalService)->reversePeriod($this->period);

        expect($result['reversed_count'])->toBe(2);
        expect(PayrollRunItem::where('period_id', $this->period->id)->count())->toBe(0);
        expect($this->period->fresh()->status)->toBe('open');
    });

    it('recalculates later periods so YTD PCB stays correct', function () {
        // Build Jan (month 1) with a bonus, then Feb (month 2) with its own
        // bonus — Feb's incremental spike depends on Jan's prior additional.
        $jan = PayrollPeriod::factory()->open()->create([
            'code' => 'REV-JAN',
            'start_date' => '2027-01-01',
            'end_date' => '2027-01-31',
            'month' => 1,
            'year' => 2027,
        ]);
        $feb = PayrollPeriod::factory()->open()->create([
            'code' => 'REV-FEB',
            'start_date' => '2027-02-01',
            'end_date' => '2027-02-28',
            'month' => 2,
            'year' => 2027,
        ]);
        $this->staff->update(['basic_salary' => 5000]);

        (new PayrollProcessor)->process($jan->id);
        $janItem = PayrollRunItem::where('period_id', $jan->id)->where('employee_id', $this->staff->id)->first();
        PayrollAdjustment::create([
            'payroll_run_item_id' => $janItem->id,
            'type' => 'earnings',
            'statutory_type' => 'additional',
            'label' => 'Bonus',
            'amount' => 5000,
        ]);
        (new PayrollProcessor)->process($jan->id);

        (new PayrollProcessor)->process($feb->id);
        $febItem = PayrollRunItem::where('period_id', $feb->id)->where('employee_id', $this->staff->id)->first();
        PayrollAdjustment::create([
            'payroll_run_item_id' => $febItem->id,
            'type' => 'earnings',
            'statutory_type' => 'additional',
            'label' => 'Bonus',
            'amount' => 3000,
        ]);
        (new PayrollProcessor)->process($feb->id);
        $febItem->refresh();
        $febPcbBefore = (float) $febItem->pcb_employee;

        // Reverse January → February's prior additional drops → PCB changes.
        $janItem->refresh();
        (new PayrollReversalService)->reverseItem($janItem);
        $affected = (new PayrollReversalService)->recalculateLater($jan->fresh());

        expect($affected)->not->toBeEmpty();
        expect($affected[0]['period_id'])->toBe($feb->id);
        $febItem->refresh();
        expect((float) $febItem->pcb_employee)->not->toBe($febPcbBefore);
    });

    it('blocks hr from reversing (admin-only)', function () {
        $item = reversalPaidItem($this->period, $this->staff);

        $hr = User::factory()->create();
        $hr->syncRoles(['hr']);
        $hrHeaders = ['Authorization' => 'Bearer '.$hr->createToken('test')->plainTextToken];

        postJson('/api/v1/payroll/periods/'.$this->period->id.'/items/'.$item->id.'/reverse', [], $hrHeaders)
            ->assertStatus(403);
        postJson('/api/v1/payroll/periods/'.$this->period->id.'/reverse', [], $hrHeaders)
            ->assertStatus(403);
        expect(PayrollRunItem::find($item->id))->not->toBeNull();
    });

    it('allows admin to reverse an item', function () {
        $item = reversalPaidItem($this->period, $this->staff);

        postJson('/api/v1/payroll/periods/'.$this->period->id.'/items/'.$item->id.'/reverse', [], $this->adminHeaders)
            ->assertOk();
        expect(PayrollRunItem::find($item->id))->toBeNull();
    });
});
