<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\User;
use App\Notifications\NotificationService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
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
     * Show the full-page notification creation form.
     */
    public function create(): View
    {
        Gate::authorize('send', Notification::class);

        return view('admin.notifications.create', [
            'notifiableUsers' => User::query()->orderBy('name')->get(),
            'roleOptions' => collect(User::CLIENT_ROLES)
                ->map(fn (string $role): array => [
                    'value' => $role,
                    'label' => __('site.admin.role_'.str_replace('-', '_', $role)),
                    'icon' => match ($role) {
                        User::ROLE_STUDENT => '🎓',
                        default => '👤',
                    },
                ])
                ->values()
                ->all(),
            'adminPage' => 'notification-create',
        ]);
    }

    /**
     * Send a notification to all users or a selected set of users.
     */
    public function send(Request $request): RedirectResponse
    {
        $actor = $request->user();

        Gate::authorize('send', Notification::class);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'recipients' => ['required', Rule::in(['all', 'role', 'selected'])],
            'roles' => ['bail', 'array', 'required_if:recipients,role', 'min:1'],
            'roles.*' => ['string', Rule::in(User::CLIENT_ROLES)],
            'recipient_ids' => ['bail', 'array', 'required_if:recipients,selected', 'min:1'],
            'recipient_ids.*' => ['integer', Rule::exists('users', 'id')],
            'action_url' => [
                'nullable',
                'string',
                'max:500',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (filled($value) && ! $this->isSafeInternalUrl((string) $value)) {
                        $fail(__('notifications.send_action_url_invalid'));
                    }
                },
            ],
        ], [
            'title.required' => __('notifications.send_title_required'),
            'title.max' => __('notifications.send_title_max'),
            'body.required' => __('notifications.send_message_required'),
            'body.max' => __('notifications.send_message_max'),
            'recipients.required' => __('notifications.send_recipients_required'),
            'recipients.in' => __('notifications.send_recipients_invalid'),
            'roles.required_if' => __('notifications.send_role_required'),
            'roles.array' => __('notifications.send_role_invalid'),
            'roles.min' => __('notifications.send_role_required'),
            'roles.*.in' => __('notifications.send_role_invalid'),
            'recipient_ids.required_if' => __('notifications.send_recipient_required'),
            'recipient_ids.array' => __('notifications.send_recipient_invalid'),
            'recipient_ids.min' => __('notifications.send_recipient_required'),
            'recipient_ids.*.integer' => __('notifications.send_recipient_invalid'),
            'recipient_ids.*.exists' => __('notifications.send_recipient_invalid'),
            'action_url.max' => __('notifications.send_action_url_invalid'),
        ]);

        $recipientIds = collect($validated['recipient_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();

        $recipients = match ($validated['recipients']) {
            'all' => User::query()->get(),
            'role' => User::query()
                ->whereIn('role', collect($validated['roles'])->unique()->values()->all())
                ->get(),
            default => User::query()->whereIn('id', $recipientIds)->get(),
        };

        if ($recipients->isEmpty()) {
            $errorKey = $validated['recipients'] === 'role' ? 'roles' : 'recipient_ids';

            throw ValidationException::withMessages([
                $errorKey => $validated['recipients'] === 'role'
                    ? __('notifications.send_role_empty')
                    : __('notifications.send_recipient_required'),
            ]);
        }

        app(NotificationService::class)->sendToUsers(
            $recipients,
            $validated['title'],
            $validated['body'],
            filled($validated['action_url'] ?? null) ? $validated['action_url'] : null,
            'info',
            null,
            $actor,
        );

        return redirect()
            ->route('notifications.index')
            ->with('toast', __('notifications.send_sent', ['count' => $recipients->count()]));
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
                'sender_name' => $notification->data['sender_name'] ?? null,
                'sender_label' => filled($notification->data['sender_name'] ?? null)
                    ? __('notifications.sent_by', ['name' => $notification->data['sender_name']])
                    : null,
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
