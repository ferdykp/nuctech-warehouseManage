<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DailyReport;
use App\Models\DailyReportPhoto;
use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Barryvdh\DomPDF\Facade\Pdf;

class DailyReportController extends Controller
{
    // public function index(Request $request)
    // {
    //     $user = auth()->user();
    //     $query = DailyReport::with(['site.branch', 'user', 'photos'])->latest('report_date');

    //     // Filter Hak Akses User biasa vs Admin
    //     if (!in_array($user->role, ['superadmin', 'administration']) && $user->site_id) {
    //         $query->where('site_id', $user->site_id);
    //     } else {
    //         // Filter Branch (Khusus Superadmin/Admin)
    //         if ($request->filled('branch_id')) {
    //             $query->whereHas('site', function ($q) use ($request) {
    //                 $q->where('branch_id', $request->branch_id);
    //             });
    //         }

    //         // Filter Site Spesifik
    //         if ($request->filled('site_id')) {
    //             $query->where('site_id', $request->site_id);
    //         }
    //     }

    //     // Filter Pencarian Teks Notes
    //     if ($request->filled('search')) {
    //         $query->where('description', 'like', '%' . $request->search . '%');
    //     }

    //     // Filter Rentang Tanggal (Start Date - End Date)
    //     if ($request->filled('start_date') && $request->filled('end_date')) {
    //         $query->whereBetween('report_date', [$request->start_date, $request->end_date]);
    //     } elseif ($request->filled('start_date')) {
    //         $query->whereDate('report_date', '>=', $request->start_date);
    //     } elseif ($request->filled('end_date')) {
    //         $query->whereDate('report_date', '<=', $request->end_date);
    //     }

    //     $reports = $query->paginate(10)->withQueryString();
    //     $branches = Branch::all();
    //     $sites = Site::with('branch')->get();

