@extends('layouts.admin')

@section('title', __('site.admin.title', ['brand' => __('site.common.brand')]))

@section('content')
@php
    $recentUsers = $users->take(2);
    $modeLabels = [
        'student' => __('site.admin.mode_student'),
        'personal' => __('site.admin.mode_personal'),
        'business' => __('site.admin.mode_business'),
        'admin' => __('site.admin.mode_admin'),
    ];
    $modeStyles = [
        'student' => 'text-emerald-400 bg-emerald-500/10',
        'personal' => 'text-teal-400 bg-teal-500/10',
        'business' => 'text-amber-400 bg-amber-500/10',
        'admin' => 'text-lime-400 bg-lime-500/10',
    ];
@endphp

<!-- ================= 1. DASHBOARD ================= -->
<div x-show="page === 'admin-dashboard'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-emerald-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.total_users') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ localize_number($totalUsers) }}</p>
            <p class="text-[11px] sm:text-xs text-emerald-400 mt-2 font-bn">{{ __('site.admin.registered_accounts') }}</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-teal-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.active_subscriptions') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ localize_number(850) }}</p>
            <p class="text-[11px] sm:text-xs text-teal-400 mt-2 font-bn">{{ __('site.admin.free_trial', ['count' => localize_number(1200)]) }}</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-amber-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.this_month_revenue') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display text-amber-400">৳{{ localize_number(41650) }}</p>
            <p class="text-[11px] sm:text-xs text-gray-400 mt-2 font-bn">{{ __('site.admin.last_month', ['amount' => '৳'.localize_number(35000)]) }}</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-lime-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.total_support_tickets') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ localize_number(12) }}</p>
            <p class="text-[11px] sm:text-xs text-red-400 mt-2 font-bn">{{ __('site.admin.unresolved', ['count' => localize_digits(3)]) }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="glass rounded-xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex justify-between items-center bg-white/5 gap-3">
                <h3 class="font-bold font-bn">{{ __('site.admin.new_registrations') }}</h3>
                <a href="#" @click.prevent="page = 'admin-users'" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">{{ __('site.admin.see_all') }}</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($recentUsers as $user)
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                        <div class="flex items-center gap-3 min-w-0">
                            <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold shrink-0">
                                {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-bold font-bn truncate">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400 font-bn">{{ __('site.admin.mode_suffix', ['mode' => $modeLabels[$user->mode] ?? $user->mode]) }}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded font-bn shrink-0 {{ $modeStyles[$user->mode] ?? 'text-gray-400 bg-white/5' }}">
                            {{ $modeLabels[$user->mode] ?? $user->mode }}
                        </span>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gray-400 font-bn">{{ __('site.admin.no_users_yet') }}</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex justify-between items-center bg-white/5 gap-3">
                <h3 class="font-bold font-bn">{{ __('site.admin.recent_payments') }}</h3>
                <a href="#" @click.prevent="page = 'admin-subscriptions'" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">{{ __('site.admin.see_all') }}</a>
            </div>
            <div class="divide-y divide-white/5">
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                    <div><p class="text-sm font-bold font-bn">করিম ভাই</p><p class="text-xs text-gray-400 font-bn">bKash • {{ __('site.admin.today') }} ১০:৩০</p></div>
                    <span class="text-sm font-bold text-emerald-400 shrink-0">+৳৪৯</span>
                </div>
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                    <div><p class="text-sm font-bold font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400 font-bn">Nagad • {{ __('site.admin.yesterday') }}</p></div>
                    <span class="text-sm font-bold text-emerald-400 shrink-0">+৳৪৯৯</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= 2. USERS ================= -->
<div x-show="page === 'admin-users'" x-cloak x-transition.opacity x-data="{ search: '' }" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <input x-model="search" type="text" placeholder="{{ __('site.admin.user_search_placeholder') }}" class="glass bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full sm:w-64 text-white focus:outline-none focus:border-emerald-500 font-bn">
        <button @click="modalOpen = true; modalTitle = @js(__('site.admin.new_user_modal'))" class="bg-emerald-500/20 text-emerald-400 px-4 py-2 rounded-lg text-sm font-bold font-bn hover:bg-emerald-500/30 transition">{{ __('site.admin.new_user') }}</button>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.name') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.type') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.joining') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($users as $user)
                        <tr class="hover:bg-white/5 transition" x-show="search === '' || @js(mb_strtolower($user->name . ' ' . $user->email)).includes(search.toLowerCase())">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center font-bold shrink-0">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-white font-bn truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bn {{ $modeStyles[$user->mode] ?? 'text-gray-400 bg-white/5' }}">{{ $modeLabels[$user->mode] ?? $user->mode }}</span>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bn {{ $user->email_verified_at ? 'text-emerald-400 bg-emerald-500/10' : 'text-amber-400 bg-amber-500/10' }}">
                                    {{ $user->email_verified_at ? __('site.admin.active') : __('site.admin.trial') }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs font-bn">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button @click="modalOpen = true; modalTitle = @js(__('site.admin.edit_user'))" class="text-gray-400 hover:text-white font-bn text-xs px-2">{{ __('site.admin.edit') }}</button>
                                <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">{{ __('site.admin.block') }}</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_users_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= 3. SUBSCRIPTION MANAGEMENT ================= -->
<div x-show="page === 'admin-subscriptions'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <div class="flex border-b border-white/10 gap-4 sm:gap-6 overflow-x-auto admin-scroll">
        <button @click="subTab = 'transactions'" :class="subTab === 'transactions' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('site.admin.transactions') }}</button>
        <button @click="subTab = 'active'" :class="subTab === 'active' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('site.admin.active_subscribers') }}</button>
        <button @click="subTab = 'expiring'" :class="subTab === 'expiring' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('site.admin.expiring_soon') }}</button>
    </div>

    <div x-show="subTab === 'transactions'" x-cloak x-transition.opacity>
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <h3 class="font-bold text-white font-bn">{{ __('site.admin.all_payment_records') }}</h3>
            <button class="glass px-4 py-2 rounded-lg text-sm text-gray-300 font-bn hover:text-white transition">{{ __('site.admin.export_csv') }}</button>
        </div>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.trx_id') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.user') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.gateway') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.amount') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923401</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">করিম ভাই</p><p class="text-xs text-gray-400">karim@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-pink-400 bg-pink-500/10 px-2 py-1 rounded text-xs font-bn">bKash</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.success') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">{{ __('site.admin.receipt') }}</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923399</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400">sadia@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-orange-400 bg-orange-500/10 px-2 py-1 rounded text-xs font-bn">Nagad</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.success') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">{{ __('site.admin.receipt') }}</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923380</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">তানভীর হাসান</p><p class="text-xs text-gray-400">tanvir@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-purple-400 bg-purple-500/10 px-2 py-1 rounded text-xs font-bn">Rocket</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-red-400 bg-red-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.failed') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">{{ __('site.admin.retry') }}</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'active'" x-cloak x-transition.opacity>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('site.admin.monthly_paid') }}</p>
                <p class="text-2xl font-bold text-emerald-400">৬৪০</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('site.admin.yearly_paid') }}</p>
                <p class="text-2xl font-bold text-teal-400">২১০</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('site.admin.free_trial_count') }}</p>
                <p class="text-2xl font-bold text-amber-400">১,২০০</p>
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.plan') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.expire_date') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">করিম ভাই</p><p class="text-xs text-gray-400">karim@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.monthly') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-gray-300 text-xs font-bn">১২ ডিসেম্বর, ২০২৫</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bn px-2">{{ __('site.admin.extend_1_month') }}</button>
                                <button class="text-red-400 hover:text-red-300 text-xs font-bn px-2">{{ __('site.admin.cancel') }}</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400">sadia@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-teal-400 bg-teal-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.yearly') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-gray-300 text-xs font-bn">১ নভেম্বর, ২০২৬</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bn px-2">{{ __('site.admin.extend_1_year') }}</button>
                                <button class="text-red-400 hover:text-red-300 text-xs font-bn px-2">{{ __('site.admin.cancel') }}</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'expiring'" x-cloak x-transition.opacity>
        <div class="bg-amber-500/10 border border-amber-500/20 rounded-xl p-4 mb-4 flex gap-3 items-start">
            <span class="text-amber-400 text-xl">⚠️</span>
            <div>
                <p class="font-bold text-amber-400 font-bn">{{ __('site.admin.warning') }}</p>
                <p class="text-sm text-amber-200/70 font-bn">{{ __('site.admin.expiring_alert') }}</p>
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.expire_date') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">রাফি আহমেদ</p><p class="text-xs text-gray-400">rafi@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-red-400 font-bold font-bn">{{ __('site.admin.expires_tomorrow') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="bg-amber-500/20 text-amber-400 px-3 py-1.5 rounded text-xs font-bn hover:bg-amber-500/30 transition whitespace-nowrap">{{ __('site.admin.reminder') }}</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">ফারহানা ইসলাম</p><p class="text-xs text-gray-400">farhana@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-amber-400 font-bold font-bn">{{ __('site.admin.expires_in_days', ['count' => 3]) }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="bg-amber-500/20 text-amber-400 px-3 py-1.5 rounded text-xs font-bn hover:bg-amber-500/30 transition whitespace-nowrap">{{ __('site.admin.reminder') }}</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ================= 4. PRICING CMS ================= -->
<div x-show="page === 'admin-pricing'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3">
        <p class="text-sm text-gray-400 font-bn">{{ __('site.admin.pricing_note') }}</p>
        <button @click="modalOpen = true; modalTitle = @js(__('site.admin.new_plan'))" class="btn-emerald px-4 py-2 rounded-lg text-sm font-bold font-bn shrink-0">{{ __('site.admin.add_new_plan') }}</button>
    </div>

    <div class="grid md:grid-cols-2 gap-4 sm:gap-6">
        <div class="glass-strong rounded-xl p-5 sm:p-6 relative border border-emerald-500/30">
            <div class="flex justify-between items-start gap-3 mb-4">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white font-bn">{{ __('site.admin.monthly') }}</h3>
                    <p class="text-2xl sm:text-3xl font-bold text-emerald-400 font-display mt-2">৳৪৯<span class="text-sm text-gray-400 font-bn font-normal">{{ __('site.admin.per_month') }}</span></p>
                </div>
                <button @click="modalOpen = true; modalTitle = @js(__('site.admin.edit_monthly'))" class="text-gray-400 hover:text-white bg-white/5 px-3 py-1 rounded text-xs font-bn shrink-0">{{ __('site.admin.edit') }}</button>
            </div>
            <div class="space-y-2 mb-4">
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.all_features_unlocked') }}</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.unlimited_entries') }}</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.report_export') }}</p>
            </div>
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-white/10">
                <span class="text-xs text-gray-400 font-bn">{{ __('site.admin.status_colon') }}</span>
                <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded font-bn">{{ __('site.admin.active') }}</span>
            </div>
        </div>

        <div class="glass rounded-xl p-5 sm:p-6 relative">
            <div class="flex justify-between items-start gap-3 mb-4">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white font-bn">{{ __('site.admin.yearly') }}</h3>
                    <p class="text-2xl sm:text-3xl font-bold text-emerald-400 font-display mt-2">৳৪৯৯<span class="text-sm text-gray-400 font-bn font-normal">{{ __('site.admin.per_year') }}</span></p>
                </div>
                <button @click="modalOpen = true; modalTitle = @js(__('site.admin.edit_yearly'))" class="text-gray-400 hover:text-white bg-white/5 px-3 py-1 rounded text-xs font-bn shrink-0">{{ __('site.admin.edit') }}</button>
            </div>
            <div class="space-y-2 mb-4">
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.all_features_unlocked') }}</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.two_months_free') }}</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> {{ __('site.admin.priority_support') }}</p>
            </div>
        </div>
    </div>
