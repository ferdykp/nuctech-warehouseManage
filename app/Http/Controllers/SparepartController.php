<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Category;
use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use App\Models\SparepartTransfer; // <--- PASTIKAN MODEL INI DI-IMPORT
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SparepartExport;
use App\Imports\SparepartImport;

class SparepartController extends Controller
{
    private function getSite(string $slug): Site
    {
        return Site::where('slug', $slug)->firstOrFail();
    }

    private function authorizeSiteAccess(Site $siteData)
    {
        $user = Auth::user();
        if ($user->role !== 'superadmin' && (int) $user->site_id !== (int) $siteData->id) {
            abort(403, 'Anda tidak memiliki akses ke site ini.');
        }
    }

    public function index(Request $request, string $slug)
    {
        $siteData = $this->getSite($slug);
        $search = $request->input('search');
        $condition = $request->input('condition');

        // Setup Query Utama Ketersediaan Stok
        $query = SparepartStock::with([
            'sparepart.category',
            'sparepart.stocks.site',
            'sparepart.histories.fromSite',
            'sparepart.histories.toSite',
            'site'
        ])->where('site_id', $siteData->id);

        if ($condition) {
            $query->where('condition', $condition);
        }

        if ($search) {
            $query->whereHas('sparepart', function ($q) use ($search) {
                $q->where('item_name', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%")
                    ->orWhere('type', 'like', "%{$search}%");
            });
        }

        $data = $query->latest()->paginate(10)->withQueryString();

        // 1. Ambil Permintaan KELUAR yang butuh di-APPROVE oleh site asal ini
        $pendingApprovals = SparepartTransfer::where('from_site_id', $siteData->id)
            ->where('status', 'pending')
            ->with(['sparepart', 'toSite'])
            ->get();

        // 2. Ambil Permintaan MASUK yang butuh di-CONFIRM RECEIPT oleh site tujuan ini
        $pendingReceipts = SparepartTransfer::where('to_site_id', $siteData->id)
            ->where('status', 'approved')
            ->with(['sparepart', 'fromSite'])
            ->get();

        // Response AJAX Live Search
        if ($request->ajax()) {
            return view('spareparts.table', [
                'assets' => $data,
                'siteData' => $siteData,
                'slug' => $slug,
                'all_sites' => Site::with('branch')->where('id', '!=', $siteData->id)->get()
            ])->render();
        }

        $all_sites = Site::with('branch')->where('id', '!=', $siteData->id)->get();
        $sites = Site::all();
        $categories = Category::all();

        return view('spareparts.index', [
            'data'             => $data,
            'assets'           => $data,
            'slug'             => $slug,
            'siteData'         => $siteData,
            'all_sites'        => $all_sites,
            'sites'            => $sites,
            'categories'       => $categories,
            'pendingApprovals' => $pendingApprovals, // <--- DIKIRIM KE VIEW
            'pendingReceipts'  => $pendingReceipts,  // <--- DIKIRIM KE VIEW
        ]);
    }


    /**
     * Menyimpan data sparepart baru.
     */
    public function store(Request $request, string $slug)
    {
        $siteData = $this->getSite($slug);
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'item_name'     => 'required|string',
            'serial_number' => 'nullable|string|unique:spareparts,serial_number',
            'category_id'   => 'nullable|exists:categories,id',
            'type'          => 'required|string',
            'uom'           => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'qty'           => 'required|integer|min:1',
            'condition' => 'required|in:new,used-good,damaged,repair',
            'note'          => 'nullable|string', // Tambahkan ini agar aman
        ]);

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
                'note'         => "Created by " . Auth::user()->name,
            ]);

            return redirect()->route('sparepart.index', $slug)->with('success', 'Sparepart berhasil ditambahkan');
        });
    }

    /**
     * Update data sparepart.
     */
    public function update(Request $request, string $site, int $id)
    {
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'item_name'     => 'required|string',
            'serial_number' => 'nullable|string|unique:spareparts,serial_number,' . $id,
            'category_id'   => 'nullable|exists:categories,id',
            'type'          => 'required|string',
            'uom'           => 'required|string',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
        ]);

        $sparepart = Sparepart::findOrFail($id);
        abort_unless(auth()->user()->isSuperAdmin() || $sparepart->stocks()->where('site_id', $siteData->id)->exists(), 403);

        if ($request->hasFile('image')) {
            if ($sparepart->image) Storage::disk('public')->delete($sparepart->image);
            $sparepart->image = $request->file('image')->store('spareparts', 'public');
        }

        $sparepart->update($request->only('item_name', 'serial_number', 'category_id', 'type', 'uom', 'note'));

        return redirect()->route('sparepart.index', $site)->with('success', 'Data sparepart diperbarui');
    }

    /**
     * Menghapus sparepart.
     */
    public function destroy(string $site, int $id)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        $sparepart = Sparepart::findOrFail($id);
        abort_unless(auth()->user()->isSuperAdmin() || $sparepart->stocks()->where('site_id', $siteData->id)->exists(), 403);
        if ($sparepart->image) Storage::disk('public')->delete($sparepart->image);
        $sparepart->delete();

        return redirect()->route('sparepart.index', $site)->with('success', 'Sparepart dihapus');
    }

    public function destroyStock(string $site, int $stockId)
    {
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        $stock = SparepartStock::where('site_id', $siteData->id)->findOrFail($stockId);

        // Opsional: Buat history bahwa stok ini dihapus manual
        SparepartHistory::create([
            'sparepart_id' => $stock->sparepart_id,
            'to_site_id'   => $siteData->id,
            'action'       => 'ADJUSTMENT',
            'qty'          => $stock->qty,
            'condition'    => $stock->condition,
            'note'         => "Stock baris ini dihapus oleh " . Auth::user()->name,
        ]);

        $stock->delete();

        return redirect()->route('sparepart.index', $site)->with('success', 'Baris stok berhasil dihapus');
    }

    /**
     * Bulk Delete.
     */
    public function bulkDelete(Request $request, string $site)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        $siteData = $this->getSite($site);
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:spareparts,id'
        ]);

        $spareparts = Sparepart::whereIn('id', $request->ids)->get();
        foreach ($spareparts as $sp) {
            if ($sp->image) Storage::disk('public')->delete($sp->image);
            $sp->delete();
        }

        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    /**
     * Import Excel.
     */
    public function importExcel(Request $request, $slug)
    {
        $siteData = $this->getSite($slug);
        $this->authorizeSiteAccess($siteData);

        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $filename = $request->file('file')->getClientOriginalName();
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($request->file('file')->getPathname());
            $sheetNames = $spreadsheet->getSheetNames();
            $spreadsheet->disconnectWorksheets();

            $import = new SparepartImport($siteData->id, $filename, $sheetNames);
            Excel::import($import, $request->file('file'));

            $summary = $import->getSummary();
            $msg = "Import berhasil! {$summary['total_imported']} data diimport.";

            return $request->ajax()
                ? response()->json(['success' => true, 'message' => $msg])
                : redirect()->back()->with('success', $msg);
        } catch (\Exception $e) {
            return $request->ajax()
                ? response()->json(['success' => false, 'message' => 'Gagal: ' . $e->getMessage()], 500)
                : redirect()->back()->with('error', 'Gagal: ' . $e->getMessage());
        }
    }

    /**
     * Export Excel.
     */
    public function exportExcel(string $site)
    {
        return Excel::download(
            new SparepartExport($site),
            strtoupper($site) . '_SPAREPART_' . now()->format('Ymd_His') . '.xlsx'
        );
    }

    public function allSpareparts(Request $request)
    {
        $search = $request->query('search');

        $allStocks = SparepartStock::with(['sparepart', 'site.branch'])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($root) use ($search) {
                    $root->whereHas('sparepart', function ($q) use ($search) {
                        $q->where('item_name', 'LIKE', "%{$search}%")
                            ->orWhere('serial_number', 'LIKE', "%{$search}%");
                    })
                        ->orWhereHas('site', function ($q) use ($search) {
                            $q->where('machine_name', 'LIKE', "%{$search}%");
                        });
                });
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10)
            ->withQueryString();

        if ($request->ajax()) {
            // Langsung return string HTML dari view partial
            return view('spareparts.all_table', compact('allStocks'))->render();
        }

        return view('spareparts.all', compact('allStocks'));
    }

    public function adjust(Request $request, $slug, $id)
    {
        $site = $this->getSite($slug);
        $this->authorizeSiteAccess($site);
        $data = $request->validate([
            'qty_to_move' => 'required|integer|min:1',
            'new_condition' => 'required|in:new,used-good,damaged,repair',
        ]);
        DB::transaction(function () use ($site, $id, $data) {
            $reference = SparepartStock::where('site_id', $site->id)->findOrFail($id);
            Sparepart::whereKey($reference->sparepart_id)->lockForUpdate()->firstOrFail();
            $source = SparepartStock::where('site_id', $site->id)->lockForUpdate()->findOrFail($id);
            $qty = (int) $data['qty_to_move'];
            $oldCondition = $source->condition;
            if ($oldCondition === $data['new_condition']) {
                $note = "Koreksi stok dari {$source->qty} menjadi {$qty}";
                $source->update(['qty' => $qty]);
                $action = 'ADJUSTMENT';
            } else {
                if ($source->qty < $qty) {
                    throw \Illuminate\Validation\ValidationException::withMessages(['qty_to_move' => 'Jumlah melebihi stok tersedia.']);
                }
                $target = SparepartStock::firstOrCreate([
                    'sparepart_id' => $source->sparepart_id, 'site_id' => $site->id, 'condition' => $data['new_condition'],
                ], ['qty' => 0]);
                $source->decrement('qty', $qty);
                $target->increment('qty', $qty);
                if ($source->qty === 0) $source->delete();
                $note = "Mengubah {$qty} unit dari {$oldCondition} ke {$data['new_condition']}";
                $action = 'CONDITION_CHANGE';
            }
            SparepartHistory::create([
                'sparepart_id' => $source->sparepart_id, 'from_site_id' => $site->id, 'to_site_id' => $site->id,
                'action' => $action, 'condition' => $data['new_condition'], 'qty' => $qty, 'note' => $note,
            ]);
        }, 3);
        return back()->with('success', 'Stok berhasil diperbarui.');
    }
}
