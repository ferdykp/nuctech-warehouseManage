<?php

namespace App\Http\Controllers;

use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use App\Models\SparepartTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SparepartStockController extends Controller
{
    // public function move(Request $request, $id)
    // {
    //     $request->validate([
    //         'to_site_id'   => 'required|exists:sites,id',
    //         'condition'    => 'required|in:new,used-good,damaged,repaired',
    //         'qty'          => 'required|integer|min:1',
    //     ]);

    //     $from = SparepartStock::findOrFail($id);

    //     if ($from->qty < $request->qty) {
    //         return back()->with('error', 'Stock tidak cukup! Stok tersedia: ' . $from->qty);
    //     }

    //     $from->decrement('qty', $request->qty);

    //     $currentQty = $from->qty;
    //     if ($currentQty <= 0) {
    //         $from->delete();
    //     }

    //     $to = SparepartStock::firstOrCreate(
    //         [
    //             'sparepart_id' => $from->sparepart_id,
    //             'site_id'      => $request->to_site_id,
    //             'condition'    => $request->condition,
    //         ],
    //         ['qty' => 0]
    //     );
    //     $to->increment('qty', $request->qty);

    //     SparepartHistory::create([
    //         'sparepart_id' => $from->sparepart_id,
    //         'from_site_id' => $from->site_id, 
    //         'to_site_id'   => $request->to_site_id,
    //         'action'       => 'MOVE',
    //         'condition'    => $request->condition,
    //         'qty'          => $request->qty,
    //         'note'         => $request->note,
    //     ]);

    //     return back()->with('success', 'Stock berhasil dipindahkan');
    // }

    // public function changeCondition(Request $request)
    // {
    //     $request->validate([
    //         'sparepart_id' => 'required|exists:spareparts,id',
    //         'site_id'      => 'required|exists:sites,id',
    //         'from_condition' => 'required|in:new,used-good,damaged,repaired',
    //         'to_condition'   => 'required|in:new,used-good,damaged,repaired',
    //         'qty'            => 'required|integer|min:1',
    //     ]);

    //     $from = SparepartStock::where([
    //         'sparepart_id' => $request->sparepart_id,
    //         'site_id'      => $request->site_id,
    //         'condition'    => $request->from_condition,
    //     ])->firstOrFail();

    //     abort_if($from->qty < $request->qty, 400, 'Stock tidak cukup');

    //     $from->decrement('qty', $request->qty);

    //     $to = SparepartStock::firstOrCreate(
    //         [
    //             'sparepart_id' => $request->sparepart_id,
    //             'site_id'      => $request->site_id,
    //             'condition'    => $request->to_condition,
    //         ],
    //         ['qty' => 0]
    //     );

    //     $to->increment('qty', $request->qty);

    //     SparepartHistory::create([
    //         'sparepart_id' => $request->sparepart_id,
    //         'from_site_id' => $request->site_id,
    //         'action'       => 'CHANGE_CONDITION',
    //         'old_condition' => $request->from_condition,
    //         'new_condition' => $request->to_condition,
    //         'qty'          => $request->qty,
    //         'note'         => $request->note,
    //     ]);

    //     return back()->with('success', 'Kondisi berhasil diubah');
    // }


    /**
     * TAHAP 1: REQUEST (Dilakukan oleh Cabang SMG)
     * Membuat permintaan tanpa memotong stok sama sekali.
     */
    public function requestMove(Request $request, $id)
    {
        $request->validate([
            'to_site_id' => 'required|exists:sites,id',
            'qty'        => 'required|integer|min:1',
        ]);

        $from = SparepartStock::findOrFail($id);

        if ($from->qty < $request->qty) {
            return back()->with('error', 'Stok tidak mencukupi.');
        }

        SparepartTransfer::create([
            'sparepart_id' => $from->sparepart_id,
            'from_site_id' => $from->site_id,
            'to_site_id'   => $request->to_site_id,
            'qty'          => $request->qty,
            'condition'    => $from->condition,
            'status'       => 'pending',
            'note'         => $request->note,
        ]);

        return back()->with('success', 'Permintaan mutasi berhasil dikirim.');
    }

    /**
     * TAHAP 2: APPROVE (Dilakukan oleh Cabang SBY)
     * Stok SBY berkurang, tapi stok SMG BELUM bertambah.
     * Barang dianggap "In-Transit".
     */
    public function approveMove($transferId)
    {
        $transfer = SparepartTransfer::findOrFail($transferId);

        if (auth()->user()->role !== 'superadmin' && auth()->user()->site_id !== $transfer->from_site_id) {
            return back()->with('error', 'Anda tidak memiliki otoritas di site asal ini.');
        }

        if ($transfer->status !== 'pending') {
            return back()->with('error', 'Transaksi ini sudah diproses.');
        }

        // Cari stok di site asal
        $stockSource = SparepartStock::where([
            'sparepart_id' => $transfer->sparepart_id,
            'site_id'      => $transfer->from_site_id,
            'condition'    => $transfer->condition
        ])->first();

        if (!$stockSource || $stockSource->qty < $transfer->qty) {
            return back()->with('error', 'Stok di gudang asal tidak cukup.');
        }

        DB::transaction(function () use ($transfer, $stockSource) {
            // 1. Kurangi stok asal
            $stockSource->decrement('qty', $transfer->qty);
            if ($stockSource->qty <= 0) {
                $stockSource->delete();
            }

            // 2. Update status transfer
            $transfer->update([
                'status' => 'approved',
                'approved_at' => now()
            ]);

            // 3. Catat History (Status: Dikirim)
            SparepartHistory::create([
                'sparepart_id' => $transfer->sparepart_id,
                'from_site_id' => $transfer->from_site_id,
                'to_site_id'   => $transfer->to_site_id,
                'action'       => 'OUT_TRANSFER',
                'condition'    => $transfer->condition,
                'qty'          => $transfer->qty,
                'note'         => 'Barang dalam perjalanan (Approved)',
            ]);
        });

        return back()->with('success', 'Barang disetujui dan sedang dalam pengiriman.');
    }

    /**
     * TAHAP 3: RECEIVE (Dilakukan oleh Cabang SMG)
     * Konfirmasi fisik sampai, baru stok SMG bertambah.
     */
    public function receiveMove($transferId)
    {
        $transfer = SparepartTransfer::findOrFail($transferId);

        if (auth()->user()->role !== 'superadmin' && auth()->user()->site_id !== $transfer->to_site_id) {
            return back()->with('error', 'Hanya admin site tujuan yang boleh melakukan konfirmasi terima.');
        }

        if ($transfer->status !== 'approved') {
            return back()->with('error', 'Barang belum di-approve oleh pengirim.');
        }

        DB::transaction(function () use ($transfer) {
            // 1. Tambah/Buat stok di tujuan
            $toStock = SparepartStock::firstOrCreate(
                [
                    'sparepart_id' => $transfer->sparepart_id,
                    'site_id'      => $transfer->to_site_id,
                    'condition'    => $transfer->condition,
                ],
                ['qty' => 0]
            );
            $toStock->increment('qty', $transfer->qty);

            // 2. Update status transfer
            $transfer->update([
                'status' => 'received',
                'received_at' => now()
            ]);

            // 3. Catat History Final
            SparepartHistory::create([
                'sparepart_id' => $transfer->sparepart_id,
                'from_site_id' => $transfer->from_site_id,
                'to_site_id'   => $transfer->to_site_id,
                'action'       => 'IN_TRANSFER',
                'condition'    => $transfer->condition,
                'qty'          => $transfer->qty,
                'note'         => 'Barang diterima oleh tujuan',
            ]);
        });

        return back()->with('success', 'Barang berhasil diterima dan masuk ke stok.');
    }
}
