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
    }
}
