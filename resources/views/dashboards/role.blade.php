<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard {{ $area }} — Portal Sekolah</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight">Portal Sekolah</h1>
                    <p class="text-xs text-slate-500">Area: {{ $area }} (RBAC Foundation — Prompt 25)</p>
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold text-slate-600">{{ $user->username }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg px-3 py-1.5 bg-white">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-10 w-full flex-1">
        <div class="bg-white rounded-2xl p-8 border border-slate-200/80 shadow-xs">
            <p class="text-xs font-bold uppercase tracking-wider text-emerald-600">Akses Server-Side Ditegakkan</p>
            <h2 class="mt-2 text-2xl font-bold text-slate-900">
                Selamat datang di area {{ $area }}, {{ $user->teacher->full_name ?? $user->username }}!
            </h2>
            <p class="mt-2 text-sm text-slate-500">
                Halaman ini hanya dapat dibuka oleh role <span class="font-mono font-semibold">{{ $role }}</span>.
                Role lain menerima HTTP 403 Forbidden, termasuk pada akses URL langsung.
            </p>

            <dl class="mt-6 grid grid-cols-1 sm:grid-cols-3 gap-4 text-sm">
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">Route area</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900">{{ $areaRoute }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">Role sesi (DB)</dt>
                    <dd class="mt-1 font-mono font-semibold text-slate-900">{{ $user->role }}</dd>
                </div>
                <div class="p-4 rounded-xl bg-slate-50 border border-slate-200">
                    <dt class="text-xs font-semibold text-slate-500">Status akun</dt>
                    <dd class="mt-1 font-semibold text-slate-900">{{ $user->is_active ? 'AKTIF' : 'NONAKTIF' }}</dd>
                </div>
            </dl>

            <p class="mt-6 text-xs text-slate-400">
                Catatan: dashboard ini adalah fondasi pemisahan area. Modul lengkap (master data,
                repositori dokumen, monitoring, scanner absensi, jadwal piket, laporan) menyusul
                pada tahap implementasi berikutnya.
            </p>
        </div>
    </main>
</body>
</html>
