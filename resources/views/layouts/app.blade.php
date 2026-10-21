<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: true,
    mobileMenu: false,
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
}" :class="dark ? 'dark' : ''">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>@yield('title', 'HisabKhata — টাকার হিসাব, সহজ ও পরিষ্কার')</title>
<meta name="description" content="মেস মিল রেট, সংসারের খরচ, দোকানের বাকি — সব হিসাব এক অ্যাপে। প্রথম মাস সম্পূর্ণ ফ্রি।">
<script>window.__flashToast = @js(session('toast'));</script>
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  body { font-family: 'Inter', 'Hind Siliguri', sans-serif; }
  .font-display { font-family: 'Inter', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
  .font-bn { font-family: 'Hind Siliguri', sans-serif; }
  .glass { background: rgba(255, 255, 255, 0.04); backdrop-filter: blur(20px) saturate(180%); -webkit-backdrop-filter: blur(20px) saturate(180%); border: 1px solid rgba(16, 185, 129, 0.15); box-shadow: 0 8px 32px rgba(6, 19, 13, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.08); }
  .glass-strong { background: rgba(16, 185, 129, 0.08); backdrop-filter: blur(28px) saturate(200%); -webkit-backdrop-filter: blur(28px) saturate(200%); border: 1px solid rgba(16, 185, 129, 0.25); box-shadow: 0 12px 40px rgba(6, 19, 13, 0.7), inset 0 1px 0 rgba(255, 255, 255, 0.12); }
  .glass-nav { background: rgba(6, 19, 13, 0.8); backdrop-filter: blur(24px) saturate(180%); -webkit-backdrop-filter: blur(24px) saturate(180%); border-bottom: 1px solid rgba(16, 185, 129, 0.15); }
  .gradient-green { background: linear-gradient(135deg, #D1FAE5 0%, #34D399 40%, #10B981 70%, #047857 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
  .gradient-shimmer { background: linear-gradient(90deg, #34D399, #D1FAE5, #10B981, #34D399); background-size: 300% 100%; -webkit-background-clip: text; -webkit-text-fill-color: transparent; animation: shimmer 6s ease infinite; }
  @keyframes shimmer { 0%, 100% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } }
  .blob { position: absolute; filter: blur(130px); border-radius: 50%; pointer-events: none; animation: blob-float 25s ease-in-out infinite; }
  @keyframes blob-float { 0%, 100% { transform: translate(0, 0) scale(1); } 33% { transform: translate(40px, -30px) scale(1.1); } 66% { transform: translate(-30px, 30px) scale(0.95); } }
  @keyframes float { 0%,100%{transform:translateY(0)} 50%{transform:translateY(-15px)} }
  .float { animation: float 6s ease-in-out infinite; }
  .card-3d { transition: all .5s cubic-bezier(.16,1,.3,1); }
  .card-3d:hover { transform: translateY(-8px) scale(1.02); background: rgba(16, 185, 129, 0.12); border-color: rgba(52, 211, 153, 0.4); box-shadow: 0 25px 60px -12px rgba(16, 185, 129, 0.3), inset 0 1px 0 rgba(255, 255, 255, 0.15); }
  .glow-green { position: relative; }
  .glow-green::before { content: ''; position: absolute; inset: -1px; border-radius: inherit; padding: 1px; background: linear-gradient(135deg, rgba(52,211,153,.8), rgba(16,185,129,.6), rgba(245,158,11,.7)); -webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0); -webkit-mask-composite: xor; mask-composite: exclude; pointer-events: none; }
  .btn-green { background: linear-gradient(135deg, #34D399 0%, #10B981 50%, #047857 100%); color: #06130D; box-shadow: 0 0 30px rgba(16, 185, 129, 0.4), 0 8px 32px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.5); transition: all 0.3s; }
  .btn-green:hover { box-shadow: 0 0 50px rgba(16, 185, 129, 0.7), 0 12px 40px rgba(0, 0, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.6); transform: translateY(-2px); }
  .btn-shine { position: relative; overflow: hidden; }
  .btn-shine::before { content: ''; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(90deg, transparent, rgba(255,255,255,.5), transparent); transition: left .7s; }
  .btn-shine:hover::before { left: 100%; }
  .cursor { display: inline-block; width: 3px; height: 1em; background: #34D399; animation: blink 1s infinite; vertical-align: middle; }
  @keyframes blink { 50% { opacity: 0; } }
  @keyframes marquee { from { transform: translateX(0); } to { transform: translateX(-50%); } }
  .marquee { animation: marquee 35s linear infinite; }
  .tab-btn.active { background: linear-gradient(135deg, #34D399 0%, #10B981 50%, #047857 100%); color: #06130D; box-shadow: 0 0 25px rgba(16, 185, 129, 0.5); }
  html:not(.dark) body { background: linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 50%, #F7FAFC 100%); color: #06130D; }
  html:not(.dark) .glass { background: rgba(255, 255, 255, 0.6); border-color: rgba(16, 185, 129, 0.2); box-shadow: 0 8px 32px rgba(16, 185, 129, 0.1), inset 0 1px 0 rgba(255,255,255,0.9); }
  html:not(.dark) .glass-strong { background: rgba(255, 255, 255, 0.8); border-color: rgba(16, 185, 129, 0.3); }
  html:not(.dark) .glass-nav { background: rgba(240, 253, 244, 0.8); border-bottom-color: rgba(16, 185, 129, 0.15); }
  html:not(.dark) .text-white { color: #06130D; }
  html:not(.dark) .text-gray-300 { color: #475569; }
  html:not(.dark) .text-gray-400 { color: #64748B; }
  html:not(.dark) .gradient-green { background: linear-gradient(135deg, #06130D 0%, #10B981 50%, #047857 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text; }
  html:not(.dark) .blob { opacity: 0.2; }
  html { scroll-behavior: smooth; }
  body { background: #06130D; min-height: 100vh; position: relative; }
  .noise-overlay { position: fixed; inset: 0; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E"); opacity: 0.04; pointer-events: none; z-index: 1; mix-blend-mode: overlay; }
  .grid-bg { position: fixed; inset: 0; background-image: linear-gradient(rgba(16, 185, 129, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(16, 185, 129, 0.03) 1px, transparent 1px); background-size: 60px 60px; pointer-events: none; z-index: 0; mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%); }
  [x-cloak] { display: none !important; }
</style>
</head>

<body class="text-white overflow-x-hidden">

<div class="noise-overlay"></div>
<div class="grid-bg"></div>
<div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
    <div class="blob w-[700px] h-[700px] bg-emerald-600 opacity-20 -top-40 -left-40"></div>
    <div class="blob w-[500px] h-[500px] bg-teal-500 opacity-15 top-1/3 -right-40" style="animation-delay:-5s"></div>
    <div class="blob w-[600px] h-[600px] bg-orange-600 opacity-10 bottom-0 left-1/3" style="animation-delay:-10s"></div>
</div>

<!-- TOAST -->
    <div x-show="showToast" x-transition x-init="showToast && setTimeout(() => showToast = false, 2500)" class="fixed top-20 right-6 z-[100] glass-strong text-emerald-300 px-5 py-3 rounded-xl shadow-2xl text-sm font-bn" x-text="toastMsg" x-cloak></div>

    <!-- LANGUAGE SELECTOR -->
    @php $currentLocale = app()->getLocale(); @endphp
    <div class="fixed bottom-6 right-6 z-50 flex items-center gap-2 glass rounded-full px-3 py-2 shadow-2xl">
        <a href="{{ route('lang.switch', 'bn') }}" class="px-3 py-1.5 text-xs font-bn rounded-full transition {{ $currentLocale === 'bn' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}">বাংলা</a>
        <div class="w-px h-4 bg-white/20"></div>
        <a href="{{ route('lang.switch', 'en') }}" class="px-3 py-1.5 text-xs font-bn rounded-full transition {{ $currentLocale === 'en' ? 'bg-emerald-500 text-white' : 'text-gray-300 hover:bg-white/10' }}">English</a>
    </div>

@yield('content')

@stack('scripts')
</body>
</html>
