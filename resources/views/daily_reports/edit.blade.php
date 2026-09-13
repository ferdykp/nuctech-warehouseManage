@extends('layout.master')

@section('title', 'Edit Daily Activity Report')

@section('content')
    <div class="w-full max-w-4xl mx-auto space-y-6">

        {{-- HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('daily_reports.index') }}" class="transition-colors hover:text-emerald-600">Daily
                            Activity Reports</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-emerald-600">Edit Report</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit Activity Report
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Update site inspection details and manage photo documentations.
                    </p>
                </div>
                <a href="{{ route('daily_reports.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i> Back
                </a>
            </div>
        </div>

        {{-- ALERTS --}}
        @if (session('error'))
            <div class="p-4 text-xs font-bold border text-rose-800 border-rose-200 bg-rose-50 rounded-2xl">
                {{ session('error') }}
            </div>
        @endif

        {{-- FORM CARD --}}
        <form action="{{ route('daily_reports.update', $report->id) }}" method="POST" enctype="multipart/form-data"
            class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            @csrf
            @method('PUT')

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Report Details</h2>
            </div>

            <div class="p-6 space-y-6 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- SITE LOCATION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Site <span class="text-rose-500">*</span>
                        </label>
                        @if (in_array(auth()->user()->role, ['superadmin', 'administration']))
                            <select name="site_id"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer"
                                required>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}"
                                        {{ old('site_id', $report->site_id) == $site->id ? 'selected' : '' }}>
                                        {{ $site->machine_name }} ({{ $site->branch->branch_name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" value="{{ $report->site_id }}">
                            <input type="text" disabled value="{{ $report->site->machine_name ?? '-' }}"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold bg-slate-100 border border-slate-200 rounded-xl text-slate-500 cursor-not-allowed">
                        @endif
                    </div>

                    {{-- REPORT DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Report Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="report_date"
                            value="{{ old('report_date', $report->report_date->format('Y-m-d')) }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer"
                            required>
                    </div>
                </div>

                {{-- LOG NOTES --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                        Log Notes / Description <span class="text-rose-500">*</span>
                    </label>
                    <textarea name="description" rows="5"
                        class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                        placeholder="Write activity descriptions or inspection details..." required>{{ old('description', $report->description) }}</textarea>
                </div>

                {{-- EXISTING PHOTOS --}}
                @if ($report->photos->count() > 0)
                    <div class="pt-4 space-y-3 border-t border-slate-100">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Existing Documentation Photos
                        </label>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($report->photos as $photo)
                                <div id="photo_card_{{ $photo->id }}"
                                    class="relative p-3 space-y-2 border border-slate-200/80 rounded-2xl bg-slate-50">
                                    <div class="relative overflow-hidden bg-white border group rounded-xl border-slate-100">
                                        <img src="{{ asset('storage/' . $photo->photo_path) }}"
                                            class="object-cover w-full h-36" />
                                        <button type="button" onclick="deleteExistingPhoto({{ $photo->id }})"
                                            class="absolute flex items-center justify-center text-xs text-white transition-all rounded-lg shadow-md cursor-pointer top-2 right-2 w-7 h-7 bg-rose-600 hover:bg-rose-700"
                                            title="Delete Photo">
                                            <i class="fa-solid fa-trash-can"></i>
                                        </button>
                                    </div>
                                    <input type="text" name="existing_captions[{{ $photo->id }}]"
                                        value="{{ $photo->caption }}" placeholder="Caption..."
                                        class="w-full px-3 py-1.5 text-xs border border-slate-200 rounded-lg bg-white outline-none focus:border-emerald-500 text-slate-700">
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                {{-- ADD NEW PHOTOS --}}
                <div class="pt-4 space-y-3 border-t border-slate-100">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Add New Photos (Optional)
                        </label>
                        <button type="button" onclick="addPhotoField()"
                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-all cursor-pointer">
                            <i class="fa-solid fa-plus"></i> Add More Photo
                        </button>
                    </div>

                    <div id="photos_container" class="space-y-3">
                        <div class="p-4 space-y-3 border border-slate-200/80 rounded-2xl bg-slate-50">
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Upload
                                        Photo</label>
                                    <input type="file" name="photos[]" accept="image/*"
                                        class="w-full text-xs cursor-pointer text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                                </div>
                                <div>
                                    <label
                                        class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Caption</label>
                                    <input type="text" name="captions[]" placeholder="Photo caption..."
                                        class="w-full px-3 py-2 text-xs bg-white border outline-none border-slate-200 rounded-xl focus:border-emerald-500 text-slate-700">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Fields marked with (<span class="text-rose-500">*</span>) are required.
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('daily_reports.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-md shadow-emerald-600/20 active:scale-95 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        function addPhotoField() {
            const container = document.getElementById('photos_container');
            const fieldGroup = document.createElement('div');
            fieldGroup.className = 'p-4 border border-slate-200/80 rounded-2xl bg-slate-50 space-y-3 relative';
            fieldGroup.innerHTML = `
                <button type="button" onclick="this.parentElement.remove()" class="absolute text-base font-bold cursor-pointer top-3 right-3 text-slate-400 hover:text-rose-600">&times;</button>
                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Upload Photo</label>
                        <input type="file" name="photos[]" accept="image/*"
                            class="w-full text-xs cursor-pointer text-slate-500 file:mr-3 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>
                    <div>
                        <label class="block text-[10px] font-extrabold uppercase tracking-wider text-slate-400 mb-1">Caption</label>
                        <input type="text" name="captions[]" placeholder="Photo caption..."
                            class="w-full px-3 py-2 text-xs bg-white border outline-none border-slate-200 rounded-xl focus:border-emerald-500 text-slate-700">
                    </div>
                </div>
            `;
            container.appendChild(fieldGroup);
        }

        function deleteExistingPhoto(photoId) {
            if (!confirm('Hapus foto dokumentasi ini?')) return;

            fetch(`/daily-reports/photos/${photoId}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    const card = document.getElementById(`photo_card_${photoId}`);
                    if (card) card.remove();
                })
                .catch(err => alert('Gagal menghapus foto.'));
        }
    </script>
@endpush
