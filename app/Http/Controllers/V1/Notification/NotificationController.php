<?php

namespace App\Http\Controllers\V1\Notification;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'unread' => ['nullable', 'boolean'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        $user = $request->user();
        $perPage = (int) ($validated['per_page'] ?? 20);
        $unread = $validated['unread'] ?? null;

        $query = $user->notifications()->orderByDesc('created_at');

        if (!is_null($unread)) {
            $query = $unread ? $user->unreadNotifications() : $user->readNotifications();
            $query->orderByDesc('created_at');
        }

        $items = $query->paginate($perPage);

        return response()->json([
            'data' => $items->items(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'per_page' => $items->perPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function markRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->firstOrFail();
        $notification->markAsRead();

        return response()->json([], 204);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();
        return response()->json([], 204);
    }
}
