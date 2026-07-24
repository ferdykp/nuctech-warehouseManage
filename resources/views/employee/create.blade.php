@extends('layout.master')

@section('title', 'Add New Employee')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Add New Employee
                </h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">
                    Register a new staff member into the system database.
                </p>
            </div>
            <a href="{{ route('employee.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <form action="{{ route('employee.store') }}" method="POST"
            class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            @csrf

            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Employment Details</h2>
            </div>

            <div class="p-6 space-y-5 sm:p-8">
                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    {{-- FULL NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Full Name <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="name"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Full name as per ID..." required>
                    </div>

                    {{-- PHONE NUMBER --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Phone Number <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" name="phone_number"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="Phone number..." required>
                    </div>

                    {{-- SITE LOCATION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Site Placement <span class="text-rose-500">*</span>
                        </label>

                        @if (Auth::user()->role === 'superadmin')
                            <select name="site_id"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                                required>
                                <option value="">-- Select Site Location --</option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}">
                                        {{ $site->machine_name }} (Branch: {{ $site->branch->branch_name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" value="{{ Auth::user()->site_id }}">
                            <input type="text"
                                value="{{ Auth::user()->site->machine_name ?? 'Registered Site' }} (Branch: {{ Auth::user()->site->branch->branch_name ?? '-' }})"
                                class="w-full px-3.5 py-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                                readonly>
                        @endif
                        <p class="text-[11px] text-slate-400 mt-1">Branch will automatically sync based on the selected
                            Site.</p>
                    </div>

                    {{-- STATUS --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Employment Status
                        </label>
                        <select name="status"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                            <option value="Probation" selected>Probation</option>
                            <option value="Contract">Contract</option>
                            <option value="Permanent">Permanent</option>
                            <option value="Daily">Daily</option>
                        </select>
                    </div>

                    {{-- POSITION --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Position / Job Title
                        </label>
                        <input type="text" name="position"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800 placeholder-slate-400"
                            placeholder="e.g. Supervisor, Operator, Admin">
                    </div>

                    {{-- JOIN DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Join Date <span class="text-rose-500">*</span>
                        </label>
                        <input type="date" name="join_date"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800"
                            required>
                    </div>

                    {{-- CONTRACT START DATE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Contract Start Date
                        </label>
                        <input type="date" name="contract_start_date"
                            class="w-full px-3.5 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white text-slate-800">
                    </div>
                </div>
            </div>

            <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100 bg-slate-50/50">
                <div class="text-xs font-medium text-slate-400">
                    Asterisk (<span class="text-rose-500">*</span>) fields are required.
                </div>
                <div class="flex items-center gap-2.5">
                    <a href="{{ route('employee.index') }}"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Save Employee
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection
