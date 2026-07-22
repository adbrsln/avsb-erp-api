<?php

namespace App\Services;

use App\Models\EInvoiceCredential;
use App\Models\EInvoiceSubmissionLog;
use Carbon\Carbon;

class EInvoiceClient
{
    private const SANDBOX_BASE = 'https://preprod-api.myinvois.hasil.gov.my';

    private const PRODUCTION_BASE = 'https://api.myinvois.hasil.gov.my';

    private ?EInvoiceCredential $credential = null;

    private function credential(): EInvoiceCredential
    {
        if ($this->credential === null) {
            $this->credential = EInvoiceCredential::where('is_active', true)->first();
            if (! $this->credential) {
                throw new \RuntimeException('No active e-invoice credential found.');
            }
        }

        return $this->credential;
    }

    private function getBaseUrl(): string
    {
        $cred = $this->credential();

        return $cred->environment === 'production' ? self::PRODUCTION_BASE : self::SANDBOX_BASE;
    }

    public function authenticate(): array
    {
        $cred = $this->credential();
        $payload = [
            'client_id' => $cred->client_id,
            'client_secret' => $cred->client_secret,
            'grant_type' => 'client_credentials',
            'scope' => 'InvoicingAPI',
        ];

        $response = $this->request('POST', '/connect/token', $payload, false);

        $cred->access_token = $response['access_token'] ?? null;
        $cred->token_expires_at = isset($response['expires_in'])
            ? Carbon::now()->addSeconds((int) $response['expires_in'])
            : Carbon::now()->addHour();
        $cred->save();

        return $response;
    }

    public function submitDocument(array $doc): array
    {
        return $this->request('POST', '/api/v1.0/documentsubmissions', $doc);
    }

    public function getSubmission(string $submissionUid): array
    {
        return $this->request('GET', "/api/v1.0/documentsubmissions/{$submissionUid}");
    }

    public function cancelDocument(string $uuid, string $reason): array
    {
        return $this->request('PUT', "/api/v1.0/documents/{$uuid}/state", [
            'status' => 'cancelled',
            'reason' => $reason,
        ]);
    }

    public function rejectDocument(string $uuid, string $reason): array
    {
        return $this->request('PUT', "/api/v1.0/documents/{$uuid}/state", [
            'status' => 'rejected',
            'reason' => $reason,
        ]);
    }

    public function getDocument(string $uuid): array
    {
        return $this->request('GET', "/api/v1.0/documents/{$uuid}/raw");
    }

    public function getDocumentDetail(string $uuid): array
    {
        return $this->request('GET', "/api/v1.0/documents/{$uuid}/details");
    }

    public function searchDocuments(array $filters): array
    {
        return $this->request('GET', '/api/v1.0/documents/search?'.http_build_query($filters));
    }

    private function getToken(): string
    {
        $cred = $this->credential();

        if ($cred->access_token && $cred->token_expires_at && Carbon::now()->lt($cred->token_expires_at)) {
            return $cred->access_token;
        }

        $auth = $this->authenticate();

        return $auth['access_token'] ?? '';
    }

