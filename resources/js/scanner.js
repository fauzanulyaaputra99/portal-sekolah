/**
 * Scanner Absensi Masuk/Pulang Siswa (Adjustment D).
 *
 * Acuan: PRD 01 §7.4 (GUR-PIK-002..005), PRD 02 §4b, ADDENDUM §1–§7,
 * ADR 20 [FINAL — Keputusan B2] (barcode/kamera via html5-qrcode), PRD 04 §10.
 *
 * Batasan yang sengaja dipatuhi:
 * - Vanilla JS / ES module. Tidak ada React/Vue, tidak ada WebSocket,
 *   tidak ada queue/Redis, tidak ada layanan worker.
 * - TIDAK ada penyimpanan apa pun di localStorage/sessionStorage/indexedDB:
 *   hasil decode hanya mengisi field form pada halaman ini (PRD 04 §10).
 * - Kamera BUKAN sumber identitas dan BUKAN penentu tanggal/mode. Kamera hanya
 *   mengubah pixel menjadi teks barcode; mode dan operator tetap keputusan
 *   guru + server (PRD 04 §3.2.4).
 * - Tidak ada request AJAX berisi data absensi: satu-satunya jalur tulis adalah
 *   submit form biasa (POST + CSRF) ke /guru/school-attendance-scanner.
 */
import { Html5Qrcode, Html5QrcodeSupportedFormats } from 'html5-qrcode';

/**
 * Jendela anti-decode-ganda untuk kode yang sama (UX). Ini BUKAN
 * anti-duplikasi bisnis — penolakan record ganda ditegakkan server
 * (application guard + UNIQUE student_id/attendance_date).
 */
const DUPLICATE_WINDOW_MS = 2500;

const READER_REGION_ID = 'qr-reader';

/** Preferensi kamera belakang (HP guru diarahkan ke kartu siswa). */
const CAMERA_CONFIGS = [
    { video: { facingMode: { exact: 'environment' } } },
    { video: { facingMode: 'environment' } },
    { video: true },
];

const els = {
    form: document.getElementById('scan-form'),
    mode: document.getElementById('scan-mode'),
    barcode: document.getElementById('scan-barcode'),
    status: document.getElementById('camera-status'),
    start: document.getElementById('camera-start'),
    stop: document.getElementById('camera-stop'),
    submit: document.getElementById('scan-submit'),
};

// Halaman lain (mis. halaman terkunci 403) memakai layout yang sama tanpa
// bagian scanner: tanpa elemen ini, skrip cukup diam.
if (els.form && els.mode && els.barcode) {
    initScanner();
}

