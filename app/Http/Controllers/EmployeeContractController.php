<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ContractEvent;
use App\Models\ContractTemplate;
use App\Models\Employee;
use App\Models\EmployeeContract;
use App\Services\Contracts\ContractDocuments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmployeeContractController extends Controller
{
    public function index(Request $request, \App\Services\Contracts\ContractRenewalList $list, \App\Services\Contracts\RenewalListDocument $document)
    {
        $filters = $list->filters($request);
        $rows = $list->rows($filters);
        $page = max(1, $request->integer('page', 1));
        $candidates = new \Illuminate\Pagination\LengthAwarePaginator($rows->forPage($page, 15)->values(), $rows->count(), 15, $page, ['path' => $request->url(), 'query' => $request->query()]);

        return view('contracts.index', $this->renewalViewData($filters, $rows, $list, $document) + [
            'candidates' => $candidates,
            'branches' => Branch::orderBy('branch_name')->get(),
            'sites' => \App\Models\Site::orderBy('machine_name')->get(),
            'positions' => Employee::whereNotNull('position')->where('position', '!=', '')->distinct()->orderBy('position')->pluck('position'),
        ]);
    }

    public function renewalExport(Request $request, \App\Services\Contracts\ContractRenewalList $list, \App\Services\Contracts\RenewalListDocument $document)
    {
        $filters = $list->filters($request);
        $rows = $list->rows($filters);

        return view('contracts.renewal-export', $this->renewalViewData($filters, $rows, $list, $document));
    }

    public function downloadRenewalList(Request $request, \App\Services\Contracts\ContractRenewalList $list, \App\Services\Contracts\RenewalListDocument $document)
    {
        $filters = $list->filters($request);
        $rows = $list->rows($filters);
        if ($rows->isEmpty()) {
            return redirect()->route('contracts.renewals.export', $filters)->with('error', 'Tidak ada karyawan sesuai filter untuk diekspor.');
        }
        if ($rows->contains(fn ($r) => count($r['missing']) > 0) && ! $request->boolean('allow_incomplete')) {
            return redirect()->route('contracts.renewals.export', $filters)->with('error', 'Ada data belum lengkap. Periksa ringkasan dan konfirmasi sebelum mengunduh.');
        }
        $path = $document->generate($rows, $list->period($filters, 'en'));

        return response()->download($path, 'Remuneration-PKWT-'.$filters['year'].'-'.sprintf('%02d-%02d', $filters['month'], $filters['month_end']).'.docx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff',
        ])->deleteFileAfterSend(true);
    }

    private function renewalViewData(array $filters, $rows, $list, $document): array
    {
        return ['filters' => $filters, 'rows' => $rows, 'period' => $list->period($filters), 'total' => $document->money($document->totalCents($rows)),
            'missingCount' => $rows->filter(fn ($r) => count($r['missing']) > 0)->count(),
            'months' => collect(range(1, 12))->mapWithKeys(fn ($m) => [$m => \Carbon\Carbon::create(2026, $m, 1)->locale('id')->translatedFormat('F')])->all()];
    }

    public function archive(Request $request)
    {
        $query = EmployeeContract::with('employee.site', 'employee.branch')->withExists(['renewals as has_approved_renewal' => fn ($q) => $q->where('status', 'active')]);
        if ($request->filled('search')) {
            $search = mb_substr((string) $request->search, 0, 150);
            $query->where(fn ($q) => $q->where('contract_number', 'like', "%$search%")->orWhereHas('employee', fn ($e) => $e->where('name', 'like', "%$search%")));
        }
        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('branch_id')) {
            $query->whereHas('employee', fn ($e) => $e->where('branch_id', $request->integer('branch_id')));
        }
        switch ($request->status) {
            case 'attention': $query->whereIn('status', ['needs_verification', 'draft', 'pending_signature']);
                break;
            case 'active': $query->where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today());
                break;
            case 'scheduled': $query->where('status', 'active')->whereDate('start_date', '>', today());
                break;
            case 'expired': $query->where('status', 'active')->whereDate('end_date', '<', today());
                break;
            case 'due': $query->needsRenewal()->whereBetween('end_date', [today()->toDateString(), today()->addDays(60)->toDateString()]);
                break;
            case 'needs_verification': case 'draft': case 'pending_signature': case 'cancelled': $query->where('status', $request->status);
                break;
        }
        $stats = [
            'active' => EmployeeContract::where('status', 'active')->whereDate('start_date', '<=', today())->whereDate('end_date', '>=', today())->count(),
            'due' => EmployeeContract::needsRenewal()->whereBetween('end_date', [today()->toDateString(), today()->addDays(60)->toDateString()])->count(),
            'attention' => EmployeeContract::whereIn('status', ['needs_verification', 'draft', 'pending_signature'])->count(),
        ];

        return view('contracts.archive', ['contracts' => $query->orderByDesc('created_at')->orderByDesc('id')->paginate(15)->withQueryString(), 'stats' => $stats, 'branches' => Branch::orderBy('branch_name')->get()]);
    }

    public function create(Request $request, ContractDocuments $documents)
    {
        $employees = Employee::with('branch', 'site')->where('is_active', true)->where('status', '!=', 'Resigned')->orderBy('name')->get();
        $employee = $request->filled('employee_id') ? $employees->firstWhere('id', $request->integer('employee_id')) : null;
        abort_if($request->filled('employee_id') && ! $employee, 404);
        if ($employee && ($unfinished = $employee->contracts()->whereIn('status', ['draft', 'pending_signature', 'needs_verification'])->first())) {
            return redirect()->route('contracts.show', $unfinished)->with('success', 'Lanjutkan kontrak yang masih perlu diselesaikan ini terlebih dahulu.');
        }
        $previous = $employee?->contracts()->where('status', 'active')->orderByDesc('end_date')->first();
        $documents->defaultTemplate();
        $contract = new EmployeeContract(['employee_id' => $employee?->id, 'previous_contract_id' => $previous?->id,
            'contract_date' => $previous?->end_date?->copy()->addDay() ?? $employee?->pkwt_date,
            'start_date' => $previous?->end_date?->copy()->addDay() ?? $employee?->pkwt_date,
            'end_date' => ($previous?->end_date?->copy()->addDay() ?? $employee?->pkwt_date)?->copy()->addYearNoOverflow()->subDay(),
            'contract_number' => $previous ? '' : $employee?->pkwt_number,
            'snapshot' => $employee ? $this->snapshot($employee) : [],
        ]);

        return view('contracts.form', compact('contract', 'employee', 'employees', 'previous') + ['templates' => ContractTemplate::latest('id')->get()]);
    }

    public function store(Request $request)
    {
        $employee = Employee::with('branch', 'site')->findOrFail($request->integer('employee_id'));
        abort_unless($employee->is_active && $employee->status !== 'Resigned', 422, 'Karyawan tidak aktif.');
        $data = $this->validated($request, new EmployeeContract);
        $this->validateSnapshot($this->snapshot($employee));

        return DB::transaction(function () use ($request, $employee, $data) {
            Employee::whereKey($employee->id)->lockForUpdate()->first();
            if (EmployeeContract::where('employee_id', $employee->id)->whereIn('status', ['draft', 'pending_signature', 'needs_verification'])->exists()) {
                throw ValidationException::withMessages(['employee_id' => 'Masih ada kontrak yang perlu diselesaikan. Buka riwayat karyawan untuk melanjutkannya.']);
            }
            $previous = EmployeeContract::where('employee_id', $employee->id)->where('status', 'active')->orderByDesc('end_date')->first();
            $this->noOverlap($employee->id, $data['start_date'], $data['end_date']);
            $contract = EmployeeContract::create($data + ['employee_id' => $employee->id, 'status' => 'draft', 'created_by' => auth()->id(),
                'previous_contract_id' => $previous?->id, 'snapshot' => array_merge($this->snapshot($employee), $this->individual($request))]);
            $this->event($contract, 'Draft dibuat');

            return redirect()->route('contracts.show', $contract)->with('success', 'Draft dibuat. Periksa data lalu buat dokumen.');
        });
    }

    public function show(EmployeeContract $contract)
    {
        $contract->load('employee', 'template', 'previous', 'events.user');

        return view('contracts.show', compact('contract') + ['history' => $contract->employee->contracts()->get()]);
    }

    public function edit(EmployeeContract $contract, ContractDocuments $documents)
    {
        abort_unless($contract->editable, 409, 'Kontrak telah dikunci.');
        $employee = $contract->employee()->with('branch', 'site')->first();
        $documents->defaultTemplate();
        $contract->snapshot = array_merge($this->snapshot($employee), $contract->snapshot ?? []);

        return view('contracts.form', compact('contract', 'employee') + ['employees' => collect([$employee]), 'previous' => $contract->previous, 'templates' => ContractTemplate::latest('id')->get()]);
    }

    public function update(Request $request, EmployeeContract $contract)
    {
        $data = $this->validated($request, $contract);

        return DB::transaction(function () use ($request, $contract, $data) {
            Employee::whereKey($contract->employee_id)->lockForUpdate()->first();
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless($contract->editable, 409, 'Kontrak telah dikunci.');
            $this->noOverlap($contract->employee_id, $data['start_date'], $data['end_date'], $contract->id);
            $contract->fill($data);
            $snapshot = $this->snapshot($contract->employee()->with('branch', 'site')->first());
            $this->validateSnapshot($snapshot);
            $contract->snapshot = array_merge($contract->snapshot ?? [], $snapshot, $this->individual($request));
            $contract->status = 'draft';
            $contract->save();
            $this->event($contract, 'Draft diperiksa dan diperbarui');

            return redirect()->route('contracts.show', $contract)->with('success', 'Draft diperbarui.');
        });
    }

    public function generate(EmployeeContract $contract, ContractDocuments $documents)
    {
        return DB::transaction(function () use ($contract, $documents) {
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless(in_array($contract->status, ['draft', 'pending_signature']), 409, 'Verifikasi periode kontrak terlebih dahulu.');
            abort_unless($contract->template_id && $contract->start_date && $contract->end_date && $contract->contract_number && ! empty($contract->snapshot['employee_nik']), 422, 'Lengkapi data kontrak sebelum membuat dokumen.');
            $result = $documents->generate($contract);
            $contract->fill(collect($result)->except('conversion_error')->all());
            $contract->document_fingerprint = $contract->fingerprint();
            $contract->status = 'pending_signature';
            $contract->save();
            $this->event($contract, 'Dokumen dibuat', $result);

            return redirect()->route('contracts.show', $contract)->with($result['conversion_error'] ? 'error' : 'success',
                $result['conversion_error'] ? 'Word berhasil dibuat, tetapi konversi PDF belum tersedia. Periksa konfigurasi LibreOffice lalu coba Buat Ulang Dokumen.' : 'Word dan PDF siap diperiksa. Unggah dokumen bertanda tangan setelah proses selesai.');
        });
    }

    public function reopen(EmployeeContract $contract)
    {
        return DB::transaction(function () use ($contract) {
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless($contract->status === 'pending_signature', 409);
            $this->event($contract, 'Dikembalikan ke draft', ['docx_path' => $contract->docx_path, 'pdf_path' => $contract->pdf_path, 'signed_document_path' => $contract->signed_document_path]);
            $contract->update(['status' => 'draft', 'docx_path' => null, 'pdf_path' => null, 'signed_document_path' => null, 'document_fingerprint' => null]);

            return redirect()->route('contracts.edit', $contract)->with('success', 'Draft dibuka kembali. Buat ulang dokumen setelah mengubah data.');
        });
    }

    public function signed(Request $request, EmployeeContract $contract)
    {
        $request->validate(['signed_document' => 'required|file|mimes:pdf|max:10240']);

        return DB::transaction(function () use ($request, $contract) {
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            $importing = $contract->status === 'draft' && array_key_exists('legacy_contract_number', $contract->snapshot ?? []);
            abort_unless($importing || ($contract->status === 'pending_signature' && $contract->document_fingerprint === $contract->fingerprint()), 409, 'Dokumen draft belum siap.');
            if ($importing) {
                abort_unless($contract->start_date && $contract->end_date && $contract->contract_number, 422, 'Verifikasi periode dan nomor kontrak terlebih dahulu.');
                $contract->status = 'pending_signature';
                $contract->document_fingerprint = $contract->fingerprint();
            }
            $path = $request->file('signed_document')->store('contracts/'.$contract->id.'/signed', 'local');
            abort_unless($path, 500, 'Dokumen gagal disimpan.');
            $contract->signed_document_path = $path;
            $contract->save();
            $this->event($contract, 'Dokumen bertanda tangan diunggah', ['signed_document_path' => $path, 'sha256' => hash_file('sha256', Storage::disk('local')->path($path))]);

            return back()->with('success', 'Dokumen tersimpan. Periksa dokumen final sebelum mengaktifkan kontrak.');
        });
    }

    public function communication(Request $request, EmployeeContract $contract)
    {
        $data = $request->validate(['notified_at' => 'required|date|before_or_equal:today', 'employee_response' => 'nullable|in:accepted,declined', 'responded_at' => 'nullable|required_with:employee_response|date|after_or_equal:notified_at|before_or_equal:today']);

        return DB::transaction(function () use ($data, $contract) {
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless($contract->status === 'pending_signature', 409);
            $contract->update(['notified_at' => $data['notified_at'], 'responded_at' => $data['responded_at'] ?? null, 'employee_response' => $data['employee_response'] ?? null]);
            $this->event($contract, 'Pemberitahuan / respons dicatat', $data);

            return back()->with('success', 'Pemberitahuan dan respons karyawan dicatat.');
        });
    }

    public function activate(Request $request, EmployeeContract $contract)
    {
        $request->validate(['confirmed' => 'accepted']);

        return DB::transaction(function () use ($contract) {
            Employee::whereKey($contract->employee_id)->lockForUpdate()->first();
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless($contract->status === 'pending_signature' && $contract->signed_document_path && Storage::disk('local')->exists($contract->signed_document_path)
                && $contract->employee_response === 'accepted' && $contract->document_fingerprint === $contract->fingerprint(), 422, 'Unggah dokumen final dan catat persetujuan karyawan terlebih dahulu.');
            $this->noOverlap($contract->employee_id, $contract->start_date->toDateString(), $contract->end_date->toDateString(), $contract->id);
            $contract->update(['status' => 'active', 'activated_at' => now(), 'activated_by' => auth()->id()]);
            $this->event($contract, 'Kontrak disetujui dan diaktifkan');

            return back()->with('success', 'Kontrak disetujui. Status aktif atau terjadwal mengikuti periode berlakunya.');
        });
    }

    public function cancel(Request $request, EmployeeContract $contract)
    {
        $request->validate(['reason' => 'required|string|max:1000']);

        return DB::transaction(function () use ($contract, $request) {
            $contract = EmployeeContract::lockForUpdate()->findOrFail($contract->id);
            abort_unless(in_array($contract->status, ['draft', 'needs_verification', 'pending_signature']), 409, 'Kontrak aktif tidak dapat dibatalkan melalui tindakan ini.');
            $contract->update(['status' => 'cancelled']);
            $this->event($contract, 'Tidak diperpanjang / draft dibatalkan', ['reason' => $request->reason]);

            return back()->with('success', 'Keputusan dicatat. Riwayat dan dokumen tetap tersimpan.');
        });
    }

    public function download(Request $request, EmployeeContract $contract, string $kind)
    {
        abort_unless(in_array($kind, ['docx', 'pdf', 'signed']), 404);
        $key = ['docx' => 'docx_path', 'pdf' => 'pdf_path', 'signed' => 'signed_document_path'][$kind];
        $path = $contract->$key;
        if ($request->filled('event')) {
            $event = $contract->events()->findOrFail($request->integer('event'));
            $path = $event->details[$key] ?? null;
        }
        abort_unless($path && str_starts_with($path, 'contracts/'.$contract->id.'/') && Storage::disk('local')->exists($path), 404);
        $name = 'PKWT-'.$contract->id.'-'.$kind.'.'.($kind === 'docx' ? 'docx' : 'pdf');

        return $request->boolean('preview') && $kind !== 'docx'
            ? response()->file(Storage::disk('local')->path($path), ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'inline; filename="'.$name.'"', 'Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff'])
            : Storage::disk('local')->download($path, $name, ['Cache-Control' => 'private, no-store', 'X-Content-Type-Options' => 'nosniff']);
    }

    public function templates(ContractDocuments $documents)
    {
        $documents->defaultTemplate();

        return view('contracts.templates', ['templates' => ContractTemplate::latest('id')->get(), 'placeholders' => ContractDocuments::REQUIRED]);
    }

    public function storeTemplate(Request $request, ContractDocuments $documents)
    {
        $data = $request->validate(['name' => 'required|string|max:150', 'version' => 'required|string|max:80|unique:contract_templates,version', 'template' => 'required|file|mimes:docx|max:5120']);
        $documents->validateTemplate($request->file('template')->getRealPath());
        $path = $request->file('template')->store('contract-templates', 'local');
        abort_unless($path, 500);
        ContractTemplate::create(['name' => $data['name'], 'version' => $data['version'], 'path' => $path, 'sha256' => hash_file('sha256', Storage::disk('local')->path($path)), 'created_by' => auth()->id()]);

        return back()->with('success', 'Versi template disimpan. Kontrak lama tetap menggunakan versi sebelumnya.');
    }

    public function downloadTemplate(ContractTemplate $template, ContractDocuments $documents)
    {
        $path = $documents->templatePath($template);
        abort_unless(is_file($path), 404);

        return response()->download($path, 'Template-PKWT-'.$template->id.'.docx', ['Cache-Control' => 'private, no-store']);
    }

    private function validated(Request $request, EmployeeContract $contract): array
    {
        $data = $request->validate([
            'contract_number' => ['required', 'string', 'max:150', Rule::unique('employee_contracts')->ignore($contract->id)],
            'pkwt_date' => 'required|date', 'start_date' => 'required|date', 'end_date' => 'required|date|after_or_equal:start_date',
            'template_id' => 'required|exists:contract_templates,id', 'job_type' => 'required|string|max:200', 'working_hours' => 'required|string|max:200', 'leave_entitlement' => 'required|string|max:150',
            'representative_name' => 'required|string|max:150', 'representative_position' => 'required|string|max:150', 'representative_address' => 'required|string|max:500',
        ]);

        // Contract issue date may differ from commencement; both remain explicit in the form.
        return ['contract_number' => $data['contract_number'], 'contract_date' => $data['pkwt_date'], 'start_date' => $data['start_date'], 'end_date' => $data['end_date'], 'template_id' => $data['template_id']];
    }

    private function snapshot(Employee $e): array
    {
        $branch = $e->branch ?? $e->site?->branch;

        return ['employee_name' => $e->name, 'employee_nik' => $e->nik, 'employee_phone' => $e->phone_number,
            'employee_address' => $branch?->branch_address ?: $branch?->branch_name, 'branch_name' => $branch?->branch_name,
            'basic_salary' => (string) $e->basic_salary, 'position' => $e->position, 'work_location' => $branch?->branch_name ?: $e->site?->location,
            'job_type' => $e->position, 'working_hours' => 'jam kerja kantor atau sistem shift', 'leave_entitlement' => '12 hari kerja per tahun',
            'employer' => config('contracts.employer'), 'placement_company' => config('contracts.placement_company'),
            'representative_name' => config('contracts.representative_name'), 'representative_position' => config('contracts.representative_position'), 'representative_address' => config('contracts.representative_address')];
    }

    private function validateSnapshot(array $snapshot): void
    {
        $missing = [];
        foreach (['employee_name' => 'nama', 'employee_nik' => 'NIK KTP', 'employee_phone' => 'nomor HP', 'position' => 'jabatan', 'employee_address' => 'alamat / nama branch', 'work_location' => 'lokasi kerja'] as $key => $label) {
            if (blank($snapshot[$key] ?? null)) {
                $missing[] = $label;
            }
        }
        if (! is_numeric($snapshot['basic_salary'] ?? null) || (float) $snapshot['basic_salary'] < 0) {
            $missing[] = 'gaji pokok';
        }
        if ($missing) {
            throw ValidationException::withMessages(['employee_id' => 'Lengkapi profil karyawan terlebih dahulu: '.implode(', ', $missing).'.']);
        }
    }

    private function individual(Request $r): array
    {
        return $r->only(['job_type', 'working_hours', 'leave_entitlement', 'representative_name', 'representative_position', 'representative_address']);
    }

    private function noOverlap(int $employee, string $start, string $end, ?int $except = null): void
    {
        if (EmployeeContract::where('employee_id', $employee)->where('status', 'active')->when($except, fn ($q) => $q->where('id', '!=', $except))->whereDate('start_date', '<=', $end)->whereDate('end_date', '>=', $start)->exists()) {
            throw ValidationException::withMessages(['start_date' => 'Periode bertumpuk dengan kontrak yang sudah disetujui. Periksa riwayat dan tanggal mulai.']);
        }
    }

    private function event(EmployeeContract $c, string $action, array $details = []): void
    {
        ContractEvent::create(['employee_contract_id' => $c->id, 'user_id' => auth()->id(), 'action' => $action, 'details' => $details]);
    }
}
