<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Error masking (PRD 04 §10.1) — LAPIS KEDUA.
 *
 * Lapis PERTAMA adalah view milik aplikasi di resources/views/errors/
 * (4xx & 5xx). View itu yang menjadi alasan utama perbaikan ini: framework
 * hanya punya view untuk sebagian status (401/402/403/404/419/429/500/503),
 * sehingga status lain — contohnya 405 Method Not Allowed — jatuh ke
 * Symfony HtmlErrorRenderer. Pada APP_DEBUG=true hasilnya halaman debug
 * ±867 kB berisi 358 path file framework + nama class exception (terbukti
 * dari probe nyata ke server development).
 *
 * Class ini hanya bekerja saat config('app.debug') === false dan hanya untuk
 * status >= 400.
 *
 * ## Kenapa desainnya "ganti seluruh body", bukan "sisip regex"?
 * Body yang TIDAK terdeteksi sebagai halaman debug framework dipastikan apa
 * adanya: view error milik aplikasi hanya memuat angka status + teks statis,
 * dan view bisnis (mis. halaman terkunci 403 "Anda hari ini bukan Guru Piket.")
 * tidak memuat detail internal sama sekali. Menyaring teks view dengan regex
 * justru BERBAHAYA — bisa menghapus pesan UX yang diwajibkan PRD. Jadi:
 *   - terdeteksi halaman debug  -> diganti halaman generik;
 *   - payload JSON debug        -> key exception/file/line/trace dibuang;
 *   - selain itu                -> tidak disentuh.
 */
final class ErrorMask
{
    /**
     * Penanda khas halaman debug framework. Semua string ini MUSTAHIL muncul
     * pada view aplikasi (view aplikasi tidak menyebut vendor/FQCN/path).
     *
     * @var array<int, string>
     */
    private const DEBUG_MARKERS = [
        'vendor/laravel',
        'vendor\\laravel',
        'Illuminate\\',
        'Symfony\\Component',
        'resources/views',
        'resources\\views',
        'bootstrap/app.php',
        'bootstrap\\app.php',
        'public/index.php',
        'public\\index.php',
        'HttpKernel',
        'MethodNotAllowedHttpException',
        'NotFoundHttpException',
        'AccessDeniedHttpException',
        'TokenMismatchException',
        'Stacktrace',
        'stack-trace',
    ];

    /**
     * @var array<string, string>
     *
     * Pola path absolut. `(?<!\w)[A-Za-z]:\\` sengaja MEMERLUKAN backslash
     * setelah drive letter agar "https://host/x" tidak ikut kena (URL memakai
     * forward slash), dan `(?<!\w)` menolak huruf URL ("s://" dari "https").
     */
    private const PATH_PATTERNS = [
        '~(?<!\w)[A-Za-z]:\\\\[^\s"\'<>|]{2,200}~',       // D:\anti\portal-sekolah\...
        '~(?<!\w)(?:/usr/|/var/|/home/|/etc/|/opt/)[^\s"\'<>|]{2,200}~', // *nix
        '~\b\#?\d+\s+[\w\\/.:\-]{4,}\.php(?:\(\d+\))?~',   // "#12 /a/b/C.php(34)"
    ];

    /** Terapkan penyaringan (no-op saat debug=true atau status < 400). */
    public static function apply(Response $response): Response
    {
        if (config('app.debug') === true || $response->getStatusCode() < 400) {
            return $response;
        }

        // Response biner/streamed tidak punya "konten" yang bisa dibaca dan
        // tidak pernah berupa halaman debug; menyentuhnya justru memicu
        // LogicException pada jalur unduh berkas.
        if ($response instanceof StreamedResponse || $response instanceof BinaryFileResponse) {
            return $response;
        }

        return $response instanceof JsonResponse
            ? self::stripJsonDebugKeys($response)
            : self::replaceDebugPageBody($response);
    }

    /**
     * Buang key yang hanya dikeluarkan renderer debug JSON
     * (Illuminate\Foundation\Exceptions\Handler::prepareJsonResponse).
     * Pesan validasi milik aplikasi (`message`, `errors`) TIDAK disentuh agar
     * UX klien JSON tetap berguna.
     */
    private static function stripJsonDebugKeys(JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);

        if (is_array($payload)) {
            $response->setData(self::withoutDebugKeys($payload));
        }

        return $response;
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function withoutDebugKeys(array $data): array
    {
        unset(
            $data['exception'],
            $data['file'],
            $data['line'],
            $data['trace'],
            $data['errors_trace'],
            $data['previous'],
        );

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::withoutDebugKeys($value);
            }
        }

        return $data;
    }

    private static function replaceDebugPageBody(Response $response): Response
    {
        $content = $response->getContent();

        if (! is_string($content) || $content === '') {
            return $response;
        }

        if (! self::looksLikeFrameworkDebugPage($content)) {
            // View aplikasi: biarkan utuh (berisi pesan UX yang diwajibkan PRD).
            return $response;
        }

        $response->setContent(self::genericPage($response->getStatusCode()));

        return $response;
    }

    private static function looksLikeFrameworkDebugPage(string $content): bool
    {
        foreach (self::DEBUG_MARKERS as $marker) {
            if (str_contains($content, $marker)) {
                return true;
            }
        }

        foreach (self::PATH_PATTERNS as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Halaman generik super-kecil tanpa dependensi view/asset — aman dipakai
     * bahkan ketika error terjadi di tengah rendering view.
     */
    public static function genericPage(int $status): string
    {
        $status = ($status >= 400 && $status <= 599) ? $status : 500;

        return '<!DOCTYPE html><html lang="id"><head><meta charset="utf-8">'
            . '<meta name="viewport" content="width=device-width, initial-scale=1">'
            . '<meta name="robots" content="noindex,nofollow">'
            . '<title>Terjadi kesalahan — ' . $status . '</title></head>'
            . '<body style="font-family:system-ui,sans-serif;margin:0;padding:40px;background:#0f172a;color:#e2e8f0">'
            . '<div style="max-width:32rem;margin:0 auto;text-align:center">'
            . '<div style="font-size:2.5rem;font-weight:700;color:#38bdf8">' . $status . '</div>'
            . '<p style="color:#94a3b8">Permintaan tidak dapat diproses. '
            . 'Silakan ulangi dari beranda atau hubungi Admin/Staff TU.</p>'
            . '</div></body></html>';
    }
}
