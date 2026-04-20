<?php

namespace App\Http\Controllers;

use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SparepartStockController extends Controller
{
    public function move(Request $request, $id)
    {
        $request->validate([
            'to_site_id' => 'required|exists:sites,id',
            'condition'  => 'required|in:new,used-good,damaged,repair',
            'qty'        => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $id) {
            $from = SparepartStock::findOrFail($id);

            if ($from->qty < $request->qty) {
                return back()->with('error', 'Stock tidak cukup! Tersedia: ' . $from->qty);
            }

            $from->decrement('qty', $request->qty);
            
            if ($from->fresh()->qty <= 0) {
                $from->delete();
            }

            $to = SparepartStock::firstOrCreate(
                [
                    'sparepart_id' => $from->sparepart_id,
                    'site_id'      => $request->to_site_id,
                    'condition'    => $request->condition,
                ],
                ['qty' => 0]
            );
            $to->increment('qty', $request->qty);

            SparepartHistory::create([
                'sparepart_id' => $from->sparepart_id,
                'from_site_id' => $from->site_id,
                'to_site_id'   => $request->to_site_id,
                'action'       => 'MOVE',
                'condition'    => $request->condition,
                'qty'          => $request->qty,
                'note'         => $request->note,
            ]);

            return back()->with('success', 'Stock berhasil dipindahkan ke lokasi baru.');
        });
    }

    public function changeCondition(Request $request)
    {
        $request->validate([
            'sparepart_id'   => 'required|exists:spareparts,id',
            'site_id'        => 'required|exists:sites,id',
            'from_condition' => 'required|in:new,used-good,damaged,repair',
            'to_condition'   => 'required|in:new,used-good,damaged,repair',
            'qty'            => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request) {
            $from = SparepartStock::where([
                'sparepart_id' => $request->sparepart_id,
                'site_id'      => $request->site_id,
                'condition'    => $request->from_condition,
            ])->firstOrFail();

            if ($from->qty < $request->qty) {
                return back()->with('error', 'Stock tidak cukup untuk perubahan kondisi.');
            }

            $from->decrement('qty', $request->qty);
            if ($from->fresh()->qty <= 0) $from->delete();

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
                'action'       => 'CONDITION_CHANGE',
                'condition'    => $request->to_condition,
                'qty'          => $request->qty,
                'note'         => $request->note . " (Changed from {$request->from_condition})",
            ]);

            return back()->with('success', 'Kondisi stok berhasil diperbarui.');
        });
    }
}