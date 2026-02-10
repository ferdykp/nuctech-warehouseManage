<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;

use App\Models\Site;
use App\Models\Sparepart;
use App\Models\Report;


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
        view()->composer('*', function ($view) {
            $role = Auth::check() ? Auth::user()->role : null;

            $machineQuery = Site::query();
            $sparepartQuery = Sparepart::query();
            $reportQuery = Report::query();

            $dataCounts = [
                'totalMachine' => $machineQuery->count(),
                'totalSparepart' => $sparepartQuery->count(),
                'totalReport' => $reportQuery->count(),

            ];

            $view->with($dataCounts);
        });
    }
}
