<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SparepartExport;

class SparepartController extends Controller
{
    private function getSite(string $slug): Site
    {
        return Site::where('slug', $slug)->firstOrFail();
    }

    public function index(string $slug)
    {
        $siteData = $this->getSite($slug);
        
        $data = Sparepart::whereHas('stocks', function ($q) use ($siteData) {
            $q->where('site_id', $siteData->id);
        })
        ->with([
            'stocks.site',
            'histories.fromSite',
            'histories.toSite',
        ])
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
        $request->validate([
            'item_name'     => 'required|string',
            'serial_number' => 'nullable|string|unique:spareparts,serial_number',
            'type'          => 'required|string',
            'uom'           => 'required|string',
            'qty'           => 'required|integer|min:1',
            'condition'     => 'required|in:new,used-good,damaged,repair',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $siteData = $this->getSite($slug);

        return DB::transaction(function () use ($request, $siteData, $slug) {
            $imagePath = $request->hasFile('image') 
                ? $request->file('image')->store('spareparts', 'public') 
                : null;

            $sparepart = Sparepart::create([
                'item_name'     => $request->item_name,
                'serial_number' => $request->serial_number,
                'type'          => $request->type,
                'uom'           => $request->uom,
                'note'          => $request->note,
                'image'         => $imagePath,
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
                'note'         => $request->note,
            ]);

            return redirect()->route('sparepart.index', $slug)->with('success', 'Sparepart berhasil ditambahkan');
        });
    }

    public function update(Request $request, string $site, int $id)
    {
        $request->validate([
            'item_name'     => 'required|string',
            'serial_number' => 'nullable|string|unique:spareparts,serial_number,'.$id,
            'type'          => 'required|string',
            'uom'           => 'required|string',
        ]);

        $sparepart = Sparepart::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($sparepart->image) Storage::disk('public')->delete($sparepart->image);
            $sparepart->image = $request->file('image')->store('spareparts', 'public');
        }

        $sparepart->update($request->only('item_name', 'serial_number', 'type', 'uom', 'note'));

        return redirect()->route('sparepart.index', $site)->with('success', 'Data sparepart diperbarui');
    }

    public function exportExcel(string $site)
    {
        return Excel::download(
            new SparepartExport($site),
            strtoupper($site) . '_SPAREPART_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function destroy(string $site, int $id)
    {
        $sparepart = Sparepart::findOrFail($id);
        if ($sparepart->image) Storage::disk('public')->delete($sparepart->image);
        $sparepart->delete();

        return redirect()->route('sparepart.index', $site)->with('success', 'Sparepart dihapus');
    }
}