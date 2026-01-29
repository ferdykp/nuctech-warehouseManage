<?php

namespace App\Http\Controllers;

use App\Models\Sparepart;
use App\Models\SparepartHistory;
use App\Models\Site;
use Illuminate\Http\Request;

class SparepartHistoryController extends Controller
{
    public function index($sparepartId)
    {
        return SparepartHistory::where('sparepart_id', $sparepartId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function store(array $data)
    {
        SparepartHistory::create([
            'sparepart_id'   => $data['sparepart_id'],
            'from_site_id'   => $data['from_site_id'] ?? null,
            'to_site_id'     => $data['to_site_id'] ?? null,
            'action'         => $data['action'],
            'old_condition'  => $data['old_condition'] ?? null,
            'new_condition'  => $data['new_condition'] ?? null,
            'quantity'       => $data['quantity'] ?? 0,
            'note'           => $data['note'] ?? null,
        ]);
    }
}
