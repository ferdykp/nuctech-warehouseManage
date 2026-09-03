@extends('layout.master')

@section('title', 'Schedule Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- 1. HEADER CARD (TERPISAH) --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-amber-600">Attendance & Roster</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-amber-600">Work Schedules</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Employee Work Schedules
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Monitor duty schedules, configure site work patterns, and generate team rotas automatically.
                    </p>
                    @if (Auth::user()?->role === 'team_leader')
                        <p
                            class="mt-2 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-building-user"></i> Access Mode: Site Admin
                            ({{ Auth::user()->site->machine_name ?? 'Registered Site' }})
                        </p>
                    @endif
                </div>

                {{-- ACTION BUTTONS TOOLBAR --}}
                <div class="flex flex-wrap items-center gap-2.5 shrink-0">
                    <button type="button" onclick="openGenerateModal()"
                        class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95">
                        <i class="fa-solid fa-wand-magic-sparkles"></i> Generate Rotas
                    </button>

                    {{-- EXPORT EXCEL BUTTON --}}
                    @if (Auth::user()?->role === 'superadmin')
                        {{-- Jika Superadmin: Buka Modal Pilihan Export --}}
                        <button type="button" onclick="openModal('modal-export-excel')"
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95"
                            title="Export schedule options">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </button>
                    @else
                        {{-- Jika User Site (ebeam, team leader, dsb): Langsung Download Site Miliknya --}}
                        <a href="{{ route('schedule.export', ['site_id' => Auth::user()->site_id, 'month' => sprintf('%02d', $month), 'year' => $year]) }}"
                            up-follow="false" download
                            class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95"
                            title="Export schedule to Excel">
                            <i class="fa-solid fa-file-excel"></i> Export Excel
                        </a>
                    @endif

                    <!-- RESET SCHEDULE BUTTON -->
                    <button type="button" onclick="openModal('modal-clear')"
                        class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold text-rose-600 transition-all border border-rose-200/80 bg-rose-50 hover:bg-rose-100 rounded-xl active:scale-95">
                        <i class="fa-solid fa-trash-can"></i> Reset Schedule
                    </button>
                </div>
            </div>
        </div>

        {{-- 2. ALERTS SECTION --}}
        @if (session('success'))
            <div
                class="flex items-center gap-3 p-4 text-xs font-bold border sm:text-sm text-emerald-800 border-emerald-200/80 bg-emerald-50 rounded-2xl shadow-2xs">
                <i class="text-base fa-solid fa-circle-check text-emerald-600 shrink-0"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="p-4 space-y-1.5 text-xs sm:text-sm border text-rose-800 border-rose-200/80 bg-rose-50 rounded-2xl shadow-2xs">
                <div class="flex items-center gap-2 font-extrabold">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600 shrink-0"></i> Failed to process schedule
                    update, please verify:
                </div>
                <ul class="list-disc pl-5 space-y-0.5 font-semibold">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- =========================================================== --}}
        {{-- MODAL: EXPORT EXCEL OPTIONS (KHUSUS SUPERADMIN) --}}
        {{-- =========================================================== --}}
        @if (Auth::user()?->role === 'superadmin')
            <div id="modal-export-excel"
                class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
                onclick="if(event.target===this) closeModal('modal-export-excel')">
                <div class="w-full max-w-md overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
                    {{-- Modal Header --}}
                    <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                        <div class="flex items-center gap-3">
                            <div
                                class="flex items-center justify-center w-10 h-10 border text-emerald-600 bg-emerald-50 border-emerald-100 rounded-2xl shrink-0">
                                <i class="text-lg fa-solid fa-file-excel"></i>
                            </div>
                            <div>
                                <h3 class="text-base font-extrabold text-slate-900">Export Schedule to Excel</h3>
                                <p class="text-xs font-medium text-slate-500 mt-0.5">Pilih cakupan site dan periode jadwal
                                    yang ingin diexport.</p>
                            </div>
                        </div>
                        <button type="button" onclick="closeModal('modal-export-excel')"
                            class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
                    </div>

                    {{-- Form Export --}}
                    <form action="{{ route('schedule.export') }}" method="GET" class="p-6 space-y-4">
                        {{-- Pilihan Site Location --}}
                        <div class="space-y-1.5">
                            <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                                Select Site Scope
                            </label>
                            <select name="site_id" id="export_site_id"
                                class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800">
                                <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>
                                    🌐 All Sites (Semua Site)
                                </option>
                                @foreach ($sites as $st)
                                    <option value="{{ $st->id }}"
                                        {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                        📍 {{ $st->machine_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Pilihan Month & Year --}}
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Month</label>
                                <select name="month"
                                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800">
                                    @for ($m = 1; $m <= 12; $m++)
                                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == $m ? 'selected' : '' }}>
                                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                        </option>
                                    @endfor
                                </select>
                            </div>

                            <div class="space-y-1.5">
                                <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Year</label>
                                <select name="year"
                                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-emerald-500/10 focus:border-emerald-500 outline-none transition-all text-slate-800">
                                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                            {{ $y }}</option>
                                    @endfor
                                </select>
                            </div>
                        </div>

                        {{-- Action Buttons --}}
                        <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                            <button type="button" onclick="closeModal('modal-export-excel')"
                                class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                                Cancel
                            </button>
                            <button type="submit" onclick="closeModal('modal-export-excel')"
                                class="inline-flex items-center gap-2 px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 rounded-xl hover:bg-emerald-700 shadow-emerald-600/20 active:scale-95">
                                <i class="fa-solid fa-download"></i> Download Excel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        {{-- 3. MAIN WORKSPACE CARD CONTAINER --}}
        <div class="space-y-6 overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">

            {{-- FILTER BAR --}}
            <div class="p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50">
                <form action="{{ route('schedule.index') }}" method="GET" id="mainFilterForm"
                    class="flex flex-wrap items-end gap-3.5">

                    @if (Auth::user()?->role === 'superadmin')
                        <div class="w-full sm:w-56">
                            <label
                                class="block mb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Site
                                Location</label>
                            <select name="site_id" id="main_site_select"
                                class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                                <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>-- All
                                    Sites --</option>
                                @foreach ($sites as $st)
                                    <option value="{{ $st->id }}"
                                        {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                        {{ $st->machine_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @else
                        <input type="hidden" id="main_site_select" value="{{ Auth::user()->site_id }}">
                    @endif

                    <div class="w-1/2 sm:w-44">
                        <label
                            class="block mb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Month</label>
                        <select name="month" id="main_month_select"
                            class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ sprintf('%02d', $m) }}" {{ $month == $m ? 'selected' : '' }}>
                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div class="w-1/2 sm:w-36">
                        <label
                            class="block mb-1.5 text-[10px] font-extrabold uppercase tracking-wider text-slate-500">Year</label>
                        <select name="year" id="main_year_select"
                            class="w-full py-2.5 px-3.5 text-xs sm:text-sm font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                    {{ $y }}</option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95">
                        <i class="mr-1.5 fa-solid fa-filter"></i> Apply Filter
                    </button>
                </form>
            </div>

            {{-- NATIONAL HOLIDAYS BANNER --}}
            @if (!empty($holidays))
                <div class="p-4 mx-5 border sm:mx-6 border-rose-200/80 rounded-2xl bg-rose-50/60">
                    <h6 class="flex items-center gap-2 mb-2 text-xs font-extrabold tracking-wider uppercase text-rose-700">
                        <i class="fa-solid fa-circle-exclamation"></i> National Holidays & Red Dates This Month
                    </h6>
                    <ul class="grid grid-cols-1 gap-x-6 gap-y-1.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($holidays as $date => $name)
                            <li class="flex items-baseline gap-2 text-xs">
                                <span
                                    class="w-6 font-black text-right text-rose-700 shrink-0">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                                <span class="font-semibold text-slate-700">{{ $name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- CALENDAR GRID PREVIEW --}}
            <div>
                <div class="flex flex-col justify-between gap-3 px-5 pb-4 sm:px-6 lg:flex-row lg:items-center">
                    <div class="flex flex-wrap items-center gap-2">
                        <h3 class="text-sm font-extrabold text-slate-900 sm:text-base">Monthly Schedule Grid</h3>
                        @if (Auth::user()?->role === 'team_leader')
                            <span
                                class="px-2.5 py-0.5 text-[10px] font-extrabold text-amber-800 bg-amber-50 border border-amber-200/80 rounded-md uppercase">
                                Site: {{ Auth::user()->site->machine_name ?? 'Registered' }}
                            </span>
                        @endif
                        <span
                            class="px-3 py-1 text-xs font-black text-blue-800 uppercase border rounded-full border-blue-200/80 bg-blue-50">
                            {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                        </span>
                    </div>

                    {{-- LEGEND BADGES --}}
                    <div class="flex flex-wrap items-center gap-2.5 text-[10px] font-bold text-slate-500">
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-blue-50 border border-blue-200"></span> Shift
                            1</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-amber-50 border border-amber-200"></span>
                            Shift 2</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-purple-50 border border-purple-200"></span>
                            Shift 3</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-emerald-50 border border-emerald-200"></span>
                            Other / OH</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-rose-50 border border-rose-200"></span>
                            OFF</span>
                        <span class="flex items-center gap-1.5"><span
                                class="inline-block w-2.5 h-2.5 rounded-xs bg-white border border-slate-200"></span>
                            Unassigned</span>
                        <span class="flex items-center gap-1 font-extrabold text-rose-600"><i
                                class="fa-solid fa-circle-exclamation"></i> National Holiday</span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full border-collapse table-fixed min-w-[900px]">
                        <thead>
                            <tr
                                class="text-[10px] font-extrabold uppercase tracking-wider text-slate-500 bg-slate-50 border-y border-slate-100">
                                <th
                                    class="w-48 px-4 py-3.5 text-left sticky left-0 top-0 bg-slate-50 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                    Employee / Site
                                </th>

                                @foreach ($datesInMonth as $date)
                                    @php
                                        $holidayName = $holidays[$date->format('Y-m-d')] ?? null;
                                        $isRedDate = $date->isWeekend() || $holidayName;
                                    @endphp
                                    <th class="w-11 text-center py-2.5 border-l border-slate-100 {{ $isRedDate ? 'bg-rose-50/80 text-rose-700' : '' }}"
                                        @if ($holidayName) title="{{ $holidayName }}" @endif>
                                        <div class="font-black">{{ $date->format('d') }}</div>
                                        <div class="text-[8px] font-bold uppercase mt-0.5">
                                            {{ $date->translatedFormat('D') }}</div>
                                        @if ($holidayName)
                                            <div class="mt-0.5 truncate px-0.5 text-[7px] font-bold text-rose-600"
                                                title="{{ $holidayName }}">
                                                <i class="fa-solid fa-circle-exclamation"></i>
                                            </div>
                                        @endif
                                    </th>
                                @endforeach

                                <th
                                    class="py-2.5 font-extrabold text-center text-blue-800 border-l border-slate-100 w-14 bg-blue-50/60">
                                    <div>WORK</div>
                                    <div class="text-[7px] text-blue-600 uppercase">(Days)</div>
                                </th>
                                <th
                                    class="py-2.5 font-extrabold text-center border-l text-rose-800 border-slate-100 w-14 bg-rose-50/60">
                                    <div>OFF</div>
                                    <div class="text-[7px] text-rose-600 uppercase">(Days)</div>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                            @forelse($employees as $emp)
                                @if (Auth::user()?->role === 'superadmin' ||
                                        (Auth::user()?->role === 'team_leader' && Auth::user()->site_id === $emp->site_id))
                                    <tr class="transition-colors hover:bg-slate-50/60">
                                        <td
                                            class="px-4 py-3 sticky left-0 bg-white font-extrabold text-slate-900 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                            <div class="text-xs truncate">{{ $emp->name }}</div>
                                            <div class="text-[10px] font-semibold text-slate-400 truncate mt-0.5">
                                                {{ $emp->site->machine_name ?? '-' }}
                                            </div>
                                        </td>

                                        @php
                                            $totalWorkCount = 0;
                                            $totalOffCount = 0;
                                            $schedulesByDate = $emp->schedules->keyBy(
                                                fn($s) => $s->date->format('Y-m-d'),
                                            );
                                        @endphp

                                        @foreach ($datesInMonth as $date)
                                            @php
                                                $schedule = $schedulesByDate->get($date->format('Y-m-d'));
                                                $shiftName = $schedule?->shift?->shift_name;

                                                $badgeColor = 'bg-white text-slate-300 border-slate-200';
                                                $label = '-';

                                                if ($schedule && $schedule->shift) {
                                                    if ($schedule->shift->is_off) {
                                                        $badgeColor = 'bg-rose-50 text-rose-800 border-rose-200';
                                                        $label = 'OFF';
                                                        $totalOffCount++;
                                                    } else {
                                                        $totalWorkCount++;
                                                        $label = '';
                                                        foreach (explode(' ', $shiftName) as $word) {
                                                            $label .= strtoupper(substr($word, 0, 1));
                                                        }

                                                        if (
                                                            str_contains(strtolower($shiftName), '1') ||
                                                            str_contains(strtolower($shiftName), 'hour')
                                                        ) {
                                                            $badgeColor = 'bg-blue-50 text-blue-800 border-blue-200';
                                                        } elseif (str_contains(strtolower($shiftName), '2')) {
                                                            $badgeColor = 'bg-amber-50 text-amber-800 border-amber-200';
                                                        } elseif (str_contains(strtolower($shiftName), '3')) {
                                                            $badgeColor =
                                                                'bg-purple-50 text-purple-800 border-purple-200';
                                                        } else {
                                                            $badgeColor =
                                                                'bg-emerald-50 text-emerald-800 border-emerald-200';
                                                        }
                                                    }
                                                }
                                            @endphp
                                            <td class="p-1 text-center border-l border-slate-100">
                                                <button type="button"
                                                    onclick="openEditShiftModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $date->format('Y-m-d') }}', '{{ $schedule?->shift_id ?? '' }}')"
                                                    class="w-full py-1 text-[9px] font-black border rounded-md transition-transform active:scale-95 cursor-pointer {{ $badgeColor }}"
                                                    title="Click to edit shift ({{ $shiftName ?? 'Unassigned' }})">
                                                    {{ $label }}
                                                </button>
                                            </td>
                                        @endforeach

                                        <td
                                            class="px-2 py-3 font-black text-center text-blue-800 border-l border-slate-100 bg-blue-50/30">
                                            {{ $totalWorkCount }}
                                        </td>
                                        <td
                                            class="px-2 py-3 font-black text-center border-l text-rose-800 border-slate-100 bg-rose-50/30">
                                            {{ $totalOffCount }}
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="{{ count($datesInMonth) + 3 }}" class="p-12 text-center text-slate-400">
                                        <div
                                            class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                            <i class="fa-solid fa-calendar-xmark"></i>
                                        </div>
                                        <p class="text-sm font-bold text-slate-800">No Schedule Data Found</p>
                                        <p class="mt-1 text-xs text-slate-400">No rotas generated for this site & period.
                                        </p>
                                        <button type="button" onclick="openGenerateModal()"
                                            class="inline-flex items-center gap-1 mt-3 text-xs font-bold text-amber-600 hover:underline">
                                            Generate rotas now &rarr;
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL: GENERATE TEAM ROTAS (site pattern + generate, 1 alur) --}}
    {{-- =========================================================== --}}
    <div id="modal-generate"
        class="fixed inset-0 z-50 items-center justify-center hidden p-3 transition-all duration-200 sm:p-6 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-generate')">
        <div
            class="w-full max-w-6xl bg-white border border-slate-100 shadow-2xl rounded-3xl overflow-hidden max-h-[94vh] flex flex-col">

            {{-- MODAL HEADER --}}
            <div
                class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50 shrink-0 sm:px-8">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Generate Team Rota Schedule</h3>
                    <p class="mt-1 text-xs font-medium sm:text-sm text-slate-500">Atur pola kerja site dan generate
                        jadwal dalam satu alur — tidak perlu pindah menu.</p>
                </div>
                <button type="button" onclick="closeModal('modal-generate')"
                    class="flex items-center justify-center transition-colors rounded-lg w-9 h-9 text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 shrink-0">&times;</button>
            </div>

            <form action="{{ route('schedule.generate') }}" method="POST" id="generateForm"
                class="flex flex-col flex-1 overflow-hidden">
                @csrf

                {{-- SCROLLABLE BODY --}}
                <div class="flex-1 px-6 py-6 space-y-6 overflow-y-auto sm:px-8">

                    {{-- ROW 1: STEP 1 + STEP 2 (left) / STEP 3 (right) --}}
                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">

                        {{-- LEFT COLUMN --}}
                        <div class="space-y-6 lg:col-span-7">

                            {{-- STEP 1: SITE & PERIOD --}}
                            <div class="p-5 border border-teal-200/80 bg-teal-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center gap-2.5 mb-4">
                                    <span
                                        class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-teal-500 rounded-full shrink-0">1</span>
                                    <span
                                        class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">
                                        Site & Target Periode
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 gap-x-4 gap-y-5 sm:grid-cols-4">
                                    @if (Auth::user()?->role === 'superadmin')
                                        <div class="col-span-2 sm:col-span-2">
                                            <label
                                                class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Target
                                                Site</label>
                                            <select name="target_site_id" id="gen_target_site"
                                                onchange="onGenSiteChange()"
                                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all"
                                                required>
                                                <option value="">-- Pilih Site --</option>
                                                @foreach ($sites as $st)
                                                    <option value="{{ $st->id }}"
                                                        {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                                        {{ $st->machine_name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>
                                    @else
                                        <input type="hidden" name="target_site_id" id="gen_target_site"
                                            value="{{ Auth::user()->site_id }}">
                                        <div class="col-span-2 sm:col-span-2">
                                            <label
                                                class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Target
                                                Site</label>
                                            <div
                                                class="w-full p-2.5 text-xs font-bold bg-slate-100 border border-slate-200 rounded-xl text-slate-600">
                                                {{ Auth::user()->site->machine_name ?? '-' }}
                                            </div>
                                        </div>
                                    @endif

                                    <div class="col-span-1 sm:col-span-1">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Start
                                            Day</label>
                                        <input type="number" name="start_day" value="{{ old('start_day', 1) }}"
                                            min="1" max="31"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all"
                                            required>
                                    </div>

                                    <div class="col-span-1 sm:col-span-1"></div>

                                    <div class="col-span-1 sm:col-span-2">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Month</label>
                                        <select name="month" id="gen_month"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all">
                                            @for ($m = 1; $m <= 12; $m++)
                                                <option value="{{ sprintf('%02d', $m) }}"
                                                    {{ sprintf('%02d', $month) == sprintf('%02d', $m) ? 'selected' : '' }}>
                                                    {{ date('F', mktime(0, 0, 0, $m, 1)) }}
                                                </option>
                                            @endfor
                                        </select>
                                    </div>
                                    <div class="col-span-1 sm:col-span-2">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Year</label>
                                        <select name="year" id="gen_year"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-teal-500/10 focus:border-teal-500 text-slate-800 transition-all">
                                            @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                                    {{ $y }}</option>
                                            @endfor
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- STEP 2: SITE WORK PATTERN --}}
                            <div class="p-5 border border-amber-200/80 bg-amber-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center gap-2.5 mb-1.5">
                                    <span
                                        class="flex items-center justify-center w-6 h-6 text-xs font-black text-white rounded-full bg-amber-500 shrink-0">2</span>
                                    <span
                                        class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">
                                        Pola Kerja Site
                                    </span>
                                </div>
                                <p class="mb-4 text-[10px] font-medium leading-relaxed text-slate-500 ml-[34px]">
                                    Pola ini akan otomatis disimpan untuk site yang dipilih saat Anda menekan Generate —
                                    tidak perlu disimpan terpisah.
                                </p>

                                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                    <div class="sm:col-span-1">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Pattern
                                            Type</label>
                                        <select name="schedule_type" id="gen_schedule_type"
                                            onchange="onScheduleTypeChange()"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all">
                                            <option value="office_hour">Office Hours (Mon - Fri)</option>
                                            <option value="shift_rotation">Dynamic Shift Rotation</option>
                                        </select>
                                    </div>
                                    <div id="gen_work_days_wrapper">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Work
                                            Days</label>
                                        <input type="number" name="work_days" id="gen_work_days" value="6"
                                            min="1"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all">
                                    </div>
                                    <div id="gen_off_days_wrapper">
                                        <label
                                            class="block mb-1.5 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Off
                                            Days</label>
                                        <input type="number" name="off_days" id="gen_off_days" value="2"
                                            min="1"
                                            class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all">
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- RIGHT COLUMN: STEP 3 --}}
                        <div class="lg:col-span-5">
                            <div
                                class="flex flex-col h-full p-5 border border-blue-200/80 bg-blue-50/40 rounded-2xl sm:p-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center gap-2.5">
                                        <span
                                            class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-blue-500 rounded-full shrink-0">3</span>
                                        <span
                                            class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">
                                            Select Staff
                                        </span>
                                    </div>
                                    <label
                                        class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer">
                                        <input type="checkbox" onchange="toggleAllEmployees(this.checked)"
                                            class="w-3.5 h-3.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                                        Select All
                                    </label>
                                </div>

                                <input type="text" id="employee-search" oninput="filterEmployees()"
                                    placeholder="Search employee name..."
                                    class="w-full p-2.5 mb-3 text-xs font-medium bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 placeholder-slate-400 transition-all">

                                <div id="employee-empty-notice"
                                    class="hidden p-3 mb-3 text-xs font-semibold text-center border border-dashed text-slate-400 border-slate-300 rounded-xl">
                                    Pilih Target Site di Step 1 untuk menampilkan daftar staff.
                                </div>

                                <div
                                    class="grid flex-1 grid-cols-1 content-start sm:grid-cols-2 gap-2.5 p-3 bg-white border border-slate-200 rounded-2xl min-h-[220px] max-h-[340px] lg:max-h-none overflow-y-auto">
                                    @foreach ($employees as $emp)
                                        @if (Auth::user()?->role === 'superadmin' ||
                                                (Auth::user()?->role === 'team_leader' && Auth::user()->site_id === $emp->site_id))
                                            <label data-name="{{ strtolower($emp->name) }}"
                                                data-site-id="{{ $emp->site_id }}"
                                                class="flex items-center gap-2 p-2 text-xs font-medium border border-transparent cursor-pointer text-slate-700 rounded-xl employee-option hover:bg-slate-50 hover:border-slate-100">
                                                <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                                    class="w-4 h-4 text-blue-600 rounded border-slate-300 employee-checkbox focus:ring-blue-500 shrink-0"
                                                    {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                                <div class="truncate">
                                                    <span
                                                        class="block text-xs font-bold text-slate-800">{{ $emp->name }}</span>
                                                    <span class="text-[10px] block text-slate-400 font-semibold">Site:
                                                        {{ $emp->site->machine_name ?? '-' }}</span>
                                                </div>
                                            </label>
                                        @endif
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- STEP 4: SHIFT ROTATION & SEQUENCE (FULL WIDTH) --}}
                    <div id="gen_step4" class="p-5 border border-purple-200/80 bg-purple-50/40 rounded-2xl sm:p-6">
                        <div class="flex items-center gap-2.5 mb-4">
                            <span
                                class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-purple-500 rounded-full shrink-0">4</span>
                            <span class="text-xs font-extrabold tracking-wider uppercase text-slate-800 sm:text-sm">
                                Set Shift Rotation & Custom Sequence
                            </span>
                        </div>

                        <div id="oh-notice-banner"
                            class="hidden p-4 mb-4 text-xs font-medium leading-relaxed text-blue-800 border border-blue-200/80 bg-blue-50 rounded-xl">
                            <i class="mr-1.5 fa-solid fa-circle-info"></i>
                            Site ini dikonfigurasi untuk <strong>Office Hours</strong>. Jadwal akan otomatis dibuat untuk
                            hari kerja normal (Senin–Jumat) tanpa rotasi shift.
                        </div>

                        <div class="grid grid-cols-1 gap-5 lg:grid-cols-12">

                            {{-- ACTIVE SHIFT SEQUENCE (now also defines the starting shift — top of list) --}}
                            <div class="lg:col-span-8">
                                <div class="flex items-center justify-between mb-2">
                                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Active
                                        Shift Sequence</label>
                                    <span class="text-[10px] text-slate-400 font-medium">Use <i
                                            class="fa-solid fa-arrow-up text-slate-500"></i> <i
                                            class="fa-solid fa-arrow-down text-slate-500"></i> to reorder — top item
                                        starts the rotation</span>
                                </div>

                                <div id="shift-sequence-container"
                                    class="space-y-2 p-2.5 bg-white border border-slate-200 rounded-2xl min-h-[200px] max-h-[280px] overflow-y-auto">
                                    @foreach (App\Models\Shift::where('is_off', false)->orderBy('start_time', 'asc')->get() as $sf)
                                        @php
                                            $sfNameLower = strtolower($sf->shift_name);
                                            $isOfficeHour =
                                                str_contains($sfNameLower, 'office') ||
                                                str_contains($sfNameLower, 'oh') ||
                                                str_contains($sfNameLower, 'normal');
                                        @endphp
                                        <div class="flex items-center justify-between p-2.5 border bg-slate-50 border-slate-200/80 rounded-xl shift-item-row transition-shadow duration-200"
                                            data-shift-id="{{ $sf->id }}" data-shift-name="{{ $sf->shift_name }}"
                                            data-is-oh="{{ $isOfficeHour ? 'true' : 'false' }}">

                                            <label
                                                class="flex items-center gap-2 text-xs font-bold cursor-pointer text-slate-700">
                                                <input type="checkbox" name="active_shifts[]"
                                                    value="{{ $sf->id }}" onchange="updateStartingShift()"
                                                    class="w-4 h-4 text-purple-600 rounded active-shift-checkbox focus:ring-purple-500"
                                                    {{ old('active_shifts') ? (in_array($sf->id, old('active_shifts')) ? 'checked' : '') : 'checked' }}>
                                                <span>{{ $sf->shift_name }}</span>
                                                <span
                                                    class="start-badge hidden items-center gap-1 px-1.5 py-0.5 text-[9px] font-black tracking-wider text-white uppercase bg-purple-600 rounded-full">
                                                    <i class="fa-solid fa-flag text-[8px]"></i> Start
                                                </span>
                                            </label>

                                            <div class="flex items-center gap-1">
                                                <button type="button" onclick="moveShiftItem(this, 'up')"
                                                    class="p-1.5 transition-colors rounded-lg text-slate-400 hover:text-purple-600 hover:bg-slate-200"
                                                    title="Move Up">
                                                    <i class="text-xs fa-solid fa-arrow-up"></i>
                                                </button>
                                                <button type="button" onclick="moveShiftItem(this, 'down')"
                                                    class="p-1.5 transition-colors rounded-lg text-slate-400 hover:text-purple-600 hover:bg-slate-200"
                                                    title="Move Down">
                                                    <i class="text-xs fa-solid fa-arrow-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            {{-- RIGHT SIDE: derived starting shift readout + shift duration --}}
                            <div class="flex flex-col gap-5 lg:col-span-4">

                                {{-- STARTING SHIFT (read-only, auto-derived from the list above) --}}
                                <div>
                                    <label class="block mb-2 text-xs font-bold tracking-wider uppercase text-slate-700">
                                        Rotation Starts With
                                    </label>
                                    <input type="hidden" name="start_shift_id" id="gen_start_shift_id">
                                    <div id="start_shift_display"
                                        class="flex items-center gap-2 w-full p-2.5 text-xs font-bold bg-purple-50 border border-purple-200 rounded-xl text-purple-800 transition-colors">
                                        <i class="text-purple-500 fa-solid fa-flag"></i>
                                        <span id="start_shift_display_text">—</span>
                                    </div>
                                    <p class="mt-2.5 text-[10px] text-slate-400 leading-relaxed font-medium">
                                        Follows the top item of Active Shift Sequence. Reorder the list to change it.
                                    </p>
                                </div>

                                {{-- SHIFT DURATION --}}
                                <div id="gen_shift_duration_wrapper">
                                    <label
                                        class="block mb-2 text-xs font-bold tracking-wider uppercase text-slate-700">Shift
                                        Duration (Days)</label>
                                    <input type="number" name="shift_duration" id="gen_shift_duration"
                                        value="{{ old('shift_duration', 2) }}" min="1"
                                        class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:ring-4 focus:ring-purple-500/10 focus:border-purple-500 text-slate-800 transition-all">
                                    <p class="mt-2.5 text-[10px] text-slate-400 font-medium leading-relaxed">Consecutive
                                        days in the same shift.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- STICKY FOOTER --}}
                <div
                    class="flex flex-col-reverse items-center justify-between gap-3 px-6 py-4 border-t sm:flex-row border-slate-100 bg-slate-50/70 shrink-0 sm:px-8">
                    <p id="employee-count" class="text-xs font-bold text-slate-500">No employees selected</p>
                    <div class="flex items-center gap-3">
                        <button type="button" onclick="closeModal('modal-generate')"
                            class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                            Cancel
                        </button>
                        <button type="submit" id="btn-submit-generate"
                            class="px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                            <i class="mr-1.5 fa-solid fa-wand-magic-sparkles"></i> Save Pattern & Generate Schedules
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL: EDIT INDIVIDUAL SHIFT BY DATE --}}
    {{-- =========================================================== --}}
    <div id="modal-edit-shift"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-edit-shift')">
        <div class="w-full max-w-sm overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-center justify-between px-6 py-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Change Employee Shift</h3>
                    <p id="edit-shift-subtitle" class="text-xs font-medium text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-shift')"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form id="form-quick-edit-shift" onsubmit="submitQuickEditShift(event)" class="p-6 space-y-4">
                @csrf
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <input type="hidden" id="edit_date" name="date">

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Select Shift</label>
                    <select id="edit_shift_id" name="shift_id"
                        class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all text-slate-800"
                        required>
                        @foreach (App\Models\Shift::orderBy('start_time', 'asc')->get() as $sf)
                            <option value="{{ $sf->id }}">
                                {{ $sf->shift_name }} {{ $sf->is_off ? '(OFF DAY)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-shift')"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 shadow-md rounded-xl hover:bg-blue-700 shadow-blue-600/20 active:scale-95">Save
                        Shift</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL: RESET / CLEAR PERIOD SCHEDULE --}}
    {{-- =========================================================== --}}
    <div id="modal-clear"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-clear')">
        <div class="w-full max-w-md p-6 overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-3">
                    <div
                        class="flex items-center justify-center w-10 h-10 border text-rose-600 bg-rose-50 border-rose-100 rounded-2xl shrink-0">
                        <i class="text-base fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-slate-900">Reset Period Schedule</h3>
                        <p class="text-xs font-medium text-slate-500 mt-0.5">This action will delete all schedule logs for
                            the selected site.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-clear')"
                    class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200">&times;</button>
            </div>

            <form action="{{ route('schedule.clear') }}" method="POST" class="space-y-4">
                @csrf
                @method('DELETE')

                <input type="hidden" name="month" value="{{ sprintf('%02d', $month) }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">

                <div
                    class="p-3.5 text-xs text-rose-800 border border-rose-200/80 bg-rose-50/80 rounded-2xl font-semibold leading-relaxed">
                    Are you sure you want to delete all schedule logs for
                    <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>?
                </div>

                <div class="flex items-center justify-end gap-3 pt-3 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-clear')"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">Cancel</button>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-rose-600 rounded-xl hover:bg-rose-700 shadow-rose-600/20 active:scale-95">Yes,
                        Delete Schedule</button>
                </div>
            </form>
        </div>
    </div>

    @php
        $sitePatternsForJs = $sites->mapWithKeys(function ($s) {
            return [
                $s->id => [
                    'schedule_type' => $s->schedulePattern->schedule_type ?? 'office_hour',
                    'work_days' => $s->schedulePattern->work_days ?? 6,
                    'off_days' => $s->schedulePattern->off_days ?? 2,
                ],
            ];
        });
    @endphp
    <script>
        const SITE_PATTERNS = @json($sitePatternsForJs);

        function openModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (!modal) return;
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal-overlay').forEach(function(modal) {
                    if (!modal.classList.contains('hidden')) closeModal(modal.id);
                });
            }
        });

        // Smooth reorder (FLIP technique): capture positions before the DOM move,
        // then animate every row from its old position to its new one.
        function moveShiftItem(button, direction) {
            const row = button.closest('.shift-item-row');
            if (!row) return;

            const container = document.getElementById('shift-sequence-container');
            const rows = Array.from(container.children);

            // FIRST: record current position of every row
            const firstRects = new Map();
            rows.forEach(r => firstRects.set(r, r.getBoundingClientRect()));

            // move the DOM node
            if (direction === 'up' && row.previousElementSibling) {
                container.insertBefore(row, row.previousElementSibling);
            } else if (direction === 'down' && row.nextElementSibling) {
                container.insertBefore(row.nextElementSibling, row);
            } else {
                updateStartingShift();
                return;
            }

            // LAST + INVERT + PLAY: animate from old position to new position
            rows.forEach(r => {
                const first = firstRects.get(r);
                const last = r.getBoundingClientRect();
                const deltaY = first.top - last.top;

                if (deltaY) {
                    r.style.transition = 'none';
                    r.style.transform = `translateY(${deltaY}px)`;
                    requestAnimationFrame(() => {
                        r.style.transition = 'transform 220ms cubic-bezier(0.22, 1, 0.36, 1)';
                        r.style.transform = '';
                    });
                }
            });

            // brief highlight on the row that actually moved
            row.classList.add('ring-2', 'ring-purple-400', 'ring-offset-1');
            setTimeout(() => row.classList.remove('ring-2', 'ring-purple-400', 'ring-offset-1'), 260);

            updateStartingShift();
        }

        // The starting shift is simply the first visible, checked row in the
        // Active Shift Sequence list — no separate dropdown needed.
        function updateStartingShift() {
            const startShiftInput = document.getElementById('gen_start_shift_id');
            const startShiftDisplayText = document.getElementById('start_shift_display_text');
            if (!startShiftInput) return;

            let starter = null;

            document.querySelectorAll('.shift-item-row').forEach(row => {
                row.querySelector('.start-badge')?.classList.add('hidden');
                row.querySelector('.start-badge')?.classList.remove('inline-flex');

                if (!starter && row.style.display !== 'none') {
                    const cb = row.querySelector('.active-shift-checkbox');
                    if (cb && cb.checked) {
                        starter = row;
                    }
                }
            });

            if (starter) {
                startShiftInput.value = starter.getAttribute('data-shift-id');
                if (startShiftDisplayText) startShiftDisplayText.textContent = starter.getAttribute('data-shift-name');
                const badge = starter.querySelector('.start-badge');
                badge?.classList.remove('hidden');
                badge?.classList.add('inline-flex');
            } else {
                startShiftInput.value = '';
                if (startShiftDisplayText) startShiftDisplayText.textContent = 'No active shift selected';
            }
        }

        // Step 2: toggle work/off days input berdasarkan pattern type
        function onScheduleTypeChange() {
            const type = document.getElementById('gen_schedule_type').value;
            const workWrap = document.getElementById('gen_work_days_wrapper');
            const offWrap = document.getElementById('gen_off_days_wrapper');
            const durationWrapper = document.getElementById('gen_shift_duration_wrapper');
            const durationInput = document.getElementById('gen_shift_duration');
            const ohNoticeBanner = document.getElementById('oh-notice-banner');
            const isOfficeHour = type === 'office_hour';

            workWrap.classList.toggle('hidden', isOfficeHour);
            offWrap.classList.toggle('hidden', isOfficeHour);
            ohNoticeBanner.classList.toggle('hidden', !isOfficeHour);

            if (isOfficeHour) {
                durationWrapper.classList.add('hidden');
                durationInput.removeAttribute('required');
            } else {
                durationWrapper.classList.remove('hidden');
                durationInput.setAttribute('required', 'required');
            }

            // Step 4: filter shift aktif sesuai office_hour vs shift_rotation
            document.querySelectorAll('.shift-item-row').forEach(row => {
                const isOh = row.getAttribute('data-is-oh') === 'true';
                const cb = row.querySelector('.active-shift-checkbox');
                const shouldShow = isOfficeHour ? isOh : !isOh;
                row.style.display = shouldShow ? '' : 'none';
                if (cb) cb.checked = shouldShow;
            });

            updateStartingShift();
        }

        // Step 1: site berubah -> filter staff (Step 3) + prefill pattern (Step 2)
        function onGenSiteChange() {
            const siteId = document.getElementById('gen_target_site').value;

            document.querySelectorAll('.employee-option').forEach(el => {
                const empSiteId = el.getAttribute('data-site-id');
                const match = siteId && empSiteId == siteId;
                el.style.display = match ? '' : 'none';
                if (!match) {
                    const cb = el.querySelector('.employee-checkbox');
                    if (cb) cb.checked = false;
                }
            });

            const emptyNotice = document.getElementById('employee-empty-notice');
            if (emptyNotice) emptyNotice.classList.toggle('hidden', !!siteId);

            const pattern = SITE_PATTERNS[siteId] ?? {
                schedule_type: 'office_hour',
                work_days: 6,
                off_days: 2
            };
            document.getElementById('gen_schedule_type').value = pattern.schedule_type;
            document.getElementById('gen_work_days').value = pattern.work_days;
            document.getElementById('gen_off_days').value = pattern.off_days;

            onScheduleTypeChange();
            updateEmployeeCount();
        }

        function filterEmployees() {
            const q = document.getElementById('employee-search').value.toLowerCase();
            const siteId = document.getElementById('gen_target_site').value;

            document.querySelectorAll('.employee-option').forEach(function(el) {
                const empSiteId = el.getAttribute('data-site-id');
                const matchesName = el.dataset.name.includes(q);
                const matchesSite = siteId && empSiteId == siteId;

                el.style.display = (matchesName && matchesSite) ? '' : 'none';
            });
        }

        function toggleAllEmployees(checked) {
            document.querySelectorAll('.employee-option').forEach(function(el) {
                if (el.style.display !== 'none') {
                    const cb = el.querySelector('.employee-checkbox');
                    if (cb) cb.checked = checked;
                }
            });
            updateEmployeeCount();
        }

        function updateEmployeeCount() {
            const total = document.querySelectorAll('.employee-checkbox:checked').length;
            const countEl = document.getElementById('employee-count');
            const btnSubmit = document.getElementById('btn-submit-generate');

            if (countEl) {
                countEl.textContent = total > 0 ? `${total} employee(s) selected` : 'No employees selected';
            }

            if (btnSubmit) {
                btnSubmit.disabled = total === 0;
            }
        }

        document.querySelectorAll('.employee-checkbox').forEach(function(cb) {
            cb.addEventListener('change', updateEmployeeCount);
        });

        function openGenerateModal() {
            const targetSelect = document.getElementById('gen_target_site');
            const mainSiteSelect = document.getElementById('main_site_select');
            const mainMonth = document.getElementById('main_month_select');
            const mainYear = document.getElementById('main_year_select');

            if (mainMonth && document.getElementById('gen_month')) {
                document.getElementById('gen_month').value = mainMonth.value;
            }
            if (mainYear && document.getElementById('gen_year')) {
                document.getElementById('gen_year').value = mainYear.value;
            }

            // Jika superadmin dan filter utama sedang menunjuk 1 site spesifik, auto-pilih di modal
            if (targetSelect && targetSelect.tagName === 'SELECT' && mainSiteSelect && mainSiteSelect.value !== 'all') {
                targetSelect.value = mainSiteSelect.value;
            }

            onGenSiteChange();
            openModal('modal-generate');
        }

        document.addEventListener('DOMContentLoaded', function() {
            updateEmployeeCount();
            onGenSiteChange();

            @if (
                $errors->has('employee_ids') ||
                    $errors->has('start_shift_id') ||
                    $errors->has('active_shifts') ||
                    $errors->has('start_day') ||
                    $errors->has('shift_duration') ||
                    $errors->has('schedule_type') ||
                    $errors->has('work_days') ||
                    $errors->has('off_days') ||
                    $errors->has('target_site_id'))
                openGenerateModal();
            @endif
        });

        function openEditShiftModal(empId, empName, dateStr, currentShiftId) {
            document.getElementById('edit_employee_id').value = empId;
            document.getElementById('edit_date').value = dateStr;
            document.getElementById('edit-shift-subtitle').innerText = `${empName} (${dateStr})`;

            if (currentShiftId) {
                document.getElementById('edit_shift_id').value = currentShiftId;
            }
            openModal('modal-edit-shift');
        }

        function submitQuickEditShift(e) {
            e.preventDefault();
            const form = document.getElementById('form-quick-edit-shift');
            const formData = new FormData(form);

            fetch('{{ route('schedule.updateSingle') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert(data.message || 'Failed to update shift');
                    }
                })
                .catch(err => {
                    alert('A network error occurred.');
                });
        }
    </script>
@endsection
