@extends('layout.master')
@section('title', 'Koreksi Histori Gaji')
@section('content')
<div class="max-w-4xl mx-auto space-y-5 pb-8">
    <header class="flex flex-wrap justify-between gap-3">
        <div><h1 class="text-2xl font-bold">Koreksi Histori Gaji</h1><p class="mt-1 text-sm text-slate-600">{{ $employee->name }} · Catatan {{ $history->created_at->format('d/m/Y H:i') }}</p></div>
        <a href="{{ route('employee.index', ['search'=>$employee->name]) }}" class="inline-block px-4 py-3 text-sm bg-white border rounded-xl">Kembali ke Karyawan</a>
    </header>
    <section class="p-5 space-y-3 bg-white border rounded-2xl">
        <h2 class="font-bold">Catatan yang dipilih @if($history->cancelled_at)<span class="text-rose-700">· Dibatalkan</span>@endif</h2>
        <dl class="grid gap-4 text-sm sm:grid-cols-2">
            @foreach(['Gaji lama'=>'Rp '.\App\Support\UiText::number($history->old_salary), 'Gaji baru'=>'Rp '.\App\Support\UiText::number($history->new_salary), 'Alasan catatan'=>$history->reason, 'Dicatat oleh'=>$history->updated_by, 'Gaji pokok saat ini'=>'Rp '.\App\Support\UiText::number($employee->basic_salary)] as $label=>$value)
                <div class="min-w-0"><dt class="text-slate-500">{{ $label }}</dt><dd class="font-semibold break-words">{{ $value ?: '—' }}</dd></div>
            @endforeach
        </dl>
        <p class="text-sm text-slate-600">Koreksi memperbaiki catatan yang salah. Rantai nominal histori dan gaji pokok akan diselaraskan. Payroll yang sudah tersimpan tetap dipertahankan.</p>
    </section>
    @if(!$history->cancelled_at)
    <form method="POST" action="{{ route('employee.salary-history.update', [$employee, $history]) }}" class="p-5 space-y-4 bg-white border rounded-2xl">
        @csrf @method('PUT')
        <input type="hidden" name="action" value="correct"><input type="hidden" name="version" value="{{ $version }}">
        <h2 class="font-bold">Koreksi Salah Input</h2>
        <div class="grid gap-4 sm:grid-cols-2">
            <div class="min-w-0"><label for="history-amount" class="block mb-1 text-sm font-semibold">Nominal yang benar (Rp)</label><input id="history-amount" type="text" inputmode="decimal" data-money-target="history-amount-raw" value="{{ \App\Support\UiText::number(old('amount', $history->new_salary)) }}" pattern="[0-9.]+(,[0-9]{0,2})?" required class="w-full border-slate-300 rounded-xl"><input type="hidden" id="history-amount-raw" name="amount" value="{{ old('amount', $history->new_salary) }}"></div>
            <div class="min-w-0"><label for="history-entry-reason" class="block mb-1 text-sm font-semibold">Keterangan catatan yang benar</label><input id="history-entry-reason" name="entry_reason" value="{{ old('entry_reason', $history->reason) }}" required maxlength="255" class="w-full border-slate-300 rounded-xl"></div>
        </div>
        <div><label for="history-correction-reason" class="block mb-1 text-sm font-semibold">Alasan koreksi</label><textarea id="history-correction-reason" name="correction_reason" required maxlength="255" class="w-full border-slate-300 rounded-xl" placeholder="Contoh: Nominal awal salah ketik, seharusnya Rp6 juta.">{{ old('action') === 'correct' ? old('correction_reason') : '' }}</textarea></div>
        <p class="p-3 text-sm font-semibold text-blue-800 bg-blue-50 rounded-xl">Gaji pokok setelah koreksi: <span id="history-result">Rp {{ \App\Support\UiText::number($isLatest ? old('amount', $history->new_salary) : $employee->basic_salary) }}</span></p>
        @if(!$isLatest)<p class="text-xs text-slate-500">Ada perubahan gaji setelah catatan ini. Gaji pokok saat ini tetap mengikuti catatan aktif terakhir.</p>@endif
        <button type="submit" class="px-5 py-3 text-sm font-semibold text-white bg-blue-600 rounded-xl">Simpan Koreksi</button>
    </form>
    <details class="bg-white border border-rose-200 rounded-2xl" @if(old('action') === 'cancel') open @endif>
        <summary class="p-5 font-semibold cursor-pointer text-rose-800">Batalkan Perubahan yang Tidak Seharusnya Terjadi</summary>
        <form method="POST" action="{{ route('employee.salary-history.update', [$employee, $history]) }}" class="p-5 pt-0 space-y-4">
            @csrf @method('PUT')
            <input type="hidden" name="action" value="cancel"><input type="hidden" name="version" value="{{ $version }}">
            <p class="text-sm">Catatan ini ditandai dibatalkan dan tetap terlihat untuk penelusuran. Gaji pokok setelah pembatalan: <strong>Rp {{ \App\Support\UiText::number($afterCancel) }}</strong>.</p>
            <div><label for="history-cancel-reason" class="block mb-1 text-sm font-semibold">Alasan pembatalan</label><textarea id="history-cancel-reason" name="correction_reason" required maxlength="255" class="w-full border-slate-300 rounded-xl">{{ old('action') === 'cancel' ? old('correction_reason') : '' }}</textarea></div>
            <label class="flex items-start gap-2 text-sm"><input type="checkbox" name="confirm_cancel" value="1" required class="mt-1 rounded"><span>Saya sudah memeriksa nominal hasil pembatalan di atas.</span></label>
            <button type="submit" class="px-5 py-3 text-sm font-semibold text-white bg-rose-700 rounded-xl">Batalkan Catatan Ini</button>
        </form>
    </details>
    @endif
    <section class="p-5 space-y-4 bg-white border rounded-2xl">
        <h2 class="font-bold">Jejak Koreksi dan Pembatalan</h2>
        @forelse($audits as $audit)
            <article class="p-4 space-y-2 text-sm border rounded-xl">
                <p class="font-semibold">{{ $audit->action === 'cancelled' ? 'Pembatalan' : 'Koreksi salah input' }} · {{ $audit->created_at->format('d/m/Y H:i') }}</p>
                <p class="break-words">{{ $audit->actor }} · {{ $audit->reason }}</p>
                <p>Gaji pokok: Rp {{ \App\Support\UiText::number($audit->before['basic_salary'] ?? 0) }} → Rp {{ \App\Support\UiText::number($audit->after['basic_salary'] ?? 0) }}</p>
                @php($before = collect($audit->before['histories'] ?? [])->firstWhere('id', $history->id))
                @php($after = collect($audit->after['histories'] ?? [])->firstWhere('id', $history->id))
                @if($audit->action === 'cancelled')
                    <p>Status catatan: Aktif → Dibatalkan. Nominal asli tetap disimpan untuk penelusuran.</p>
                @else
                    <p>Nominal catatan: {{ $before ? 'Rp '.\App\Support\UiText::number($before['new_salary']) : 'Belum ada catatan' }} → {{ $after ? 'Rp '.\App\Support\UiText::number($after['new_salary']) : '—' }}</p>
                @endif
                <p class="break-words">Keterangan sebelumnya: {{ $before['reason'] ?? '—' }} · Sesudah: {{ $after['reason'] ?? '—' }}</p>
            </article>
        @empty<p class="text-sm text-slate-500">Belum ada koreksi atau pembatalan pada catatan ini.</p>@endforelse
    </section>
</div>
@endsection
@push('scripts')
<script>
(() => {
    const input = document.getElementById('history-amount');
    if (!input || !@json($isLatest)) return;
    input.addEventListener('input', () => queueMicrotask(() => {
        const raw = document.getElementById('history-amount-raw').value;
        document.getElementById('history-result').textContent = raw === '' ? 'Isi nominal yang benar' : 'Rp ' + new Intl.NumberFormat('id-ID', {maximumFractionDigits:2}).format(Number(raw));
    }));
})();
</script>
@endpush
