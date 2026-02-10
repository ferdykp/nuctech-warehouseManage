<?php

namespace App\Http\Controllers;

use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use Illuminate\Http\Request;

class SparepartStockController extends Controller
{
    /* =====================
        MOVE STOCK
    ====================== */
    // public function move(Request $request)
    // {
    //     $request->validate([
    //         'sparepart_id' => 'required|exists:spareparts,id',
    //         'from_site_id' => 'required|exists:sites,id',
    //         'to_site_id'   => 'required|exists:sites,id|different:from_site_id',
    //         'condition'    => 'required|in:new,used-good,damaged,repaired',
    //         'qty'          => 'required|integer|min:1',
    //     ]);

    //     $from = SparepartStock::where([
    //         'sparepart_id' => $request->sparepart_id,
    //         'site_id'      => $request->from_site_id,
    //         'condition'    => $request->condition,
    //     ])->firstOrFail();

    //     abort_if($from->qty < $request->qty, 400, 'Stock tidak cukup');

    //     $from->decrement('qty', $request->qty);

    //     $to = SparepartStock::firstOrCreate(
    //         [
    //             'sparepart_id' => $request->sparepart_id,
    //             'site_id'      => $request->to_site_id,
    //             'condition'    => $request->condition,
    //         ],
    //         ['qty' => 0]
    //     );

    //     $to->increment('qty', $request->qty);

    //     SparepartHistory::create([
    //         'sparepart_id' => $request->sparepart_id,
    //         'from_site_id' => $request->from_site_id,
    //         'to_site_id'   => $request->to_site_id,
    //         'action'       => 'MOVE',
    //         'condition'    => $request->condition,
    //         'qty'          => $request->qty,
    //         'note'         => $request->note,
    //     ]);

    //     $from->decrement('qty', $request->qty);

    //     if ($from->qty - $request->qty <= 0) {
    //         $from->delete();
    //     }


    //     return back()->with('success', 'Stock berhasil dipindahkan');
    // }

    public function move(Request $request, $id) // Tangkap ID dari URL
    {
        $request->validate([
            'to_site_id'   => 'required|exists:sites,id',
            'condition'    => 'required|in:new,used-good,damaged,repaired',
            'qty'          => 'required|integer|min:1',
        ]);

        // Cari data stok asal berdasarkan ID yang dikirim di URL
        $from = SparepartStock::findOrFail($id);

        // Validasi stok cukup
        if ($from->qty < $request->qty) {
            return back()->with('error', 'Stock tidak cukup! Stok tersedia: ' . $from->qty);
        }

        // 1. Kurangi stok asal
        $from->decrement('qty', $request->qty);

        // Hapus record jika stok habis agar tidak mengotori tabel
        $currentQty = $from->qty;
        if ($currentQty <= 0) {
            $from->delete();
        }

        // 2. Tambah/Buat stok di tujuan
        $to = SparepartStock::firstOrCreate(
            [
                'sparepart_id' => $from->sparepart_id,
                'site_id'      => $request->to_site_id,
                'condition'    => $request->condition,
            ],
            ['qty' => 0]
        );
        $to->increment('qty', $request->qty);

        // 3. Catat History
        SparepartHistory::create([
            'sparepart_id' => $from->sparepart_id,
            'from_site_id' => $from->site_id, // Ambil otomatis dari data stok asal
            'to_site_id'   => $request->to_site_id,
            'action'       => 'MOVE',
            'condition'    => $request->condition,
            'qty'          => $request->qty,
            'note'         => $request->note,
        ]);

        return back()->with('success', 'Stock berhasil dipindahkan');
    }

    /* =====================
        CHANGE CONDITION
    ====================== */
    public function changeCondition(Request $request)
    {
        $request->validate([
            'sparepart_id' => 'required|exists:spareparts,id',
            'site_id'      => 'required|exists:sites,id',
            'from_condition' => 'required|in:new,used-good,damaged,repaired',
            'to_condition'   => 'required|in:new,used-good,damaged,repaired',
            'qty'            => 'required|integer|min:1',
        ]);

        $from = SparepartStock::where([
            'sparepart_id' => $request->sparepart_id,
            'site_id'      => $request->site_id,
            'condition'    => $request->from_condition,
        ])->firstOrFail();

        abort_if($from->qty < $request->qty, 400, 'Stock tidak cukup');

        $from->decrement('qty', $request->qty);

        $to = SparepartStock::firstOrCreate(
            [
                'sparepart_id' => $request->sparepart_id,
                'site_id'      => $request->site_id,
                'condition'    => $request->to_condition,
            ],
            ['qty' => 0]
        );

        $to->increment('qty', $request->qty);

        SparepartHistory::create([
            'sparepart_id' => $request->sparepart_id,
            'from_site_id' => $request->site_id,
            'action'       => 'CHANGE_CONDITION',
            'old_condition' => $request->from_condition,
            'new_condition' => $request->to_condition,
            'qty'          => $request->qty,
            'note'         => $request->note,
        ]);

        return back()->with('success', 'Kondisi berhasil diubah');
    }
}
