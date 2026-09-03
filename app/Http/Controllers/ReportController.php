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
    // Mengambil data report beserta antrean kegagalan (Failure Queue)
    public function index()
    {
        $report = Report::latest()->paginate(10);

        // Ambil sparepart berstatus 'damaged' yang qty-nya > 0 untuk antrean
        $failureQueue = SparepartStock::where('condition', 'damaged')
            ->where('qty', '>', 0)
            ->with(['sparepart', 'site'])
            ->get();

        return view('report.index', compact('report', 'failureQueue'));
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
                $selectedSiteSlug = $selectedStock->site?->slug;
                $selectedSubsystem = $selectedStock->sparepart?->item_name;
            }
        }

        // PERBAIKAN: Hanya mengambil Site yang MEMILIKI data di tabel sparepart_stocks (qty > 0)
        $sites = Site::whereHas('sparepartStocks', function ($query) {
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

        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('report', 'public')
            : null;

        return \DB::transaction(function () use ($request, $failureNote, $imagePath) {

            Report::create([
                'attendant'    => $request->attendant,
                'site_machine' => $request->site_machine,
                'failure_date' => $request->failure_date,
                'failure_note' => $failureNote,
                'ts_procedure' => $request->ts_procedure,
                'image'        => $imagePath,
            ]);

            // Jika dipicu dari antrean damage, kurangi/hapus stok damage tersebut
            if ($request->filled('stock_id')) {
                $stock = SparepartStock::find($request->stock_id);
                if ($stock && $stock->condition === 'damaged') {
                    if ($stock->qty <= 1) {
                        $stock->delete();
                    } else {
                        $stock->decrement('qty', 1);
                    }
                }
            }

            return redirect()->route('report.index')->with('success', 'Report Successfully Created and Queue Updated');
        });
    }

    public function edit(string $id)
    {
        if (!Auth::check() || !in_array(Auth::user()->role, ['superadmin', 'team_leader'])) {
            return redirect()->route('report.index')
                ->with('error', 'No access');
        }

        $report = Report::findOrFail($id);
        $sites = Site::all();

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

        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $report = Report::findOrFail($id);

        $data = [
            'attendant'    => $request->attendant,
            'site_machine' => $request->site_machine,
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

        return redirect()->route('report.index')->with('success', 'Report Successfully Updated');
    }

    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            return redirect()->route('report.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $report = Report::findOrFail($id);

        if ($report->image) {
            Storage::disk('public')->delete($report->image);
        }

        $report->delete();

        return redirect()->route('report.index')
            ->with('success', 'Data Successfully Deleted.');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $data = Report::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('attendant', 'LIKE', "%{$query}%")
                        ->orWhere('site_machine', 'LIKE', "%{$query}%")
                        ->orWhere('failure_note', 'LIKE', "%{$query}%");
                });
            }

            $report = $data->latest()->paginate(10)->withQueryString();

            if ($request->ajax()) {
                $html = view('report.table', [
                    'data' => $report,
                    'routePrefix' => 'report',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('report.index', compact('report'));
        } catch (\Exception $e) {
            \Log::error('report search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('report.table', [
                    'data' => collect(),
                    'routePrefix' => 'report',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('report.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }

    public function exportAll(Request $request)
    {
        $searchTerm = $request->get('search');
        $fileName = 'Global_Inventory_Report_' . now()->format('Y-m-d_His') . '.xlsx';

        return Excel::download(new GlobalSparepartExport($searchTerm), $fileName);
    }
}
