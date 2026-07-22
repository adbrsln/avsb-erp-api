<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\EInvoiceSubmissionLog;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SelfBilledInvoice;
use App\Models\StaffProfile;
use App\Models\Subcontractor;
use App\Services\DocumentGenerator;
use App\Services\EInvoiceClient;
use App\Services\EInvoiceDocumentBuilder;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SelfBilledInvoiceController extends Controller
{
    use PaginatedResponse;

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    private function getStaffId(Request $request): ?int
    {
        $user = $request->user();
        if (! $user || empty($user->email)) {
            return null;
        }
        $staff = StaffProfile::where('email', $user->email)->first();

        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = SelfBilledInvoice::with('supplier', 'project');

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('invoice_number', 'like', "%{$s}%")
                    ->orWhereHas('supplier', fn ($q2) => $q2->where('company_name', 'like', "%{$s}%"));
            });
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $countQuery = clone $query;
        $statuses = ['draft', 'pending_approval', 'approved', 'submitted', 'valid', 'error', 'cancelled', 'paid'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = (clone $countQuery)->where('status', $status)->count();
        }

        return $this->paginate($query, $params, ['counts' => $counts]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['supplier_id']) || ! Subcontractor::find($data['supplier_id'])) {
            return response()->json(['error' => 'Valid supplier (subcontractor) is required'], 422);
        }

        $data['invoice_number'] = (new NumberingService)->generate('self_billed_invoice');
        $data['status'] = 'draft';
        $data['created_by'] = $this->getStaffId($request);

        $item = SelfBilledInvoice::create(fillableData(new SelfBilledInvoice, $data));
        $item->load('supplier', 'project');

        try {
            $recipients = NotificationRecipientResolver::getApprovers('self-billed');
            $supplierName = $item->supplier?->company_name ?? 'Unknown';
            NotificationService::queueToMany(
                NotificationEvent::SELF_BILLED_CREATED,
                $recipients,
                [
                    'invoice_number' => $item->invoice_number ?? '',
                    'supplier' => $supplierName,
                    'total' => number_format($item->total ?? 0, 2),
                    'url' => '/approvals?type=self-billed',
                ],
                'App\\Models\\SelfBilledInvoice',
                $item->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: self-billed.created', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::with('supplier', 'project', 'approver', 'creator')
            ->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = SelfBilledInvoice::findOrFail($id);

        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft self-billed invoices can be edited'], 422);
        }

        unset($data['invoice_number'], $data['status']);
        $item->update(fillableData($item, $data));
        $item->load('supplier', 'project', 'approver', 'creator');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::findOrFail($id);

        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft self-billed invoices can be deleted'], 422);
        }

        $item->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::findOrFail($id);

        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft self-billed invoices can be approved'], 422);
        }

        $item->update([
            'status' => 'approved',
            'approved_by' => $this->getStaffId($request),
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        $item->load('supplier', 'project', 'approver', 'creator');

        try {
            $creator = StaffProfile::find($item->created_by);
            if ($creator) {
                NotificationService::queue(
                    NotificationEvent::SELF_BILLED_APPROVED,
                    $creator->email,
                    $creator->name,
                    [
                        'invoice_number' => $item->invoice_number ?? '',
                        'url' => '/finance/self-billed/'.$item->id,
                    ],
                    'App\\Models\\SelfBilledInvoice',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: self-billed.approved', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::findOrFail($id);

        if ($item->status !== 'approved') {
            return response()->json(['error' => 'Only approved self-billed invoices can be rejected'], 422);
        }

        $body = $request->all();
        $reason = trim($body['reason'] ?? '');

        $item->update([
            'status' => 'draft',
            'approved_by' => null,
            'approved_at' => null,
        ]);

        $item->load('supplier', 'project', 'approver', 'creator');

        try {
            $creator = StaffProfile::find($item->created_by);
            if ($creator && $creator->email) {
                NotificationService::queue(
                    NotificationEvent::SELF_BILLED_REJECTED,
                    $creator->email,
                    $creator->name ?? '',
                    [
                        'invoice_number' => $item->invoice_number ?? '',
                        'reason' => $reason ?? '',
                        'url' => '/finance/self-billed/'.$item->id,
                    ],
                    'App\\Models\\SelfBilledInvoice',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: self-billed.rejected', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::with('supplier')->findOrFail($id);

        if ($item->status !== 'approved') {
            return response()->json(['error' => 'Only approved self-billed invoices can be submitted'], 422);
        }

        try {
            $builder = new EInvoiceDocumentBuilder;
            $xml = $builder->buildSelfBilled($item);

            $uuid = $item->uuid;
            if (empty($uuid)) {
                $hex = bin2hex(random_bytes(16));
                $uuid = substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-4'.substr($hex, 13, 3)
                    .'-'.dechex(0x80 | (hexdec(substr($hex, 16, 1)) & 0x3F)).substr($hex, 17, 2)
                    .'-'.substr($hex, 20, 12);
            }

            $client = new EInvoiceClient;
            $result = $client->submitDocument(['documents' => [
                ['format' => 'XML', 'document' => $xml, 'documentHash' => hash('sha256', $xml)],
            ]]);

            $submissionUid = $result['submissionUid'] ?? '';

            $item->update([
                'uuid' => $uuid,
                'einvoice_xml' => $xml,
                'submission_status' => 'submitted',
                'submission_uid' => $submissionUid,
                'submitted_at' => date('Y-m-d H:i:s'),
                'last_submission_attempt' => date('Y-m-d H:i:s'),
                'submission_error' => null,
            ]);

            $item->load('supplier', 'project', 'approver', 'creator');

            try {
                $creator = StaffProfile::find($item->created_by);
                if ($creator) {
                    NotificationService::queue(
                        NotificationEvent::SELF_BILLED_SUBMITTED,
                        $creator->email,
                        $creator->name,
                        [
                            'invoice_number' => $item->invoice_number ?? '',
                            'url' => '/finance/self-billed/'.$item->id,
                        ],
                        'App\\Models\\SelfBilledInvoice',
                        $item->id
                    );
                }
            } catch (\Throwable $e) {
                Log::error('Notification failed: self-billed.submitted', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
            }

            return response()->json($item);
        } catch (\RuntimeException $e) {
            $item->update([
                'submission_status' => 'failed',
                'last_submission_attempt' => date('Y-m-d H:i:s'),
                'submission_error' => $e->getMessage(),
            ]);

            $item->load('supplier', 'project', 'approver', 'creator');

            return response()->json([
                'error' => $e->getMessage(),
                'data' => $item,
            ], 422);
        }
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = SelfBilledInvoice::findOrFail($id);

        if (! in_array($item->status, ['approved', 'valid', 'submitted'])) {
            return response()->json(['error' => 'Invoice status must be approved, valid, or submitted to mark as paid'], 422);
        }

        DB::beginTransaction();
        try {
            $item->update([
                'status' => 'paid',
                'paid_at' => date('Y-m-d H:i:s'),
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            $subcontractorCosts = ChartOfAccount::where('code', '5103')->first();
            $bankAccount = ChartOfAccount::where('code', '1102')->first();

            if ($subcontractorCosts && $bankAccount) {
                $totalAmount = round(($item->subtotal ?? 0) + ($item->sst ?? 0) - ($item->retention ?? 0), 2);

                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => date('Y-m-d'),
                    'description' => 'Self-billed invoice paid - '.($item->invoice_number ?? ''),
                    'reference_type' => 'self_billed_invoice',
                    'reference_id' => $item->id,
                    'status' => 'posted',
                    'posted_at' => date('Y-m-d H:i:s'),
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $subcontractorCosts->id,
                    'debit' => $totalAmount,
                    'description' => $item->invoice_number,
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $bankAccount->id,
                    'credit' => $totalAmount,
                    'description' => $item->invoice_number,
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Self-billed markPaid failed', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to mark as paid: '.$e->getMessage()], 500);
        }

        $item->load('supplier', 'project', 'approver', 'creator');

        try {
            $creator = StaffProfile::find($item->created_by);
            if ($creator) {
                NotificationService::queue(
                    NotificationEvent::SELF_BILLED_PAID,
                    $creator->email,
                    $creator->name,
                    [
                        'invoice_number' => $item->invoice_number ?? '',
                        'total' => number_format($item->total ?? 0, 2),
                        'url' => '/finance/self-billed/'.$item->id,
                    ],
                    'App\\Models\\SelfBilledInvoice',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: self-billed.paid', ['invoice_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function submitEInvoice(Request $request, int $id): JsonResponse
    {
        return $this->submit($request, $id);
    }

    public function submissionStatus(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::findOrFail($id);

        $logs = EInvoiceSubmissionLog::where('model_type', SelfBilledInvoice::class)
            ->where('model_id', $item->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'submission_status' => $item->submission_status,
            'submission_uid' => $item->submission_uid,
            'uuid' => $item->uuid,
            'submitted_at' => $item->submitted_at,
            'last_submission_attempt' => $item->last_submission_attempt,
            'submission_error' => $item->submission_error,
            'logs' => $logs,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $item = SelfBilledInvoice::findOrFail($id);

        if (empty($item->uuid)) {
            return response()->json(['error' => 'This invoice has not been submitted yet'], 422);
        }

        $data = $request->all();
        $reason = $data['reason'] ?? 'Cancelled by user';

        try {
            $client = new EInvoiceClient;
            $client->cancelDocument($item->uuid, $reason);

            $item->update(['submission_status' => 'cancelled']);
            $item->load('supplier', 'project', 'approver', 'creator');

            return response()->json($item);
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    public function download(Request $request, int $id): Response|JsonResponse
    {
        $item = SelfBilledInvoice::with('supplier')->findOrFail($id);
        $filename = ($item->invoice_number ?? 'self_billed').'.pdf';
        $path = 'documents/self-billed/'.$item->id.'.pdf';

        $pdf = (new DocumentGenerator)->selfBilled($item);
        if ($pdf === null) {
            return response()->json(['error' => 'Failed to generate PDF'], 500);
        }
        $this->storage->put($path, $pdf, 'application/pdf');

        $url = $this->storage->getPresignedUrl($path, 5, $filename);
        if ($url) {
            return response()->json(['url' => $url, 'filename' => $filename]);
        }

        $pdf = $this->storage->get($path);
        if ($pdf === null) {
            return response()->json(['error' => 'PDF not found'], 404);
        }

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Content-Length' => strlen($pdf),
        ]);
    }
}
