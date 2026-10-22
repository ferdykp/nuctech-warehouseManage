<?php

namespace App\Http\Controllers;

use App\Models\Reimbursement;
use App\Models\User;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use setasign\Fpdi\Fpdi;
use App\Exports\ReimbursementExport;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\ReimbursementImport;


class AdminReimbursementController extends Controller
{
    /**
     * MENAMPILKAN LOG KLAIM (AJAX + DEBOUNCE READY)
     */
    public function index(Request $request)
    {
        $role = strtolower(auth()->user()->role ?? 'employee_role');
        $pageTitle = 'Klaim Penggantian Biaya';

        $query = \App\Services\ReimbursementAccess::query()->with('user');
        $pdfBase64 = null;
        \App\Services\ReimbursementFilters::apply($query, $request->only(['month', 'search', 'status']));

        $totalApprovedAmount = (clone $query)->sum('amount');

        // 4. Pagination
        $reimbursements = $query
            ->orderByRaw("CASE category WHEN 'transportation' THEN 1 WHEN 'delivery' THEN 2 WHEN 'office' THEN 3 ELSE 4 END")
            ->orderBy('person_name', 'asc')
            ->orderBy('date', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('reimbursements.index', compact('reimbursements', 'pageTitle', 'totalApprovedAmount', 'pdfBase64'));
    }
    public function create()
    {
        $user = auth()->user();
        $employeesQuery = Employee::with('site');

        // Jika BUKAN superadmin dan akun terikat pada site_id tertentu
        if ($user && $user->role !== 'superadmin' && $user->site_id) {
            $employeesQuery->where('site_id', $user->site_id);
        }

        $employees = $employeesQuery->orderBy('name', 'asc')->get();

        return view('reimbursements.create', compact('employees'));
    }

    /**
     * PENGAJUAN KLAIM BARU
     */
    public function store(Request $request)
    {
        if ($request->has('amount')) {
            $request->merge(['amount' => \App\Services\FormAmount::normalize($request->input('amount'))]);
        }
        $request->validate([
            'person_name' => 'required|string|max:255',
            'date' => 'required|date',
            'category' => 'required|in:transportation,delivery,office',
            'amount' => 'required|numeric|min:0',
            'receipt_attachment' => 'required|file|mimes:jpeg,png,jpg,pdf|max:4096',
        ]);

        $file = $request->file('receipt_attachment');
        $extension = strtolower($file->getClientOriginalExtension());
        $excludedPages = json_decode($request->excluded_pages, true) ?? [];
        $path = null;

        if ($extension === 'pdf' && !empty($excludedPages)) {
            try {
                $tempPath = $file->getRealPath();
                $pdfData = $this->convertPdfToVersion14($tempPath);
                $targetPdf = $pdfData['path'];

                $pdf = new Fpdi();
                $pageCount = $pdf->setSourceFile($targetPdf);

                $pagesProcessed = 0;
                for ($i = 1; $i <= $pageCount; $i++) {
                    if (!in_array($i, $excludedPages)) {
                        $templateId = $pdf->importPage($i);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                        $pagesProcessed++;
                    }
                }

                if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                    @unlink($targetPdf);
                }

                if ($pagesProcessed === 0) {
                    return redirect()->back()->with('error', 'Anda tidak boleh menghapus semua halaman PDF.');
                }

                $fileName = 'receipts/processed_' . time() . '_' . uniqid() . '.pdf';
                Storage::disk('sensitive')->put($fileName, $pdf->Output('S'));
                $path = $fileName;
            } catch (\Exception $e) {
                Log::error("Gagal memproses PDF Slicing: " . $e->getMessage());
                return redirect()->back()->with('error', 'Gagal memproses lampiran PDF.');
            }
        } else {
            $path = $file->store('receipts', 'sensitive');
        }

        $userRole = strtolower(auth()->user()->role ?? 'employee_role');
        $initialStatus = \App\Services\ReimbursementAccess::approvalStatus($userRole);

        Reimbursement::create([
            'user_id' => auth()->id(),
            'person_name' => $request->person_name,
            'date' => $request->date,
            'category' => $request->category,
            'from_location' => $request->from_location,
            'to_location' => $request->to_location,
            'amount' => $request->amount,
            'comment' => $request->comment,
            'receipt_attachment' => $path,
            'status' => $initialStatus
        ]);

        return redirect()->route('reimbursements.index')->with('success', 'Klaim berhasil diajukan.');
    }

