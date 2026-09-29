{{--
    Halaman galat untuk SEMUA status 4xx yang tidak punya view sendiri,
    termasuk HTTP 405 Method Not Allowed.

    Kenapa view ini ada (bukti, bukan asumsi):
    renderer bawaan hanya memiliki view untuk 401/402/403/404/419/429/500/503.
    Status di luar daftar itu jatuh ke Symfony HtmlErrorRenderer, dan pada
    APP_DEBUG=true respons 405 terukur ±867 kB memuat 358 path file framework
    + FQCN exception. View milik aplikasi MENANG atas view vendor
    (RegisterErrorViewPaths mendaftarkan path aplikasi lebih dulu) dan dipakai
    untuk status HTTP >= 400 BAHKAN saat debug=true, karena
    Handler::renderHttpException() dievaluasi sebelum renderer Symfony.

    PRD 04 §10.1: tidak menampilkan stack trace, query, path filesystem, atau
    variabel lingkungan. Pesan $exception SENGAJA tidak pernah dibaca.
--}}
@php
    // Hanya ANGKA STATUS yang diambil dari exception, dengan penjagaan ketat.
    $__status = 400;

    if (isset($exception) && method_exists($exception, 'getStatusCode')) {
        $code = (int) $exception->getStatusCode();
        $__status = ($code >= 400 && $code <= 499) ? $code : 400;
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex,nofollow">
    <title>Permintaan tidak dapat diproses — {{ $__status }}</title>
    {{-- Self-contained: halaman error tidak boleh bergantung pada asset build. --}}
    <style>
        *{box-sizing:border-box}
        body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;
            font-family:system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
            background:#0f172a;color:#e2e8f0;padding:24px}
        .card{max-width:32rem;width:100%;background:#1e293b;border:1px solid #334155;
            border-radius:14px;padding:32px;text-align:center;box-shadow:0 12px 32px rgba(2,6,23,.45)}
        .code{font-size:3rem;font-weight:700;letter-spacing:-.02em;color:#38bdf8;line-height:1}
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
        <h1>Permintaan tidak dapat diproses</h1>
        <p>Tautan, metode, atau formulir yang Anda gunakan tidak dapat diproses oleh sistem. Silakan kembali ke halaman sebelumnya.</p>
        <a href="{{ url('/dashboard') }}">Kembali ke Beranda</a>
        <p class="hint">Jika keluhan ini muncul berulang kali, hubungi Admin/Staff TU.</p>
    </main>
</body>
</html>
