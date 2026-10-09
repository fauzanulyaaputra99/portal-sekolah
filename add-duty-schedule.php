<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Teacher;
use App\Models\TeacherDutySchedule;
use App\Models\User;

$teacher = Teacher::whereHas('user', fn($q) => $q->where('username', '198501012010011001'))->first();
$admin = User::where('role', 'admin')->first();

if ($teacher && $admin) {
    TeacherDutySchedule::updateOrCreate(
        [
            'teacher_id' => $teacher->id,
            'schedule_date' => today()->toDateString(),
        ],
        [
            'notes' => 'Jadwal piket test - auto generated',
            'created_by_user_id' => $admin->id,
        ]
    );
    
    echo "✅ Jadwal piket berhasil dibuat untuk: {$teacher->full_name}\n";
    echo "📅 Tanggal: " . today()->toDateString() . "\n";
} else {
    echo "❌ Guru atau admin tidak ditemukan\n";
}
