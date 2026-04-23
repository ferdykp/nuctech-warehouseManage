<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use Illuminate\Http\Request;

class BranchController extends Controller
{
    public function index()
    {
        // Mengambil branch beserta jumlah sitenya agar informatif
        $branches = Branch::withCount('sites')->paginate(10);
        return view('branches.index', compact('branches'));
    }

    public function create()
    {
        return view('branches.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'branch_name'    => 'required|string|max:255',
            'branch_code'    => 'required|string|unique:branches,branch_code',
            'branch_address' => 'nullable|string',
        ]);

        Branch::create($request->all());

        return redirect()->route('branches.index')
            ->with('success', 'Branch berhasil ditambahkan!');
    }

    public function edit(string $id)
    {
        $branch = Branch::findOrFail($id);
        return view('branches.edit', compact('branch'));
    }

    public function update(Request $request, string $id)
    {
        $branch = Branch::findOrFail($id);

        $validated = $request->validate([
            'branch_name'    => 'required|string|max:255',
            'branch_code'    => 'required|string',
            'branch_address' => 'nullable|string',

        ]);
        $branch->update($validated);

        return redirect()->route('branches.index')
            ->with('success', 'Branch berhasil diupdate!');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')
            ->with('success', 'Branch berhasil dihapus!');
    }
}
