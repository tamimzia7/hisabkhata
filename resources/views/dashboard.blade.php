@extends('layouts.app')

@section('title', __('site.dashboard.title'))

@section('content')
@php($user = auth()->user())
<div class="min-h-screen flex relative z-10" x-data="{
    members: @js(__('site.dashboard.data.members')),
    newMemberName: '',
    personalExpenses: @js(__('site.dashboard.data.expenses')),
    newExpenseName:'', newExpenseAmount:'', newExpenseBudget:'',
    businessInvoices: @js(__('site.dashboard.data.invoices')),
    newInvoiceCustomer:'', newInvoiceAmount:'',
    toast(msg) { toastMsg = msg; showToast = true; setTimeout(() => showToast = false, 2500); }
}">
    <!-- Sidebar -->
    <aside class="hidden md:flex w-64 flex-col glass-strong m-3 rounded-2xl p-4 sticky top-3 h-[calc(100vh-24px)]">
        <a href="{{ route('home') }}" class="flex items-center gap-2 mb-6 px-2">
            <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black">৳</div>
            <div><div class="text-sm font-bold font-bn">{{ __('site.common.brand') }}</div><div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div></div>
        </a>
        <nav class="space-y-1 text-sm font-bn">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg bg-emerald-500/20 text-emerald-300">{{ __('site.dashboard.dashboard') }}</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.entries') }}</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.reports') }}</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">{{ __('site.dashboard.settings') }}</a>
        </nav>
        <div class="mt-auto">
            <div class="glass rounded-xl p-3 mb-3">
                <div class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.logged_in_as') }}</div>
                <div class="text-sm font-bold font-bn">{{ $user->name }}</div>
                <div class="text-[10px] text-emerald-400 uppercase">{{ $user->mode }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full glass rounded-lg py-2 text-sm text-red-300 hover:bg-red-500/10 font-bn">{{ __('site.dashboard.logout') }}</button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-3 md:p-6 overflow-x-hidden">
        <div class="flex items-center justify-between mb-6 gap-3">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold font-display font-bn">
                    @if($user->mode === 'admin') {{ __('site.dashboard.heading_admin') }}
                    @elseif($user->mode === 'personal') {{ __('site.dashboard.heading_personal') }}
                    @elseif($user->mode === 'business') {{ __('site.dashboard.heading_business') }}
                    @else {{ __('site.dashboard.heading_student') }}
                    @endif
                </h1>
                <p class="text-sm text-gray-400 font-bn">{{ __('site.dashboard.welcome', ['name' => $user->name]) }}</p>
            </div>
            <div class="flex items-center gap-2">
                @include('partials.language-switcher')
                <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="glass px-3 py-2 rounded-lg text-sm font-bn">{{ __('site.dashboard.logout') }}</button>
                </form>
            </div>
        </div>

        <!-- ===== ADMIN ===== -->
        @if($user->mode === 'admin')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.total_users') }}</p><p class="text-2xl font-bold text-emerald-400">{{ __('site.dashboard.values.total_users') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.active') }}</p><p class="text-2xl font-bold text-teal-400">{{ __('site.dashboard.values.active') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.monthly_income') }}</p><p class="text-2xl font-bold text-lime-400">{{ __('site.dashboard.values.monthly_income') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.total_entries') }}</p><p class="text-2xl font-bold text-orange-400">{{ __('site.dashboard.values.total_entries') }}</p></div>
                </div>
                <div class="grid lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.user_list') }}</h3>
                        <div class="overflow-x-auto"><table class="w-full text-sm">
                            <thead class="text-gray-400 text-left"><tr><th class="py-2 font-bn">{{ __('site.dashboard.name') }}</th><th class="font-bn">{{ __('site.dashboard.mode') }}</th><th class="font-bn">{{ __('site.dashboard.payment') }}</th></tr></thead>
                            <tbody>
                                @foreach([[__('site.dashboard.admin_user_1'), 'student', true], [__('site.dashboard.admin_user_2'), 'personal', true], [__('site.dashboard.admin_user_3'), 'business', false]] as [$name, $mode, $paid])
                                    <tr class="border-t border-white/5">
                                        <td class="py-2 font-bn">{{ $name }}</td>
                                        <td class="font-bn">{{ $mode }}</td>
                                        <td><span class="text-xs font-bn {{ $paid ? 'text-emerald-400' : 'text-orange-400' }}">{{ $paid ? __('site.dashboard.paid') : __('site.dashboard.due') }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.quick_actions') }}</h3>
                        <div class="space-y-2">
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">{{ __('site.dashboard.new_user') }}</button>
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">{{ __('site.dashboard.view_reports') }}</button>
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">{{ __('site.dashboard.payment_log') }}</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== STUDENT / MEAL ===== -->
        @if($user->mode === 'student')
            <div>
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.total_meals') }}</p><p class="text-2xl font-bold text-emerald-400">{{ __('site.dashboard.values.total_meals') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.meal_rate') }}</p><p class="text-2xl font-bold text-teal-400">{{ __('site.dashboard.values.meal_rate') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.balance') }}</p><p class="text-2xl font-bold text-orange-400">{{ __('site.dashboard.values.balance') }}</p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.member_balances') }}</h3>
                        <template x-for="m in members" :key="m.id">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
                                <div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full"></div><span class="text-sm font-medium font-bn" x-text="m.name"></span></div>
                                <span class="text-sm font-bold font-bn" :class="(m.deposit - (m.meals * 42.5)) >= 0 ? 'text-emerald-400' : 'text-red-400'" x-text="((m.deposit - (m.meals * 42.5)) >= 0 ? '+' : '') + '৳' + Math.abs(m.deposit - (m.meals * 42.5)).toFixed(0)"></span>
                            </div>
                        </template>
                        <div class="mt-4 flex gap-2">
                            <input x-model="newMemberName" placeholder="{{ __('site.dashboard.new_member_placeholder') }}" class="flex-1 glass rounded-lg px-3 py-2 text-sm font-bn">
                            <button @click="if(newMemberName){ members.push({id:Date.now(), name:newMemberName, deposit:0, meals:0}); newMemberName=''; toast(@js(__('site.dashboard.member_added'))); }" class="btn-green px-4 py-2 rounded-lg text-sm font-bold font-bn">{{ __('site.dashboard.add') }}</button>
                        </div>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.today_meals') }}</h3>
                        <div class="space-y-2 text-sm font-bn">
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.breakfast') }}</span><span class="text-emerald-400">✓ {{ __('site.dashboard.people_count') }}</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.lunch') }}</span><span class="text-emerald-400">✓ {{ __('site.dashboard.people_count') }}</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.dinner') }}</span><span class="text-gray-500">{{ __('site.dashboard.not_yet') }}</span></div>
                        </div>
                        <button class="w-full mt-4 btn-green rounded-lg py-2 font-bold text-sm font-bn">{{ __('site.dashboard.add_bazaar') }}</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== PERSONAL ===== -->
        @if($user->mode === 'personal')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.income') }}</p><p class="text-2xl font-bold text-emerald-400">{{ __('site.dashboard.values.income') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.expense') }}</p><p class="text-2xl font-bold text-orange-400">৳<span x-text="personalExpenses.reduce((s,e)=>s+e.amount,0)"></span></p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.savings') }}</p><p class="text-2xl font-bold text-teal-400">{{ __('site.dashboard.values.savings') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.budget_left') }}</p><p class="text-2xl font-bold text-lime-400">{{ __('site.dashboard.values.budget_left') }}</p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.budget_title') }}</h3>
                        <template x-for="e in personalExpenses" :key="e.id">
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-2 font-bn"><span x-text="e.cat"></span><span x-text="'৳' + e.amount + ' / ৳' + e.budget"></span></div>
                                <div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full" :style="'width:' + Math.min(100, (e.amount/e.budget)*100) + '%'"></div></div>
                            </div>
                        </template>
                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <input x-model="newExpenseName" placeholder="{{ __('site.dashboard.expense_name_placeholder') }}" class="glass rounded-lg px-3 py-2 text-sm col-span-1 font-bn">
                            <input x-model="newExpenseAmount" type="number" placeholder="{{ __('site.dashboard.amount_placeholder') }}" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                            <input x-model="newExpenseBudget" type="number" placeholder="{{ __('site.dashboard.budget_placeholder') }}" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                        </div>
                        <button @click="if(newExpenseName && newExpenseAmount){ personalExpenses.push({id:Date.now(), cat:newExpenseName, amount:+newExpenseAmount, budget:+newExpenseBudget || +newExpenseAmount}); newExpenseName=''; newExpenseAmount=''; newExpenseBudget=''; toast(@js(__('site.dashboard.expense_added'))); }" class="w-full mt-2 btn-green rounded-lg py-2 font-bold text-sm font-bn">{{ __('site.dashboard.add_expense') }}</button>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.recent_entries') }}</h3>
                        <div class="space-y-2 text-sm font-bn">
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.groceries') }}</span><span class="text-red-400">{{ __('site.dashboard.groceries_amount') }}</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.salary') }}</span><span class="text-emerald-400">{{ __('site.dashboard.salary_amount') }}</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>{{ __('site.dashboard.fuel') }}</span><span class="text-red-400">{{ __('site.dashboard.fuel_amount') }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== BUSINESS ===== -->
        @if($user->mode === 'business')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.today_sales') }}</p><p class="text-2xl font-bold text-emerald-400">{{ __('site.dashboard.values.today_sales') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.total_due') }}</p><p class="text-2xl font-bold text-orange-400">৳<span x-text="businessInvoices.filter(i=>!i.paid).reduce((s,i)=>s+i.amount,0)"></span></p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.month_profit') }}</p><p class="text-2xl font-bold text-teal-400">{{ __('site.dashboard.values.month_profit') }}</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">{{ __('site.dashboard.invoices') }}</p><p class="text-2xl font-bold text-lime-400" x-text="businessInvoices.length"></p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.recent_invoices') }}</h3>
                        <template x-for="i in businessInvoices" :key="i.id">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
                                <div><p class="text-xs font-mono text-gray-400" x-text="i.inv"></p><p class="text-sm font-medium font-bn" x-text="i.customer"></p></div>
                                <div class="text-right"><p class="text-sm font-bold font-bn" x-text="'৳' + i.amount"></p><span class="text-xs font-bn" :class="i.paid ? 'text-emerald-400' : 'text-amber-400'" x-text="i.paid ? @js(__('site.dashboard.paid')) : @js(__('site.dashboard.due'))"></span></div>
                            </div>
                        </template>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <input x-model="newInvoiceCustomer" placeholder="{{ __('site.dashboard.customer') }}" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                            <input x-model="newInvoiceAmount" type="number" placeholder="{{ __('site.dashboard.amount_placeholder') }}" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                        </div>
                        <button @click="if(newInvoiceCustomer && newInvoiceAmount){ businessInvoices.unshift({id:Date.now(), inv:'INV-'+(1025+businessInvoices.length), customer:newInvoiceCustomer, amount:+newInvoiceAmount, paid:false}); newInvoiceCustomer=''; newInvoiceAmount=''; toast(@js(__('site.dashboard.invoice_created'))); }" class="w-full mt-2 btn-green rounded-lg py-2 font-bold text-sm font-bn">{{ __('site.dashboard.new_invoice') }}</button>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">{{ __('site.dashboard.customer_due') }}</h3>
                        <template x-for="i in businessInvoices.filter(x=>!x.paid)" :key="i.id">
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg mb-2 text-sm font-bn"><span x-text="i.customer"></span><span class="text-orange-400" x-text="'৳' + i.amount"></span></div>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    </main>
</div>
@endsection
