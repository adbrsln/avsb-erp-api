<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Services\DocumentGenerator;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    use PaginatedResponse;

    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    private function syncClientBuyerFields($model, string $clientName): void
    {
        if (empty($clientName)) {
            return;
        }
        $client = Client::where('company_name', $clientName)->first();
        if (! $client) {
            return;
        }
        $updates = [
            'buyer_tin' => $client->tax_id,
            'buyer_reg_no' => $client->registration_no,
            'buyer_sst_reg_no' => $client->sst_reg_no,
            'buyer_type' => $client->buyer_type,
            'buyer_email' => $client->email,
            'contact_phone' => $client->contact_phone,
            'buyer_contact' => $client->billing_address ?: $client->address,
        ];
        $updates = array_filter($updates, fn ($v) => $v !== null && $v !== '');
        if (! empty($updates)) {
            $model->update($updates);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $params = $request->query();
        $query = Contract::query();

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('contract_number', 'like', "%{$s}%")
                    ->orWhere('client', 'like', "%{$s}%");
            });
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $countQuery = clone $query;
        $statuses = ['draft', 'active', 'completed'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = (clone $countQuery)->where('status', $status)->count();
        }

        return $this->paginate($query, $params, [
            'counts' => $counts,
            'sortable' => ['contract_number', 'client', 'date', 'total_amount', 'status', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['client'])) {
            return response()->json(['error' => 'client is required'], 400);
        }

        if (empty($data['contract_number'])) {
            $data['contract_number'] = (new NumberingService)->generate('contract');
        }

        if (Contract::where('contract_number', $data['contract_number'])->exists()) {
            return response()->json(['error' => 'contract_number already exists'], 409);
        }

        $items = $data['items'] ?? [];
        if (! empty($items) && is_array($items)) {
            $rawSubtotal = 0;
            foreach ($items as $item) {
                $qty = $item['quantity'] ?? 0;
                $rate = $item['unit_rate'] ?? $item['unitRate'] ?? 0;
                $rawSubtotal += $qty * $rate;
            }
            $data['subtotal'] = r2($rawSubtotal);
            $sstRate = floatval($data['sst_rate'] ?? 8);
            $retentionRate = floatval($data['retention_rate'] ?? 5);
            $sst = round($rawSubtotal * ($sstRate / 100), 2);
            $retention = round($rawSubtotal * ($retentionRate / 100), 2);
            $data['sst_rate'] = $sstRate;
            $data['retention_rate'] = $retentionRate;
            $data['total_amount'] = round($rawSubtotal + $sst - $retention, 2);
        }

        if (empty($data['billing_milestones']) && ! empty($data['subtotal'])) {
            $base = $data['subtotal'];
            $data['billing_milestones'] = [
                ['description' => 'Initial Payment', 'percentage' => 30, 'amount' => round($base * 0.3, 2), 'status' => 'pending'],
                ['description' => 'Progress Payment', 'percentage' => 50, 'amount' => round($base * 0.5, 2), 'status' => 'pending'],
                ['description' => 'Final Payment', 'percentage' => 20, 'amount' => round($base * 0.2, 2), 'status' => 'pending'],
            ];
        }

        $item = Contract::create(fillableData(new Contract, $data));
        $this->syncClientBuyerFields($item, $data['client'] ?? '');

        try {
            $clientEmail = $item->client ? Client::where('company_name', $item->client)->value('email') : '';
            $recipients = [];
            if (! empty($clientEmail)) {
                $recipients[] = ['email' => $clientEmail, 'name' => $item->client];
            }
            if (! empty($recipients)) {
                NotificationService::queueToMany(
                    NotificationEvent::CONTRACT_CREATED,
                    $recipients,
                    ['contract_number' => $item->contract_number ?? '', 'client' => $item->client ?? '', 'amount' => number_format($item->total_amount ?? 0, 2), 'url' => '/finance/contracts/'.$item->id],
                    'App\\Models\\Contract',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: contract.created', ['contract_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Contract::with('invoices', 'project')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $data = $request->all();
        $item = Contract::findOrFail($id);

        if (isset($data['contract_number']) && $data['contract_number'] !== $item->contract_number) {
            if (Contract::where('contract_number', $data['contract_number'])->where('id', '!=', $id)->exists()) {
                return response()->json(['error' => 'contract_number already exists'], 409);
            }
        }

        $items = $data['items'] ?? [];
        if (! empty($items) && is_array($items)) {
            $rawSubtotal = 0;
            foreach ($items as $it) {
                $qty = $it['quantity'] ?? 0;
                $rate = $it['unit_rate'] ?? $it['unitRate'] ?? 0;
                $rawSubtotal += $qty * $rate;
            }
            $data['subtotal'] = r2($rawSubtotal);
            $sstRate = floatval($data['sst_rate'] ?? $item->sst_rate ?? 8);
            $retentionRate = floatval($data['retention_rate'] ?? $item->retention_rate ?? 5);
            $sst = round($rawSubtotal * ($sstRate / 100), 2);
            $retention = round($rawSubtotal * ($retentionRate / 100), 2);
            $data['sst_rate'] = $sstRate;
            $data['retention_rate'] = $retentionRate;
            $data['total_amount'] = round($rawSubtotal + $sst - $retention, 2);
        }

        $item->update(fillableData($item, $data));
        try {
            $this->storage->delete('documents/contracts/'.$item->id.'.pdf');
        } catch (\Throwable $e) {
        }

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $item = Contract::findOrFail($id);
        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft contracts can be deleted'], 422);
        }
        $item->delete();

        return response()->json(null, 204);
    }

    public function activate(Request $request, int $id): JsonResponse
    {
        $item = Contract::findOrFail($id);
        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft contracts can be activated'], 422);
        }
        $item->update(['status' => 'active']);

        try {
            $clientEmail = $item->client ? Client::where('company_name', $item->client)->value('email') : '';
            $recipients = [];
            if (! empty($clientEmail)) {
                $recipients[] = ['email' => $clientEmail, 'name' => $item->client];
            }
            if (! empty($recipients)) {
                NotificationService::queueToMany(
                    NotificationEvent::CONTRACT_ACTIVATED,
                    $recipients,
                    ['contract_number' => $item->contract_number ?? '', 'client' => $item->client ?? '', 'url' => '/finance/contracts/'.$item->id],
                    'App\\Models\\Contract',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: contract.activated', ['contract_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function complete(Request $request, int $id): JsonResponse
    {
        $item = Contract::findOrFail($id);
        if ($item->status !== 'active') {
            return response()->json(['error' => 'Only active contracts can be completed'], 422);
        }
        $item->update(['status' => 'completed']);

        try {
            $clientEmail = $item->client ? Client::where('company_name', $item->client)->value('email') : '';
            $recipients = [];
            if (! empty($clientEmail)) {
                $recipients[] = ['email' => $clientEmail, 'name' => $item->client];
            }
            if (! empty($recipients)) {
                NotificationService::queueToMany(
                    NotificationEvent::CONTRACT_COMPLETED,
                    $recipients,
                    ['contract_number' => $item->contract_number ?? '', 'client' => $item->client ?? '', 'amount' => number_format($item->total_amount ?? 0, 2), 'url' => '/finance/contracts/'.$item->id],
                    'App\\Models\\Contract',
                    $item->id
                );
            }
        } catch (\Throwable $e) {
            Log::error('Notification failed: contract.completed', ['contract_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function revertToDraft(Request $request, int $id): JsonResponse
    {
        $item = Contract::findOrFail($id);
        if (! in_array($item->status, ['active', 'completed'])) {
            return response()->json(['error' => 'Only active or completed contracts can be reverted to draft'], 422);
        }
        $item->update(['status' => 'draft']);

        return response()->json($item);
    }

    public function generateInvoice(Request $request, int $id): JsonResponse
    {
        $contract = Contract::findOrFail($id);

        if ($contract->status !== 'active' && $contract->status !== 'draft') {
            return response()->json(['error' => 'Invoices can only be generated for draft or active contracts'], 422);
        }

        $data = $request->all();
        $milestones = is_array($contract->billing_milestones) ? $contract->billing_milestones : (json_decode($contract->billing_milestones ?? '[]', true) ?: []);
        $selectedIndices = $data['milestone_indices'] ?? [];

        $billedMilestones = [];
        if (! empty($selectedIndices) && is_array($selectedIndices)) {
            foreach ($selectedIndices as $idx) {
                if (isset($milestones[$idx]) && $milestones[$idx]['status'] === 'pending') {
                    $billedMilestones[] = $milestones[$idx];
                    $milestones[$idx]['status'] = 'billed';
                }
            }
            if (empty($billedMilestones)) {
                return response()->json(['error' => 'No valid pending milestones selected'], 422);
            }
        } else {
            foreach ($milestones as $idx => $m) {
                if ($m['status'] === 'pending') {
                    $billedMilestones[] = $m;
                    $milestones[$idx]['status'] = 'billed';
                }
            }
            if (empty($billedMilestones)) {
                return response()->json(['error' => 'All milestones have already been billed'], 422);
            }
        }

        $invoice = DB::transaction(function () use ($contract, $billedMilestones, $milestones) {
            $subtotal = round(array_sum(array_column($billedMilestones, 'amount')), 2);
            $sstAmount = round($subtotal * ($contract->sst_rate / 100), 2);
            $retentionAmount = round($subtotal * ($contract->retention_rate / 100), 2);
            $total = round($subtotal + $sstAmount - $retentionAmount, 2);

            $invoiceItems = [];
            foreach ($billedMilestones as $m) {
                $amt = round($m['amount'] ?? 0, 2);
                $invoiceItems[] = [
                    'description' => $m['description'] ?? 'Progress billing',
                    'item_name' => $m['description'] ?? 'Progress billing',
                    'unit' => 'Lot', 'quantity' => 1,
                    'unit_rate' => $amt, 'total' => $amt,
                ];
            }

            $invoice = Invoice::create([
                'invoice_number' => (new NumberingService)->generate('invoice'),
                'contract_id' => $contract->id, 'project_id' => $contract->project_id,
                'client' => $contract->client, 'date' => Carbon::now(),
                'due_date' => Carbon::now()->addDays(30), 'status' => 'draft',
                'subtotal' => $subtotal, 'sst' => $sstAmount,
                'retention' => $retentionAmount, 'total' => $total,
                'items' => $invoiceItems,
            ]);

            foreach ($milestones as $idx => $m) {
                if (($m['status'] ?? '') === 'billed' && empty($m['invoice_id'])) {
                    $milestones[$idx]['invoice_id'] = $invoice->id;
                }
            }
            $contract->billing_milestones = $milestones;
            $contract->save();

            $revenueAccount = ChartOfAccount::where('code', '4101')->first();
            $arAccount = ChartOfAccount::where('code', '1104')->first();
            $sstPayableAccount = ChartOfAccount::where('code', '2107')->first();

            if ($revenueAccount && $arAccount) {
                $je = JournalEntry::create([
                    'entry_number' => (new NumberingService)->generate('journal'),
                    'entry_date' => Carbon::now(),
                    'description' => 'Auto-post: Invoice '.$invoice->invoice_number,
                    'reference_type' => 'invoice', 'reference_id' => $invoice->id,
                    'status' => 'posted', 'posted_at' => Carbon::now(),
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $je->id, 'account_id' => $arAccount->id,
                    'debit' => $subtotal + $sstAmount - $retentionAmount, 'description' => 'Invoice '.$invoice->invoice_number,
                ]);
                JournalEntryLine::create([
                    'journal_entry_id' => $je->id, 'account_id' => $revenueAccount->id,
                    'credit' => $subtotal, 'description' => 'Invoice '.$invoice->invoice_number,
                ]);
                if ($sstAmount > 0 && $sstPayableAccount) {
                    JournalEntryLine::create([
                        'journal_entry_id' => $je->id, 'account_id' => $sstPayableAccount->id,
                        'credit' => $sstAmount, 'description' => 'SST on '.$invoice->invoice_number,
                    ]);
                }
                if ($retentionAmount > 0) {
                    $retentionPayableAccount = ChartOfAccount::where('code', '2108')->first();
                    if ($retentionPayableAccount) {
                        JournalEntryLine::create([
                            'journal_entry_id' => $je->id, 'account_id' => $retentionPayableAccount->id,
                            'credit' => $retentionAmount, 'description' => 'Retention on '.$invoice->invoice_number,
                        ]);
                    }
                }
            }

            return $invoice;
        });

        return response()->json($invoice, 201);
    }

    public function download(Request $request, int $id): Response|JsonResponse
    {
        $c = Contract::findOrFail($id);
        $filename = $c->contract_number.'.pdf';
        $path = 'documents/contracts/'.$c->id.'.pdf';

        $pdf = (new DocumentGenerator)->contract($c);
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
