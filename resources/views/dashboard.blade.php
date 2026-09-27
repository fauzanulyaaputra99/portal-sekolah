<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Sementara — Portal Sekolah</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col selection:bg-indigo-500 selection:text-white">
    <!-- Top Navigation Bar -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <!-- Brand Title -->
                <div class="flex items-center gap-3">
                    <div class="h-10 w-10 bg-gradient-to-tr from-indigo-600 to-blue-500 rounded-xl flex items-center justify-center shadow-md shadow-indigo-100">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 0l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14zm-4 6v-7.5l4-2.222" />
                        </svg>
                    </div>
                    <div>
                        <h1 class="text-base font-bold text-slate-900 leading-tight">Portal Sekolah</h1>
                        <p class="text-xs text-slate-500">Authentication & User Foundation (Prompt 24)</p>
                    </div>
                </div>

                <!-- User Profile & Logout -->
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex flex-col text-right">
                        <span class="text-sm font-semibold text-slate-800">
                            {{ $user->teacher->full_name ?? $user->username }}
                        </span>
                        <div class="flex items-center justify-end gap-1.5">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium 
                                @if($user->role === 'admin') bg-purple-100 text-purple-700
                                @elseif($user->role === 'teacher') bg-blue-100 text-blue-700
                                @elseif($user->role === 'supervisor') bg-amber-100 text-amber-700
                                @else bg-slate-100 text-slate-700 @endif">
                                {{ strtoupper($user->role) }}
                            </span>
                            <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                Online
                            </span>
                        </div>
                    </div>

                    <!-- Logout Button (POST via Form) -->
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button
                            type="submit"
                            id="logoutButton"
                            class="inline-flex items-center gap-2 px-3.5 py-2 border border-slate-200 hover:border-rose-200 rounded-xl text-xs font-semibold text-slate-700 hover:text-rose-600 hover:bg-rose-50 transition duration-150 cursor-pointer shadow-xs"
                            title="Keluar dari sistem"
                        >
                            <svg class="w-4 h-4 text-slate-500 group-hover:text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                            </svg>
                            <span>Keluar</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Status Welcome Card -->
        <div class="bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 rounded-2xl p-6 sm:p-8 text-white shadow-lg shadow-indigo-100 mb-8">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-white/20 backdrop-blur-xs text-xs font-medium mb-3">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Sesi Terautentikasi Aktif
                    </span>
                    <h2 class="text-2xl sm:text-3xl font-bold tracking-tight">
                        Selamat Datang, {{ $user->teacher->full_name ?? $user->username }}!
                    </h2>
                    <p class="mt-1 text-sm text-indigo-100 max-w-2xl">
                        Anda telah berhasil login ke Portal Sekolah. Sesi Anda dilindungi oleh session timeout 120 menit dan tercatat pada audit logging sistem.
                    </p>
                </div>

                <div class="shrink-0">
                    <span class="inline-block px-4 py-2 bg-white/10 rounded-xl text-xs font-mono font-medium border border-white/20">
                        Session Lifetime: 120 Min
                    </span>
                </div>
            </div>
        </div>

        <!-- Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Card 1: Data Akun -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="p-2 bg-indigo-50 text-indigo-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Identitas Pengguna</h3>
                        <p class="text-xs text-slate-500">Tabel users (Prompt 23)</p>
                    </div>
                </div>

                <dl class="space-y-3 text-xs">
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Username / NIP</dt>
                        <dd class="font-semibold text-slate-900 font-mono">{{ $user->username }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Email</dt>
                        <dd class="font-semibold text-slate-900">{{ $user->email }}</dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Role Sistem</dt>
                        <dd class="font-semibold text-slate-900">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold
                                @if($user->role === 'admin') bg-purple-100 text-purple-700
                                @elseif($user->role === 'teacher') bg-blue-100 text-blue-700
                                @elseif($user->role === 'supervisor') bg-amber-100 text-amber-700
                                @else bg-slate-100 text-slate-700 @endif">
                                {{ strtoupper($user->role) }}
                            </span>
                        </dd>
                    </div>
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <dt class="text-slate-500">Status Akun</dt>
                        <dd class="font-semibold text-emerald-600 flex items-center gap-1">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                            Aktif (is_active = true)
                        </dd>
                    </div>
                    <div class="flex justify-between py-1">
                        <dt class="text-slate-500">Login Terakhir</dt>
                        <dd class="font-medium text-slate-700">
                            {{ $user->last_login_at?->format('d/m/Y H:i:s') ?? 'Sesi Ini' }}
                        </dd>
                    </div>
                </dl>
            </div>

            <!-- Card 2: Relasi Master Profil -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Relasi Profil Entitas</h3>
                        <p class="text-xs text-slate-500">User ↔ Teacher / Student</p>
                    </div>
                </div>

                @if ($user->teacher)
                    <dl class="space-y-3 text-xs">
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <dt class="text-slate-500">Nama Lengkap</dt>
                            <dd class="font-semibold text-slate-900">{{ $user->teacher->full_name }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <dt class="text-slate-500">NIP</dt>
                            <dd class="font-mono font-semibold text-slate-900">{{ $user->teacher->nip }}</dd>
                        </div>
                        <div class="flex justify-between py-1 border-b border-slate-50">
                            <dt class="text-slate-500">Status Kepegawaian</dt>
                            <dd class="font-semibold text-slate-700">{{ $user->teacher->employment_status ?? 'GTY' }}</dd>
                        </div>
                        <div class="flex justify-between py-1">
                            <dt class="text-slate-500">Jenis Kelamin</dt>
                            <dd class="font-semibold text-slate-700">{{ $user->teacher->gender === 'L' ? 'Laki-laki' : 'Perempuan' }}</dd>
                        </div>
                    </dl>
                @elseif ($user->role === 'admin')
                    <div class="py-4 text-center">
                        <span class="inline-flex p-3 bg-purple-50 text-purple-600 rounded-full mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                        </span>
                        <p class="text-xs font-semibold text-slate-800">Akun Administrator / Staf TU</p>
                        <p class="text-xs text-slate-500 mt-1">Mengelola operasional sekolah, user, dan audit trail.</p>
                    </div>
                @elseif ($user->role === 'supervisor')
                    <div class="py-4 text-center">
                        <span class="inline-flex p-3 bg-amber-50 text-amber-600 rounded-full mb-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </span>
                        <p class="text-xs font-semibold text-slate-800">Akun Atasan / Supervisor</p>
                        <p class="text-xs text-slate-500 mt-1">Kepala Sekolah / Pengawas untuk supervisi dokumen guru.</p>
                    </div>
                @else
                    <p class="text-xs text-slate-500 py-4 text-center">Tidak ada profil entitas khusus terhubung.</p>
                @endif
            </div>

            <!-- Card 3: Boundary & Next Milestone -->
            <div class="bg-white rounded-2xl p-6 border border-slate-200/80 shadow-xs md:col-span-2 lg:col-span-1">
                <div class="flex items-center gap-3 mb-4 pb-3 border-b border-slate-100">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-sm font-bold text-slate-900">Milestone Roadmap</h3>
                        <p class="text-xs text-slate-500">Status Fondasi Sistem</p>
                    </div>
                </div>

                <div class="space-y-3 text-xs">
                    <div class="flex items-start gap-2.5 p-2 rounded-xl bg-emerald-50 border border-emerald-100">
                        <svg class="w-4 h-4 text-emerald-600 shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-emerald-900">Prompt 24 Selesai</p>
                            <p class="text-emerald-700">Autentikasi sesi, rate limiting, hashing, dan audit logging telah aktif.</p>
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 p-2 rounded-xl bg-slate-50 border border-slate-200">
                        <svg class="w-4 h-4 text-slate-400 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="font-semibold text-slate-800">Menunggu Prompt 25</p>
                            <p class="text-slate-500">Role-Based Access Control (RBAC), prefix route per role, dan kebijakan otorisasi penuh.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="bg-white border-t border-slate-200 py-4 text-center text-xs text-slate-500">
        <p>Portal Sekolah &copy; {{ date('Y') }} &bull; Sesi ID Aktif &bull; Hashing Argon2id/Bcrypt &bull; Audit Trail Recorded</p>
    </footer>
</body>
</html>
