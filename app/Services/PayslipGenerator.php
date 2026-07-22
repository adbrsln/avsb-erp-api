<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Services\FileStorageService;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;

class PayslipGenerator
{
    public function generate(int $itemId): string
    {
        $item = PayrollRunItem::with('adjustments', 'period')->find($itemId);
        if (!$item) throw new \RuntimeException("Payroll item #{$itemId} not found.");

        $employee = StaffProfile::find($item->employee_id);
        $company = CompanySetting::first();

        $totalEe = $item->epf_employee + $item->socso_employee + $item->eis_employee + (float) ($item->socso_24h_employee ?? 0);
        $totalEr = $item->epf_employer + $item->socso_employer + $item->eis_employer;
        $netSalary = $item->salary - $totalEe;
        $isHourly = $item->wage_type === 'hourly_timesheet';

        $earningsAdjustments = $item->adjustments ? $item->adjustments->where('type', 'earnings') : collect();
        $deductionsAdjustments = $item->adjustments ? $item->adjustments->where('type', 'deductions') : collect();
        $adjEarningsTotal = $earningsAdjustments->sum('amount');
        $adjDeductionsTotal = $deductionsAdjustments->sum('amount');

        $grossEarnings = $item->salary + $adjEarningsTotal;
        $totalDeductions = $totalEe + $adjDeductionsTotal;
        $adjustedNet = $grossEarnings - $totalDeductions;

        $html = $this->buildHtml($item, $employee, $company, $totalEr, $totalEe, $netSalary, $adjustedNet, $isHourly, $earningsAdjustments, $deductionsAdjustments, $adjEarningsTotal, $adjDeductionsTotal);

        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $path = 'uploads/payslips/' . $item->period_id . '/' . $item->id . '.pdf';
        $storage = new FileStorageService();
        $storage->put($path, $dompdf->output(), 'application/pdf');

        return $path;
    }

