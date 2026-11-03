<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Notification::class);

        $query = Notification::query()
            ->where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->latest();

        $filter = $request->get('filter', 'all');
        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20);

        return view('notifications.index', [
            'notifications' => $notifications,
            'filter' => $filter,
            'counts' => [
                'all' => Notification::where('notifiable_id', $request->user()->getKey())
                    ->where('notifiable_type', User::class)
                    ->count(),
                'unread' => Notification::where('notifiable_id', $request->user()->getKey())
                    ->where('notifiable_type', User::class)
                    ->whereNull('read_at')
                    ->count(),
                'read' => Notification::where('notifiable_id', $request->user()->getKey())
                    ->where('notifiable_type', User::class)
                    ->whereNotNull('read_at')
                    ->count(),
            ],
        ]);
    }

    public function mark(Request $request): JsonResponse
    {
        $request->validate(['notification_id' => ['required', 'string', 'uuid']]);

        $notification = Notification::where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->where('id', $request->input('notification_id'))
            ->first();

        if (! $notification) {
            return response()->json(['ok' => false, 'error' => 'not_found'], 404);
        }

        $notification->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function bulkMarkRead(Request $request): JsonResponse
    {
        $this->authorize('update', Notification::class);

        $request->validate(['ids' => ['required', 'array']]);

        $ids = $request->input('ids');

        Notification::where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->whereIn('id', $ids)
            ->each->markAsRead();

        return response()->json(['ok' => true]);
    }

    public function markAllRead(Request $request): JsonResponse
    {
        $this->authorize('update', Notification::class);

        Notification::where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    public function delete(Request $request, Notification $notification): RedirectResponse
    {
        $this->authorize('delete', $notification);

        if ($notification->notifiable_id !== $request->user()->getKey()) {
            throw new AuthorizationException;
        }

        $notification->delete();

        return back();
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->authorize('delete', Notification::class);

        $request->validate(['ids' => ['required', 'array']]);

        Notification::where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->whereIn('id', $request->input('ids'))
            ->delete();

        return back();
    }

    public function getNext(Request $request): JsonResponse
    {
        $notification = Notification::where('notifiable_id', $request->user()->getKey())
            ->where('notifiable_type', User::class)
            ->whereNull('read_at')
            ->latest()
            ->first();

        if (! $notification) {
            return response()->json(['next' => null]);
        }

        $notification->markAsRead();

        return response()->json([
            'next' => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read' => true,
            ],
        ]);
    }
}
