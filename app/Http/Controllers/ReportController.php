<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use App\Exports\GlobalSparepartExport;
use Maatwebsite\Excel\Facades\Excel;


class ReportController extends Controller
{
    public function index()
    {
        $report = Report::paginate(10);
        return view('report.index', compact('report'));
    }

    public function create()
    {
        return view('report.create');
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }
        $request->validate([
            'attendant' => 'string|required',
            'site_machine' => 'string|required',
            'series_machine' => 'string|required',
            'failure_date' => 'required',
            // 'failure_note' => 'required',
            'ts_procedure' => 'required',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'failed_subsystem'     => 'required|string',
            'failure_phenomenon'  => 'required|string',
        ]);

        // 🔗 Gabungkan failure note
        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $imagePath = $request->hasFile('image')
            ? $request->file('image')->store('report', 'public')
            : null;

        Report::create([
            'attendant' => $request->attendant,
            'site_machine' => $request->site_machine,
            'series_machine' => $request->series_machine,
            'failure_date' => $request->failure_date,
            // 'failure_note' => $request->failure_note,
            'failure_note' => $failureNote,
            'ts_procedure' => $request->ts_procedure,
            'image'     => $imagePath,
        ]);

        return redirect()->route('report.index')->with('success', 'Added');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            return redirect()->route('report.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $report = Report::findOrFail($id);
        return view('report.edit', compact('report'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            return redirect()->route('report.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $request->validate([
            'attendant' => 'string|required',
            'site_machine' => 'string|required',
            'series_machine' => 'string|required',
            'failure_date' => 'required',
            // 'failure_note' => 'required',
            'ts_procedure' => 'required',
            'image'     => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',

            'failed_subsystem'     => 'required|string',
            'failure_phenomenon'   => 'required|string',
        ]);

        $failureNote =
            "Failed Sub-System:\n" . $request->failed_subsystem .
            "\n\nFailure Phenomenon:\n" . $request->failure_phenomenon;

        $report = Report::findOrFail($id);

        $data = [
            'attendant' => $request->attendant,
            'site_machine' => $request->site_machine,
            'series_machine' => $request->series_machine,
            'failure_date' => $request->failure_date,
            // 'failure_note' => $request->failure_note,
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

        return redirect()->route('report.index');
    }

    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'superadmin') {
            return redirect()->route('report.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        Report::findOrFail($id)->delete();

        return redirect()->route('report.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            $data = Report::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('item_name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('stock', 'LIKE', "%{$query}%");
                });
            }

            // $report = $data->paginate(10);
            $report = $data->paginate(10)->withQueryString();


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
