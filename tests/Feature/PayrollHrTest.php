<?php

use App\Models\CompanySetting;
use App\Models\EPFSchedule;
use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\PayrollPeriod;
use App\Models\PayrollRunItem;
use App\Models\StaffProfile;
use App\Models\User;
use App\Services\PayslipGenerator;

use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;

beforeEach(function () {
    $this->user = User::where('email', 'superadmin@azamventures.com')->first();
    $this->token = $this->user->createToken('test')->plainTextToken;
    $this->headers = ['Authorization' => 'Bearer '.$this->token];

    // Ensure epf_schedule codes referenced by factory exist
    foreach (['A', 'B', 'C', 'D'] as $code) {
        EPFSchedule::firstOrCreate(
            ['code' => $code],
            ['name' => "Schedule {$code}", 'employer_rate' => 12, 'employee_rate' => 11, 'max_tier_wage' => 5000],
        );
    }

    // Ensure company settings exist for payslip generation
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
});

describe('Payroll', function () {

    it('returns 401 without token', function () {
        getJson('/api/v1/payroll/periods')->assertStatus(401);
    });

    it('lists payroll periods', function () {
        getJson('/api/v1/payroll/periods', $this->headers)
            ->assertStatus(200);
    });

    it('returns my payslips with statutory employee fields', function () {
        $staff = StaffProfile::where('email', 'superadmin@azamventures.com')->first();

        if (PayrollRunItem::where('employee_id', $staff->id)->where('paid', true)->doesntExist()) {
            $period = PayrollPeriod::factory()->create();
            PayrollRunItem::factory()->create([
                'employee_id' => $staff->id,
                'period_id' => $period->id,
                'paid' => true,
                'paid_at' => now(),
                'paid_by' => $staff->id,
                'confirmed' => true,
                'confirmed_at' => now(),
                'confirmed_by' => $staff->id,
            ]);
        }

        getJson('/api/v1/my-payslips', $this->headers)
            ->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'employee' => [
                    'epf_no', 'socso_no', 'tax_no', 'bank_name', 'bank_account_no',
                ],
            ]);
    });

    it('returns payslip item with statutory fields', function () {
        $staff = StaffProfile::where('email', 'superadmin@azamventures.com')->first();
        $period = PayrollPeriod::factory()->create();
        $item = PayrollRunItem::factory()->create([
            'employee_id' => $staff->id,
            'period_id' => $period->id,
        ]);

        getJson("/api/v1/payroll/periods/{$period->id}/items/{$item->id}", $this->headers)
            ->assertStatus(200)
            ->assertJsonStructure([
                'epf_no', 'socso_no', 'tax_no', 'bank_name', 'bank_account_no',
            ]);
    });

    it('downloads payslip as binary PDF', function () {
        $staff = StaffProfile::where('email', 'superadmin@azamventures.com')->first();
        $period = PayrollPeriod::factory()->create();
        $item = PayrollRunItem::factory()->create([
            'employee_id' => $staff->id,
            'period_id' => $period->id,
            'paid' => true,
            'paid_at' => now(),
            'paid_by' => $staff->id,
            'confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $staff->id,
        ]);

        $response = getJson("/api/v1/payroll/payslips/{$item->id}/download", $this->headers);

        $response->assertStatus(200);
        expect($response->headers->get('Content-Type'))->toBe('application/pdf');
        expect($response->headers->get('Content-Disposition'))->toContain('attachment; filename="');
        expect(strlen($response->content()))->toBeGreaterThan(0);
    });

    it('handles missing company settings in payslip generation', function () {
        CompanySetting::truncate();

        $staff = StaffProfile::where('email', 'superadmin@azamventures.com')->first();
        $period = PayrollPeriod::factory()->create();
        $item = PayrollRunItem::factory()->create([
            'employee_id' => $staff->id,
            'period_id' => $period->id,
            'paid' => true,
            'paid_at' => now(),
            'paid_by' => $staff->id,
            'confirmed' => true,
            'confirmed_at' => now(),
            'confirmed_by' => $staff->id,
        ]);

        $generator = new PayslipGenerator;
        $path = $generator->generate($item->id);

        expect($path)->toBeString();
        expect(str_ends_with($path, '.pdf'))->toBeTrue();
    });

});

describe('Leaves', function () {

    it('lists leaves', function () {
        getJson('/api/v1/leaves', $this->headers)
            ->assertStatus(200);
    });

    it('shows single leave', function () {
        $leave = LeaveApplication::first();
        if (! $leave) {
            $this->markTestSkipped('No leaves in database');
        }

        getJson('/api/v1/leaves/'.$leave->id, $this->headers)
            ->assertStatus(200);
    });

    it('creates leave with validation error', function () {
        postJson('/api/v1/leaves', [], $this->headers)
            ->assertStatus(422);
    });

});

describe('Claims', function () {

    it('lists all claims', function () {
        getJson('/api/v1/claims', $this->headers)
            ->assertStatus(200);
    });

    it('lists my claims', function () {
        getJson('/api/v1/my-claims', $this->headers)
            ->assertStatus(200);
    });

    it('shows single claim', function () {
        $claim = ExpenseClaim::first();
        if (! $claim) {
            $this->markTestSkipped('No claims in database');
        }

        getJson('/api/v1/claims/'.$claim->id, $this->headers)
            ->assertStatus(200);
    });

});

describe('Timecards', function () {

    it('lists timecards', function () {
        getJson('/api/v1/timecards', $this->headers)
            ->assertStatus(200);
    });

});

describe('Attendance', function () {

    it('returns attendance records', function () {
        getJson('/api/v1/attendance/records', $this->headers)
            ->assertStatus(200);
    });

    it('returns today attendance', function () {
        getJson('/api/v1/attendance/today', $this->headers)
            ->assertStatus(200);
    });

    it('returns attendance summary', function () {
        getJson('/api/v1/attendance/summary', $this->headers)
            ->assertStatus(200);
    });

});

describe('Leave Groups', function () {

    it('lists leave groups', function () {
        getJson('/api/v1/leave-groups', $this->headers)
            ->assertStatus(200);
    });

});
