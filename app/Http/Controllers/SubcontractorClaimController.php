<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\BillItem;
use App\Models\BillPayment;
use App\Models\ChartOfAccount;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\ProjectSubcontractor;
use App\Models\StaffProfile;
use App\Models\SubcontractorClaim;
use App\Models\SubcontractorClaimDocument;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubcontractorClaimController extends Controller
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
        if (! $user || ! $user->email) {
            return null;
        }

        $staff = StaffProfile::where('email', $user->email)->first();

        return $staff ? (int) $staff->id : null;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $params = $request->query();
        $query = SubcontractorClaim::with('projectSubcontractor.subcontractor', 'projectSubcontractor.project')
            ->where('project_subcontractor_id', $id)
            ->orderByDesc('created_at');

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        return $this->paginate($query, $params);
    }

    public function listAll(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = SubcontractorClaim::with('projectSubcontractor.subcontractor', 'projectSubcontractor.project')
            ->orderByDesc('created_at');

        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('claim_number', 'like', "%{$s}%")
                    ->orWhereHas('projectSubcontractor.subcontractor', function ($sq) use ($s) {
                        $sq->where('company_name', 'like', "%{$s}%");
                    })
                    ->orWhereHas('projectSubcontractor.project', function ($sq) use ($s) {
                        $sq->where('name', 'like', "%{$s}%");
                    });
            });
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $assignment = ProjectSubcontractor::findOrFail($id);
        $data = $request->all();

        if (empty($data['claimed_amount'])) {
            return response()->json(['error' => 'claimed_amount is required'], 422);
        }

        $claimedAmount = (float) $data['claimed_amount'];
        $retentionPct = (float) ($data['retention_pct'] ?? $assignment->retention_pct);
        $retentionDeducted = round($claimedAmount * $retentionPct / 100, 2);
        $netPayable = round($claimedAmount - $retentionDeducted, 2);
        $previousPaid = (float) ($data['previous_paid'] ?? 0);
        $currentDue = round($netPayable - $previousPaid, 2);

        $claim = SubcontractorClaim::create([
            'project_subcontractor_id' => $assignment->id,
            'claim_number' => (new NumberingService)->generate('subcontractor_claim'),
            'claim_date' => $data['claim_date'] ?? Carbon::now()->format('Y-m-d'),
            'period_start' => $data['period_start'] ?? null,
            'period_end' => $data['period_end'] ?? null,
            'work_done_pct' => $data['work_done_pct'] ?? null,
            'cumulative_pct' => $data['cumulative_pct'] ?? null,
            'claimed_amount' => $claimedAmount,
            'retention_deducted' => $retentionDeducted,
            'net_payable' => $netPayable,
            'previous_paid' => $previousPaid,
            'current_due' => $currentDue,
            'status' => 'draft',
            'notes' => $data['notes'] ?? null,
        ]);

        $claim->load('projectSubcontractor');

        return response()->json($claim, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::with('projectSubcontractor', 'documents')->findOrFail($id);

        return response()->json($claim);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        if ($claim->status !== 'draft') {
            return response()->json(['error' => 'Only draft claims can be edited'], 422);
        }

        $data = $request->all();

        if (isset($data['claimed_amount'])) {
            $claimedAmount = (float) $data['claimed_amount'];
            $retentionPct = (float) ($data['retention_pct'] ?? $claim->retention_pct ?? 0);
            $retentionDeducted = round($claimedAmount * $retentionPct / 100, 2);
            $netPayable = round($claimedAmount - $retentionDeducted, 2);
            $previousPaid = (float) ($data['previous_paid'] ?? $claim->previous_paid);
            $currentDue = round($netPayable - $previousPaid, 2);

            $data['retention_deducted'] = $retentionDeducted;
            $data['net_payable'] = $netPayable;
            $data['previous_paid'] = $previousPaid;
            $data['current_due'] = $currentDue;
        }

        $claim->update(fillableData($claim, $data));
        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        if ($claim->status !== 'draft') {
            return response()->json(['error' => 'Only draft claims can be deleted'], 422);
        }

        $claim->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        if ($claim->status !== 'draft') {
            return response()->json(['error' => 'Only draft claims can be submitted'], 422);
        }

        $claim->update([
            'status' => 'submitted',
            'submitted_by' => $this->getStaffId($request),
            'submitted_at' => Carbon::now(),
        ]);

        try {
            $recipients = NotificationRecipientResolver::getApprovers('subcon');
            $assignment = $claim->projectSubcontractor;
            $subcontractorName = $assignment?->subcontractor?->company_name ?? 'Unknown';
            NotificationService::queueToMany(
                NotificationEvent::SUBCON_CLAIM_SUBMITTED,
                $recipients,
                [
                    'subcontractor' => $subcontractorName,
                    'claim_number' => $claim->claim_number ?? '',
                    'amount' => number_format($claim->claimed_amount, 2),
                    'url' => '/approvals?type=subcon-claim',
                ],
                'App\\Models\\SubcontractorClaim',
                $claim->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: subcon-claim.submitted', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    public function verify(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        if ($claim->status !== 'submitted') {
            return response()->json(['error' => 'Only submitted claims can be verified'], 422);
        }

        $claim->update([
            'status' => 'verified',
            'verified_by' => $this->getStaffId($request),
            'verified_at' => Carbon::now(),
        ]);

        try {
            $recipients = NotificationRecipientResolver::getByRole(['admin', 'finance']);
            $assignment = $claim->projectSubcontractor;
            $subcontractorName = $assignment?->subcontractor?->company_name ?? 'Unknown';
            NotificationService::queueToMany(
                NotificationEvent::SUBCON_CLAIM_VERIFIED,
                $recipients,
                [
                    'subcontractor' => $subcontractorName,
                    'claim_number' => $claim->claim_number ?? '',
                    'amount' => number_format($claim->claimed_amount, 2),
                    'url' => '/subcontractors',
                ],
                'App\\Models\\SubcontractorClaim',
                $claim->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: subcon-claim.verified', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);
        $data = $request->all();

        $allowedStatuses = ['draft', 'submitted', 'verified'];
        if (! in_array($claim->status, $allowedStatuses)) {
            return response()->json(['error' => 'Claim cannot be rejected in its current status'], 422);
        }

        $claim->update([
            'status' => 'rejected',
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        try {
            $submitter = StaffProfile::find($claim->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::SUBCON_CLAIM_REJECTED,
                    $submitter->email,
                    $submitter->name,
                    [
                        'claim_number' => $claim->claim_number ?? '',
                        'amount' => number_format($claim->claimed_amount, 2),
                        'reason' => $claim->rejection_reason ?? '',
                        'url' => '/subcontractors',
                    ],
                    'App\\Models\\SubcontractorClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: subcon-claim.rejected', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        if ($claim->status !== 'verified') {
            return response()->json(['error' => 'Only verified claims can be approved'], 422);
        }

        $claim->update([
            'status' => 'approved',
            'approved_by' => $this->getStaffId($request),
            'approved_at' => Carbon::now(),
        ]);

        // Proper AP flow: approved claim becomes an AP bill (subcontractor as bill source)
        $this->ensureClaimBill($claim);

        try {
            $submitter = StaffProfile::find($claim->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::SUBCON_CLAIM_APPROVED,
                    $submitter->email,
                    $submitter->name,
                    [
                        'claim_number' => $claim->claim_number ?? '',
                        'amount' => number_format($claim->claimed_amount, 2),
                        'url' => '/subcontractors',
                    ],
                    'App\\Models\\SubcontractorClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: subcon-claim.approved', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::with('projectSubcontractor')->findOrFail($id);

        if ($claim->status !== 'approved') {
            return response()->json(['error' => 'Only approved claims can be marked paid'], 422);
        }

        $data = $request->all();

        DB::beginTransaction();
        try {
            $claim->update([
                'status' => 'paid',
                'paid_at' => Carbon::now(),
                'payment_reference' => $data['payment_reference'] ?? null,
            ]);

            $assignment = $claim->projectSubcontractor;
            if ($assignment) {
                $assignment->increment('retention_amount', $claim->retention_deducted);
            }

            // Pay the claim against its AP bill (created at approval)
            $bill = $this->ensureClaimBill($claim);
            $apAccount = ChartOfAccount::where('code', '2101')->first();
            $retentionAccount = ChartOfAccount::where('code', '2109')->first();
            $bankAccount = ChartOfAccount::where('code', '1102')->first();

            if ($bill && $apAccount && $bankAccount && ! BillPayment::where('bill_id', $bill->id)->exists()) {
                BillPayment::create([
                    'bill_id' => $bill->id,
                    'amount' => $claim->net_payable,
                    'payment_date' => Carbon::now()->format('Y-m-d'),
                    'debit_account_id' => $apAccount->id,
                    'credit_account_id' => $bankAccount->id,
                    'payment_reference' => $claim->claim_number,
                ]);

                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => Carbon::now()->format('Y-m-d'),
                    'description' => 'Subcontractor claim payment - '.$claim->claim_number,
                    'reference_type' => 'bill_payment',
                    'reference_id' => $bill->id,
                    'status' => 'posted',
                    'posted_at' => Carbon::now(),
                    'created_by' => $this->getStaffId($request),
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $apAccount->id,
                    'debit' => $claim->net_payable,
                    'description' => $claim->claim_number.' - AP payment',
                ]);

                JournalEntryLine::create([
                    'journal_entry_id' => $je->id,
                    'account_id' => $bankAccount->id,
                    'credit' => $claim->net_payable,
                    'description' => $claim->claim_number.' - Bank payment',
                ]);

                if ($retentionAccount && $claim->retention_deducted > 0) {
                    $retentionJe = JournalEntry::create([
                        'entry_number' => (new NumberingService)->generate('journal'),
                        'entry_date' => Carbon::now()->format('Y-m-d'),
                        'description' => 'Retention held - '.$claim->claim_number,
                        'reference_type' => 'bill_payment',
                        'reference_id' => $bill->id,
                        'status' => 'posted',
                        'posted_at' => Carbon::now(),
                        'created_by' => $this->getStaffId($request),
                    ]);

                    JournalEntryLine::create([
                        'journal_entry_id' => $retentionJe->id,
                        'account_id' => $apAccount->id,
                        'debit' => $claim->retention_deducted,
                        'description' => $claim->claim_number.' - Retention held',
                    ]);

                    JournalEntryLine::create([
                        'journal_entry_id' => $retentionJe->id,
                        'account_id' => $retentionAccount->id,
                        'credit' => $claim->retention_deducted,
                        'description' => $claim->claim_number.' - Retention',
                    ]);
                }

                $bill->update([
                    'paid_amount' => $claim->net_payable,
                    'balance' => $claim->retention_deducted,
                    'status' => $claim->retention_deducted > 0 ? 'partially_paid' : 'paid',
                ]);
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error('Subcontractor claim markPaid failed', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to mark claim as paid: '.$e->getMessage()], 500);
        }

        try {
            $submitter = StaffProfile::find($claim->submitted_by);
            if ($submitter) {
                NotificationService::queue(
                    NotificationEvent::SUBCON_CLAIM_PAID,
                    $submitter->email,
                    $submitter->name,
                    [
                        'claim_number' => $claim->claim_number ?? '',
                        'amount' => number_format($claim->claimed_amount, 2),
                        'url' => '/subcontractors',
                    ],
                    'App\\Models\\SubcontractorClaim',
                    $claim->id
                );
            }
            $subEmail = NotificationRecipientResolver::getSubcontractorEmail($claim->projectSubcontractor?->subcontractor_id);
            if ($subEmail) {
                NotificationService::queue(
                    NotificationEvent::SUBCON_CLAIM_PAID,
                    $subEmail['email'],
                    $subEmail['name'],
                    [
                        'claim_number' => $claim->claim_number ?? '',
                        'amount' => number_format($claim->claimed_amount, 2),
                        'url' => '/subcontractors',
                    ],
                    'App\\Models\\SubcontractorClaim',
                    $claim->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: subcon-claim.paid', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        $claim->load('projectSubcontractor');

        return response()->json($claim);
    }

    /**
     * Create the AP bill for an approved claim if it does not exist yet
     * (bill_number = claim_number, subcontractor as bill source) plus the
     * receive JE (DR subcontractor cost 5103 / CR AP 2101).
     */
    private function ensureClaimBill(SubcontractorClaim $claim): ?Bill
    {
        $bill = Bill::where('bill_number', $claim->claim_number)->first();
        if ($bill) {
            return $bill;
        }

        $assignment = $claim->projectSubcontractor;
        $billDate = $claim->claim_date?->format('Y-m-d') ?? Carbon::now()->format('Y-m-d');

        $bill = Bill::create([
            'bill_number' => $claim->claim_number,
            'subcontractor_id' => $assignment?->subcontractor_id,
            'bill_date' => $billDate,
            'due_date' => Carbon::parse($billDate)->addDays(30)->format('Y-m-d'),
            'status' => 'unpaid',
            'subtotal' => $claim->claimed_amount,
            'tax' => 0,
            'total' => $claim->claimed_amount,
            'paid_amount' => 0,
            'balance' => $claim->claimed_amount,
        ]);

        $subName = $assignment?->subcontractor?->company_name ?? 'Subcontractor';
        BillItem::create([
            'bill_id' => $bill->id,
            'description' => 'Claim '.$claim->claim_number.' — '.$subName,
            'unit' => 'Lot',
            'quantity' => 1,
            'unit_price' => $claim->claimed_amount,
            'total' => $claim->claimed_amount,
        ]);

        $costAccount = ChartOfAccount::where('code', '5103')->first();
        $apAccount = ChartOfAccount::where('code', '2101')->first();
        if ($costAccount && $apAccount) {
            $je = JournalEntry::create([
                'entry_number' => (new NumberingService)->generate('journal'),
                'entry_date' => $billDate,
                'description' => 'Bill received - '.$claim->claim_number,
                'reference_type' => 'bill',
                'reference_id' => $bill->id,
                'status' => 'posted',
                'posted_at' => Carbon::now(),
            ]);
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $costAccount->id,
                'debit' => $claim->claimed_amount,
                'description' => $claim->claim_number.' - Subcontractor costs',
            ]);
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $apAccount->id,
                'credit' => $claim->claimed_amount,
                'description' => $claim->claim_number.' - AP',
            ]);
        }

        return $bill;
    }

    public function uploadDocument(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);

        $file = $request->file('file');
        if (! $file) {
            return response()->json(['error' => 'No file uploaded'], 422);
        }

        $body = $request->all();
        $originalName = $file->getClientOriginalName();

        $docError = FileStorageService::validateUpload($file);
        if ($docError) {
            return response()->json(['error' => $docError], 422);
        }

        $mimeType = $file->getClientMimeType() ?: 'application/octet-stream';
        $fileSize = $file->getSize();
        $ext = pathinfo($originalName, PATHINFO_EXTENSION);
        $storedName = uniqid('scl_', true).'.'.$ext;

        $relativePath = 'uploads/subcontractor-claims/'.$claim->id.'/'.$storedName;
        $this->storage->put($relativePath, file_get_contents($file->getPathname()), $mimeType);

        $doc = SubcontractorClaimDocument::create([
            'subcontractor_claim_id' => $claim->id,
            'uploaded_by' => $this->getStaffId($request),
            'original_filename' => $originalName,
            'stored_filename' => $storedName,
            'file_path' => $relativePath,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'notes' => $body['notes'] ?? null,
        ]);

        return response()->json($doc, 201);
    }

    public function listDocuments(Request $request, int $id): JsonResponse
    {
        $claim = SubcontractorClaim::findOrFail($id);
        $docs = SubcontractorClaimDocument::with('uploader:id,name')
            ->where('subcontractor_claim_id', $claim->id)
            ->orderByDesc('created_at')
            ->get();

        return response()->json(['data' => $docs]);
    }

    public function serveDocument(Request $request, int $docId): JsonResponse
    {
        $doc = SubcontractorClaimDocument::with('claim.projectSubcontractor.project')->findOrFail($docId);
        $project = $doc->claim?->projectSubcontractor?->project;

        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        if (! array_intersect($userRoles, ['admin', 'super_admin'])) {
            $staff = $user->email ? StaffProfile::where('email', $user->email)->first() : null;
            $isPic = $staff && $project && $project->staffPics()->where('staff_id', $staff->id)->exists();
            if (! $isPic) {
                return response()->json(['error' => 'Forbidden'], 403);
            }
        }

        if (! $this->storage->exists($doc->file_path)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($doc->file_path);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => $doc->original_filename]);
            }
        }

        $contents = $this->storage->get($doc->file_path);
        $disposition = str_starts_with($doc->mime_type, 'image/') ? 'inline' : 'attachment';

        return response($contents, 200, [
            'Content-Type' => $doc->mime_type,
            'Content-Disposition' => $disposition.'; filename="'.$doc->original_filename.'"',
        ]);
    }

    public function deleteDocument(Request $request, int $docId): JsonResponse
    {
        $doc = SubcontractorClaimDocument::findOrFail($docId);
        $staffId = $this->getStaffId($request);
        $user = $request->user();
        $roles = $user ? $user->getRoleNames() : [];
        $isAdmin = (bool) array_intersect($roles, ['admin', 'super_admin']);
        if (! $staffId || ($doc->uploaded_by !== $staffId && ! $isAdmin)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        $this->storage->delete($doc->file_path);
        $doc->forceDelete();

        return response()->json(null, 204);
    }
}
