<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminReimbursementController extends Controller
{
    public function index()
    {
        $role = auth()->user()->role ?? 'staff';
        $pageTitle = 'Reimbursement Claims';

        // Query dasar
        $query = Reimbursement::with('user');

        if (!in_array($role, ['superadmin', 'manager'])) {
            $query->where('user_id', auth()->id());
        }

        // Hitung total akumulasi khusus untuk klaim yang sudah disetujui (Approved)
        $totalApprovedAmount = (clone $query)->where('status', 'approved')->sum('amount');

        $reimbursements = $query->latest()->paginate(10);

        return view('reimbursements.index', compact('reimbursements', 'pageTitle', 'totalApprovedAmount'));
    }

    public function create()
    {
        return view('reimbursements.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'person_name' => 'required|string|max:255',
            'date' => 'required|date',
            'category' => 'required|in:transportation,delivery,office',
            'from_location' => 'required_if:category,transportation,delivery|nullable|string|max:255',
            'to_location' => 'required_if:category,transportation,delivery|nullable|string|max:255',
            'amount' => 'required|numeric|min:0',
            'comment' => 'nullable|string|max:1000',
            'receipt_attachment' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $path = null;
        if ($request->hasFile('receipt_attachment')) {
            $path = $request->file('receipt_attachment')->store('receipts', 'public');
        }

        Reimbursement::create([
            'user_id' => auth()->id(),
            'person_name' => $request->person_name,
            'date' => $request->date,
            'category' => $request->category,
            'from_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->from_location : null,
            'to_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->to_location : null,
            'amount' => $request->amount,
            'comment' => $request->comment,
            'receipt_attachment' => $path,
            'status' => 'pending'
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Reimbursement claim filed successfully.');
    }

    public function approve($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        $reimbursement->update([
            'status' => 'approved',
            'approved_by' => auth()->id()
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Claim approved successfully.');
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500'
        ]);

        $reimbursement = Reimbursement::findOrFail($id);
        $reimbursement->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'rejected_reason' => $request->rejected_reason
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Claim rejected.');
    }

    public function destroy($id)
    {
        $reimbursement = Reimbursement::where('admin_id', auth()->id())
            ->where('status', 'pending')
            ->findOrFail($id);

        if ($reimbursement->receipt_attachment) {
            Storage::disk('public')->delete($reimbursement->receipt_attachment);
        }

        $reimbursement->delete();
        return redirect()->route('reimbursements.index')->with('success', 'Claim canceled successfully.');
    }
}
