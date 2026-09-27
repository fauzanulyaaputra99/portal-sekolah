<?php

namespace App\Providers;

use App\Models\Document;
use App\Models\StudentAttendanceSession;
use App\Models\TeachingAssignment;
use App\Policies\DocumentPolicy;
use App\Policies\StudentAttendanceSessionPolicy;
use App\Policies\TeachingAssignmentPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Registrasi Policy EKSPLISIT (PRD 04 §3.2.3). Ditulis eksplisit agar
     * pemetaan model -> policy tidak bergantung pada auto-discovery, sehingga
     * penegakan otorisasi level resource (anti-IDOR) tidak diam-diam hilang
     * bila nama kelas/namespace berubah.
     */
    public function boot(): void
    {
        Gate::policy(Document::class, DocumentPolicy::class);
        Gate::policy(TeachingAssignment::class, TeachingAssignmentPolicy::class);
        Gate::policy(StudentAttendanceSession::class, StudentAttendanceSessionPolicy::class);
    }
}