</div>

<!-- ================= 5. PAGE CONTENT (CMS) ================= -->
<div x-show="page === 'admin-content'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="glass rounded-xl p-5 sm:p-6">
        <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('site.admin.home_hero_section') }}</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.main_title') }}</label>
                <input type="text" value="টাকার হিসাব সহজ, পরিষ্কার, নিশ্চিন্ত" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.sub_title') }}</label>
                <textarea rows="2" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">মেসের মিল, সংসারের খরচ, দোকানের বাকি — সব এক অ্যাপে। হিসাব রাখা এত সহজ আর কখনো হয়নি।</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.primary_button_text') }}</label>
                    <input type="text" value="ফ্রি অ্যাকাউন্ট খুলুন" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.trial_text') }}</label>
                    <input type="text" value="প্রথম মাস পুরোটাই ফ্রি — কার্ড লাগবে না" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
            </div>
            <div class="pt-4">
                <button class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">{{ __('site.admin.save') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= 6. BLOG CMS ================= -->
<div x-show="page === 'admin-blog'" x-cloak x-transition.opacity class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <h3 class="font-bold text-white font-bn">{{ __('site.admin.all_posts') }}</h3>
        <button @click="modalOpen = true; modalTitle = @js(__('site.admin.new_blog_post'))" class="btn-emerald px-4 py-2 rounded-lg text-sm font-bold font-bn">{{ __('site.admin.new_blog_post') }}</button>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.blog_title') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.category') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 sm:px-6 py-4 font-bold text-white font-bn">{{ __('site.admin.post_title_1') }}</td>
                        <td class="px-4 sm:px-6 py-4 text-gray-300 font-bn">{{ __('site.admin.guide') }}</td>
                        <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.published') }}</span></td>
                        <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                            <button @click="modalOpen = true; modalTitle = @js(__('site.admin.post_edit'))" class="text-gray-400 hover:text-white font-bn text-xs px-2">{{ __('site.admin.edit') }}</button>
                            <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">{{ __('site.admin.delete') }}</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 sm:px-6 py-4 font-bold text-white font-bn">{{ __('site.admin.post_title_2') }}</td>
                        <td class="px-4 sm:px-6 py-4 text-gray-300 font-bn">{{ __('site.admin.tips') }}</td>
                        <td class="px-4 sm:px-6 py-4"><span class="text-amber-400 bg-amber-500/10 px-2 py-1 rounded text-xs font-bn">{{ __('site.admin.draft') }}</span></td>
                        <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                            <button @click="modalOpen = true; modalTitle = @js(__('site.admin.post_edit'))" class="text-gray-400 hover:text-white font-bn text-xs px-2">{{ __('site.admin.edit') }}</button>
                            <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">{{ __('site.admin.delete') }}</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= 7. TESTIMONIALS CMS ================= -->
