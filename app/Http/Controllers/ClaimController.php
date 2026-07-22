<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\ExpenseClaim;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\StaffProfile;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClaimController extends Controller
{
    use PaginatedResponse;

    const APPROVER_ROLES = ['admin', 'hr', 'finance', 'super_admin'];

    const CATEGORIES = [
        'mileage' => ['label' => 'Mileage', 'account_code' => '6301'],
        'toll' => ['label' => 'Toll', 'account_code' => '6301'],
        'parking' => ['label' => 'Parking', 'account_code' => '6301'],
        'accommodation' => ['label' => 'Accommodation', 'account_code' => '6302'],
        'meals' => ['label' => 'Meals', 'account_code' => '6303'],
        'communication' => ['label' => 'Communication', 'account_code' => '6204'],
        'office_supplies' => ['label' => 'Office Supplies', 'account_code' => '6203'],
        'training' => ['label' => 'Training', 'account_code' => '6401'],
        'medical' => ['label' => 'Medical', 'account_code' => '6303'],
        'entertainment' => ['label' => 'Entertainment', 'account_code' => '6302'],
        'others' => ['label' => 'Others', 'account_code' => '2102'],
    ];

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    private function getStaff(Request $request): StaffProfile
    {
        $user = $request->user();
        $email = $user->email ?? '';

        return StaffProfile::where('email', $email)->firstOrFail();
    }

    private function requireRole(Request $request, array $roles): void
    {
        $user = $request->user();
        $userRoles = $user ? $user->getRoleNames() : [];
        if (empty(array_intersect($userRoles, $roles))) {
            throw new \RuntimeException('Insufficient permissions');
        }
    }

    public function myClaims(Request $request): JsonResponse
    {
        $staff = $this->getStaff($request);
        $params = $request->query();
        $query = ExpenseClaim::where('staff_id', $staff->id)->with('staff:id,name');

        return $this->paginate($query, $params);
    }

    public function storeMyClaim(Request $request): JsonResponse
    {
        $staff = $this->getStaff($request);
        $data = $request->all();

        if (empty($data['title'])) {
            return response()->json(['error' => 'title is required'], 422);
        }

        $items = $data['items'] ?? [];
        if (empty($items)) {
            return response()->json(['error' => 'At least one item is required'], 422);
        }

        $total = array_sum(array_column($items, 'amount'));

        $claim = ExpenseClaim::create([
            'claim_ref' => (new NumberingService)->generate('claim'),
            'staff_id' => $staff->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? '',
            'items' => $items,
            'total_amount' => $total,
            'status' => 'submitted',
            'submitted_date' => date('Y-m-d'),
        ]);

        $claim->load('staff:id,name');

        try {
            $recipients = NotificationRecipientResolver::getApprovers('claim');
            NotificationService::queueToMany(
                NotificationEvent::CLAIM_SUBMITTED,
                $recipients,
                [
                    'staff_name' => $staff->name,
                    'title' => $claim->title,
                    'amount' => number_format($total, 2),
                    'url' => '/approvals?type=claim',
                ],
                'App\\Models\\ExpenseClaim',
                $claim->id
            );
        } catch (\Throwable $e) {
            logger()->error('Notification failed: claim.submitted', ['claim_id' => $claim->id, 'error' => $e->getMessage()]);
        }

        return response()->json($claim, 201);
    }

    public function showMyClaim(Request $request, int $id): JsonResponse
    {
        $staff = $this->getStaff($request);
        $claim = ExpenseClaim::where('staff_id', $staff->id)
            ->with('staff:id,name', 'approver:id,name')
            ->findOrFail($id);

        return response()->json($claim);
    }

    public function uploadReceipt(Request $request, int $id): JsonResponse
    {
        $staff = $this->getStaff($request);
        $claim = ExpenseClaim::where('staff_id', $staff->id)->findOrFail($id);
        if ($claim->status !== 'submitted') {
            return response()->json(['error' => 'Can only upload receipt for submitted claims'], 422);
        }

        $file = $request->file('receipt');
        if (! $file || ! $file->isValid()) {
            return response()->json(['error' => 'No valid receipt file uploaded'], 422);
        }

        $error = FileStorageService::validateUpload($file);
        if ($error) {
            return response()->json(['error' => $error], 422);
        }

        $ext = pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION);
        $filename = 'claim-'.$claim->id.'-'.time().'.'.$ext;
        $path = 'uploads/claims/'.$filename;
        $this->storage->put($path, file_get_contents($file->getPathname()), $file->getClientMimeType());

        $claim->update(['receipt_url' => $path]);

        return response()->json($claim);
    }

    public function serveReceipt(Request $request, int $id): JsonResponse
    {
        $claim = ExpenseClaim::findOrFail($id);
        $staff = $this->getStaff($request);
        if ($claim->staff_id !== $staff->id) {
            return response()->json(['error' => 'Forbidden'], 403);
        }
        if (! $claim->receipt_url) {
            return response()->json(['error' => 'No receipt uploaded'], 404);
        }
        if (! $claim->receipt_url || ! $this->storage->exists($claim->receipt_url)) {
            return response()->json(['error' => 'File not found'], 404);
        }

        $params = $request->query();
        if (isset($params['presign']) && $params['presign'] === '1') {
            $url = $this->storage->getPresignedUrl($claim->receipt_url);
            if ($url) {
                return response()->json(['url' => $url, 'filename' => basename($claim->receipt_url)]);
            }
        }

        $contents = $this->storage->get($claim->receipt_url);

        return response($contents, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Length' => $this->storage->size($claim->receipt_url),
            'Cache-Control' => 'private, max-age=86400',
        ]);
    }

    public function categories(Request $request): JsonResponse
    {
        return response()->json(self::CATEGORIES);
    }

    public function index(Request $request): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $params = $request->query();
        $query = ExpenseClaim::with('staff:id,name', 'approver:id,name');
        if (isset($params['staff_id'])) {
            $query->where('staff_id', $params['staff_id']);
        }

        return $this->paginate($query, $params);
    }

    public function store(Request $request): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $data = $request->all();
        if (empty($data['staff_id'])) {
            return response()->json(['error' => 'staff_id is required'], 422);
        }
        if (empty($data['title'])) {
            return response()->json(['error' => 'title is required'], 422);
        }
        $data['status'] = 'submitted';
        $data['claim_ref'] = (new NumberingService)->generate('claim');
        $item = ExpenseClaim::create(fillableData(new ExpenseClaim, $data));
        $item->load('staff:id,name');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $item = ExpenseClaim::with('staff:id,name', 'approver:id,name')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $item = ExpenseClaim::findOrFail($id);
        if (! in_array($item->status, ['submitted', 'pending'])) {
            return response()->json(['error' => 'Can only edit submitted claims'], 422);
        }
        $item->update(fillableData($item, $request->all()));

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $item = ExpenseClaim::findOrFail($id);
        if (! in_array($item->status, ['submitted', 'pending'])) {
            return response()->json(['error' => 'Can only delete submitted claims'], 422);
        }
        $item->delete();

        return response()->json(null, 204);
    }

    public function approve(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $staff = $this->getStaff($request);
        $item = ExpenseClaim::findOrFail($id);

        if (! in_array($item->status, ['submitted', 'pending'])) {
            return response()->json(['error' => 'Claim is already '.$item->status], 422);
        }

        $item->update([
            'status' => 'approved',
            'approver_id' => $staff->id,
            'approved_at' => date('Y-m-d H:i:s'),
        ]);

        try {
            $this->postApprovalJournalEntry($item);
        } catch (\Throwable $e) {
            logger()->error('Claim approval JE auto-post failed', [
                'claim_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        $item->load('staff:id,name', 'approver:id,name');

        try {
            $claimant = StaffProfile::find($item->staff_id);
            if ($claimant) {
                NotificationService::queue(
                    NotificationEvent::CLAIM_APPROVED,
                    $claimant->email,
                    $claimant->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->total_amount, 2),
                        'url' => '/my-claims',
                    ],
                    'App\\Models\\ExpenseClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: claim.approved', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function reject(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $staff = $this->getStaff($request);
        $data = $request->all();
        $item = ExpenseClaim::findOrFail($id);

        if (! in_array($item->status, ['submitted', 'pending'])) {
            return response()->json(['error' => 'Claim is already '.$item->status], 422);
        }

        $item->update([
            'status' => 'rejected',
            'approver_id' => $staff->id,
            'rejection_reason' => $data['reason'] ?? '',
            'rejected_at' => date('Y-m-d H:i:s'),
        ]);

        $item->load('staff:id,name', 'approver:id,name');

        try {
            $claimant = StaffProfile::find($item->staff_id);
            if ($claimant) {
                NotificationService::queue(
                    NotificationEvent::CLAIM_REJECTED,
                    $claimant->email,
                    $claimant->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->total_amount, 2),
                        'reason' => $item->rejection_reason ?? '',
                        'url' => '/my-claims',
                    ],
                    'App\\Models\\ExpenseClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: claim.rejected', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function markPaid(Request $request, int $id): JsonResponse
    {
        $this->requireRole($request, self::APPROVER_ROLES);
        $item = ExpenseClaim::findOrFail($id);

        if (! in_array($item->status, ['approved', 'paid'])) {
            return response()->json(['error' => 'Only approved claims can be marked paid'], 422);
        }

        if ($item->status === 'paid') {
            return response()->json(['error' => 'Claim is already paid'], 422);
        }

        $data = $request->all();
        $paymentRef = trim($data['payment_reference'] ?? '');
        $updateData = [
            'status' => 'paid',
            'paid_at' => date('Y-m-d H:i:s'),
        ];
        if ($paymentRef) {
            $updateData['payment_reference'] = $paymentRef;
        }
        $item->update($updateData);

        try {
            $this->postPaymentJournalEntry($item);
        } catch (\Throwable $e) {
            logger()->error('Claim payment JE auto-post failed', [
                'claim_id' => $item->id,
                'error' => $e->getMessage(),
            ]);
        }

        $item->load('staff:id,name', 'approver:id,name');

        try {
            $claimant = StaffProfile::find($item->staff_id);
            if ($claimant) {
                NotificationService::queue(
                    NotificationEvent::CLAIM_PAID,
                    $claimant->email,
                    $claimant->name,
                    [
                        'title' => $item->title,
                        'amount' => number_format($item->total_amount, 2),
                        'url' => '/my-claims',
                    ],
                    'App\\Models\\ExpenseClaim',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            logger()->error('Notification failed: claim.paid', ['claim_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    private function postApprovalJournalEntry(ExpenseClaim $claim): void
    {
        $accruedAccount = ChartOfAccount::where('code', '2108')->first();
        if (! $accruedAccount) {
            return;
        }

        $items = $claim->items ?? [];
        if (empty($items)) {
            return;
        }

        $byAccount = [];
        foreach ($items as $item) {
            $code = self::CATEGORIES[$item['category']]['account_code'] ?? '2102';
            $amount = (float) ($item['amount'] ?? 0);
            if ($amount <= 0) {
                continue;
            }
            if (! isset($byAccount[$code])) {
                $byAccount[$code] = 0;
            }
            $byAccount[$code] += $amount;
        }

        if (empty($byAccount)) {
            return;
        }

        $je = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => date('Y-m-d'),
            'description' => 'Claim approved - '.($claim->claim_ref ?? ''),
            'reference_type' => 'claim',
            'reference_id' => $claim->id,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
        ]);

        foreach ($byAccount as $code => $amount) {
            $account = ChartOfAccount::where('code', $code)->first();
            if (! $account) {
                continue;
            }
            JournalEntryLine::create([
                'journal_entry_id' => $je->id,
                'account_id' => $account->id,
                'debit' => round($amount, 2),
                'description' => $claim->claim_ref.' - '.$code,
            ]);
        }

        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $accruedAccount->id,
            'credit' => round(array_sum($byAccount), 2),
            'description' => $claim->claim_ref,
        ]);
    }

    private function postPaymentJournalEntry(ExpenseClaim $claim): void
    {
        $accruedAccount = ChartOfAccount::where('code', '2108')->first();
        $bankAccount = ChartOfAccount::where('code', '1102')->first();
        if (! $accruedAccount || ! $bankAccount) {
            return;
        }

        $je = JournalEntry::create([
            'entry_number' => (new NumberingService)->generate('journal'),
            'entry_date' => date('Y-m-d'),
            'description' => 'Claim payment - '.($claim->claim_ref ?? ''),
            'reference_type' => 'claim',
            'reference_id' => $claim->id,
            'status' => 'posted',
            'posted_at' => date('Y-m-d H:i:s'),
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $accruedAccount->id,
            'debit' => round((float) $claim->total_amount, 2),
            'description' => $claim->claim_ref,
        ]);

        JournalEntryLine::create([
            'journal_entry_id' => $je->id,
            'account_id' => $bankAccount->id,
            'credit' => round((float) $claim->total_amount, 2),
            'description' => $claim->claim_ref,
        ]);
    }
}
