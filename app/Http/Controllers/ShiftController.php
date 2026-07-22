<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;

class ShiftController extends Controller
{
    public function index()
    {
        $shifts = Shift::orderBy('is_off', 'asc')->get();
        return view('shift.index', compact('shifts'));
    }

    public function store(Request $request)
    {
        // Proteksi Role: Hanya Superadmin yang boleh membuat Shift baru
        if (Auth::user()->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang diizinkan menambah Master Shift.');
        }

        $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'nullable|required_if:is_off,0',
            'end_time'   => 'nullable|required_if:is_off,0',
        ]);

        $isOff = $request->has('is_off') && $request->is_off == '1';

        Shift::create([
            'shift_name' => $request->shift_name,
            'start_time' => $isOff ? '00:00:00' : $request->start_time,
            'end_time'   => $isOff ? '00:00:00' : $request->end_time,
            'is_off'     => $isOff,
        ]);

        return redirect()->route('shift.index')->with('success', 'Master Shift berhasil ditambahkan!');
    }

    public function update(Request $request, Shift $shift)
    {
        // Proteksi Role: Hanya Superadmin yang boleh mengedit Shift
        if (Auth::user()->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang diizinkan mengedit Master Shift.');
        }

        $request->validate([
            'shift_name' => 'required|string|max:255',
            'start_time' => 'nullable|required_if:is_off,0',
            'end_time'   => 'nullable|required_if:is_off,0',
        ]);

        $isOff = $request->has('is_off') && $request->is_off == '1';

        $shift->update([
            'shift_name' => $request->shift_name,
            'start_time' => $isOff ? '00:00:00' : $request->start_time,
            'end_time'   => $isOff ? '00:00:00' : $request->end_time,
            'is_off'     => $isOff,
        ]);

        return redirect()->route('shift.index')->with('success', 'Master Shift berhasil diperbarui!');
    }

    public function destroy(Shift $shift)
    {
        // Proteksi Role: Hanya Superadmin yang boleh menghapus Shift
        if (Auth::user()->role !== 'superadmin') {
            abort(403, 'Hanya Superadmin yang diizinkan menghapus Master Shift.');
        }

        $shift->delete();
        return redirect()->route('shift.index')->with('success', 'Master Shift berhasil dihapus.');
    }
}
