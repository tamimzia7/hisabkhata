@extends('layouts.admin')

@section('title', __('site.admin.title', ['brand' => __('site.common.brand')]))

@section('content')
@php
    $roleLabels = [
        'super-admin' => __('site.admin.role_super_admin'),
        'admin' => __('site.admin.role_admin'),
        'developer' => __('site.admin.role_developer'),
        'customer-care' => __('site.admin.role_customer_care'),
        'student' => __('site.admin.role_student'),
    ];
    $roleStyles = [
        'super-admin' => 'text-teal-400 bg-teal-500/10',
        'admin' => 'text-purple-400 bg-purple-500/10',
        'developer' => 'text-sky-400 bg-sky-500/10',
        'customer-care' => 'text-orange-400 bg-orange-500/10',
        'student' => 'text-emerald-400 bg-emerald-500/10',
    ];
    $staffCanManage = $admin->canCreateSystemUsers();
    $staffCanRemove = $admin->canRemoveSystemUsers();
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
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ localize_number($activeSubscriptionCount) }}</p>
            <p class="text-[11px] sm:text-xs text-teal-400 mt-2 font-bn">{{ __('site.admin.free_trial', ['count' => localize_number($trialCount)]) }}</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-amber-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.this_month_revenue') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display text-amber-400">৳{{ localize_number($monthlyRevenue) }}</p>
            <p class="text-[11px] sm:text-xs text-gray-400 mt-2 font-bn">{{ __('site.admin.last_month', ['amount' => '৳'.localize_number($lastMonthRevenue)]) }}</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-lime-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">{{ __('site.admin.total_support_tickets') }}</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ localize_number($totalTickets) }}</p>
            <p class="text-[11px] sm:text-xs text-red-400 mt-2 font-bn">{{ __('site.admin.unresolved', ['count' => localize_number($unresolvedTickets)]) }}</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="glass rounded-xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex justify-between items-center bg-white/5 gap-3">
                <h3 class="font-bold font-bn">{{ __('site.admin.new_registrations') }}</h3>
                <a href="#" @click.prevent="setPage('client-users')" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">{{ __('site.admin.see_all') }}</a>
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
                                <p class="text-xs text-gray-400 font-bn">{{ $roleLabels[$user->role] ?? __('site.admin.role_unknown') }}</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded font-bn shrink-0 {{ $roleStyles[$user->role] ?? 'text-gray-400 bg-white/5' }}">
                            {{ $roleLabels[$user->role] ?? __('site.admin.role_unknown') }}
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
                <a href="#" @click.prevent="setPage('admin-subscriptions')" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">{{ __('site.admin.see_all') }}</a>
            </div>
            <div class="divide-y divide-white/5">
                @forelse($payments->where('status', \App\Models\Payment::STATUS_SUCCESSFUL)->take(2) as $payment)
                    <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                        <div>
                            <p class="text-sm font-bold font-bn">{{ $payment->user?->name ?? '—' }}</p>
                            <p class="text-xs text-gray-400 font-bn">{{ $payment->payment_method ?? $payment->gateway }} • {{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y') }}</p>
                        </div>
                        <span class="text-sm font-bold text-emerald-400 shrink-0">+৳{{ localize_money($payment->amount) }}</span>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gray-400 font-bn">{{ __('subscription.admin.no_payments') }}</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- ================= 2. SYSTEM USERS ================= -->
<div x-show="page === 'system-users'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-6xl" x-data="{
        search: '',
        roleFilter: 'all',
        statusFilter: 'all',
        timeFilter: 'all',
        dateFrom: '',
        dateTo: '',
        visible: 0,
        apply() {
            const query = this.search.trim().toLowerCase();
            const cutoff = this.timeFilter === 'all' ? 0 : Date.now() - Number(this.timeFilter) * 86400000;
            const fromTime = this.dateFrom ? new Date(this.dateFrom + 'T00:00:00').getTime() : 0;
            const toTime = this.dateTo ? new Date(this.dateTo + 'T23:59:59.999').getTime() : Infinity;
            let count = 0;
            this.$refs.sysUserRows.querySelectorAll('tr[data-staff]').forEach((row) => {
                const matchesSearch = query === '' || row.dataset.staff.includes(query);
                const matchesRole = this.roleFilter === 'all' || row.dataset.role === this.roleFilter;
                const matchesStatus = this.statusFilter === 'all' || row.dataset.status === this.statusFilter;
                const joiningTime = Number(row.dataset.joining) * 1000;
                const matchesTime = (cutoff === 0 || joiningTime >= cutoff)
                    && (fromTime === 0 || joiningTime >= fromTime)
                    && (toTime === Infinity || joiningTime <= toTime);
                const show = matchesSearch && matchesRole && matchesStatus && matchesTime;
                row.style.display = show ? '' : 'none';
                if (show) {
                    count++;
                }
            });
            this.visible = count;
        },
    }" x-init="apply()">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        @if($staffCanManage)
            <p class="text-sm text-gray-400 font-bn">
                {{ count($admin->assignableStaffRoles()) > 1 ? __('site.admin.create_any_staff') : __('site.admin.create_only_customer_care') }}
            </p>
            <button @click="staffModalOpen = true" class="btn-emerald px-5 py-2.5 rounded-lg text-sm font-bold font-bn shrink-0">{{ __('site.admin.new_system_user') }}</button>
        @else
            <p class="text-sm text-orange-400 font-bn">{{ __('site.admin.no_staff_permission') }}</p>
        @endif
    </div>

    <div class="flex flex-col sm:flex-row sm:flex-wrap sm:items-center gap-3">
        <input x-model="search" @input="apply()" type="text" placeholder="{{ __('site.admin.staff_search_placeholder') }}" class="glass bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full sm:w-64 text-white focus:outline-none focus:border-emerald-500 font-bn">
        <select x-model="roleFilter" @change="apply()" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn">
            <option value="all">{{ __('site.admin.filter_all_roles') }}</option>
            @foreach($admin->assignableStaffRoles() as $roleOption)
                <option value="{{ $roleOption }}">{{ $roleLabels[$roleOption] }}</option>
            @endforeach
        </select>
        <select x-model="statusFilter" @change="apply()" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn">
            <option value="all">{{ __('site.admin.filter_all_status') }}</option>
            <option value="active">{{ __('site.admin.active') }}</option>
        </select>
        <select x-model="timeFilter" @change="apply()" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn">
            <option value="all">{{ __('site.admin.filter_all_time') }}</option>
            <option value="7">{{ __('site.admin.filter_last_7_days') }}</option>
            <option value="30">{{ __('site.admin.filter_last_30_days') }}</option>
            <option value="90">{{ __('site.admin.filter_last_90_days') }}</option>
        </select>
        <div class="flex items-center gap-2">
            <input x-model="dateFrom" @change="apply()" type="date" aria-label="{{ __('site.admin.filter_from') }}" title="{{ __('site.admin.filter_from') }}" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn [color-scheme:dark]">
            <input x-model="dateTo" @change="apply()" type="date" aria-label="{{ __('site.admin.filter_to') }}" title="{{ __('site.admin.filter_to') }}" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn [color-scheme:dark]">
        </div>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[780px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.name') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.role') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.joining') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.access_control') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5" x-ref="sysUserRows">
                    @forelse($systemUsers as $staff)
                        <tr class="hover:bg-white/5 transition" data-staff="@js(mb_strtolower($staff->name . ' ' . $staff->email))" data-role="{{ $staff->role }}" data-status="active" data-joining="{{ $staff->created_at?->timestamp ?? 0 }}">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold shrink-0">
                                        {{ mb_strtoupper(mb_substr($staff->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-white font-bn truncate">
                                            {{ $staff->name }}
                                            @if($staff->is($admin))
                                                <span class="text-xs text-gray-500 font-bn">({{ __('site.admin.you') }})</span>
                                            @endif
                                        </p>
                                        <p class="text-xs text-gray-400 truncate">{{ $staff->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bn font-bold {{ $roleStyles[$staff->role] ?? 'text-gray-400 bg-white/5' }}">
                                    {{ $roleLabels[$staff->role] ?? __('site.admin.role_unknown') }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 font-bn text-xs">{{ __('site.admin.active') }}</span></td>
                            <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs font-bn">{{ $staff->created_at?->format('d M Y') }}</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                @if($staff->is($admin))
                                    <span class="text-xs text-gray-500 font-bn">{{ __('site.admin.you') }}</span>
                                @elseif($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                    <form method="POST" action="{{ route('admin.system-users.update', $staff) }}" onsubmit="return confirm(@js(__('site.admin.confirm_role_change')))" class="inline-flex">
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="role" value="{{ $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? \App\Models\User::ROLE_ADMIN : \App\Models\User::ROLE_CUSTOMER_CARE }}">
                                        <input type="hidden" name="return_to" value="system-users">
                                        <button type="submit" @class(['relative w-11 h-6 rounded-full transition shrink-0', $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? 'bg-white/10' : 'bg-emerald-500']) :aria-pressed="@js($staff->role !== \App\Models\User::ROLE_CUSTOMER_CARE)" :title="@js($staff->role !== \App\Models\User::ROLE_CUSTOMER_CARE ? __('site.admin.access_click_to_revoke') : __('site.admin.access_click_to_grant'))" aria-label="{{ __('site.admin.access_control') }}">
                                            <span @class(['absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition-transform', $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? 'translate-x-1' : 'translate-x-6'])></span>
                                        </button>
                                    </form>
                                @else
                                    <div class="relative inline-block w-11 h-6 rounded-full bg-white/10 opacity-50 cursor-not-allowed" title="{{ __('site.admin.developer_access_restricted') }}">
                                        <span class="absolute top-1 left-1 w-4 h-4 bg-white rounded-full"></span>
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                @if($staffCanRemove && ! $staff->is($admin) && in_array($staff->role, [\App\Models\User::ROLE_ADMIN, \App\Models\User::ROLE_DEVELOPER, \App\Models\User::ROLE_CUSTOMER_CARE], true))
                                    <form method="POST" action="{{ route('admin.system-users.destroy', $staff) }}" onsubmit="return confirm(@js(__('site.admin.confirm_remove_staff')))">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-400 hover:text-red-300 font-bn text-xs px-2">{{ __('site.admin.remove') }}</button>
                                    </form>
                                @else
                                    <span class="text-gray-500 text-xs font-bn">{{ __('site.admin.unchangeable') }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_system_users') }}</td></tr>
                    @endforelse
                    @if($systemUsers->isNotEmpty())
                        <tr x-show="visible === 0" x-cloak>
                            <td colspan="6" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_matching_users') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= 3. SYSTEM USER ACTIVITY ================= -->
<div x-show="page === 'system-activity'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-6xl">
    <div class="glass rounded-xl overflow-hidden">
        <div class="px-4 sm:px-6 py-4 border-b border-white/10 bg-white/5 flex justify-between items-center gap-3">
            <h3 class="font-bold text-white font-bn">{{ __('site.admin.activity_log') }}</h3>
            <button @click="window.location.reload()" class="text-xs text-emerald-400 border border-emerald-500/30 px-3 py-1 rounded hover:bg-emerald-500/10 font-bn">{{ __('site.admin.refresh') }}</button>
        </div>
        <div class="divide-y divide-white/5">
            @forelse($activities as $activity)
                <div class="p-4 flex items-start gap-4 hover:bg-white/5 transition">
                    <div class="w-8 h-8 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold shrink-0">
                        {{ mb_strtoupper(mb_substr($activity->user?->name ?? __('site.admin.system_log'), 0, 1)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white font-bn">
                            <strong>{{ $activity->user?->name ?? __('site.admin.system_log') }}</strong>
                            {{ __('site.admin.activity_'.$activity->action, ['subject' => $activity->subject ?? '']) }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1 font-bn">
                            {{ $activity->created_at?->format('d M Y, H:i') }}
                            @if($activity->ip_address)
                                • IP: {{ $activity->ip_address }}
                            @endif
                        </p>
                    </div>
                </div>
            @empty
                <p class="p-5 text-sm text-gray-400 font-bn">{{ __('site.admin.no_activity') }}</p>
            @endforelse
        </div>
    </div>
</div>

<!-- ================= 4. DEVELOPER ACCESS CONTROL ================= -->
<div x-show="page === 'developer-access'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-6xl"    x-data="{
        search: '',
        roleFilter: 'all',
        timeFilter: 'all',
        dateFrom: '',
        dateTo: '',
        visible: 0,
        apply() {
            const query = this.search.trim().toLowerCase();
            const cutoff = this.timeFilter === 'all' ? 0 : Date.now() - Number(this.timeFilter) * 86400000;
            const fromTime = this.dateFrom ? new Date(this.dateFrom + 'T00:00:00').getTime() : 0;
            const toTime = this.dateTo ? new Date(this.dateTo + 'T23:59:59.999').getTime() : Infinity;
            let count = 0;
            this.$refs.staffRows.querySelectorAll('tr[data-staff]').forEach((row) => {
                const matchesSearch = query === '' || row.dataset.staff.includes(query);
                const matchesRole = this.roleFilter === 'all' || row.dataset.role === this.roleFilter;
                const joiningTime = Number(row.dataset.joining) * 1000;
                const matchesTime = (cutoff === 0 || joiningTime >= cutoff)
                    && (fromTime === 0 || joiningTime >= fromTime)
                    && (toTime === Infinity || joiningTime <= toTime);
                const show = matchesSearch && matchesRole && matchesTime;
                row.style.display = show ? '' : 'none';
                if (show) {
                    count++;
                }
            });
            this.visible = count;
        },
    }" x-init="apply()">
    @if($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
        <p class="text-sm text-gray-400 font-bn">{{ __('site.admin.developer_access_note') }}</p>

        <div class="flex flex-col sm:flex-row sm:items-center gap-3">
            <input x-model="search" @input="apply()" type="text" placeholder="{{ __('site.admin.staff_search_placeholder') }}" class="glass bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full sm:w-64 text-white focus:outline-none focus:border-emerald-500 font-bn">
            <select x-model="roleFilter" @change="apply()" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn">
                <option value="all">{{ __('site.admin.filter_all_roles') }}</option>
                @foreach(\App\Models\User::SYSTEM_ROLES as $roleOption)
                    <option value="{{ $roleOption }}">{{ $roleLabels[$roleOption] }}</option>
                @endforeach
            </select>
            <select x-model="timeFilter" @change="apply()" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn">
                <option value="all">{{ __('site.admin.filter_all_time') }}</option>
                <option value="7">{{ __('site.admin.filter_last_7_days') }}</option>
                <option value="30">{{ __('site.admin.filter_last_30_days') }}</option>
                <option value="90">{{ __('site.admin.filter_last_90_days') }}</option>
            </select>
            <div class="flex items-center gap-2">
                <input x-model="dateFrom" @change="apply()" type="date" aria-label="{{ __('site.admin.filter_from') }}" title="{{ __('site.admin.filter_from') }}" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn [color-scheme:dark]">
                <input x-model="dateTo" @change="apply()" type="date" aria-label="{{ __('site.admin.filter_to') }}" title="{{ __('site.admin.filter_to') }}" class="bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-sm text-white focus:outline-none focus:border-emerald-500 font-bn [color-scheme:dark]">
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.name') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.email_address') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.joining') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.access_control') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5" x-ref="staffRows">
                        @forelse($systemUsers as $staff)
                            <tr class="hover:bg-white/5 transition" data-staff="@js(mb_strtolower($staff->name . ' ' . $staff->email))" data-role="{{ $staff->role }}" data-joining="{{ $staff->created_at?->timestamp ?? 0 }}">
                                <td class="px-4 sm:px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-teal-500/20 text-teal-400 flex items-center justify-center font-bold shrink-0">
                                            {{ mb_strtoupper(mb_substr($staff->name, 0, 1)) }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="font-bold text-white font-bn truncate">
                                                {{ $staff->name }}
                                                @if($staff->is($admin))
                                                    <span class="text-xs text-gray-500 font-bn">({{ __('site.admin.you') }})</span>
                                                @endif
                                            </p>
                                            <span class="mt-1 inline-block px-2 py-0.5 rounded text-[11px] font-bn font-bold {{ $roleStyles[$staff->role] ?? 'text-gray-400 bg-white/5' }}">
                                                {{ $roleLabels[$staff->role] ?? __('site.admin.role_unknown') }}
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs truncate">{{ $staff->email }}</td>
                                <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs font-bn">{{ $staff->created_at?->format('d M Y') }}</td>
                                <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                    @if($staff->is($admin))
                                        <span class="text-xs text-gray-500 font-bn">{{ __('site.admin.you') }}</span>
                                    @else
                                        <form method="POST" action="{{ route('admin.system-users.update', $staff) }}" onsubmit="return confirm(@js(__('site.admin.confirm_role_change')))" class="inline-flex">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="role" value="{{ $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? \App\Models\User::ROLE_ADMIN : \App\Models\User::ROLE_CUSTOMER_CARE }}">
                                            <button type="submit" @class(['relative w-11 h-6 rounded-full transition shrink-0', $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? 'bg-white/10' : 'bg-emerald-500']) :aria-pressed="@js($staff->role !== \App\Models\User::ROLE_CUSTOMER_CARE)" :title="@js($staff->role !== \App\Models\User::ROLE_CUSTOMER_CARE ? __('site.admin.access_click_to_revoke') : __('site.admin.access_click_to_grant'))" aria-label="{{ __('site.admin.access_control') }}">
                                                <span @class(['absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition-transform', $staff->role === \App\Models\User::ROLE_CUSTOMER_CARE ? 'translate-x-1' : 'translate-x-6'])></span>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_system_users') }}</td></tr>
                        @endforelse
                        @if($systemUsers->isNotEmpty())
                            <tr x-show="visible === 0" x-cloak>
                                <td colspan="4" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_matching_users') }}</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="glass rounded-xl p-5 sm:p-6 border-l-4 border-orange-500">
            <p class="text-sm text-orange-400 font-bn">{{ __('site.admin.developer_access_restricted') }}</p>
        </div>
    @endif
</div>

<!-- ================= 5. ROLES & PERMISSION ================= -->
@php
    $permissionLabels = [
        'create_staff' => __('site.admin.perm_create_staff'),
        'remove_staff' => __('site.admin.perm_remove_staff'),
    ];
    $assignableRoleMap = [
        \App\Models\User::ROLE_SUPER_ADMIN => \App\Models\User::SYSTEM_ROLES,
        \App\Models\User::ROLE_ADMIN => [\App\Models\User::ROLE_DEVELOPER, \App\Models\User::ROLE_CUSTOMER_CARE],
        \App\Models\User::ROLE_DEVELOPER => [\App\Models\User::ROLE_CUSTOMER_CARE],
        \App\Models\User::ROLE_CUSTOMER_CARE => [],
    ];
    $canEditPermissions = $admin->role === \App\Models\User::ROLE_SUPER_ADMIN;
@endphp
<div x-show="page === 'roles-permission'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-6xl">
    <p class="text-sm text-gray-400 font-bn">{{ __('site.admin.permissions_note') }}</p>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <form method="POST" action="{{ route('admin.roles-permission.update') }}">
                @csrf
                <table class="w-full min-w-[680px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.perm_role') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.perm_assignable_roles') }}</th>
                            @foreach($permissionLabels as $permissionLabel)
                                <th class="px-4 sm:px-6 py-4 font-bn text-center">{{ $permissionLabel }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @foreach(\App\Models\User::SYSTEM_ROLES as $role)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4">
                                    <span class="px-2 py-1 rounded text-xs font-bn font-bold {{ $roleStyles[$role] ?? 'text-gray-400 bg-white/5' }}">{{ $roleLabels[$role] }}</span>
                                </td>
                                <td class="px-4 sm:px-6 py-4">
                                    @forelse($assignableRoleMap[$role] ?? [] as $assignableRole)
                                        <span class="px-2 py-1 rounded text-[11px] font-bn font-bold {{ $roleStyles[$assignableRole] ?? 'text-gray-400 bg-white/5' }}">{{ $roleLabels[$assignableRole] ?? $assignableRole }}</span>
                                    @empty
                                        <span class="text-xs text-gray-500 font-bn">{{ __('site.admin.perm_none') }}</span>
                                    @endforelse
                                </td>
                                @foreach($permissionLabels as $permissionKey => $permissionLabel)
                                    <td class="px-4 sm:px-6 py-4 text-center">
                                        <input type="checkbox" name="permissions[{{ $role }}][]" value="{{ $permissionKey }}" @checked($permissionMatrix[$role][$permissionKey] ?? false) @disabled(! $canEditPermissions) class="w-4 h-4 accent-emerald-500 align-middle">
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="px-4 sm:px-6 py-4 border-t border-white/10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    @if($canEditPermissions)
                        <button type="submit" class="btn-emerald px-5 py-2.5 rounded-lg text-sm font-bold font-bn">{{ __('site.admin.save_permissions') }}</button>
                    @else
                        <p class="text-sm text-orange-400 font-bn">{{ __('site.admin.permissions_restricted') }}</p>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ================= 6. CLIENT USERS ================= -->
<div x-show="page === 'client-users'" x-cloak x-transition.opacity x-data="{ search: '' }" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <p class="text-sm text-gray-400 font-bn">{{ __('site.admin.client_users_note') }}</p>
        <input x-model="search" type="text" placeholder="{{ __('site.admin.user_search_placeholder') }}" class="glass bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full sm:w-64 text-white focus:outline-none focus:border-emerald-500 font-bn">
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.client') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.type') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('site.admin.joining') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    @forelse($clientUsers as $user)
                        <tr class="hover:bg-white/5 transition" x-show="search === '' || @js(mb_strtolower($user->name . ' ' . $user->email)).includes(search.toLowerCase())">
                            <td class="px-4 sm:px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 rounded-full bg-blue-500/20 text-blue-400 flex items-center justify-center font-bold shrink-0">
                                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <p class="font-bold text-white font-bn truncate">{{ $user->name }}</p>
                                        <p class="text-xs text-gray-400 truncate">{{ $user->email }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <span class="px-2 py-1 rounded text-xs font-bn {{ $roleStyles[$user->role] ?? 'text-gray-400 bg-white/5' }}">{{ $roleLabels[$user->role] ?? __('site.admin.role_unknown') }}</span>
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
                        <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('site.admin.no_clients_found') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= 5. CLIENT SETTINGS ================= -->
<div x-show="page === 'client-settings'" x-cloak x-transition.opacity class="space-y-6 max-w-3xl"
    x-data="{ freeTrial: @js($clientSettings['free_trial']), manualAccess: @js($clientSettings['manual_access']) }">
    <div class="glass rounded-xl p-5 sm:p-6">
        <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('site.admin.client_settings_title') }}</h3>
        <form method="POST" action="{{ route('admin.client-settings.update') }}" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="free_trial" :value="freeTrial ? 1 : 0">
            <input type="hidden" name="manual_access" :value="manualAccess ? 1 : 0">

            <div class="flex items-center justify-between gap-4 p-4 bg-white/5 rounded-lg">
                <div>
                    <p class="text-sm font-bold text-white font-bn">{{ __('site.admin.free_trial_setting') }}</p>
                    <p class="text-xs text-gray-400 font-bn">{{ __('site.admin.free_trial_setting_note') }}</p>
                </div>
                <button type="button" @click="freeTrial = !freeTrial" :class="freeTrial ? 'bg-emerald-500' : 'bg-white/10'" class="relative w-11 h-6 rounded-full transition shrink-0" :aria-pressed="freeTrial" aria-label="{{ __('site.admin.free_trial_setting') }}">
                    <span :class="freeTrial ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition-transform"></span>
                </button>
            </div>

            <div class="flex items-center justify-between gap-4 p-4 bg-white/5 rounded-lg">
                <div>
                    <p class="text-sm font-bold text-white font-bn">{{ __('site.admin.manual_access_setting') }}</p>
                    <p class="text-xs text-gray-400 font-bn">{{ __('site.admin.manual_access_setting_note') }}</p>
                </div>
                <button type="button" @click="manualAccess = !manualAccess" :class="manualAccess ? 'bg-emerald-500' : 'bg-white/10'" class="relative w-11 h-6 rounded-full transition shrink-0" :aria-pressed="manualAccess" aria-label="{{ __('site.admin.manual_access_setting') }}">
                    <span :class="manualAccess ? 'translate-x-6' : 'translate-x-1'" class="absolute top-1 left-0 w-4 h-4 bg-white rounded-full transition-transform"></span>
                </button>
            </div>

            <div class="pt-2">
                <button type="submit" class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">{{ __('site.admin.save_settings') }}</button>
            </div>
        </form>
    </div>
</div>

<!-- ================= 6. SUBSCRIPTION MANAGEMENT ================= -->
<div x-show="page === 'admin-subscriptions'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <div class="flex border-b border-white/10 gap-4 sm:gap-6 overflow-x-auto admin-scroll">
        <button @click="subTab = 'transactions'" :class="subTab === 'transactions' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.payments') }}</button>
        <button @click="subTab = 'subscriptions'" :class="subTab === 'subscriptions' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.all_subscriptions') }}</button>
        <button @click="subTab = 'active'" :class="subTab === 'active' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.active_subscriptions') }}</button>
        <button @click="subTab = 'expiring'" :class="subTab === 'expiring' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('site.admin.expiring_soon') }}</button>
        <button @click="subTab = 'trials'" :class="subTab === 'trials' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.trial_usage') }}</button>
        <button @click="subTab = 'failed'" :class="subTab === 'failed' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.failed_payments') }}</button>
        <button @click="subTab = 'revenue'" :class="subTab === 'revenue' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">{{ __('subscription.admin.revenue') }}</button>
    </div>

    <div x-show="subTab === 'transactions'" x-cloak x-transition.opacity>
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <h3 class="font-bold text-white font-bn">{{ __('subscription.admin.payments') }}</h3>
        </div>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.transaction_id') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.product') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.method') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.amount') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.date') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.status') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($payments as $payment)
                            @php
                                $statusClass = match ($payment->status) {
                                    \App\Models\Payment::STATUS_SUCCESSFUL => 'bg-emerald-500/15 text-emerald-400',
                                    \App\Models\Payment::STATUS_PENDING => 'bg-amber-500/15 text-amber-400',
                                    default => 'bg-red-500/15 text-red-400',
                                };
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-300">
                                    {{ $payment->transaction_id }}
                                    @if($payment->gateway_transaction_id)
                                        <span class="block text-[10px] text-gray-500">{{ $payment->gateway_transaction_id }}</span>
                                    @endif
                                </td>
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $payment->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $payment->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4"><p class="text-xs font-bn text-gray-300">{{ __('subscription.products.'.$payment->product) }}</p><p class="text-[10px] text-gray-500 font-bn">{{ $payment->plan?->name }}</p></td>
                                <td class="px-4 sm:px-6 py-4"><span class="text-gray-300 bg-white/5 px-2 py-1 rounded text-xs font-bn">{{ $payment->payment_method ?? $payment->gateway }}</span></td>
                                <td class="px-4 sm:px-6 py-4 font-bold text-white">৳{{ localize_money($payment->amount) }} <span class="text-[10px] text-gray-500">{{ $payment->currency }}</span></td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ ($payment->paid_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                                <td class="px-4 sm:px-6 py-4"><span class="px-2 py-1 rounded text-xs font-bn {{ $statusClass }}">{{ __('subscription.status.'.$payment->status) }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_payments') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'subscriptions'" x-cloak x-transition.opacity>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.product') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.plan') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.started') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.expires') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.status') }}</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($subscriptions as $subscription)
                            @php
                                $displayStatus = $subscription->status === \App\Models\UserSubscription::STATUS_ACTIVE && $subscription->isExpired() ? 'expired' : $subscription->status;
                                $subStatusClass = match ($displayStatus) {
                                    'active' => 'bg-emerald-500/15 text-emerald-400',
                                    'suspended' => 'bg-amber-500/15 text-amber-400',
                                    default => 'bg-red-500/15 text-red-400',
                                };
                            @endphp
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $subscription->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $subscription->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $subscription->plan?->product ? __('subscription.products.'.$subscription->plan->product) : '—' }}</td>
                                <td class="px-4 sm:px-6 py-4"><p class="text-xs text-gray-300 font-bn">{{ $subscription->plan?->name }}</p><p class="text-[10px] text-gray-500 font-bn">{{ $subscription->billing_cycle ?? $subscription->plan?->billing_cycle }}</p></td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $subscription->starts_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $subscription->ends_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 sm:px-6 py-4"><span class="px-2 py-1 rounded text-xs font-bn {{ $subStatusClass }}">{{ __('subscription.status.'.$displayStatus) }}</span></td>
                                <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                    @if($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        @if($displayStatus === 'active')
                                            <form method="POST" action="{{ route('admin.subscriptions.suspend', $subscription) }}" class="inline-flex items-center gap-1">
                                                @csrf
                                                <input type="text" name="reason" placeholder="{{ __('subscription.admin.reason') }}" class="w-28 rounded border border-white/10 bg-black/30 px-2 py-1 text-[10px] text-white font-bn">
                                                <button type="submit" class="text-red-400 hover:text-red-300 text-xs font-bn px-2">{{ __('subscription.admin.suspend') }}</button>
                                            </form>
                                        @elseif($displayStatus === 'suspended')
                                            <form method="POST" action="{{ route('admin.subscriptions.reactivate', $subscription) }}" class="inline">
                                                @csrf
                                                <button type="submit" class="text-emerald-400 hover:text-emerald-300 text-xs font-bn px-2">{{ __('subscription.admin.reactivate') }}</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'active'" x-cloak x-transition.opacity>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.monthly_paid') }}</p>
                <p class="text-2xl font-bold text-emerald-400">{{ localize_number($monthlyPaidCount) }}</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.yearly_paid') }}</p>
                <p class="text-2xl font-bold text-teal-400">{{ localize_number($yearlyPaidCount) }}</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.trial_users') }}</p>
                <p class="text-2xl font-bold text-amber-400">{{ localize_number($trialCount) }}</p>
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.plan') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.expires') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">{{ __('site.admin.action') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($activeSubscriptions as $subscription)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $subscription->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $subscription->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">{{ $subscription->plan?->name }}</span></td>
                                <td class="px-4 sm:px-6 py-4 text-gray-300 text-xs font-bn">{{ $subscription->ends_at?->format('d M Y') ?? '—' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                    @if($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
                                        <form method="POST" action="{{ route('admin.subscriptions.suspend', $subscription) }}" class="inline-flex items-center gap-1">
                                            @csrf
                                            <input type="text" name="reason" placeholder="{{ __('subscription.admin.reason') }}" class="w-24 rounded border border-white/10 bg-black/30 px-2 py-1 text-[10px] text-white font-bn">
                                            <button type="submit" class="text-red-400 hover:text-red-300 text-xs font-bn px-2">{{ __('subscription.admin.suspend') }}</button>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-500">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_records') }}</td></tr>
                        @endforelse
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
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.product') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.expires') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($expiringSubscriptions as $subscription)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $subscription->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $subscription->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $subscription->plan?->product ? __('subscription.products.'.$subscription->plan->product) : '—' }}</td>
                                <td class="px-4 sm:px-6 py-4"><span class="text-amber-400 font-bold font-bn">{{ $subscription->ends_at?->format('d M Y') }}</span></td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'trials'" x-cloak x-transition.opacity>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[760px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.product') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.trial_started') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.trial_expires') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.current.remaining') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($subscriptionTrials as $trial)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $trial->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $trial->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ __('subscription.products.'.$trial->product) }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $trial->starts_at->format('d M Y') }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ $trial->ends_at->format('d M Y') }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs font-bn {{ $trial->isActive() ? 'text-emerald-400' : 'text-red-400' }}">{{ localize_number($trial->remainingDays()) }} {{ __('subscription.plans.days') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_trials') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'failed'" x-cloak x-transition.opacity>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[860px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.transaction_id') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.user') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.amount') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.admin.reason') }}</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">{{ __('subscription.payment.date') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        @forelse($failedPayments as $payment)
                            <tr class="hover:bg-white/5 transition">
                                <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-300">{{ $payment->transaction_id }}</td>
                                <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">{{ $payment->user?->name ?? '—' }}</p><p class="text-xs text-gray-400">{{ $payment->user?->email }}</p></td>
                                <td class="px-4 sm:px-6 py-4 font-bold text-white">৳{{ localize_money($payment->amount) }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-red-400 font-bn">{{ $payment->failure_reason ?? '—' }}</td>
                                <td class="px-4 sm:px-6 py-4 text-xs text-gray-300 font-bn">{{ ($payment->failed_at ?? $payment->created_at)?->format('d M Y H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 font-bn">{{ __('subscription.admin.no_records') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'revenue'" x-cloak x-transition.opacity class="space-y-5">
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.total_revenue') }}</p>
                <p class="text-2xl font-bold text-emerald-400">৳{{ localize_money($totalRevenue) }}</p>
            </div>
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.this_month_revenue') }}</p>
                <p class="text-2xl font-bold text-teal-400">৳{{ localize_money($monthlyRevenue) }}</p>
            </div>
            <div class="glass rounded-xl p-4">
                <p class="text-xs text-gray-400 font-bn mb-1">{{ __('subscription.admin.last_month_revenue') }}</p>
                <p class="text-2xl font-bold text-amber-400">৳{{ localize_money($lastMonthRevenue) }}</p>
            </div>
        </div>

        <div class="glass rounded-xl p-5">
            <h3 class="font-bold text-white font-bn mb-4">{{ __('subscription.admin.revenue') }}</h3>
            <div class="grid gap-3 sm:grid-cols-3">
                @foreach(\App\Models\SubscriptionPlan::PRODUCTS as $product)
                    <div class="rounded-xl bg-white/5 p-4">
                        <p class="text-xs text-gray-400 font-bn">{{ __('subscription.products.'.$product) }}</p>
                        <p class="mt-2 text-xl font-bold text-emerald-400">৳{{ localize_money($revenueByProduct[$product] ?? 0) }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<!-- ================= 7. PRICING CMS ================= -->
<div x-show="page === 'admin-pricing'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <p class="text-sm text-gray-400 font-bn">{{ __('subscription.admin.pricing_note') }}</p>

    @foreach($subscriptionPlans->groupBy('product') as $product => $plans)
        <div class="glass rounded-xl p-5 sm:p-6">
            <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('subscription.products.'.$product) }}</h3>
            <div class="space-y-3">
                @foreach($plans as $plan)
                    <div class="rounded-xl bg-white/5 p-4">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <p class="text-sm font-bold text-white font-bn">{{ $plan->name }}</p>
                                <p class="text-xs text-gray-400 font-bn">{{ ucfirst($plan->billing_cycle) }} · {{ $plan->slug }}</p>
                            </div>
                            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $plan->is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-red-500/15 text-red-400' }}">{{ $plan->is_active ? __('subscription.status.active') : __('subscription.status.inactive') }}</span>
                        </div>

                        @if($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
                            <form method="POST" action="{{ route('admin.pricing.update', $plan) }}" class="mt-3 grid gap-2 sm:grid-cols-5 sm:items-end">
                                @csrf
                                @method('PUT')
                                <label class="sm:col-span-2">
                                    <span class="block text-[10px] text-gray-500 font-bn">{{ __('subscription.admin.plan') }}</span>
                                    <input type="text" name="name" value="{{ $plan->name }}" class="mt-1 w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-xs text-white font-bn">
                                </label>
                                <label>
                                    <span class="block text-[10px] text-gray-500 font-bn">{{ __('subscription.payment.amount') }} (৳)</span>
                                    <input type="number" step="0.01" min="0" name="price" value="{{ $plan->price }}" class="mt-1 w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-xs text-white">
                                </label>
                                <label>
                                    <span class="block text-[10px] text-gray-500 font-bn">{{ __('subscription.plans.days') }}</span>
                                    <input type="number" min="1" name="duration_days" value="{{ $plan->duration_days }}" class="mt-1 w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-xs text-white">
                                </label>
                                <div class="flex items-center gap-2">
                                    <input type="hidden" name="is_active" value="0">
                                    <label class="flex items-center gap-1 text-xs text-gray-300 font-bn">
                                        <input type="checkbox" name="is_active" value="1" @checked($plan->is_active) class="rounded border-white/10 bg-black/30">
                                        {{ __('subscription.status.active') }}
                                    </label>
                                    <button type="submit" class="btn-emerald rounded-lg px-3 py-2 text-xs font-bold font-bn">{{ __('subscription.admin.update') }}</button>
                                </div>
                            </form>
                        @else
                            <p class="mt-3 text-xs text-gray-400 font-bn">৳{{ localize_money($plan->price) }} · {{ localize_number($plan->duration_days) }} {{ __('subscription.plans.days') }}</p>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach

    <div class="glass rounded-xl p-5 sm:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-white/10 pb-3">
            <div>
                <h3 class="text-lg font-bold text-white font-bn">{{ __('subscription.admin.gateway_title') }}</h3>
                <p class="mt-1 text-xs text-gray-400 font-bn">{{ __('subscription.admin.gateway_note') }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-[10px] font-bold font-bn {{ $gatewaySettings['configured'] ? 'bg-emerald-500/15 text-emerald-400' : 'bg-red-500/15 text-red-400' }}">{{ $gatewaySettings['configured'] ? __('subscription.admin.configured') : __('subscription.admin.not_configured') }}</span>
        </div>

        @if($admin->role === \App\Models\User::ROLE_SUPER_ADMIN)
            <form method="POST" action="{{ route('admin.payment-gateway.update') }}" class="mt-4 grid gap-3 sm:grid-cols-2">
                @csrf
                @method('PUT')
                <label>
                    <span class="block text-xs text-gray-400 font-bn">{{ __('subscription.admin.store_id') }}</span>
                    <input type="text" name="store_id" value="{{ $gatewaySettings['store_id'] }}" class="mt-1 w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white">
                </label>
                <label>
                    <span class="block text-xs text-gray-400 font-bn">{{ __('subscription.admin.store_password') }}</span>
                    <input type="password" name="store_password" autocomplete="new-password" placeholder="{{ $gatewaySettings['password_set'] ? '••••••••' : __('subscription.admin.store_password_placeholder') }}" class="mt-1 w-full rounded-lg border border-white/10 bg-black/30 px-3 py-2 text-sm text-white">
                </label>
                <label class="flex items-center gap-2 text-sm text-gray-300 font-bn">
                    <input type="hidden" name="sandbox" value="0">
                    <input type="checkbox" name="sandbox" value="1" @checked($gatewaySettings['sandbox']) class="rounded border-white/10 bg-black/30">
                    {{ __('subscription.admin.sandbox') }}
                </label>
                <div class="sm:text-right">
                    <button type="submit" class="btn-emerald rounded-lg px-5 py-2.5 text-sm font-bold font-bn">{{ __('subscription.admin.save_gateway') }}</button>
                </div>
            </form>
        @else
            <p class="mt-4 text-xs text-gray-400 font-bn">{{ $gatewaySettings['store_id'] ?: '—' }}</p>
        @endif
    </div>
</div>

<!-- ================= 8. PAGE CONTENT (CMS) ================= -->
<div x-show="page === 'admin-content'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="glass rounded-xl p-5 sm:p-6">
        <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('site.admin.home_hero_section') }}</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.main_title') }}</label>
                <input type="text" value="টাকার হিসাব সহজ, পরিষ্কার, নিশ্চিন্ত" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.sub_title') }}</label>
                <textarea rows="2" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">মেসের মিল, সংসারের খরচ, দোকানের বাকি — সব এক অ্যাপে। হিসাব রাখা এত সহজ আর কখনো হয়নি।</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.primary_button_text') }}</label>
                    <input type="text" value="ফ্রি অ্যাকাউন্ট খুলুন" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.trial_text') }}</label>
                    <input type="text" value="প্রথম মাস পুরোটাই ফ্রি — কার্ড লাগবে না" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
            </div>
            <div class="pt-4">
                <button class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">{{ __('site.admin.save') }}</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= 9. BLOG CMS ================= -->
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

<!-- ================= 10. TESTIMONIALS CMS ================= -->
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

<!-- ================= 11. PROFILE ================= -->
<div x-show="page === 'admin-profile'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="grid md:grid-cols-5 gap-4 sm:gap-6">
        <div class="glass rounded-xl p-5 sm:p-6 md:col-span-2">
            <div class="flex flex-col items-center text-center">
                @if($admin->avatarUrl())
                    <img src="{{ $admin->avatarUrl() }}" alt="{{ $admin->name }}" class="w-20 h-20 rounded-full object-cover ring-2 ring-emerald-500/40 shrink-0">
                @else
                    <div class="w-20 h-20 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full flex items-center justify-center text-[#050B14] text-3xl font-bold shrink-0">
                        {{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}
                    </div>
                @endif
                <h3 class="mt-4 text-lg font-bold text-white font-bn truncate max-w-full">{{ $admin->name }}</h3>
                <p class="text-sm text-gray-400 truncate max-w-full">{{ $admin->email }}</p>
                <span class="mt-3 text-xs px-2.5 py-1 rounded {{ $roleStyles[$admin->role] ?? 'text-gray-400 bg-white/5' }} font-bn">{{ $roleLabels[$admin->role] ?? __('site.admin.role_unknown') }}</span>

                @if($admin->avatarUrl())
                    <form method="POST" action="{{ route('admin.profile.avatar.destroy') }}" class="mt-3" onsubmit="return confirm(@js(__('site.admin.remove_photo')).concat('?'))">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="text-xs text-red-400 border border-red-400/30 px-3 py-1 rounded hover:bg-red-400/10 font-bn transition">{{ __('site.admin.remove_photo') }}</button>
                    </form>
                @endif
            </div>
            <div class="mt-5 pt-4 border-t border-white/10 space-y-3">
                <div class="flex justify-between items-center gap-3">
                    <span class="text-xs text-gray-400 font-bn shrink-0">{{ __('site.admin.role') }}</span>
                    <span class="text-xs text-white font-bn truncate">{{ $roleLabels[$admin->role] ?? __('site.admin.role_unknown') }}</span>
                </div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-xs text-gray-400 font-bn shrink-0">{{ __('site.admin.phone') }}</span>
                    <span class="text-xs text-white font-bn truncate">{{ filled($admin->phone) ? $admin->phone : '—' }}</span>
                </div>
                <div class="flex justify-between items-center gap-3">
                    <span class="text-xs text-gray-400 font-bn shrink-0">{{ __('site.admin.member_since') }}</span>
                    <span class="text-xs text-white font-bn truncate">{{ $admin->created_at?->format('d M Y') }}</span>
                </div>
                @if(filled($admin->bio))
                    <div class="pt-3 border-t border-white/10">
                        <p class="text-xs text-gray-400 font-bn mb-1">{{ __('site.admin.bio') }}</p>
                        <p class="text-sm text-gray-300 font-bn break-words">{{ $admin->bio }}</p>
                    </div>
                @endif
            </div>
        </div>

        <div class="glass-strong rounded-xl p-5 sm:p-6 md:col-span-3">
            <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">{{ __('site.admin.edit_profile') }}</h3>
            <form method="POST" action="{{ route('admin.profile.update') }}" enctype="multipart/form-data" class="space-y-4">
                @csrf
                @method('PUT')

                <div x-data="{ avatarPreview: null }">
                    <label class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.profile_photo') }}</label>
                    <div class="flex items-center gap-3">
                        <div class="w-14 h-14 rounded-full overflow-hidden shrink-0 bg-gradient-to-br from-emerald-400 to-emerald-600 flex items-center justify-center text-[#050B14] text-xl font-bold">
                            <template x-if="avatarPreview">
                                <img :src="avatarPreview" alt="" class="w-full h-full object-cover">
                            </template>
                            <template x-if="!avatarPreview">
                                @if($admin->avatarUrl())
                                    <img src="{{ $admin->avatarUrl() }}" alt="" class="w-full h-full object-cover">
                                @else
                                    <span>{{ mb_strtoupper(mb_substr($admin->name, 0, 1)) }}</span>
                                @endif
                            </template>
                        </div>
                        <div class="min-w-0 flex-1">
                            <input id="avatar" name="avatar" type="file" accept="image/jpeg,image/png,image/webp"
                                @change="avatarPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null"
                                class="block w-full text-xs text-gray-400 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-emerald-500/15 file:text-emerald-400 hover:file:bg-emerald-500/25 file:font-bn file:cursor-pointer">
                            <p class="text-xs text-gray-500 mt-1 font-bn">{{ __('site.admin.photo_hint') }}</p>
                        </div>
                    </div>
                    @error('avatar')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                </div>

                <div class="pt-2 border-t border-white/10">
                    <p class="text-xs text-gray-500 font-bn mb-3">{{ __('site.admin.profile_details') }}</p>
                    <div class="space-y-4">
                        <div>
                            <label for="name" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.full_name') }}</label>
                            <input id="name" name="name" type="text" value="{{ old('name', $admin->name) }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                            @error('name')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="email" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.email_address') }}</label>
                            <input id="email" name="email" type="email" value="{{ old('email', $admin->email) }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                            @error('email')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="phone" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.phone') }}</label>
                            <input id="phone" name="phone" type="text" value="{{ old('phone', $admin->phone) }}" placeholder="{{ __('site.admin.phone_placeholder') }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                            @error('phone')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label for="bio" class="block text-sm text-gray-400 mb-1 font-bn">{{ __('site.admin.bio') }}</label>
                            <textarea id="bio" name="bio" rows="3" placeholder="{{ __('site.admin.bio_placeholder') }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">{{ old('bio', $admin->bio) }}</textarea>
                            @error('bio')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </div>
                    </div>
                </div>

                <div class="pt-2 border-t border-white/10">
                    <p class="text-xs text-gray-500 font-bn mb-3">{{ __('site.admin.password_note') }}</p>
                    <div class="space-y-4">
                        <x-password-input id="current_password" name="current_password" :label="__('site.admin.current_password')"
                            label-class="block text-sm text-gray-400 mb-1 font-bn"
                            input-class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn"
                            autocomplete="current-password">
                            @error('current_password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                        </x-password-input>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <x-password-input id="password" name="password" :label="__('site.admin.new_password')"
                                label-class="block text-sm text-gray-400 mb-1 font-bn"
                                input-class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn"
                                autocomplete="new-password">
                                <p class="text-xs text-gray-500 mt-1 font-bn">{{ __('site.admin.new_password_hint') }}</p>
                                @error('password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
                            </x-password-input>
                            <x-password-input id="password_confirmation" name="password_confirmation" :label="__('site.admin.confirm_password')"
                                label-class="block text-sm text-gray-400 mb-1 font-bn"
                                input-class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn"
                                autocomplete="new-password" />
                        </div>
                    </div>
                </div>
                <div class="pt-2">
                    <button type="submit" class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">{{ __('site.admin.save_changes') }}</button>
                </div>
            </form>
        </div>
    </div>

    <div class="glass rounded-xl p-5 sm:p-6 border border-red-500/20">
        <h3 class="text-lg font-bold text-red-400 font-bn mb-2">{{ __('site.admin.delete_account') }}</h3>
        <p class="text-sm text-gray-400 font-bn mb-4">{{ __('site.admin.delete_account_note') }}</p>
        <form method="POST" action="{{ route('admin.profile.destroy') }}" class="flex flex-col sm:flex-row gap-3 sm:items-end"
            onsubmit="return confirm(@js(__('site.admin.delete_account_confirm')))">
            @csrf
            @method('DELETE')
            <x-password-input id="delete_password" name="delete_password" :label="__('site.admin.current_password')"
                wrapper-class="flex-1"
                label-class="block text-sm text-gray-400 mb-1 font-bn"
                input-class="w-full bg-[#050B14] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-red-500 font-bn"
                autocomplete="current-password">
                @error('delete_password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
            </x-password-input>
            <button type="submit" class="bg-red-500/15 text-red-400 border border-red-500/30 px-5 py-2 rounded-lg font-bold font-bn text-sm hover:bg-red-500/25 transition shrink-0">{{ __('site.admin.delete_account_button') }}</button>
        </form>
    </div>
</div>

<!-- ================= MODAL: NEW SYSTEM USER ================= -->
@if($staffCanManage)
<div x-show="staffModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center px-4 py-6">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm" @click="staffModalOpen = false"></div>
    <div class="glass-strong rounded-2xl w-full max-w-md p-5 sm:p-6 relative z-10 max-h-[90vh] overflow-y-auto admin-scroll" x-transition>
        <div class="flex justify-between items-center mb-4 border-b border-white/10 pb-3 gap-3">
            <h3 class="text-lg font-bold font-bn text-white">{{ __('site.admin.new_system_user_modal') }}</h3>
            <button @click="staffModalOpen = false" class="text-gray-400 hover:text-white shrink-0" aria-label="{{ __('site.admin.cancel') }}">✕</button>
        </div>

        <form method="POST" action="{{ route('admin.system-users.store') }}" class="space-y-4">
            @csrf
            <input type="hidden" name="form" value="staff">

            <div>
                <label for="staff_name" class="text-sm text-gray-400 font-bn">{{ __('site.admin.staff_name') }}</label>
                <input id="staff_name" name="name" type="text" value="{{ old('name') }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500 font-bn">
                @error('name')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="staff_email" class="text-sm text-gray-400 font-bn">{{ __('site.admin.email_address') }}</label>
                <input id="staff_email" name="email" type="email" value="{{ old('email') }}" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                @error('email')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
            </div>
            <x-password-input id="staff_password" name="password" :label="__('site.admin.staff_password')"
                label-class="text-sm text-gray-400 font-bn"
                input-class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500"
                autocomplete="new-password">
                <p class="text-xs text-gray-500 mt-1 font-bn">{{ __('site.admin.new_password_hint') }}</p>
                @error('password')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
            </x-password-input>
            <div>
                <label for="staff_role" class="text-sm text-gray-400 font-bn">{{ __('site.admin.staff_role') }}</label>
                <select id="staff_role" name="role" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500 font-bn">
                    @foreach($admin->assignableStaffRoles() as $assignableRole)
                        <option value="{{ $assignableRole }}" @selected(old('role', \App\Models\User::ROLE_CUSTOMER_CARE) === $assignableRole)>{{ $roleLabels[$assignableRole] }}</option>
                    @endforeach
                </select>
                @error('role')<p class="text-xs text-red-400 mt-1 font-bn">{{ $message }}</p>@enderror
            </div>

            <div class="flex gap-3 mt-6 border-t border-white/10 pt-4">
                <button type="button" @click="staffModalOpen = false" class="flex-1 py-2 rounded-lg bg-white/5 hover:bg-white/10 font-bold font-bn transition text-sm">{{ __('site.admin.cancel') }}</button>
                <button type="submit" class="flex-1 py-2 rounded-lg btn-emerald font-bold font-bn transition text-sm">{{ __('site.admin.create_user') }}</button>
            </div>
        </form>
    </div>
</div>
@endif

<!-- ================= MODAL: GENERAL ================= -->
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
                <input type="text" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.description_content') }}</label>
                <textarea rows="3" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.status') }}</label>
                    <select class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                        <option>{{ __('site.admin.published') }}</option>
                        <option>{{ __('site.admin.draft') }}</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-400 font-bn">{{ __('site.admin.category_field') }}</label>
                    <input type="text" class="w-full bg-[#050B14] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
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
