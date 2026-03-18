<?php

namespace App\Providers;

use App\Models\Announcement;
use App\Models\Building;
use App\Models\Tenant;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
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
        // Force HTTPS in production to match APP_URL scheme
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Schema::defaultStringLength(191);

        View::composer('*', function ($view) {
            $count = 0;

            if (! Schema::hasTable('announcements')) {
                $view->with('globalAnnouncementCount', $count);
                return;
            }

            if (Auth::check()) {
                $user = Auth::user();

                if ($user->isAdmin()) {
                    $count = Announcement::whereDoesntHave('dismissedBy', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    })->count();
                } elseif ($user->isManager()) {
                    $buildingIds = Building::where('manager_id', $user->id)->pluck('id');
                    if ($buildingIds->isNotEmpty()) {
                        $count = Announcement::whereIn('building_id', $buildingIds)
                            ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                                $q->where('users.id', $user->id);
                            })->count();
                    }
                } elseif ($user->isTenant()) {
                    $tenant = $user->tenant;
                    if (! $tenant) {
                        $tenant = Tenant::where('email', $user->email)->first();
                    }
                    if ($tenant) {
                        $rent = $tenant->currentRent ?? $tenant->rents()->active()->latest()->first();
                        $buildingId = $rent ? optional($rent->unit)->building_id : optional(optional($tenant->unit))->building_id;
                        if ($buildingId) {
                            $count = Announcement::where('building_id', $buildingId)
                                ->whereDoesntHave('dismissedBy', function ($q) use ($user) {
                                    $q->where('users.id', $user->id);
                                })->count();
                        }
                    }
                }
            }

            $view->with('globalAnnouncementCount', $count);
        });
    }
}
