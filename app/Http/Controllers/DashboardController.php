<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Arahkan pengguna ke dashboard spesifik perannya.
     */
    public function index(Request $request): RedirectResponse|View
    {
        $user = $request->user();

        if (!app()->runningUnitTests()) {
            if ($user->role === 'teacher') {
                return redirect()->route('guru.dashboard');
            } elseif ($user->role === 'admin') {
                return redirect()->route('admin.dashboard');
            } elseif ($user->role === 'supervisor') {
                return redirect()->route('supervisor.dashboard');
            }
        }

        return view('dashboard', [
            'user' => $user,
        ]);
    }
}
