@extends('layout.master')

@section('title', 'Ajukan Reimbursement')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('reimbursements.index') }}"
                            class="transition-colors hover:text-amber-600">Reimbursement</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Pengajuan Klaim Baru</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Ajukan Reimbursement
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Isi rincian biaya operasional dan unggah bukti nota.
                    </p>
                </div>
                <a href="{{ route('reimbursements.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Kembali</span>
                </a>
            </div>
        </div>

        {{-- ALERT ERROR VALIDASI --}}
        @if ($errors->any())
            <div class="p-4 space-y-1 text-xs border text-rose-800 border-rose-200 bg-rose-50 rounded-2xl">
                <div class="flex items-center gap-2 font-extrabold">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Periksa isian berikut:
                </div>
                <ul class="list-disc pl-5 font-semibold space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- 2. FORM WORKSPACE CARD --}}
        <form method="POST" action="{{ route('reimbursements.store') }}" enctype="multipart/form-data" id="claimForm"
            class="grid items-start grid-cols-1 gap-6 xl:grid-cols-12">
            @csrf
            <input type="hidden" name="excluded_pages" id="excludedPagesInput" value="[]">

            {{-- LEFT SECTOR --}}
            <div class="space-y-6 xl:col-span-5">
                <div class="p-6 space-y-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">

                    {{-- Section 1: Requester --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <span
                                class="flex items-center justify-center w-6 h-6 rounded-lg bg-slate-900 text-white text-[10px] font-black">01</span>
                            <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Data Pengaju
                            </h3>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Diajukan oleh
                                    <span class="text-rose-500">*</span></label>
                                <div class="relative flex items-center">
                                    <span
                                        class="absolute left-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="text-xs fa-solid fa-user"></i>
                                    </span>
                                    <select name="person_name" required
                                        class="w-full py-2.5 pl-10 pr-10 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none appearance-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                                        <option value="" disabled {{ old('person_name') ? '' : 'selected' }}>Pilih
                                            Karyawan...</option>
                                        @foreach ($employees as $employee)
                                            <option value="{{ $employee->name }}"
                                                {{ old('person_name') == $employee->name ? 'selected' : '' }}>
                                                {{ $employee->name }}
                                                {{ $employee->position ? '(' . $employee->position . ')' : '' }}
                                                @if (auth()->user()?->role === 'superadmin' && $employee->site)
                                                    - [{{ $employee->site->machine_name }}]
                                                @endif
                                            </option>
                                        @endforeach
                                    </select>
                                    <span
                                        class="absolute right-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Tanggal Pengeluaran <span class="text-rose-500">*</span></label>
                                <div class="relative flex items-center">
                                    <span
                                        class="absolute left-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="text-xs fa-solid fa-calendar-days"></i>
                                    </span>
                                    <input type="date" name="date" required value="{{ old('date', date('Y-m-d')) }}"
                                        class="w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Section 2: Financials --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2.5 pb-3 border-b border-slate-100">
                            <span
                                class="flex items-center justify-center w-6 h-6 rounded-lg bg-slate-900 text-white text-[10px] font-black">02</span>
                            <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Rincian Biaya</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Kategori Biaya <span class="text-rose-500">*</span></label>
                                <div class="relative flex items-center">
                                    <span
                                        class="absolute left-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="text-xs fa-solid fa-layer-group"></i>
                                    </span>
                                    <select name="category" id="categorySelect" onchange="handleCategoryChange()" required
                                        class="w-full py-2.5 pl-10 pr-10 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none appearance-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                                        <option value="office" {{ old('category') == 'office' ? 'selected' : '' }}>Perlengkapan Kantor / Lainnya</option>
                                        <option value="transportation"
                                            {{ old('category') == 'transportation' ? 'selected' : '' }}>Transportasi
                                        </option>
                                        <option value="delivery" {{ old('category') == 'delivery' ? 'selected' : '' }}>
                                            Pengiriman / Logistik</option>
                                    </select>
                                    <span
                                        class="absolute right-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="fa-solid fa-chevron-down text-[10px]"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Total Klaim (Rupiah) <span class="text-rose-500">*</span></label>
                                <div class="relative flex items-center">
                                    <span
                                        class="absolute left-3.5 z-10 flex items-center justify-center text-xs font-black text-slate-400 pointer-events-none">Rp</span>
                                    <input type="text" id="currencyMaskInput" required placeholder="Contoh: 250.000"
                                        class="w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-black border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                                    <input type="hidden" name="amount" id="actualAmountInput"
                                        value="{{ old('amount') }}">
                                </div>
                            </div>

                            {{-- DYNAMIC ROUTING --}}
                            <div id="routingFields"
                                class="hidden grid-cols-1 gap-3 p-4 border border-slate-200/80 bg-slate-50/50 rounded-2xl">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1">
                                        <label
                                            class="block text-[10px] font-bold uppercase text-slate-500 tracking-wider">Dari (Asal)</label>
                                        <div class="relative flex items-center">
                                            <span
                                                class="absolute z-10 flex items-center justify-center pointer-events-none left-3 text-slate-400">
                                                <i class="fa-solid fa-circle-dot text-[11px] text-emerald-500"></i>
                                            </span>
                                            <input type="text" name="from_location" id="fromLocation"
                                                value="{{ old('from_location') }}" placeholder="Lokasi asal..."
                                                class="w-full py-2 pl-8 pr-3 text-xs font-semibold bg-white border outline-none border-slate-200 rounded-xl focus:border-amber-500">
                                        </div>
                                    </div>
                                    <div class="space-y-1">
                                        <label
                                            class="block text-[10px] font-bold uppercase text-slate-500 tracking-wider">Ke (Tujuan)</label>
                                        <div class="relative flex items-center">
                                            <span
                                                class="absolute z-10 flex items-center justify-center pointer-events-none left-3 text-slate-400">
                                                <i class="fa-solid fa-location-dot text-[11px] text-rose-500"></i>
                                            </span>
                                            <input type="text" name="to_location" id="toLocation"
                                                value="{{ old('to_location') }}" placeholder="Lokasi tujuan..."
                                                class="w-full py-2 pl-8 pr-3 text-xs font-semibold bg-white border outline-none border-slate-200 rounded-xl focus:border-amber-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Nomor Nota / Keterangan</label>
                                <div class="relative">
                                    <span
                                        class="absolute top-3 left-3.5 z-10 flex items-center justify-center text-slate-400 pointer-events-none">
                                        <i class="text-xs fa-solid fa-receipt"></i>
                                    </span>
                                    <textarea name="comment" rows="2" placeholder="Nomor nota atau keterangan..."
                                        class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">{{ old('comment') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- SUBMIT FOOTER --}}
                <div class="flex items-center gap-3 p-4 shadow-md bg-slate-900 rounded-2xl">
                    <button type="submit"
                        class="flex-1 py-3 text-xs font-bold tracking-wider text-white uppercase transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-[0.98] cursor-pointer">
                        Ajukan Klaim <i class="fa-solid fa-arrow-right text-[10px] ml-1"></i>
                    </button>
                    <a href="{{ route('reimbursements.index') }}"
                        class="px-4 py-3 text-xs font-bold tracking-wider uppercase transition-colors text-slate-400 hover:text-white bg-slate-800 rounded-xl">
                        Batal
                    </a>
                </div>
            </div>

            {{-- RIGHT SECTOR --}}
            <div class="space-y-4 xl:col-span-7">
                <div class="p-6 space-y-4 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">

                    <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                        <div class="flex items-center gap-2.5">
                            <span
                                class="flex items-center justify-center w-6 h-6 rounded-lg bg-slate-900 text-white text-[10px] font-black">03</span>
                            <h3 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Bukti Nota</h3>
                        </div>
                        <span id="pageCountBadge"
                            class="hidden text-[10px] font-bold text-amber-800 bg-amber-50 px-2.5 py-1 rounded-full border border-amber-200"></span>
                    </div>

                    {{-- FILE INPUT --}}
                    <div id="dropzone"
                        class="relative p-8 text-center transition-all border-2 border-dashed cursor-pointer rounded-2xl bg-slate-50/50 border-slate-200 hover:border-amber-500 group">
                        <input type="file" name="receipt_attachment" id="fileInput" required
                            accept="image/*,application/pdf"
                            class="absolute inset-0 z-20 w-full h-full opacity-0 cursor-pointer">
                        <div id="dropzoneContent" class="space-y-2 pointer-events-none">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 transition-all bg-white border shadow-2xs rounded-2xl border-slate-200 text-slate-400 group-hover:text-amber-600">
                                <i class="text-xl fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-800">Tarik file nota ke sini, atau <span
                                        class="underline text-amber-600">pilih file</span></p>
                                <p class="text-[10px] text-slate-400 mt-0.5">Mendukung gambar dan PDF beberapa halaman (maksimal 4 MB)</p>
                            </div>
                        </div>
                    </div>

                    {{-- LOADING INDICATOR --}}
                    <div id="rendering-loader"
                        class="items-center justify-center hidden gap-2 py-6 text-xs font-bold tracking-wider uppercase border border-dashed border-slate-200 bg-slate-50/50 rounded-2xl text-slate-500 animate-pulse">
                        <i class="text-base fa-solid fa-spinner animate-spin text-amber-500"></i> Memuat halaman PDF...
                    </div>

                    {{-- PREVIEW CONTAINER --}}
                    <div id="filePreviewContainer"
                        class="hidden grid-cols-1 gap-3 p-4 overflow-y-auto border md:grid-cols-2 bg-slate-50/50 border-slate-200/80 rounded-2xl"
                        style="max-height: 500px;">
                    </div>

                    {{-- PLACEHOLDER --}}
                    <div id="emptyPreviewPlaceholder"
                        class="flex flex-col items-center justify-center py-16 border border-dashed border-slate-200 bg-slate-50/50 rounded-2xl text-slate-400">
                        <i class="mb-2 text-3xl opacity-50 fa-solid fa-paste"></i>
                        <p class="text-xs font-bold text-slate-600">Belum ada lampiran</p>
                        <p class="text-[10px] text-slate-400 mt-0.5">Pratinjau akan ditampilkan di area ini</p>
                    </div>

                </div>
            </div>

        </form>
    </div>
@endsection

@push('scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const fileInput = document.getElementById('fileInput');
        const dropzone = document.getElementById('dropzone');
        const previewContainer = document.getElementById('filePreviewContainer');
        const placeholder = document.getElementById('emptyPreviewPlaceholder');
        const pageCountBadge = document.getElementById('pageCountBadge');
        const renderingLoader = document.getElementById('rendering-loader');
        const excludedInput = document.getElementById('excludedPagesInput');
        let excludedPages = [];

        if (fileInput && dropzone) {
            ['dragenter', 'dragover'].forEach(eventName => {
                fileInput.addEventListener(eventName, () => {
                    dropzone.classList.add('border-amber-500', 'bg-amber-50/20');
                }, false);
            });
            ['dragleave', 'drop'].forEach(eventName => {
                fileInput.addEventListener(eventName, () => {
                    dropzone.classList.remove('border-amber-500', 'bg-amber-50/20');
                }, false);
            });

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (!file) return;

                previewContainer.innerHTML = '';
                previewContainer.classList.add('hidden');
                placeholder.classList.add('hidden');
                pageCountBadge.classList.add('hidden');
                excludedPages = [];
                excludedInput.value = JSON.stringify(excludedPages);

                if (file.type === 'application/pdf') {
                    renderingLoader.classList.replace('hidden', 'flex');
                    renderPdfPreview(file);
                } else if (file.type.startsWith('image/')) {
                    renderImagePreview(file);
                }
            });
        }

        function renderImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                renderingLoader.classList.add('hidden');
                previewContainer.classList.replace('hidden', 'grid');
                pageCountBadge.classList.replace('hidden', 'inline-flex');
                pageCountBadge.innerHTML = `<i class="fa-solid fa-image mr-1 mt-0.5"></i> File Gambar`;

                previewContainer.innerHTML = `
                    <div class="relative p-2 overflow-hidden bg-white border md:col-span-2 border-slate-200 rounded-xl shadow-2xs">
                        <img src="${e.target.result}" class="w-full h-auto max-h-[400px] object-contain rounded-lg bg-slate-50">
                        <div class="absolute top-3 left-3 bg-slate-900 text-white text-[8px] px-2 py-0.5 rounded-md font-bold uppercase">Satu Gambar</div>
                    </div>
                `;
            }
            reader.readAsDataURL(file);
        }

        async function renderPdfPreview(file) {
            try {
                const arrayBuffer = await file.arrayBuffer();
                const pdf = await pdfjsLib.getDocument({
                    data: arrayBuffer
                }).promise;

                previewContainer.classList.replace('hidden', 'grid');
                pageCountBadge.classList.replace('hidden', 'inline-flex');
                pageCountBadge.innerHTML = `<i class="fa-solid fa-file-pdf mr-1 mt-0.5"></i> ${pdf.numPages} Halaman`;

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const unscaledViewport = page.getViewport({
                        scale: 1
                    });
                    const scale = 250 / unscaledViewport.width;
                    const viewport = page.getViewport({
                        scale: scale
                    });

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.className = "w-full h-auto rounded-lg";

                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;

                    const wrapper = document.createElement('div');
                    wrapper.className =
                        "relative border border-slate-200 rounded-xl p-2 bg-white shadow-2xs transition-all";
                    wrapper.id = `page-wrapper-${i}`;
                    wrapper.innerHTML = `
                        <div class="absolute top-3 left-3 z-10 bg-slate-900 text-white text-[8px] px-2 py-0.5 rounded-md font-bold uppercase">Halaman ${i}</div>
                        <button type="button" onclick="togglePage(${i})" class="absolute z-10 flex items-center justify-center w-6 h-6 transition-colors bg-white border rounded-full cursor-pointer border-slate-200 shadow-2xs text-slate-400 top-3 right-3 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200" title="Kecualikan Halaman">
                            <i class="fa-solid fa-trash-can text-[9px]"></i>
                        </button>
                        <div class="overflow-hidden rounded-lg bg-slate-50">
                            <img src="${canvas.toDataURL()}" class="object-contain w-full h-auto">
                        </div>
                        <div id="overlay-${i}" class="absolute inset-0 bg-white/95 hidden flex-col gap-1.5 items-center justify-center rounded-xl z-20">
                            <div class="flex items-center justify-center w-6 h-6 border rounded-full bg-rose-50 border-rose-200 text-rose-500"><i class="fa-solid fa-eye-slash text-[9px]"></i></div>
                            <span class="text-rose-600 font-bold text-[9px] uppercase">Halaman Dikecualikan</span>
                            <button type="button" onclick="togglePage(${i})" class="mt-1 bg-slate-900 hover:bg-slate-800 text-white text-[8px] px-2 py-1 rounded-md font-bold uppercase cursor-pointer">Restore Page</button>
                        </div>
                    `;
                    previewContainer.appendChild(wrapper);
                }
            } catch (err) {
                console.error("PDF Parsing Exception:", err);
            } finally {
                renderingLoader.classList.replace('flex', 'hidden');
            }
        }

        function togglePage(pageNum) {
            const overlay = document.getElementById(`overlay-${pageNum}`);
            const wrapper = document.getElementById(`page-wrapper-${pageNum}`);
            const idx = excludedPages.indexOf(pageNum);

            if (idx === -1) {
                excludedPages.push(pageNum);
                overlay?.classList.replace('hidden', 'flex');
                wrapper?.classList.add('border-rose-200', 'bg-rose-50/20');
            } else {
                excludedPages.splice(idx, 1);
                overlay?.classList.replace('flex', 'hidden');
                wrapper?.classList.remove('border-rose-200', 'bg-rose-50/20');
            }
            excludedInput.value = JSON.stringify(excludedPages);
        }

        function handleCategoryChange() {
            const categorySelect = document.getElementById('categorySelect');
            if (!categorySelect) return;

            const category = categorySelect.value;
            const routingFields = document.getElementById('routingFields');
            const fromInput = document.getElementById('fromLocation');
            const toInput = document.getElementById('toLocation');

            if (category === 'transportation' || category === 'delivery') {
                routingFields?.classList.replace('hidden', 'grid');
                fromInput?.setAttribute('required', 'required');
                toInput?.setAttribute('required', 'required');
            } else {
                routingFields?.classList.replace('grid', 'hidden');
                fromInput?.removeAttribute('required');
                toInput?.removeAttribute('required');
                if (fromInput) fromInput.value = '';
                if (toInput) toInput.value = '';
            }
        }

        const maskInput = document.getElementById('currencyMaskInput');
        const actualInput = document.getElementById('actualAmountInput');

        maskInput?.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/\D/g, '');
            if (rawValue === '') {
                maskInput.value = '';
                if (actualInput) actualInput.value = '';
                return;
            }
            if (actualInput) actualInput.value = rawValue;
            e.target.value = new Intl.NumberFormat('id-ID').format(rawValue);
        });

        document.addEventListener("DOMContentLoaded", function() {
            handleCategoryChange();

            if (actualInput && actualInput.value) {
                if (maskInput) maskInput.value = new Intl.NumberFormat('id-ID').format(actualInput.value);
            }
        });
    </script>
@endpush
