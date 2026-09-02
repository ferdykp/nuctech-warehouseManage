@extends('layout.master')

@section('title', 'Create Daily Activity Report')

@section('content')
    <div class="w-full space-y-6" x-data="dailyReportForm()">

        {{-- 1. HEADER CARD (FULL WIDTH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('daily_reports.index') }}" class="transition-colors hover:text-emerald-600">Daily
                            Activity Logs</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Create Report</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        New Daily Activity Report
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Record daily machine site activities and attached photo documentations.
                    </p>
                </div>
                <a href="{{ route('daily_reports.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD (FULL WIDTH) --}}
        <form action="{{ route('daily_reports.store') }}" method="POST" enctype="multipart/form-data"
            class="w-full overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            @csrf

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Report Details</h2>
            </div>

            <div class="p-6 space-y-6 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- MACHINE SITE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Site <span class="text-rose-500">*</span>
                        </label>
                        <select name="site_id" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                            <option value="">-- Select Machine Site --</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site->id }}"
                                    {{ old('site_id', auth()->user()->site_id) == $site->id ? 'selected' : '' }}>
                                    {{ $site->machine_name }} ({{ $site->location ?? 'Site Location' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- REPORT DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Report Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="report_date" value="{{ old('report_date', date('Y-m-d')) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                    </div>

                    {{-- CATATAN LAPORAN HARIAN --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Daily Report Notes <span class="text-rose-500">*</span>
                        </label>
                        <textarea name="description" rows="6" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Tuliskan catatan kegiatan harian di lapangan..."></textarea>
                    </div>
                </div>

                {{-- DYNAMIC PHOTO ATTACHMENT SECTION (FULL WIDTH) --}}
                <div class="pt-6 space-y-4 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Photo Documentations
                                (Optional)</h3>
                            <p class="text-[11px] font-medium text-slate-400">Attach photos of activity with optional
                                captions.</p>
                        </div>
                        <button type="button" @click="addPhotoRow()"
                            class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-all cursor-pointer">
                            <i class="fa-solid fa-plus"></i> Add Photo
                        </button>
                    </div>

                    <div class="space-y-3">
                        <template x-for="(row, index) in photos" :key="index">
                            <div
                                class="flex flex-col items-start w-full gap-4 p-4 border border-slate-200/80 rounded-2xl bg-slate-50/50 sm:flex-row sm:items-center">
                                <div class="w-full space-y-1 sm:w-1/3">
                                    <input type="file" name="photos[]" accept="image/*"
                                        class="block w-full text-xs bg-white border cursor-pointer text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-600 file:text-white hover:file:bg-emerald-700 border-slate-200 rounded-xl">
                                </div>
                                <div class="flex items-center w-full gap-2 sm:w-2/3">
                                    <input type="text" name="captions[]"
                                        placeholder="Photo caption (e.g., Kondisi mesin jam 09:00, Ganti oli)"
                                        class="w-full px-3.5 py-2 text-xs font-medium border border-slate-200 rounded-xl outline-none focus:border-emerald-500 bg-white text-slate-800">
                                    <button type="button" @click="removePhotoRow(index)" x-show="photos.length > 1"
                                        class="p-2 transition-colors cursor-pointer text-rose-600 hover:bg-rose-50 rounded-xl shrink-0"
                                        title="Remove Photo">
                                        <i class="fa-solid fa-trash-can"></i>
                                    </button>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            {{-- FORM FOOTER --}}
            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <span class="text-xs font-medium text-slate-400">Fields marked with (<span class="text-rose-500">*</span>)
                    are mandatory.</span>
                <div class="flex items-center gap-3">
                    <a href="{{ route('daily_reports.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Activity Report
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function dailyReportForm() {
            return {
                photos: [{
                    id: 1
                }],
                addPhotoRow() {
                    this.photos.push({
                        id: Date.now()
                    });
                },
                removePhotoRow(index) {
                    if (this.photos.length > 1) {
                        this.photos.splice(index, 1);
                    }
                }
            }
        }
    </script>
@endpush
