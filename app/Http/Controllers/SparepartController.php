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
use App\Imports\SparepartImport;
use App\Models\Category;

class SparepartController extends Controller
{
    private function getSite(string $slug): Site
    {
        return Site::where('slug', $slug)->firstOrFail();
    }

    public function index(Request $request, string $slug)
    {
        $siteData = $this->getSite($slug);
        
        // --- FITUR FILTER & SEARCH ---
        $search = $request->input('search');
        $condition = $request->input('condition');

        $data = Sparepart::whereHas('stocks', function ($q) use ($siteData, $condition) {
            $q->where('site_id', $siteData->id);
            if ($condition) {
                $q->where('condition', $condition);
            }
        })
        ->when($search, function ($q) use ($search) {
            $q->where(function ($sub) use ($search) {
                $sub->where('item_name', 'like', "%{$search}%")
                  ->orWhere('serial_number', 'like', "%{$search}%")
                  ->orWhere('type', 'like', "%{$search}%");
            });
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
        ->paginate(10)
        ->withQueryString();

        $all_sites = Site::with('branch')->where('id', '!=', $siteData->id)->get();
        $sites = Site::all();
        $categories = Category::all();

        // Support AJAX untuk Search Real-time
        if ($request->ajax()) {
            return response()->json([
                'html' => view('spareparts.table', [
                    'assets' => $data,
                    'slug' => $slug,
                    'siteData' => $siteData,
                    'all_sites' => $all_sites,
                    'sites' => $sites,
                    'categories' => $categories
                ])->render()
            ]);
        }

        return view('spareparts.index', compact('data', 'slug', 'siteData', 'all_sites', 'sites', 'categories'));
    }

    public function store(Request $request, string $slug)
    {
        $request->validate([
            'item_name'     => 'required|string',
            'serial_number' => 'nullable|string|unique:spareparts,serial_number',
            'category_id'   => 'nullable|exists:categories,id',
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
                'category_id'   => $request->category_id,
                'type'          => $request->type,
                'uom'           => $request->uom,
                'note'          => $request->note,
                'image'         => $imagePath,
                'source_data'   => 'Manual Input',
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
            'category_id'   => 'nullable|exists:categories,id',
            'type'          => 'required|string',
            'uom'           => 'required|string',
        ]);

        $sparepart = Sparepart::findOrFail($id);

        if ($request->hasFile('image')) {
            if ($sparepart->image) Storage::disk('public')->delete($sparepart->image);
            $sparepart->image = $request->file('image')->store('spareparts', 'public');
        }

        $sparepart->update($request->only('item_name', 'serial_number', 'category_id', 'type', 'uom', 'note'));

        return redirect()->route('sparepart.index', $site)->with('success', 'Data sparepart diperbarui');
    }

    // --- FITUR IMPORT EXCEL ---
    public function importExcel(Request $request, $slug)
    {
        try {
            $request->validate([
                'file' => 'required|mimes:xlsx,xls,csv|max:10240'
            ]);
        } catch (ValidationException $e) {
            if ($request->ajax() || $request->wantsJson()) {
                $errors = $e->errors();
                $msg = collect($errors)->flatten()->first() ?? 'File tidak valid.';
                return response()->json(['success' => false, 'message' => $msg], 422);
            }
            throw $e;
        }

        $siteData = $this->getSite($slug);
        $filename = $request->file('file')->getClientOriginalName();

        try {
            // Baca daftar sheet dari file Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
            $sheetNames = $spreadsheet->getSheetNames();
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);

            $import = new SparepartImport($siteData->id, $filename, $sheetNames);
            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();

            // Bangun pesan detail
            $msg = "Import berhasil! {$summary['total_imported']} data diimport, {$summary['total_skipped']} baris di-skip.";
            if (!empty($summary['sheets'])) {
                $sheetInfo = [];
                foreach ($summary['sheets'] as $sheet => $detail) {
                    $sheetInfo[] = "{$sheet}: {$detail['imported']} imported";
                }
                $msg .= ' (' . implode(', ', $sheetInfo) . ')';
            }

            if (!empty($summary['errors'])) {
                $errorCount = count($summary['errors']);
                $msg .= " — {$errorCount} error(s) terjadi.";
            }

            // Return JSON untuk AJAX, redirect untuk normal request
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $msg]);
            }

            return redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Gagal import: ' . $e->getMessage()], 500);
            }

            return redirect()->back()->with('error', 'Gagal import: ' . $e->getMessage());
        }
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