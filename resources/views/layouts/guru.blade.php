<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Portal Guru') — Portal Sekolah</title>
    
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    {{-- Dark Mode Init Script (Mencegah Flash FOUC) --}}
    <script>
        if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-50 dark:bg-slate-900 text-slate-800 dark:text-slate-100 antialiased min-h-screen transition-colors duration-200 selection:bg-indigo-500 selection:text-white">

    {{-- Backdrop untuk mobile saat sidebar terbuka --}}
    <div id="sidebar-backdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 z-40 bg-slate-900/50 backdrop-blur-xs hidden md:hidden"></div>

    {{-- ========================================================================= --}}
    {{-- SIDEBAR KIRI (Fixed, tidak menabrak konten berkat pl-64 pada main wrapper)  --}}
    {{-- ========================================================================= --}}
    <aside id="main-sidebar" class="fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-slate-800 border-r border-slate-200 dark:border-slate-700/80 flex flex-col justify-between transition-transform duration-200 -translate-x-full md:translate-x-0">
        
        <div>
            {{-- Header Sidebar (Logo & Nama Aplikasi) --}}
            <div class="h-16 px-5 border-b border-slate-200 dark:border-slate-700/80 flex items-center justify-between">
                <a href="{{ route('guru.dashboard') }}" class="flex items-center gap-3 group">
                    @if (file_exists(public_path('images/logosekolah.png')))
                        <img src="{{ asset('images/logosekolah.png') }}" alt="Logo Sekolah" class="w-9 h-9 object-contain rounded-lg">
                    @else
                        <div class="w-9 h-9 bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg flex items-center justify-center font-bold text-lg shadow-sm">
                            PS
                        </div>
                    @endif
                    <div>
                        <span class="text-sm font-bold text-slate-900 dark:text-white leading-none block">Portal Sekolah</span>
                        <span class="text-[11px] font-medium text-slate-500 dark:text-slate-400">Area Guru</span>
                    </div>
                </a>
                
                {{-- Tombol Tutup Sidebar di Layar Mobile --}}
                <button type="button" onclick="toggleMobileSidebar()" class="md:hidden text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-white p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Label Kategori Menu --}}
            <div class="px-5 pt-5 pb-2 text-[11px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">
                Menu Utama
            </div>

            {{-- Navigasi Menu Guru --}}
            <nav class="px-3 space-y-1">
                @php
                    $currentRoute = request()->route()->getName();
                @endphp

                {{-- 1. Dashboard --}}
                <a href="{{ route('guru.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ str_starts_with($currentRoute, 'guru.dashboard') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ str_starts_with($currentRoute, 'guru.dashboard') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span>Dashboard</span>
                </a>

                {{-- 2. Absensi Siswa (Per Mapel) --}}
                <a href="{{ route('guru.attendance.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ str_starts_with($currentRoute, 'guru.attendance.') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ str_starts_with($currentRoute, 'guru.attendance.') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                    </svg>
                    <span>Absensi Siswa</span>
                </a>

                {{-- 3. Scanner Piket (Masuk / Pulang) --}}
                <a href="{{ route('guru.school-attendance-scanner.index') }}"
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ str_starts_with($currentRoute, 'guru.school-attendance-scanner.') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ str_starts_with($currentRoute, 'guru.school-attendance-scanner.') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>Scanner Piket</span>
                </a>

                {{-- 4. Profil Guru --}}
                <a href="{{ route('guru.profile.show') }}"
                   class="flex items-center gap-3 px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-150 {{ str_starts_with($currentRoute, 'guru.profile.') ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-600 dark:text-indigo-400 font-semibold shadow-xs' : 'text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700/50 hover:text-slate-900 dark:hover:text-white' }}">
                    <svg class="w-5 h-5 shrink-0 {{ str_starts_with($currentRoute, 'guru.profile.') ? 'text-indigo-600 dark:text-indigo-400' : 'text-slate-400 dark:text-slate-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span>Profil Saya</span>
                </a>
            </nav>
        </div>

        {{-- Footer Sidebar: Tombol Logout di Bawah --}}
        <div class="p-4 border-t border-slate-200 dark:border-slate-700/80">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit"
                        class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl border border-rose-200 dark:border-rose-900/50 bg-rose-50/50 dark:bg-rose-950/30 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/50 text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    <span>Keluar</span>
                </button>
            </form>
        </div>
    </aside>

    {{-- ========================================================================= --}}
    {{-- MAIN CONTENT WRAPPER (Dengan pl-64 di desktop agar TIDAK TABRAKAN)        --}}
    {{-- ========================================================================= --}}
    <div class="md:pl-64 flex flex-col min-h-screen">
        
        {{-- NAVBAR ATAS (Sticky Header dengan Zen CBT Style) --}}
        <header class="sticky top-0 z-30 h-16 bg-white/95 dark:bg-slate-800/95 backdrop-blur border-b border-slate-200 dark:border-slate-700/80 px-4 sm:px-6 flex items-center justify-between transition-colors duration-200 shadow-2xs">
            
            {{-- Sisi Kiri: Hamburger Mobile + Breadcrumb/Title --}}
            <div class="flex items-center gap-3">
                <button type="button" onclick="toggleMobileSidebar()" class="md:hidden text-slate-600 dark:text-slate-300 hover:text-slate-900 dark:hover:text-white p-1.5 rounded-lg border border-slate-200 dark:border-slate-700">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                </button>

                <div class="hidden sm:flex items-center gap-2.5">
                    <div>
                        <h1 class="text-sm font-bold text-slate-900 dark:text-white leading-tight">@yield('title', 'Dashboard')</h1>
                        <p class="text-[11px] text-slate-500 dark:text-slate-400">@yield('subtitle', 'Portal Sekolah')</p>
                    </div>
                </div>
            </div>

            {{-- Sisi Kanan: Dark Mode Toggle + Info Profil + Tombol Logout Zen CBT --}}
            <div class="flex items-center gap-3">
                
                {{-- 1. Dark / Light Mode Toggle Button --}}
                <button type="button" onclick="toggleDarkMode()"
                        class="p-2 rounded-xl text-slate-500 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-700/60 hover:text-slate-900 dark:hover:text-white transition-colors focus:outline-hidden"
                        title="Ganti Mode Gelap / Terang">
                    {{-- Icon Sun (Ditampilkan saat Dark Mode aktif) --}}
                    <svg id="theme-icon-sun" class="w-5 h-5 hidden dark:block text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    {{-- Icon Moon (Ditampilkan saat Light Mode aktif) --}}
                    <svg id="theme-icon-moon" class="w-5 h-5 block dark:hidden text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <div class="h-6 w-px bg-slate-200 dark:bg-slate-700"></div>

                {{-- 2. Info Profil (Klik langsung menuju halaman profil) --}}
                @php
                    $user = auth()->user();
                    $teacher = $user?->teacher;
                    $displayName = $teacher?->full_name ?? $user?->username ?? 'Guru';
                @endphp
                <a href="{{ route('guru.profile.show') }}"
                   class="flex items-center gap-2.5 px-2.5 py-1.5 rounded-xl hover:bg-slate-100 dark:hover:bg-slate-700/50 transition-colors group"
                   title="Buka Profil Saya">
                    <div class="w-8 h-8 rounded-full bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300 font-bold text-xs flex items-center justify-center border border-indigo-200 dark:border-indigo-800">
                        {{ strtoupper(substr($user?->username ?? 'G', 0, 1)) }}
                    </div>
                    <div class="text-left hidden sm:block">
                        <div class="text-xs font-semibold text-slate-900 dark:text-white group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors leading-tight">
                            {{ $displayName }}
                        </div>
                        <div class="text-[10px] font-medium text-slate-400 dark:text-slate-400 capitalize">
                            {{ $user?->role ?? 'Guru' }}
                        </div>
                    </div>
                </a>

                {{-- 3. Tombol Logout Zen CBT Style --}}
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-rose-200 dark:border-rose-900/60 bg-rose-50 dark:bg-rose-950/40 text-rose-600 dark:text-rose-400 hover:bg-rose-100 dark:hover:bg-rose-900/60 text-xs font-semibold transition-colors shadow-2xs">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span>Logout</span>
                    </button>
                </form>

            </div>
        </header>

        {{-- AREA KONTEN UTAMA --}}
        <main class="flex-1 p-4 sm:p-6 lg:p-8 max-w-7xl w-full mx-auto">
            
            {{-- Flash Messages --}}
            @if (session('status') || session('success') || session('scan_status'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm flex items-start gap-3 shadow-2xs" role="status">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('status') ?? session('success') ?? session('scan_status') }}</span>
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm flex items-start gap-3 shadow-2xs">
                    <svg class="w-5 h-5 text-rose-600 dark:text-rose-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-800 dark:text-rose-300 text-sm shadow-2xs" role="alert">
                    <p class="font-semibold">{{ request()->routeIs('guru.school-attendance-scanner.*') ? 'Scan tidak tersimpan:' : 'Periksa kembali isian formulir:' }}</p>
                    <ul class="mt-1 list-disc list-inside space-y-0.5 text-xs">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Dynamic Content Injected Here --}}
            @yield('content')
        </main>
    </div>

    {{-- Script untuk Dark Mode Toggle & Mobile Drawer --}}
    <script>
        function toggleDarkMode() {
            if (document.documentElement.classList.contains('dark')) {
                document.documentElement.classList.remove('dark');
                localStorage.theme = 'light';
            } else {
                document.documentElement.classList.add('dark');
                localStorage.theme = 'dark';
            }
        }

        function toggleMobileSidebar() {
            const sidebar = document.getElementById('main-sidebar');
            const backdrop = document.getElementById('sidebar-backdrop');
            
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }
    </script>
    
    @stack('scripts')
</body>
</html>
