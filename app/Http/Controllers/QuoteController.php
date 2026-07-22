<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Quotation;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationService;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class QuoteController extends Controller
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
        $query = Quotation::with('project');

        if (! empty($params['search'])) {
            $s = $params['search'];
            $query->where(function ($q) use ($s) {
                $q->where('quote_number', 'like', "%{$s}%")
                    ->orWhere('client', 'like', "%{$s}%");
            });
        }
        if (! empty($params['status'])) {
            $query->where('status', $params['status']);
        }

        $countQuery = clone $query;
        $statuses = ['draft', 'sent', 'accepted', 'declined', 'converted'];
        $counts = [];
        foreach ($statuses as $status) {
            $counts[$status] = (clone $countQuery)->where('status', $status)->count();
        }

        return $this->paginate($query, $params, [
            'counts' => $counts,
            'sortable' => ['quote_number', 'client', 'date', 'valid_until', 'total', 'status', 'created_at'],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['client'])) {
            return response()->json(['error' => 'client is required'], 422);
        }
        if (! isset($data['items']) || ! is_array($data['items'])) {
            return response()->json(['error' => 'items must be an array'], 422);
        }

        try {
            $data['quote_number'] = (new NumberingService)->generate('quote');
        } catch (\Exception $e) {
            Log::error('Failed to generate quote number', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Failed to generate quote number'], 500);
        }

        $items = $data['items'];
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = $item['quantity'] ?? 0;
            $rate = $item['unit_rate'] ?? $item['unitRate'] ?? 0;
            $subtotal += $qty * $rate;
        }
        $sstRate = floatval($data['sst_rate'] ?? 8);
        $retentionPct = floatval($data['retention_pct'] ?? 0);
        $sst = r2($subtotal * ($sstRate / 100));
        $retentionAmount = r2($subtotal * ($retentionPct / 100));
        $data['subtotal'] = r2($subtotal);
        $data['sst'] = $sst;
        $data['sst_rate'] = $sstRate;
        $data['retention_pct'] = $retentionPct;
        $data['retention_amount'] = $retentionAmount;
        $data['total'] = r2($subtotal + $sst - $retentionAmount);

        $item = Quotation::create(fillableData(new Quotation, $data));
        $this->syncClientBuyerFields($item, $data['client'] ?? '');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Quotation::with('project')->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $quote = Quotation::findOrFail($id);
        $data = $request->all();

        if (empty($data['client'])) {
            return response()->json(['error' => 'client is required'], 422);
        }
        if (! isset($data['items']) || ! is_array($data['items'])) {
            return response()->json(['error' => 'items must be an array'], 422);
        }

        $items = $data['items'];
        $subtotal = 0;
        foreach ($items as $item) {
            $qty = $item['quantity'] ?? 0;
            $rate = $item['unit_rate'] ?? $item['unitRate'] ?? 0;
            $subtotal += $qty * $rate;
        }
        $sstRate = floatval($data['sst_rate'] ?? 8);
        $retentionPct = floatval($data['retention_pct'] ?? 0);
        $sst = r2($subtotal * ($sstRate / 100));
        $retentionAmount = r2($subtotal * ($retentionPct / 100));
        $data['subtotal'] = r2($subtotal);
        $data['sst'] = $sst;
        $data['sst_rate'] = $sstRate;
        $data['retention_pct'] = $retentionPct;
        $data['retention_amount'] = $retentionAmount;
        $data['total'] = r2($subtotal + $sst - $retentionAmount);

        $quote->update(fillableData(new Quotation, $data));
        try {
            $this->storage->delete('documents/quotations/'.$quote->id.'.pdf');
        } catch (\Throwable $e) {
        }

        return response()->json($quote);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $quote = Quotation::findOrFail($id);
        if (! in_array($quote->status, ['draft', 'declined'])) {
            return response()->json(['error' => 'Only draft or declined quotations can be deleted'], 422);
        }
        $quote->delete();

        return response()->json(null, 204);
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $item = Quotation::findOrFail($id);
        if ($item->status !== 'draft') {
            return response()->json(['error' => 'Only draft quotations can be submitted'], 422);
        }
        $item->update(['status' => 'sent']);

        try {
            $recipients = [['email' => $item->client_email ?? '', 'name' => $item->client ?? '']];
            NotificationService::queueToMany(
                NotificationEvent::QUOTE_SUBMITTED,
                $recipients,
                ['quote_number' => $item->quote_number ?? '', 'client' => $item->client ?? '', 'total' => number_format($item->total ?? 0, 2), 'url' => '/finance/quotations/'.$item->id],
                'App\\Models\\Quotation',
                $item->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: quote.submitted', ['quote_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function accept(Request $request, int $id): JsonResponse
    {
        $item = Quotation::findOrFail($id);
        if ($item->status !== 'sent') {
            return response()->json(['error' => 'Only sent quotations can be accepted'], 422);
        }
        $item->update(['status' => 'accepted']);

        try {
            $recipients = [['email' => $item->client_email ?? '', 'name' => $item->client ?? '']];
            NotificationService::queueToMany(
                NotificationEvent::QUOTE_ACCEPTED,
                $recipients,
                ['quote_number' => $item->quote_number ?? '', 'client' => $item->client ?? '', 'total' => number_format($item->total ?? 0, 2), 'url' => '/finance/quotations/'.$item->id],
                'App\\Models\\Quotation',
                $item->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: quote.accepted', ['quote_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function decline(Request $request, int $id): JsonResponse
    {
        $item = Quotation::findOrFail($id);
        if ($item->status !== 'sent' && $item->status !== 'draft') {
            return response()->json(['error' => 'Only sent or draft quotations can be declined'], 422);
        }
        $item->update(['status' => 'declined']);

        try {
            $recipients = [['email' => $item->client_email ?? '', 'name' => $item->client ?? '']];
            NotificationService::queueToMany(
                NotificationEvent::QUOTE_DECLINED,
                $recipients,
                ['quote_number' => $item->quote_number ?? '', 'client' => $item->client ?? '', 'url' => '/finance/quotations/'.$item->id],
                'App\\Models\\Quotation',
                $item->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: quote.declined', ['quote_id' => $item->id, 'error' => $e->getMessage()]);
        }

        return response()->json($item);
    }

    public function revertToDraft(Request $request, int $id): JsonResponse
    {
        $item = Quotation::findOrFail($id);
        if (! in_array($item->status, ['sent', 'declined'])) {
            return response()->json(['error' => 'Only sent or declined quotations can be reverted to draft'], 422);
        }
        $item->update(['status' => 'draft']);

        return response()->json($item);
    }

    public function convertToContract(Request $request, int $id): JsonResponse
    {
        $quote = Quotation::findOrFail($id);

        if ($quote->status !== 'sent' && $quote->status !== 'accepted') {
            return response()->json(['error' => 'Only sent or accepted quotes can be converted'], 422);
        }

        $retentionRate = $quote->retention_pct ?? 5.00;
        $contract = Contract::create([
            'contract_number' => (new NumberingService)->generate('contract'),
            'project_id' => $quote->project_id,
            'client' => $quote->client,
            'date' => Carbon::now(),
            'status' => 'draft',
            'total_amount' => $quote->total,
            'subtotal' => $quote->subtotal,
            'sst_rate' => $quote->sst_rate ?? 8.00,
            'retention_rate' => $retentionRate,
            'terms' => $quote->notes,
            'items' => $quote->items,
            'billing_milestones' => [
                ['description' => 'Initial Payment', 'percentage' => 30, 'amount' => round($quote->total * 0.3, 2), 'status' => 'pending'],
                ['description' => 'Progress Payment', 'percentage' => 50, 'amount' => round($quote->total * 0.5, 2), 'status' => 'pending'],
                ['description' => 'Final Payment', 'percentage' => 20, 'amount' => round($quote->total * 0.2, 2), 'status' => 'pending'],
            ],
        ]);
        $quote->update(['status' => 'converted']);

        try {
            $recipients = [['email' => $quote->client_email ?? '', 'name' => $quote->client ?? '']];
            NotificationService::queueToMany(
                NotificationEvent::QUOTE_CONVERTED,
                $recipients,
                ['quote_number' => $quote->quote_number ?? '', 'contract_number' => $contract->contract_number ?? '', 'client' => $quote->client ?? '', 'url' => '/finance/contracts/'.$contract->id],
                'App\\Models\\Quotation',
                $quote->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: quote.converted', ['quote_id' => $quote->id, 'error' => $e->getMessage()]);
        }

        $this->syncClientBuyerFields($contract, $contract->client ?? '');

        return response()->json($contract);
    }

    public function generateInvoice(Request $request, int $id): JsonResponse
    {
        $quote = Quotation::findOrFail($id);

        if (! in_array($quote->status, ['sent', 'accepted'])) {
            return response()->json(['error' => 'Only sent or accepted quotations can generate invoices'], 422);
        }

        $invoice = Invoice::create([
            'invoice_number' => (new NumberingService)->generate('invoice'),
            'project_id' => $quote->project_id,
            'client' => $quote->client,
            'date' => Carbon::now(),
            'due_date' => Carbon::now()->addDays(30),
            'status' => 'draft',
            'subtotal' => $quote->subtotal,
            'sst' => $quote->sst,
            'retention' => $quote->retention_amount ?? 0,
            'total' => $quote->total,
            'items' => $quote->items,
        ]);

        $this->syncClientBuyerFields($invoice, $invoice->client ?? '');
        $quote->update(['status' => 'converted']);

        try {
            $recipients = [['email' => $quote->client_email ?? '', 'name' => $quote->client ?? '']];
            NotificationService::queueToMany(
                NotificationEvent::QUOTE_CONVERTED,
                $recipients,
                ['quote_number' => $quote->quote_number ?? '', 'invoice_number' => $invoice->invoice_number ?? '', 'client' => $quote->client ?? '', 'url' => '/finance/invoices/'.$invoice->id],
                'App\\Models\\Quotation',
                $quote->id
            );
        } catch (\Throwable $e) {
            Log::error('Notification failed: quote.converted', ['quote_id' => $quote->id, 'error' => $e->getMessage()]);
        }

        return response()->json($invoice, 201);
    }
}
