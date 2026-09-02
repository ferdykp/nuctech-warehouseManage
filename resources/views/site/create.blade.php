@extends('layout.master')

@section('title', 'Add New Site')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- 1. HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <a href="{{ route('site.index') }}" class="transition-colors hover:text-blue-600">Machine Sites</a>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Register Machine</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Add New Site
                    </h1>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">
                        Register a new machine location unit into the fleet.
                    </p>
                </div>
                <a href="{{ route('site.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-all rounded-xl active:scale-95 shrink-0">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>Back</span>
                </a>
            </div>
        </div>

        {{-- 2. FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Site Configuration</h2>
            </div>

            <form action="{{ route('site.store') }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    {{-- SITE CODE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Code
                        </label>
                        <input type="text" name="code" id="code" readonly
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-500 font-bold cursor-not-allowed"
                            placeholder="Auto-generated...">
                    </div>

                    {{-- MACHINE NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Name / Label <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="machine_name" id="machine_name_input" required
                            value="{{ old('machine_name') }}"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-medium border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="e.g. FS6000 Jakarta HQ">
                    </div>

                    {{-- MACHINE TYPE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Machine Type
                        </label>
                        <input type="text" name="machine_type" id="machine_type" readonly
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm bg-slate-100 border border-slate-200 rounded-xl font-mono text-slate-500 font-bold cursor-not-allowed"
                            placeholder="Auto-detected...">
                    </div>

                    {{-- IS ACTIVE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Operational Status
                        </label>
                        <select name="is_active"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 cursor-pointer">
                            <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                </div>

                {{-- BUTTONS --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <div class="text-xs font-medium text-slate-400">
                        Asterisk (<span class="text-rose-500">*</span>) fields are required.
                    </div>
                    <div class="flex items-center gap-3">
                        <a href="{{ route('site.index') }}"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Cancel
                        </a>
                        <button type="submit"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 hover:bg-blue-700 rounded-xl shadow-md shadow-blue-600/20 active:scale-95 cursor-pointer">
                            <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Register Machine
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
        <script>
            function initSiteCodeGenerator() {
                const machineName = document.getElementById('machine_name_input');
                const machineType = document.getElementById('machine_type');
                const code = document.getElementById('code');

                if (machineName) {
                    machineName.replaceWith(machineName.cloneNode(true));
                    const newMachineName = document.getElementById('machine_name_input');

                    newMachineName.addEventListener('input', function() {
                        const value = this.value.trim();

                        if (!value) {
                            if (machineType) machineType.value = '';
                            if (code) code.value = '';
                            return;
                        }

                        const firstWord = value.split(' ')[0];
                        if (machineType) machineType.value = firstWord.toLowerCase();
                        if (code) code.value = 'IDN_' + firstWord.toUpperCase();
                    });
                }
            }

            document.addEventListener('DOMContentLoaded', initSiteCodeGenerator);

            if (window.up) {
                up.on('up:fragment:inserted', function() {
                    initSiteCodeGenerator();
                });
            }
        </script>
    @endpush
@endsection
