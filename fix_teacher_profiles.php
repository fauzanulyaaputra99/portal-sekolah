<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;
use App\Models\Teacher;
use Illuminate\Support\Facades\DB;

echo "Memeriksa user dengan role 'teacher' yang belum punya profil di tabel 'teachers'...\n";

// Cari semua user teacher
$teacherUsers = User::where('role', 'teacher')->get();

foreach ($teacherUsers as $user) {
    // Cek apakah sudah punya profil teacher
    $teacherProfile = Teacher::where('user_id', $user->id)->first();

    if (!$teacherProfile) {
        echo "User '{$user->username}' (ID: {$user->id}) belum punya profil teacher. Membuatkan...\n";

        // Generate NIP unik agar tidak bentrok
        // Format: 1990 + ID user + random 4 digit
        $uniqueNip = '1990' . str_pad($user->id, 4, '0', STR_PAD_LEFT) . rand(1000, 9999);

        try {
            Teacher::create([
                'user_id' => $user->id,
                'nip' => $uniqueNip,
                'full_name' => $user->username . ' (Auto Generated)',
                'gender' => 'L',
                'phone_number' => '081234567890',
                'employment_status' => 'GTY',
                'address' => 'Alamat belum diisi',
            ]);
            echo "  -> Profil berhasil dibuat dengan NIP: {$uniqueNip}\n";
        } catch (\Exception $e) {
            echo "  -> Gagal membuat profil: " . $e->getMessage() . "\n";
        }
    } else {
        echo "User '{$user->username}' sudah punya profil teacher (NIP: {$teacherProfile->nip}).\n";
    }
}

echo "\nSelesai. Silakan cek dropdown Guru di halaman Jadwal Piket Admin.\n";
