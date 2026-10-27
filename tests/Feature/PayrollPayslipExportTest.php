<?php

use App\Models\CompanySetting;
use App\Models\EPFSchedule;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\FileStorageService;

use function Pest\Laravel\getJson;

beforeEach(function () {
    $this->admin = User::where('email', 'superadmin@azamventures.com')->first();
    $this->adminHeaders = ['Authorization' => 'Bearer '.$this->admin->createToken('test')->plainTextToken];

    if (CompanySetting::count() === 0) {
        CompanySetting::create([
            'company_name' => 'Test Company Sdn Bhd',
            'address' => '123 Test Street',
            'reg_no' => 'REG-123',
            'epf_no' => 'EPF-001',
            'socso_no' => 'SOCSO-001',
            'eis_no' => 'EIS-001',
        ]);
    }

    foreach (['A', 'B', 'C', 'D'] as $code) {
        EPFSchedule::firstOrCreate(
            ['code' => $code],
            ['name' => "Schedule {$code}", 'employer_rate' => 12, 'employee_rate' => 11, 'max_tier_wage' => 5000],
        );
    }

    $this->period = PayrollPeriod::factory()->open()->create();
});

function staffAuthHeaders(): array
{
    $staff = StaffProfile::factory()->create();
    $user = User::factory()->create(['email' => $staff->email]);
    $user->syncRoles(['staff']);

    return [
        'headers' => ['Authorization' => 'Bearer '.$user->createToken('test')->plainTextToken],
        'staff' => $staff,
    ];
}

function payrollItem(PayrollPeriod $period, StaffProfile $staff, string $state = 'confirmed'): PayrollRunItem
{
    $confirmed = $state !== 'draft';
    $paid = $state === 'paid';

    return PayrollRunItem::factory()->create([
        'period_id' => $period->id,
        'employee_id' => $staff->id,
        'confirmed' => $confirmed,
        'confirmed_at' => $confirmed ? now() : null,
        'confirmed_by' => $confirmed ? $staff->id : null,
        'paid' => $paid,
        'paid_at' => $paid ? now() : null,
        'paid_by' => $paid ? $staff->id : null,
    ]);
}

function openZip($response): array
{
    $file = $response->baseResponse;
    $file->deleteFileAfterSend(false);

    $zip = new ZipArchive;
    expect($zip->open($file->getFile()->getPathname()))->toBeTrue();

    return [$zip];
}

describe('Single payslip export', function () {

    it('lets an employee download their own paid payslip', function () {
        ['headers' => $headers, 'staff' => $staff] = staffAuthHeaders();
        $item = payrollItem($this->period, $staff, 'paid');

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', $headers)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('blocks an employee from downloading their own unpaid payslip', function () {
        ['headers' => $headers, 'staff' => $staff] = staffAuthHeaders();
        $item = payrollItem($this->period, $staff, 'confirmed');

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', $headers)
            ->assertStatus(403);
    });

    it('lets hr download another employee confirmed payslip', function () {
        $staff = StaffProfile::factory()->create();
        $item = payrollItem($this->period, $staff, 'confirmed');

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', $this->adminHeaders)
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    });

    it('rejects an unconfirmed payslip for payroll roles', function () {
        $staff = StaffProfile::factory()->create();
        $item = payrollItem($this->period, $staff, 'draft');

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', $this->adminHeaders)
            ->assertStatus(422);
    });

    it('blocks an unrelated employee from downloading someone else payslip', function () {
        ['headers' => $headers] = staffAuthHeaders();
        $other = StaffProfile::factory()->create();
        $item = payrollItem($this->period, $other, 'paid');

        getJson('/api/v1/payroll/payslips/'.$item->id.'/download', $headers)
            ->assertStatus(403);
    });
});

describe('Bulk payslip ZIP export', function () {

    it('bundles every confirmed payslip in the period', function () {
        foreach (range(1, 2) as $i) {
            payrollItem($this->period, StaffProfile::factory()->create());
        }

        $response = $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders);

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/zip');

        [$zip] = openZip($response);
        expect($zip->numFiles)->toBe(2);
        expect($zip->getNameIndex(0))->toContain('Payslip_'.$this->period->code.'_');
        $zip->close();
    });

    it('excludes unconfirmed payslips', function () {
        payrollItem($this->period, StaffProfile::factory()->create());
        payrollItem($this->period, StaffProfile::factory()->create(), 'draft');

        $response = $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders);

        $response->assertOk();
        [$zip] = openZip($response);
        expect($zip->numFiles)->toBe(1);
        $zip->close();
    });

    it('excludes payslips with a zero salary', function () {
        payrollItem($this->period, StaffProfile::factory()->create());
        $unpaidStaff = StaffProfile::factory()->create();
        PayrollRunItem::factory()->create([
            'period_id' => $this->period->id,
            'employee_id' => $unpaidStaff->id,
            'salary' => 0,
            'confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $unpaidStaff->id,
        ]);

        $response = $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders);

        $response->assertOk();
        [$zip] = openZip($response);
        expect($zip->numFiles)->toBe(1);
        $zip->close();
    });

    it('returns 422 when no payslip is confirmed', function () {
        payrollItem($this->period, StaffProfile::factory()->create(), 'draft');

        $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders)
            ->assertStatus(422);
    });

    it('forbids staff from bulk exporting a period', function () {
        ['headers' => $headers] = staffAuthHeaders();

        $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $headers)
            ->assertStatus(403);
    });

    it('regenerates a missing payslip pdf before zipping', function () {
        $staff = StaffProfile::factory()->create();
        $item = payrollItem($this->period, $staff);

        (new FileStorageService)->delete('payslips/'.$this->period->id.'/'.$item->id.'.pdf');

        $response = $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders);

        $response->assertOk();
        [$zip] = openZip($response);
        expect($zip->numFiles)->toBe(1);
        $zip->close();
    });

    it('sanitizes employee names in archive entries', function () {
        $staff = StaffProfile::factory()->create(['name' => 'Ali / Ahmad']);
        payrollItem($this->period, $staff);

        $response = $this->get('/api/v1/payroll/periods/'.$this->period->id.'/payslips/download', $this->adminHeaders);

        $response->assertOk();
        [$zip] = openZip($response);
        expect($zip->getNameIndex(0))->toContain('Ali_Ahmad');
        $zip->close();
    });
});
