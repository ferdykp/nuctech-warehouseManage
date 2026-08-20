<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;

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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // HANYA SHARE KEDALAM ASIDE NAV, BUKAN WILDCARD ('*')
        View::composer('layout.aside', function ($view) {

            // Cache data sidebar selama 10 menit agar tidak query terus-menerus di lokal
            $sidebarSites = Cache::remember('global_sidebar_sites', 600, function () {
                return Site::select('id', 'machine_name', 'slug', 'branch_id')
                    ->orderBy('machine_name')
                    ->get();
            });

            $globalBranches = Cache::remember('global_branches', 600, function () {
                return Branch::select('id', 'branch_name')
                    ->orderBy('branch_name')
                    ->get();
            });

            $view->with('sidebarSites', $sidebarSites)
                ->with('globalBranches', $globalBranches);
        });

        // HANYA SHARE COUNTERS KE DASHBOARD VIEW
        View::composer('dashboard.index', function ($view) {
            $dataCounts = Cache::remember('dashboard_counters', 60, function () {
                return [
                    'totalMachine'   => Site::count(),
                    'totalSparepart' => Sparepart::count(),
                    'totalReport'    => Report::count(),
                    'totalBranch'    => Branch::count()
                ];
            });

            $view->with($dataCounts);
        });
    }
}
