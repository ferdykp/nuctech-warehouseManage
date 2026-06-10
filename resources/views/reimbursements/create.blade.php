@extends('layout.master')

@section('title', 'New Reimbursement Claim')

@section('content')
    <div class="w-full max-w-2xl px-6 py-8 pb-10 mx-auto space-y-6">
        <div class="space-y-1">
            <h2 class="text-2xl font-black tracking-tighter text-slate-800">File Operational Claim</h2>
            <p class="text-xs font-medium text-slate-500">Submit your operational expenses with explicit categories and
                invoice proof.</p>
        </div>

        <div class="bg-white border border-slate-100 shadow-sm rounded-[2rem] p-6 md:p-8">
            <form method="POST" action="{{ route('reimbursements.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    {{-- Person Name --}}
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Person
                            Name</label>
                        <input type="text" name="person_name" required placeholder="e.g., John Doe"
                            class="w-full px-4 py-3 text-xs font-medium border border-slate-100 bg-slate-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">
                    </div>

                    {{-- Date --}}
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Date of
                            Expense</label>
                        <input type="date" name="date" required value="{{ date('Y-m-d') }}"
                            class="w-full px-4 py-3 text-xs font-medium border border-slate-100 bg-slate-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">
                    </div>
                </div>

                {{-- Expense Category --}}
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Expense
                        Category</label>
                    <select name="category" id="categorySelect" onchange="handleCategoryChange()" required
                        class="w-full px-4 py-3 text-xs font-bold border border-slate-100 bg-slate-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">
                        <option value="office">Office Supplies / Others</option>
                        <option value="transportation">Transportation</option>
                        <option value="delivery">Delivery / Logistics</option>
                    </select>
                </div>

                {{-- Dynamic Routing Fields (From & To) --}}
                <div id="routingFields"
                    class="grid hidden grid-cols-1 gap-4 p-4 transition-all duration-300 border sm:grid-cols-2 bg-slate-50 rounded-2xl border-slate-100">
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">From
                            (Origin)</label>
                        <input type="text" name="from_location" id="fromLocation" placeholder="e.g., Warehouse Jakarta"
                            class="w-full px-4 py-3 text-xs font-medium bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">
                    </div>
                    <div>
                        <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">To
                            (Destination)</label>
                        <input type="text" name="to_location" id="toLocation" placeholder="e.g., Site Port Tanjung Priok"
                            class="w-full px-4 py-3 text-xs font-medium bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">
                    </div>
                </div>

                {{-- Amount (Satu Elemen Bersih Tanpa Label Bawah) --}}
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Amount
                        (IDR)</label>
                    <div class="relative">
                        <span
                            class="absolute inset-y-0 left-0 flex items-center pl-4 text-xs font-black pointer-events-none text-slate-400">Rp</span>

                        {{-- 1. Input Mask Palsu (Tipe Text agar bisa pakai titik ribuan) --}}
                        <input type="text" id="currencyMaskInput" required placeholder="e.g., 250.000"
                            class="w-full py-3 pl-10 pr-4 text-xs font-black tracking-wide border border-slate-100 bg-slate-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700">

                        {{-- 2. Input Asli Tersembunyi (Dikirim ke Controller berupa angka bersih) --}}
                        <input type="hidden" name="amount" id="actualAmountInput">
                    </div>
                </div>

                {{-- Comment --}}
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Comment /
                        Notes</label>
                    <textarea name="comment" rows="3" placeholder="Write short statement or description regarding the invoice..."
                        class="w-full px-4 py-3 text-xs font-medium border border-slate-100 bg-slate-50/50 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-500/20 focus:border-amber-500 text-slate-700"></textarea>
                </div>

                {{-- Receipt Attachment --}}
                <div>
                    <label class="block text-[10px] font-black uppercase text-slate-400 tracking-wider mb-2">Invoice /
                        Receipt Upload</label>
                    <input type="file" name="receipt_attachment" required accept="image/*,application/pdf"
                        class="w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-[10px] file:font-black file:uppercase file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100 cursor-pointer transition-all">
                    <p class="text-[9px] text-slate-400 mt-2">Format: JPG, JPEG, PNG, PDF. Max file size: 4MB.</p>
                </div>

                <div class="flex flex-col gap-3 pt-4 border-t sm:flex-row border-slate-50">
                    <button type="submit"
                        class="flex-1 py-4 text-xs font-black tracking-widest text-center text-white uppercase transition-all shadow-lg bg-amber-600 rounded-xl shadow-amber-600/10 active:scale-95">
                        Submit Claim
                    </button>
                    <a href="{{ route('reimbursements.index') }}"
                        class="px-6 py-4 text-xs font-black tracking-widest text-center uppercase transition-all text-slate-400 bg-slate-50 rounded-xl active:scale-95">
                        Cancel
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function handleCategoryChange() {
            const category = document.getElementById('categorySelect').value;
            const routingFields = document.getElementById('routingFields');
            const fromInput = document.getElementById('fromLocation');
            const toInput = document.getElementById('toLocation');

            if (category === 'transportation' || category === 'delivery') {
                routingFields.classList.remove('hidden');
                fromInput.setAttribute('required', 'required');
                toInput.setAttribute('required', 'required');
            } else {
                routingFields.classList.add('hidden');
                fromInput.removeAttribute('required');
                toInput.removeAttribute('required');
                fromInput.value = '';
                toInput.value = '';
            }
        }

        // 🛠️ FIX TOTAL: Logika Live Masking di Dalam Kotak Input
        const maskInput = document.getElementById('currencyMaskInput');
        const actualInput = document.getElementById('actualAmountInput');

        maskInput?.addEventListener('input', function(e) {
            // 1. Bersihkan semua karakter non-angka (seperti titik atau huruf yang tidak sengaja terketik)
            let rawValue = e.target.value.replace(/\D/g, '');

            if (rawValue === '') {
                maskInput.value = '';
                actualInput.value = '';
                return;
            }

            // 2. Simpan nilai angka bersih ke input hidden untuk dikirim ke Backend Laravel
            actualInput.value = rawValue;

            // 3. Format angka murni tadi menjadi format titik ribuan lokal (id-ID)
            let formattedValue = new Intl.NumberFormat('id-ID').format(rawValue);

            // 4. Masukkan kembali teks yang sudah rapi ber-titik ke dalam layar ketikan user
            e.target.value = formattedValue;
        });

        document.addEventListener("DOMContentLoaded", handleCategoryChange);
    </script>
@endpush