<div x-show="page === 'admin-reviews'" x-cloak x-transition.opacity class="space-y-6">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div class="glass rounded-xl p-5 relative">
            <div class="flex text-amber-400 mb-2">★★★★★</div>
            <p class="text-sm text-gray-300 font-bn mb-4">{{ __('site.admin.testimonial_1') }}</p>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-white/5 pt-3">
                <p class="text-xs text-white font-bn">- রাফি আহমেদ</p>
                <div class="space-x-2">
                    <button class="text-xs text-emerald-400 border border-emerald-400/30 px-2 py-1 rounded hover:bg-emerald-400/10 font-bn">{{ __('site.admin.approve') }}</button>
                    <button class="text-xs text-red-400 border border-red-400/30 px-2 py-1 rounded hover:bg-red-400/10 font-bn">{{ __('site.admin.reject') }}</button>
                </div>
            </div>
        </div>
        <div class="glass rounded-xl p-5 relative opacity-60">
            <div class="flex text-amber-400 mb-2">★★★★☆</div>
            <p class="text-sm text-gray-300 font-bn mb-4">{{ __('site.admin.testimonial_2') }}</p>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-white/5 pt-3">
                <p class="text-xs text-white font-bn">- করিম ভাই</p>
                <span class="text-xs text-gray-500 font-bn">{{ __('site.admin.pending') }}</span>
            </div>
        </div>
    </div>
