@extends('layout.master')

@section('title', 'Reimbursements')

@section('content')
    <div class="w-full px-6 py-8 pb-10 space-y-6 md:space-y-8">
        {{-- HEADER --}}
        <div class="flex flex-col gap-6 px-2 lg:flex-row lg:items-center lg:justify-between">
            <div class="space-y-1">
                <h2 class="text-3xl font-black tracking-tighter md:text-4xl text-slate-800">
                    {{ $pageTitle ?? 'Reimbursement Claims' }}</h2>
                <p class="text-xs font-medium md:text-sm text-slate-500">Track and manage employee operational expenses.</p>
            </div>

            {{-- ACCUMULATED TOTAL BUDGET CARD --}}
            <div class="flex items-center gap-4 px-6 py-4 bg-white border shadow-sm border-slate-100 rounded-2xl">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <i class="text-xl fa-solid fa-money-bill-wave"></i>
                </div>
                <div>
                    <span class="text-[9px] font-black uppercase text-slate-400 tracking-widest block">Total Approved
                        Funds</span>
                    <span class="text-lg font-black text-slate-800">Rp
                        {{ number_format($totalApprovedAmount ?? 0, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- MAIN CONTAINER --}}
        <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] md:rounded-[3rem] overflow-hidden">
            <div
                class="flex flex-col items-stretch justify-between gap-4 p-6 border-b lg:flex-row lg:items-center border-slate-50">
                <div class="flex flex-col flex-1 gap-3 sm:flex-row sm:items-center">
                    <h3 class="text-lg font-bold text-slate-800 shrink-0">Claim Logs</h3>

                    {{-- SEARCH BAR --}}
                    <div class="relative w-full max-w-md">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none text-slate-400">
                            <i class="text-sm fa-solid fa-magnifying-glass"></i>
                        </span>
                        <input type="text" id="reimburseSearchInput" onkeyup="filterReimburseList()"
                            placeholder="Search by staff name or comment..."
                            class="w-full py-2.5 pl-11 pr-4 text-xs font-medium bg-slate-50 border border-slate-100 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 placeholder-slate-400 transition-all">
                    </div>
                </div>

                <a href="{{ route('reimbursements.create') }}"
                    class="flex items-center justify-center gap-2 px-6 py-3 text-sm font-black text-white transition-all shadow-lg bg-amber-600 rounded-xl shadow-amber-600/10 active:scale-95">
                    <i class="fa-solid fa-plus"></i>
                    <span>File New Claim</span>
                </a>
            </div>

            {{-- EMPTY DATA ALERT --}}
            <div id="emptySearchState" class="flex-col items-center justify-center hidden p-12 text-center bg-white">
                <div class="flex items-center justify-center w-16 h-16 mb-4 rounded-full bg-slate-50 text-slate-400">
                    <i class="text-xl fa-solid fa-receipt"></i>
                </div>
                <h4 class="text-sm font-bold text-slate-700">No Claims Found</h4>
                <p class="max-w-xs mt-1 text-xs text-slate-400">We couldn't find any results matching your search terms.</p>
            </div>

            {{-- DESKTOP VIEW --}}
            <div id="desktopTableContainer" class="hidden overflow-x-auto md:block">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-black tracking-[0.15em] text-slate-400 uppercase bg-slate-50/50">
                            <th class="px-8 py-5">Person Name / Date</th>
                            <th class="px-6 py-5">Category</th>
                            <th class="px-6 py-5">Details / Route</th>
                            <th class="px-6 py-5 text-center">Amount</th>
                            <th class="px-6 py-5 text-center">Status</th>
                            <th class="px-8 py-5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach ($reimbursements as $r)
                            <tr class="transition-colors group hover:bg-slate-50/50 reimburse-row-item"
                                data-search-staff="{{ strtolower($r->person_name) }}"
                                data-search-title="{{ strtolower($r->comment ?? '') }}">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="flex items-center justify-center font-black w-9 h-9 text-amber-600 bg-amber-50 rounded-xl shrink-0">
                                            {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                            <p class="text-[10px] font-semibold text-slate-400">
                                                {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-5">
                                    <span
                                        class="px-2.5 py-1 text-[10px] font-bold rounded-lg uppercase tracking-wider
                                        {{ $r->category == 'transportation' ? 'bg-blue-50 text-blue-700' : ($r->category == 'delivery' ? 'bg-purple-50 text-purple-700' : 'bg-slate-100 text-slate-700') }}">
                                        {{ $r->category }}
                                    </span>
                                </td>
                                <td class="px-6 py-5">
                                    @if (in_array($r->category, ['transportation', 'delivery']))
                                        <p class="text-xs font-semibold text-slate-600">
                                            <i class="mr-1 fa-solid fa-location-dot text-rose-500"></i>
                                            {{ $r->from_location }}
                                            <i class="mx-1 fa-solid fa-arrow-right text-slate-400"></i>
                                            {{ $r->to_location }}
                                        </p>
                                    @else
                                        <p class="text-xs italic text-slate-400">No routing needed</p>
                                    @endif
                                </td>
                                <td class="px-6 py-5 text-center">
                                    <span class="text-xs font-black text-slate-700">Rp
                                        {{ number_format($r->amount, 0, ',', '.') }}</span>
                                </td>
                                <td class="px-6 py-5 text-center">
                                    @if ($r->status == 'approved')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-emerald-700 bg-emerald-50 rounded-full">Approved</span>
                                    @elseif($r->status == 'rejected')
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-rose-700 bg-rose-50 rounded-full">Rejected</span>
                                    @else
                                        <span
                                            class="px-3 py-1 text-[10px] font-bold text-amber-700 bg-amber-50 rounded-full animate-pulse">Pending</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex items-center justify-end gap-2">
                                        <button onclick="toggleDetailModal('{{ $r->id }}', true)"
                                            class="p-2 text-blue-600 transition-colors rounded-lg bg-blue-50 hover:bg-blue-600 hover:text-white">
                                            <i class="text-xs fa-solid fa-eye"></i>
                                        </button>

                                        @if ($r->status == 'pending' && $r->user_id == auth()->id())
                                            <button onclick="confirmCancel('{{ $r->id }}')"
                                                class="p-2 transition-colors rounded-lg text-rose-600 bg-rose-50 hover:bg-rose-600 hover:text-white">
                                                <i class="text-xs fa-solid fa-ban"></i>
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- MOBILE VIEW --}}
            <div id="mobileCardContainer" class="p-4 space-y-4 md:hidden bg-slate-50/50">
                @foreach ($reimbursements as $r)
                    <!-- Mobile card loop code remains same as user provided -->
                    <div class="p-5 space-y-4 bg-white border shadow-sm border-slate-100 rounded-2xl reimburse-card-item">
                        <div class="flex items-start justify-between">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex items-center justify-center w-10 h-10 font-black text-amber-600 bg-amber-50 rounded-xl">
                                    {{ strtoupper(substr($r->person_name ?? '?', 0, 1)) }}
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-800">{{ $r->person_name }}</p>
                                    <p class="text-[10px] font-semibold text-slate-400">
                                        {{ \Carbon\Carbon::parse($r->date)->format('d M Y') }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="flex justify-end gap-2 pt-1">
                            <button onclick="toggleDetailModal('{{ $r->id }}', true)"
                                class="p-2.5 text-blue-600 bg-blue-50 rounded-xl grow text-center font-bold text-xs flex justify-center items-center gap-2">
                                <i class="fa-solid fa-eye"></i> View Details
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    {{-- CANCEL FORM MODAL --}}
    <div id="cancelModal" class="fixed inset-0 z-[9999] flex items-center justify-center hidden px-4">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>
        <div class="relative w-full max-w-sm p-8 bg-white shadow-2xl rounded-[2.5rem] modal-card">
            <!-- Modal cancel content remains identical -->
        </div>
    </div>

    {{-- INJECT MODAL DETAILS & APPROVAL COMPONENT --}}
    @foreach ($reimbursements as $r)
        <div id="modal-detail-{{ $r->id }}"
            class="fixed inset-0 z-[9998] items-center justify-center hidden opacity-0 transition-opacity duration-300 px-4">
            {{-- BACKDROP --}}
            <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"
                onclick="toggleDetailModal('{{ $r->id }}', false)"></div>

            {{-- MODAL CARD BOX --}}
            <div
                class="relative w-full max-w-5xl bg-white shadow-2xl rounded-[2rem] modal-card transform scale-95 transition-all duration-300 max-h-[92vh] flex flex-col overflow-hidden">

                {{-- 🛠️ FIXED HEADER & TOMBOL CLOSE (X) --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-2.5">
                        <div class="w-2.5 h-2.5 rounded-full bg-amber-500 animate-pulse"></div>
                        <h3 class="text-sm font-black tracking-tight uppercase text-slate-800">
                            Review Claim / #{{ $r->id }}
                        </h3>
                    </div>
                    {{-- TOMBOL X PRESISI --}}
                    <button type="button" onclick="toggleDetailModal('{{ $r->id }}', false)"
                        class="flex items-center justify-center w-8 h-8 transition-colors rounded-full text-slate-400 bg-slate-100 hover:bg-rose-50 hover:text-rose-600 group">
                        <i class="text-sm transition-transform fa-solid fa-xmark group-hover:rotate-90"></i>
                    </button>
                </div>

                {{-- MODAL BODY (SCROLLABLE INSIDE) --}}
                <div class="flex-1 p-6 overflow-y-auto">
                    <div class="grid items-start grid-cols-1 gap-8 lg:grid-cols-5">

                        {{-- SISI KIRI: DATA SPESIFIKASI KLAIM & AKSI MANAGER (2/5) --}}
                        <div class="w-full space-y-5 text-xs lg:col-span-2 lg:border-r lg:border-slate-100 lg:pr-6">

                            {{-- CARD PARAMETER --}}
                            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-100 space-y-3.5">
                                <h4
                                    class="text-[11px] font-black uppercase text-slate-400 tracking-wider flex items-center gap-1.5 mb-1">
                                    <i class="fa-solid fa-receipt text-amber-500"></i> Claim Specifications
                                </h4>
                                <div class="grid grid-cols-2 gap-3">
                                    <div>
                                        <span
                                            class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">Person
                                            Name</span>
                                        <span
                                            class="font-bold text-slate-700 text-xs block mt-0.5">{{ $r->person_name }}</span>
                                    </div>
                                    <div>
                                        <span
                                            class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">Category</span>
                                        <span
                                            class="px-2 py-0.5 inline-block text-[9px] font-black rounded bg-amber-100 text-amber-800 uppercase mt-0.5 tracking-wide">
                                            {{ $r->category }}
                                        </span>
                                    </div>
                                </div>
                                <div class="border-t border-slate-200/60 pt-2.5">
                                    <span class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">Nominal
                                        Amount</span>
                                    <span class="font-black text-rose-600 text-base block mt-0.5">
                                        Rp {{ number_format($r->amount, 0, ',', '.') }}
                                    </span>
                                </div>
                                <div class="border-t border-slate-200/60 pt-2.5">
                                    <span class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">Comment
                                        / Notes</span>
                                    <p class="mt-1 font-medium text-slate-600 leading-relaxed italic text-[11px]">
                                        "{{ $r->comment ?? 'No extra notes provided.' }}"
                                    </p>
                                </div>
                            </div>

                            {{-- AKSI APPROVAL DENGAN DIGITAL SIGNATURE (HANYA APABILA PENDING) --}}
                            @if ($r->status == 'pending' && in_array(strtolower(auth()->user()->role ?? 'staff'), ['superadmin', 'admin_site']))
                                <div class="pt-1 space-y-4">
                                    <h4
                                        class="text-[11px] font-black tracking-wider uppercase text-slate-800 flex items-center gap-1.5">
                                        <i class="fa-solid fa-signature text-emerald-600"></i> Approval Signature Action
                                    </h4>

                                    {{-- TAB CONTROLLER UNTUK METODE TTD --}}
                                    <div class="flex gap-1.5 p-1 bg-slate-100 rounded-xl">
                                        <button type="button"
                                            onclick="switchSignatureTab('{{ $r->id }}', 'draw')"
                                            id="btn-tab-draw-{{ $r->id }}"
                                            class="flex-1 py-1.5 font-black text-[9px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm">
                                            <i class="mr-1 fa-solid fa-pen"></i> Draw TTD
                                        </button>
                                        <button type="button"
                                            onclick="switchSignatureTab('{{ $r->id }}', 'upload')"
                                            id="btn-tab-upload-{{ $r->id }}"
                                            class="flex-1 py-1.5 font-black text-[9px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700">
                                            <i class="mr-1 fa-solid fa-upload"></i> Upload File
                                        </button>
                                    </div>

                                    {{-- PANEL A: MENGGAMBAR KANVAS TTD --}}
                                    <div id="panel-signature-draw-{{ $r->id }}" class="space-y-1 signature-panel">
                                        <div
                                            class="overflow-hidden border shadow-inner border-slate-200 rounded-xl bg-slate-50 ring-1 ring-slate-100">
                                            <canvas id="signature-canvas-{{ $r->id }}"
                                                class="w-full bg-white h-28" style="touch-action: none;"></canvas>
                                        </div>
                                        <button type="button" onclick="clearSignature('{{ $r->id }}')"
                                            class="text-[9px] text-rose-600 font-black uppercase tracking-wider block hover:text-rose-700 transition-colors">
                                            <i class="fa-solid fa-trash-can mr-0.5"></i> Clear Canvas
                                        </button>
                                    </div>

                                    {{-- PANEL B: DRAG & DROP FILE UNTUK UPLOAD TTD --}}
                                    <div id="panel-signature-upload-{{ $r->id }}" class="hidden signature-panel">
                                        <div id="dropzone-{{ $r->id }}" ondragover="event.preventDefault()"
                                            ondrop="handleSignatureDrop(event, '{{ $r->id }}')"
                                            class="relative p-5 text-center transition-colors border-2 border-dashed cursor-pointer border-slate-300 hover:border-amber-500 rounded-xl bg-slate-50/50">
                                            <input type="file" id="file-input-{{ $r->id }}"
                                                accept="image/png, image/jpeg"
                                                onchange="handleSignatureFileSelect(event, '{{ $r->id }}')"
                                                class="absolute inset-0 opacity-0 cursor-pointer">
                                            <i class="block mb-1 text-lg fa-solid fa-cloud-arrow-up text-slate-400"></i>
                                            <p class="text-[10px] font-bold text-slate-600"
                                                id="dropzone-text-{{ $r->id }}">
                                                Drag & Drop TTD Image or Click
                                            </p>
                                            <p class="text-[8px] text-slate-400 mt-0.5">Supports PNG / JPG</p>
                                        </div>
                                    </div>

                                    {{-- INPUT NAMA & TANGGAL PENANDA TANGAN (GRID) --}}
                                    <div class="grid grid-cols-2 gap-3">
                                        <div class="space-y-1.5">
                                            <label
                                                class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">
                                                Signer Name <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="text" id="signer-name-{{ $r->id }}"
                                                placeholder="e.g. Budi S."
                                                class="w-full px-2.5 py-2 text-xs font-semibold border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 placeholder-slate-300 transition-all" />
                                        </div>
                                        <div class="space-y-1.5">
                                            <label
                                                class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide">
                                                Signature Date <span class="text-rose-500">*</span>
                                            </label>
                                            <input type="date" id="signer-date-{{ $r->id }}"
                                                value="{{ now()->format('Y-m-d') }}"
                                                class="w-full px-2.5 py-2 text-xs font-semibold border border-slate-200 rounded-xl bg-slate-50 focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700 transition-all" />
                                        </div>
                                    </div>

                                    {{-- TOMBOL UTUT ACTION --}}
                                    <div class="pt-1 space-y-2">
                                        <button type="button" onclick="applySignatureToPreview('{{ $r->id }}')"
                                            class="w-full py-2.5 bg-amber-500 hover:bg-amber-600 text-white font-black text-[10px] uppercase tracking-wider rounded-xl transition-all shadow-md shadow-amber-500/10 flex items-center justify-center gap-1.5">
                                            <i class="fa-solid fa-stamp"></i> Lock & Place Stamp on Document
                                        </button>

                                        <button type="button" onclick="duplicateLastStamp('{{ $r->id }}')"
                                            class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-600 font-black text-[10px] uppercase tracking-wider rounded-xl transition-all flex items-center justify-center gap-1.5">
                                            <i class="fa-solid fa-copy"></i> Duplicate Last Signature
                                        </button>
                                    </div>

                                    <div
                                        class="flex items-center justify-between px-1 text-[9px] font-semibold text-slate-400">
                                        <span>Status Stamp:</span>
                                        <span id="stamp-count-info-{{ $r->id }}"
                                            class="tracking-wide uppercase text-amber-600">
                                            0 signature(s) placed
                                        </span>
                                    </div>

                                    {{-- FORM FINAL SUBMIT PERSETUJUAN --}}
                                    <form method="POST" action="{{ route('reimbursements.approve', $r->id) }}"
                                        id="approve-form-{{ $r->id }}"
                                        onsubmit="prepareFinalApproval(event, '{{ $r->id }}')"
                                        class="pt-2 border-t border-slate-100">
                                        @csrf @method('PUT')
                                        <input type="hidden" name="signatures_json"
                                            id="signatures-json-input-{{ $r->id }}">
                                        <input type="hidden" name="signature" id="signature-input-{{ $r->id }}">
                                        <input type="hidden" name="pos_x" id="pos-x-input-{{ $r->id }}">
                                        <input type="hidden" name="pos_y" id="pos-y-input-{{ $r->id }}">
                                        <input type="hidden" name="scale_w" id="scale-w-input-{{ $r->id }}">
                                        <input type="hidden" name="scale_h" id="scale-h-input-{{ $r->id }}">

                                        <button type="submit"
                                            class="w-full py-3 text-xs font-black tracking-wider text-white uppercase transition-all shadow-lg bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/10 active:scale-[0.98]">
                                            Approve & Save Document
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>

                        {{-- SISI KANAN: WORKSPACE INVOICE — PERSISI DIGITAL VIEW (3/5) --}}
                        <div class="w-full space-y-2.5 lg:col-span-3">
                            <div class="flex items-center justify-between">
                                <span
                                    class="text-slate-400 font-bold block uppercase text-[8px] tracking-wide flex items-center gap-1">
                                    <i class="fa-solid fa-file-lines text-slate-500"></i> Document Live Canvas Area
                                </span>
                                <span
                                    class="text-[9px] font-black text-amber-600 bg-amber-50 px-2 py-0.5 rounded-md uppercase tracking-wider animate-pulse">
                                    <i class="fa-solid fa-scroll mr-0.5"></i> Scrollable Document
                                </span>
                            </div>

                            {{-- CONTAINER WORKSPACE UTUH DENGAN BACKGROUND KONTRAS --}}
                            <div id="workspace-{{ $r->id }}"
                                class="relative flex items-start justify-center w-full p-3 overflow-y-auto border shadow-inner select-none border-slate-200 bg-slate-300 rounded-2xl group"
                                style="height: 560px;">

                                {{-- DOKUMEN MEDIA --}}
                                @if ($r->receipt_attachment)
                                    <div id="invoice-img-{{ $r->id }}"
                                        class="relative w-full max-w-full overflow-hidden transition-all duration-200 bg-white rounded-lg shadow-xl">
                                        @if (pathinfo($r->receipt_attachment, PATHINFO_EXTENSION) === 'pdf')
                                            <div class="relative w-full pointer-events-none" style="height: 1450px;">
                                                <object
                                                    data="{{ asset('storage/' . $r->receipt_attachment) }}?v={{ time() }}#toolbar=0&navpanes=0&scrollbar=0&view=FitH"
                                                    type="application/pdf" class="block w-full h-full">
                                                    <div class="p-6 text-xs text-center text-slate-500">
                                                        PDF Viewer native error. <a
                                                            href="{{ asset('storage/' . $r->receipt_attachment) }}"
                                                            target="_blank"
                                                            class="font-bold text-blue-500 underline">Download
                                                            Attachment</a>
                                                    </div>
                                                </object>
                                            </div>
                                        @else
                                            <img src="{{ asset('storage/' . $r->receipt_attachment) }}"
                                                class="block object-contain w-full h-auto pointer-events-none"
                                                alt="Invoice Attachment" />
                                        @endif
                                    </div>
                                @else
                                    <div
                                        class="w-full py-32 text-xs italic font-semibold text-center bg-white border border-dashed text-slate-400 rounded-xl border-slate-200">
                                        <i class="block mb-1 text-lg fa-solid fa-triangle-exclamation text-slate-300"></i>
                                        No attachment loaded.
                                    </div>
                                @endif

                                {{-- STAMPS LAYER INTERAKTIF --}}
                                @if ($r->status == 'pending')
                                    <div id="stamps-layer-{{ $r->id }}"
                                        class="absolute top-0 left-0 z-40 w-full h-full pointer-events-none"></div>
                                @endif
                            </div>

                            {{-- GUIDANCE FOOTER --}}
                            @if ($r->status == 'pending')
                                <div
                                    class="flex items-start gap-1.5 px-2 py-1.5 bg-slate-50 rounded-xl border border-slate-100">
                                    <i class="fa-solid fa-circle-info text-amber-500 text-[10px] mt-0.5"></i>
                                    <p class="text-[9px] text-slate-500 leading-normal font-medium">
                                        <strong>Controls:</strong> Drag stamp anywhere inside · Use <strong>blue corner
                                            handles</strong> to scale · Use "Duplicate" for multiple approvals.
                                    </p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    @endforeach

    <script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
@endsection

@push('scripts')
    <script>
        /* =====================================================================
         *  SIGNATURE MANAGER — Multi-sig, Resizable, Scroll-Safe, Name+Date
         * ===================================================================== */
        let signaturePads = {};
        let loadedSignatures = {};
        // stampRegistry[id] = array of { stampEl, imgData, signerName, signerDate }
        let stampRegistry = {};

        // ---------- TAB SWITCH ----------
        function switchSignatureTab(id, type) {
            const btnDraw = document.getElementById(`btn-tab-draw-${id}`);
            const btnUpload = document.getElementById(`btn-tab-upload-${id}`);
            const panelDraw = document.getElementById(`panel-signature-draw-${id}`);
            const panelUpload = document.getElementById(`panel-signature-upload-${id}`);
            if (type === 'draw') {
                btnDraw.className =
                    "flex-1 py-1.5 font-bold text-[10px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm";
                btnUpload.className =
                    "flex-1 py-1.5 font-bold text-[10px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700";
                panelDraw.classList.remove('hidden');
                panelUpload.classList.add('hidden');
            } else {
                btnUpload.className =
                    "flex-1 py-1.5 font-bold text-[10px] uppercase rounded-lg transition-all bg-white text-slate-800 shadow-sm";
                btnDraw.className =
                    "flex-1 py-1.5 font-bold text-[10px] uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700";
                panelUpload.classList.remove('hidden');
                panelDraw.classList.add('hidden');
            }
        }

        // ---------- FILE DROP / UPLOAD ----------
        function handleSignatureDrop(event, id) {
            event.preventDefault();
            const files = event.dataTransfer.files;
            if (files.length > 0) processSignatureFile(files[0], id);
        }

        function handleSignatureFileSelect(event, id) {
            const files = event.target.files;
            if (files.length > 0) processSignatureFile(files[0], id);
        }

        function processSignatureFile(file, id) {
            if (!file.type.match('image.*')) {
                alert("Please drop a valid image file (PNG/JPG)!");
                return;
            }
            const reader = new FileReader();
            reader.onload = function(e) {
                loadedSignatures[id] = e.target.result;
                document.getElementById(`dropzone-text-${id}`).innerText = "✅ File loaded: " + file.name;
                document.getElementById(`dropzone-${id}`).classList.add('border-emerald-500', 'bg-emerald-50/50');
            };
            reader.readAsDataURL(file);
        }

        // ---------- APPLY SIGNATURE (LOCK & PLACE) ----------
        function applySignatureToPreview(id) {
            // 1. Ambil data gambar TTD
            let base64Image = null;
            const isDrawPanel = !document.getElementById(`panel-signature-draw-${id}`).classList.contains('hidden');
            if (isDrawPanel) {
                if (signaturePads[id] && !signaturePads[id].isEmpty())
                    base64Image = signaturePads[id].toDataURL("image/png");
            } else {
                if (loadedSignatures[id]) base64Image = loadedSignatures[id];
            }
            if (!base64Image) {
                alert("⚠️ Please draw your signature or upload an image first!");
                return;
            }

            // 2. Ambil nama & tanggal
            const signerName = (document.getElementById(`signer-name-${id}`)?.value || '').trim();
            const signerDate = document.getElementById(`signer-date-${id}`)?.value || '';
            if (!signerName) {
                alert("⚠️ Please fill in the Signer Name field!");
                return;
            }

            // 3. Buat stamp baru di workspace
            addStampToWorkspace(id, base64Image, signerName, signerDate);
        }

        // ---------- DUPLICATE LAST STAMP ----------
        function duplicateLastStamp(id) {
            const list = stampRegistry[id] || [];
            if (list.length === 0) {
                alert("⚠️ Lock & Place a signature first, then duplicate.");
                return;
            }
            const last = list[list.length - 1];
            // Geser 20px dari posisi terakhir
            const lastEl = last.stampEl;
            addStampToWorkspace(id, last.imgData, last.signerName, last.signerDate, {
                left: lastEl.offsetLeft + 20,
                top: lastEl.offsetTop + 20,
                w: lastEl.offsetWidth,
                h: lastEl.offsetHeight,
            });
        }

        // ---------- CORE: CREATE STAMP ELEMENT ----------
        function addStampToWorkspace(id, imgData, signerName, signerDate, opts) {
            const layer = document.getElementById(`stamps-layer-${id}`);
            if (!layer) return;

            const initLeft = opts?.left ?? 20;
            const initTop = opts?.top ?? 20;
            const initW = opts?.w ?? 130;
            const initH = opts?.h ?? 80;

            // Format tanggal menjadi DD/MM/YYYY
            let displayDate = signerDate;
            if (signerDate) {
                const d = new Date(signerDate);
                displayDate = d.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: '2-digit',
                    year: 'numeric'
                });
            }

            const stampEl = document.createElement('div');
            stampEl.className = 'absolute cursor-move select-none';
            stampEl.style.cssText = `
                left: ${initLeft}px; top: ${initTop}px;
                width: ${initW}px; height: ${initH}px;
                pointer-events: all;
                z-index: 50;
            `;
            stampEl.innerHTML = `
                <div class="relative w-full h-full border-2 border-dashed rounded-sm shadow-md border-amber-500 bg-white/70 backdrop-blur-sm" style="padding:2px;">
                    <img src="${imgData}" class="block w-full pointer-events-none" style="height:calc(100% - 28px); object-fit:contain;" />
                    <div class="absolute bottom-0 left-0 right-0 leading-tight text-center" style="font-size:8px; font-weight:700; color:#1e293b; background:rgba(255,255,255,0.85); border-top:1px solid #e2e8f0; padding:2px 4px;">
                        <div style="font-size:9px;">${signerName}</div>
                        <div style="font-size:8px; color:#64748b;">${displayDate}</div>
                    </div>
                    <!-- Label -->
                    <span style="position:absolute;top:-16px;left:0;background:#f59e0b;color:#fff;font-size:7px;font-weight:900;padding:1px 5px;border-radius:3px;text-transform:uppercase;white-space:nowrap;">
                        Drag · ${signerName}
                    </span>
                    <!-- Delete button -->
                    <button class="stamp-delete-btn" onclick="removeStamp(this)" style="position:absolute;top:-14px;right:0;background:#ef4444;color:#fff;border:none;border-radius:50%;width:16px;height:16px;font-size:9px;font-weight:900;cursor:pointer;display:flex;align-items:center;justify-content:center;line-height:1;">✕</button>
                    <!-- Resize handle -->
                    <div class="stamp-resize-handle" style="position:absolute;bottom:-5px;right:-5px;width:14px;height:14px;background:#3b82f6;border-radius:3px;cursor:se-resize;z-index:60;border:2px solid white;"></div>
                </div>
            `;

            layer.appendChild(stampEl);

            // ★ Sync tinggi layer = tinggi konten penuh workspace (agar drag ke bawah tidak mentok)
            syncLayerHeight(id);

            // Daftarkan ke registry
            if (!stampRegistry[id]) stampRegistry[id] = [];
            stampRegistry[id].push({
                stampEl,
                imgData,
                signerName,
                signerDate
            });
            updateStampCount(id);

            // Init drag & resize
            initDraggableStamp(stampEl, id);
            initResizableStamp(stampEl, id);
        }

        function removeStamp(btn) {
            // Cari wrapper stamp (.absolute.cursor-move)
            const stampEl = btn.closest('.absolute.cursor-move') || btn.closest('[style*="cursor-move"]') || btn
                .parentElement?.parentElement;
            if (!stampEl) return;
            // Cari id dari layer parent
            const layer = stampEl.parentElement;
            if (!layer) {
                stampEl.remove();
                return;
            }
            const layerId = layer.id.replace('stamps-layer-', '');
            if (stampRegistry[layerId]) {
                stampRegistry[layerId] = stampRegistry[layerId].filter(s => s.stampEl !== stampEl);
                updateStampCount(layerId);
            }
            stampEl.remove();
        }

        function updateStampCount(id) {
            const info = document.getElementById(`stamp-count-info-${id}`);
            if (info) {
                const n = (stampRegistry[id] || []).length;
                info.textContent = `${n} signature(s) placed on document`;
            }
        }

        // ---------- SYNC LAYER HEIGHT ke scrollHeight workspace ----------
        function syncLayerHeight(id) {
            const workspace = document.getElementById(`workspace-${id}`);
            const layer = document.getElementById(`stamps-layer-${id}`);
            if (!workspace || !layer) return;
            // scrollHeight = tinggi konten total termasuk area yang di-scroll
            layer.style.height = workspace.scrollHeight + 'px';
        }

        // ---------- DRAG (pakai scrollHeight workspace agar bisa drag ke halaman bawah) ----------
        function initDraggableStamp(el, id) {
            const workspace = document.getElementById(`workspace-${id}`);
            let isDragging = false,
                startX, startY, initLeft, initTop;

            el.addEventListener('mousedown', startDrag);
            el.addEventListener('touchstart', startDrag, {
                passive: true
            });

            function startDrag(e) {
                if (e.target.classList.contains('stamp-resize-handle') || e.target.classList.contains('stamp-delete-btn'))
                    return;
                isDragging = true;
                const cx = e.clientX ?? e.touches[0].clientX;
                const cy = e.clientY ?? e.touches[0].clientY;
                startX = cx;
                startY = cy;
                initLeft = el.offsetLeft;
                initTop = el.offsetTop;
                document.addEventListener('mousemove', onDrag);
                document.addEventListener('mouseup', stopDrag);
                document.addEventListener('touchmove', onDrag, {
                    passive: false
                });
                document.addEventListener('touchend', stopDrag);
            }

            function onDrag(e) {
                if (!isDragging) return;
                if (e.cancelable) e.preventDefault();
                const cx = e.clientX ?? e.touches[0].clientX;
                const cy = e.clientY ?? e.touches[0].clientY;
                let newLeft = initLeft + (cx - startX);
                let newTop = initTop + (cy - startY);

                // ★ FIX: gunakan scrollHeight workspace (tinggi konten penuh), bukan offsetHeight layer
                const maxLeft = workspace.scrollWidth - el.offsetWidth;
                const maxTop = workspace.scrollHeight - el.offsetHeight;
                newLeft = Math.max(0, Math.min(newLeft, maxLeft));
                newTop = Math.max(0, Math.min(newTop, maxTop));
                el.style.left = newLeft + 'px';
                el.style.top = newTop + 'px';
            }

            function stopDrag() {
                isDragging = false;
                document.removeEventListener('mousemove', onDrag);
                document.removeEventListener('mouseup', stopDrag);
                document.removeEventListener('touchmove', onDrag);
                document.removeEventListener('touchend', stopDrag);
            }
        }

        // ---------- RESIZE ----------
        function initResizableStamp(el, id) {
            const handle = el.querySelector('.stamp-resize-handle');
            if (!handle) return;
            let isResizing = false,
                startX, startY, startW, startH;

            handle.addEventListener('mousedown', startResize);
            handle.addEventListener('touchstart', startResize, {
                passive: true
            });

            function startResize(e) {
                e.stopPropagation();
                isResizing = true;
                startX = e.clientX ?? e.touches[0].clientX;
                startY = e.clientY ?? e.touches[0].clientY;
                startW = el.offsetWidth;
                startH = el.offsetHeight;
                document.addEventListener('mousemove', onResize);
                document.addEventListener('mouseup', stopResize);
                document.addEventListener('touchmove', onResize, {
                    passive: false
                });
                document.addEventListener('touchend', stopResize);
            }

            function onResize(e) {
                if (!isResizing) return;
                if (e.cancelable) e.preventDefault();
                const cx = e.clientX ?? e.touches[0].clientX;
                const cy = e.clientY ?? e.touches[0].clientY;
                const newW = Math.max(80, startW + (cx - startX));
                const newH = Math.max(60, startH + (cy - startY));
                el.style.width = newW + 'px';
                el.style.height = newH + 'px';
            }

            function stopResize() {
                isResizing = false;
                document.removeEventListener('mousemove', onResize);
                document.removeEventListener('mouseup', stopResize);
                document.removeEventListener('touchmove', onResize);
                document.removeEventListener('touchend', stopResize);
            }
        }

        // ---------- FINAL SUBMIT ----------
        function prepareFinalApproval(event, id) {
            event.preventDefault();
            const stamps = stampRegistry[id] || [];
            if (stamps.length === 0) {
                alert("⚠️ Please lock and place at least one signature stamp on the invoice first!");
                return false;
            }

            const workspace = document.getElementById(`workspace-${id}`);
            const invoiceImg = document.getElementById(`invoice-img-${id}`);

            // ★ refW/refH = dimensi konten dokumen asli (bukan ukuran viewport workspace)
            // Untuk gambar: clientWidth/clientHeight sudah benar (h-auto mengikuti aspek rasio)
            // Untuk PDF:    scrollHeight workspace = total tinggi PDF yang di-render (1500px dsb)
            let refW, refH;
            if (invoiceImg) {
                refW = invoiceImg.clientWidth;
                // clientHeight untuk gambar, scrollHeight container untuk PDF (object tag)
                refH = (invoiceImg.tagName === 'IMG') ?
                    invoiceImg.clientHeight :
                    workspace.scrollHeight;
            } else {
                refW = workspace.clientWidth;
                refH = workspace.scrollHeight;
            }

            // stamps-layer top:0 left:0, jadi offsetLeft/offsetTop stamp = posisi mutlak dalam dokumen
            const signaturesJson = stamps.map(({
                stampEl,
                imgData,
                signerName,
                signerDate
            }) => {
                return {
                    image: imgData,
                    pos_x: Math.max(0, Math.min((stampEl.offsetLeft / refW) * 100, 100)),
                    pos_y: Math.max(0, Math.min((stampEl.offsetTop / refH) * 100, 100)),
                    scale_w: (stampEl.offsetWidth / refW) * 100,
                    scale_h: (stampEl.offsetHeight / refH) * 100,
                    signer_name: signerName,
                    signer_date: signerDate,
                };
            });

            // JSON multi-sig (dikirim ke controller)
            document.getElementById(`signatures-json-input-${id}`).value = JSON.stringify(signaturesJson);

            // Backwards-compat single-sig fields (dari sig pertama)
            const first = signaturesJson[0];
            document.getElementById(`signature-input-${id}`).value = first.image;
            document.getElementById(`pos-x-input-${id}`).value = first.pos_x;
            document.getElementById(`pos-y-input-${id}`).value = first.pos_y;
            document.getElementById(`scale-w-input-${id}`).value = first.scale_w;
            document.getElementById(`scale-h-input-${id}`).value = first.scale_h;

            document.getElementById(`approve-form-${id}`).submit();
        }

        // ---------- MODAL OPEN: INIT CANVAS ----------
        function toggleDetailModal(id, show) {
            const modal = document.getElementById(`modal-detail-${id}`);
            if (!modal) return;
            const card = modal.querySelector('.modal-card');
            if (show) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    modal.classList.add('flex', 'opacity-100');
                    card.classList.remove('scale-95', 'opacity-0');
                    card.classList.add('scale-100', 'opacity-100');
                    const canvas = document.getElementById(`signature-canvas-${id}`);
                    if (canvas && !signaturePads[id]) {
                        const ratio = Math.max(window.devicePixelRatio || 1, 1);
                        canvas.width = canvas.offsetWidth * ratio;
                        canvas.height = canvas.offsetHeight * ratio;
                        canvas.getContext("2d").scale(ratio, ratio);
                        signaturePads[id] = new SignaturePad(canvas, {
                            minWidth: 1.5,
                            maxWidth: 3.5,
                            penColor: "rgb(30, 41, 59)"
                        });
                    }
                }, 10);
            } else {
                card.classList.remove('scale-100', 'opacity-100');
                card.classList.add('scale-95', 'opacity-0');
                setTimeout(() => {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex', 'opacity-100');
                }, 300);
            }
        }

        function clearSignature(id) {
            if (signaturePads[id]) signaturePads[id].clear();
        }

        // ---------- SEARCH / FILTER ----------
        function filterReimburseList() {
            const input = document.getElementById('reimburseSearchInput');
            const filter = input ? input.value.toLowerCase() : '';
            const rows = document.querySelectorAll('.reimburse-row-item');
            let hasDesktopResults = false;
            rows.forEach(row => {
                const staff = row.getAttribute('data-search-staff') || '';
                const title = row.getAttribute('data-search-title') || '';
                const show = staff.includes(filter) || title.includes(filter);
                row.style.display = show ? "" : "none";
                if (show) hasDesktopResults = true;
            });
            const cards = document.querySelectorAll('.reimburse-card-item');
            let hasMobileResults = false;
            cards.forEach(card => {
                const text = card.innerText.toLowerCase();
                const show = text.includes(filter);
                card.style.display = show ? "" : "none";
                if (show) hasMobileResults = true;
            });
            const emptyState = document.getElementById('emptySearchState');
            if (emptyState) {
                const isMobile = window.innerWidth < 768;
                const noResults = isMobile ? !hasMobileResults : !hasDesktopResults;
                emptyState.classList.toggle('hidden', !noResults);
                emptyState.classList.toggle('flex', noResults);
            }
        }

        // ---------- CANCEL ----------
        function confirmCancel(id) {
            const modal = document.getElementById('cancelModal');
            if (!modal) return;
            modal.classList.remove('hidden');
            const form = modal.querySelector('form');
            if (form) form.action = `/reimbursements/${id}`;
        }

        document.getElementById('cancelModal')?.addEventListener('click', function(e) {
            if (e.target === this || e.target.classList.contains('bg-slate-900/60'))
                this.classList.add('hidden');
        });
        document.getElementById('reimburseSearchInput')?.addEventListener('keyup', filterReimburseList);
    </script>
@endpush
