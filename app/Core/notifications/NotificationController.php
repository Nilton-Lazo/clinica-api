<?php

namespace App\Core\notifications;

use App\Core\notifications\Models\UserNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $userId  = $request->user()->id;
        $perPage = min(50, max(1, (int) $request->input('per_page', 20)));
        $unread  = $request->boolean('unread');

        $query = UserNotification::where('user_id', $userId)
            ->orderByDesc('created_at');

        if ($unread) {
            $query->whereNull('read_at');
        }

        $paginated = $query->paginate($perPage);

        $unreadCount = UserNotification::where('user_id', $userId)
            ->whereNull('read_at')
            ->count();

        return response()->json([
            'data' => $paginated->items(),
            'meta' => [
                'current_page' => $paginated->currentPage(),
                'last_page'    => $paginated->lastPage(),
                'total'        => $paginated->total(),
                'unread_count' => $unreadCount,
            ],
        ]);
    }

    public function markAsRead(Request $request, int $id): JsonResponse
    {
        $notification = UserNotification::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $notification->markAsRead();

        return response()->json(['data' => $notification]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        UserNotification::where('user_id', $request->user()->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas.',
        ]);
    }
}