</div>

<!-- ================= 8. PROFILE ================= -->
<div x-show="page === 'admin-profile'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="grid md:grid-cols-5 gap-4 sm:gap-6">
        <div class="glass rounded-xl p-5 sm:p-6 md:col-span-2">
            <div class="flex flex-col items-center text-center">
                <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-[#06130D] text-3xl font-bold shrink-0">
                    {{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}
                </div>
                <h3 class="mt-4 text-lg font-bold text-white font-bn truncate max-w-full">{{ $admin->name }}</h3>
                <p class="text-sm text-gray-400 truncate max-w-full">{{ $admin->email }}</p>
                <span class="mt-3 text-xs px-2.5 py-1 rounded bg-lime-500/10 text-lime-400 font-bn">{{ __('site.admin.mode_admin') }}</span>
            </div>
            <div class="mt-5 pt-4 border-t border-white/10 space-y-3">
                <div class="flex justify-between items-center gap-3">
                    <span class="text-xs text-gray-400 font-bn shrink-0">{{ __('site.admin.role') }}</span>
                    <span class="text-xs text-white font-bn truncate">{{ __('site.admin.mode_admin') }}</span>
                </div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-xs text-gray-400 font-bn shrink-0">{{ __('site.admin.member_since') }}</span>
                    <span class="text-xs text-white font-bn truncate">{{ $admin->created_at?->format('d M Y') }}</span>
                </div>
            </div>
        </div>

        <div class="glass-strong rounded-xl p-5 sm:p-6 md:col-span-3">
            <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('site.admin.edit_profile') }}</h3>
            <form method="POST" action="{{ route('admin.profile.update') }}" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label for="name" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.full_name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name', $admin->name) }}" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                    @error('name')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="email" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.email_address') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email', $admin->email) }}" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                    @error('email')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                </div>
                <div class="pt-2 border-t border-white/10">
                    <p class="text-xs text-gray-500 font-bn mb-3">{{ __('site.admin.password_note') }}</p>
                    <div class="space-y-4">
                        <div>
                            <label for="current_password" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.current_password') }}</label>
                            <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                            @error('current_password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label for="password" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.new_password') }}</label>
                                <input id="password" name="password" type="password" autocomplete="new-password" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                                <p class="text-xs text-gray-500 mt-1 font-bn">{{ __('site.admin.new_password_hint') }}</p>
                                @error('password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label for="password_confirmation" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.confirm_password') }}</label>
                                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">{{ __('site.admin.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= MODAL ================= -->
