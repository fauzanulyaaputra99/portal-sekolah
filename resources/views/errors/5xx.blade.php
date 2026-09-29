{{--
    Halaman galat 5xx milik aplikasi (menimpa view bawaan 500 karena path view
    aplikasi didaftarkan lebih dulu oleh RegisterErrorViewPaths), sekaligus
    penutup status 5xx tanpa view sendiri (mis. 501/502).

    PRD 04 §10.1: tampilan galat generik yang bersih - DILARANG menampilkan
    stack trace, query database mentah, path filesystem server, atau variabel
    lingkungan. Pesan $exception SENGAJA tidak pernah dibaca; hanya angkanya.
    Detail teknis tetap tersimpan di log internal (PRD 04 §10.2).
--}}
@php
    $__status = 500;

    if (isset($exception) && method_exists($exception, 'getStatusCode')) {
        $code = (int) $exception->getStatusCode();
        $__status = ($code >= 500 && $code <= 599) ? $code : 500;
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Sistem mengalami kendala — {{ $__status }}</title>
    {{-- Self-contained: tidak bergantung pada asset build. --}}
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
            background:#0f172a;color:#e2e8f0;padding:24px}
        .card{max-width:32rem;width:100%;background:#1e293b;border:1px solid #334155;
            border-radius:14px;padding:32px;text-align:center;box-shadow:0 12px 32px rgba(2,6,23,.45)}
        .code{font-size:3rem;font-weight:700;letter-spacing:-.02em;color:#fbbf24;line-height:1}
        h1{font-size:1.15rem;margin:14px 0 8px}
        p{margin:0;color:#94a3b8;font-size:.94rem;line-height:1.55}
        a{display:inline-block;margin-top:22px;padding:11px 20px;border-radius:10px;
            background:#0ea5e9;color:#04121f;font-weight:600;text-decoration:none;font-size:.92rem}
        a:hover{background:#38bdf8}
        .hint{margin-top:18px;font-size:.8rem;color:#64748b}
    </style>
</head>
<body>
    <main class="card">
        <div class="code">{{ $__status }}</div>
        <h1>Sistem sedang mengalami kendala</h1>
        <p>Permintaan Anda tidak dapat diselesaikan saat ini. Silakan coba beberapa saat lagi.</p>
        <a href="{{ url('/dashboard') }}">Kembali ke Beranda</a>
        <p class="hint">Jika kendala berlanjut, laporkan ke Admin/Staff TU beserta waktunya.</p>
    </main>
</body>
</html>
