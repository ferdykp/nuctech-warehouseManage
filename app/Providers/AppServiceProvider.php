<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
// use Illuminate\View\View;
use Illuminate\Support\Facades\View;
// use Illuminate\Support\Facades\Auth;
// use Illuminate\Support\Facades\URL;


use App\Models\Site;
use App\Models\Sparepart;
use App\Models\Report;
use App\Models\Branch;


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
        // FORCE HTTPS DI PRODUCTION
        // if ($this->app->environment('production')) {
        //     URL::forceScheme('https');
        // }

        $this->app->booted(function () {

            \Illuminate\Support\Facades\View::composer('*', function ($view) {

                // ===== Dashboard Counters =====
                $dataCounts = [
                    'totalMachine'   => \App\Models\Site::count(),
                    'totalSparepart' => \App\Models\Sparepart::count(),
                    'totalReport'    => \App\Models\Report::count(),
                    'totalBranch'    => \App\Models\Branch::count()
                ];

                // ===== Sidebar Data =====
                $sidebarSites = \App\Models\Site::with('branch')
                    ->orderBy('machine_name')
                    ->get();

                $sidebarBranches = \App\Models\Branch::orderBy('branch_name')->get();

                $view->with($dataCounts)
                    ->with('sidebarSites', $sidebarSites)
                    ->with('sidebarBranches', $sidebarBranches);
            });
        });
    }
}
