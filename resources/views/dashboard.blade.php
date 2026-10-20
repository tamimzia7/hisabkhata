@extends('layouts.app')

@section('title', 'ড্যাশবোর্ড — হিসাবখাতা')

@section('content')
@php($user = auth()->user())
<div class="min-h-screen flex relative z-10" x-data="{
    members: [
        { id:1, name:'রাফি', deposit:1500, meals:35 },
        { id:2, name:'সাকিব', deposit:1200, meals:28 },
        { id:3, name:'তানভীর', deposit:800, meals:30 }
    ],
    newMemberName: '',
    personalExpenses: [
        { id:1, cat:'খাবার', amount:8500, budget:12000 },
        { id:2, cat:'যাতায়াত', amount:3200, budget:4000 },
        { id:3, cat:'বাসা ভাড়া', amount:8000, budget:8000 }
    ],
    newExpenseName:'', newExpenseAmount:'', newExpenseBudget:'',
    businessInvoices: [
        { id:1, inv:'INV-1024', customer:'করিম ভাই', amount:2450, paid:true },
        { id:2, inv:'INV-1023', customer:'রহিম স্টোর', amount:5200, paid:false },
        { id:3, inv:'INV-1022', customer:'সালাম ট্রেডার্স', amount:1800, paid:true }
    ],
    newInvoiceCustomer:'', newInvoiceAmount:'',
    toast(msg) { toastMsg = msg; showToast = true; setTimeout(() => showToast = false, 2500); }
}">
    <!-- Sidebar -->
    <aside class="hidden md:flex w-64 flex-col glass-strong m-3 rounded-2xl p-4 sticky top-3 h-[calc(100vh-24px)]">
        <a href="{{ route('home') }}" class="flex items-center gap-2 mb-6 px-2">
            <div class="w-9 h-9 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-lg flex items-center justify-center font-bold text-black">৳</div>
            <div><div class="text-sm font-bold font-bn">হিসাবখাতা</div><div class="text-[9px] tracking-widest text-emerald-400">DASHBOARD</div></div>
        </a>
        <nav class="space-y-1 text-sm font-bn">
            <a href="{{ route('dashboard') }}" class="block px-3 py-2 rounded-lg bg-emerald-500/20 text-emerald-300">ড্যাশবোর্ড</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">এন্ট্রি</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">রিপোর্ট</a>
            <a href="#" @click.prevent class="block px-3 py-2 rounded-lg text-gray-300 hover:bg-white/5">সেটিংস</a>
        </nav>
        <div class="mt-auto">
            <div class="glass rounded-xl p-3 mb-3">
                <div class="text-xs text-gray-400 font-bn">লগইন করেছেন</div>
                <div class="text-sm font-bold font-bn">{{ $user->name }}</div>
                <div class="text-[10px] text-emerald-400 uppercase">{{ $user->mode }}</div>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="w-full glass rounded-lg py-2 text-sm text-red-300 hover:bg-red-500/10 font-bn">লগআউট</button>
            </form>
        </div>
    </aside>

    <!-- Main -->
    <main class="flex-1 p-3 md:p-6 overflow-x-hidden">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold font-display font-bn">
                    @if($user->mode === 'admin') অ্যাডমিন প্যানেল
                    @elseif($user->mode === 'personal') পার্সোনাল হিসাব ড্যাশবোর্ড
                    @elseif($user->mode === 'business') ব্যবসা / দোকান ড্যাশবোর্ড
                    @else মেস / শিক্ষার্থী ড্যাশবোর্ড
                    @endif
                </h1>
                <p class="text-sm text-gray-400 font-bn">স্বাগতম, {{ $user->name }}! আজকের হিসাব দেখে নিন</p>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="md:hidden">
                @csrf
                @method('DELETE')
                <button type="submit" class="glass px-3 py-2 rounded-lg text-sm font-bn">লগআউট</button>
            </form>
        </div>

        <!-- ===== ADMIN ===== -->
        @if($user->mode === 'admin')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মোট ব্যবহারকারী</p><p class="text-2xl font-bold text-emerald-400">৩,৫০০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">সক্রিয়</p><p class="text-2xl font-bold text-teal-400">১,২৫০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মাসিক আয়</p><p class="text-2xl font-bold text-lime-400">৳১,৩৫,০০০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মোট এন্ট্রি</p><p class="text-2xl font-bold text-orange-400">২.৫ লক্ষ</p></div>
                </div>
                <div class="grid lg:grid-cols-3 gap-4">
                    <div class="lg:col-span-2 glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">ব্যবহারকারী তালিকা</h3>
                        <div class="overflow-x-auto"><table class="w-full text-sm">
                            <thead class="text-gray-400 text-left"><tr><th class="py-2 font-bn">নাম</th><th class="font-bn">মোড</th><th class="font-bn">পেমেন্ট</th></tr></thead>
                            <tbody>
                                @foreach([['রাফি আহমেদ', 'student', true], ['সাদিয়া আক্তার', 'personal', true], ['করিম ভাই', 'business', false]] as [$name, $mode, $paid])
                                    <tr class="border-t border-white/5">
                                        <td class="py-2 font-bn">{{ $name }}</td>
                                        <td class="font-bn">{{ $mode }}</td>
                                        <td><span class="text-xs font-bn {{ $paid ? 'text-emerald-400' : 'text-orange-400' }}">{{ $paid ? 'পরিশোধিত' : 'বাকি' }}</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table></div>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">দ্রুত কাজ</h3>
                        <div class="space-y-2">
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">➕ নতুন ব্যবহারকারী</button>
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">📊 রিপোর্ট দেখুন</button>
                            <button class="w-full glass rounded-lg py-2 text-sm hover:bg-emerald-500/10 font-bn">💳 পেমেন্ট লগ</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== STUDENT / MEAL ===== -->
        @if($user->mode === 'student')
            <div>
                <div class="grid grid-cols-3 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মোট মিল</p><p class="text-2xl font-bold text-emerald-400">১৪২</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মিল রেট</p><p class="text-2xl font-bold text-teal-400">৳৪২.৫</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">ব্যালেন্স</p><p class="text-2xl font-bold text-orange-400">+৳৮৫০</p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">মেম্বারদের ব্যালেন্স</h3>
                        <template x-for="m in members" :key="m.id">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
                                <div class="flex items-center gap-3"><div class="w-8 h-8 bg-gradient-to-br from-emerald-400 to-emerald-600 rounded-full"></div><span class="text-sm font-medium font-bn" x-text="m.name"></span></div>
                                <span class="text-sm font-bold font-bn" :class="(m.deposit - (m.meals * 42.5)) >= 0 ? 'text-emerald-400' : 'text-red-400'" x-text="((m.deposit - (m.meals * 42.5)) >= 0 ? '+' : '') + '৳' + Math.abs(m.deposit - (m.meals * 42.5)).toFixed(0)"></span>
                            </div>
                        </template>
                        <div class="mt-4 flex gap-2">
                            <input x-model="newMemberName" placeholder="নতুন সদস্যের নাম" class="flex-1 glass rounded-lg px-3 py-2 text-sm font-bn">
                            <button @click="if(newMemberName){ members.push({id:Date.now(), name:newMemberName, deposit:0, meals:0}); newMemberName=''; toast('সদস্য যোগ হয়েছে'); }" class="btn-green px-4 py-2 rounded-lg text-sm font-bold font-bn">যোগ</button>
                        </div>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">আজকের মিল</h3>
                        <div class="space-y-2 text-sm font-bn">
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>সকাল</span><span class="text-emerald-400">✓ ৫ জন</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>দুপুর</span><span class="text-emerald-400">✓ ৫ জন</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>রাত</span><span class="text-gray-500">— এখনো হয়নি</span></div>
                        </div>
                        <button class="w-full mt-4 btn-green rounded-lg py-2 font-bold text-sm font-bn">➕ বাজার যোগ করুন</button>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== PERSONAL ===== -->
        @if($user->mode === 'personal')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">এই মাসের আয়</p><p class="text-2xl font-bold text-emerald-400">৳৪৫,০০০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">ব্যয়</p><p class="text-2xl font-bold text-orange-400">৳<span x-text="personalExpenses.reduce((s,e)=>s+e.amount,0)"></span></p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">সঞ্চয়</p><p class="text-2xl font-bold text-teal-400">৳১৬,৫০০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">বাজেট বাকি</p><p class="text-2xl font-bold text-lime-400">৳৩,৫০০</p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">নভেম্বরের বাজেট</h3>
                        <template x-for="e in personalExpenses" :key="e.id">
                            <div class="mb-4">
                                <div class="flex justify-between text-sm mb-2 font-bn"><span x-text="e.cat"></span><span x-text="'৳' + e.amount + ' / ৳' + e.budget"></span></div>
                                <div class="h-2 bg-white/10 rounded-full overflow-hidden"><div class="h-full bg-gradient-to-r from-emerald-400 to-teal-500 rounded-full" :style="'width:' + Math.min(100, (e.amount/e.budget)*100) + '%'"></div></div>
                            </div>
                        </template>
                        <div class="grid grid-cols-3 gap-2 mt-2">
                            <input x-model="newExpenseName" placeholder="খরচের নাম" class="glass rounded-lg px-3 py-2 text-sm col-span-1 font-bn">
                            <input x-model="newExpenseAmount" type="number" placeholder="টাকা" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                            <input x-model="newExpenseBudget" type="number" placeholder="বাজেট" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                        </div>
                        <button @click="if(newExpenseName && newExpenseAmount){ personalExpenses.push({id:Date.now(), cat:newExpenseName, amount:+newExpenseAmount, budget:+newExpenseBudget || +newExpenseAmount}); newExpenseName=''; newExpenseAmount=''; newExpenseBudget=''; toast('খরচ যোগ হয়েছে'); }" class="w-full mt-2 btn-green rounded-lg py-2 font-bold text-sm font-bn">➕ খরচ যোগ করুন</button>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">সাম্প্রতিক এন্ট্রি</h3>
                        <div class="space-y-2 text-sm font-bn">
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>🛒 বাজার</span><span class="text-red-400">−৳১,২০০</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>💼 বেতন</span><span class="text-emerald-400">+৳৪৫,০০০</span></div>
                            <div class="flex justify-between p-2 bg-white/5 rounded-lg"><span>⛽ জ্বালানি</span><span class="text-red-400">−৳৮০০</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- ===== BUSINESS ===== -->
        @if($user->mode === 'business')
            <div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-6">
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">আজকের বিক্রয়</p><p class="text-2xl font-bold text-emerald-400">৳১২,৪৫০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">মোট বাকি</p><p class="text-2xl font-bold text-orange-400">৳<span x-text="businessInvoices.filter(i=>!i.paid).reduce((s,i)=>s+i.amount,0)"></span></p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">এই মাসের লাভ</p><p class="text-2xl font-bold text-teal-400">৳৩২,০০০</p></div>
                    <div class="glass-strong rounded-xl p-4"><p class="text-xs text-gray-400 font-bn">ইনভয়েস</p><p class="text-2xl font-bold text-lime-400" x-text="businessInvoices.length"></p></div>
                </div>
                <div class="grid lg:grid-cols-2 gap-4">
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">সাম্প্রতিক ইনভয়েস</h3>
                        <template x-for="i in businessInvoices" :key="i.id">
                            <div class="flex items-center justify-between p-3 bg-white/5 rounded-lg mb-2">
                                <div><p class="text-xs font-mono text-gray-400" x-text="i.inv"></p><p class="text-sm font-medium font-bn" x-text="i.customer"></p></div>
                                <div class="text-right"><p class="text-sm font-bold font-bn" x-text="'৳' + i.amount"></p><span class="text-xs font-bn" :class="i.paid ? 'text-emerald-400' : 'text-amber-400'" x-text="i.paid ? 'পরিশোধিত' : 'বাকি'"></span></div>
                            </div>
                        </template>
                        <div class="grid grid-cols-2 gap-2 mt-2">
                            <input x-model="newInvoiceCustomer" placeholder="কাস্টমার" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                            <input x-model="newInvoiceAmount" type="number" placeholder="টাকা" class="glass rounded-lg px-3 py-2 text-sm font-bn">
                        </div>
                        <button @click="if(newInvoiceCustomer && newInvoiceAmount){ businessInvoices.unshift({id:Date.now(), inv:'INV-'+(1025+businessInvoices.length), customer:newInvoiceCustomer, amount:+newInvoiceAmount, paid:false}); newInvoiceCustomer=''; newInvoiceAmount=''; toast('ইনভয়েস তৈরি হয়েছে'); }" class="w-full mt-2 btn-green rounded-lg py-2 font-bold text-sm font-bn">➕ নতুন ইনভয়েস</button>
                    </div>
                    <div class="glass-strong rounded-2xl p-5">
                        <h3 class="font-bold mb-4 font-bn">কাস্টমার বাকি</h3>
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