function initScanner() {
    let lastDecode = { code: '', at: 0 };
    let submitting = false;
    let scanner = null;
    let running = false;

    const modeButtons = Array.from(document.querySelectorAll('.scan-mode-button'));

    const setStatus = (text) => {
        if (els.status) {
            els.status.textContent = text;
        }
    };

    /**
     * Mode dipilih EKSPISIT oleh guru (ADDENDUM §1 — auto-detect dilarang).
     * Kamera dihentikan saat mode diganti: scan yang sedang berlangsung tidak
     * boleh dikirim dengan mode yang baru saja dipilih guru.
     */
    const applyMode = (mode) => {
        els.mode.value = mode;

        modeButtons.forEach((button) => {
            button.setAttribute('aria-pressed', button.dataset.mode === mode ? 'true' : 'false');
        });
    };

    modeButtons.forEach((button) => {
        button.addEventListener('click', async () => {
            const mode = button.dataset.mode;

            if (!mode || els.mode.value === mode) {
                return;
            }

            applyMode(mode);
            els.barcode.value = '';
            lastDecode = { code: '', at: 0 };

            if (running) {
                await stopCamera();
            }

            setStatus('Mode ' + mode + ' dipilih. Nyalakan kamera atau isi kode, lalu proses scan.');
        });
    });

    const fillBarcode = (code) => {
        const now = Date.now();
        const isRepeat = code === lastDecode.code && (now - lastDecode.at) < DUPLICATE_WINDOW_MS;

        if (isRepeat) {
            return;
        }

        lastDecode = { code, at: now };
        els.barcode.value = code;

        if (submitting) {
            return;
        }

        submitting = true;

        if (els.submit) {
            els.submit.disabled = true;
            els.submit.textContent = 'Memproses…';
        }

        // Submit form biasa -> POST + middleware CSRF web group.
        els.form.submit();
    };

    async function stopCamera() {
        if (!scanner || !running) {
            running = false;
            return;
        }

        try {
            await scanner.stop();
        } catch (error) {
            // Stop bisa gagal bila stream sudah mati; tetap lanjut ke cleanup.
        }

        try {
            await scanner.clear();
        } catch (error) {
            // Abaikan: elemen reader akan diganti instance baru.
        }

        running = false;
        scanner = null;

        if (els.start) {
            els.start.disabled = false;
        }

        if (els.stop) {
            els.stop.disabled = true;
        }
    }

    async function startCamera() {
        if (running) {
            setStatus('Kamera sudah aktif.');
            return;
        }

        if (!window.isSecureContext) {
            // getUserMedia mensyaratkan secure context (PRD 04 §5.2: scanner
            // produksi berjalan di atas HTTPS). Tidak ada upaya bypass.
            setStatus('Laman ini tidak berjalan di konteks aman (HTTPS). Gunakan input manual.');
            return;
        }

        if (typeof navigator === 'undefined' || !navigator.mediaDevices) {
            setStatus('Kamera tidak tersedia pada browser ini. Gunakan input manual.');
            return;
        }

        if (els.start) {
            els.start.disabled = true;
        }

        setStatus('Meminta izin kamera…');

        for (const config of CAMERA_CONFIGS) {
            const instance = new Html5Qrcode(READER_REGION_ID, {
                formatsToSupport: [
                    Html5QrcodeSupportedFormats.CODE_128,
                    Html5QrcodeSupportedFormats.CODE_39,
                    Html5QrcodeSupportedFormats.EAN_13,
                    Html5QrcodeSupportedFormats.EAN_8,
                    Html5QrcodeSupportedFormats.UPC_A,
                    Html5QrcodeSupportedFormats.UPC_E,
                    Html5QrcodeSupportedFormats.QR_CODE,
                ],
                verbose: false,
            });

            try {
                await instance.start(
                    config,
                    {
                        fps: 10,
                        qrbox: { width: 260, height: 140 },
                        aspectRatio: 1.777,
                        disableFlip: false,
                    },
                    (decodedText) => fillBarcode(String(decodedText).trim()),
                    () => {
                        /* frame tanpa deteksi — biarkan loop berjalan */
                    },
                );

                scanner = instance;
                running = true;

                if (els.stop) {
                    els.stop.disabled = false;
                }

                setStatus('Kamera aktif. Arahkan ke barcode kartu siswa.');
                return;
            } catch (error) {
                try {
                    await instance.clear();
                } catch (cleanupError) {
                    // Instances yang gagal start tidak menyisakan stream.
                }
            }
        }

        if (els.start) {
            els.start.disabled = false;
        }

        running = false;
        setStatus('Kamera tidak dapat dibuka (ditolak/tidak ada). Gunakan input manual kode barcode.');
    }

    if (els.start) {
        els.start.disabled = false;
        els.start.addEventListener('click', startCamera);
    }

    if (els.stop) {
        els.stop.disabled = true;
        els.stop.addEventListener('click', async () => {
            await stopCamera();
            setStatus('Kamera dimatikan.');
        });
    }

    // Bila server mengembalikan error validasi (redirect-back), submit
    // dikunci-lepas agar guru bisa mencoba lagi.
    els.form.addEventListener('submit', (event) => {
        if (event.defaultPrevented) {
            return;
        }

        if (!els.barcode.value.trim()) {
            event.preventDefault();
            submitting = false;
            setStatus('Isi kode barcode terlebih dahulu.');
            return;
        }

        submitting = true;
        void stopCamera();
    });

    window.addEventListener('pagehide', () => {
        void stopCamera();
    });

    if (els.barcode) {
        els.barcode.focus();
    }
}
