<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\SparepartStock;
use App\Models\SparepartHistory;
use App\Models\SparepartTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SparepartStockController extends Controller
{
    public function requestMove(Request $request, $id)
    {
        $data = $request->validate([
            'to_site_id' => 'required|integer|exists:sites,id',
            'qty' => 'required|integer|min:1',
            'condition' => 'required|in:new,used-good,damaged,repair',
            'note' => 'nullable|string|max:2000',
        ]);
        $from = SparepartStock::findOrFail($id);
        // The receiving site may request stock from another site.
        $this->authorizeSite($data['to_site_id']);
        if ((int) $from->site_id === (int) $data['to_site_id'] || $from->qty < $data['qty']) {
            throw ValidationException::withMessages(['qty' => 'Site tujuan harus berbeda dan stok harus mencukupi.']);
        }
        SparepartTransfer::create($data + [
            'sparepart_id' => $from->sparepart_id,
            'from_site_id' => $from->site_id,
            'from_condition' => $from->condition,
            'status' => 'pending',
        ]);
        return back()->with('success', 'Permintaan mutasi berhasil dikirim.');
    }

    public function approveMove($id)
    {
        DB::transaction(function () use ($id) {
            $transfer = SparepartTransfer::lockForUpdate()->findOrFail($id);
            $this->authorizeSite($transfer->from_site_id);
            if ($transfer->status !== 'pending') {
                throw ValidationException::withMessages(['transfer' => 'Transaksi ini sudah diproses.']);
            }
            // Serialize stock mutations even when a destination row does not yet exist.
            Sparepart::whereKey($transfer->sparepart_id)->lockForUpdate()->firstOrFail();
            $source = SparepartStock::where('sparepart_id', $transfer->sparepart_id)
                ->where('site_id', $transfer->from_site_id)
                ->where('condition', $transfer->from_condition)->lockForUpdate()->first();
            if (!$source || $source->qty < $transfer->qty) {
                throw ValidationException::withMessages(['qty' => 'Stok di gudang asal tidak cukup.']);
            }
            $source->decrement('qty', $transfer->qty);
            if ($source->qty === 0) $source->delete();
            $transfer->update(['status' => 'approved', 'approved_at' => now()]);
            $this->history($transfer, 'OUT_TRANSFER', 'Barang keluar (Approved).');
        }, 3);
        return back()->with('success', 'Barang disetujui dan sedang dalam pengiriman.');
    }

    public function receiveMove($id)
    {
        DB::transaction(function () use ($id) {
            $transfer = SparepartTransfer::lockForUpdate()->findOrFail($id);
            $this->authorizeSite($transfer->to_site_id);
            if ($transfer->status !== 'approved') {
                throw ValidationException::withMessages(['transfer' => 'Barang belum disetujui atau sudah diterima.']);
            }
            Sparepart::whereKey($transfer->sparepart_id)->lockForUpdate()->firstOrFail();
            $stock = SparepartStock::firstOrCreate([
                'sparepart_id' => $transfer->sparepart_id,
                'site_id' => $transfer->to_site_id,
                'condition' => $transfer->condition,
            ], ['qty' => 0]);
            $stock->increment('qty', $transfer->qty);
            $transfer->update(['status' => 'received', 'received_at' => now()]);
            $this->history($transfer, 'IN_TRANSFER', 'Barang diterima oleh tujuan.');
        }, 3);
        return back()->with('success', 'Barang berhasil diterima dan masuk ke stok.');
    }

    private function authorizeSite($siteId): void
    {
        abort_unless(auth()->user()->isSuperAdmin() || (auth()->user()->site_id && (int) auth()->user()->site_id === (int) $siteId), 403);
    }

    private function history(SparepartTransfer $transfer, string $action, string $note): void
    {
        SparepartHistory::create([
            'sparepart_id' => $transfer->sparepart_id,
            'from_site_id' => $transfer->from_site_id,
            'to_site_id' => $transfer->to_site_id,
            'action' => $action,
            'condition' => $transfer->condition,
            'qty' => $transfer->qty,
            'note' => $note,
        ]);
    }
}
