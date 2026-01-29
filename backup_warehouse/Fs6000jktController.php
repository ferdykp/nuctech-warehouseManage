<?php

namespace App\Http\Controllers;

use App\Models\Fs6000jkt;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use App\Models\SparePart;
use App\Models\Location;
use Illuminate\Support\Facades\Storage;
use App\Exports\Fs6000jktExport;
use Maatwebsite\Excel\Facades\Excel;


class Fs6000jktController extends Controller
{
    public function index()
    {
        $fs6000jkts = Fs6000jkt::paginate(10);
        return view('fsjkt.index', compact('fs6000jkts'));
    }
    public function show(string $id)
    {
        $fs6000jkt = Fs6000jkt::findOrFail($id);
        return view('fsjkt.show', compact('fs6000jkt'));
    }


    // public function create()
    // {
    //     if (!Auth::check() || Auth::user()->role !== 'admin') {
    //         return redirect()->route('fsjkt.index')
    //             ->with('error', 'Anda tidak memiliki akses.');
    //     }
    //     $location = Location::all();


    //     return view('fsjkt.create', compact('location'));
    // }
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $location = Location::where('machine_type', 'fs6000jkt')->get();

        return view('fsjkt.create', compact('location'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
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
        $imagePath = $request->file('image')->store('fsjkt', 'public');

        Fs6000jkt::create([
            'item_name'   => $request->item_name,
            'type'        => $request->type,
            'stock'       => $request->stock,
            'uom'       => $request->uom,
            'date_update' => $request->date_update,
            'location'   => $request->location, // ikut nama kolom DB
            'note'        => $request->note,
            'image'       => $imagePath, // fsjkt/namafile.jpg
        ]);

        return redirect()->route('fsjkt.index')
            ->with('success', 'Data berhasil ditambahkan.');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $fs6000jkt = Fs6000jkt::findOrFail($id);
        $locations = Location::all(); // ✅ ambil semua lokasi

        return view('fsjkt.edit', compact('fs6000jkt', 'locations'));
    }


    public function update(Request $request, string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
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

        $fs6000jkt = Fs6000jkt::findOrFail($id);

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
            if ($fs6000jkt->image) {
                Storage::disk('public')->delete($fs6000jkt->image);
            }

            $data['image'] = $request->file('image')->store('fsjkt', 'public');
        }

        $fs6000jkt->update($data);

        return redirect()->route('fsjkt.index')
            ->with('success', 'Data berhasil diperbarui.');
    }


    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        Fs6000jkt::findOrFail($id)->delete();

        return redirect()->route('fsjkt.index')
            ->with('success', 'Data berhasil dihapus.');
    }

    // public function search(Request $request)
    // {
    //     $query = $request->input('query');

    //     $data = Fs6000jkt::query();

    //     if ($query) {
    //         $data->where(function ($q) use ($query) {
    //             foreach (Schema::getColumnListing('fs6000jkts') as $column) {
    //                 $q->orWhere($column, 'LIKE', "%{$query}%");
    //             }
    //         });
    //     }

    //     $fs6000jkts = $data->paginate(10);

    //     if ($request->ajax()) {
    //         $html = view('fsjkt.table', compact('fs6000jkts'))->render();
    //         return response()->json(['html' => $html]);
    //     }

    //     return view('fsjkt.index', compact('fs6000jkts'));
    // }


    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            $data = Fs6000jkt::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('item_name', 'LIKE', "%{$query}%")
                        ->orWhere('type', 'LIKE', "%{$query}%")
                        ->orWhere('stock', 'LIKE', "%{$query}%");
                });
            }

            // $fs6000jkts = $data->paginate(10);
            $fs6000jkts = $data->paginate(10)->withQueryString();


            if ($request->ajax()) {
                $html = view('fsjkt.table', [
                    'data' => $fs6000jkts,
                    'routePrefix' => 'fsjkt',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('fsjkt.index', compact('fs6000jkts'));
        } catch (\Exception $e) {

            \Log::error('FSJKT search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('fsjkt.table', [
                    'data' => collect(),
                    'routePrefix' => 'fsjkt',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('fsjkt.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }

    public function exportExcel()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('fsjkt.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        return Excel::download(
            new Fs6000jktExport,
            'FS6000_Jakarta_' . now()->format('Ymd_His') . '.xlsx'
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

        Fs6000jkt::whereIn('id', $ids)->delete();

        return response()->json(['success' => true]);
    }
}
