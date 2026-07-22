<?php

use App\Http\Controllers\AccountingController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApprovalController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\AssetLicenseController;
use App\Http\Controllers\AssetMovementController;
use App\Http\Controllers\AssetServiceController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BillController;
use App\Http\Controllers\BillPaymentController;
use App\Http\Controllers\BoardController;
use App\Http\Controllers\ChartOfAccountController;
use App\Http\Controllers\ClaimController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ClientPICController;
use App\Http\Controllers\CompanySettingController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractVariationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EInvoiceController;
use App\Http\Controllers\EisController;
use App\Http\Controllers\EPFController;
use App\Http\Controllers\FiscalPeriodController;
use App\Http\Controllers\GeocodeController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\LeaveController;
use App\Http\Controllers\LeaveGroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\PartTimeController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayrollController;
use App\Http\Controllers\PayRunController;
use App\Http\Controllers\PhaseController;
use App\Http\Controllers\PhaseStaffController;
use App\Http\Controllers\PhaseTemplateController;
use App\Http\Controllers\ProjectClaimController;
use App\Http\Controllers\ProjectClaimDocumentController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\ProjectDocumentController;
use App\Http\Controllers\ProjectGroupController;
use App\Http\Controllers\ProjectMaterialController;
use App\Http\Controllers\ProjectStaffPicController;
use App\Http\Controllers\ProjectSubcontractorController;
use App\Http\Controllers\ProjectTypeController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SelfBilledInvoiceController;
use App\Http\Controllers\ServiceCatalogController;
use App\Http\Controllers\ServiceTypeController;
use App\Http\Controllers\SocsoController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\SubcontractorClaimController;
use App\Http\Controllers\SubcontractorController;
use App\Http\Controllers\SubcontractorPICController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\TimecardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VendorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public Auth Routes (with rate limiting) ──
    Route::post('auth/login', [AuthController::class, 'login'])->middleware('throttle:10,60');
    Route::post('auth/register', [AuthController::class, 'register'])->middleware('throttle:5,3600');
    Route::post('auth/verify-password', [AuthController::class, 'verifyPassword'])->middleware('throttle:5,60');

    // ── Authenticated Routes ──
    Route::middleware('auth:sanctum')->group(function () {

        // ── Auth ──
        Route::get('auth/me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::put('auth/change-password', [AuthController::class, 'changePassword']);
        Route::post('auth/verify-password', [AuthController::class, 'verifyPassword']);

        // ── Staff ──
        Route::get('staff/me/profile', [StaffController::class, 'myProfile']);
        Route::get('staff', [StaffController::class, 'index']);
        Route::post('staff', [StaffController::class, 'store']);
        Route::get('staff/{id}', [StaffController::class, 'show']);
        Route::put('staff/{id}', [StaffController::class, 'update']);
        Route::delete('staff/{id}', [StaffController::class, 'destroy']);
        Route::post('staff/{id}/reset-password', [StaffController::class, 'resetPassword']);
        Route::post('staff/{id}/status', [StaffController::class, 'updateStatus']);
        Route::get('staff/{id}/projects', [StaffController::class, 'myProjects']);
        Route::get('staff/{id}/tasks', [StaffController::class, 'myTasks']);
        Route::get('staff/{id}/phases', [StaffController::class, 'projectPhases']);

        // ── Users ──
        Route::get('users', [UserController::class, 'index']);
        Route::post('users', [UserController::class, 'store']);
        Route::get('users/{id}', [UserController::class, 'show']);
        Route::put('users/{id}', [UserController::class, 'update']);
        Route::delete('users/{id}', [UserController::class, 'destroy']);
        Route::post('users/{id}/roles', [UserController::class, 'updateRoles']);
        Route::post('users/{id}/reset-password', [UserController::class, 'resetPassword']);

        // ── Clients ──
        Route::get('clients', [ClientController::class, 'index']);
        Route::post('clients', [ClientController::class, 'store']);
        Route::get('clients/{id}', [ClientController::class, 'show']);
        Route::put('clients/{id}', [ClientController::class, 'update']);
        Route::delete('clients/{id}', [ClientController::class, 'destroy']);
        Route::get('clients/{id}/pics', [ClientController::class, 'pics']);

        // ── Vendors ──
        Route::get('vendors', [VendorController::class, 'index']);
        Route::post('vendors', [VendorController::class, 'store']);
        Route::get('vendors/{id}', [VendorController::class, 'show']);
        Route::put('vendors/{id}', [VendorController::class, 'update']);
        Route::delete('vendors/{id}', [VendorController::class, 'destroy']);

        // ── Company Settings ──
        Route::get('company-settings', [CompanySettingController::class, 'show']);
        Route::put('company-settings', [CompanySettingController::class, 'update']);
        Route::post('company-settings/logo', [CompanySettingController::class, 'uploadLogo']);
        Route::delete('company-settings/logo', [CompanySettingController::class, 'deleteLogo']);
        Route::get('company-settings/logo', [CompanySettingController::class, 'serveLogo']);

        // ── Projects ──
        Route::get('projects/stats', [ProjectController::class, 'stats']);
        Route::get('projects', [ProjectController::class, 'index']);
        Route::post('projects', [ProjectController::class, 'store']);
        Route::get('projects/{id}', [ProjectController::class, 'show']);
        Route::put('projects/{id}', [ProjectController::class, 'update']);
        Route::delete('projects/{id}', [ProjectController::class, 'destroy']);
        Route::get('projects/{id}/activity-log', [ActivityLogController::class, 'projectLog']);

        // ── Project Documents ──
        Route::get('projects/{projectId}/documents', [ProjectDocumentController::class, 'index']);
        Route::post('projects/{projectId}/documents', [ProjectDocumentController::class, 'store']);
        Route::get('documents/{id}', [ProjectDocumentController::class, 'show']);
        Route::delete('documents/{id}', [ProjectDocumentController::class, 'destroy']);
        Route::get('documents/{id}/download', [ProjectDocumentController::class, 'download']);

        // ── Project Phases ──
        Route::get('project-phases', [PhaseController::class, 'index']);
        Route::post('project-phases', [PhaseController::class, 'store']);
        Route::get('project-phases/{id}', [PhaseController::class, 'show']);
        Route::put('project-phases/{id}', [PhaseController::class, 'update']);
        Route::delete('project-phases/{id}', [PhaseController::class, 'destroy']);
        Route::put('project-phases/{id}/status', [PhaseController::class, 'updateStatus']);
        Route::post('project-phases/{id}/start', [PhaseController::class, 'start']);
        Route::post('project-phases/{id}/complete', [PhaseController::class, 'complete']);
        Route::post('project-phases/reorder', [PhaseController::class, 'reorder']);
        Route::post('project-phases/reorder-batch', [PhaseController::class, 'reorderBatch']);
        Route::get('project-phases/{id}/comments', [PhaseController::class, 'comments']);
        Route::post('project-phases/{id}/comments', [PhaseController::class, 'addComment']);
        Route::get('project-phases/{id}/checklist-items', [PhaseController::class, 'checklistItems']);
        Route::get('project-phases/{id}/checklist-results', [PhaseController::class, 'checklistResults']);

        // ── Tasks ──
        Route::get('tasks', [TaskController::class, 'index']);
        Route::post('tasks', [TaskController::class, 'store']);
        Route::get('tasks/{id}', [TaskController::class, 'show']);
        Route::put('tasks/{id}', [TaskController::class, 'update']);
        Route::delete('tasks/{id}', [TaskController::class, 'destroy']);
        Route::post('tasks/{id}/start', [TaskController::class, 'start']);
        Route::post('tasks/{id}/pause', [TaskController::class, 'pause']);
        Route::post('tasks/{id}/resume', [TaskController::class, 'resume']);
        Route::post('tasks/{id}/complete', [TaskController::class, 'complete']);
        Route::post('tasks/{id}/sync-staff', [TaskController::class, 'syncStaff']);

        // ── Phase Templates ──
        Route::get('phase-templates', [PhaseTemplateController::class, 'index']);
        Route::post('phase-templates', [PhaseTemplateController::class, 'store']);
        Route::get('phase-templates/{id}', [PhaseTemplateController::class, 'show']);
        Route::put('phase-templates/{id}', [PhaseTemplateController::class, 'update']);
        Route::delete('phase-templates/{id}', [PhaseTemplateController::class, 'destroy']);

        // ── Project Types ──
        Route::get('project-types', [ProjectTypeController::class, 'index']);
        Route::post('project-types', [ProjectTypeController::class, 'store']);
        Route::get('project-types/{id}', [ProjectTypeController::class, 'show']);
        Route::put('project-types/{id}', [ProjectTypeController::class, 'update']);
        Route::delete('project-types/{id}', [ProjectTypeController::class, 'destroy']);
        Route::get('project-types/{id}/templates', [ProjectTypeController::class, 'templates']);
        Route::post('project-types/{id}/sync-templates', [ProjectTypeController::class, 'syncTemplates']);

        // ── Project Claims ──
        Route::get('projects/{projectId}/claims', [ProjectClaimController::class, 'index']);
        Route::post('projects/{projectId}/claims', [ProjectClaimController::class, 'store']);
        Route::get('project-claims/{id}', [ProjectClaimController::class, 'show']);
        Route::put('project-claims/{id}', [ProjectClaimController::class, 'update']);
        Route::delete('project-claims/{id}', [ProjectClaimController::class, 'destroy']);
        Route::post('project-claims/{id}/submit', [ProjectClaimController::class, 'submit']);
        Route::post('project-claims/{id}/approve', [ProjectClaimController::class, 'approve']);
        Route::post('project-claims/{id}/reject', [ProjectClaimController::class, 'reject']);
        Route::post('project-claims/{id}/mark-paid', [ProjectClaimController::class, 'markPaid']);
        Route::get('project-claims/{id}/documents', [ProjectClaimController::class, 'listDocuments']);
        Route::post('project-claims/{claimId}/documents', [ProjectClaimController::class, 'uploadDocument']);
        Route::get('project-claim-documents/{id}/download', [ProjectClaimDocumentController::class, 'download']);
        Route::delete('project-claim-documents/{id}', [ProjectClaimDocumentController::class, 'destroy']);

        // ── Subcontractors ──
        Route::get('subcontractors', [SubcontractorController::class, 'index']);
        Route::post('subcontractors', [SubcontractorController::class, 'store']);
        Route::get('subcontractors/{id}', [SubcontractorController::class, 'show']);
        Route::put('subcontractors/{id}', [SubcontractorController::class, 'update']);
        Route::delete('subcontractors/{id}', [SubcontractorController::class, 'destroy']);
        Route::get('subcontractors/{id}/projects', [SubcontractorController::class, 'projects']);
        Route::get('subcontractors/{id}/claims', [SubcontractorController::class, 'claims']);
        Route::get('subcontractors/{id}/pics', [SubcontractorPICController::class, 'index']);
        Route::post('subcontractors/{subcontractorId}/pics', [SubcontractorPICController::class, 'store']);
        Route::put('subcontractor-pics/{id}', [SubcontractorPICController::class, 'update']);
        Route::delete('subcontractor-pics/{id}', [SubcontractorPICController::class, 'destroy']);

        // ── Subcontractor Claims ──
        Route::get('subcontractor-claims', [SubcontractorClaimController::class, 'listAll']);
        Route::post('subcontractors/{subcontractorId}/claims', [SubcontractorClaimController::class, 'store']);
        Route::get('subcontractor-claims/{id}', [SubcontractorClaimController::class, 'show']);
        Route::put('subcontractor-claims/{id}', [SubcontractorClaimController::class, 'update']);
        Route::delete('subcontractor-claims/{id}', [SubcontractorClaimController::class, 'destroy']);
        Route::post('subcontractor-claims/{id}/submit', [SubcontractorClaimController::class, 'submit']);
        Route::post('subcontractor-claims/{id}/verify', [SubcontractorClaimController::class, 'verify']);
        Route::post('subcontractor-claims/{id}/approve', [SubcontractorClaimController::class, 'approve']);
        Route::post('subcontractor-claims/{id}/reject', [SubcontractorClaimController::class, 'reject']);
        Route::post('subcontractor-claims/{id}/mark-paid', [SubcontractorClaimController::class, 'markPaid']);
        Route::get('subcontractor-claims/{id}/documents', [SubcontractorClaimController::class, 'listDocuments']);
        Route::post('subcontractor-claims/{claimId}/documents', [SubcontractorClaimController::class, 'uploadDocument']);
        Route::get('subcontractor-claim-documents/{docId}/download', [SubcontractorClaimController::class, 'serveDocument']);
        Route::delete('subcontractor-claim-documents/{docId}', [SubcontractorClaimController::class, 'deleteDocument']);

        // ── Project Subcontractors ──
        Route::get('project-subcontractors/{id}', [ProjectSubcontractorController::class, 'show']);
        Route::post('projects/{projectId}/subcontractors', [ProjectSubcontractorController::class, 'store']);
        Route::delete('project-subcontractors/{id}', [ProjectSubcontractorController::class, 'destroy']);

        // ── Quotations ──
        Route::get('quotations', [QuoteController::class, 'index']);
        Route::post('quotations', [QuoteController::class, 'store']);
        Route::get('quotations/{id}', [QuoteController::class, 'show']);
        Route::put('quotations/{id}', [QuoteController::class, 'update']);
        Route::delete('quotations/{id}', [QuoteController::class, 'destroy']);
        Route::post('quotations/{id}/submit', [QuoteController::class, 'submit']);
        Route::post('quotations/{id}/convert', [QuoteController::class, 'convertToContract']);
        Route::post('quotations/{id}/generate-invoice', [QuoteController::class, 'generateInvoice']);
        Route::post('quotations/{id}/decline', [QuoteController::class, 'decline']);
        Route::post('quotations/{id}/revert-draft', [QuoteController::class, 'revertToDraft']);
        Route::post('quotations/{id}/accept', [QuoteController::class, 'accept']);

        // ── Contracts ──
        Route::get('contracts', [ContractController::class, 'index']);
        Route::post('contracts', [ContractController::class, 'store']);
        Route::get('contracts/{id}', [ContractController::class, 'show']);
        Route::put('contracts/{id}', [ContractController::class, 'update']);
        Route::delete('contracts/{id}', [ContractController::class, 'destroy']);
        Route::post('contracts/{id}/activate', [ContractController::class, 'activate']);
        Route::post('contracts/{id}/complete', [ContractController::class, 'complete']);
        Route::post('contracts/{id}/revert-draft', [ContractController::class, 'revertToDraft']);
        Route::post('contracts/{id}/generate-invoice', [ContractController::class, 'generateInvoice']);
        Route::get('contracts/{id}/variations', [ContractVariationController::class, 'index']);
        Route::post('contracts/{contractId}/variations', [ContractVariationController::class, 'store']);
        Route::get('contract-variations/{id}', [ContractVariationController::class, 'show']);
        Route::put('contract-variations/{id}', [ContractVariationController::class, 'update']);
        Route::delete('contract-variations/{id}', [ContractVariationController::class, 'destroy']);
        Route::post('contract-variations/{id}/approve', [ContractVariationController::class, 'approve']);
        Route::post('contract-variations/{id}/reject', [ContractVariationController::class, 'reject']);

        // ── Invoices ──
        Route::get('invoices', [InvoiceController::class, 'index']);
        Route::post('invoices', [InvoiceController::class, 'store']);
        Route::get('invoices/{id}', [InvoiceController::class, 'show']);
        Route::put('invoices/{id}', [InvoiceController::class, 'update']);
        Route::delete('invoices/{id}', [InvoiceController::class, 'destroy']);
        Route::post('invoices/{id}/issue', [InvoiceController::class, 'issue']);
        Route::post('invoices/{id}/mark-paid', [InvoiceController::class, 'markPaid']);
        Route::post('invoices/{id}/payments', [InvoiceController::class, 'storePayment']);
        Route::post('invoices/{id}/credit-note', [InvoiceController::class, 'creditNote']);
        Route::post('invoices/{id}/restore', [InvoiceController::class, 'restore']);
        Route::get('invoices/{id}/payments', [InvoiceController::class, 'payments']);
        Route::post('invoices/{id}/submit-einvoice', [InvoiceController::class, 'submitEInvoice']);
        Route::post('invoices/{id}/cancel-einvoice', [InvoiceController::class, 'cancelEInvoice']);
        Route::post('invoices/{id}/resubmit-einvoice', [InvoiceController::class, 'resubmitEInvoice']);

        // ── Self-Billed Invoices ──
        Route::get('self-billed-invoices', [SelfBilledInvoiceController::class, 'index']);
        Route::post('self-billed-invoices', [SelfBilledInvoiceController::class, 'store']);
        Route::get('self-billed-invoices/{id}', [SelfBilledInvoiceController::class, 'show']);
        Route::put('self-billed-invoices/{id}', [SelfBilledInvoiceController::class, 'update']);
        Route::delete('self-billed-invoices/{id}', [SelfBilledInvoiceController::class, 'destroy']);
        Route::post('self-billed-invoices/{id}/submit', [SelfBilledInvoiceController::class, 'submit']);
        Route::post('self-billed-invoices/{id}/approve', [SelfBilledInvoiceController::class, 'approve']);
        Route::post('self-billed-invoices/{id}/reject', [SelfBilledInvoiceController::class, 'reject']);
        Route::post('self-billed-invoices/{id}/mark-paid', [SelfBilledInvoiceController::class, 'markPaid']);

        // ── Payments Hub ──
        Route::get('payments', [PaymentController::class, 'index']);
        Route::post('payments/mark-paid', [PaymentController::class, 'markPaid']);

        // ── Attendance ──
        Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn']);
        Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut']);
        Route::get('attendance/today', [AttendanceController::class, 'today']);
        Route::get('attendance/my-projects', [AttendanceController::class, 'myProjects']);
        Route::get('attendance/records', [AttendanceController::class, 'records']);
        Route::get('attendance/summary', [AttendanceController::class, 'summary']);
        Route::get('attendance/export', [AttendanceController::class, 'exportCsv']);
        Route::post('attendance/{id}/clear-flag', [AttendanceController::class, 'clearFlag']);
        Route::get('attendance/{id}/photo', [AttendanceController::class, 'servePhoto']);

        // ── Leave ──
        Route::get('leaves', [LeaveController::class, 'index']);
        Route::post('leaves', [LeaveController::class, 'store']);
        Route::get('leaves/{id}', [LeaveController::class, 'show']);
        Route::put('leaves/{id}', [LeaveController::class, 'update']);
        Route::delete('leaves/{id}', [LeaveController::class, 'destroy']);
        Route::post('leaves/{id}/approve', [LeaveController::class, 'approve']);
        Route::post('leaves/{id}/reject', [LeaveController::class, 'reject']);
        Route::get('leaves/{id}/mc-document', [LeaveController::class, 'serveMcDocument']);

        // ── Leave Groups ──
        Route::get('leave-groups', [LeaveGroupController::class, 'index']);
        Route::post('leave-groups', [LeaveGroupController::class, 'store']);
        Route::get('leave-groups/{id}', [LeaveGroupController::class, 'show']);
        Route::put('leave-groups/{id}', [LeaveGroupController::class, 'update']);
        Route::delete('leave-groups/{id}', [LeaveGroupController::class, 'destroy']);
        Route::get('leave-groups/{id}/entitlements', [LeaveGroupController::class, 'entitlements']);
        Route::delete('leave-group-entitlements/{id}', [LeaveGroupController::class, 'deleteEntitlement']);
        Route::post('leave-balances/{id}/adjust', [LeaveGroupController::class, 'adjustBalance']);

        // ── Claims ──
        Route::get('claims', [ClaimController::class, 'index']);
        Route::post('claims', [ClaimController::class, 'store']);
        Route::get('claims/{id}', [ClaimController::class, 'show']);
        Route::put('claims/{id}', [ClaimController::class, 'update']);
        Route::delete('claims/{id}', [ClaimController::class, 'destroy']);
        Route::post('claims/{id}/approve', [ClaimController::class, 'approve']);
        Route::post('claims/{id}/reject', [ClaimController::class, 'reject']);
        Route::post('claims/{id}/mark-paid', [ClaimController::class, 'markPaid']);
        Route::get('claims/{id}/receipt', [ClaimController::class, 'serveReceipt']);
        Route::get('my-claims', [ClaimController::class, 'myClaims']);
        Route::post('my-claims', [ClaimController::class, 'storeMyClaim']);
        Route::get('my-claims/{id}', [ClaimController::class, 'showMyClaim']);
        Route::post('my-claims/{id}/upload-receipt', [ClaimController::class, 'uploadReceipt']);

        // ── Timecards ──
        Route::get('timecards', [TimecardController::class, 'index']);
        Route::post('timecards', [TimecardController::class, 'store']);
        Route::get('timecards/{id}', [TimecardController::class, 'show']);
        Route::put('timecards/{id}', [TimecardController::class, 'update']);
        Route::delete('timecards/{id}', [TimecardController::class, 'destroy']);
        Route::post('timecards/{id}/approve', [TimecardController::class, 'approve']);
        Route::post('timecards/{id}/reject', [TimecardController::class, 'reject']);

        // ── Payroll ──
        Route::get('payroll/calculate', [PayrollController::class, 'calculate']);
        Route::get('payroll/periods', [PayrollController::class, 'listPeriods']);
        Route::post('payroll/periods', [PayrollController::class, 'createPeriod']);
        Route::get('payroll/periods/{id}', [PayrollController::class, 'getPeriodItem']);
        Route::get('payroll/periods/{id}/items', [PayrollController::class, 'getPeriodItems']);
        Route::post('payroll/periods/{id}/process', [PayrollController::class, 'processPeriod']);
        Route::post('payroll/periods/{id}/process-part-time', [PayrollController::class, 'processPartTime']);
        Route::put('payroll/periods/{id}/close', [PayrollController::class, 'closePeriod']);
        Route::put('payroll/periods/{id}/reopen', [PayrollController::class, 'reopenPeriod']);
        Route::post('payroll/periods/{id}/bulk-mark-paid', [PayrollController::class, 'bulkMarkPaid']);
        Route::post('payroll/items/{id}/confirm', [PayrollController::class, 'confirmItem']);
        Route::post('payroll/items/{id}/mark-paid', [PayrollController::class, 'markItemPaid']);
        Route::get('payroll/items/{id}/adjustments', [PayrollController::class, 'getItemAdjustments']);
        Route::post('payroll/items/{id}/adjustments', [PayrollController::class, 'createItemAdjustment']);
        Route::delete('payroll/adjustments/{id}', [PayrollController::class, 'deleteItemAdjustment']);
        Route::post('payroll/items/{id}/recalculate', [PayrollController::class, 'recalculateItem']);
        Route::get('my-payslips', [PayrollController::class, 'myPayslips']);

        // ── Part Time ──
        Route::get('part-time/staff', [PartTimeController::class, 'staff']);
        Route::post('part-time/hours', [PartTimeController::class, 'hours']);
        Route::get('part-time/pay', [PartTimeController::class, 'pay']);

        // ── Assets ──
        Route::get('assets', [AssetController::class, 'index']);
        Route::post('assets', [AssetController::class, 'store']);
        Route::get('assets/types', [AssetController::class, 'types']);
        Route::get('assets/{id}', [AssetController::class, 'show']);
        Route::put('assets/{id}', [AssetController::class, 'update']);
        Route::delete('assets/{id}', [AssetController::class, 'destroy']);
        Route::get('assets/by-code/{code}', [AssetController::class, 'findByCode']);
        Route::get('assets/{id}/licenses', [AssetLicenseController::class, 'index']);
        Route::post('assets/{assetId}/licenses', [AssetLicenseController::class, 'store']);
        Route::put('asset-licenses/{id}', [AssetLicenseController::class, 'update']);
        Route::delete('asset-licenses/{id}', [AssetLicenseController::class, 'destroy']);
        Route::get('asset-licenses/{id}/download', [AssetLicenseController::class, 'download']);
        Route::get('assets/{id}/movements', [AssetMovementController::class, 'index']);
        Route::post('assets/{assetId}/movements', [AssetMovementController::class, 'store']);
        Route::delete('asset-movements/{id}', [AssetMovementController::class, 'destroy']);
        Route::get('assets/{id}/services', [AssetServiceController::class, 'index']);
        Route::post('assets/{assetId}/services', [AssetServiceController::class, 'store']);
        Route::put('asset-services/{id}', [AssetServiceController::class, 'update']);
        Route::delete('asset-services/{id}', [AssetServiceController::class, 'destroy']);
        Route::get('asset-services/{id}/download', [AssetServiceController::class, 'download']);

        // ── Purchasing ──
        Route::get('purchase-orders', [PurchaseOrderController::class, 'index']);
        Route::post('purchase-orders', [PurchaseOrderController::class, 'store']);
        Route::get('purchase-orders/{id}', [PurchaseOrderController::class, 'show']);
        Route::put('purchase-orders/{id}', [PurchaseOrderController::class, 'update']);
        Route::delete('purchase-orders/{id}', [PurchaseOrderController::class, 'destroy']);
        Route::post('purchase-orders/{id}/receive', [PurchaseOrderController::class, 'receive']);
        Route::get('bills', [BillController::class, 'index']);
        Route::post('bills', [BillController::class, 'store']);
        Route::get('bills/{id}', [BillController::class, 'show']);
        Route::put('bills/{id}', [BillController::class, 'update']);
        Route::delete('bills/{id}', [BillController::class, 'destroy']);
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::post('inventory', [InventoryController::class, 'store']);
        Route::get('inventory/{id}', [InventoryController::class, 'show']);
        Route::put('inventory/{id}', [InventoryController::class, 'update']);
        Route::delete('inventory/{id}', [InventoryController::class, 'destroy']);
        Route::get('inventory/{id}/transactions', [InventoryController::class, 'transactions']);
        Route::post('inventory/{id}/adjust-stock', [InventoryController::class, 'adjustStock']);

        // ── Accounting ──
        Route::get('chart-of-accounts', [ChartOfAccountController::class, 'index']);
        Route::post('chart-of-accounts', [ChartOfAccountController::class, 'store']);
        Route::put('chart-of-accounts/{id}', [ChartOfAccountController::class, 'update']);
        Route::delete('chart-of-accounts/{id}', [ChartOfAccountController::class, 'destroy']);
        Route::get('chart-of-accounts/{id}/usage', [ChartOfAccountController::class, 'usage']);
        Route::get('accounting/journal-entries', [AccountingController::class, 'listJournalEntries']);
        Route::post('accounting/journal-entries', [AccountingController::class, 'storeJournalEntry']);
        Route::get('accounting/journal-entries/{id}', [AccountingController::class, 'getJournalEntry']);
        Route::post('accounting/journal-entries/{id}/post', [AccountingController::class, 'postJournalEntry']);
        Route::get('accounting/trial-balance', [AccountingController::class, 'trialBalance']);
        Route::get('accounting/balance-sheet', [AccountingController::class, 'balanceSheet']);
        Route::get('accounting/general-ledger', [AccountingController::class, 'generalLedger']);
        Route::get('accounting/profit-loss', [AccountingController::class, 'profitLoss']);
        Route::get('accounting/ar-aging', [AccountingController::class, 'arAging']);
        Route::get('accounting/ap-aging', [AccountingController::class, 'apAging']);

        // ── Fiscal Periods ──
        Route::get('fiscal-periods', [FiscalPeriodController::class, 'index']);
        Route::post('fiscal-periods', [FiscalPeriodController::class, 'store']);
        Route::get('fiscal-periods/{id}', [FiscalPeriodController::class, 'show']);
        Route::put('fiscal-periods/{id}', [FiscalPeriodController::class, 'update']);
        Route::delete('fiscal-periods/{id}', [FiscalPeriodController::class, 'destroy']);
        Route::post('fiscal-periods/{id}/close', [FiscalPeriodController::class, 'close']);
        Route::post('fiscal-periods/{id}/reopen', [FiscalPeriodController::class, 'reopen']);

        // ── Notifications ──
        Route::get('notifications/read', [NotificationController::class, 'markAllRead']);
        Route::get('notifications', [NotificationController::class, 'index']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'markRead']);
        Route::delete('notifications/read', [NotificationController::class, 'deleteRead']);
        Route::delete('notifications/{id}', [NotificationController::class, 'destroy']);

        // ── Activity Log ──
        Route::get('activity-logs', [ActivityLogController::class, 'index']);
        Route::get('activity-logs/{id}', [ActivityLogController::class, 'show']);

        // ── System ──
        Route::get('system/ping', function () {
            return response()->json(['pong' => true, 'time' => now()->toIso8601String()]);
        });

        // ── Approvals ──
        Route::get('approvals', [ApprovalController::class, 'index']);

        // ── Dashboard ──
        Route::get('dashboard/summary', [DashboardController::class, 'summary']);

        // ── Board ──
        Route::get('board/summary', [BoardController::class, 'summary']);
        Route::get('board/projects', [BoardController::class, 'projects']);
        Route::get('board/projects/{id}', [BoardController::class, 'project']);

        // ── Search ──
        Route::get('search', [SearchController::class, 'search']);

        // ── Client PICs ──
        Route::get('clients/{clientId}/pics', [ClientPICController::class, 'index']);
        Route::post('clients/{clientId}/pics', [ClientPICController::class, 'store']);
        Route::put('client-pics/{id}', [ClientPICController::class, 'update']);
        Route::delete('client-pics/{id}', [ClientPICController::class, 'destroy']);

        // ── Project Staff PICs ──
        Route::get('projects/{projectId}/staff-pics', [ProjectStaffPicController::class, 'index']);
        Route::post('projects/{projectId}/staff-pics', [ProjectStaffPicController::class, 'store']);
        Route::delete('project-staff-pics/{id}', [ProjectStaffPicController::class, 'destroy']);

        // ── Project Materials ──
        Route::get('projects/{projectId}/materials', [ProjectMaterialController::class, 'index']);
        Route::post('projects/{projectId}/materials', [ProjectMaterialController::class, 'store']);
        Route::delete('project-materials/{id}', [ProjectMaterialController::class, 'destroy']);

        // ── Project Groups ──
        Route::get('project-groups', [ProjectGroupController::class, 'index']);
        Route::post('project-groups', [ProjectGroupController::class, 'store']);
        Route::get('project-groups/{id}', [ProjectGroupController::class, 'show']);
        Route::put('project-groups/{id}', [ProjectGroupController::class, 'update']);
        Route::delete('project-groups/{id}', [ProjectGroupController::class, 'destroy']);

        // ── Phase Staff ──
        Route::get('project-phases/{phaseId}/staff', [PhaseStaffController::class, 'index']);
        Route::post('project-phases/{phaseId}/staff', [PhaseStaffController::class, 'sync']);

        // ── Project Cost Summary ──
        Route::get('projects/{id}/cost-summary', [ProjectController::class, 'costSummary']);

        // ── Project Generate Invoice ──
        Route::post('projects/{id}/generate-invoice', [ProjectController::class, 'generateInvoice']);

        // ── Project Subcontractors ──
        Route::put('project-subcontractors/{id}', [ProjectSubcontractorController::class, 'update']);
        Route::post('project-subcontractors/{id}/release-retention', [ProjectSubcontractorController::class, 'releaseRetention']);

        // ── Invoice Downloads ──
        Route::get('invoices/{id}/download', [InvoiceController::class, 'download']);
        Route::get('quotations/{id}/download', [QuoteController::class, 'download']);
        Route::get('contracts/{id}/download', [ContractController::class, 'download']);
        Route::get('self-billed-invoices/{id}/download', [SelfBilledInvoiceController::class, 'download']);

        // ── Receipts ──
        Route::get('invoices/{invoiceId}/receipts', [ReceiptController::class, 'index']);
        Route::get('receipts/{id}/download', [ReceiptController::class, 'download']);

        // ── Bill Payments ──
        Route::get('bills/{billId}/payments', [BillPaymentController::class, 'index']);
        Route::post('bills/{billId}/payments', [BillPaymentController::class, 'store']);

        // ── E-Invoice ──
        Route::get('einvoice/settings', [EInvoiceController::class, 'settings']);
        Route::put('einvoice/settings', [EInvoiceController::class, 'updateSettings']);
        Route::post('einvoice/test-connection', [EInvoiceController::class, 'testConnection']);
        Route::get('einvoice/tax-codes', [EInvoiceController::class, 'getTaxCodes']);
        Route::post('einvoice/tax-codes', [EInvoiceController::class, 'manageTaxCodes']);
        Route::post('einvoice/upload-cert', [EInvoiceController::class, 'uploadCert']);
        Route::post('einvoice/webhook', [EInvoiceController::class, 'webhook']);

        // ── Self-Billed E-Invoice ──
        Route::post('self-billed-invoices/{id}/submit-einvoice', [SelfBilledInvoiceController::class, 'submitEInvoice']);
        Route::get('self-billed-invoices/{id}/einvoice-status', [SelfBilledInvoiceController::class, 'einvoiceStatus']);
        Route::post('self-billed-invoices/{id}/cancel-einvoice', [SelfBilledInvoiceController::class, 'cancelEInvoice']);

        // ── EPF / SOCSO / EIS Calculators ──
        Route::get('epf/schedules', [EPFController::class, 'schedules']);
        Route::post('epf/calculate', [EPFController::class, 'calculate']);
        Route::post('socso/calculate', [SocsoController::class, 'calculate']);
        Route::get('socso/tiers', [SocsoController::class, 'listTiers']);
        Route::post('eis/calculate', [EisController::class, 'calculate']);
        Route::get('eis/tiers', [EisController::class, 'listTiers']);

        // ── Service Catalog ──
        Route::get('service-items', [ServiceCatalogController::class, 'index']);
        Route::post('service-items', [ServiceCatalogController::class, 'store']);
        Route::get('service-items/{id}', [ServiceCatalogController::class, 'show']);
        Route::put('service-items/{id}', [ServiceCatalogController::class, 'update']);
        Route::delete('service-items/{id}', [ServiceCatalogController::class, 'destroy']);

        // ── Service Types ──
        Route::get('service-types', [ServiceTypeController::class, 'index']);
        Route::get('service-types/{id}', [ServiceTypeController::class, 'show']);

        // ── Notification Preferences ──
        Route::get('notification-preferences', [NotificationPreferenceController::class, 'index']);
        Route::put('notification-preferences', [NotificationPreferenceController::class, 'update']);
        Route::put('notification-preferences/bulk', [NotificationPreferenceController::class, 'bulkUpdate']);

        // ── Push Subscriptions ──
        Route::post('push/subscribe', [PushSubscriptionController::class, 'subscribe']);
        Route::post('push/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);

        // ── Pay Runs ──
        Route::get('pay-runs', [PayRunController::class, 'index']);
        Route::post('pay-runs', [PayRunController::class, 'store']);
        Route::get('pay-runs/{id}', [PayRunController::class, 'show']);
        Route::put('pay-runs/{id}', [PayRunController::class, 'update']);
        Route::delete('pay-runs/{id}', [PayRunController::class, 'destroy']);
        Route::post('pay-runs/{id}/mark-paid', [PayRunController::class, 'markPaid']);

        // ── Geocoding ──
        Route::get('geocode/search', [GeocodeController::class, 'search']);
        Route::get('geocode/reverse', [GeocodeController::class, 'reverse']);

        // ── Leave Balance ──
        Route::post('staff/{staffId}/seed-leave-balance', [LeaveGroupController::class, 'seedBalance']);
        Route::post('staff/{staffId}/carry-forward', [LeaveGroupController::class, 'carryForward']);

        // ── System ──
        Route::get('system/health', [SystemController::class, 'health']);
        Route::get('system/diagnostics', [SystemController::class, 'diagnostics']);
        Route::post('system/test-mail', [SystemController::class, 'testMail']);
        Route::post('system/test-push', [SystemController::class, 'testPush']);
    });
});