    private function request(string $method, string $path, ?array $body = null, bool $useAuth = true): array
    {
        $startTime = microtime(true);
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl.$path;

        $attempt = 0;
        $maxRetries = 3;
        $lastResponse = null;
        $lastHttpStatus = 0;

        while ($attempt < $maxRetries) {
            $attempt++;

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

            $headers = ['Accept: application/json'];

            if ($useAuth) {
                $token = $this->getToken();
                $headers[] = 'Authorization: Bearer '.$token;
            }

            if ($method === 'POST' || $method === 'PUT') {
                $jsonPayload = json_encode($body ?? []);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload);
                $headers[] = 'Content-Type: application/json';
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            $cred = $this->credential();
            if ($cred->cert_path && $cred->key_path) {
                curl_setopt($ch, CURLOPT_SSLCERT, $cred->cert_path);
                curl_setopt($ch, CURLOPT_SSLKEY, $cred->key_path);
            }

            $responseBody = curl_exec($ch);
            $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($curlError) {
                writeErrorLog('EInvoiceClient cURL error', [
                    'url' => $url,
                    'method' => $method,
                    'error' => $curlError,
                    'attempt' => $attempt,
                ]);
                throw new \RuntimeException('e-Invoice API connection failed: '.$curlError);
            }

            $decoded = json_decode($responseBody, true);
            $isSuccess = $httpStatus >= 200 && $httpStatus < 300;

            $lastResponse = $decoded;
            $lastHttpStatus = $httpStatus;

            if ($isSuccess) {
                $action = $this->resolveAction($method, $path, $body);
                $this->log($action, $body, $decoded, $httpStatus, true, $durationMs);

                return $decoded ?? [];
            }

            if ($httpStatus === 401) {
                $cred = $this->credential();
                $cred->access_token = null;
                $cred->token_expires_at = null;
                $cred->save();

                writeErrorLog('EInvoiceClient 401 — refreshing token', [
                    'url' => $url,
                    'method' => $method,
                    'attempt' => $attempt,
                ]);

                $attempt = 0;

                continue;
            }

            if ($httpStatus === 429 || $httpStatus >= 500) {
                $delay = pow(2, $attempt - 1);
                writeErrorLog("EInvoiceClient {$httpStatus} — retrying in {$delay}s", [
                    'url' => $url,
                    'method' => $method,
                    'attempt' => $attempt,
                ]);
                sleep($delay);

                continue;
            }

            break;
        }

        $action = $this->resolveAction($method, $path, $body);
        $this->log($action, $body, $lastResponse, $lastHttpStatus, false, (int) round((microtime(true) - $startTime) * 1000));

        $errorMsg = $lastResponse['error'] ?? $lastResponse['message'] ?? 'Unknown API error';
        if (is_array($errorMsg)) {
            $errorMsg = json_encode($errorMsg);
        }

        writeErrorLog('EInvoiceClient API error', [
            'url' => $url,
            'method' => $method,
            'http_status' => $lastHttpStatus,
            'response' => $lastResponse,
        ]);

        throw new \RuntimeException('e-Invoice API error ['.$lastHttpStatus.']: '.$errorMsg);
    }

    private function resolveAction(string $method, string $path, ?array $body = null): string
    {
        if ($path === '/connect/token') {
            return 'authenticate';
        }
        if (strpos($path, '/api/v1.0/documentsubmissions') === 0 && ! strpos($path, '/api/v1.0/documentsubmissions/')) {
            return 'submit_document';
        }
        if (preg_match('#/api/v1\.0/documentsubmissions/(\S+)#', $path)) {
            return 'get_submission';
        }
        if (preg_match('#/api/v1\.0/documents/(\S+)/state#', $path)) {
            return isset($body['status']) && $body['status'] === 'cancelled' ? 'cancel_document' : 'reject_document';
        }
        if (preg_match('#/api/v1\.0/documents/(\S+)/raw#', $path)) {
            return 'get_document';
        }
        if (preg_match('#/api/v1\.0/documents/(\S+)/details#', $path)) {
            return 'get_document_detail';
        }
        if (strpos($path, '/api/v1.0/documents/search') === 0) {
            return 'search_documents';
        }

        return 'api_call';
    }

    private function log(
        string $action,
        ?array $request,
        ?array $response,
        int $httpStatus,
        bool $success,
        int $durationMs,
        ?string $modelType = null,
        ?int $modelId = null
    ): void {
        try {
            EInvoiceSubmissionLog::create([
                'model_type' => $modelType,
                'model_id' => $modelId,
                'action' => $action,
                'request_payload' => $request ? json_encode($request) : null,
                'response_payload' => $response ? json_encode($response) : null,
                'http_status' => $httpStatus,
                'success' => $success,
                'duration_ms' => $durationMs,
            ]);
        } catch (\Throwable $e) {
            writeErrorLog('EInvoiceClient log failed', [
                'error' => $e->getMessage(),
                'action' => $action,
            ]);
        }
    }
}
