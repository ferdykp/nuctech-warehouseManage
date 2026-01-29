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
    /* =====================
        HELPER
    ====================== */
    private function getSite(string $code): Site
    {
        return Site::where('code', $code)->firstOrFail();
    }

    private function viewPrefix(string $site): string
    {
        return match ($site) {
            'ebeam' => 'ebeam',
            'fsjkt' => 'fsjkt',
            'fssby' => 'fssby',
            'fssmg' => 'fssmg',
            'ctmic' => 'ctmic',
        };
    }

    /* =====================
        INDEX
    ====================== */
    public function index(string $site)
    {
        $siteData = $this->getSite($site);

        $data = Sparepart::whereHas('stocks', function ($q) use ($siteData) {
            $q->where('site_id', $siteData->id);
        })
            ->with([
                // stock hanya untuk site aktif
                'stocks' => function ($q) use ($siteData) {
                    $q->where('site_id', $siteData->id)->with('site');
                },

                // history lengkap (untuk modal)
                'histories.fromSite',
                'histories.toSite',
            ])
            ->withSum([
                'stocks as total_qty' => function ($q) use ($siteData) {
                    $q->where('site_id', $siteData->id);
                }
            ], 'qty')
            ->latest()
            ->paginate(10);

        $sites = Site::all();

        return view(
            $this->viewPrefix($site) . '.index',
            compact('data', 'site', 'siteData', 'sites')
        );
    }


    /* =====================
        CREATE
    ====================== */
    public function create(string $site)
    {
        abort_if(!Auth::check() || Auth::user()->role !== 'admin', 403);

        $siteData = $this->getSite($site);

        return view(
            $this->viewPrefix($site) . '.create',
            compact('site', 'siteData')
        );
    }

    /* =====================
        STORE
    ====================== */
    public function store(Request $request, string $site)
    {
        $request->validate([
            'item_name' => 'required|string',
            'type'      => 'required|string',
            'uom'       => 'required|string',
            'qty'       => 'required|integer|min:1',
            'condition' => 'required|in:new,used-good,damaged,repaired',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048', // ✅

        ]);

        $siteData = $this->getSite($site);
        $imagePath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('spareparts', 'public');
        }


        $sparepart = Sparepart::create([
            'item_name' => $request->item_name,
            'type'      => $request->type,
            'uom'       => $request->uom,
            'note'      => $request->note,
            'image'     => $imagePath, // ✅

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

        return redirect("/$site")->with('success', 'Sparepart berhasil ditambahkan');
    }

    /* =====================
        EDIT
    ====================== */
    public function edit(string $site, int $id)
    {
        $siteData = $this->getSite($site);

        $data = Sparepart::whereHas('stocks', function ($q) use ($siteData) {
            $q->where('site_id', $siteData->id);
        })->findOrFail($id);

        return view(
            $this->viewPrefix($site) . '.edit',
            compact('data', 'site', 'siteData')
        );
    }

    /* =====================
        UPDATE (MASTER DATA)
    ====================== */
    public function update(Request $request, string $site, int $id)
    {
        $request->validate([
            'item_name' => 'required|string',
            'type'      => 'required|string',
            'uom'       => 'required|string',
        ]);

        $sparepart = Sparepart::findOrFail($id);
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('spareparts', 'public');
            $sparepart->image = $imagePath;
        }


        $sparepart->update($request->only(
            'item_name',
            'type',
            'uom',
            'note'
        ));

        return redirect("/$site")->with('success', 'Data sparepart diperbarui');
    }

    /* =====================
        DELETE (MASTER)
    ====================== */
    public function destroy(string $site, int $id)
    {
        Sparepart::findOrFail($id)->delete();

        return redirect("/$site")->with('success', 'Sparepart dihapus');
    }

    /* =====================
        SEARCH
    ====================== */
    public function search(Request $request, string $site)
    {
        $siteData = $this->getSite($site);
        $query = $request->input('search');

        $data = Sparepart::query()
            ->whereHas('stocks', fn($q) => $q->where('site_id', $siteData->id)) // pastikan punya stok di site

            ->when($query, function ($q) use ($query, $siteData) {
                $q->where(function ($sub) use ($query, $siteData) {

                    // 🔹 Search di Sparepart
                    $sub->where('item_name', 'like', "%{$query}%")
                        ->orWhere('type', 'like', "%{$query}%")
                        ->orWhere('uom', 'like', "%{$query}%")

                        // 🔹 Search di Stock
                        ->orWhereHas('stocks', function ($q2) use ($query, $siteData) {
                            $q2->where('site_id', $siteData->id)
                                ->where(function ($q3) use ($query) {
                                    // Condition string
                                    $q3->where('condition', 'like', "%{$query}%")
                                        // Qty integer: bisa exact match
                                        ->orWhere('qty', $query);
                                });
                        });
                });
            })
            ->with([
                'stocks' => fn($q) => $q->where('site_id', $siteData->id)->with('site'),
                'histories.fromSite',
                'histories.toSite',
            ])
            ->withSum(['stocks as total_qty' => fn($q) => $q->where('site_id', $siteData->id)], 'qty')
            ->latest()
            ->paginate(10)
            ->withQueryString();


        $sites = Site::all();

        if ($request->ajax()) {
            return response()->json([
                'html' => view(
                    $this->viewPrefix($site) . '.table',
                    compact('data', 'site', 'siteData', 'sites')
                )->render()
            ]);
        }

        return view(
            $this->viewPrefix($site) . '.index',
            compact('data', 'site', 'siteData', 'sites')
        );
    }



    /* =====================
        EXPORT
    ====================== */
    public function exportExcel(string $site)
    {
        return Excel::download(
            new SparepartExport($site),
            strtoupper($site) . '_SPAREPART_' . now()->format('Ymd_His') . '.xlsx'
        );
    }
    public function bulkDelete(Request $request, string $site)
    {
        abort_if(!Auth::check() || Auth::user()->role !== 'admin', 403);

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
