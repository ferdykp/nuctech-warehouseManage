@extends('layout.master')

@section('title', 'New Reimbursement Claim')

@section('content')
    {{-- MAIN CONTAINER: Fills the viewport space using full-width flexible spacing --}}
    <div class="w-full px-6 py-6 mx-auto space-y-6 animate-fade-in">

        {{-- TOP ACTION BAR / HEADER --}}
        <div class="flex flex-col gap-4 pb-4 border-b border-slate-200/60 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <div class="flex items-center gap-2">
                    <span
                        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 mr-1.5 animate-pulse"></span>Operational Account
                    </span>
                    <span class="text-[11px] text-slate-400 font-bold uppercase tracking-wider">Submission Form</span>
                </div>
                <h2 class="text-2xl font-black tracking-tight text-slate-900">New Reimbursement Request</h2>
                <p class="text-xs font-medium text-slate-500">Fill in operational claim details and manage multi-page
                    document attachment structures in real-time.</p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('reimbursements.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-white border border-slate-200 rounded-xl shadow-sm hover:bg-slate-50 transition-all duration-200">
                    <i class="fa-solid fa-arrow-left text-[10px]"></i> Back to Directory
                </a>
            </div>
        </div>

        {{-- WORKSPACE SPLIT GRID SYSTEM --}}
        <form method="POST" action="{{ route('reimbursements.store') }}" enctype="multipart/form-data" id="claimForm"
            class="grid items-start grid-cols-1 gap-6 xl:grid-cols-12">
            @csrf
            <input type="hidden" name="excluded_pages" id="excludedPagesInput" value="[]">

            {{-- ── LEFT SECTOR: INPUT CONTROLS & SPECIFICATIONS (xl:col-span-5) ── --}}
            <div class="space-y-6 xl:col-span-5">
                <div
                    class="bg-white border border-slate-200/80 shadow-[0_4px_20px_rgba(0,0,0,0.01)] rounded-3xl p-6 space-y-6">

                    {{-- Sub-Section 1: Requester Details --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <span
                                class="flex items-center justify-center w-5 h-5 rounded-md bg-slate-900 text-white text-[10px] font-black">01</span>
                            <h3 class="text-xs font-black tracking-wider uppercase text-slate-700">Requester Information
                            </h3>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">Person
                                    Name</label>
                                <div class="relative group">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors pointer-events-none">
                                        <i class="text-xs fa-solid fa-user-tie"></i>
                                    </span>
                                    <input type="text" name="person_name" required placeholder="Enter employee full name"
                                        class="w-full py-3 pl-10 pr-4 text-xs font-semibold transition-all duration-150 border border-slate-200 bg-slate-50/20 rounded-xl focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800">
                                </div>
                            </div>
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">Date of
                                    Expense</label>
                                <div class="relative group">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors pointer-events-none">
                                        <i class="text-xs fa-solid fa-calendar"></i>
                                    </span>
                                    <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                                        class="w-full py-3 pl-10 pr-4 text-xs font-semibold transition-all duration-150 border border-slate-200 bg-slate-50/20 rounded-xl focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sub-Section 2: Financial & Allocations --}}
                    <div class="space-y-4">
                        <div class="flex items-center gap-2 pb-2 border-b border-slate-100">
                            <span
                                class="flex items-center justify-center w-5 h-5 rounded-md bg-slate-900 text-white text-[10px] font-black">02</span>
                            <h3 class="text-xs font-black tracking-wider uppercase text-slate-700">Financial Details &
                                Allocation</h3>
                        </div>
                        <div class="space-y-4">
                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">Expense
                                    Category</label>
                                <div class="relative group">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 group-focus-within:text-amber-600 transition-colors pointer-events-none">
                                        <i class="text-xs fa-solid fa-tags"></i>
                                    </span>
                                    <select name="category" id="categorySelect" onchange="handleCategoryChange()" required
                                        class="w-full py-3 pl-10 pr-10 text-xs font-bold transition-all duration-150 border appearance-none border-slate-200 bg-slate-50/20 rounded-xl focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800">
                                        <option value="office">Office Supplies / Others</option>
                                        <option value="transportation">Transportation</option>
                                        <option value="delivery">Delivery / Logistics</option>
                                    </select>
                                    <span
                                        class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 pointer-events-none">
                                        <i class="fa-solid fa-chevron-down text-[9px]"></i>
                                    </span>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">Total
                                    Claim Amount (IDR)</label>
                                <div class="relative group">
                                    <span
                                        class="absolute inset-y-0 left-0 flex items-center pl-4 text-xs font-black transition-colors text-slate-400 group-focus-within:text-amber-600">Rp</span>
                                    <input type="text" id="currencyMaskInput" required placeholder="e.g., 250.000"
                                        class="w-full py-3 pl-12 pr-4 text-xs font-black transition-all duration-150 border border-slate-200 bg-slate-50/20 rounded-xl focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-800">
                                    <input type="hidden" name="amount" id="actualAmountInput">
                                </div>
                            </div>

                            {{-- DYNAMIC ROUTING FIELDS (Fades in smoothly via JS) --}}
                            <div id="routingFields"
                                class="hidden grid-cols-1 gap-4 p-4 transition-all duration-300 border border-slate-100 bg-slate-50 rounded-2xl animate-fade-in">
                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    <div class="space-y-1.5">
                                        <label
                                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">From
                                            (Origin)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i
                                                    class="fa-solid fa-circle-dot text-[10px] text-teal-500"></i></span>
                                            <input type="text" name="from_location" id="fromLocation"
                                                placeholder="Origin location"
                                                class="w-full pl-8 pr-3 py-2.5 text-xs font-semibold bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-amber-500">
                                        </div>
                                    </div>
                                    <div class="space-y-1.5">
                                        <label
                                            class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">To
                                            (Destination)</label>
                                        <div class="relative">
                                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400"><i
                                                    class="fa-solid fa-location-dot text-[10px] text-rose-500"></i></span>
                                            <input type="text" name="to_location" id="toLocation"
                                                placeholder="Arrival target"
                                                class="w-full pl-8 pr-3 py-2.5 text-xs font-semibold bg-white border border-slate-200 rounded-xl focus:outline-none focus:border-amber-500">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider">
                                    Invoice Number</label>
                                <textarea name="comment" rows="3" placeholder="Invoice Number"
                                    class="w-full px-4 py-3 text-xs font-medium transition-all duration-150 border resize-none border-slate-200 bg-slate-50/20 rounded-xl focus:outline-none focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 text-slate-700"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- WORKSPACE BOTTOM ACTION FOOTER PANEL --}}
                <div class="flex items-center gap-3 p-4 shadow-md bg-slate-900 rounded-2xl">
                    <button type="submit"
                        class="flex-1 inline-flex items-center justify-center gap-2 px-6 py-3.5 text-xs font-black tracking-wider text-white uppercase bg-amber-600 hover:bg-amber-500 rounded-xl shadow-lg shadow-amber-600/10 active:scale-[0.99] transition-all">
                        Submit Claim Request <i class="fa-solid fa-arrow-right text-[10px]"></i>
                    </button>
                    <a href="{{ route('reimbursements.index') }}"
                        class="px-5 py-3.5 text-xs font-black tracking-wider text-center text-slate-400 hover:text-white bg-slate-800 rounded-xl transition-all">
                        Discard
                    </a>
                </div>
            </div>

            {{-- ── RIGHT SECTOR: INTERACTIVE RECEIPT MANAGER SANDBOX WORKSPACE (xl:col-span-7) ── --}}
            <div class="space-y-4 xl:col-span-7">
                <div
                    class="bg-white border border-slate-200/80 shadow-[0_4px_20px_rgba(0,0,0,0.01)] rounded-3xl p-6 space-y-4">

                    <div class="flex items-center justify-between pb-2 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex items-center justify-center w-5 h-5 rounded-md bg-slate-900 text-white text-[10px] font-black">03</span>
                            <h3 class="text-xs font-black tracking-wider uppercase text-slate-700">Invoice Documentation &
                                Sandbox Editor</h3>
                        </div>
                        <span id="pageCountBadge"
                            class="hidden text-[10px] font-black text-amber-600 bg-amber-50 px-2.5 py-0.5 rounded-full border border-amber-200/60 animate-fade-in"></span>
                    </div>

                    {{-- FILE INPUT DRAG & DROP ZONE --}}
                    <div id="dropzone"
                        class="relative p-8 text-center transition-all duration-300 border-2 border-dashed cursor-pointer rounded-2xl bg-slate-50/30 border-slate-200 hover:border-amber-500 hover:bg-amber-50/10 group">
                        <input type="file" name="receipt_attachment" id="fileInput" required
                            accept="image/*,application/pdf"
                            class="absolute inset-0 z-20 w-full h-full opacity-0 cursor-pointer">
                        <div id="dropzoneContent" class="space-y-2 pointer-events-none">
                            <div
                                class="inline-flex items-center justify-center w-12 h-12 transition-all duration-300 bg-white border shadow-sm rounded-xl border-slate-100 text-slate-400 group-hover:text-amber-500 group-hover:scale-105">
                                <i class="text-xl fa-solid fa-cloud-arrow-up"></i>
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-700">Drag and drop invoice file here, or <span
                                        class="underline text-amber-600">browse files</span></p>
                                <p class="text-[10px] text-slate-400 font-medium mt-0.5">Supports high-res Images and
                                    multi-page PDF documents up to 4MB</p>
                            </div>
                        </div>
                    </div>

                    {{-- FILE LOADING INDICATOR ASSET --}}
                    <div id="rendering-loader"
                        class="hidden items-center justify-center gap-2.5 py-6 border border-dashed border-slate-200 bg-slate-50/50 rounded-2xl text-xs font-bold text-slate-500 animate-pulse uppercase tracking-wider">
                        <i class="text-sm fa-solid fa-spinner animate-spin text-amber-500"></i> PDF.js is splitting and
                        rendering document pages...
                    </div>

                    {{-- DYNAMIC PREVIEW SANDBOX (Splits PDF into modular tiles to maximize workspace resolution) --}}
                    <div id="filePreviewContainer"
                        class="hidden grid-cols-1 gap-4 p-4 overflow-y-auto border md:grid-cols-2 bg-slate-50 border-slate-100 rounded-2xl"
                        style="max-height: 540px;">
                        {{-- Halaman PDF / File Gambar akan disuntikkan secara dinamis di sini --}}
                    </div>

                    {{-- INTERACTIVE PLACEHOLDER --}}
                    <div id="emptyPreviewPlaceholder"
                        class="flex flex-col items-center justify-center py-20 border border-dashed border-slate-200/80 bg-slate-50/20 rounded-2xl text-slate-400">
                        <i class="mb-2 text-3xl fa-solid fa-paste text-slate-200"></i>
                        <p class="text-xs font-bold text-slate-400">No attachments uploaded yet</p>
                        <p class="text-[10px] text-slate-400/80">Interactive sandbox document previews will be generated in
                            this workspace panel</p>
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

        // DROPZONE VISUAL ACCENTS
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

            // Reset sandbox layers
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

        function renderImagePreview(file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                renderingLoader.classList.add('hidden');
                previewContainer.classList.replace('hidden', 'grid');
                pageCountBadge.classList.replace('hidden', 'inline-flex');
                pageCountBadge.innerHTML = `<i class="fa-solid fa-image mr-1 mt-0.5"></i> Image Asset`;

                previewContainer.innerHTML = `
                    <div class="relative group md:col-span-2 overflow-hidden border border-slate-200 bg-white p-1.5 rounded-2xl shadow-sm">
                        <img src="${e.target.result}" class="w-full h-auto max-h-[460px] object-contain rounded-xl bg-slate-50">
                        <div class="absolute top-3 left-3 bg-slate-900/90 text-white text-[8px] px-2 py-1 rounded-md font-bold uppercase tracking-wider">Single File</div>
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
                pageCountBadge.innerHTML =
                    `<i class="fa-solid fa-file-pdf mr-1 mt-0.5"></i> ${pdf.numPages} Pages Detected`;

                for (let i = 1; i <= pdf.numPages; i++) {
                    const page = await pdf.getPage(i);
                    const unscaledViewport = page.getViewport({
                        scale: 1
                    });

                    // High performance thumbnail processing parameters
                    const scale = 250 / unscaledViewport.width;
                    const viewport = page.getViewport({
                        scale: scale
                    });

                    const canvas = document.createElement('canvas');
                    const context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    canvas.className = "w-full h-auto rounded-xl";

                    await page.render({
                        canvasContext: context,
                        viewport: viewport
                    }).promise;

                    const wrapper = document.createElement('div');
                    wrapper.className =
                        "relative border border-slate-200 rounded-2xl p-2 bg-white shadow-sm hover:shadow-md transition-all duration-200";
                    wrapper.id = `page-wrapper-${i}`;
                    wrapper.innerHTML = `
                        <div class="absolute top-3 left-3 z-10 bg-slate-900 text-white text-[8px] px-2 py-0.5 rounded-md font-black uppercase">Page ${i}</div>
                        <button type="button" onclick="togglePage(${i})" class="absolute z-10 flex items-center justify-center w-6 h-6 transition-all duration-150 bg-white border rounded-full shadow-sm text-slate-400 border-slate-200 top-3 right-3 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200" title="Exclude page">
                            <i class="fa-solid fa-trash-can text-[9px]"></i>
                        </button>
                        <div class="overflow-hidden page-canvas-container rounded-xl bg-slate-50">
                            <img src="${canvas.toDataURL()}" class="object-contain w-full h-auto">
                        </div>
                        <div id="overlay-${i}" class="absolute inset-0 bg-white/95 backdrop-blur-[1px] hidden flex-col gap-2 items-center justify-center rounded-2xl animate-fade-in z-20">
                            <div class="flex items-center justify-center border rounded-full w-7 h-7 bg-rose-50 border-rose-100 text-rose-500"><i class="fa-solid fa-eye-slash text-[10px]"></i></div>
                            <span class="text-rose-600 font-black text-[9px] uppercase tracking-wider">Page Excluded</span>
                            <button type="button" onclick="togglePage(${i})" class="mt-1 bg-slate-900 hover:bg-slate-800 text-white text-[8px] px-2.5 py-1 rounded-lg font-bold uppercase transition-colors">Restore Page</button>
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
                overlay.classList.replace('hidden', 'flex');
                wrapper.classList.add('border-rose-200', 'bg-rose-50/20');
            } else {
                excludedPages.splice(idx, 1);
                overlay.classList.replace('flex', 'hidden');
                wrapper.classList.remove('border-rose-200', 'bg-rose-50/20');
            }
            excludedInput.value = JSON.stringify(excludedPages);
        }

        function handleCategoryChange() {
            const category = document.getElementById('categorySelect').value;
            const routingFields = document.getElementById('routingFields');
            const fromInput = document.getElementById('fromLocation');
            const toInput = document.getElementById('toLocation');

            if (category === 'transportation' || category === 'delivery') {
                routingFields.classList.replace('hidden', 'grid');
                fromInput.setAttribute('required', 'required');
                toInput.setAttribute('required', 'required');
            } else {
                routingFields.classList.replace('grid', 'hidden');
                fromInput.removeAttribute('required');
                toInput.removeAttribute('required');
                fromInput.value = '';
                toInput.value = '';
            }
        }

        const maskInput = document.getElementById('currencyMaskInput');
        const actualInput = document.getElementById('actualAmountInput');

        maskInput?.addEventListener('input', function(e) {
            let rawValue = e.target.value.replace(/\D/g, '');
            if (rawValue === '') {
                maskInput.value = '';
                actualInput.value = '';
                return;
            }
            actualInput.value = rawValue;
            e.target.value = new Intl.NumberFormat('id-ID').format(rawValue);
        });

        document.addEventListener("DOMContentLoaded", handleCategoryChange);
    </script>

    <style>
        .animate-fade-in {
            animation: fadeIn 0.35s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(6px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endpush
