<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SiteController extends Controller
{
    public function index()
    {
        // $site = Site::all();
        // $site = Site::with('branch')->get();
        // return Site::all();
        // return view('site.siteList', compact('site'));
        // return view('dashboard.index', compact('site'));
        $branches = Branch::all();
        $sites = Site::with('branch')->paginate(10);
        // return view('site.index', compact('sites, branches'));
        return view('site.index', compact('sites', 'branches'));
        // return view('spareparts.index', compact('site', 'branches'));
    }

    public function create()
    {
        // return view('site.create');
        $branches = Branch::all();
        return view('site.create', compact('branches'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'machine_name' => 'required',
        ]);

        Site::create([
            'branch_id' => $request->branch_id,
            'machine_name' => $request->machine_name,
            'slug'         => Str::slug($request->machine_name) . '-' . Str::random(5)
        ]);
        return redirect()->route('site.index')->with('success', 'Site berhasil dibuat');
        // return redirect()->back()->with('success', 'Site berhasil dibuat');
    }


    public function show($slug)
    {
        // return Site::where('code', $code)->firstOrFail();
        $site = Site::findOrFail($slug); // Akan melempar error 404 jika user tidak ditemukan
    }


    public function edit($id)
    {
        $site = Site::findOrFail($id);
        $branches = Branch::all(); // Dibutuhkan jika ingin mengubah lokasi branch
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
            // Update slug jika nama mesin berubah
            'slug'         => Str::slug($request->machine_name) . '-' . Str::random(5)
        ]);

        return redirect()->route('site.index')->with('success', 'Site berhasil diperbarui');
    }


    public function destroy($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();

        return redirect()->route('site.index')->with('success', 'Site berhasil dihapus.');
    }
}
