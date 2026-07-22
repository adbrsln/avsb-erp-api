<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeocodeController extends Controller
{
    private function fetch(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5,
            CURLOPT_USERAGENT => 'AVSB-ERP/1.0 (erp.azamventures.com)',
            CURLOPT_FOLLOWLOCATION => true,
        ]);

        $body = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || $body === false) {
            return [];
        }

        $decoded = json_decode($body, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function search(Request $request): JsonResponse
    {
        $q = $request->input('q', '');
        if (strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $data = $this->fetch(
            'https://nominatim.openstreetmap.org/search?format=json&q='.urlencode($q).'&limit=5&countrycodes=my'
        );

        return response()->json(['data' => $data]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $lat = $request->input('lat', '');
        $lng = $request->input('lng', '');

        $data = $this->fetch(
            "https://nominatim.openstreetmap.org/reverse?format=json&lat={$lat}&lon={$lng}&accept-language=en"
        );

        return response()->json(['data' => $data]);
    }
}
