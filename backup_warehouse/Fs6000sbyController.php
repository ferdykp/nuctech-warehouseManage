<?php

namespace App\Http\Controllers;

use App\Models\Fs6000sby;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\SparePart;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use App\Exports\Fs6000sbyExport;
use Maatwebsite\Excel\Facades\Excel;

class Fs6000sbyController extends Controller
{
    public function index()
    {
        $fs6000sbys = Fs6000sby::paginate(10);
        return view('fssby.index', compact('fs6000sbys'));
    }
    public function show(string $id)
    {
        $fs6000sby = Fs6000sby::findOrFail($id);
        return view('fssby.show', compact('fs6000sby'));
    }


    // public function create()
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'admin') {
    //         return redirect()->route('fssby.index')
    //             ->with('error', 'Anda tidak memiliki akses.');
    //     }
    //     $location = Location::all();

    //     return view('fssby.create', compact('location'));
    // }

    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $location = Location::where('machine_type', 'fs6000sby')->get();

        return view('fssby.create', compact('location'));
    }


    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
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

        // ✅ SIMPAN IMAGE
        $imagePath = $request->file('image')->store('fssby', 'public');

        Fs6000sby::create([
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location, // ikut nama kolom DB
            'note'        => $request->note,
            'image'       => $imagePath, // fssby/namafile.jpg
        ]);

        return redirect()->route('fssby.index')
            ->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $fs6000sby = Fs6000sby::findOrFail($id);
        $locations = Location::all(); // ✅ ambil semua lokasi

        return view('fssby.edit', compact('fs6000sby', 'locations'));
    }


    public function update(Request $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
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

        $fs6000sby = Fs6000sby::findOrFail($id);

        $data = [
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location,
            'note'        => $request->note,
        ];

        // ✅ kalau upload image baru
        if ($request->hasFile('image')) {

            // hapus image lama
            if ($fs6000sby->image) {
                Storage::disk('public')->delete($fs6000sby->image);
            }

            $data['image'] = $request->file('image')->store('fssby', 'public');
        }

        $fs6000sby->update($data);

        return redirect()->route('fssby.index')
            ->with('success', 'Data berhasil diperbarui.');
    }


    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        Fs6000sby::findOrFail($id)->delete();

        return redirect()->route('fssby.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     $data = Fs6000sby::query();

    //     if ($query) {
    //         $data->where(function ($q) use ($query) {
    //             foreach (Schema::getColumnListing('fs6000sbys') as $column) {
    //                 $q->orWhere($column, 'LIKE', "%{$query}%");
    //             }
    //         });
    //     }

    //     $fs6000sbys = $data->paginate(10);

    //     if ($request->ajax()) {
    //         $html = view('fssby.table', compact('fs6000sbys'))->render();
    //         return response()->json(['html' => $html]);
    //     }

    //     return view('fssby.index', compact('fs6000sbys'));
    // }


    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            $data = Fs6000sby::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('item_name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('stock', 'LIKE', "%{$query}%");
                });
            }

            // $fs6000sbys = $data->paginate(10);
            $fs6000sbys = $data->paginate(10)->withQueryString();


            if ($request->ajax()) {
                $html = view('fssby.table', [
                    'data' => $fs6000sbys,
                    'routePrefix' => 'fssby',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('fssby.index', compact('fs6000sbys'));
        } catch (\Exception $e) {

            \Log::error('fssby search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('fssby.table', [
                    'data' => collect(),
                    'routePrefix' => 'fssby',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('fssby.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }

    public function exportExcel()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssby.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        return Excel::download(
            new Fs6000sbyExport,
            'FS6000_Surabaya_' . now()->format('Ymd_His') . '.xlsx'
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

        Fs6000sby::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
