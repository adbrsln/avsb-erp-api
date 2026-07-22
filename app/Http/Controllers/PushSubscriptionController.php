<?php

namespace App\Http\Controllers;

use App\Models\PushSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function subscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->all();

        if (empty($data['endpoint']) || empty($data['auth']) || empty($data['p256dh'])) {
            return response()->json(['error' => 'endpoint, auth, and p256dh are required'], 422);
        }

        $sub = PushSubscription::updateOrCreate(
            ['endpoint' => $data['endpoint']],
            [
                'user_id' => $user->id,
                'auth_key' => $data['auth'],
                'p256dh_key' => $data['p256dh'],
                'user_agent' => $data['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? null),
            ]
        );

        return response()->json(['id' => $sub->id], 201);
    }

    public function unsubscribe(Request $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->all();

        $query = PushSubscription::where('user_id', $user->id);

        if (! empty($data['endpoint'])) {
            $query->where('endpoint', $data['endpoint']);
        }

        $query->delete();

        return response()->noContent();
    }
}
