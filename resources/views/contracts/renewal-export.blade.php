@extends('layout.master')
@section('title', 'Ekspor Daftar Perpanjangan')
@section('content')
    <div class="max-w-6xl pb-8 mx-auto space-y-5">
        <header>
            <h1 class="text-2xl font-bold">Ekspor Daftar Perpanjangan</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $period }} · {{ $rows->count() }} karyawan · Total
                {{ $total }}</p>
        </header>
        <p class="text-sm text-slate-600">Format Word mengikuti Remuneration List according to PKWT. Project Team Name
            memakai position; Amount memakai gaji pokok saat ini. Seluruh hasil filter diekspor, termasuk halaman tabel
            berikutnya.</p>
        <div class="overflow-hidden bg-white border rounded-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-sm" data-mobile-cards>
                    <thead class="bg-slate-50">
                        <tr>
                            @foreach (['No.', 'Project Team Name', 'Name', 'Bank', 'Account No.', 'Amount', 'Pemeriksaan'] as $heading)
                                <th scope="col" class="p-3 text-left">{{ $heading }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($rows as $i=>$row)
                            <tr>
                                <td class="p-3">{{ $i + 1 }}</td>
                                <td class="p-3">{{ $row['employee']->position ?: '—' }}</td>
                                {{-- <td class="p-3">{{ $row['employee']->name }}{{ $row['legacy'] ? ' *' : '' }}</td> --}}
                                <td class="p-3">{{ $row['employee']->name }}</td>
                                <td class="p-3">{{ $row['employee']->bank_name ?: '—' }}</td>
                                <td class="p-3 font-mono break-all">{{ $row['employee']->bank_account_number ?: '—' }}</td>
                                <td class="p-3">{{ $row['amount_label'] }}</td>
                                <td class="p-3 text-xs">
                                    @if ($row['missing'])
                                        <span class="text-amber-800">Belum lengkap:
                                            {{ implode(', ', $row['missing']) }}</span><a
                                            class="block py-2 text-blue-700 underline"
                                            href="{{ route('employee.edit', $row['employee']) }}">Lengkapi Profil</a>
                                    @else
                                        Lengkap
                                        @endif @if ($row['legacy'])
                                            <p class="mt-1 text-slate-500">* Periode perlu verifikasi</p>
                                        @endif
                                </td>
                        </tr>@empty<tr>
                                <td colspan="7" class="p-6 text-center">Tidak ada karyawan sesuai filter.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <form method="GET" action="{{ route('contracts.renewals.download') }}"
            class="p-5 space-y-4 bg-white border rounded-2xl">
            @foreach ($filters as $key => $value)
                <input type="hidden" name="{{ $key }}" value="{{ $value }}">
            @endforeach
            @if ($missingCount)
                <p class="text-sm text-amber-800">{{ $missingCount }} karyawan memiliki data yang belum lengkap.</p><label
                    class="flex items-start gap-3 text-sm"><input type="checkbox" name="allow_incomplete" value="1"
                        required class="mt-1"><span>Saya memahami data kosong akan ditampilkan sebagai “—” dan akan
                        diperiksa sebelum dokumen digunakan.</span></label>
            @endif
            <p class="text-xs text-slate-500">* menandai data ulang tahun PKWT yang belum diverifikasi. Periksa daftar
                sebelum menggunakannya untuk proses perpanjangan.</p>
            <div class="flex flex-wrap gap-3"><a href="{{ route('contracts.index', $filters) }}"
                    class="px-4 py-3 text-sm border rounded-xl">Kembali ke Daftar</a><button type="submit"
                    class="px-5 py-3 text-sm font-semibold text-white bg-blue-600 rounded-xl"
                    @disabled($rows->isEmpty())>Unduh Word (.docx)</button></div>
        </form>
    </div>
@endsection
