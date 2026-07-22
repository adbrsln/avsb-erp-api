<?php

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Services\Notification\NotificationEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use ReflectionClass;

class NotificationPreferenceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId = $request->user()->id ?? null;
        if (! $userId) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $prefs = NotificationPreference::where('user_id', $userId)->get()->keyBy('event_type');

        $ref = new ReflectionClass(NotificationEvent::class);
        $types = [];
        foreach ($ref->getConstants() as $eventType) {
            $p = $prefs->get($eventType);
            $types[] = [
                'event_type' => $eventType,
                'email' => $p ? (bool) $p->email : true,
                'push' => $p ? (bool) $p->push : true,
                'in_app' => $p ? (bool) $p->in_app : true,
            ];
        }

        usort($types, fn ($a, $b) => $a['event_type'] <=> $b['event_type']);

        return response()->json(['data' => $types]);
    }

    public function update(Request $request, ?int $userId = null): JsonResponse
    {
        $userId = $userId ?? $request->user()->id ?? null;
        if (! $userId) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $body = $request->all();
        $eventType = $body['event_type'] ?? '';
        $channel = $body['channel'] ?? '';
        $enabled = (bool) ($body['enabled'] ?? true);

        if (! in_array($channel, ['email', 'push', 'in_app'])) {
            return response()->json(['error' => 'Invalid channel'], 422);
        }

        $constName = strtoupper(str_replace(['.', '-'], '_', $eventType));
        if (! defined(NotificationEvent::class.'::'.$constName)) {
            return response()->json(['error' => 'Invalid event_type'], 422);
        }

        NotificationPreference::updateOrCreate(
            ['user_id' => $userId, 'event_type' => $eventType],
            [$channel => $enabled]
        );

        return response()->json(['success' => true]);
    }

    public function bulkUpdate(Request $request): JsonResponse
    {
        $userId = $request->user()->id ?? null;
        if (! $userId) {
            return response()->json(['error' => 'Not authenticated'], 401);
        }

        $body = $request->all();
        $channel = $body['channel'] ?? '';
        $enabled = (bool) ($body['enabled'] ?? true);

        if (! in_array($channel, ['email', 'push', 'in_app'])) {
            return response()->json(['error' => 'Invalid channel'], 422);
        }

        $ref = new ReflectionClass(NotificationEvent::class);
        foreach ($ref->getConstants() as $eventType) {
            NotificationPreference::updateOrCreate(
                ['user_id' => $userId, 'event_type' => $eventType],
                [$channel => $enabled]
            );
        }

        return response()->json(['success' => true]);
    }
}