    private function buildHtml(
        PayrollRunItem $item,
        ?StaffProfile $employee,
        ?CompanySetting $company,
        float $totalEr,
        float $totalEe,
        float $netSalary,
        float $adjustedNet,
        bool $isHourly = false,
        $earningsAdjustments = null,
        $deductionsAdjustments = null,
        float $adjEarningsTotal = 0,
        float $adjDeductionsTotal = 0,
    ): string {
        $cName = htmlspecialchars($company->company_name ?? '');
        $cAddr = htmlspecialchars($company->address ?? '');
        $cReg = htmlspecialchars($company->reg_no ?? '');
        $cEpf = htmlspecialchars($company->epf_no ?? '');
        $cSocso = htmlspecialchars($company->socso_no ?? '');
        $cEis = htmlspecialchars($company->eis_no ?? '');
        $periodCode = htmlspecialchars($item->period->code ?? '');
        $empName = htmlspecialchars($employee->name ?? $item->employee_name);
        $empCode = htmlspecialchars($employee->employee_id ?? $item->employee_code);
        $empIc = htmlspecialchars($employee->identification_no ?? '—');
        $empDept = htmlspecialchars($employee->department ?? '—');
        $empPos = htmlspecialchars($employee->job_title ?? '—');
        $empEpf = htmlspecialchars($employee->epf_no ?? '—');
        $empSocso = htmlspecialchars($employee->socso_no ?? '—');
        $empBank = htmlspecialchars($employee->bank_name ?? '—');
        $empAcc = htmlspecialchars($employee->bank_account_no ?? '—');

        $salary = number_format($item->salary, 2);
        $epfEr = number_format($item->epf_employer, 2);
        $epfEe = number_format($item->epf_employee, 2);
        $socsoEr = number_format($item->socso_employer, 2);
        $socsoEe = number_format($item->socso_employee, 2);
        $eisEr = number_format($item->eis_employer, 2);
        $eisEe = number_format($item->eis_employee, 2);
        $tEr = number_format($totalEr, 2);
        $socso24Ee = number_format($item->socso_24h_employee ?? 0, 2);
        $tEe = number_format($totalEe, 2);
        $socso24EeDisplay = ($item->socso_24h_employee ?? 0) > 0
            ? '<tr><td></td><td class="amt"></td><td>SKBBK</td><td class="amt">' . $socso24Ee . '</td></tr>'
            : '';
        $net = number_format($netSalary, 2);
        $adjNet = number_format($adjustedNet, 2);
        $grossEarningsFormatted = number_format($item->salary + $adjEarningsTotal, 2);
        $totalDeductionsFormatted = number_format($totalEe + $adjDeductionsTotal, 2);

        $earningsLabel = $isHourly
            ? "Hours Worked ({$item->total_hours}h × RM " . number_format($item->hourly_rate_applied ?? 0, 2) . "/hr)"
            : 'Basic Salary';

        $epfErDisplay = $isHourly ? 'N/A' : $epfEr;
        $epfEeDisplay = $isHourly ? 'N/A' : $epfEe;
        $socsoErDisplay = $isHourly ? 'N/A' : $socsoEr;
        $socsoEeDisplay = $isHourly ? 'N/A' : $socsoEe;
        $eisErDisplay = $isHourly ? 'N/A' : $eisEr;
        $eisEeDisplay = $isHourly ? 'N/A' : $eisEe;
        $tErDisplay = $isHourly ? 'N/A' : $tEr;

        $adjEarningsRows = '';
        if ($earningsAdjustments && count($earningsAdjustments) > 0) {
            foreach ($earningsAdjustments as $adj) {
                $lbl = htmlspecialchars($adj->label);
                $amt = number_format($adj->amount, 2);
                $adjEarningsRows .= "<tr><td>{$lbl}</td><td class=\"amt\">{$amt}</td><td></td><td></td></tr>\n";
            }
        }

        $adjDeductionsRows = '';
        if ($deductionsAdjustments && count($deductionsAdjustments) > 0) {
            foreach ($deductionsAdjustments as $adj) {
                $lbl = htmlspecialchars($adj->label);
                $amt = number_format($adj->amount, 2);
                $adjDeductionsRows .= "<tr><td></td><td></td><td>{$lbl}</td><td class=\"amt\">{$amt}</td></tr>\n";
            }
        }

        $hasAdj = $adjEarningsTotal > 0 || $adjDeductionsTotal > 0;
        $netDisplay = $hasAdj ? $adjNet : $net;

        return <<<HTML
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
  body { font-family: 'Courier New', Courier, monospace; font-size: 9pt; color: #000; margin: 0; padding: 20px 25px; }
  table { width: 100%; border-collapse: collapse; }
  td { padding: 2px 4px; vertical-align: top; }
  .top-border { border-top: 2px solid #000; }
  .top-border td { padding-top: 6px; }
  .company-name { font-size: 12pt; font-weight: bold; }
  .company-detail { font-size: 7.5pt; color: #333; }
  .payslip-title { font-size: 14pt; font-weight: bold; text-align: right; letter-spacing: 2px; }
  .period-label { font-size: 7.5pt; text-align: right; color: #333; }
  .sep { border-bottom: 1px solid #999; margin: 4px 0 10px 0; }
  .emp-table { width: 100%; margin-bottom: 10px; }
  .emp-table td { font-size: 8pt; padding: 1px 8px 1px 0; }
  .emp-table .lbl { color: #555; width: 80px; }
  .emp-table .val { font-weight: bold; }
  .ledger { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
  .ledger td, .ledger th { border: 1px solid #000; padding: 4px 8px; font-size: 8pt; }
  .ledger th { background: #e0e0e0; font-weight: bold; text-align: center; }
  .ledger .amt { text-align: right; width: 100px; }
  .ledger .total { font-weight: bold; background: #f0f0f0; }
  .ledger .total td { border-top: 2px solid #000; }
  .net-table { width: 100%; border-collapse: collapse; margin-top: 6px; margin-bottom: 12px; }
  .net-table td { border: 1px solid #000; padding: 8px 12px; }
  .net-table .net-label { font-size: 7pt; color: #555; text-transform: uppercase; letter-spacing: 1px; }
  .net-table .net-amount { font-size: 16pt; font-weight: bold; }
  .net-table .net-status { text-align: right; font-size: 8pt; color: #333; }
  .er-table { width: 100%; border-collapse: collapse; margin-top: 2px; }
  .er-table td, .er-table th { border: 1px solid #999; padding: 3px 8px; font-size: 7.5pt; }
  .er-table th { background: #eee; font-weight: bold; text-align: center; font-size: 7pt; }
  .er-table .amt { text-align: right; width: 100px; }
  .er-table .total { font-weight: bold; }
</style>
</head>
<body>

<table class="top-border" width="100%">
<tr>
  <td width="60%">
    <div class="company-name">{$cName}</div>
    <div class="company-detail">{$cAddr}</div>
    <div class="company-detail" style="margin-top:2px;">
      Reg: {$cReg} | EPF: {$cEpf} | SOCSO: {$cSocso} | EIS: {$cEis}
    </div>
  </td>
  <td width="40%">
    <div class="payslip-title">PAYSLIP</div>
    <div class="period-label">{$periodCode}</div>
  </td>
</tr>
</table>
<div class="sep"></div>

<table class="emp-table">
<tr><td class="lbl">Employee</td><td class="val">{$empName}</td><td class="lbl">Code</td><td class="val">{$empCode}</td><td class="lbl">IC / Passport</td><td class="val">{$empIc}</td></tr>
<tr><td class="lbl">Department</td><td class="val">{$empDept}</td><td class="lbl">Position</td><td class="val">{$empPos}</td><td class="lbl">EPF No.</td><td class="val">{$empEpf}</td></tr>
<tr><td class="lbl">SOCSO No.</td><td class="val">{$empSocso}</td><td class="lbl">Bank</td><td class="val">{$empBank}</td><td class="lbl">Account</td><td class="val">{$empAcc}</td></tr>
</table>

<table class="ledger">
<tr><th width="50%">EARNINGS</th><th class="amt">Amount (RM)</th><th width="50%">DEDUCTIONS</th><th class="amt">Amount (RM)</th></tr>
<tr><td>{$earningsLabel}</td><td class="amt">{$salary}</td><td>EPF (Employee)</td><td class="amt">{$epfEeDisplay}</td></tr>
{$adjEarningsRows}
<tr><td></td><td class="amt"></td><td>SOCSO (Employee)</td><td class="amt">{$socsoEeDisplay}</td></tr>
{$socso24EeDisplay}
<tr><td></td><td class="amt"></td><td>EIS (Employee)</td><td class="amt">{$eisEeDisplay}</td></tr>
{$adjDeductionsRows}
<tr class="total"><td>TOTAL EARNINGS</td><td class="amt">{$grossEarningsFormatted}</td><td>TOTAL DEDUCTIONS</td><td class="amt">{$totalDeductionsFormatted}</td></tr>
</table>

<table class="net-table">
<tr>
  <td width="60%">
    <div class="net-label">Net Take-Home Pay</div>
    <div class="net-amount">RM {$netDisplay}</div>
  </td>
  <td width="40%" class="net-status">
    PAYMENT STATUS: PAID
  </td>
</tr>
</table>

<table class="er-table">
<tr><th colspan="2">EMPLOYER CONTRIBUTIONS (not deducted from salary)</th></tr>
<tr><td>EPF (Employer)</td><td class="amt">{$epfErDisplay}</td></tr>
<tr><td>SOCSO (Employer)</td><td class="amt">{$socsoErDisplay}</td></tr>
<tr><td>EIS (Employer)</td><td class="amt">{$eisErDisplay}</td></tr>
<tr class="total"><td>TOTAL EMPLOYER CONTRIBUTION</td><td class="amt">{$tErDisplay}</td></tr>
</table>

</body>
</html>
HTML;
    }
}