    public function approval($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        \App\Services\ReimbursementAccess::view($reimbursement);
        $currentRole = strtolower(auth()->user()->role ?? 'employee_role');
        $myId = auth()->id();

        if ($currentRole === 'employee_role' && $reimbursement->user_id !== $myId) {
            abort(403, 'Unauthorized action.');
        }

        return view('reimbursements.approval', compact('reimbursement'));
    }

    public function approve(Request $request, $id)
    {
        return app(\App\Services\ReimbursementApproval::class)->approve($request, $id);
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'rejected_reason' => 'required|string|max:500'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            $reimbursement = Reimbursement::lockForUpdate()->findOrFail($id);
            \App\Services\ReimbursementAccess::approve($reimbursement);
            \App\Services\ReimbursementAccess::view($reimbursement);
            $reimbursement->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'rejected_reason' => $request->rejected_reason
            ]);

            return redirect()->route('reimbursements.index')->with('success', 'Klaim berhasil ditolak.');
        });
    }

    public function trash(Request $request)
    {
        $query = \App\Services\ReimbursementAccess::manageableQuery()->onlyTrashed()->with('user');

        $reimbursements = $query->orderBy('deleted_at', 'desc')->paginate(10);

        return view('reimbursements.trash', compact('reimbursements'));
    }

    /**
     * RESTORE DATA KLAIM DARI RECYCLE BIN
     */
    public function restore($id)
    {
        $reimbursement = Reimbursement::onlyTrashed()->findOrFail($id);
        \App\Services\ReimbursementAccess::manage($reimbursement);
        $reimbursement->restore();

        return redirect()->route('reimbursements.trash')->with('success', 'Klaim berhasil dipulihkan.');
    }

    /**
     * HAPUS PERMANEN DATA KLAIM & FILE FISIK NOTA
     */
    public function forceDelete($id)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $claim = Reimbursement::onlyTrashed()->lockForUpdate()->findOrFail($id);
            \App\Services\ReimbursementAccess::manage($claim);
            $claim->forceDelete();
            \App\Jobs\DeletePublicFiles::dispatch([$claim->receipt_attachment], 'sensitive')->afterCommit();
            return redirect()->route('reimbursements.trash')->with('success', 'Klaim berhasil dihapus permanen.');
        });
    }

    public function destroy($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        \App\Services\ReimbursementAccess::view($reimbursement);

        \App\Services\ReimbursementAccess::manage($reimbursement);
        // Soft Delete (File fisik tidak langsung dihapus agar bisa di-restore)
        $reimbursement->delete();

        return redirect()->route('reimbursements.index')->with('success', 'Klaim dipindahkan ke sampah.');
    }
    public function show($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        \App\Services\ReimbursementAccess::view($reimbursement);
        return redirect()->route('reimbursements.approval', $reimbursement->id);
    }

    /**
     * EXPORT SUMMARY PDF (DENGAN GHOSTSCRIPT CONVERSION)
     */
    /**
     * EXPORT SUMMARY PDF
     *
     * - Portrait PDF / image  : 1 dokumen = 1 slot
     * - Landscape PDF         : setiap halaman dibelah menjadi 2 PDF temporary
     *                           (kiri + kanan), sehingga tidak membutuhkan
     *                           ClippingRect() dan tidak menyebabkan overlap.
     */
    public function exportApprovedPdf(Request $request)
    {
        return app(\App\Services\ReimbursementDocuments::class)->exportApprovedPdf($request);
    }

    public function exportSinglePdf($id)
    {
        return app(\App\Services\ReimbursementDocuments::class)->exportSinglePdf($id);
    }

    public function exportExcel(Request $request)
    {
        $user = auth()->user();
        $search = $request->get('search');
        $month = $request->get('month');

        // 🟢 Cek jika role superadmin atau administration
        $isAllSite = in_array($user->role, ['superadmin', 'administration']);

        if ($isAllSite) {
            $siteName = 'ALL_SITES';
        } else {
            $rawSiteName = $user->site->machine_name ?? 'SITE';
            $siteName = Str::slug($rawSiteName, '_');
        }

        $userName = Str::slug($user->name ?? 'User', '_');

        if ($month) {
            $monthName = \Carbon\Carbon::createFromFormat('m', $month)->format('F_Y');
        } else {
            $monthName = now()->format('F_Y');
        }

        $fileName = "Reimbursement_{$siteName}_{$userName}_{$monthName}.xlsx";

        return Excel::download(
            new ReimbursementExport($search, $month, $isAllSite, $request->input('status')),
            $fileName
        );
    }

    /**
     * Konversi PDF v1.5+ ke PDF v1.4 menggunakan Ghostscript
     */
    private function convertPdfToVersion14($inputPath)
    {
        return app(\App\Services\ReimbursementDocuments::class)->convertPdfToVersion14($inputPath);
    }

    public function edit($id)
    {
        $reimbursement = Reimbursement::findOrFail($id);
        \App\Services\ReimbursementAccess::edit($reimbursement);
        $user = auth()->user();

        $employeesQuery = Employee::query();
        if ($user && in_array($user->role, ['employee_role', 'team_leader'])) {
            if ($user->site_id) {
                $employeesQuery->where('site_id', $user->site_id);
            }
        }
        $employees = $employeesQuery->orderBy('name', 'asc')->get();

        return view('reimbursements.edit', compact('reimbursement', 'employees'));
    }

    /**
     * SIMPAN PERUBAHAN EDIT REIMBURSEMENT
     */
    public function update(Request $request, $id)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($request, $id) {
            $reimbursement = Reimbursement::lockForUpdate()->findOrFail($id);
            \App\Services\ReimbursementAccess::edit($reimbursement);
            $user = auth()->user();

            if ($request->has('amount')) {
                $request->merge(['amount' => \App\Services\FormAmount::normalize($request->input('amount'))]);
            }

            $request->validate([
                'person_name'        => 'required|string|max:255',
                'date'               => 'required|date',
                'category'           => 'required|in:transportation,delivery,office',
                'amount'             => 'required|numeric|min:0',
                'from_location'      => 'nullable|required_if:category,transportation,delivery|string|max:255',
                'to_location'        => 'nullable|required_if:category,transportation,delivery|string|max:255',
                'comment'            => 'nullable|string',
                'receipt_attachment' => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:4096',
            ]);

            $dataToUpdate = [
                'person_name'   => $request->person_name,
                'date'          => $request->date,
                'category'      => $request->category,
                'from_location' => in_array($request->category, ['transportation', 'delivery']) ? $request->from_location : null,
                'to_location'   => in_array($request->category, ['transportation', 'delivery']) ? $request->to_location : null,
                'amount'        => $request->amount,
                'comment'       => $request->comment,
            ];

            if ($request->hasFile('receipt_attachment')) {

                $file = $request->file('receipt_attachment');
                $extension = strtolower($file->getClientOriginalExtension());
                $excludedPages = json_decode($request->excluded_pages, true) ?? [];

                if ($extension === 'pdf' && !empty($excludedPages)) {
                    try {
                        $tempPath = $file->getRealPath();
                        $pdfData = $this->convertPdfToVersion14($tempPath);
                        $targetPdf = $pdfData['path'];

                        $pdf = new Fpdi();
                        $pageCount = $pdf->setSourceFile($targetPdf);

                        $pagesProcessed = 0;
                        for ($i = 1; $i <= $pageCount; $i++) {
                            if (!in_array($i, $excludedPages)) {
                                $templateId = $pdf->importPage($i);
                                $size = $pdf->getTemplateSize($templateId);
                                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                                $pdf->useTemplate($templateId);
                                $pagesProcessed++;
                            }
                        }

                        if ($pdfData['is_temp'] && file_exists($targetPdf)) {
                            @unlink($targetPdf);
                        }

                        if ($pagesProcessed === 0) {
                            return redirect()->back()->with('error', 'Anda tidak boleh menghapus semua halaman PDF.');
                        }

                        $fileName = 'receipts/processed_' . time() . '_' . uniqid() . '.pdf';
                        Storage::disk('sensitive')->put($fileName, $pdf->Output('S'));
                        $dataToUpdate['receipt_attachment'] = $fileName;
                    } catch (\Exception $e) {
                        Log::error("Gagal memproses PDF Slicing saat edit: " . $e->getMessage());
                        return redirect()->back()->with('error', 'Gagal memproses lampiran PDF.');
                    }
                } else {
                    $dataToUpdate['receipt_attachment'] = $file->store('receipts', 'sensitive');
                }
            }

            if ($reimbursement->status === 'rejected') {
                $dataToUpdate['status'] = \App\Services\ReimbursementAccess::approvalStatus($reimbursement->user->role);
                $dataToUpdate['rejected_reason'] = null;
            }
            $oldReceipt = $reimbursement->receipt_attachment;
            $reimbursement->update($dataToUpdate);
            if (isset($dataToUpdate['receipt_attachment']) && $oldReceipt !== $dataToUpdate['receipt_attachment']) {
                \App\Jobs\DeletePublicFiles::dispatch([$oldReceipt], 'sensitive')->afterCommit();
            }

            return redirect()->route('reimbursements.index')->with('success', 'Klaim berhasil diperbarui.');
        });
    }

    public function fastApprove($id)
    {
        // Fast-track bypasses intermediate signatures and is reserved for superadmin.
        abort_unless(auth()->user()->isSuperAdmin(), 403);
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id) {
            $claim = Reimbursement::lockForUpdate()->findOrFail($id);
            \App\Services\ReimbursementAccess::approve($claim);
            $claim->update(['status' => 'approved', 'approved_by' => auth()->id()]);
            return redirect()->route('reimbursements.index')->with('success', 'Klaim disetujui oleh superadmin.');
        });
    }

    public function importExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        try {
            $import = new ReimbursementImport();
            Excel::import($import, $request->file('file'));

            $count = $import->getImportedCount();

            if ($count > 0) {
                return redirect()->route('reimbursements.index')
                    ->with('success', "Berhasil mengimpor {$count} data klaim penggantian biaya.");
            }

            return redirect()->route('reimbursements.index')
                ->with('error', 'Tidak ada data klaim valid yang ditemukan pada file Excel.');
        } catch (\Throwable $e) {
            Log::error('Reimbursement Import Exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());

            return redirect()->back()
                ->with('error', 'Gagal mengimpor Excel: ' . $e->getMessage());
        }
    }

    /**
     * HAPUS BANYAK DATA KLAIM SEKALIGUS (BULK DELETE / MOVE TO TRASH)
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'required|integer|exists:reimbursements,id'
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $reimbursements = Reimbursement::whereIn('id', $request->ids)->lockForUpdate()->get();

            foreach ($reimbursements as $reimbursement) {
                \App\Services\ReimbursementAccess::manage($reimbursement);
                // Soft delete data
                $reimbursement->delete();
            }

            return redirect()->route('reimbursements.index')->with('success', 'Klaim terpilih dipindahkan ke sampah.');
        });
    }

    /**
     * RESTORE BANYAK DATA KLAIM SEKALIGUS
     */
    public function bulkRestore(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            // Gunakan Rule::exists()->whereNotNull('deleted_at') untuk memeriksa data di Recycle Bin
            'ids.*' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('reimbursements', 'id')->whereNotNull('deleted_at')
            ]
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $reimbursements = Reimbursement::onlyTrashed()->whereIn('id', $request->ids)->lockForUpdate()->get();

            foreach ($reimbursements as $reimbursement) {
                \App\Services\ReimbursementAccess::manage($reimbursement);
                $reimbursement->restore();
            }

            return redirect()->route('reimbursements.trash')->with('success', 'Klaim terpilih berhasil dipulihkan.');
        });
    }

    /**
     * HAPUS PERMANEN BANYAK DATA KLAIM SEKALIGUS
     */
    public function bulkForceDelete(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            // Gunakan Rule::exists()->whereNotNull('deleted_at') untuk memeriksa data di Recycle Bin
            'ids.*' => [
                'required',
                'integer',
                \Illuminate\Validation\Rule::exists('reimbursements', 'id')->whereNotNull('deleted_at')
            ]
        ]);

        return \Illuminate\Support\Facades\DB::transaction(function () use ($request) {
            $reimbursements = Reimbursement::onlyTrashed()->whereIn('id', $request->ids)->lockForUpdate()->get();

            foreach ($reimbursements as $reimbursement) {
                \App\Services\ReimbursementAccess::manage($reimbursement);
            }
            $paths = [];
            foreach ($reimbursements as $reimbursement) {
                \App\Services\ReimbursementAccess::manage($reimbursement);

                if ($reimbursement->receipt_attachment) {
                    $paths[] = $reimbursement->receipt_attachment;
                }

                $reimbursement->forceDelete();
            }

            \App\Jobs\DeletePublicFiles::dispatch($paths, 'sensitive')->afterCommit();
            return redirect()->route('reimbursements.trash')->with('success', 'Klaim terpilih berhasil dihapus permanen.');
        });
    }
}
