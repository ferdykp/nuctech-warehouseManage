@extends('layout.master')

@section('title', 'Periksa Klaim #' . $reimbursement->id)

@section('content')
    @php
        $canSign = false;
        $currentRole = strtolower(auth()->user()->role ?? 'employee_role');
        $myId = auth()->id();

        if ($reimbursement->status == 'pending' && $reimbursement->user_id == $myId) {
            $canSign = true;
        } elseif ($reimbursement->status == 'pending_leader' && $currentRole == 'team_leader') {
            $canSign = true;
        } elseif ($reimbursement->status == 'pending_station' && $currentRole == 'station_master') {
            $canSign = true;
        } elseif ($reimbursement->status == 'pending_manager' && $currentRole == 'manager') {
            $canSign = true;
        }

        $attachmentPath = storage_path('app/public/' . $reimbursement->receipt_attachment);
        $fileVersion = file_exists($attachmentPath) ? filemtime($attachmentPath) : time();
    @endphp

    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('reimbursements.index') }}"
                            class="transition-colors hover:text-amber-600">Riwayat Klaim</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Pemeriksaan Persetujuan</span>
                    </nav>
                    <h1 class="flex items-center gap-3 text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        <span
                            class="w-3 h-3 rounded-full bg-amber-500 @if ($reimbursement->status != 'approved' && $reimbursement->status != 'rejected') animate-pulse @endif"></span>
                        Periksa dan Tandatangani Klaim #{{ $reimbursement->id }}
                    </h1>
                </div>

                {{-- BADGE STATUS --}}
                <div class="shrink-0">
                    <x-ui.status-badge :status="$reimbursement->status" />
                </div>
            </div>
        </div>

        {{-- 2. MAIN WORKSPACE --}}
        <div class="grid items-start grid-cols-1 gap-6 lg:grid-cols-5">

            {{-- LEFT COLUMN: PANEL DATA & DIGITAL SIGNATURE (2/5) --}}
            <div class="w-full space-y-6 lg:col-span-2">

                {{-- SPECIFICATIONS CARD --}}
                <div class="p-6 space-y-4 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
                    <h4
                        class="flex items-center gap-2 pb-3 text-xs font-extrabold tracking-wider uppercase border-b text-slate-800 border-slate-100">
                        <i class="text-sm fa-solid fa-receipt text-amber-600"></i> Rincian Klaim
                    </h4>

                    <div class="grid grid-cols-2 gap-4 text-xs">
                        <div>
                            <span class="text-slate-400 font-bold block uppercase text-xs tracking-wider">Diajukan oleh</span>
                            <span
                                class="font-bold text-slate-900 text-sm block mt-0.5">{{ $reimbursement->person_name }}</span>
                        </div>
                        <div>
                            <span
                                class="text-slate-400 font-bold block uppercase text-xs tracking-wider">Kategori</span>
                            <span
                                class="px-2.5 py-1 inline-block text-xs font-extrabold rounded-lg bg-amber-50 text-amber-800 uppercase mt-0.5 tracking-wide border border-amber-200">
                                {{ \App\Support\UiText::label($reimbursement->category) }}
                            </span>
                        </div>
                    </div>

                    @if (in_array($reimbursement->category, ['transportation', 'delivery']))
                        <div class="pt-3 text-xs border-t border-slate-100">
                            <span class="text-slate-400 font-bold block uppercase text-xs tracking-wider mb-1">Informasi Rute</span>
                            <p class="font-bold text-slate-800">
                                <i
                                    class="mr-1 fa-solid fa-location-dot text-rose-500"></i>{{ $reimbursement->from_location }}
                                <i class="fa-solid fa-arrow-right mx-2 text-slate-400 text-[10px]"></i>
                                {{ $reimbursement->to_location }}
                            </p>
                        </div>
                    @endif

                    <div class="pt-3 text-xs border-t border-slate-100">
                        <span class="text-slate-400 font-bold block uppercase text-xs tracking-wider">Nominal yang Diajukan</span>
                        <span class="font-black text-rose-600 text-xl block mt-0.5">
                            Rp {{ number_format((float) $reimbursement->amount, 0, ',', '.') }}
                        </span>
                    </div>

                    <div class="pt-3 text-xs border-t border-slate-100">
                        <span class="text-slate-400 font-bold block uppercase text-xs tracking-wider">Catatan / Keterangan</span>
                        <p
                            class="p-3.5 mt-1.5 italic font-medium leading-relaxed border text-slate-700 bg-amber-50/40 rounded-2xl border-amber-200/60">
                            "{{ $reimbursement->comment ?? 'Belum ada catatan tambahan.' }}"
                        </p>
                    </div>
                </div>

                {{-- DIGITAL SIGNATURE PANEL --}}
                @if ($canSign)
                    <div class="p-6 space-y-4 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
                        <h4
                            class="flex items-center gap-2 pb-3 text-xs font-extrabold tracking-wider uppercase border-b text-slate-800 border-slate-100">
                            <i class="text-sm fa-solid fa-signature text-emerald-600"></i>
                            @if ($reimbursement->status == 'pending')
                                Tahap 1: Karyawan Pengaju Tanda Tangan
                            @elseif($reimbursement->status == 'pending_leader')
                                Tahap 2: Ketua Tim Tanda Tangan
                            @elseif($reimbursement->status == 'pending_station')
                                Tahap 3: Tanda Tangan Kepala Stasiun
                            @else
                                Tahap 4: Persetujuan Akhir Manajer
                            @endif
                        </h4>

                        {{-- TAB TTD --}}
                        <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl">
                            <button type="button" onclick="switchSignatureTab('draw')" id="btn-tab-draw"
                                class="flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all bg-white text-slate-800 shadow-2xs cursor-pointer">
                                <i class="mr-1 fa-solid fa-pen"></i> Gambar Tanda Tangan
                            </button>
                            <button type="button" onclick="switchSignatureTab('upload')" id="btn-tab-upload"
                                class="flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700 cursor-pointer">
                                <i class="mr-1 fa-solid fa-upload"></i> Unggah File
                            </button>
                        </div>

                        {{-- DRAW CANVAS PANEL --}}
                        <div id="panel-signature-draw" class="space-y-1.5">
                            <div class="overflow-hidden bg-white border border-slate-200 rounded-2xl">
                                <canvas id="signature-canvas" aria-label="Area menggambar tanda tangan; opsi unggah tersedia sebagai alternatif" class="w-full h-36 cursor-crosshair"
                                    style="touch-action: none;"></canvas>
                            </div>
                            <button type="button" onclick="clearSignature()"
                                class="text-xs text-rose-600 font-bold uppercase tracking-wider inline-block hover:text-rose-700 transition-colors px-1 cursor-pointer">
                                <i class="fa-solid fa-trash-can mr-0.5"></i> Hapus Tanda Tangan
                            </button>
                        </div>

                        {{-- UPLOAD PANEL --}}
                        <div id="panel-signature-upload" class="hidden">
                            <div id="dropzone" ondragover="event.preventDefault()" ondrop="handleSignatureDrop(event)"
                                class="relative p-6 text-center transition-all border-2 border-dashed cursor-pointer border-slate-200 hover:border-amber-500 rounded-2xl bg-slate-50/50 group">
                                <input type="file" id="file-input" accept="image/png, image/jpeg"
                                    onchange="handleSignatureFileSelect(event)"
                                    class="absolute inset-0 opacity-0 cursor-pointer">
                                <i
                                    class="block mb-1 text-2xl transition-colors fa-solid fa-cloud-arrow-up text-slate-400 group-hover:text-amber-600"></i>
                                <p class="text-xs font-bold text-slate-700" id="dropzone-text">Tarik gambar tanda tangan atau klik</p>
                                <p class="text-xs text-slate-400 mt-0.5">Mendukung file PNG atau JPG</p>
                            </div>
                        </div>

                        {{-- SIGNER INPUTS --}}
                        <div class="grid grid-cols-2 gap-3 text-xs">
                            <div class="space-y-1">
                                <label class="text-slate-700 font-bold block uppercase text-xs tracking-wider">Nama Penanda Tangan <span class="text-rose-500">*</span></label>
                                <input type="text" id="signer-name" placeholder="Contoh: Budi S."
                                    value="{{ auth()->user()->name }}"
                                    class="w-full px-3.5 py-2 font-bold transition-all border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800" />
                            </div>
                            <div class="space-y-1">
                                <label class="text-slate-700 font-bold block uppercase text-xs tracking-wider">Tanggal
                                    <span class="text-rose-500">*</span></label>
                                <input type="date" id="signer-date" value="{{ now()->format('Y-m-d') }}"
                                    class="w-full px-3.5 py-2 font-bold transition-all border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800" />
                            </div>
                        </div>

                        {{-- STAMP ACTIONS --}}
                        <div class="pt-2 space-y-2">
                            <button type="button" onclick="applySignatureToPreview()"
                                class="flex items-center justify-center w-full gap-2 py-3 text-xs font-bold text-white uppercase transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-[0.98] cursor-pointer">
                                <i class="fa-solid fa-stamp"></i> Letakkan Tanda Tangan pada Dokumen
                            </button>

                            <button type="button" onclick="duplicateLastStamp()"
                                class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs uppercase tracking-wider rounded-xl transition-colors flex items-center justify-center gap-1.5 cursor-pointer">
                                <i class="fa-solid fa-copy"></i> Gandakan Tanda Tangan Terakhir
                            </button>
                        </div>

                        <div class="flex items-center justify-between px-1 text-xs font-bold text-slate-400 pt-1">
                            <span>Status Tanda Tangan:</span>
                            <span id="stamp-count-info" role="status" aria-live="polite" class="tracking-wide uppercase text-amber-600">0 tanda tangan ditempatkan</span>
                        </div>

                        {{-- SUBMIT FORM --}}
                        <form method="POST" action="{{ route('reimbursements.approve', $reimbursement->id) }}"
                            id="approve-form" onsubmit="prepareFinalApproval(event)"
                            class="pt-3 border-t border-slate-100">
                            @csrf @method('PUT')
                            <input type="hidden" name="signatures_json" id="signatures-json-input">
                            <input type="hidden" name="signature" id="signature-input">
                            <input type="hidden" name="pos_x" id="pos-x-input">
                            <input type="hidden" name="pos_y" id="pos-y-input">
                            <input type="hidden" name="scale_w" id="scale-w-input">
                            <input type="hidden" name="scale_h" id="scale-h-input">
                            <input type="hidden" name="page" id="page-input">

                            <button type="submit"
                                class="w-full py-3 text-xs font-bold text-white uppercase transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-[0.98] cursor-pointer">
                                Setujui dan Simpan Dokumen
                            </button>
                        </form>

                        {{-- REJECT FORM --}}
                        @if ($reimbursement->status != 'pending')
                            <form method="POST" action="{{ route('reimbursements.reject', $reimbursement->id) }}"
                                class="pt-1">
                                @csrf @method('PUT')
                                <input type="hidden" name="rejected_reason"
                                    value="Claim rejected via review workspace.">
                                <button type="submit"
                                    onclick="return confirm('Yakin ingin menolak klaim ini?')"
                                    class="block w-full py-2.5 text-xs font-bold tracking-wider text-center uppercase transition-colors text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl cursor-pointer">
                                    <i class="mr-1 fa-solid fa-xmark"></i> Tolak Klaim
                                </button>
                            </form>
                        @endif
                    </div>
                @else
                    {{-- PENDING BANNER CARD --}}
                    <div
                        class="p-6 space-y-3 text-center bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
                        <div
                            class="flex items-center justify-center w-12 h-12 mx-auto border rounded-2xl bg-amber-50 text-amber-500 border-amber-100">
                            <i class="text-lg fa-solid fa-clock-rotate-left animate-spin"
                                style="animation-duration: 4s"></i>
                        </div>
                        <div>
                            <h5 class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Dokumen Menunggu Persetujuan
                            </h5>
                            <p class="max-w-xs mx-auto mt-1 text-xs font-medium leading-relaxed text-slate-500">
                                @if ($reimbursement->status == 'pending')
                                    Menunggu tanda tangan pertama dari <strong>Karyawan Pengaju</strong>.
                                @elseif($reimbursement->status == 'pending_leader')
                                    Menunggu pemeriksaan dan tanda tangan dari <strong>Ketua Tim</strong>.
                                @elseif($reimbursement->status == 'pending_station')
                                    Menunggu verifikasi dokumen dan tanda tangan dari <strong>Kepala Stasiun</strong>.
                                @elseif($reimbursement->status == 'pending_manager')
                                    Menunggu persetujuan akhir dari <strong>Manajer Operasional</strong>.
                                @elseif($reimbursement->status == 'approved')
                                    ✅ Klaim ini telah diperiksa dan disetujui.
                                @else
                                    ❌ Klaim reimbursement ini ditolak.
                                @endif
                            </p>
                        </div>
                    </div>
                @endif
            </div>

            {{-- RIGHT COLUMN: DOCUMENT LIVE CANVAS (3/5) --}}
            <div class="w-full space-y-3 lg:col-span-3">
                <div class="flex items-center justify-between px-1">
                    <span
                        class="text-slate-400 font-bold block uppercase text-xs tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-file-lines text-slate-500"></i> Pratinjau Dokumen
                    </span>
                    <span
                        class="text-xs font-extrabold text-amber-800 bg-amber-50 border border-amber-200 px-2.5 py-0.5 rounded-md uppercase tracking-wider">
                        <i class="fa-solid fa-scroll mr-0.5"></i> Dokumen PDF Beberapa Halaman
                    </span>
                </div>

                <div id="workspace-area"
                    class="relative flex flex-col items-center w-full p-4 overflow-y-auto border shadow-inner border-slate-200 bg-slate-400 rounded-3xl"
                    style="height: 800px;">

                    @if ($reimbursement->receipt_attachment)
                        @if (pathinfo($reimbursement->receipt_attachment, PATHINFO_EXTENSION) === 'pdf')
                            <div id="pdf-loading-indicator"
                                class="py-4 text-xs font-bold tracking-widest text-white uppercase animate-pulse">
                                <i class="fa-solid fa-spinner animate-spin mr-1.5"></i> Memuat halaman PDF...
                            </div>

                            <div id="invoice-target-img" class="flex flex-col items-center w-full gap-4"></div>
                        @else
                            <div id="invoice-target-img"
                                class="relative w-full max-w-full overflow-hidden transition-all duration-200 bg-white shadow-xl rounded-2xl">
                                <img src="{{ asset('storage/' . $reimbursement->receipt_attachment) }}"
                                    class="block object-contain w-full h-auto pointer-events-none"
                                    alt="Invoice Lampiran" />
                            </div>
                        @endif
                    @else
                        <div
                            class="w-full py-40 text-xs italic font-semibold text-center bg-white border border-dashed text-slate-400 rounded-2xl border-slate-200">
                            <i class="block mb-2 text-xl fa-solid fa-triangle-exclamation text-slate-300"></i> Belum ada lampiran yang dimuat.
                        </div>
                    @endif

                    <div id="stamps-rendering-layer"
                        class="absolute top-0 left-0 z-40 w-full h-full pointer-events-none RegalLayer"></div>
                </div>

                @if ($canSign)
                    <div class="flex items-start gap-2.5 px-4 py-3 border bg-slate-50 rounded-2xl border-slate-200/80">
                        <i class="fa-solid fa-circle-info text-amber-500 text-xs mt-0.5"></i>
                        <p class="text-xs font-medium leading-normal text-slate-600">
                            <strong>Petunjuk:</strong> Klik "Letakkan Tanda Tangan" untuk menempatkannya pada dokumen. Geser tanda tangan untuk mengatur posisi. Tarik sudut kanan bawah untuk mengubah ukurannya.
                        </p>
                    </div>
                @endif
            </div>
        </div>
    </div>

@endsection

@push('scripts')
    <script type="application/json" id="reimbursement-approval-config">{!! json_encode(['pdfUrl' => asset('storage/' . $reimbursement->receipt_attachment) . '?v=' . $fileVersion, 'isPdf' => pathinfo($reimbursement->receipt_attachment, PATHINFO_EXTENSION) === 'pdf'], JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite('resources/js/pages/reimbursement-approval.js')
@endpush
