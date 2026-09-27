<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Tampilkan halaman sementara setelah login (User Foundation).
     * Membuktikan bahwa autentikasi session telah berhasil.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        // Muat relasi teacher atau student jika ada
        if ($user->role === 'teacher') {
            $user->load('teacher');
        } elseif ($user->role === 'student') {
            $user->load('student');
        }

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
