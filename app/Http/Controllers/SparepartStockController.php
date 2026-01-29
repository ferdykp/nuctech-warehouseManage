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
    public function move(Request $request)
    {
        $request->validate([
            'sparepart_id' => 'required|exists:spareparts,id',
            'from_site_id' => 'required|exists:sites,id',
            'to_site_id'   => 'required|exists:sites,id|different:from_site_id',
            'condition'    => 'required|in:good,used,damaged',
            'qty'          => 'required|integer|min:1',
        ]);

        $from = SparepartStock::where([
            'sparepart_id' => $request->sparepart_id,
            'site_id'      => $request->from_site_id,
            'condition'    => $request->condition,
        ])->firstOrFail();

        abort_if($from->qty < $request->qty, 400, 'Stock tidak cukup');

        $from->decrement('qty', $request->qty);

        $to = SparepartStock::firstOrCreate(
            [
                'sparepart_id' => $request->sparepart_id,
                'site_id'      => $request->to_site_id,
                'condition'    => $request->condition,
            ],
            ['qty' => 0]
        );

        $to->increment('qty', $request->qty);

        SparepartHistory::create([
            'sparepart_id' => $request->sparepart_id,
            'from_site_id' => $request->from_site_id,
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
            'from_condition' => 'required|in:good,used,damaged',
            'to_condition'   => 'required|in:good,used,damaged',
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
