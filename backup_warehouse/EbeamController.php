<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ebeam;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use App\Exports\EbeamExport;


use function Symfony\Component\Clock\now;

class EbeamController extends Controller
{
    public function index()
    {
        $ebeams = ebeam::paginate(10);
        return view('ebeam.index', compact('ebeams'));
    }

    public function show(string $id)
    {
        $ebeams = ebeam::findOrFail($id);
        return view('ebeam.show', compact('ebeams'));
    }

    // public function create()
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'admin') {
    //         return redirect()->route('ebeam.index')
    //             ->with('error', 'Tidak memiliki akses');
    //     }
    //     $location = Location::all();

    //     return view('ebeam.create', compact('location'));
    // }
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $location = Location::where('machine_type', 'ebeam')->get();

        return view('ebeam.create', compact('location'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $request->validate([
            'item_name'   => 'required|string',
            'type'        => 'required|string',
            'stock'       => 'required|integer',
            'uom'        => 'required|string',
            'date_update' => 'required|date',
            'location'    => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',

        ]);

        $imagePath = $request->file('image')->store('ebeam', 'public');

        ebeam::create([
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location,
            'note'        => $request->note,
            'image'       => $imagePath,

        ]);

        return redirect()->route('ebeam.index')
            ->with('success', 'Data Berhasil Ditambahkan');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $ebeam = ebeam::findOrFail($id);
        $locations = Location::all();

        return view('ebeam.edit', compact('ebeam', 'locations'));
    }

    public function update(Request $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }

        $request->validate([
            'item_name'   => 'required|string',
            'type'        => 'required|string',
            'stock'       => 'required|integer',
            'uom'        => 'required|string',
            'date_update' => 'required|date',
            'location'    => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $ebeams = ebeam::findOrFail($id);

        $data = [
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location,
            'note'        => $request->note,
        ];

        if ($request->hasFile('image')) {
            if ($ebeams->image) {
                Storage::disk('public')->delete($ebeams->image);
            }
            $data['image'] = $request->file('image')->store('ebeam', 'public');
        }

        $ebeams->update($data);

        return redirect()->route('ebeam.index')
            ->with('success', 'Data Berhasil Diperbarui');
    }

    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }

        ebeam::findOrFail($id)->delete();

        return redirect()->route('ebeam.index')
            ->with('success', 'Data Berhasil Dihapus');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('query');
            $data = ebeam::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('item_name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('stock', 'LIKE', "%{$query}%");
                });
            }

            $ebeams = $data->paginate(10)->withQueryString();
            if ($request->ajax()) {
                $html = view('ebeam.table', [
                    'data' => $ebeams,
                    'routePrefix' => 'ebeam',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('ebeam.index', compact('ebeams'));
        } catch (\Exception $e) {

            \Log::error('Ebeam search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('ebeam.table', [
                    'data' => collect(),
                    'routePrefix' => 'ebeam',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('ebeam.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }

    public function exportExcel()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('ebeam.index')
                ->with('error', 'Tidak memiliki akses');
        }

        return Excel::download(
            new EbeamExport,
            'Ebeam_Jakarta_' . now()->format('Ymd_His') . '.xlsx'

        );
    }

    public function bulkDelete(Request $request)
    {
        $ids = $request->input('ids', []);

        if (empty($ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dipilih.'
            ]);
        }

        ebeam::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
