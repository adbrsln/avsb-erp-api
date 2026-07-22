<?php

namespace App\Http\Controllers;

use App\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $params = $request->query();
        $limit = min((int) ($params['limit'] ?? 20), 50);

        $notifications = UserNotification::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();

        $unreadCount = UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'data' => $notifications,
            'unread_count' => $unreadCount,
        ]);
    }

    public function markRead(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $notif = UserNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->firstOrFail();

        $notif->update(['is_read' => true]);

        return response()->json(null, 204);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        UserNotification::where('user_id', $user->id)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(null, 204);
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $user = $request->user();
        $deleted = UserNotification::where('id', $id)
            ->where('user_id', $user->id)
            ->delete();

        if ($deleted === 0) {
            return response()->json(null, 404);
        }

        return response()->json(null, 204);
    }

    public function deleteRead(Request $request): JsonResponse
    {
        $user = $request->user();
        UserNotification::where('user_id', $user->id)
            ->where('is_read', true)
            ->delete();

        return response()->json(null, 204);
    }
}
