<?php

namespace App\Providers;

use App\Models\Appointment;
use App\Models\Feedback;
use App\Models\RequestMessage;
use App\Models\ServiceRequest;
use App\Policies\AppointmentPolicy;
use App\Policies\FeedbackPolicy;
use App\Policies\RequestMessagePolicy;
use App\Policies\ServiceRequestPolicy;
use App\Services\AdminNavbarDataService;
use App\Services\CitizenNavbarDataService;
use App\Services\MunicipalityNavbarDataService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
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
     */
    public function boot(): void
    {
        Gate::policy(ServiceRequest::class, ServiceRequestPolicy::class);
        Gate::policy(Appointment::class, AppointmentPolicy::class);
        Gate::policy(Feedback::class, FeedbackPolicy::class);
        Gate::policy(RequestMessage::class, RequestMessagePolicy::class);

        View::composer(['includes.municipality-navbar', 'includes.municipality-sidebar'], function ($view) {
            $view->with(app(MunicipalityNavbarDataService::class)->forUser(Auth::user()));
        });

        View::composer(['includes.admin-navbar', 'includes.admin-sidebar'], function ($view) {
            $view->with(app(AdminNavbarDataService::class)->forUser(Auth::user()));
        });

        View::composer(['includes.citizen-navbar', 'includes.citizen-sidebar'], function ($view) {
            $view->with(app(CitizenNavbarDataService::class)->forUser(Auth::user()));
        });
    }
}
