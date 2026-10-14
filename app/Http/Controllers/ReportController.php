<?php

namespace App\Http\Controllers;

use App\Models\Report;
use App\Models\SparepartStock;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exports\GlobalSparepartExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
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
    // Mengambil data report beserta antrean kegagalan (Failure Queue)
    public function index(Request $request, ?string $slug = null)
    {
        // Jika diakses tanpa slug, arahkan ke site user atau site pertama
        if (!$slug) {
            $user = Auth::user();
            if ($user->role === 'team_leader' && $user->site) {
                return redirect()->route('report.index', $user->site->slug);
            }
            $siteData = Site::first();
            abort_unless($siteData, 404, 'Data site tidak ditemukan.');
            return redirect()->route('report.index', $siteData->slug);
        }

        $siteData = $this->getSite($slug);
        $this->authorizeSiteAccess($siteData);

        // 🟢 Filter laporan berdasarkan nama mesin/site yang cocok dengan siteData->machine_name
        $report = Report::where('site_id', $siteData->id)
            ->latest()
            ->paginate(10);

        // Ambil sparepart berstatus 'damaged' yang qty-nya > 0 untuk antrean di site ini
        $failureQueue = SparepartStock::where('site_id', $siteData->id)
            ->where('condition', 'damaged')
            ->where('qty', '>', 0)
            ->with(['sparepart', 'site'])
            ->get();

        $all_sites = Site::with('branch')->where('id', '!=', $siteData->id)->get();

        return view('report.index', compact('report', 'failureQueue', 'siteData', 'slug', 'all_sites'));
    }

    // Menerima parameter opsional stock_id jika diakses dari tombol "Process Log"
    public function create(Request $request)
    {
        $selectedStock = null;
        $selectedSiteSlug = null;
        $selectedSubsystem = null;

        if ($request->filled('stock_id')) {
            $selectedStock = SparepartStock::with(['sparepart', 'site'])->find($request->stock_id);
            if ($selectedStock) {
                \App\Services\SiteAccess::authorize($selectedStock->site_id);
                $selectedSiteSlug = $selectedStock->site?->slug;
                $selectedSubsystem = $selectedStock->sparepart?->item_name;
            }
        }

        // PERBAIKAN: Hanya mengambil Site yang MEMILIKI data di tabel sparepart_stocks (qty > 0)
        $sites = \App\Services\SiteAccess::sites(auth()->user())->whereHas('sparepartStocks', function ($query) {
            $query->where('qty', '>', 0);
        })->get();

        // Jika site dari antrean rusak belum masuk ke dalam list (misal stok damage 0), tetap masukkan site tersebut agar terpilih
        if ($selectedStock && $selectedStock->site && !$sites->contains('id', $selectedStock->site_id)) {
            $sites->push($selectedStock->site);
        }

        return view('report.create', compact('selectedStock', 'sites', 'selectedSiteSlug', 'selectedSubsystem'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['superadmin', 'team_leader'])) {
            return redirect()->route('report.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $request->validate([
            'attendant'          => 'required|string',
            'site_machine'       => 'required|string',
            'failure_date'       => 'required|date',
            'ts_procedure'       => 'required|string',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'failed_subsystem'   => 'required|string',
            'failure_phenomenon' => 'required|string',
            'stock_id'           => 'nullable|exists:sparepart_stocks,id',
        ]);

        $site = Site::where('slug', $request->site_machine)->firstOrFail();
        \App\Services\SiteAccess::authorize($site->id);
        if ($request->filled('stock_id')) {
            abort_unless(SparepartStock::whereKey($request->stock_id)->where('site_id', $site->id)->exists(), 403);
        }

        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('report', 'public')
            : null;

        return \DB::transaction(function () use ($request, $failureNote, $imagePath, $site) {

            Report::create([
                'attendant'    => $request->attendant,
                'site_id' => $site->id,
                'site_machine' => $site->machine_name,
                'failure_date' => $request->failure_date,
                'failure_note' => $failureNote,
                'ts_procedure' => $request->ts_procedure,
                'image'        => $imagePath,
            ]);

            // Jika dipicu dari antrean damage, kurangi/hapus stok damage tersebut
            if ($request->filled('stock_id')) {
                $stock = SparepartStock::lockForUpdate()->find($request->stock_id);
                if ($stock && $stock->condition === 'damaged') {
                    if ($stock->qty <= 1) {
                        $stock->delete();
                    } else {
                        $stock->decrement('qty', 1);
                    }
                }
            }

            return redirect()->route('report.index')->with('success', 'Laporan berhasil dibuat dan antrean diperbarui.');
        });
    }

    public function edit(string $id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['superadmin', 'team_leader'])) {
            return redirect()->route('report.index')
                ->with('error', 'No access');
        }

        $report = Report::findOrFail($id);
        if (!auth()->user()->isSuperAdmin()) {
            \App\Services\SiteAccess::authorize($report->site_id);
        }
        $sites = \App\Services\SiteAccess::sites(auth()->user())->get();

        return view('report.edit', compact('report', 'sites'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['superadmin', 'team_leader'])) {
            return redirect()->route('report.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $request->validate([
            'attendant'          => 'required|string',
            'site_machine'       => 'required|string',
            'failure_date'       => 'required|date',
            'ts_procedure'       => 'required|string',
            'image'              => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'failed_subsystem'   => 'required|string',
            'failure_phenomenon' => 'required|string',
        ]);

        $site = Site::where('slug', $request->site_machine)->firstOrFail();
        \App\Services\SiteAccess::authorize($site->id);
        if ($request->filled('stock_id')) {
            abort_unless(SparepartStock::whereKey($request->stock_id)->where('site_id', $site->id)->exists(), 403);
        }

        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $report = Report::findOrFail($id);
        if (!auth()->user()->isSuperAdmin()) {
            \App\Services\SiteAccess::authorize($report->site_id);
        }

        $data = [
            'attendant'    => $request->attendant,
            'site_id' => $site->id,
                'site_machine' => $site->machine_name,
            'failure_date' => $request->failure_date,
            'failure_note' => $failureNote,
            'ts_procedure' => $request->ts_procedure,
        ];

        if ($request->hasFile('image')) {
            if ($report->image) {
                Storage::disk('public')->delete($report->image);
            }
            $data['image'] = $request->file('image')->store('report', 'public');
        }

        $report->update($data);

        return redirect()->route('report.index')->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403);

        $report = Report::findOrFail($id);
        if (!auth()->user()->isSuperAdmin()) {
            \App\Services\SiteAccess::authorize($report->site_id);
        }

        if ($report->image) {
            Storage::disk('public')->delete($report->image);
        }

        $report->delete();

        return redirect()->route('report.index')
            ->with('success', 'Data Successfully Deleted.');
    }

    public function search(Request $request)
    {
        $data = Report::accessible();
        if ($request->filled('site_id')) {
            \App\Services\SiteAccess::authorize($request->site_id);
            $data->where('site_id', $request->site_id);
        }
        $search = $request->input('query', $request->input('search'));
        if ($search) {
            $data->where(fn ($q) => $q->where('attendant', 'like', "%{$search}%")
                ->orWhere('site_machine', 'like', "%{$search}%")
                ->orWhere('failure_note', 'like', "%{$search}%"));
        }
        if (!$request->ajax()) return redirect()->route('report.index');
        return response()->json(['html' => view('report.table', [
            'data' => $data->latest()->paginate(10)->withQueryString(), 'routePrefix' => 'report',
        ])->render()]);
    }

    public function export()
    {
        return Excel::download(new \App\Exports\ReportExport(), 'Failure_Reports_' . now()->format('Y-m-d') . '.xlsx');
    }

    public function exportAll(Request $request)
    {
        $searchTerm = $request->get('search');
        $fileName = 'Global_Inventory_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new GlobalSparepartExport($searchTerm), $fileName);
    }
}
