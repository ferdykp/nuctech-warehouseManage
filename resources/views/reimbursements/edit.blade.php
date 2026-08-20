@extends('layout.master')

@section('title', 'Edit Reimbursement Claim')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('reimbursements.index') }}"
                            class="transition-colors hover:text-amber-600">Reimbursements</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Update Claim Record</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Edit Claim Record
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Modify expense claim details or update receipt document proof.
                    </p>
                </div>
                <a href="{{ route('reimbursements.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Claim Details</h2>
            </div>

            <form action="{{ route('reimbursements.update', $reimbursement->id) }}" method="POST"
                enctype="multipart/form-data" class="p-6 space-y-5 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- REQUESTER NAME --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Requester Staff Name <span class="text-rose-500">*</span>
                        </label>
                        <select name="person_name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="">-- Select Requester --</option>
                            @foreach ($employees as $emp)
                                <option value="{{ $emp->name }}"
                                    {{ old('person_name', $reimbursement->person_name) == $emp->name ? 'selected' : '' }}>
                                    {{ $emp->name }} ({{ $emp->position ?? 'Staff' }})
                                </option>
                            @endforeach
                        </select>
                        @error('person_name')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- DATE FILED --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Date Filed <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="date" value="{{ old('date', $reimbursement->date) }}" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-semibold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        @error('date')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- CATEGORY --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Expense Category <span class="text-rose-500">*</span>
                        </label>
                        <select name="category" id="categorySelect" onchange="toggleRouteFields()" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="transportation"
                                {{ old('category', $reimbursement->category) == 'transportation' ? 'selected' : '' }}>
                                Transportation</option>
                            <option value="delivery"
                                {{ old('category', $reimbursement->category) == 'delivery' ? 'selected' : '' }}>Delivery /
                                Logistics</option>
                            <option value="office"
                                {{ old('category', $reimbursement->category) == 'office' ? 'selected' : '' }}>Office
                                Supplies & Maintenance</option>
                        </select>
                        @error('category')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- ROUTE FIELDS --}}
                    <div id="routeFields" class="grid grid-cols-1 gap-5 md:grid-cols-2 md:col-span-2">
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                From Location <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="from_location"
                                value="{{ old('from_location', $reimbursement->from_location) }}"
                                placeholder="Origin location"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                To Location <span class="text-rose-500">*</span>
                            </label>
                            <input type="text" name="to_location"
                                value="{{ old('to_location', $reimbursement->to_location) }}"
                                placeholder="Destination location"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                        </div>
                    </div>

                    {{-- AMOUNT --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Total Claim Amount (Rp) <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 pointer-events-none text-xs font-black text-slate-400">Rp</span>

                            <input type="text" id="amountDisplay"
                                value="{{ number_format(old('amount', $reimbursement->amount), 0, ',', '.') }}" required
                                placeholder="0" onkeyup="formatCurrency(this)"
                                class="w-full py-2.5 pl-10 pr-3.5 text-xs sm:text-sm font-black border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">

                            <input type="hidden" name="amount" id="amountRaw"
                                value="{{ old('amount', $reimbursement->amount) }}">
                        </div>
                        @error('amount')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- COMMENT --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Invoice No. / Description
                        </label>
                        <textarea name="comment" rows="2" placeholder="e.g. INV-10293 or taxi fare description..."
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">{{ old('comment', $reimbursement->comment) }}</textarea>
                    </div>

                    {{-- RECEIPT FILE ATTACHMENT --}}
                    <div class="space-y-1.5 md:col-span-2">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Update Receipt Attachment
                            <span class="text-[10px] text-slate-400 font-normal lowercase">(Leave blank if unchanged)</span>
                        </label>

                        @if ($reimbursement->receipt_attachment)
                            <div
                                class="flex items-center gap-2.5 p-3 mb-2 border rounded-2xl bg-slate-50/50 border-slate-200/80">
                                <i class="fa-solid fa-paperclip text-slate-400"></i>
                                <span class="flex-1 text-xs font-semibold truncate text-slate-700">Current file:
                                    {{ basename($reimbursement->receipt_attachment) }}</span>
                                <a href="/storage/{{ $reimbursement->receipt_attachment }}" target="_blank"
                                    class="text-xs font-bold text-amber-700 hover:underline">View File</a>
                            </div>
                        @endif

                        <input type="file" name="receipt_attachment" accept=".jpg,.jpeg,.png,.pdf"
                            class="w-full text-xs border cursor-pointer text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-amber-50 file:text-amber-800 hover:file:bg-amber-100 border-slate-200 rounded-xl bg-slate-50">
                        @error('receipt_attachment')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                </div>

                {{-- FORM ACTIONS --}}
                <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('reimbursements.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-amber-600 hover:bg-amber-700 rounded-xl shadow-md shadow-amber-600/20 active:scale-[0.98]">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleRouteFields() {
            const category = document.getElementById('categorySelect').value;
            const routeFields = document.getElementById('routeFields');
            if (category === 'transportation' || category === 'delivery') {
                routeFields.classList.remove('hidden');
            } else {
                routeFields.classList.add('hidden');
            }
        }

        function formatCurrency(input) {
            let rawValue = input.value.replace(/[^0-9]/g, '');
            document.getElementById('amountRaw').value = rawValue;
            if (rawValue) {
                input.value = new Intl.NumberFormat('id-ID').format(rawValue);
            } else {
                input.value = '';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleRouteFields();
        });
    </script>
@endpush
