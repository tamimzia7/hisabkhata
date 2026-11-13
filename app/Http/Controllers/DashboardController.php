<?php

namespace App\Http\Controllers;

use App\Models\AdminActivity;
use App\Models\Payment;
use App\Models\Setting;
use App\Models\SubscriptionPlan;
use App\Models\SubscriptionTrial;
use App\Models\Ticket;
use App\Models\User;
use App\Models\UserSubscription;
use App\Services\SslCommerzGateway;
use App\Services\SubscriptionPageData;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin panel pages that can be opened directly.
     *
     * @var list<string>
     */
    private const ADMIN_PAGES = [
        'admin-dashboard',
        'system-users',
        'system-activity',
        'developer-access',
        'roles-permission',
        'client-users',
        'client-settings',
        'admin-subscriptions',
        'admin-pricing',
        'admin-content',
        'admin-blog',
        'admin-reviews',
        'admin-profile',
    ];

    public function __construct(private readonly SubscriptionPageData $subscriptionPageData) {}

    /**
     * Show the dashboard for the authenticated user's mode.
     */
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        if (! $user->isAdmin()) {
            return $user->hasAnyActiveSubscription()
                ? view('dashboard')
                : $this->onboardingView($user);
        }

        return $this->adminView($request, $this->requestedPage($request));
    }

    /**
     * Show the mandatory subscription selection for clients without access.
     */
    private function onboardingView(User $user): View
    {
        $products = $this->subscriptionPageData->allProducts($user);

        return view('subscriptions.index', [
            'products' => $products,
            'overview' => $this->subscriptionPageData->overview($products),
            'currency' => SubscriptionPlan::CURRENCY_BDT,
            'onboarding' => true,
            'recommendedProduct' => SubscriptionPlan::PRODUCT_STUDENT,
        ]);
    }

    /**
     * Show the admin profile page.
     */
    public function profile(Request $request): View
    {
        $this->ensureAdmin($request);

        return $this->adminView($request, 'admin-profile');
    }

    /**
     * Update the authenticated admin's profile.
     */
    public function updateProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAdmin($request);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->getKey())],
            'phone' => ['nullable', 'string', 'max:30'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'current_password' => [
                'nullable', 'string',
                function (string $attribute, mixed $value, Closure $fail) use ($request, $user): void {
                    if ($request->filled('password') && ! Hash::check((string) $value, $user->password)) {
                        $fail(__('site.admin.current_password_incorrect'));
                    }
                },
            ],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => __('site.auth.messages.name_required'),
            'email.required' => __('site.auth.messages.email_required'),
            'email.email' => __('site.auth.messages.email_invalid'),
            'email.unique' => __('site.auth.messages.email_taken'),
            'phone.max' => __('site.admin.phone_invalid'),
            'bio.max' => __('site.admin.bio_invalid'),
            'avatar.image' => __('site.admin.avatar_invalid'),
            'avatar.mimes' => __('site.admin.avatar_invalid'),
            'avatar.max' => __('site.admin.avatar_too_large'),
            'password.min' => __('site.auth.messages.password_min'),
            'password.confirmed' => __('site.admin.password_mismatch'),
        ]);

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'bio' => $validated['bio'] ?? null,
        ]);

        if ($request->hasFile('avatar')) {
            $this->deleteAvatarFile($user);
            $user->avatar_path = Storage::disk('public')->putFile('avatars', $request->file('avatar'));
        }

        if (filled($validated['password'] ?? null)) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('admin.profile')
            ->with('toast', __('site.admin.profile_updated'));
    }

    /**
     * Remove the authenticated admin's avatar.
     */
    public function destroyAvatar(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAdmin($request);

        $this->deleteAvatarFile($user);
        $user->update(['avatar_path' => null]);

        return redirect()
            ->route('admin.profile')
            ->with('toast', __('site.admin.avatar_removed'));
    }

    /**
     * Permanently delete the authenticated admin's account.
     */
    public function destroyProfile(Request $request): RedirectResponse
    {
        $user = $request->user();
        $this->ensureAdmin($request);

        $request->validate([
            'delete_password' => ['required', 'string'],
        ], [
            'delete_password.required' => __('site.admin.current_password_required'),
        ]);

        if (! Hash::check((string) $request->input('delete_password'), $user->password)) {
            throw ValidationException::withMessages([
                'delete_password' => __('site.admin.current_password_incorrect'),
            ]);
        }

        $name = $user->name;

        $this->deleteAvatarFile($user);

        AdminActivity::create([
            'user_id' => $user->getKey(),
            'action' => 'account_deleted',
            'subject' => $name,
            'ip_address' => $request->ip(),
        ]);

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user->delete();

        return redirect()
            ->route('home')
            ->with('toast', __('site.admin.account_deleted'));
    }

    /**
     * Build the admin panel view with the data for every panel page.
     */
    private function adminView(Request $request, string $page): View
    {
        $gateway = app(SslCommerzGateway::class);

        $activeSubscriptions = UserSubscription::query()
            ->active()
            ->with(['user', 'plan'])
            ->latest('ends_at')
            ->get();

        $monthlyRevenue = Payment::query()
            ->successful()
            ->whereBetween('paid_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('amount');

        $lastMonth = now()->subMonthNoOverflow();
        $lastMonthRevenue = Payment::query()
            ->successful()
            ->whereBetween('paid_at', [$lastMonth->copy()->startOfMonth(), $lastMonth->copy()->endOfMonth()])
            ->sum('amount');

        return view('admin.dashboard', [
            'admin' => $request->user(),
            'adminPage' => $page,
            'adminSpa' => true,
            'recentUsers' => User::latest()->take(2)->get(),
            'clientUsers' => User::whereIn('role', User::CLIENT_ROLES)->latest()->get(),
            'systemUsers' => User::whereIn('role', User::SYSTEM_ROLES)->orderBy('id')->get(),
            'permissionMatrix' => User::permissionMatrix(),
            'activities' => AdminActivity::with('user')->latest()->take(20)->get(),
            'clientSettings' => [
                'free_trial' => Setting::boolean('client.free_trial', true),
                'manual_access' => Setting::boolean('client.manual_access', true),
            ],
            'totalUsers' => User::count(),
            'totalTickets' => Ticket::query()->count(),
            'unresolvedTickets' => Ticket::query()->whereIn('status', Ticket::UNRESOLVED_STATUSES)->count(),
            'subscriptions' => UserSubscription::query()->with(['user', 'plan'])->latest('id')->take(100)->get(),
            'activeSubscriptions' => $activeSubscriptions,
            'expiringSubscriptions' => UserSubscription::query()
                ->active()
                ->whereNotNull('ends_at')
                ->whereBetween('ends_at', [now(), now()->addDays(7)])
                ->with(['user', 'plan'])
                ->orderBy('ends_at')
                ->get(),
            'subscriptionTrials' => SubscriptionTrial::query()->with('user')->latest('id')->take(100)->get(),
            'payments' => Payment::query()->with(['user', 'plan'])->latest('id')->take(100)->get(),
            'failedPayments' => Payment::query()
                ->where('status', Payment::STATUS_FAILED)
                ->with(['user', 'plan'])
                ->latest('id')
                ->take(100)
                ->get(),
            'subscriptionPlans' => SubscriptionPlan::query()
                ->orderBy('product')
                ->orderBy('billing_cycle')
                ->orderBy('price')
                ->get(),
            'activeSubscriptionCount' => $activeSubscriptions->count(),
            'trialCount' => SubscriptionTrial::query()->count(),
            'monthlyPaidCount' => $activeSubscriptions->where('billing_cycle', SubscriptionPlan::BILLING_CYCLE_MONTHLY)->count(),
            'yearlyPaidCount' => $activeSubscriptions->where('billing_cycle', SubscriptionPlan::BILLING_CYCLE_YEARLY)->count(),
            'totalRevenue' => Payment::query()->successful()->sum('amount'),
            'monthlyRevenue' => $monthlyRevenue,
            'lastMonthRevenue' => $lastMonthRevenue,
            'revenueByProduct' => Payment::query()
                ->successful()
                ->whereNotNull('product')
                ->selectRaw('product, SUM(amount) as total')
                ->groupBy('product')
                ->pluck('total', 'product'),
            'gatewaySettings' => $gateway->publicSettings(),
        ]);
    }

    /**
     * Resolve the panel page requested through the query string.
     */
    private function requestedPage(Request $request): string
    {
        $page = (string) $request->query('page', 'admin-dashboard');

        return in_array($page, self::ADMIN_PAGES, true) ? $page : 'admin-dashboard';
    }

    /**
     * Abort unless the authenticated user is an admin.
     */
    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->isAdmin(), 403);
    }

    /**
     * Delete the stored avatar file for the given user.
     */
    private function deleteAvatarFile(User $user): void
    {
        if (filled($user->avatar_path)) {
            Storage::disk('public')->delete($user->avatar_path);
        }
    }
}
