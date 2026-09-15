@extends('layout.master')

@section('title', 'Recycle Bin - Daily Reports')

@section('content')
    <div class="w-full space-y-6">
        <div class="flex items-center justify-between p-6 bg-white border shadow-xs rounded-3xl border-slate-200/80">
            <div>
                <h1 class="text-2xl font-extrabold text-slate-900">Recycle Bin (Archived Reports)</h1>
                <p class="text-xs font-medium text-slate-500">Restore deleted data or delete them permanently.</p>
            </div>
            <a href="{{ route('daily_reports.index') }}"
                class="px-4 py-2.5 text-xs font-bold bg-slate-100 text-slate-600 rounded-xl hover:bg-slate-200">
                Back to Active Reports
            </a>
        </div>

        @if (session('success'))
            <div class="p-4 text-xs font-bold border text-emerald-800 border-emerald-200 bg-emerald-50 rounded-2xl">
                {{ session('success') }}
            </div>
        @endif

        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-extrabold uppercase text-slate-500 bg-slate-50 border-b border-slate-100">
                        <th class="px-6 py-4">Deleted Date</th>
                        <th class="px-6 py-4">Site & Report Date</th>
                        <th class="px-6 py-4">Notes</th>
                        <th class="px-6 py-4 text-center">Actions</th>
                    </tr>
                </thead>
                <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                    @forelse ($reports as $report)
                        <tr>
                            <td class="px-6 py-4 font-semibold text-slate-400">
                                {{ $report->deleted_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <strong>{{ $report->site->machine_name ?? '-' }}</strong><br>
                                <span class="text-slate-400">{{ $report->report_date->format('d M Y') }}</span>
                            </td>
                            <td class="max-w-xs px-6 py-4 truncate">
                                {{ $report->description }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Tombol Restore --}}
                                    <form action="{{ route('daily_reports.restore', $report->id) }}" method="POST">
                                        @csrf
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100">
                                            <i class="mr-1 fa-solid fa-rotate-left"></i> Restore
                                        </button>
                                    </form>

                                    {{-- Tombol Force Delete --}}
                                    <form action="{{ route('daily_reports.force_delete', $report->id) }}" method="POST"
                                        onsubmit="return confirm('Hapus PERMANEN data ini? Data tidak bisa dikembalikan lagi!')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="px-3 py-1.5 text-xs font-bold text-rose-700 bg-rose-50 border border-rose-200 rounded-xl hover:bg-rose-100">
                                            <i class="mr-1 fa-solid fa-ban"></i> Delete Permanently
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="p-8 font-bold text-center text-slate-400">
                                Recycle bin kosong. Tidak ada data yang dihapus.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="p-4 border-t border-slate-100">
                {{ $reports->links() }}
            </div>
        </div>
    </div>
@endsection
