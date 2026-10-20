<section class="p-5 space-y-5 bg-white border rounded-3xl sm:p-8" aria-labelledby="allowances-title">
    <div>
        <h2 id="allowances-title" class="text-lg font-bold text-slate-900">Tunjangan Bulanan</h2>
        <p class="mt-1 text-sm text-slate-500">Dibayar penuh setiap bulan bersama gaji, terpisah dari gaji pokok. Bulan berakhir adalah bulan terakhir dibayar.</p>
        <p class="mt-1 text-sm text-slate-500">Payroll yang sudah tersimpan tidak berubah. Untuk menerapkan penyesuaian ke payroll tersimpan, buka Ubah Data Gaji dan pilih perbarui tunjangan.</p>
    </div>
    <div class="space-y-3">
        @forelse($employee->allowances as $allowance)
            <article class="p-4 space-y-3 border rounded-2xl bg-slate-50">
                <div class="flex flex-wrap justify-between gap-2">
                    <h3 class="font-bold break-words">{{ $allowance->name }}</h3>
                    <strong>Rp {{ \App\Support\UiText::number((float) $allowance->amount) }} / bulan</strong>
                </div>
                <p class="text-sm text-slate-600">{{ $allowance->start_month }} sampai {{ $allowance->end_month ?? 'seterusnya' }} · {{ $allowance->start_month > now()->format('Y-m') ? 'Terjadwal' : ($allowance->end_month && $allowance->end_month < now()->format('Y-m') ? 'Berakhir' : 'Aktif') }}</p>
                @if($allowance->notes)<p class="text-sm break-words">{{ $allowance->notes }}</p>@endif
                @if($allowance->certificate_path)<a class="inline-block py-2 text-sm font-semibold text-blue-700" href="{{ route('employee.allowances.certificate', [$employee, $allowance]) }}">Unduh sertifikat</a>@endif
                @if(!$allowance->end_month || $allowance->end_month >= now()->format('Y-m'))
                    <form action="{{ route('employee.allowances.end', [$employee, $allowance]) }}" method="POST" class="flex flex-wrap items-end gap-3">
                        @csrf @method('PUT')
                        <div><label for="allowance-end-{{ $allowance->id }}" class="block mb-1 text-sm">Bulan terakhir dibayar</label><x-month-picker :id="'allowance-end-'.$allowance->id" name="end_month" :min="max($allowance->start_month, now()->format('Y-m'))" :value="$allowance->end_month" :required="true" /></div>
                        <button class="px-4 py-3 text-sm font-semibold bg-white border rounded-xl">Simpan Masa Berlaku</button>
                    </form>
                @endif
            </article>
        @empty
            <p class="p-4 text-sm text-slate-500 border rounded-xl">Belum ada tunjangan bulanan.</p>
        @endforelse
    </div>
    <details class="border rounded-2xl" @if($errors->hasAny(['name','amount','start_month','end_month','notes','certificate'])) open @endif>
        <summary class="p-4 font-semibold cursor-pointer">Tambah Tunjangan Bulanan</summary>
        <form action="{{ route('employee.allowances.store', $employee) }}" method="POST" enctype="multipart/form-data" class="grid gap-4 p-4 border-t sm:grid-cols-2">
            @csrf
            <div class="min-w-0"><label for="allowance-name" class="block mb-1 text-sm font-semibold">Nama tunjangan</label><input id="allowance-name" name="name" value="{{ old('name') }}" placeholder="Sertifikasi PPR" maxlength="100" required class="w-full border-slate-300 rounded-xl"></div>
            <div class="min-w-0"><label for="allowance-amount" class="block mb-1 text-sm font-semibold">Nominal per bulan (Rp)</label><div class="relative"><span class="absolute left-3 top-3 text-sm font-semibold text-slate-500" aria-hidden="true">Rp</span><input type="text" inputmode="decimal" id="allowance-amount" data-money-target="allowance-amount-raw" value="{{ old('amount') !== null ? number_format((float) old('amount'), 2, ',', '.') : '' }}" pattern="[0-9.]+(,[0-9]{0,2})?" maxlength="24" placeholder="1.000.000" autocomplete="off" required class="w-full pl-10 border-slate-300 rounded-xl tabular-nums"><input type="hidden" id="allowance-amount-raw" name="amount" value="{{ old('amount') }}"></div><p class="mt-1 text-xs text-slate-500">Contoh: Rp 1.000.000 · Desimal memakai koma.</p></div>
            <div class="min-w-0"><label for="allowance-start" class="block mb-1 text-sm font-semibold">Mulai bulan</label><x-month-picker id="allowance-start" name="start_month" :value="old('start_month', now()->format('Y-m'))" :required="true" /></div>
            <div class="min-w-0"><label for="allowance-end" class="block mb-1 text-sm font-semibold">Bulan terakhir dibayar (opsional)</label><x-month-picker id="allowance-end" name="end_month" :value="old('end_month')" /></div>
            <div class="min-w-0"><label for="allowance-notes" class="block mb-1 text-sm font-semibold">Keterangan (opsional)</label><textarea id="allowance-notes" name="notes" maxlength="500" rows="2" class="w-full border-slate-300 rounded-xl">{{ old('notes') }}</textarea></div>
            <div class="min-w-0"><label for="allowance-certificate" class="block mb-1 text-sm font-semibold">Sertifikat (opsional)</label><input type="file" id="allowance-certificate" name="certificate" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-sm"><p class="mt-1 text-xs text-slate-500">PDF, JPG, PNG · Maksimal 5 MB</p></div>
            <div class="sm:col-span-2"><button class="px-5 py-3 text-sm font-semibold text-white bg-emerald-600 rounded-xl">Simpan Tunjangan</button></div>
        </form>
    </details>
</section>
