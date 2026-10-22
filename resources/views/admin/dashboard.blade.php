@extends('layouts.admin')

@section('title', 'অ্যাডমিন প্যানেল — ' . __('site.common.brand'))

@section('content')
@php
    $recentUsers = $users->take(2);
    $modeLabels = [
        'student' => 'স্টুডেন্ট',
        'personal' => 'পার্সোনাল',
        'business' => 'বিজনেস',
        'admin' => 'অ্যাডমিন',
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
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">মোট ইউজার</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">{{ number_format($totalUsers) }}</p>
            <p class="text-[11px] sm:text-xs text-emerald-400 mt-2 font-bn">রেজিস্টার্ড অ্যাকাউন্ট</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-teal-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">অ্যাক্টিভ সাবস্ক্রিপশন</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">৮৫০</p>
            <p class="text-[11px] sm:text-xs text-teal-400 mt-2 font-bn">ফ্রি ট্রায়াল: ১২০০</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-amber-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">এই মাসের রেভিনিউ</p>
            <p class="text-2xl sm:text-3xl font-bold font-display text-amber-400">৳৪১,৬৫০</p>
            <p class="text-[11px] sm:text-xs text-gray-400 mt-2 font-bn">গত মাসে: ৳৩৫,০০০</p>
        </div>
        <div class="glass rounded-xl p-4 sm:p-5 border-l-4 border-lime-500">
            <p class="text-xs sm:text-sm text-gray-400 font-bn mb-1">মোট সাপোর্ট টিকিট</p>
            <p class="text-2xl sm:text-3xl font-bold font-display">১২</p>
            <p class="text-[11px] sm:text-xs text-red-400 mt-2 font-bn">৩টি আনরিজলভড</p>
        </div>
    </div>

    <div class="grid lg:grid-cols-2 gap-4 sm:gap-6">
        <div class="glass rounded-xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex justify-between items-center bg-white/5 gap-3">
                <h3 class="font-bold font-bn">নতুন রেজিস্ট্রেশন</h3>
                <a href="#" @click.prevent="page = 'admin-users'" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">সব দেখুন →</a>
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
                                <p class="text-xs text-gray-400 font-bn">{{ $modeLabels[$user->mode] ?? $user->mode }} মোড</p>
                            </div>
                        </div>
                        <span class="text-xs px-2 py-1 rounded font-bn shrink-0 {{ $modeStyles[$user->mode] ?? 'text-gray-400 bg-white/5' }}">
                            {{ $modeLabels[$user->mode] ?? $user->mode }}
                        </span>
                    </div>
                @empty
                    <p class="p-5 text-sm text-gray-400 font-bn">এখনো কোনো ইউজার রেজিস্টার করেনি।</p>
                @endforelse
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="px-4 sm:px-5 py-4 border-b border-white/5 flex justify-between items-center bg-white/5 gap-3">
                <h3 class="font-bold font-bn">সাম্প্রতিক পেমেন্ট</h3>
                <a href="#" @click.prevent="page = 'admin-subscriptions'" class="text-sm text-emerald-400 font-bn hover:underline shrink-0">সব দেখুন →</a>
            </div>
            <div class="divide-y divide-white/5">
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                    <div><p class="text-sm font-bold font-bn">করিম ভাই</p><p class="text-xs text-gray-400 font-bn">bKash • আজ ১০:৩০</p></div>
                    <span class="text-sm font-bold text-emerald-400 shrink-0">+৳৪৯</span>
                </div>
                <div class="p-4 flex items-center justify-between gap-3 hover:bg-white/5 transition">
                    <div><p class="text-sm font-bold font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400 font-bn">Nagad • গতকাল</p></div>
                    <span class="text-sm font-bold text-emerald-400 shrink-0">+৳৪৯৯</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ================= 2. USERS ================= -->
<div x-show="page === 'admin-users'" x-cloak x-transition.opacity x-data="{ search: '' }" class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <input x-model="search" type="text" placeholder="ইউজার সার্চ করুন..." class="glass bg-white/5 border border-white/10 rounded-lg px-4 py-2 text-sm w-full sm:w-64 text-white focus:outline-none focus:border-emerald-500 font-bn">
        <button @click="modalOpen = true; modalTitle = 'নতুন ইউজার'" class="bg-emerald-500/20 text-emerald-400 px-4 py-2 rounded-lg text-sm font-bold font-bn hover:bg-emerald-500/30 transition">+ নতুন ইউজার</button>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[720px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">নাম</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">ধরন</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">স্ট্যাটাস</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">জয়েনিং</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">অ্যাকশন</th>
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
                                    {{ $user->email_verified_at ? 'সক্রিয়' : 'ট্রায়াল' }}
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-gray-400 text-xs font-bn">{{ $user->created_at?->format('d M Y') }}</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button @click="modalOpen = true; modalTitle = 'ইউজার এডিট'" class="text-gray-400 hover:text-white font-bn text-xs px-2">এডিট</button>
                                <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">ব্লক</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-6 text-center text-gray-400 font-bn">কোনো ইউজার পাওয়া যায়নি।</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ================= 3. SUBSCRIPTION MANAGEMENT ================= -->
<div x-show="page === 'admin-subscriptions'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6">
    <div class="flex border-b border-white/10 gap-4 sm:gap-6 overflow-x-auto admin-scroll">
        <button @click="subTab = 'transactions'" :class="subTab === 'transactions' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">💳 ট্রানজেকশন</button>
        <button @click="subTab = 'active'" :class="subTab === 'active' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">✅ অ্যাক্টিভ সাবস্ক্রাইবার</button>
        <button @click="subTab = 'expiring'" :class="subTab === 'expiring' ? 'border-emerald-500 text-emerald-400' : 'border-transparent text-gray-400 hover:text-white'" class="pb-3 border-b-2 font-bold font-bn text-xs sm:text-sm transition whitespace-nowrap">⚠️ শীঘ্রই শেষ হবে</button>
    </div>

    <div x-show="subTab === 'transactions'" x-cloak x-transition.opacity>
        <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
            <h3 class="font-bold text-white font-bn">সব পেমেন্টের রেকর্ড</h3>
            <button class="glass px-4 py-2 rounded-lg text-sm text-gray-300 font-bn hover:text-white transition">এক্সপোর্ট CSV</button>
        </div>
        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">TrxID</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">ইউজার</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">গেটওয়ে</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">পরিমাণ</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">স্ট্যাটাস</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923401</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">করিম ভাই</p><p class="text-xs text-gray-400">karim@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-pink-400 bg-pink-500/10 px-2 py-1 rounded text-xs font-bn">bKash</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">Success</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">রিসিপ্ট</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923399</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400">sadia@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-orange-400 bg-orange-500/10 px-2 py-1 rounded text-xs font-bn">Nagad</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">Success</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">রিসিপ্ট</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4 text-xs font-mono text-gray-400">TRX-8923380</td>
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">তানভীর হাসান</p><p class="text-xs text-gray-400">tanvir@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-purple-400 bg-purple-500/10 px-2 py-1 rounded text-xs font-bn">Rocket</span></td>
                            <td class="px-4 sm:px-6 py-4 font-bold text-white">৳৪৯</td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-red-400 bg-red-500/10 px-2 py-1 rounded text-xs font-bn">Failed</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="text-gray-400 hover:text-white text-xs font-bn">রিট্রাই</button></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div x-show="subTab === 'active'" x-cloak x-transition.opacity>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">মাসিক পেইড</p>
                <p class="text-2xl font-bold text-emerald-400">৬৪০</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">বাৎসরিক পেইড</p>
                <p class="text-2xl font-bold text-teal-400">২১০</p>
            </div>
            <div class="glass rounded-xl p-4 text-center">
                <p class="text-xs text-gray-400 font-bn mb-1">ফ্রি ট্রায়াল</p>
                <p class="text-2xl font-bold text-amber-400">১,২০০</p>
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[700px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">ইউজার</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">প্ল্যান</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">এক্সপায়ার ডেট</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">করিম ভাই</p><p class="text-xs text-gray-400">karim@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">মাসিক</span></td>
                            <td class="px-4 sm:px-6 py-4 text-gray-300 text-xs font-bn">১২ ডিসেম্বর, ২০২৫</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bn px-2">+১ মাস এক্সটেন্ড</button>
                                <button class="text-red-400 hover:text-red-300 text-xs font-bn px-2">ক্যান্সেল</button>
                            </td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">সাদিয়া আক্তার</p><p class="text-xs text-gray-400">sadia@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-teal-400 bg-teal-500/10 px-2 py-1 rounded text-xs font-bn">বাৎসরিক</span></td>
                            <td class="px-4 sm:px-6 py-4 text-gray-300 text-xs font-bn">১ নভেম্বর, ২০২৬</td>
                            <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                                <button class="text-emerald-400 hover:text-emerald-300 text-xs font-bn px-2">+১ বছর এক্সটেন্ড</button>
                                <button class="text-red-400 hover:text-red-300 text-xs font-bn px-2">ক্যান্সেল</button>
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
                <p class="font-bold text-amber-400 font-bn">সতর্কতা</p>
                <p class="text-sm text-amber-200/70 font-bn">নিচের ইউজারদের সাবস্ক্রিপশন আগামী ৭ দিনের মধ্যে শেষ হয়ে যাবে। চাইলে ম্যানুয়ালি রিমাইন্ডার পাঠাতে পারেন।</p>
            </div>
        </div>

        <div class="glass rounded-xl overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full min-w-[600px] text-sm text-left">
                    <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                        <tr>
                            <th class="px-4 sm:px-6 py-4 font-bn">ইউজার</th>
                            <th class="px-4 sm:px-6 py-4 font-bn">শেষ হচ্ছে</th>
                            <th class="px-4 sm:px-6 py-4 font-bn text-right">অ্যাকশন</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-white/5">
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">রাফি আহমেদ</p><p class="text-xs text-gray-400">rafi@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-red-400 font-bold font-bn">আগামীকাল</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="bg-amber-500/20 text-amber-400 px-3 py-1.5 rounded text-xs font-bn hover:bg-amber-500/30 transition whitespace-nowrap">রিমাইন্ডার পাঠান</button></td>
                        </tr>
                        <tr class="hover:bg-white/5 transition">
                            <td class="px-4 sm:px-6 py-4"><p class="font-bold text-white font-bn">ফারহানা ইসলাম</p><p class="text-xs text-gray-400">farhana@example.com</p></td>
                            <td class="px-4 sm:px-6 py-4"><span class="text-amber-400 font-bold font-bn">৩ দিন পর</span></td>
                            <td class="px-4 sm:px-6 py-4 text-right"><button class="bg-amber-500/20 text-amber-400 px-3 py-1.5 rounded text-xs font-bn hover:bg-amber-500/30 transition whitespace-nowrap">রিমাইন্ডার পাঠান</button></td>
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
        <p class="text-sm text-gray-400 font-bn">এখান থেকে দাম ও ফিচার পরিবর্তন করলে সরাসরি ওয়েবসাইটে আপডেট হয়ে যাবে।</p>
        <button @click="modalOpen = true; modalTitle = 'নতুন প্ল্যান যোগ করুন'" class="btn-emerald px-4 py-2 rounded-lg text-sm font-bold font-bn shrink-0">+ নতুন প্ল্যান</button>
    </div>

    <div class="grid md:grid-cols-2 gap-4 sm:gap-6">
        <div class="glass-strong rounded-xl p-5 sm:p-6 relative border border-emerald-500/30">
            <div class="flex justify-between items-start gap-3 mb-4">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white font-bn">মাসিক প্ল্যান</h3>
                    <p class="text-2xl sm:text-3xl font-bold text-emerald-400 font-display mt-2">৳৪৯<span class="text-sm text-gray-400 font-bn font-normal">/মাস</span></p>
                </div>
                <button @click="modalOpen = true; modalTitle = 'মাসিক প্ল্যান এডিট'" class="text-gray-400 hover:text-white bg-white/5 px-3 py-1 rounded text-xs font-bn shrink-0">এডিট</button>
            </div>
            <div class="space-y-2 mb-4">
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> সব ফিচার আনলকড</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> আনলিমিটেড এন্ট্রি</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> রিপোর্ট এক্সপোর্ট</p>
            </div>
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-white/10">
                <span class="text-xs text-gray-400 font-bn">স্ট্যাটাস:</span>
                <span class="text-xs text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded font-bn">অ্যাক্টিভ</span>
            </div>
        </div>

        <div class="glass rounded-xl p-5 sm:p-6 relative">
            <div class="flex justify-between items-start gap-3 mb-4">
                <div>
                    <h3 class="text-lg sm:text-xl font-bold text-white font-bn">বাৎসরিক প্ল্যান</h3>
                    <p class="text-2xl sm:text-3xl font-bold text-emerald-400 font-display mt-2">৳৪৯৯<span class="text-sm text-gray-400 font-bn font-normal">/বছর</span></p>
                </div>
                <button @click="modalOpen = true; modalTitle = 'বাৎসরিক প্ল্যান এডিট'" class="text-gray-400 hover:text-white bg-white/5 px-3 py-1 rounded text-xs font-bn shrink-0">এডিট</button>
            </div>
            <div class="space-y-2 mb-4">
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> সব ফিচার আনলকড</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> ২ মাস ফ্রি</p>
                <p class="text-sm text-gray-300 font-bn flex gap-2"><span class="text-emerald-500">✓</span> প্রায়োরিটি সাপোর্ট</p>
            </div>
        </div>
    </div>
</div>

<!-- ================= 5. PAGE CONTENT (CMS) ================= -->
<div x-show="page === 'admin-content'" x-cloak x-transition.opacity class="space-y-5 sm:space-y-6 max-w-4xl">
    <div class="glass rounded-xl p-5 sm:p-6">
        <h3 class="text-lg font-bold text-white font-bn mb-4 border-b border-white/10 pb-2">হোম পেজ — হিরো সেকশন</h3>
        <div class="space-y-4">
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">মেইন টাইটেল (Main Title)</label>
                <input type="text" value="টাকার হিসাব সহজ, পরিষ্কার, নিশ্চিন্ত" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
            </div>
            <div>
                <label class="block text-sm text-gray-400 mb-1 font-bn">সাব-টাইটেল (Sub Title)</label>
                <textarea rows="2" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">মেসের মিল, সংসারের খরচ, দোকানের বাকি — সব এক অ্যাপে। হিসাব রাখা এত সহজ আর কখনো হয়নি।</textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">প্রাইমারি বাটন টেক্সট</label>
                    <input type="text" value="ফ্রি অ্যাকাউন্ট খুলুন" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-1 font-bn">ট্রায়াল টেক্সট</label>
                    <input type="text" value="প্রথম মাস পুরোটাই ফ্রি — কার্ড লাগবে না" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-4 py-2 text-white focus:outline-none focus:border-emerald-500 font-bn">
                </div>
            </div>
            <div class="pt-4">
                <button class="btn-emerald px-6 py-2 rounded-lg font-bold font-bn text-sm">সেভ করুন</button>
            </div>
        </div>
    </div>
</div>

<!-- ================= 6. BLOG CMS ================= -->
<div x-show="page === 'admin-blog'" x-cloak x-transition.opacity class="space-y-4">
    <div class="flex flex-col sm:flex-row sm:justify-between sm:items-center gap-3 mb-4">
        <h3 class="font-bold text-white font-bn">সব পোস্ট</h3>
        <button @click="modalOpen = true; modalTitle = 'নতুন ব্লগ পোস্ট'" class="btn-emerald px-4 py-2 rounded-lg text-sm font-bold font-bn">+ নতুন পোস্ট</button>
    </div>

    <div class="glass rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full min-w-[640px] text-sm text-left">
                <thead class="bg-white/5 text-xs text-gray-400 uppercase">
                    <tr>
                        <th class="px-4 sm:px-6 py-4 font-bn">টাইটেল</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">ক্যাটাগরি</th>
                        <th class="px-4 sm:px-6 py-4 font-bn">স্ট্যাটাস</th>
                        <th class="px-4 sm:px-6 py-4 font-bn text-right">অ্যাকশন</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-white/5">
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 sm:px-6 py-4 font-bold text-white font-bn">মেস মিল রেট কীভাবে বের করবেন</td>
                        <td class="px-4 sm:px-6 py-4 text-gray-300 font-bn">গাইড</td>
                        <td class="px-4 sm:px-6 py-4"><span class="text-emerald-400 bg-emerald-500/10 px-2 py-1 rounded text-xs font-bn">পাবলিশড</span></td>
                        <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                            <button @click="modalOpen = true; modalTitle = 'পোস্ট এডিট'" class="text-gray-400 hover:text-white font-bn text-xs px-2">এডিট</button>
                            <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">ডিলিট</button>
                        </td>
                    </tr>
                    <tr class="hover:bg-white/5 transition">
                        <td class="px-4 sm:px-6 py-4 font-bold text-white font-bn">ছোট ব্যবসার ১০টি টিপস</td>
                        <td class="px-4 sm:px-6 py-4 text-gray-300 font-bn">টিপস</td>
                        <td class="px-4 sm:px-6 py-4"><span class="text-amber-400 bg-amber-500/10 px-2 py-1 rounded text-xs font-bn">ড্রাফট</span></td>
                        <td class="px-4 sm:px-6 py-4 text-right whitespace-nowrap">
                            <button @click="modalOpen = true; modalTitle = 'পোস্ট এডিট'" class="text-gray-400 hover:text-white font-bn text-xs px-2">এডিট</button>
                            <button class="text-red-400 hover:text-red-300 font-bn text-xs px-2">ডিলিট</button>
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
            <p class="text-sm text-gray-300 font-bn mb-4">"মেসের হিসাব রাখা এত সহজ হবে ভাবিনি। মিল রেট নিজে থেকেই বেরিয়ে আসে।"</p>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-white/5 pt-3">
                <p class="text-xs text-white font-bn">- রাফি আহমেদ</p>
                <div class="space-x-2">
                    <button class="text-xs text-emerald-400 border border-emerald-400/30 px-2 py-1 rounded hover:bg-emerald-400/10 font-bn">Approve</button>
                    <button class="text-xs text-red-400 border border-red-400/30 px-2 py-1 rounded hover:bg-red-400/10 font-bn">Reject</button>
                </div>
            </div>
        </div>
        <div class="glass rounded-xl p-5 relative opacity-60">
            <div class="flex text-amber-400 mb-2">★★★★☆</div>
            <p class="text-sm text-gray-300 font-bn mb-4">"ইনভয়েস অপশনটা খুব কাজে লাগছে। আরেকটু ফিচার চাই।"</p>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-t border-white/5 pt-3">
                <p class="text-xs text-white font-bn">- করিম ভাই</p>
                <span class="text-xs text-gray-500 font-bn">Pending</span>
            </div>
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
                <label class="text-sm text-gray-400 font-bn">টাইটেল / নাম</label>
                <input type="text" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
            </div>
            <div>
                <label class="text-sm text-gray-400 font-bn">বিবরণ / কনটেন্ট</label>
                <textarea rows="3" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500"></textarea>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-sm text-gray-400 font-bn">স্ট্যাটাস</label>
                    <select class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                        <option>পাবলিশড</option>
                        <option>ড্রাফট</option>
                    </select>
                </div>
                <div>
                    <label class="text-sm text-gray-400 font-bn">ক্যাটাগরি</label>
                    <input type="text" class="w-full bg-[#06130D] border border-white/10 rounded-lg px-3 py-2 text-white mt-1 focus:outline-none focus:border-emerald-500">
                </div>
            </div>
        </div>

        <div class="flex gap-3 mt-6 border-t border-white/10 pt-4">
            <button @click="modalOpen = false" class="flex-1 py-2 rounded-lg bg-white/5 hover:bg-white/10 font-bold font-bn transition text-sm">বাতিল</button>
            <button @click="modalOpen = false" class="flex-1 py-2 rounded-lg btn-emerald font-bold font-bn transition text-sm">সেভ করুন</button>
        </div>
    </div>
</div>
@endsection
