<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    {{-- Scanner hanya berjalan di atas HTTPS di produksi (PRD 04 §5.2); kamera
         browser (getUserMedia) mensyaratkan secure context. Tidak ada data
         scanner yang disimpan di localStorage/sessionStorage (PRD 04 §10). --}}
    <title>@yield('title', 'Scanner Absensi Siswa') — Portal Sekolah</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600,700" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/scanner.js'])
    @endif

    <style>
        body { font-family: 'Instrument Sans', ui-sans-serif, system-ui, sans-serif; }

        /* Tombol mode scanner: state aktif ditentukan oleh atribut aria-pressed
           yang dikelola JS/browser, bukan oleh kelas yang ditempel manual. */
        .scan-mode-button {
            border-color: #cbd5e1;
            background-color: #ffffff;
            color: #475569;
        }
        .scan-mode-button:hover {
            border-color: #94a3b8;
            background-color: #f8fafc;
        }
        .scan-mode-button[aria-pressed="true"] {
            border-color: #059669;
            background-color: #ecfdf5;
            color: #065f46;
            box-shadow: 0 0 0 3px rgba(5, 150, 105, 0.15);
        }
        /* Kamera html5-qrcode menyisipkan <video>/<canvas> penuh lebar. */
        #qr-reader video {
            width: 100%;
            display: block;
        }
        #qr-reader:not(:has(video)) {
            min-height: 9rem;
            background-image: linear-gradient(135deg, #0f172a, #1e293b);
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 antialiased min-h-screen flex flex-col">
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-3xl mx-auto px-4 sm:px-6">
            <div class="flex justify-between items-center h-16">
                <div>
                    <h1 class="text-base font-bold text-slate-900 leading-tight">Portal Sekolah — Guru</h1>
                    <p class="text-xs text-slate-500">@yield('subtitle', 'Scanner Absensi Masuk/Pulang Siswa')</p>
                </div>
                {{-- Navigasi saja, BUKAN kontrol keamanan. Otorisasi scanner
                     ditegakkan server-side (middleware role:teacher + on.duty). --}}
                <div class="flex items-center gap-3">
                    <a href="{{ route('guru.dashboard') }}"
                       class="text-xs font-semibold text-slate-600 hover:text-slate-900">Dashboard</a>
                    <span class="text-xs font-semibold text-slate-600">{{ auth()->user()->username ?? '' }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit"
                                class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg px-3 py-1.5 bg-white">
                            Keluar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="max-w-3xl mx-auto px-4 sm:px-6 py-6 w-full flex-1">
        @if (session('scan_status'))
            <div class="mb-4 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm" role="status">
                {{-- Dikeluarkan lewat sintaks {{ }} => di-escape Blade (PRD 04 §8). --}}
                {{ session('scan_status') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="mb-4 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm" role="alert">
                <p class="font-semibold">Scan tidak tersimpan:</p>
                <ul class="mt-1 list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>
</body>
</html>
