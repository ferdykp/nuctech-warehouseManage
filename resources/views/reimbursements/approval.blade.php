@extends('layout.master')

@section('title', 'Review Claim #' . $reimbursement->id)

@section('content')
    {{-- LOGIKA WORKFLOW: AMBIL STATUS & CEK HAK AKSES USER UNTUK TTD --}}
    @php
        $canSign = false;
        $currentRole = strtolower(auth()->user()->role ?? 'admin_site');
        $myId = auth()->id();

        // 1. Staff mengawali TTD dokumennya sendiri
        if ($reimbursement->status == 'pending' && $reimbursement->user_id == $myId) {
            $canSign = true;
        }
        // 2. Jatah Team Leader (bisa berkas kiriman staff, atau berkas klaim buatannya sendiri)
        elseif ($reimbursement->status == 'pending_leader' && $currentRole == 'team_leader') {
            $canSign = true;
        }
        // 3. Jatah Station Master
        elseif ($reimbursement->status == 'pending_station' && $currentRole == 'station_master') {
            $canSign = true;
        }
        // 4. Jatah Manager (Final)
        elseif ($reimbursement->status == 'pending_manager' && $currentRole == 'manager') {
            $canSign = true;
        }
    @endphp

    <div class="w-full px-6 py-8 pb-10 space-y-6">

        {{-- BACK TO INDEX HEADER --}}
        <div class="flex flex-col gap-4 px-2 sm:flex-row sm:items-center sm:justify-between">
            <div class="space-y-1">
                <a href="{{ route('reimbursements.index') }}"
                    class="inline-flex items-center gap-2 mb-1 text-xs font-bold transition-colors text-slate-400 hover:text-amber-600">
                    <i class="fa-solid fa-arrow-left"></i> Back to Claim Logs
                </a>
                <h2 class="text-2xl font-black tracking-tighter text-slate-800 flex items-center gap-2.5">
                    <div class="w-3 h-3 rounded-full bg-amber-500 @if ($reimbursement->status != 'approved' && $reimbursement->status != 'rejected') animate-pulse @endif">
                    </div>
                    Review & Digital Sign Claim #{{ $reimbursement->id }}
                </h2>
            </div>

            {{-- BADGE STATUS BERJALAN SAAT INI --}}
            <div>
                @if ($reimbursement->status == 'approved')
                    <span
                        class="px-4 py-2 text-xs font-black tracking-wider uppercase border text-emerald-700 bg-emerald-50 border-emerald-100 rounded-xl">Approved
                        / Final</span>
                @elseif($reimbursement->status == 'rejected')
                    <span
                        class="px-4 py-2 text-xs font-black tracking-wider uppercase border text-rose-700 bg-rose-50 border-rose-100 rounded-xl">Rejected</span>
                @else
                    <span
                        class="px-4 py-2 text-xs font-black tracking-wider uppercase border text-amber-700 bg-amber-50 border-amber-100 rounded-xl animate-pulse">
                        @if ($reimbursement->status == 'pending')
                            Waiting Staff Sign
                        @elseif($reimbursement->status == 'pending_leader')
                            Waiting Leader Sign
                        @elseif($reimbursement->status == 'pending_station')
                            Waiting Station Master Sign
                        @elseif($reimbursement->status == 'pending_manager')
                            Waiting Manager Sign
                        @endif
                    </span>
                @endif
            </div>
        </div>

        {{-- MAIN 2-COLUMN WORKSPACE --}}
        <div class="grid items-start grid-cols-1 gap-8 lg:grid-cols-5">

            {{-- SISI KIRI: PANEL DATA & CANVAS TTD (2/5) --}}
            <div class="w-full space-y-6 lg:col-span-2">

                {{-- DATA SPESIFIKASI KLAIM --}}
                <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] p-6 space-y-4">
                    <h4
                        class="flex items-center gap-2 pb-3 text-xs font-black tracking-wider uppercase border-b text-slate-400 border-slate-50">
                        <i class="text-sm fa-solid fa-receipt text-amber-500"></i> Claim Specifications
                    </h4>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Person
                                Name</span>
                            <span
                                class="font-bold text-slate-700 text-sm block mt-0.5">{{ $reimbursement->person_name }}</span>
                        </div>
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Category</span>
                            <span
                                class="px-2.5 py-1 inline-block text-[10px] font-black rounded-lg bg-amber-50 text-amber-800 uppercase mt-0.5 tracking-wide border border-amber-100/50">
                                {{ $reimbursement->category }}
                            </span>
                        </div>
                    </div>

                    @if (in_array($reimbursement->category, ['transportation', 'delivery']))
                        <div class="pt-3 text-xs border-t border-slate-50">
                            <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide mb-1">Route
                                Info</span>
                            <p class="font-bold text-slate-700">
                                <i
                                    class="mr-1 fa-solid fa-location-dot text-rose-500"></i>{{ $reimbursement->from_location }}
                                <i class="fa-solid fa-arrow-right mx-2 text-slate-400 text-[10px]"></i>
                                {{ $reimbursement->to_location }}
                            </p>
                        </div>
                    @endif

                    <div class="pt-3 text-xs border-t border-slate-50">
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Nominal
                            Amount</span>
                        <span class="font-black text-rose-600 text-lg block mt-0.5">
                            Rp {{ number_format($reimbursement->amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="pt-3 text-xs border-t border-slate-50">
                        <span class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Comment /
                            Notes</span>
                        <p
                            class="mt-1.5 font-medium text-slate-600 leading-relaxed italic bg-slate-50 p-3 rounded-xl border border-slate-100">
                            "{{ $reimbursement->comment ?? 'No extra notes provided.' }}"
                        </p>
                    </div>
                </div>

                {{-- PANEL AKSI DIGITAL SIGNATURE (MUNCUL JIKA HAK USER SAAT INI) --}}
                @if ($canSign)
                    <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] p-6 space-y-4">
                        <h4
                            class="flex items-center gap-2 pb-3 text-xs font-black tracking-wider uppercase border-b text-slate-800 border-slate-50">
                            <i class="text-sm fa-solid fa-signature text-emerald-600"></i>
                            @if ($reimbursement->status == 'pending')
                                Langkah 1: TTD Anda (Staff Pengaju)
                            @elseif($reimbursement->status == 'pending_leader')
                                Langkah 2: TTD Local Team Leader
                            @elseif($reimbursement->status == 'pending_station')
                                Langkah 3: TTD Station Master
                            @else
                                Langkah 4: TTD Final Manager
                            @endif
                        </h4>

                        {{-- TAB METODE TTD --}}
                        <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl">
                            <button type="button" onclick="switchSignatureTab('draw')" id="btn-tab-draw"
                                class="flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm">
                                <i class="mr-1 fa-solid fa-pen"></i> Draw TTD
                            </button>
                            <button type="button" onclick="switchSignatureTab('upload')" id="btn-tab-upload"
                                class="flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700">
                                <i class="mr-1 fa-solid fa-upload"></i> Upload File
                            </button>
                        </div>

                        {{-- PANEL KANVAS GAMBAR --}}
                        <div id="panel-signature-draw" class="space-y-1.5">
                            <div
                                class="overflow-hidden bg-white border shadow-inner border-slate-200 rounded-2xl ring-1 ring-slate-100/50">
                                <canvas id="signature-canvas" class="w-full h-36 cursor-crosshair"
                                    style="touch-action: none;"></canvas>
                            </div>
                            <button type="button" onclick="clearSignature()"
                                class="text-[10px] text-rose-600 font-black uppercase tracking-wider inline-block hover:text-rose-700 transition-colors px-1">
                                <i class="fa-solid fa-trash-can mr-0.5"></i> Clear Canvas
                            </button>
                        </div>

                        {{-- PANEL UPLOAD FILE TTD --}}
                        <div id="panel-signature-upload" class="hidden">
                            <div id="dropzone" ondragover="event.preventDefault()" ondrop="handleSignatureDrop(event)"
                                class="relative p-6 text-center transition-colors border-2 border-dashed cursor-pointer border-slate-200 hover:border-amber-500 rounded-2xl bg-slate-50/50 group">
                                <input type="file" id="file-input" accept="image/png, image/jpeg"
                                    onchange="handleSignatureFileSelect(event)"
                                    class="absolute inset-0 opacity-0 cursor-pointer">
                                <i
                                    class="block mb-1.5 text-2xl fa-solid fa-cloud-arrow-up text-slate-400 group-hover:text-amber-500 transition-colors"></i>
                                <p class="text-xs font-bold text-slate-600" id="dropzone-text">Drag & Drop TTD Image or
                                    Click</p>
                                <p class="text-[9px] text-slate-400 mt-0.5">Supports PNG / JPG image file formats</p>
                            </div>
                        </div>

                        {{-- GRID DATA PENANDA TNAN --}}
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="space-y-1.5">
                                <label class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Signer Name
                                    <span class="text-rose-500">*</span></label>
                                <input type="text" id="signer-name" placeholder="e.g. Budi S."
                                    value="{{ auth()->user()->name }}"
                                    class="w-full px-3 py-2.5 font-semibold border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 placeholder-slate-300 transition-all" />
                            </div>
                            <div class="space-y-1.5">
                                <label class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide">Signature
                                    Date <span class="text-rose-500">*</span></label>
                                <input type="date" id="signer-date" value="{{ now()->format('Y-m-d') }}"
                                    class="w-full px-3 py-2.5 font-semibold border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 transition-all" />
                            </div>
                        </div>

                        {{-- AKSI ALOKASI STAMP PADA DOKUMEN --}}
                        <div class="pt-2 space-y-2.5">
                            <button type="button" onclick="applySignatureToPreview()"
                                class="flex items-center justify-center w-full gap-2 py-3 text-xs font-black tracking-wider text-white uppercase transition-all shadow-md bg-amber-500 hover:bg-amber-600 rounded-xl shadow-amber-500/10">
                                <i class="fa-solid fa-stamp"></i> Lock & Place Stamp on Document
                            </button>

                            <button type="button" onclick="duplicateLastStamp()"
                                class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-[11px] uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-2">
                                <i class="fa-solid fa-copy"></i> Duplicate Last Signature
                            </button>
                        </div>

                        <div class="flex items-center justify-between px-1 text-[10px] font-bold text-slate-400 pt-1">
                            <span>Status Stamp:</span>
                            <span id="stamp-count-info" class="tracking-wide uppercase text-amber-600">0 signature(s)
                                placed</span>
                        </div>

                        {{-- FORM SUBMIT DATA KE CONTROLLER --}}
                        <form method="POST" action="{{ route('reimbursements.approve', $reimbursement->id) }}"
                            id="approve-form" onsubmit="prepareFinalApproval(event)"
                            class="pt-4 border-t border-slate-100">
                            @csrf @method('PUT')
                            <input type="hidden" name="signatures_json" id="signatures-json-input">
                            <input type="hidden" name="signature" id="signature-input">
                            <input type="hidden" name="pos_x" id="pos-x-input">
                            <input type="hidden" name="pos_y" id="pos-y-input">
                            <input type="hidden" name="scale_w" id="scale-w-input">
                            <input type="hidden" name="scale_h" id="scale-h-input">

                            <button type="submit"
                                class="w-full py-3.5 text-xs font-black tracking-widest text-white uppercase transition-all shadow-lg bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/10 active:scale-[0.98]">
                                Approve & Save Document
                            </button>
                        </form>

                        {{-- TOMBOL REJECT / PENOLAKAN KLAIM KHUSUS PEMERIKSA --}}
                        @if ($reimbursement->status != 'pending')
                            <form method="POST" action="{{ route('reimbursements.reject', $reimbursement->id) }}"
                                class="pt-2">
                                @csrf @method('PUT')
                                <input type="hidden" name="rejected_reason"
                                    value="Klaim ditolak melalui workspace peninjauan.">
                                <button type="submit"
                                    onclick="return confirm('Apakah Anda yakin ingin menolak klaim operational reimbursement ini?')"
                                    class="w-full py-2.5 text-xs font-bold text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl transition-all uppercase tracking-wider text-center block">
                                    <i class="mr-1 fa-solid fa-xmark"></i> Reject / Tolak Klaim
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    {{-- BANNER INFORMASI MENUNGGU JATWAL ANTRIAN --}}
                    <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] p-6 text-center space-y-3">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto rounded-full bg-amber-50 text-amber-500">
                            <i class="text-lg fa-solid fa-clock-rotate-left animate-spin"
                                style="animation-duration: 4s"></i>
                        </div>
                        <div>
                            <h5 class="text-xs font-black tracking-wider uppercase text-slate-700">Berkas Sedang Mengantre
                            </h5>
                            <p class="text-[11px] text-slate-400 font-medium mt-1 leading-normal max-w-xs mx-auto">
                                @if ($reimbursement->status == 'pending')
                                    Menunggu pembubuhan tanda tangan pertama oleh <strong>Staff Pengaju</strong>.
                                @elseif($reimbursement->status == 'pending_leader')
                                    Menunggu proses verifikasi & TTD dari <strong>Local Team Leader</strong>.
                                @elseif($reimbursement->status == 'pending_station')
                                    Menunggu pemeriksaan dokumen & TTD dari <strong>Station Master</strong>.
                                @elseif($reimbursement->status == 'pending_manager')
                                    Menunggu keputusan validasi akhir dari berkas <strong>Manager</strong>.
                                @elseif($reimbursement->status == 'approved')
                                    ✅ Berkas ini telah selesai divalidasi dan disetujui sepenuhnya oleh semua pihak
                                    berwenang.
                                @else
                                    ❌ Dokumen klaim reimbursement operasional ini telah ditolak.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- SISI KANAN: WORKSPACE LIVE CANVAS DOKUMEN PENUH (3/5) --}}
            <div class="w-full space-y-3 lg:col-span-3">
                <div class="flex items-center justify-between px-1">
                    <span
                        class="text-slate-400 font-bold block uppercase text-[9px] tracking-wide flex items-center gap-1.5">
                        <i class="fa-solid fa-file-lines text-slate-500"></i> Document Live Canvas Area
                    </span>
                    <span
                        class="text-[10px] font-black text-amber-600 bg-amber-50 border border-amber-100/50 px-2.5 py-1 rounded-md uppercase tracking-wider">
                        <i class="fa-solid fa-scroll mr-0.5"></i> Scrollable Invoice
                    </span>
                </div>

                {{-- LIVE AREA CANVAS UTUH --}}
                <div id="workspace-area"
                    class="relative flex items-start justify-center w-full p-4 overflow-y-auto border shadow-inner select-none border-slate-200 bg-slate-400 rounded-[2rem]"
                    style="height: 900px;">

                    @if ($reimbursement->receipt_attachment)
                        <div id="invoice-target-img"
                            class="relative w-full max-w-full overflow-hidden transition-all duration-200 bg-white shadow-xl rounded-xl">
                            @if (pathinfo($reimbursement->receipt_attachment, PATHINFO_EXTENSION) === 'pdf')
                                <div class="relative w-full pointer-events-none" style="height: 1650px;">
                                    <object
                                        data="{{ asset('storage/' . $reimbursement->receipt_attachment) }}?v={{ time() }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                                        type="application/pdf" class="block w-full h-full">
                                        <div class="p-6 text-xs text-center text-slate-500">
                                            PDF Viewer native error. <a
                                                href="{{ asset('storage/' . $reimbursement->receipt_attachment) }}"
                                                target="_blank" class="font-bold text-blue-500 underline">Download
                                                Attachment</a>
                                        </div>
                                    </object>
                                </div>
                            @else
                                <img src="{{ asset('storage/' . $reimbursement->receipt_attachment) }}"
                                    class="block object-contain w-full h-auto pointer-events-none"
                                    alt="Invoice Attachment" />
                            @endif
                        </div>
                    @else
                        <div
                            class="w-full py-40 text-xs italic font-semibold text-center bg-white border border-dashed text-slate-400 rounded-xl border-slate-200">
                            <i class="block mb-2 text-xl fa-solid fa-triangle-exclamation text-slate-300"></i> No
                            attachment loaded.
                        </div>
                    @endif

                    {{-- LAYER UTK PENEMPATAN STAMP TTD BARU MAUPUN LAMA --}}
                    <div id="stamps-rendering-layer"
                        class="absolute top-0 left-0 z-40 w-full h-full pointer-events-none RegalLayer"></div>
                </div>

                @if ($canSign)
                    <div class="flex items-start gap-2 px-4 py-3 border bg-slate-50 rounded-2xl border-slate-100">
                        <i class="fa-solid fa-circle-info text-amber-500 text-xs mt-0.5"></i>
                        <p class="text-[10px] text-slate-500 leading-normal font-semibold">
                            <strong>Panduan Kontrol:</strong> Tekan "Lock & Place" untuk mengirim tanda tangan ke dokumen ·
                            Geser (*drag*) posisi stamp secara bebas di dalam area invoice · Gunakan **handle kotak biru**
                            di ujung kanan bawah stamp untuk mengubah ukuran skala logo tanda tangan Anda.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
