<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationController extends Controller
{
    /**
     * Show the authenticated user's notifications page.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $filter = (string) $request->query('filter', 'all');

        if (! in_array($filter, ['all', 'unread', 'read'], true)) {
            $filter = 'all';
        }

        $query = $user->notifications()->latest();

        if ($filter === 'unread') {
            $query->whereNull('read_at');
        } elseif ($filter === 'read') {
            $query->whereNotNull('read_at');
        }

        $notifications = $query->paginate(20)->withQueryString();

        $counts = [
            'all' => $user->notifications()->count(),
            'unread' => $user->unreadNotifications()->count(),
            'read' => $user->notifications()->whereNotNull('read_at')->count(),
        ];

        return view(
            $user->isSystemUser() ? 'admin.notifications.index' : 'notifications.index',
            [
                'notifications' => $notifications,
                'filter' => $filter,
                'counts' => $counts,
                'adminPage' => $user->isSystemUser() ? 'notifications' : null,
            ],
        );
    }

    /**
     * Return the latest notifications for the navbar dropdown.
     */
    public function feed(Request $request): JsonResponse
    {
        $user = $request->user();

        $notifications = $user->notifications()
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'title' => $notification->data['title'] ?? __('notifications.unknown'),
                'body' => $notification->data['body'] ?? '',
                'url' => filled($notification->data['action_url'] ?? null)
                    ? route('notifications.open', $notification->id)
                    : null,
                'read' => $notification->read_at !== null,
                'created_at' => $notification->created_at?->toIso8601String(),
                'created_at_human' => $notification->created_at?->diffForHumans(),
            ]);

        return response()
            ->json([
                'unread' => $user->unreadNotifications()->count(),
                'notifications' => $notifications,
            ])
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    /**
     * Mark a single notification as read.
     */
    public function mark(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'notification_id' => ['required', 'uuid'],
        ]);

        $notification = $request->user()
            ->notifications()
            ->whereKey($validated['notification_id'])
            ->first();

        if (! $notification) {
            if ($request->expectsJson()) {
                return response()->json(['ok' => false, 'error' => 'not_found'], 404);
            }

            return back()->with('toast', __('notifications.not_found'));
        }

        $notification->markAsRead();

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread' => $request->user()->unreadNotifications()->count(),
            ]);
        }

        return back()->with('toast', __('notifications.marked_read'));
    }

    /**
     * Open a notification, mark it read, and redirect to its destination.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->markAsRead();

        $actionUrl = $record->data['action_url'] ?? null;

        if (is_string($actionUrl) && $this->isSafeInternalUrl($actionUrl)) {
            return redirect()->to($actionUrl);
        }

        return redirect()->route('dashboard');
    }

    /**
     * Mark every notification for the authenticated user as read.
     */
    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread' => 0,
            ]);
        }

        return back()->with('toast', __('notifications.all_marked_read'));
    }

    /**
     * Delete a single notification owned by the authenticated user.
     */
    public function destroy(Request $request, string $notification): JsonResponse|RedirectResponse
    {
        $record = $request->user()
            ->notifications()
            ->whereKey($notification)
            ->firstOrFail();

        $record->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('toast', __('notifications.deleted'));
    }

    /**
     * Delete several notifications owned by the authenticated user.
     */
    public function destroySelected(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['uuid'],
        ]);

        $request->user()
            ->notifications()
            ->whereIn('id', $validated['ids'])
            ->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true]);
        }

        return back()->with('toast', __('notifications.deleted'));
    }

    /**
     * Only allow local, single-slash destinations.
     */
    private function isSafeInternalUrl(string $url): bool
    {
        return str_starts_with($url, '/')
            && ! str_starts_with($url, '//')
            && ! str_contains($url, '\\')
            && ! str_contains($url, '://');
    }
}
