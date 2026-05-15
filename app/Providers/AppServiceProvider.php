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

        View::composer(['includes.citizen-navbar', 'includes.citizen-sidebar'], function ($view) {
            $user = Auth::user();

            $view->with('citizenUnreadMessageCount', $user
                ? RequestMessage::query()
                    ->unread()
                    ->where('sender_id', '!=', $user->id)
                    ->whereHas('serviceRequest', function ($query) use ($user) {
                        $query->where('user_id', $user->id);
                    })
                    ->count()
                : 0);
        });
    }
}
