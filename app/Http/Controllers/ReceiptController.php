<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Receipt;
use App\Services\DocumentGenerator;
use App\Services\FileStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReceiptController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);
        $receipts = Receipt::where('invoice_id', $invoice->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        return response()->json(['data' => $receipts]);
    }

    public function download(Request $request, int $id): JsonResponse
    {
        $r = Receipt::with('invoice')->findOrFail($id);
        $filename = $r->receipt_number.'.pdf';
        $path = 'documents/receipts/'.$r->id.'.pdf';

        $pdf = (new DocumentGenerator)->receipt($r);
        $this->storage->put($path, $pdf, 'application/pdf');

        $url = $this->storage->getPresignedUrl($path, 5, $filename);
        if ($url) {
            return response()->json(['url' => $url, 'filename' => $filename]);
        }

        $pdf = $this->storage->get($path);

        return response()->json(['pdf_base64' => base64_encode($pdf), 'filename' => $filename]);
    }
}
