<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Services\NumberingService;
use App\Traits\PaginatedResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    use PaginatedResponse;

    public function index(Request $request): JsonResponse
    {
        $query = Client::with('pics')->withCount([
            'pics',
            'projects as projects_count' => fn ($q) => $q->where('status', '!=', 'cancelled'),
            'projects as active_projects_count' => fn ($q) => $q->whereIn('status', ['active', 'planning', 'in_progress']),
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('company_name', 'like', '%'.$search.'%')
                    ->orWhere('email', 'like', '%'.$search.'%')
                    ->orWhere('registration_no', 'like', '%'.$search.'%');
            });
        }

        if ($request->input('all') === 'true') {
            return response()->json(['data' => $query->orderBy('company_name')->get()]);
        }

        return $this->paginate($query, $request->all());
    }

    private function validateBuyerFields(array $data): ?string
    {
        $buyerType = $data['buyer_type'] ?? 'company';
        if ($buyerType === 'foreign') {
            return null;
        }

        if ($buyerType === 'company') {
            if (empty($data['registration_no'])) {
                return 'Registration No (BRN) is required for company clients';
            }
            if (empty($data['tax_id'])) {
                return 'TIN is required for company clients';
            }
        }

        return null;
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        if (empty($data['company_name'])) {
            return response()->json(['error' => 'company_name is required'], 422);
        }

        $validationError = $this->validateBuyerFields($data);
        if ($validationError) {
            return response()->json(['error' => $validationError], 422);
        }

        $data['client_code'] = (new NumberingService)->generate('client');
        $item = Client::create(fillableData(new Client, $data));
        $item->load('pics');

        return response()->json($item, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $item = Client::with(['pics', 'projects' => fn ($q) => $q->where('status', '!=', 'cancelled')])->findOrFail($id);

        return response()->json($item);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $item = Client::findOrFail($id);
        $data = $request->all();

        $validationError = $this->validateBuyerFields($data);
        if ($validationError) {
            return response()->json(['error' => $validationError], 422);
        }

        $item->update(fillableData($item, $data));
        $item->load('pics');

        return response()->json($item);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        Client::findOrFail($id)->delete();

        return response()->noContent();
    }

    public function pics(Request $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        return response()->json($client->pics);
    }
}
