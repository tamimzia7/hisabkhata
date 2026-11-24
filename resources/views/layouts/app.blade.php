<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" x-data="{
    dark: window.hisabTheme ? window.hisabTheme.isDark() : true,
    toggleDark() {
        window.hisabTheme.toggle();
        this.dark = window.hisabTheme.isDark();
    },
    init() {
        window.addEventListener('hisab-theme-changed', (event) => {
            this.dark = event.detail.dark;
        });
    },
    toastMsg: window.__flashToast || '',
    showToast: Boolean(window.__flashToast),
}">

<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
<link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">
<link rel="apple-touch-icon" href="{{ asset('apple-touch-icon.png') }}">
<title>@yield('title', __('site.meta.default_title'))</title>
<meta name="description" content="{{ __('site.meta.description') }}">
@include('partials.theme-init')
<script>window.__flashToast = @js(session('toast'));</script>
<script src="https://cdn.tailwindcss.com"></script>
<script>
    if (window.tailwind) {
        tailwind.config = Object.assign({}, tailwind.config, { darkMode: 'class' });
    }
</script>
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js" defer></script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700&family=Plus+Jakarta+Sans:wght@200;300;400;500;600;700;800&display=swap" rel="stylesheet">
<style>
  html { -webkit-text-size-adjust: 100%; font-size: 18px; }
  .text-\[8px\] { font-size: 10px !important; }
  .text-\[9px\] { font-size: 11px !important; }
  .text-\[10px\] { font-size: 12px !important; }
  .text-\[11px\] { font-size: 13px !important; }
  .text-\[13px\] { font-size: 15px !important; }
  body { font-family: 'Plus Jakarta Sans', 'Hind Siliguri', sans-serif; }
  .font-display { font-family: 'Plus Jakarta Sans', sans-serif; font-weight: 800; letter-spacing: -0.03em; }
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
  .bg-emerald-500.text-white { color: #04120B; }
  html:not(.dark) {
    --ui-bg: #F8FAFC;
    --ui-surface: #FFFFFF;
    --ui-muted: #F1F5F9;
    --ui-text: #0F172A;
    --ui-secondary: #64748B;
    --ui-navy: #1E3A5F;
    --ui-blue: #2563EB;
    --ui-border: #E2E8F0;
    --ui-success: #0D9488;
    --ui-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
    --ui-shadow-md: 0 8px 24px rgba(15, 23, 42, 0.06);
    color-scheme: light !important;
  }
  html:not(.dark) body { background: var(--ui-bg) !important; color: var(--ui-text) !important; }
  html:not(.dark) body,
  html:not(.dark) body * { font-weight: 400 !important; }
  html:not(.dark) body h1,
  html:not(.dark) body h2,
  html:not(.dark) body h3,
  html:not(.dark) body h4,
  html:not(.dark) body h5,
  html:not(.dark) body h6,
  html:not(.dark) body th,
  html:not(.dark) body strong,
  html:not(.dark) body b,
  html:not(.dark) body .font-display,
  html:not(.dark) body .font-bold,
  html:not(.dark) body .font-semibold { font-weight: 600 !important; }
  html:not(.dark) body .font-medium { font-weight: 500 !important; }
  html:not(.dark) body .font-extrabold,
  html:not(.dark) body .font-black { font-weight: 700 !important; }
  html:not(.dark) ::selection { background: #DBEAFE; color: var(--ui-navy); }
  html:not(.dark) .noise-overlay,
  html:not(.dark) .grid-bg,
  html:not(.dark) .blob { display: none !important; }
  html:not(.dark) .glass,
  html:not(.dark) .glass-strong,
  html:not(.dark) .glass-nav,
  html:not(.dark) .student-sidebar,
  html:not(.dark) .student-topbar,
  html:not(.dark) .student-popover,
  html:not(.dark) .admin-bg,
  html:not(.dark) .admin-surface,
  html:not(.dark) [role="dialog"],
  html:not(.dark) [role="menu"] { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important; box-shadow: var(--ui-shadow) !important; }
  html:not(.dark) .card-hover,
  html:not(.dark) .card-3d { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; box-shadow: var(--ui-shadow) !important; }
  html:not(.dark) .card-hover:hover,
  html:not(.dark) .card-3d:hover { transform: none !important; border-color: var(--ui-border) !important; box-shadow: var(--ui-shadow-md) !important; }
  html:not(.dark) .text-white,
  html:not(.dark) .text-gray-200,
  html:not(.dark) .text-gray-300 { color: var(--ui-text) !important; }
  html:not(.dark) .text-gray-400,
  html:not(.dark) .text-gray-500,
  html:not(.dark) .text-gray-600 { color: var(--ui-secondary) !important; }
  html:not(.dark) .text-emerald-200,
  html:not(.dark) .text-emerald-300,
  html:not(.dark) .text-emerald-400,
  html:not(.dark) .text-emerald-500 { color: var(--ui-navy) !important; }
  html:not(.dark) .text-teal-300,
  html:not(.dark) .text-teal-400,
  html:not(.dark) .text-teal-500 { color: var(--ui-success) !important; }
  html:not(.dark) .text-sky-300,
  html:not(.dark) .text-sky-400,
  html:not(.dark) .text-blue-400,
  html:not(.dark) .text-blue-500 { color: var(--ui-blue) !important; }
  html:not(.dark) .text-orange-300,
  html:not(.dark) .text-orange-400,
  html:not(.dark) .text-amber-300,
  html:not(.dark) .text-amber-400 { color: #B45309 !important; }
  html:not(.dark) .text-red-300,
  html:not(.dark) .text-red-400 { color: #B91C1C !important; }
  html:not(.dark) .text-lime-300,
  html:not(.dark) .text-lime-400 { color: var(--ui-success) !important; }
  html:not(.dark) .text-violet-300 { color: #6D28D9 !important; }
  html:not(.dark) .hover\:text-white:hover,
  html:not(.dark) .hover\:text-emerald-300:hover { color: var(--ui-navy) !important; }
  html:not(.dark) aside a:hover,
  html:not(.dark) nav a:hover { color: var(--ui-navy) !important; }
  html:not(.dark) .btn-green,
  html:not(.dark) .btn-emerald,
  html:not(.dark) .tab-btn.active,
  html:not(.dark) .bg-emerald-500,
  html:not(.dark) .bg-emerald-600,
  html:not(.dark) .bg-emerald-700 { background-color: var(--ui-navy) !important; background-image: none !important; border-color: var(--ui-navy) !important; box-shadow: none !important; color: #FFFFFF !important; }
  html:not(.dark) .btn-green:hover,
  html:not(.dark) .btn-emerald:hover,
  html:not(.dark) .hover\:bg-emerald-400:hover,
  html:not(.dark) .hover\:bg-emerald-500:hover { background-color: var(--ui-blue) !important; box-shadow: var(--ui-shadow) !important; color: #FFFFFF !important; transform: none !important; }
  html:not(.dark) .bg-emerald-400,
  html:not(.dark) .bg-teal-500,
  html:not(.dark) .bg-teal-600 { background-color: var(--ui-success) !important; box-shadow: none !important; color: #FFFFFF !important; }
  html:not(.dark) .bg-emerald-400\/10,
  html:not(.dark) .bg-emerald-500\/10,
  html:not(.dark) .bg-emerald-500\/15,
  html:not(.dark) .bg-emerald-500\/20,
  html:not(.dark) .bg-teal-500\/10 { background-color: var(--ui-muted) !important; }
  html:not(.dark) .btn-ghost,
  html:not(.dark) .btn-secondary { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; color: var(--ui-navy) !important; }
  html:not(.dark) .btn-ghost:hover,
  html:not(.dark) .btn-secondary:hover { background-color: var(--ui-muted) !important; }
  html:not(.dark) input,
  html:not(.dark) select,
  html:not(.dark) textarea,
  html:not(.dark) .field { background-color: var(--ui-surface) !important; border-color: var(--ui-border) !important; box-shadow: none !important; color: var(--ui-text) !important; }
  html:not(.dark) input::placeholder,
  html:not(.dark) textarea::placeholder,
  html:not(.dark) .field::placeholder { color: var(--ui-secondary) !important; }
  html:not(.dark) input:focus,
  html:not(.dark) select:focus,
  html:not(.dark) textarea:focus,
  html:not(.dark) .field:focus { border-color: var(--ui-blue) !important; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important; outline: none !important; }
  html:not(.dark) option { background-color: var(--ui-surface) !important; color: var(--ui-text) !important; }
  html:not(.dark) input[type="checkbox"],
  html:not(.dark) input[type="radio"],
  html:not(.dark) input[type="range"],
  html:not(.dark) progress { accent-color: var(--ui-blue) !important; }
  html:not(.dark) .\[color-scheme\:dark\] { color-scheme: light !important; }
  html:not(.dark) .focus\:border-emerald-500:focus,
  html:not(.dark) .focus\:border-emerald-500\/60:focus { border-color: var(--ui-blue) !important; }
  html:not(.dark) .focus\:ring-emerald-400\/50:focus,
  html:not(.dark) .focus\:ring-emerald-500\/40:focus,
  html:not(.dark) .focus\:ring-emerald-500\/60:focus,
  html:not(.dark) .ring-emerald-400\/40 { --tw-ring-color: rgba(37, 99, 235, 0.25) !important; }
  html:not(.dark) table { background-color: var(--ui-surface) !important; }
  html:not(.dark) thead th,
  html:not(.dark) .data-table th { background-color: var(--ui-muted) !important; border-bottom-color: var(--ui-border) !important; color: var(--ui-secondary) !important; }
  html:not(.dark) tbody td,
  html:not(.dark) .data-table td { border-bottom-color: var(--ui-border) !important; color: var(--ui-text) !important; }
  html:not(.dark) tbody tr:hover,
  html:not(.dark) .data-table tbody tr:hover { background-color: var(--ui-muted) !important; }
  html:not(.dark) .bg-black\/20,
  html:not(.dark) .bg-black\/30,
  html:not(.dark) .bg-black\/40,
  html:not(.dark) .bg-emerald-500\/5,
  html:not(.dark) .bg-white\/5,
  html:not(.dark) .bg-white\/10,
  html:not(.dark) .bg-\[\#050B14\],
  html:not(.dark) .bg-\[\#06130D\],
  html:not(.dark) .bg-\[\#091C14\] { background-color: var(--ui-muted) !important; }
  html:not(.dark) .bg-black\/60 { background-color: rgba(15, 23, 42, 0.24) !important; }
  html:not(.dark) .bg-red-500\/10 { background-color: #FEF2F2 !important; }
  html:not(.dark) .border-emerald-400\/20,
  html:not(.dark) .border-emerald-400\/25,
  html:not(.dark) .border-emerald-400\/30,
  html:not(.dark) .border-emerald-400\/40,
  html:not(.dark) .border-emerald-400\/60,
  html:not(.dark) .border-emerald-500\/10,
  html:not(.dark) .border-emerald-500\/15,
  html:not(.dark) .border-emerald-500\/20,
  html:not(.dark) .border-emerald-500\/25,
  html:not(.dark) .border-emerald-500\/30,
  html:not(.dark) .border-emerald-500\/40,
  html:not(.dark) .hover\:border-emerald-400\/20:hover,
  html:not(.dark) .hover\:border-emerald-400\/25:hover,
  html:not(.dark) .hover\:border-emerald-400\/30:hover,
  html:not(.dark) .hover\:border-emerald-400\/40:hover,
  html:not(.dark) .border-white\/5,
  html:not(.dark) .border-white\/10,
  html:not(.dark) .border-white\/20,
  html:not(.dark) .border-\[\#06130D\] { border-color: var(--ui-border) !important; }
  html:not(.dark) .divide-white\/5 > :not([hidden]) ~ :not([hidden]),
  html:not(.dark) .divide-white\/10 > :not([hidden]) ~ :not([hidden]) { border-color: var(--ui-border) !important; }
  html:not(.dark) .hover\:bg-emerald-400\/10:hover,
  html:not(.dark) .hover\:bg-emerald-500\/5:hover,
  html:not(.dark) .hover\:bg-emerald-500\/10:hover,
  html:not(.dark) .hover\:bg-emerald-500\/15:hover,
  html:not(.dark) .hover\:bg-emerald-500\/20:hover,
  html:not(.dark) .hover\:bg-white\/5:hover,
  html:not(.dark) .hover\:bg-white\/10:hover { background-color: var(--ui-muted) !important; }
  html:not(.dark) .peer:checked ~ .peer-checked\:border-emerald-400\/60 { border-color: var(--ui-blue) !important; }
  html:not(.dark) .peer:checked ~ .peer-checked\:bg-emerald-500\/15 { background-color: #EFF6FF !important; }
  html:not(.dark) .peer:checked ~ .peer-checked\:text-emerald-300 { color: var(--ui-navy) !important; }
  html:not(.dark) .nav-child.active,
  html:not(.dark) .nav-active { background-color: #EFF6FF !important; border-left-color: var(--ui-blue) !important; color: var(--ui-blue) !important; }
  html:not(.dark) [aria-current="page"] { background-color: #EFF6FF !important; border-color: #BFDBFE !important; color: var(--ui-navy) !important; }
  html:not(.dark) .gradient-green,
  html:not(.dark) .gradient-shimmer { background-image: none !important; color: var(--ui-navy) !important; -webkit-text-fill-color: currentColor !important; }
  html:not(.dark) .from-emerald-300,
  html:not(.dark) .from-emerald-400,
  html:not(.dark) .from-emerald-500 { background-image: none !important; background-color: var(--ui-navy) !important; color: #FFFFFF !important; }
  html:not(.dark) .from-emerald-500\/10,
  html:not(.dark) .from-emerald-500\/15,
  html:not(.dark) .via-emerald-500\/5,
  html:not(.dark) .via-emerald-950\/20 { background-image: none !important; background-color: var(--ui-muted) !important; }
  html:not(.dark) .glow-green::before,
  html:not(.dark) .btn-shine::before { display: none !important; }
  html:not(.dark) .shadow-emerald-500\/20,
  html:not(.dark) .shadow-emerald-500\/30,
  html:not(.dark) .shadow-emerald-950\/20,
  html:not(.dark) .shadow-emerald-950\/30 { box-shadow: var(--ui-shadow) !important; }
  html:not(.dark) .cursor { background-color: var(--ui-blue) !important; }
  html:not(.dark) .glass-strong.bg-red-500 { background-color: #DC2626 !important; border-color: #FECACA !important; color: #FFFFFF !important; }
  html:not(.dark) .kpi-label { color: var(--ui-secondary) !important; }
  html:not(.dark) ::-webkit-scrollbar-thumb,
  html:not(.dark) .student-scroll::-webkit-scrollbar-thumb,
  html:not(.dark) .admin-scroll::-webkit-scrollbar-thumb { background-color: #CBD5E1 !important; }
  html { scroll-behavior: smooth; }
  body { background: #06130D; min-height: 100vh; position: relative; }
  .noise-overlay { position: fixed; inset: 0; background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 200 200' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.85'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)' opacity='0.5'/%3E%3C/svg%3E"); opacity: 0.04; pointer-events: none; z-index: 1; mix-blend-mode: overlay; }
  .grid-bg { position: fixed; inset: 0; background-image: linear-gradient(rgba(16, 185, 129, 0.03) 1px, transparent 1px), linear-gradient(90deg, rgba(16, 185, 129, 0.03) 1px, transparent 1px); background-size: 60px 60px; pointer-events: none; z-index: 0; mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%); -webkit-mask-image: radial-gradient(ellipse at center, black 30%, transparent 80%); }
  [x-cloak] { display: none !important; }
  /* Global typography standard: thin (100) text everywhere. */
  html body,
  html body * {
    font-weight: 100 !important;
  }
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

@yield('content')

<script>
    document.addEventListener('DOMContentLoaded', function () {
        if (window.lucide) {
            window.lucide.createIcons();
        }
    });
</script>

@stack('scripts')
</body>
</html>