@endsection

@push('scripts')
    <script>
        let signaturePad = null;
        let loadedSignatureBase64 = null;
        let activeStampsArray = [];

        // ---------- INITIALIZE CANVAS & RENDERING TTD TERDAHULU ----------
        document.addEventListener("DOMContentLoaded", function() {
            const canvas = document.getElementById('signature-canvas');
            if (canvas) {
                const ratio = Math.max(window.devicePixelRatio || 1, 1);
                canvas.width = canvas.offsetWidth * ratio;
                canvas.height = canvas.offsetHeight * ratio;
                canvas.getContext("2d").scale(ratio, ratio);

                signaturePad = new SignaturePad(canvas, {
                    minWidth: 1.5,
                    maxWidth: 3.5,
                    penColor: "rgb(30, 41, 59)"
                });
            }

            // Render TTD lama yang sudah terekam di database (Read-Only)
            const rawHistory = {!! json_encode($reimbursement->signatures_json) !!};
            if (rawHistory) {
                const historySignatures = JSON.parse(rawHistory);
                const renderLayer = document.getElementById('stamps-rendering-layer');
                const targetInvoice = document.getElementById('invoice-target-img');

                if (renderLayer && historySignatures.length > 0) {
                    setTimeout(() => {
                        let currentW = targetInvoice ? targetInvoice.clientWidth : renderLayer.clientWidth;
                        let currentH = targetInvoice ? ((targetInvoice.tagName === 'IMG') ? targetInvoice
                            .clientHeight : renderLayer.scrollHeight) : renderLayer.scrollHeight;

                        // historySignatures.forEach(sig => {
                        //     const staticStamp = document.createElement('div');
                        //     staticStamp.className = 'absolute select-none pointer-events-none';

                        //     // Mengonversi kembali persentase (%) database ke ukuran Pixel aktual monitor
                        //     const pxX = (sig.pos_x / 100) * currentW;
                        //     const pxY = (sig.pos_y / 100) * currentH;
                        //     const pxW = (sig.scale_w / 100) * currentW;
                        //     const pxH = (sig.scale_h / 100) * currentH;

                        //     staticStamp.style.cssText =
                        //         `left: ${pxX}px; top: ${pxY}px; width: ${pxW}px; height: ${pxH}px; z-index: 43;`;
                        //     staticStamp.innerHTML = `
                    //         <div class="relative w-full h-full border border-emerald-500/40 rounded-lg bg-emerald-50/20 p-0.5">
                    //             <img src="${sig.image}" class="block object-contain w-full h-auto opacity-90" style="height: calc(100% - 14px);" />
                    //             <div class="absolute bottom-0 left-0 right-0 text-center bg-white/95 text-[7px] font-black text-slate-700 truncate px-0.5 border-t border-slate-100">
                    //                 ${sig.signer_name}
                    //             </div>
                    //         </div>
                    //     `;
                        //     renderLayer.appendChild(staticStamp);
                        // });
                    }, 600);
                }
            }

            setTimeout(syncLayerHeight, 800);
        });

        // ---------- PENYELARASAN TINGGI LAYER CANVAS ----------
        function syncLayerHeight() {
            const workspace = document.getElementById('workspace-area');
            const layer = document.getElementById('stamps-rendering-layer');
            if (workspace && layer) {
                layer.style.height = workspace.scrollHeight + 'px';
            }
        }

        function clearSignature() {
            if (signaturePad) signaturePad.clear();
        }

        // ---------- TAB SWITCH INTERFACE ----------
        function switchSignatureTab(type) {
            const btnDraw = document.getElementById('btn-tab-draw');
            const btnUpload = document.getElementById('btn-tab-upload');
            const panelDraw = document.getElementById('panel-signature-draw');
            const panelUpload = document.getElementById('panel-signature-upload');

            if (!btnDraw || !btnUpload) return;

            if (type === 'draw') {
                btnDraw.className =
                    "flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm";
                btnUpload.className =
                    "flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700";
                panelDraw.classList.remove('hidden');
                panelUpload.classList.add('hidden');
            } else {
                btnUpload.className =
                    "flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm";
                btnDraw.className =
                    "flex-1 py-2 font-black text-[10px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700";
                panelUpload.classList.remove('hidden');
                panelDraw.classList.add('hidden');
            }
        }

        // ---------- MANAJEMEN FILE STAMP UPLOAD ----------
        function handleSignatureDrop(event) {
            event.preventDefault();
            if (event.dataTransfer.files.length > 0) processFile(event.dataTransfer.files[0]);
        }

        function handleSignatureFileSelect(event) {
            if (event.target.files.length > 0) processFile(event.target.files[0]);
        }

        function processFile(file) {
            if (!file.type.match('image.*')) {
                alert("Format berkas harus berupa gambar valid (PNG/JPG)!");
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                loadedSignatureBase64 = e.target.result;
                document.getElementById(`dropzone-text`).innerText = "✅ Berhasil Dimuat: " + file.name;
                document.getElementById(`dropzone`).classList.add('border-emerald-500', 'bg-emerald-50/50');
            };
            reader.readAsDataURL(file);
        }

        // ---------- MENEMPATKAN STAMP BARU KE WORKSPACE DOKUMEN ----------
        function applySignatureToPreview() {
            let base64Image = null;
            const drawPanel = document.getElementById('panel-signature-draw');
            const isDrawPanel = drawPanel ? !drawPanel.classList.contains('hidden') : true;

            if (isDrawPanel) {
                if (signaturePad && !signaturePad.isEmpty()) {
                    base64Image = signaturePad.toDataURL("image/png");
                }
            } else {
                if (loadedSignatureBase64) base64Image = loadedSignatureBase64;
            }

            if (!base64Image) {
                alert("⚠️ Silakan buat goresan TTD atau unggah file gambar terlebih dahulu!");
                return;
            }

            const name = (document.getElementById('signer-name')?.value || '').trim();
            const dateVal = document.getElementById('signer-date')?.value || '';

            if (!name) {
                alert("⚠️ Kolom nama penanda tangan wajib diisi!");
                return;
            }

            generateStampMarkup(base64Image, name, dateVal);
        }

        // ---------- DUPLIKASI DATA STAMP TERAKHIR ----------
        function duplicateLastStamp() {
            if (activeStampsArray.length === 0) {
                alert("⚠️ Belum ada objek TTD terpasang untuk diduplikasi.");
                return;
            }
            const lastStamp = activeStampsArray[activeStampsArray.length - 1];
            const lastEl = lastStamp.stampEl;

            generateStampMarkup(lastStamp.imgData, lastStamp.signerName, lastStamp.signerDate, {
                left: lastEl.offsetLeft + 25,
                top: lastEl.offsetTop + 25,
                w: lastEl.offsetWidth,
                h: lastEl.offsetHeight
            });
        }

        // ---------- PEMBUATAN DOM ELEMENT STAMP AKTIF (DRAGGABLE & RESIZABLE) ----------
        function generateStampMarkup(imgData, signerName, signerDate, opts) {
            const layer = document.getElementById('stamps-rendering-layer');
            if (!layer) return;

            const leftPos = opts?.left ?? 30;
            const topPos = opts?.top ?? 30;
            const widthSize = opts?.w ?? 140;
            const heightSize = opts?.h ?? 85;

            let formattedDate = signerDate;
            if (signerDate) {
                const d = new Date(signerDate);
                formattedDate = d.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }

            const stampEl = document.createElement('div');
            stampEl.className = 'absolute cursor-move select-none';
            stampEl.style.cssText =
                `left: ${leftPos}px; top: ${topPos}px; width: ${widthSize}px; height: ${heightSize}px; pointer-events: all; z-index: 50;`;

            stampEl.innerHTML = `
                <div class="relative w-full h-full border-2 border-dashed rounded-lg shadow-xl border-amber-500 bg-white/80 backdrop-blur-sm" style="padding: 3px;">
                    <img src="${imgData}" class="block w-full pointer-events-none" style="height: calc(100% - 30px); object-fit: contain;" />
                    <div class="absolute bottom-0 left-0 right-0 leading-tight text-center border-t bg-white/95 border-slate-100 rounded-b-md" style="padding: 2px 4px;">
                        <div class="font-black text-slate-800" style="font-size: 9px;">${signerName}</div>
                        <div class="font-bold text-slate-400" style="font-size: 8px;">${formattedDate}</div>
                    </div>
                    <span class="absolute -top-4 left-0 bg-amber-500 text-white font-black rounded px-1.5 uppercase text-[7px] tracking-wider pointer-events-none shadow-sm">
                        Drag · ${signerName}
                    </span>
                    <button type="button" class="stamp-delete-btn flex items-center justify-center bg-rose-500 hover:bg-rose-600 text-white border-none rounded-full absolute -top-3.5 -right-1.5 transition-colors" style="width: 16px; height: 16px; font-size: 8px; font-weight: 900; line-height: 1;" onclick="removeTargetStamp(this)">✕</button>
                    <div class="absolute bg-blue-500 border-2 border-white rounded shadow-sm stamp-resize-handle bottom-1 right-1 cursor-se-resize" style="width: 12px; height: 12px; z-index: 60;"></div>
                </div>
            `;

            layer.appendChild(stampEl);
            syncLayerHeight();

            activeStampsArray.push({
                stampEl,
                imgData,
                signerName,
                signerDate
            });

            const infoText = document.getElementById('stamp-count-info');
            if (infoText) infoText.textContent = `${activeStampsArray.length} signature(s) placed on document`;

            bindDragEvents(stampEl);
            bindResizeEvents(stampEl);
        }

        function removeTargetStamp(btn) {
            const stampWrapper = btn.closest('.absolute.cursor-move');
            if (!stampWrapper) return;

            activeStampsArray = activeStampsArray.filter(item => item.stampEl !== stampWrapper);
            const infoText = document.getElementById('stamp-count-info');
            if (infoText) infoText.textContent = `${activeStampsArray.length} signature(s) placed on document`;
            stampWrapper.remove();
        }

        // ---------- LOGIKA EVENT DRAG INTERAKTIF ----------
        function bindDragEvents(el) {
            const workspace = document.getElementById('workspace-area');
            let dragging = false,
                sX, sY, iL, iT;

            el.addEventListener('mousedown', startDrag);
            el.addEventListener('touchstart', startDrag, {
                passive: true
            });

            function startDrag(e) {
                if (e.target.classList.contains('stamp-resize-handle') || e.target.classList.contains('stamp-delete-btn'))
                    return;
                dragging = true;
                sX = e.clientX ?? e.touches[0].clientX;
                sY = e.clientY ?? e.touches[0].clientY;
                iL = el.offsetLeft;
                iT = el.offsetTop;

                document.addEventListener('mousemove', processDrag);
                document.addEventListener('mouseup', stopDrag);
                document.addEventListener('touchmove', processDrag, {
                    passive: false
                });
                document.addEventListener('touchend', stopDrag);
            }

            function processDrag(e) {
                if (!dragging) return;
                if (e.cancelable) e.preventDefault();
                const cX = e.clientX ?? e.touches[0].clientX;
                const cY = e.clientY ?? e.touches[0].clientY;

                let nL = iL + (cX - sX);
                let nT = iT + (cY - sY);

                const maxL = workspace.scrollWidth - el.offsetWidth;
                const maxT = workspace.scrollHeight - el.offsetHeight;

                nL = Math.max(0, Math.min(nL, maxL));
                nT = Math.max(0, Math.min(nT, maxT));

                el.style.left = nL + 'px';
                el.style.top = nT + 'px';
            }

            function stopDrag() {
                dragging = false;
                document.removeEventListener('mousemove', processDrag);
                document.removeEventListener('mouseup', stopDrag);
                document.removeEventListener('touchmove', processDrag);
                document.removeEventListener('touchend', stopDrag);
            }
        }

        // ---------- LOGIKA EVENT RESIZE INTERAKTIF ----------
        function bindResizeEvents(el) {
            const handle = el.querySelector('.stamp-resize-handle');
            if (!handle) return;
            let resizing = false,
                sX, sY, sW, sH;

            handle.addEventListener('mousedown', startResize);
            handle.addEventListener('touchstart', startResize, {
                passive: true
            });

            function startResize(e) {
                e.stopPropagation();
                resizing = true;
                sX = e.clientX ?? e.touches[0].clientX;
                sY = e.clientY ?? e.touches[0].clientY;
                sW = el.offsetWidth;
                sH = el.offsetHeight;

                document.addEventListener('mousemove', processResize);
                document.addEventListener('mouseup', stopResize);
                document.addEventListener('touchmove', processResize, {
                    passive: false
                });
                document.addEventListener('touchend', stopResize);
            }

            function processResize(e) {
                if (!resizing) return;
                if (e.cancelable) e.preventDefault();
                const cX = e.clientX ?? e.touches[0].clientX;
                const cY = e.clientY ?? e.touches[0].clientY;

                el.style.width = Math.max(80, sW + (cX - sX)) + 'px';
                el.style.height = Math.max(60, sH + (cY - sY)) + 'px';
            }

            function stopResize() {
                resizing = false;
                document.removeEventListener('mousemove', processResize);
                document.removeEventListener('mouseup', stopResize);
                document.removeEventListener('touchmove', processResize);
                document.removeEventListener('touchend', stopResize);
            }
        }

        // ---------- FORM PRE-SUBMIT PACKAGING ----------
        function prepareFinalApproval(event) {
            event.preventDefault();
            if (activeStampsArray.length === 0) {
                alert("⚠️ Pasang minimal satu cap tanda tangan di atas area invoice sebelum melakukan konfirmasi!");
                return false;
            }

            const workspace = document.getElementById('workspace-area');
            const imgTarget = document.getElementById('invoice-target-img');

            let refW = imgTarget ? imgTarget.clientWidth : workspace.clientWidth;
            let refH = imgTarget ? ((imgTarget.tagName === 'IMG') ? imgTarget.clientHeight : workspace.scrollHeight) :
                workspace.scrollHeight;

            const signaturesMap = activeStampsArray.map(({
                stampEl,
                imgData,
                signerName,
                signerDate
            }) => {
                return {
                    image: imgData,
                    pos_x: (stampEl.offsetLeft / refW) * 100,
                    pos_y: (stampEl.offsetTop / refH) * 100,
                    scale_w: (stampEl.offsetWidth / refW) * 100,
                    scale_h: (stampEl.offsetHeight / refH) * 100,
                    signer_name: signerName,
                    signer_date: signerDate
                };
            });

            // Set data input hidden untuk dikirim ke API Controller
            document.getElementById('signatures-json-input').value = JSON.stringify(signaturesMap);
            document.getElementById('signature-input').value = signaturesMap[0].image;
            document.getElementById('pos-x-input').value = signaturesMap[0].pos_x;
            document.getElementById('pos-y-input').value = signaturesMap[0].pos_y;
            document.getElementById('scale-w-input').value = signaturesMap[0].scale_w;
            document.getElementById('scale-h-input').value = signaturesMap[0].scale_h;

            document.getElementById('approve-form').submit();
        }
    </script>
@endpush
