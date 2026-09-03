<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache; // <--- 1. Tambahkan Import Cache

class SiteController extends Controller
{
    public function index()
    {
        $branches = Branch::all();
        $sites = Site::with('branch')->paginate(10);
        return view('site.index', compact('sites', 'branches'));
    }

    public function create()
    {
        $branches = Branch::all();
        return view('site.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'machine_name' => 'required',
        ]);

        Site::create([
            'branch_id'    => $request->branch_id,
            'machine_name' => $request->machine_name,
            'slug'         => Str::slug($request->machine_name) . '-' . Str::random(5),
            'location'     => $request->location,
        ]);

        // <--- 2. Hapus Cache Sidebar & Dashboard saat ada site baru
        Cache::forget('global_sidebar_sites');
        Cache::forget('dashboard_counters');

        return redirect()->route('site.index')->with('success', 'Site successfully created.');
    }

    public function show($slug)
    {
        $site = Site::findOrFail($slug);
    }

    public function edit($id)
    {
        $site = Site::findOrFail($id);
        $branches = Branch::all();
        return view('site.siteEdit', compact('site', 'branches'));
    }

    public function update(Request $request, Site $site)
    {
        $request->validate([
            'branch_id'    => 'required|exists:branches,id',
            'machine_name' => 'required|string|max:255',
        ]);

        $site->update([
            'branch_id'    => $request->branch_id,
            'machine_name' => $request->machine_name,
            'slug'         => Str::slug($request->machine_name) . '-' . Str::random(5),
            'location'     => $request->location,
        ]);

        // <--- 3. Hapus Cache Sidebar saat ada update data site
        Cache::forget('global_sidebar_sites');

        return redirect()->route('site.index')->with('success', 'Site Successfully Updated');
    }

    public function destroy($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();

        // <--- 4. Hapus Cache Sidebar & Dashboard saat site dihapus (Garis Solusi Utama)
        Cache::forget('global_sidebar_sites');
        Cache::forget('dashboard_counters');

        return redirect()->route('site.index')->with('success', 'Site successfully Deleted.');
    }
}
