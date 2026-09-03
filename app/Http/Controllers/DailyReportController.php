<?php

namespace App\Http\Controllers;

use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = DailyReport::with(['site', 'user', 'photos'])->latest('report_date');

        if ($user->role !== 'superadmin' && $user->site_id) {
            $query->where('site_id', $user->site_id);
        } elseif ($request->filled('site_id')) {
            $query->where('site_id', $request->site_id);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date')) {
            $query->whereDate('report_date', $request->date);
        }

        $reports = $query->paginate(10)->withQueryString();
        $sites = Site::all();

        return view('daily_reports.index', compact('reports', 'sites'));
    }

    public function create()
    {
        $user = auth()->user();
        $sites = ($user->role === 'superadmin') ? Site::all() : Site::where('id', $user->site_id)->get();

        return view('daily_reports.create', compact('sites'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'site_id'     => 'required|exists:sites,id',
            'report_date' => 'required|date',
            'description' => 'required|string',
            'photos.*'    => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'captions.*'  => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $report = DailyReport::create([
                'site_id'     => $request->site_id,
                'user_id'     => auth()->id(),
                'report_date' => $request->report_date,
                'description' => $request->description,
            ]);

            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $index => $photoFile) {
                    $path = $photoFile->store('daily_reports', 'public');
                    $caption = $request->captions[$index] ?? null;

                    DailyReportPhoto::create([
                        'daily_report_id' => $report->id,
                        'photo_path'      => $path,
                        'caption'         => $caption,
                    ]);
                }
            }

            DB::commit();
            return redirect()->route('daily_reports.index')->with('success', 'Catatan laporan harian berhasil disimpan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan laporan: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $report = DailyReport::with('photos')->findOrFail($id);

        foreach ($report->photos as $photo) {
            Storage::disk('public')->delete($photo->photo_path);
        }

        $report->delete();
        return redirect()->route('daily_reports.index')->with('success', 'Laporan berhasil dihapus.');
    }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'site_id'    => 'nullable',
        ]);

        $user = auth()->user();
        $query = DailyReport::with(['site', 'user', 'photos'])
            ->whereBetween('report_date', [$request->start_date, $request->end_date])
            ->orderBy('report_date', 'asc');

        // Pengecekan Hak Akses Eksklusif Superadmin
        if ($user->role === 'superadmin') {
            // Jika Superadmin memilih 'all' atau mengosongkan site_id, tampilkan semua site
            if ($request->filled('site_id') && $request->site_id !== 'all') {
                $query->where('site_id', $request->site_id);
                $site = Site::find($request->site_id);
            } else {
                $site = null; // null menandakan "All Sites"
            }
        } else {
            // Pengguna Non-Superadmin dipaksa HANYA melihat site mereka sendiri
            $userSiteId = $user->site_id;
            $query->where('site_id', $userSiteId);
            $site = Site::find($userSiteId);
        }

        $reports = $query->get();
        $startDate = $request->start_date;
        $endDate = $request->end_date;

        return view('daily_reports.export_pdf', compact('reports', 'startDate', 'endDate', 'site'));
    }
}
