<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SparepartExport;

class SparepartController extends Controller
{
    private function getSite(string $slug): Site
    {
        return Site::where('slug', $slug)->firstOrFail();
    }

    // Middleware check untuk memastikan admin tidak mengutak-atik site orang lain
    private function authorizeSiteAccess(Site $siteData)
    {

        $user = Auth::user();
        if ($user->role !== 'superadmin' && $user->site_id !== $siteData->id) {
            abort(403, 'Anda tidak memiliki akses ke site ini.');
        }
    }

    // public function index(string $slug)
    // {
    //     $siteData = $this->getSite($slug);

    //     // Hanya superadmin atau admin dari site ini yang bisa melihat halaman ini
    //     // Jika Anda ingin admin SBY bisa melihat stok SMG (Read Only), hapus baris di bawah ini
    //     $this->authorizeSiteAccess($siteData);

    //     $data = Sparepart::whereHas('stocks', function ($q) use ($siteData) {
    //         $q->where('site_id', $siteData->id);
    //     })
    //         ->with([
    //             'stocks.site',
    //             'histories.fromSite',
    //             'histories.toSite',
    //         ])
    //         ->withSum(['stocks as total_qty' => function ($q) use ($siteData) {
    //             $q->where('site_id', $siteData->id);
    //         }], 'qty')
    //         ->latest()
    //         ->paginate(10);

    //     $all_sites = Site::with('branch')->where('id', '!=', $siteData->id)->get();
    //     $sites = Site::all();

    //     return view('spareparts.index', compact('data', 'slug', 'siteData', 'all_sites', 'sites'));
    // }
    public function index(string $slug)
    {
        $siteData = $this->getSite($slug);

        // HAPUS ATAU KOMENTAR BARIS INI agar admin bisa buka site lain (Read Only)
        // $this->authorizeSiteAccess($siteData); 

        $data = Sparepart::whereHas('stocks', function ($q) use ($siteData) {
            $q->where('site_id', $siteData->id);
        })
            ->with(['stocks.site', 'histories.fromSite', 'histories.toSite'])
            ->withSum(['stocks as total_qty' => function ($q) use ($siteData) {
                $q->where('site_id', $siteData->id);
            }], 'qty')
            ->latest()
            ->paginate(10);

        $all_sites = Site::with('branch')->where('id', '!=', $siteData->id)->get();
        $sites = Site::all();

        return view('spareparts.index', compact('data', 'slug', 'siteData', 'all_sites', 'sites'));
    }

    public function store(Request $request, string $slug)
    {
        $siteData = $this->getSite($slug);

        // VALIDASI KEAMANAN: Cek apakah user berhak menambah barang di site ini
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'item_name' => 'required|string',
            'serial_number' => 'nullable|string',
            'type'      => 'required|string',
            'uom'       => 'required|string',
            'qty'       => 'required|integer|min:1',
            'condition' => 'required|in:new,used-good,damaged,repaired',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('spareparts', 'public');
        }

        $sparepart = Sparepart::create([
            'item_name' => $request->item_name,
            'serial_number' => $request->serial_number,
            'type'      => $request->type,
            'uom'       => $request->uom,
            'note'      => $request->note,
            'image'     => $imagePath,
        ]);

        SparepartStock::create([
            'sparepart_id' => $sparepart->id,
            'site_id'      => $siteData->id,
            'condition'    => $request->condition,
            'qty'          => $request->qty,
        ]);

        SparepartHistory::create([
            'sparepart_id' => $sparepart->id,
            'to_site_id'   => $siteData->id,
            'action'       => 'CREATE',
            'condition'    => $request->condition,
            'qty'          => $request->qty,
            'note'         => "Created by " . Auth::user()->name . " at " . $siteData->name,
        ]);

        return redirect()->route('sparepart.index', $slug)->with('success', 'Sparepart berhasil ditambahkan');
    }

    public function destroy(string $site, int $id)
    {
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        // Cari sparepart yang HANYA ada di site ini untuk dihapus stoknya
        // Catatan: Jika Sparepart ini global, sebaiknya hanya hapus stoknya, bukan model Sparepart-nya
        $sparepart = Sparepart::findOrFail($id);
        $sparepart->delete();

        return redirect()->route('sparepart.index', $site)->with('success', 'Sparepart dihapus');
    }

    public function bulkDelete(Request $request, string $site)
    {
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer'
        ]);

        Sparepart::whereIn('id', $request->ids)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus'
        ]);
    }
}
