<?php

namespace App\Http\Controllers;

use App\Models\CompanySetting;
use App\Models\EInvoiceCredential;
use App\Models\EInvoiceSubmissionLog;
use App\Models\Invoice;
use App\Models\TaxCode;
use App\Services\EInvoiceClient;
use App\Services\EInvoiceDocumentBuilder;
use App\Services\FileStorageService;
use App\Services\Notification\NotificationEvent;
use App\Services\Notification\NotificationRecipientResolver;
use App\Services\Notification\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EInvoiceController extends Controller
{
    private FileStorageService $storage;

    public function __construct()
    {
        $this->storage = new FileStorageService;
    }

    public function submit(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $result = $this->submitInvoice($invoice);

        if ($result['success']) {
            $invoice = $result['invoice'];
            try {
                $recipients = NotificationRecipientResolver::getFinance();
                NotificationService::queueToMany(
                    NotificationEvent::EINVOICE_SUBMITTED,
                    $recipients,
                    [
                        'invoice_number' => $invoice->invoice_number ?? '',
                        'submission_uid' => $invoice->submission_uid ?? '',
                        'url' => '/finance/invoices/'.$invoice->id,
                    ],
                    'App\\Models\\Invoice',
                    $invoice->id
                );
            } catch (\Throwable $e) {
                logger()->error('Notification failed: einvoice.submitted', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }

            return response()->json($result['invoice']);
        }

        return response()->json([
            'error' => $result['error'],
            'invoice' => $result['invoice'],
        ], $result['http_status']);
    }

    private function submitInvoice(Invoice $invoice): array
    {
        if (! in_array($invoice->status, ['unpaid', 'partially_paid', 'paid'])) {
            return [
                'success' => false,
                'error' => 'Only unpaid, partially paid, or paid invoices can be submitted for e-invoicing',
                'invoice' => $invoice,
                'http_status' => 422,
            ];
        }

        if ($invoice->buyer_type === 'foreign') {
            return [
                'success' => false,
                'error' => 'Foreign entity — e-invoice not required',
                'invoice' => $invoice,
                'http_status' => 422,
            ];
        }

        if (empty($invoice->buyer_tin)) {
            return [
                'success' => false,
                'error' => 'Buyer TIN is required for e-invoice submission',
                'invoice' => $invoice,
                'http_status' => 422,
            ];
        }

        try {
            $hex = bin2hex(random_bytes(16));
            $timeLow = substr($hex, 0, 8);
            $timeMid = substr($hex, 8, 4);
            $timeHiAndVersion = '4'.substr($hex, 13, 3);
            $clockSeqHiAndReserved = dechex(0x80 | (hexdec(substr($hex, 16, 1)) & 0x3F));
            $clockSeqLow = substr($hex, 17, 2);
            $node = substr($hex, 20, 12);
            $uuid = "{$timeLow}-{$timeMid}-{$timeHiAndVersion}-{$clockSeqHiAndReserved}{$clockSeqLow}-{$node}";

            $builder = new EInvoiceDocumentBuilder;
            $base64Xml = $builder->buildInvoice($invoice);

            $xmlDecoded = base64_decode($base64Xml);
            $documentHash = hash('sha256', $xmlDecoded);

            $invoice->update([
                'uuid' => $uuid,
                'submission_status' => 'pending',
                'einvoice_xml' => $xmlDecoded,
                'submitted_at' => date('Y-m-d H:i:s'),
                'last_submission_attempt' => date('Y-m-d H:i:s'),
            ]);

            $client = new EInvoiceClient;
            $result = $client->submitDocument([
                'documents' => [
                    [
                        'format' => 'XML',
                        'document' => $base64Xml,
                        'documentHash' => $documentHash,
                        'codeNumber' => $invoice->invoice_number,
                    ],
                ],
            ]);

            $submissionUid = $result['submissionUid'] ?? null;
            $acceptedDocs = $result['acceptedDocuments'] ?? [];
            $firstDoc = $acceptedDocs[0] ?? [];

            $invoice->update([
                'submission_status' => 'processing',
                'submission_uid' => $submissionUid,
                'long_id' => $firstDoc['longId'] ?? null,
                'uuid' => $firstDoc['uuid'] ?? $uuid,
            ]);

            $this->logSubmission($invoice, 'submit', $result, 200, true);

            return [
                'success' => true,
                'invoice' => $invoice->fresh(),
                'http_status' => 200,
            ];
        } catch (\RuntimeException $e) {
            $invoice->update([
                'submission_status' => 'error',
                'submission_error' => $e->getMessage(),
                'last_submission_attempt' => date('Y-m-d H:i:s'),
            ]);

            try {
                $recipients = NotificationRecipientResolver::getFinance();
                NotificationService::queueToMany(
                    NotificationEvent::EINVOICE_FAILED,
                    $recipients,
                    [
                        'invoice_number' => $invoice->invoice_number ?? '',
                        'error' => $e->getMessage(),
                        'url' => '/finance/invoices/'.$invoice->id,
                    ],
                    'App\\Models\\Invoice',
                    $invoice->id
                );
            } catch (\Throwable $e2) {
                logger()->error('Notification failed: einvoice.failed', ['invoice_id' => $invoice->id, 'error' => $e2->getMessage()]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'invoice' => $invoice->fresh(),
                'http_status' => 500,
            ];
        }
    }

    public function submissionStatus(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        $logs = EInvoiceSubmissionLog::where('model_type', 'invoice')
            ->where('model_id', $invoice->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return response()->json([
            'submission_status' => $invoice->submission_status,
            'uuid' => $invoice->uuid,
            'long_id' => $invoice->long_id,
            'qr_code_url' => $invoice->qr_code_url,
            'submission_error' => $invoice->submission_error,
            'submitted_at' => $invoice->submitted_at,
            'submission_uid' => $invoice->submission_uid,
            'logs' => $logs,
        ]);
    }

    public function cancel(Request $request, int $id): JsonResponse
    {
        $body = $request->all();
        $invoice = Invoice::findOrFail($id);

        if (! in_array($invoice->submission_status, ['processing', 'valid', 'pending'])) {
            return response()->json(['error' => 'Invoice submission status cannot be cancelled: '.$invoice->submission_status], 422);
        }

        if ($invoice->submitted_at) {
            $submittedAt = strtotime($invoice->submitted_at);
            $hoursSince = (time() - $submittedAt) / 3600;
            if ($hoursSince > 72) {
                return response()->json(['error' => 'e-Invoice can only be cancelled within 72 hours of submission'], 422);
            }
        }

        $reason = $body['reason'] ?? '';
        if (empty($reason)) {
            return response()->json(['error' => 'Cancellation reason is required'], 422);
        }

        $currentUuid = $invoice->uuid;
        if (empty($currentUuid)) {
            return response()->json(['error' => 'Invoice has no UUID — cannot cancel'], 422);
        }

        try {
            $client = new EInvoiceClient;
            $result = $client->cancelDocument($currentUuid, $reason);

            $invoice->update([
                'submission_status' => 'cancelled',
            ]);

            $this->logSubmission($invoice, 'cancel', $result, 200, true);

            try {
                $recipients = NotificationRecipientResolver::getFinance();
                NotificationService::queueToMany(
                    NotificationEvent::EINVOICE_CANCELLED,
                    $recipients,
                    [
                        'invoice_number' => $invoice->invoice_number ?? '',
                        'url' => '/finance/invoices/'.$invoice->id,
                    ],
                    'App\\Models\\Invoice',
                    $invoice->id
                );
            } catch (\Throwable $e) {
                logger()->error('Notification failed: einvoice.cancelled', ['invoice_id' => $invoice->id, 'error' => $e->getMessage()]);
            }

            return response()->json($invoice->fresh());
        } catch (\RuntimeException $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function resubmit(Request $request, int $id): JsonResponse
    {
        $invoice = Invoice::findOrFail($id);

        if ($invoice->submission_status !== 'error') {
            return response()->json(['error' => 'Only invoices with submission_status "error" can be resubmitted'], 422);
        }

        $invoice->update([
            'submission_status' => 'pending',
            'submission_error' => null,
            'last_submission_attempt' => date('Y-m-d H:i:s'),
        ]);

        $result = $this->submitInvoice($invoice);

        if ($result['success']) {
            return response()->json($result['invoice']);
        }

        return response()->json([
            'error' => $result['error'],
            'invoice' => $result['invoice'],
        ], $result['http_status']);
    }

    public function webhook(Request $request): JsonResponse
    {
        $signatureHeader = $request->header('X-MyInvois-Signature', '');
        $rawBody = $request->getContent();

        $credential = EInvoiceCredential::where('is_active', true)->first();
        if (! $credential || empty($credential->client_secret)) {
            logger()->error('EInvoice webhook — no active credential');

            return response()->json(['status' => 'error', 'message' => 'No active credential'], 500);
        }

        if (empty($signatureHeader)) {
            logger()->error('EInvoice webhook — missing signature header');

            return response()->json(['status' => 'error', 'message' => 'Missing signature'], 401);
        }

        $computed = hash_hmac('sha256', $rawBody, $this->decryptSecret($credential->client_secret));
        if (! hash_equals($computed, $signatureHeader)) {
            logger()->error('EInvoice webhook — invalid signature', [
                'computed' => $computed,
                'received' => $signatureHeader,
            ]);

            return response()->json(['status' => 'error', 'message' => 'Invalid signature'], 401);
        }

        $payload = json_decode($rawBody, true);
        if (! $payload) {
            logger()->error('EInvoice webhook — invalid JSON', ['body' => $rawBody]);

            return response()->json(['status' => 'error', 'message' => 'Invalid JSON'], 400);
        }

        $uuid = $payload['uuid'] ?? $payload['documentUUID'] ?? null;
        if (! $uuid) {
            logger()->error('EInvoice webhook — missing uuid', ['payload' => $payload]);

            return response()->json(['status' => 'error', 'message' => 'Missing UUID'], 400);
        }

        $invoice = Invoice::where('uuid', $uuid)->first();
        if (! $invoice) {
            logger()->error('EInvoice webhook — invoice not found for uuid', ['uuid' => $uuid]);

            return response()->json(['status' => 'ok', 'message' => 'Invoice not found']);
        }

        $status = $payload['status'] ?? $payload['documentStatus'] ?? null;
        $longId = $payload['longId'] ?? null;
        $qrCodeUrl = $payload['qrCodeUrl'] ?? $payload['qrCodeURL'] ?? null;
        $validatedAt = $payload['dateTimeValidated'] ?? $payload['validatedDateTime'] ?? null;
        $rejectionReason = $payload['rejectionReason'] ?? $payload['cancelReason'] ?? null;

        $data = ['source' => 'webhook'];
        if ($status) {
            $data['status'] = $status;
            $invoice->submission_status = $status;
        }
        if ($longId) {
            $invoice->long_id = $longId;
        }
        if ($qrCodeUrl) {
            $invoice->qr_code_url = $qrCodeUrl;
        }
        if ($validatedAt) {
            $invoice->einvoice_validated_at = $validatedAt;
        }
        if ($rejectionReason) {
            $invoice->submission_error = $rejectionReason;
        }

        $invoice->save();

        EInvoiceSubmissionLog::create([
            'model_type' => 'invoice',
            'model_id' => $invoice->id,
            'action' => 'webhook',
            'request_payload' => $rawBody,
            'response_payload' => json_encode($data),
            'http_status' => 200,
            'success' => true,
            'duration_ms' => 0,
        ]);

        return response()->json(['status' => 'ok']);
    }

    public function settings(Request $request): JsonResponse
    {
        $credential = EInvoiceCredential::where('is_active', true)->first();
        $company = CompanySetting::first();

        return response()->json([
            'credential' => $credential ? $credential->makeVisible(['client_id'])->toArray() : null,
            'company' => [
                'tax_id_number' => $company->tax_id_number ?? null,
                'sst_registration_no' => $company->sst_registration_no ?? null,
                'msic_code' => $company->msic_code ?? null,
                'msic_description' => $company->msic_description ?? null,
                'business_phone' => $company->business_phone ?? null,
                'business_email' => $company->business_email ?? null,
                'company_name' => $company->company_name ?? null,
                'address' => $company->address ?? null,
                'reg_no' => $company->reg_no ?? null,
                'logo_path' => $company->logo_path ?? null,
            ],
        ]);
    }

    public function updateSettings(Request $request): JsonResponse
    {
        $body = $request->all();

        if (! empty($body['credentials']) && is_array($body['credentials'])) {
            $fillableFields = ['label', 'client_id', 'environment', 'is_active', 'cert_path', 'key_path'];
            foreach ($body['credentials'] as $credData) {
                if (! empty($credData['id'])) {
                    $credential = EInvoiceCredential::find($credData['id']);
                }
                if (empty($credential)) {
                    $credential = new EInvoiceCredential;
                }

                foreach ($fillableFields as $field) {
                    if (array_key_exists($field, $credData)) {
                        $credential->{$field} = $credData[$field];
                    }
                }

                if (! empty($credData['client_secret'])) {
                    $credential->client_secret = $this->encryptSecret($credData['client_secret']);
                }

                $credential->save();
            }
        }

        if (! empty($body['company'])) {
            $company = CompanySetting::first();
            if ($company) {
                $fillableFields = [
                    'tax_id_number', 'sst_registration_no', 'msic_code',
                    'msic_description', 'business_phone', 'business_email',
                    'company_name', 'address', 'reg_no',
                ];
                $updateData = [];
                foreach ($fillableFields as $field) {
                    if (array_key_exists($field, $body['company'])) {
                        $updateData[$field] = $body['company'][$field];
                    }
                }
                if (! empty($updateData)) {
                    $company->update($updateData);
                }
            }
        }

        return $this->settings($request);
    }

    public function testConnection(Request $request): JsonResponse
    {
        try {
            $client = new EInvoiceClient;
            $result = $client->authenticate();

            return response()->json([
                'success' => true,
                'message' => 'Connected successfully',
                'expires_in' => $result['expires_in'] ?? null,
            ]);
        } catch (\RuntimeException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function getTaxCodes(Request $request): JsonResponse
    {
        $codes = TaxCode::orderBy('code')->get();

        return response()->json(['data' => $codes]);
    }

    public function manageTaxCodes(Request $request): JsonResponse
    {
        $body = $request->all();
        $codes = $body['tax_codes'] ?? $body['codes'] ?? $body ?? [];

        if (! is_array($codes)) {
            return response()->json(['error' => 'Expected array of tax codes'], 422);
        }

        foreach ($codes as $item) {
            if (empty($item['id'])) {
                TaxCode::create([
                    'code' => $item['code'] ?? '',
                    'name' => $item['name'] ?? '',
                    'rate' => $item['rate'] ?? 0,
                    'is_active' => $item['is_active'] ?? true,
                ]);
            } else {
                $taxCode = TaxCode::find($item['id']);
                if ($taxCode) {
                    $taxCode->update([
                        'code' => $item['code'] ?? $taxCode->code,
                        'name' => $item['name'] ?? $taxCode->name,
                        'rate' => $item['rate'] ?? $taxCode->rate,
                        'is_active' => $item['is_active'] ?? $taxCode->is_active,
                    ]);
                }
            }
        }

        $allCodes = TaxCode::orderBy('code')->get();

        return response()->json($allCodes);
    }

    public function uploadCert(Request $request): JsonResponse
    {
        $pemFile = $request->file('cert_pem');
        $keyFile = $request->file('cert_key');

        $credential = EInvoiceCredential::where('is_active', true)->first();
        if (! $credential) {
            $credential = new EInvoiceCredential(['is_active' => true]);
            $credential->save();
        }

        $updates = [];

        if ($pemFile && $pemFile->isValid()) {
            $pemError = FileStorageService::validateUpload($pemFile);
            if ($pemError) {
                return response()->json(['error' => $pemError], 422);
            }
            $pemPath = 'certs/cert_'.time().'.pem';
            $this->storage->put($pemPath, $pemFile->get(), 'application/x-pem-file');
            $updates['cert_path'] = $pemPath;
        }

        if ($keyFile && $keyFile->isValid()) {
            $keyError = FileStorageService::validateUpload($keyFile);
            if ($keyError) {
                return response()->json(['error' => $keyError], 422);
            }
            $keyPath = 'certs/key_'.time().'.key';
            $this->storage->put($keyPath, $keyFile->get(), 'application/x-pem-file');
            $updates['key_path'] = $keyPath;
        }

        if (empty($updates)) {
            return response()->json(['error' => 'No valid certificate files uploaded'], 422);
        }

        foreach ($updates as $field => $value) {
            $credential->{$field} = $value;
        }
        $credential->save();

        return response()->json([
            'message' => 'Certificate uploaded successfully',
            'cert_path' => $credential->cert_path,
            'key_path' => $credential->key_path,
        ]);
    }

    public function batchSubmit(Request $request): JsonResponse
    {
        $body = $request->all();
        $invoiceIds = $body['invoice_ids'] ?? [];

        if (! is_array($invoiceIds) || empty($invoiceIds)) {
            return response()->json(['error' => 'invoice_ids array is required'], 422);
        }

        $results = [];
        $successCount = 0;
        $errorCount = 0;

        foreach ($invoiceIds as $id) {
            $invoice = Invoice::find($id);
            if (! $invoice) {
                $results[] = [
                    'invoice_id' => $id,
                    'success' => false,
                    'error' => 'Invoice not found',
                ];
                $errorCount++;

                continue;
            }

            if (in_array($invoice->submission_status, ['processing', 'valid'])) {
                $results[] = [
                    'invoice_id' => (int) $id,
                    'invoice_number' => $invoice->invoice_number,
                    'success' => true,
                    'already_submitted' => true,
                ];
                $successCount++;

                continue;
            }

            $subResult = $this->submitInvoice($invoice);

            if ($subResult['success']) {
                $results[] = [
                    'invoice_id' => (int) $id,
                    'invoice_number' => $invoice->invoice_number,
                    'success' => true,
                    'data' => $subResult['invoice'],
                ];
                $successCount++;
            } else {
                $results[] = [
                    'invoice_id' => (int) $id,
                    'invoice_number' => $invoice->invoice_number,
                    'success' => false,
                    'error' => $subResult['error'],
                ];
                $errorCount++;
            }
        }

        return response()->json([
            'summary' => [
                'total' => count($invoiceIds),
                'success' => $successCount,
                'error' => $errorCount,
            ],
            'results' => $results,
        ]);
    }

    private function logSubmission(Invoice $invoice, string $action, ?array $responsePayload, int $httpStatus, bool $success): void
    {
        try {
            EInvoiceSubmissionLog::create([
                'model_type' => 'invoice',
                'model_id' => $invoice->id,
                'action' => $action,
                'request_payload' => json_encode([
                    'invoice_number' => $invoice->invoice_number,
                    'uuid' => $invoice->uuid,
                ]),
                'response_payload' => $responsePayload ? json_encode($responsePayload) : null,
                'http_status' => $httpStatus,
                'success' => $success,
                'duration_ms' => 0,
            ]);
        } catch (\Throwable $e) {
            logger()->error('EInvoiceController logSubmission failed', [
                'invoice_id' => $invoice->id,
                'action' => $action,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function encryptionKey(): string
    {
        $secret = config('app.jwt_secret', '');
        if (empty($secret)) {
            throw new \RuntimeException('JWT_SECRET environment variable is not set');
        }

        return hash('sha256', $secret, true);
    }

    private function encryptSecret(string $plaintext): string
    {
        $key = $this->encryptionKey();
        $iv = random_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $ciphertext = openssl_encrypt($plaintext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$ciphertext);
    }

    private function decryptSecret(string $encoded): string
    {
        $key = $this->encryptionKey();
        $data = base64_decode($encoded);
        $ivLen = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivLen);
        $ciphertext = substr($data, $ivLen);
        $result = openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);

        return $result !== false ? $result : '';
    }
}
