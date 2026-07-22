<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\AssetLicense;
use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\ClientPIC;
use App\Models\ExpenseClaim;
use App\Models\LeaveApplication;
use App\Models\Phase;
use App\Models\Project;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorPIC;
use App\Models\Task;
use App\Models\Vendor;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TestDataSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        $pm = StaffProfile::first();
        $pmId = $pm?->id ?? 1;

        // ── Clients ──
        if (Client::count() === 0) {
            $client = Client::create([
                'client_code' => 'TEST-CLT-001',
                'company_name' => 'Test Client Sdn Bhd',
                'registration_no' => 'REG-123456',
                'tax_id' => 'TIN-123456',
                'buyer_type' => 'company',
                'phone' => '03-12345678',
                'email' => 'test@testclient.com',
                'status' => 'active',
            ]);
            ClientPIC::create([
                'client_id' => $client->id,
                'name' => 'Test PIC',
                'email' => 'pic@testclient.com',
                'phone' => '012-3456789',
                'job_title' => 'Manager',
                'is_primary' => true,
            ]);
        }

        // ── Vendors ──
        if (Vendor::count() === 0) {
            Vendor::create([
                'vendor_code' => 'TEST-VEN-001',
                'company_name' => 'Test Vendor Sdn Bhd',
                'registration_no' => 'VEN-REG-789',
                'tax_id' => 'VEN-TIN-789',
                'phone' => '03-87654321',
                'email' => 'vendor@testvendor.com',
                'status' => 'active',
            ]);
        }

        // ── Chart of Accounts ──
        if (ChartOfAccount::count() === 0) {
            ChartOfAccount::create(['code' => '1001', 'name' => 'Cash at Bank', 'type' => 'asset', 'category' => 'current_asset', 'is_active' => true]);
            ChartOfAccount::create(['code' => '1104', 'name' => 'Trade Receivables', 'type' => 'asset', 'category' => 'current_asset', 'is_active' => true]);
            ChartOfAccount::create(['code' => '2101', 'name' => 'Trade Payables', 'type' => 'liability', 'category' => 'current_liability', 'is_active' => true]);
            ChartOfAccount::create(['code' => '4101', 'name' => 'Revenue', 'type' => 'income', 'category' => 'operating_income', 'is_active' => true]);
            ChartOfAccount::create(['code' => '6101', 'name' => 'Salary Expense', 'type' => 'expense', 'category' => 'operating_expense', 'is_active' => true]);
        }

        // ── Projects + Phases + Tasks ──
        if (Project::count() === 0) {
            $project = Project::create([
                'name' => 'Test Project',
                'project_code' => 'TEST-PRJ-001',
                'client' => 'Test Client',
                'status' => 'active',
                'start_date' => $now->toDateString(),
                'end_date' => $now->addDays(30)->toDateString(),
                'budget_amount' => 100000,
                'project_manager_id' => $pmId,
            ]);
            $phase = Phase::create([
                'project_id' => $project->id,
                'name' => 'Test Phase',
                'order' => 1,
                'status' => 'pending',
            ]);
            Task::create([
                'phase_id' => $phase->id,
                'title' => 'Test Task',
                'description' => 'Task for automated test',
                'status' => 'todo',
                'priority' => 'medium',
                'assigned_to' => $pmId,
            ]);
        }

        // ── Subcontractors ──
        if (Subcontractor::count() === 0) {
            $sub = Subcontractor::create([
                'subcontractor_code' => 'TEST-SUB-001',
                'company_name' => 'Test Subcontractor Sdn Bhd',
                'registration_no' => 'SUB-REG-001',
                'tax_id' => 'SUB-TIN-001',
                'phone' => '03-11112222',
                'email' => 'sub@testsub.com',
                'status' => 'active',
            ]);
            SubcontractorPIC::create([
                'subcontractor_id' => $sub->id,
                'name' => 'Sub PIC',
                'phone' => '012-11112222',
                'is_primary' => true,
            ]);
            // Need a project_subcontractor before subcontractor_claim can reference it
            $ps = DB::table('project_subcontractors')->first();
            if ($ps) {
                SubcontractorClaim::create([
                    'project_subcontractor_id' => $ps->id,
                    'claim_number' => 'TEST-SC-001',
                    'claim_date' => $now->toDateString(),
                    'claimed_amount' => 5000,
                    'net_payable' => 5000,
                    'status' => 'draft',
                ]);
            }
        }

        // ── Self-Billed Invoices ──
        if (SelfBilledInvoice::count() === 0) {
            $supplier = Subcontractor::first() ?? DB::table('subcontractors')->first();
            SelfBilledInvoice::create([
                'invoice_number' => 'TEST-SBI-001',
                'supplier_id' => $supplier?->id ?? 1,
                'date' => $now->toDateString(),
                'due_date' => $now->addDays(30)->toDateString(),
                'status' => 'draft',
                'subtotal' => 1000,
                'sst' => 80,
                'retention' => 50,
                'total' => 1030,
                'created_by' => $pmId,
            ]);
        }

        // ── Leave Applications ──
        if (LeaveApplication::count() === 0) {
            LeaveApplication::create([
                'staff_id' => $pmId,
                'type' => 'annual',
                'start_date' => $now->toDateString(),
                'end_date' => $now->addDays(1)->toDateString(),
                'reason' => 'Test leave',
                'status' => 'pending',
            ]);
        }

        // ── Claims ──
        if (ExpenseClaim::count() === 0) {
            ExpenseClaim::create([
                'staff_id' => $pmId,
                'title' => 'Test Claim',
                'description' => 'Claim for automated test',
                'status' => 'pending',
                'total_amount' => 500,
                'submitted_date' => $now->toDateString(),
            ]);
        }

        // ── Assets ──
        if (Asset::count() === 0) {
            $asset = Asset::create([
                'name' => 'Test Asset',
                'asset_code' => 'TEST-ASST-001',
                'asset_type' => 'equipment',
                'make' => 'TestMake',
                'model' => 'TestModel',
                'purchase_date' => $now->toDateString(),
                'purchase_cost' => 10000,
                'current_value' => 10000,
                'status' => 'available',
            ]);
            AssetLicense::create([
                'asset_id' => $asset->id,
                'license_type' => 'software',
                'license_number' => 'LIC-001',
                'issuing_authority' => 'Test Vendor',
                'issue_date' => $now->toDateString(),
                'expiry_date' => $now->addYear()->toDateString(),
                'status' => 'active',
            ]);
            DB::table('asset_movements')->insert([
                'asset_id' => $asset->id,
                'movement_type' => 'assignment',
                'from_location' => 'Store',
                'to_location' => 'Site',
                'movement_date' => $now->toDateString(),
                'created_by' => $pmId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            DB::table('asset_services')->insert([
                'asset_id' => $asset->id,
                'service_type' => 'maintenance',
                'service_date' => $now->toDateString(),
                'next_service_date' => $now->addMonths(6)->toDateString(),
                'cost' => 500,
                'vendor' => 'Test Service Co',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── E-Invoice Credentials ──
        if (DB::table('einvoice_credentials')->count() === 0) {
            DB::table('einvoice_credentials')->insert([
                'label' => 'Test Credential',
                'client_id' => 'TEST-CLIENT',
                'client_secret' => 'TEST-SECRET',
                'environment' => 'test',
                'is_active' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }
}