<div x-show="modalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="modalOpen = false"></div>
    <div class="glass-strong rounded-2xl w-full max-w-lg p-5 sm:p-6 relative z-10 max-h-[90vh] overflow-y-auto admin-scroll" x-transition>
        <div class="flex justify-between items-center mb-4 border-b border-white/10 pb-3 gap-3">
            <h3 class="text-lg font-bold font-bn text-white" x-text="modalTitle"></h3>
            <button @click="modalOpen = false" class="text-gray-400 hover:text-white shrink-0">✕</button>
        </div>

        <div class="space-y-4">
            <div>
                <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.title_name') }}</label>
                <input type="text" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.description_content') }}</label>
                <textarea rows="3" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.status') }}</label>
                    <select class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                        <option>{{ __('site.admin.published') }}</option>
                        <option>{{ __('site.admin.draft') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.category_field') }}</label>
                    <input type="text" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6 border-t border-white/10 pt-4">
            <button @click="modalOpen = false" class="flex-1 py-2 rounded-lg bg-white/5 hover:bg-white/10 font-bold font-bn transition text-sm">{{ __('site.admin.cancel') }}</button>
            <button @click="modalOpen = false" class="flex-1 py-2 rounded-lg btn-emerald font-bold font-bn transition text-sm">{{ __('site.admin.save') }}</button>
        </div>
    </div>
</div>
@endsection
