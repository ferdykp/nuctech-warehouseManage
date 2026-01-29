<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Location;
use Illuminate\Support\Facades\Auth;

class LocationController extends Controller
{

    public function index()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        };
        // $location = location::paginate(10);
        $location = Location::orderBy('machine_type', 'asc')->paginate(10);

        return view('location.index', compact('location'));
    }
    public function create()
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('location.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }
        $location = Location::all();


        return view('location.create', compact('location'));
    }

    public function store(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        };
        $request->validate([
            'machine_type' => 'required',
            'location_name' => 'required',
        ]);

        location::create($request->all());

        return redirect()->route('location.index')->with('success', 'Mesin ditambahkan.');
    }

    public function edit(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('location.index')
                ->with('error', 'Anda tidak memiliki akses.');
        }

        $location = Location::findOrFail($id);
        $locations = Location::all(); // ✅ ambil semua lokasi

        return view('location.edit', compact('location', 'locations'));
    }

    public function update(Request $request, $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        };
        $location = location::findOrFail($id);

        $request->validate([
            'machine_type' => 'required',
            'location_name' => 'required',
        ]);

        $location->update($request->all());

        return redirect()->route('location.index')->with('success', 'Mesin berhasil diperbarui.');
    }

    public function destroy(string $id)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            return redirect()->route('dashboard')->with('error', 'Anda tidak memiliki akses.');
        };
        $location = location::findOrFail($id);
        $location->delete();

        return redirect('location.index')->with('success', 'Mesin berhasil dihapus.');
    }

    public function search(Request $request)
    {
        try {
            $query = $request->input('query');

            $data = Location::query();

            if (!empty($query)) {
                $data->where(function ($q) use ($query) {
                    $q->where('machine_type', 'LIKE', "%{$query}%")
                        ->orWhere('location_name', 'LIKE', "%{$query}%");
                });
            }

            // $location = $data->paginate(10);
            $location = $data->paginate(10)->withQueryString();


            if ($request->ajax()) {
                $html = view('location.table', [
                    'data' => $location,
                    'routePrefix' => 'location',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return view('location.index', compact('location'));
        } catch (\Exception $e) {

            \Log::error('location search error: ' . $e->getMessage());

            if ($request->ajax()) {
                $html = view('location.table', [
                    'data' => collect(),
                    'routePrefix' => 'location',
                ])->render();

                return response()->json(['html' => $html]);
            }

            return redirect()->route('location.index')
                ->with('error', 'Terjadi kesalahan saat search');
        }
    }
}
