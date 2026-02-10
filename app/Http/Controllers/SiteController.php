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
        $site = Site::with('branch')->get();
        // return Site::all();
        // return view('site.siteList', compact('site'));
        return view('spareparts.index', compact('site'));
    }

    public function create()
    {
        // return view('site.create');
        $branches = Branch::all();
        return view('site.create', compact('branches'));
    }

    // public function store(Request $request)
    // {
    //     $request->validate([
    //         'name' => 'required|string|max:255',
    //         'is_active' => 'required|boolean',
    //     ]);

    //     // Ambil kata pertama dari name
    //     $firstWord = explode(' ', trim($request->name))[0];

    //     Site::create([
    //         'code' => 'IDN_' . strtoupper($firstWord),
    //         'name' => $request->name,
    //         'machine_type' => strtolower($firstWord),
    //         'is_active' => $request->is_active,
    //     ]);

    //     return redirect()
    //         ->route('site.index')
    //         ->with('success', 'Site berhasil ditambahkan');
    // }
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


    public function show($code)
    {
        // return Site::where('code', $code)->firstOrFail();
        $site = Site::findOrFail($code); // Akan melempar error 404 jika user tidak ditemukan
    }

    // public function edit($id)
    // {
    //     $site = Site::findOrFail($id);
    //     return view('site.siteEdit', compact('site'));
    // }
    public function edit($id)
    {
        $site = Site::findOrFail($id);
        $branches = Branch::all(); // Dibutuhkan jika ingin mengubah lokasi branch
        return view('site.siteEdit', compact('site', 'branches'));
    }

    // public function update(Request $request, Site $site)
    // {
    //     $validatedData = $request->validate([
    //         'code' => 'required|string|max:255',
    //         'name' => 'required|string|max:255' . $site->id,
    //         'machine_type' => 'required|string|max:255' . $site->id,
    //         'is_active' => 'required|string',
    //     ]);

    //     // Update data site
    //     $site->name = $validatedData['code'];
    //     $site->site = $validatedData['name'];
    //     $site->email = $validatedData['machine_type'];
    //     $site->role = $validatedData['is_active'];

    //     // Jika password diisi, baru diupdate
    //     if (!empty($validatedData['password'])) {
    //         $site->password = bcrypt($validatedData['password']);
    //     }

    //     $site->save();

    //     return redirect()->route('site.index')->with('success', 'Site berhasil diperbarui');
    // }

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

    // public function destroy($id)
    // {
    //     $site = Site::findOrFail($id);

    //     // Log sebelum penghapusan

    //     $site->delete();

    //     // Log setelah penghapusan
    //     return redirect()->route('site.index')->with('success', 'Site berhasil dihapus.');
    // }
    public function destroy($id)
    {
        $site = Site::findOrFail($id);
        $site->delete();

        return redirect()->route('site.index')->with('success', 'Site berhasil dihapus.');
    }
}
