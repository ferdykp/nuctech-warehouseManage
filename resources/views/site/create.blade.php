@extends('layout.master')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Add New Site</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Register a new machine location unit.</p>
            </div>
            <a href="{{ route('site.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Site Configuration</h2>
            </div>

            <form action="{{ route('site.store') }}" method="POST" class="p-6 space-y-5 sm:p-8">
                @csrf

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- CODE SITE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Code
                        </label>
                        <input type="text" name="code" id="code" readonly
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-500 cursor-not-allowed">
                    </div>

                    {{-- MACHINE NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Name
                        </label>
                        <input type="text" name="name" id="name" required
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                            placeholder="e.g. FS6000 Jakarta">
                    </div>

                    {{-- MACHINE TYPE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Type
                        </label>
                        <input type="text" name="machine_type" id="machine_type" readonly
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-500 cursor-not-allowed">
                    </div>

                    {{-- IS ACTIVE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Operational Status
                        </label>
                        <select name="is_active"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                {{-- BUTTONS --}}
                <div class="flex items-center justify-end gap-2.5 pt-4 border-t border-slate-100">
                    <a href="{{ route('site.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Register Machine
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- AUTO GENERATE SCRIPT --}}
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const machineName = document.getElementById('name');
            const machineType = document.getElementById('machine_type');
            const code = document.getElementById('code');

            if (machineName) {
                machineName.addEventListener('input', function() {
                    const value = this.value.trim();

                    if (!value) {
                        machineType.value = '';
                        code.value = '';
                        return;
                    }

                    const firstWord = value.split(' ')[0];
                    machineType.value = firstWord.toLowerCase();
                    code.value = 'IDN_' + firstWord.toUpperCase();
                });
            }
        });
    </script>
@endsection
