<?php

namespace App\Http\Controllers;

use App\Models\Site;

class SiteController extends Controller
{
    public function index()
    {
        return Site::all();
    }

    public function show($code)
    {
        return Site::where('code', $code)->firstOrFail();
    }
}