    //     return view('daily_reports.index', compact('reports', 'sites', 'branches'));
    // }
    public function index(Request $request)
    {
        $user = auth()->user();

        // UBAH DARI latest('report_date') MENJADI orderBy('site_id', 'asc')
        $query = DailyReport::with(['site.branch', 'user', 'photos'])
            ->orderBy('site_id', 'asc')       // Mengurutkan berdasarkan ID Site (terkecil ke terbesar)
            ->orderBy('report_date', 'desc'); // (Opsional) Mengurutkan tanggal terbaru untuk site yang sama

        // Filter Hak Akses User biasa vs Admin
        if (!in_array($user->role, ['superadmin', 'administration']) && $user->site_id) {
            $query->where('site_id', $user->site_id);
        } else {
            // Filter Branch (Khusus Superadmin/Admin)
            if ($request->filled('branch_id')) {
                $query->whereHas('site', function ($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            // Filter Site Spesifik
            if ($request->filled('site_id')) {
                $query->where('site_id', $request->site_id);
            }
        }

        // Filter Pencarian Teks Notes
        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        // Filter Rentang Tanggal (Start Date - End Date)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('report_date', [$request->start_date, $request->end_date]);
        } elseif ($request->filled('start_date')) {
            $query->whereDate('report_date', '>=', $request->start_date);
        } elseif ($request->filled('end_date')) {
            $query->whereDate('report_date', '<=', $request->end_date);
        }

        $reports = $query->paginate(10)->withQueryString();
        $branches = Branch::all();
        $sites = Site::with('branch')->get();

        return view('daily_reports.index', compact('reports', 'sites', 'branches'));
    }
    public function create()
    {
        $user = auth()->user();
        $sites = in_array($user->role, ['superadmin', 'administration'])
            ? Site::with('branch')->get()
            : Site::where('id', $user->site_id)->get();

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

    // public function exportPdf(Request $request)
    // {
    //     $request->validate([
    //         'start_date' => 'required|date',
    //         'end_date'   => 'required|date|after_or_equal:start_date',
    //         'site_id'    => 'nullable',
    //         'branch_id'  => 'nullable',
    //     ]);

    //     $user = auth()->user();
    //     $query = DailyReport::with(['site.branch', 'user', 'photos'])
    //         ->whereBetween('report_date', [$request->start_date, $request->end_date])
    //         ->orderBy('site_id', 'asc')
    //         ->orderBy('report_date', 'asc');

    //     if (in_array($user->role, ['superadmin', 'administration'])) {
    //         if ($request->filled('branch_id') && $request->branch_id !== 'all') {
    //             $query->whereHas('site', function ($q) use ($request) {
    //                 $q->where('branch_id', $request->branch_id);
    //             });
    //         }

    //         if ($request->filled('site_id') && $request->site_id !== 'all') {
    //             $query->where('site_id', $request->site_id);
    //             $site = Site::find($request->site_id);
    //         } else {
    //             $site = null;
    //         }
    //     } else {
    //         $userSiteId = $user->site_id;
    //         $query->where('site_id', $userSiteId);
    //         $site = Site::find($userSiteId);
    //     }

    //     $reports = $query->get();

    //     // Kelompokkan laporan berdasarkan Site
    //     $groupedReports = $reports->groupBy('site_id');

    //     $startDate = $request->start_date;
    //     $endDate = $request->end_date;

    //     return view('daily_reports.export_pdf', compact('groupedReports', 'startDate', 'endDate', 'site'));
    // }

    public function exportPdf(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'site_id'    => 'nullable',
            'branch_id'  => 'nullable',
        ]);

        $user = auth()->user();

        $query = DailyReport::with(['site.branch', 'user', 'photos'])
            ->whereBetween('report_date', [
                $request->start_date,
                $request->end_date
            ])
            ->orderBy('site_id', 'asc')
            ->orderBy('report_date', 'asc');

        if (in_array($user->role, ['superadmin', 'administration'])) {
            if ($request->filled('branch_id') && $request->branch_id !== 'all') {
                $query->whereHas('site', function ($q) use ($request) {
                    $q->where('branch_id', $request->branch_id);
                });
            }

            if ($request->filled('site_id') && $request->site_id !== 'all') {
                $query->where('site_id', $request->site_id);
                $site = Site::find($request->site_id);
            } else {
                $site = null;
            }
        } else {
            $userSiteId = $user->site_id;
            $query->where('site_id', $userSiteId);
            $site = Site::find($userSiteId);
        }

        $reports = $query->get();
        $groupedReports = $reports->groupBy('site_id');

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        /*
        |--------------------------------------------------------------------------
        | Generate PDF Menggunakan DomPDF
        |--------------------------------------------------------------------------
        */
        $pdf = Pdf::loadView('daily_reports.export_pdf', compact(
            'groupedReports',
            'startDate',
            'endDate',
            'site'
        ));

        $pdf->setPaper('a4', 'portrait');
        $pdf->setOption(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);

        $fileName = 'Daily-Activity-Report-' . $startDate . '-to-' . $endDate . '.pdf';

        return $pdf->download($fileName);
    }

    public function edit($id)
    {
        $user = auth()->user();
        $report = DailyReport::with(['site.branch', 'photos'])->findOrFail($id);

        if (!in_array($user->role, ['superadmin', 'administration']) && $user->site_id !== $report->site_id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $sites = in_array($user->role, ['superadmin', 'administration'])
            ? Site::with('branch')->get()
            : Site::where('id', $user->site_id)->get();

        return view('daily_reports.edit', compact('report', 'sites'));
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $report = DailyReport::findOrFail($id);

        if (!in_array($user->role, ['superadmin', 'administration']) && $user->site_id !== $report->site_id) {
            abort(403, 'Anda tidak memiliki akses untuk mengubah laporan ini.');
        }

        $request->validate([
            'site_id'            => 'required|exists:sites,id',
            'report_date'        => 'required|date',
            'description'        => 'required|string',
            'photos.*'           => 'nullable|image|mimes:jpeg,png,jpg,webp|max:5120',
            'captions.*'         => 'nullable|string|max:255',
            'existing_captions.*' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $report->update([
                'site_id'     => $request->site_id,
                'report_date' => $request->report_date,
                'description' => $request->description,
            ]);

            // Update caption foto lama
            if ($request->has('existing_captions')) {
                foreach ($request->existing_captions as $photoId => $caption) {
                    DailyReportPhoto::where('id', $photoId)
                        ->where('daily_report_id', $report->id)
                        ->update(['caption' => $caption]);
                }
            }

            // Upload foto baru jika ada
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
            return redirect()->route('daily_reports.index')->with('success', 'Catatan laporan harian berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui laporan: ' . $e->getMessage());
        }
    }

    public function destroyPhoto($id)
    {
        $photo = DailyReportPhoto::findOrFail($id);
        $user = auth()->user();

        if (!in_array($user->role, ['superadmin', 'administration']) && $user->site_id !== $photo->dailyReport->site_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        Storage::disk('public')->delete($photo->photo_path);
        $photo->delete();

        return response()->json(['message' => 'Foto berhasil dihapus.']);
    }
}
