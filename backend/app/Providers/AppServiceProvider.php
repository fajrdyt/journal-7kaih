<?php

namespace App\Providers;

use App\Models\DailyCheckin;
use App\Policies\CheckinPolicy;
use App\Policies\ValidationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Gate::policy(DailyCheckin::class, CheckinPolicy::class);

        Gate::define('view-parent-checkin', [ValidationPolicy::class, 'viewParentCheckin']);
        Gate::define('view-teacher-checkin', [ValidationPolicy::class, 'viewTeacherCheckin']);

        Gate::define('validate-home', [ValidationPolicy::class, 'validateHome']);
        Gate::define('validate-home-item', [ValidationPolicy::class, 'validateHomeItem']);

        Gate::define('validate-school', [ValidationPolicy::class, 'validateSchool']);
        Gate::define('validate-school-item', [ValidationPolicy::class, 'validateSchoolItem']);
    }
}