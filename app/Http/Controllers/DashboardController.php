<?php

namespace App\Http\Controllers;

use App\Models\Maintenance;


class DashboardController extends Controller
{
    public function index()
    {
        /*$notes = Maintenance::where('status', 'belum selesai')->get();*/
        // $notes = Maintenance::all();
        return view('dashboard.index');
        // $criticalStock = \App\Models\Sparepart::where('stock', '<=', 'minimum_stock')->count();

        // return view('dashboard.index', compact(
        //     'totalBranch',
        //     'totalSparepart',
        //     'totalMachine',
        //     'criticalStock'
        // ));
    }
}
