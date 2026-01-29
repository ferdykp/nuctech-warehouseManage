<?php

namespace App\Http\Controllers;

use App\Models\Fs6000smg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\SparePart;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use App\Exports\Fs6000smgExport;
use Maatwebsite\Excel\Facades\Excel;

class Fs6000smgController extends Controller
{
    public function index()
    {
        $fs6000smgs = Fs6000smg::paginate(10);
        return view('fssmg.index', compact('fs6000smgs'));
    }
    public function show(string $id)
    {
        $fs6000smg = Fs6000smg::findOrFail($id);
        return view('fssmg.show', compact('fs6000smg'));
    }


    // public function create()
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'admin') {
    //         return redirect()->route('fssmg.index')
    //             ->with('error', 'Anda tidak memiliki akses.');
    //     }
    //     $location = Location::all();


    //     return view('fssmg.create', compact('location'));
    // }
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $location = Location::where('machine_type', 'fs6000smg')->get();

        return view('fssmg.create', compact('location'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $request->validate([
            'item_name'   => 'required|string',
            'type'        => 'required|string',
            'stock'       => 'required|integer',
            'date_update' => 'required|date',
            'location'    => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'required|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // ✅ SIMPAN IMAGE
        $imagePath = $request->file('image')->store('fssmg', 'public');

        Fs6000smg::create([
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location,
            'note'        => $request->note,
            'image'       => $imagePath,
        ]);

        return redirect()->route('fssmg.index')
            ->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $fs6000smg = Fs6000smg::findOrFail($id);
        $locations = Location::all(); // ✅ ambil semua lokasi

        return view('fssmg.edit', compact('fs6000smg', 'locations'));
    }


    public function update(Request $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $request->validate([
            'item_name'   => 'required|string',
            'type'        => 'required|string',
            'stock'       => 'required|integer',
            'date_update' => 'required|date',
            'location'    => 'nullable|string',
            'note'        => 'nullable|string',
            'image'       => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $fs6000smg = Fs6000smg::findOrFail($id);

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
            if ($fs6000smg->image) {
                Storage::disk('public')->delete($fs6000smg->image);
            }

            $data['image'] = $request->file('image')->store('fssmg', 'public');
        }

        $fs6000smg->update($data);

        return redirect()->route('fssmg.index')
            ->with('success', 'Data berhasil diperbarui.');
    }


    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        Fs6000smg::findOrFail($id)->delete();

        return redirect()->route('fssmg.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     $data = Fs6000smg::query();

    //     if ($query) {
    //         $data->where(function ($q) use ($query) {
    //             foreach (Schema::getColumnListing('fs6000smgs') as $column) {
    //                 $q->orWhere($column, 'LIKE', "%{$query}%");
    //             }
    //         });
    //     }

    //     $fs6000smgs = $data->paginate(10);

    //     if ($request->ajax()) {
    //         $html = view('fssmg.table', compact('fs6000smgs'))->render();
    //         return response()->json(['html' => $html]);
    //     }

    //     return view('fssmg.index', compact('fs6000smgs'));
    // }


    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            $data = Fs6000smg::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('item_name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('stock', 'LIKE', "%{$query}%");
                });
            }

            // $fs6000smgs = $data->paginate(10);
            $fs6000smgs = $data->paginate(10)->withQueryString();


            if ($request->ajax()) {
                $html = view('fssmg.table', [
                    'data' => $fs6000smgs,
                    'routePrefix' => 'fssmg',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('fssmg.index', compact('fs6000smgs'));
        } catch (\Exception $e) {

            \Log::error('fssmg search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('fssmg.table', [
                    'data' => collect(),
                    'routePrefix' => 'fssmg',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('fssmg.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }

    public function exportExcel()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fssmg.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        return Excel::download(
            new Fs6000smgExport,
            'FS6000_Semarang_' . now()->format('Ymd_His') . '.xlsx'
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

        Fs6000smg::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
