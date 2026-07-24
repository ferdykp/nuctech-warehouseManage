@extends('layout.master')

@section('title', 'Schedule Management')

@section('content')
    <div class="w-full space-y-6">

        {{-- ============ HEADER ============ --}}
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                    Employee Work Schedules
                </h1>
                <p class="mt-0.5 text-xs sm:text-sm font-medium text-slate-500">
                    Monitor duty schedules, configure site work patterns, and generate team rotas automatically.
                </p>
                @if (Auth::user()->role === 'admin_site')
                    <p class="mt-1 text-xs font-semibold text-blue-600 flex items-center gap-1.5">
                        <i class="fa-solid fa-building-user"></i> Access Mode: Site Admin
                        ({{ Auth::user()->site->machine_name ?? 'Registered Site' }})
                    </p>
                @endif
            </div>

            {{-- Action Buttons --}}
            <div class="flex flex-wrap items-center gap-2 shrink-0">
                <button type="button" onclick="openPatternModal()"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold text-slate-700 transition-all bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shadow-2xs">
                    <i class="text-slate-400 fa-solid fa-sliders"></i> Site Patterns
                </button>
                <button type="button" onclick="openGenerateModal()"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95">
                    <i class="fa-solid fa-plus"></i> Generate Rotas
                </button>

                <!-- EXPORT EXCEL BUTTON -->
                <a href="{{ route('schedule.export', ['site_id' => $selectedSiteId ?? 'all', 'month' => sprintf('%02d', $month), 'year' => $year]) }}"
                    class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95"
                    title="Export schedule to Excel">
                    <i class="fa-solid fa-file-excel"></i> Export Excel
                </a>

                <!-- RESET SCHEDULE BUTTON -->
                <button type="button" onclick="openModal('modal-clear')"
                    class="inline-flex items-center gap-2 px-3.5 py-2.5 text-xs font-bold text-rose-600 transition-all border border-rose-200/80 bg-rose-50 hover:bg-rose-100 rounded-xl active:scale-95">
                    <i class="fa-solid fa-trash-can"></i> Reset Schedule
                </button>
            </div>
        </div>

        {{-- ============ ALERTS ============ --}}
        @if (session('success'))
            <div
                class="flex items-start gap-2.5 p-4 text-xs sm:text-sm font-semibold text-emerald-800 border border-emerald-200/80 bg-emerald-50 rounded-2xl">
                <i class="mt-0.5 fa-solid fa-circle-check text-emerald-600"></i>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        @if ($errors->any())
            <div
                class="flex items-start gap-2.5 p-4 text-xs sm:text-sm text-rose-800 border border-rose-200/80 bg-rose-50 rounded-2xl">
                <i class="mt-0.5 fa-solid fa-triangle-exclamation text-rose-600"></i>
                <div>
                    <div class="mb-1 font-bold">Failed to process schedule update, please verify:</div>
                    <ul class="pl-4 list-disc space-y-0.5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        {{-- ============ VIEW FILTERS ============ --}}
        <form action="{{ route('schedule.index') }}" method="GET" id="mainFilterForm"
            class="flex flex-wrap items-end gap-3 p-4 bg-white border border-slate-200/80 shadow-2xs rounded-2xl">

            @if (Auth::user()->role === 'superadmin')
                <div class="w-full sm:w-52">
                    <label class="block mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Site
                        Location</label>
                    <select name="site_id" id="main_site_select" onchange="syncPatternWithSite()"
                        class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                        <option value="all" {{ ($selectedSiteId ?? 'all') == 'all' ? 'selected' : '' }}>
                            -- All Sites --</option>
                        @foreach ($sites as $st)
                            <option value="{{ $st->id }}" {{ ($selectedSiteId ?? '') == $st->id ? 'selected' : '' }}>
                                {{ $st->machine_name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @else
                <input type="hidden" id="main_site_select" value="{{ Auth::user()->site_id }}">
            @endif

            <div class="w-1/2 sm:w-40">
                <label class="block mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Month</label>
                <select name="month" id="main_month_select"
                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                    @for ($m = 1; $m <= 12; $m++)
                        <option value="{{ sprintf('%02d', $m) }}" {{ $month == $m ? 'selected' : '' }}>
                            {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                    @endfor
                </select>
            </div>

            <div class="w-1/2 sm:w-32">
                <label class="block mb-1 text-[10px] font-bold uppercase tracking-wider text-slate-500">Year</label>
                <select name="year" id="main_year_select"
                    class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-amber-500/10 focus:border-amber-500 text-slate-800 transition-all outline-none">
                    @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}
                        </option>
                    @endfor
                </select>
            </div>

            <button type="submit"
                class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 rounded-xl shadow-emerald-600/20 active:scale-95">
                <i class="mr-1 fa-solid fa-filter"></i> Apply Filter
            </button>
        </form>

        {{-- ============ NATIONAL HOLIDAYS BANNER ============ --}}
        @if (!empty($holidays))
            <div class="p-4 border border-rose-200/80 rounded-2xl bg-rose-50/60">
                <h6 class="flex items-center gap-2 mb-2 text-xs font-extrabold tracking-wider uppercase text-rose-600">
                    <i class="fa-solid fa-circle-exclamation"></i> National Holidays & Red Dates This Month
                </h6>
                <ul class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($holidays as $date => $name)
                        <li class="flex items-baseline gap-2 text-xs">
                            <span
                                class="w-6 font-bold text-right text-rose-700 shrink-0">{{ \Carbon\Carbon::parse($date)->format('d') }}</span>
                            <span class="font-medium text-slate-700">{{ $name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ============ CALENDAR GRID PREVIEW ============ --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl sm:rounded-3xl">
            <div
                class="flex flex-col justify-between gap-3 p-5 border-b lg:flex-row lg:items-center sm:p-6 border-slate-100 bg-slate-50/50">
                <div class="flex flex-wrap items-center gap-2">
                    <h3 class="text-sm font-extrabold text-slate-800 sm:text-base">Monthly Schedule Grid</h3>
                    @if (Auth::user()->role === 'admin_site')
                        <span
                            class="px-2.5 py-0.5 text-[10px] font-bold text-amber-800 bg-amber-50 border border-amber-200/60 rounded-md uppercase">
                            Site: {{ Auth::user()->site->machine_name ?? 'Registered' }}
                        </span>
                    @endif
                    <span
                        class="px-3 py-1 text-xs font-black text-blue-700 uppercase border rounded-full border-blue-200/60 bg-blue-50">
                        {{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}
                    </span>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 text-[10px] font-bold text-slate-500">
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-blue-50 border border-blue-200"></span> Shift
                        1</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-amber-50 border border-amber-200"></span> Shift
                        2</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-purple-50 border border-purple-200"></span> Shift
                        3</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-emerald-50 border border-emerald-200"></span>
                        Other / OH</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-rose-50 border border-rose-200"></span> OFF</span>
                    <span class="flex items-center gap-1"><span
                            class="inline-block w-2.5 h-2.5 rounded-xs bg-white border border-slate-200"></span>
                        Unassigned</span>
                    <span class="flex items-center gap-1 text-rose-500"><i class="fa-solid fa-circle-exclamation"></i>
                        National Holiday</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full border-collapse table-fixed min-w-[900px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold uppercase tracking-wider text-slate-600 bg-slate-100/80 border-b border-slate-200/80">
                            <th
                                class="w-48 px-4 py-3 text-left sticky left-0 top-0 bg-slate-100 z-20 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                Employee / Site
                            </th>

                            @foreach ($datesInMonth as $date)
                                @php
                                    $holidayName = $holidays[$date->format('Y-m-d')] ?? null;
                                    $isRedDate = $date->isWeekend() || $holidayName;
                                @endphp
                                <th class="w-11 text-center py-2 border-l border-slate-200/80 {{ $isRedDate ? 'bg-rose-50/80 text-rose-600' : '' }}"
                                    @if ($holidayName) title="{{ $holidayName }}" @endif>
                                    <div>{{ $date->format('d') }}</div>
                                    <div class="text-[8px] font-normal uppercase">{{ $date->translatedFormat('D') }}</div>
                                    @if ($holidayName)
                                        <div class="mt-0.5 truncate px-0.5 text-[7px] font-semibold text-rose-500"
                                            title="{{ $holidayName }}">
                                            <i class="fa-solid fa-circle-exclamation"></i>
                                        </div>
                                    @endif
                                </th>
                            @endforeach

                            <th
                                class="py-2 font-bold text-center text-blue-700 border-l border-slate-200/80 w-14 bg-blue-50/70">
                                <div>WORK</div>
                                <div class="text-[7px] text-blue-500 uppercase">(Days)</div>
                            </th>
                            <th
                                class="py-2 font-bold text-center border-l text-rose-700 border-slate-200/80 w-14 bg-rose-50/70">
                                <div>OFF</div>
                                <div class="text-[7px] text-rose-500 uppercase">(Days)</div>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="text-xs font-medium divide-y divide-slate-100 sm:text-sm text-slate-700">
                        @forelse($employees as $emp)
                            @if (Auth::user()->role === 'superadmin' ||
                                    (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $emp->site_id))
                                <tr class="transition-colors hover:bg-slate-50/80">
                                    <td
                                        class="px-4 py-3 sticky left-0 bg-white font-bold text-slate-800 z-10 shadow-[2px_0_5px_-2px_rgba(0,0,0,0.05)]">
                                        <div class="truncate">{{ $emp->name }}</div>
                                        <div class="text-[10px] font-normal text-slate-400 truncate">
                                            {{ $emp->site->machine_name ?? '-' }}</div>
                                    </td>

                                    @php
                                        $totalWorkCount = 0;
                                        $totalOffCount = 0;
                                    @endphp

                                    @foreach ($datesInMonth as $date)
                                        @php
                                            $schedule = $emp->schedules->firstWhere('date', $date->format('Y-m-d'));
                                            $shiftName = $schedule?->shift?->shift_name;

                                            $badgeColor = 'bg-white text-slate-300 border-slate-200';
                                            $label = '-';

                                            if ($schedule && $schedule->shift) {
                                                if ($schedule->shift->is_off) {
                                                    $badgeColor = 'bg-rose-50 text-rose-700 border-rose-200/60';
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
                                                        $badgeColor = 'bg-blue-50 text-blue-700 border-blue-200/60';
                                                    } elseif (str_contains(strtolower($shiftName), '2')) {
                                                        $badgeColor = 'bg-amber-50 text-amber-700 border-amber-200/60';
                                                    } elseif (str_contains(strtolower($shiftName), '3')) {
                                                        $badgeColor =
                                                            'bg-purple-50 text-purple-700 border-purple-200/60';
                                                    } else {
                                                        $badgeColor =
                                                            'bg-emerald-50 text-emerald-700 border-emerald-200/60';
                                                    }
                                                }
                                            }
                                        @endphp
                                        <td class="p-1 text-center border-l border-slate-100">
                                            <button type="button"
                                                onclick="openEditShiftModal({{ $emp->id }}, '{{ addslashes($emp->name) }}', '{{ $date->format('Y-m-d') }}', '{{ $schedule?->shift_id ?? '' }}')"
                                                class="w-full py-1 text-[9px] font-bold border rounded-md transition-transform active:scale-95 cursor-pointer {{ $badgeColor }}"
                                                title="Click to edit shift ({{ $shiftName ?? 'Unassigned' }})">
                                                {{ $label }}
                                            </button>
                                        </td>
                                    @endforeach

                                    <td
                                        class="px-2 py-3 font-extrabold text-center text-blue-700 border-l border-slate-200/80 bg-blue-50/30">
                                        {{ $totalWorkCount }}
                                    </td>
                                    <td
                                        class="px-2 py-3 font-extrabold text-center border-l text-rose-700 border-slate-200/80 bg-rose-50/30">
                                        {{ $totalOffCount }}
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr>
                                <td colspan="{{ count($datesInMonth) + 3 }}"
                                    class="px-6 py-16 text-center text-slate-400">
                                    <i class="block mb-2 text-3xl opacity-50 fa-solid fa-calendar-xmark"></i>
                                    <p class="text-sm font-bold text-slate-700">No schedule data found for this site &
                                        period.</p>
                                    <button type="button" onclick="openGenerateModal()"
                                        class="inline-flex items-center gap-1 mt-2 text-xs font-bold text-amber-600 hover:underline">
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

    {{-- =========================================================== --}}
    {{-- MODAL 1: SITE WORK PATTERN CONFIGURATION                    --}}
    {{-- =========================================================== --}}
    <div id="modal-pattern"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-pattern')">
        <div
            class="w-full max-w-2xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden max-h-[85vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Configure Site Work Patterns</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Define operational work/off day rules used by the
                        auto-generator.</p>
                </div>
                <button type="button" onclick="closeModal('modal-pattern')"
                    class="p-2 transition-colors rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <div class="p-6 space-y-4 overflow-y-auto">
                @foreach ($sites as $site)
                    @if (Auth::user()->role === 'superadmin' || (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $site->id))
                        <form action="{{ route('schedule.site.update', $site->id) }}" method="POST"
                            id="pattern_form_{{ $site->id }}"
                            class="p-4 border border-slate-200/80 bg-slate-50/60 rounded-2xl pattern-site-form"
                            data-site-id="{{ $site->id }}">
                            @csrf
                            <div class="mb-3 text-xs font-extrabold tracking-wider uppercase text-slate-800">
                                {{ $site->machine_name }}</div>
                            <div class="space-y-3">
                                <div>
                                    <label
                                        class="block mb-1 text-xs font-bold tracking-wider uppercase text-slate-700">Pattern
                                        Type</label>
                                    <select name="schedule_type" id="pattern_type_{{ $site->id }}"
                                        class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                                        onchange="toggleShiftInputs(this, '{{ $site->id }}')">
                                        <option value="office_hour"
                                            {{ ($site->schedulePattern->schedule_type ?? '') == 'office_hour' ? 'selected' : '' }}>
                                            Office Hours (Mon - Fri)</option>
                                        <option value="shift_rotation"
                                            {{ ($site->schedulePattern->schedule_type ?? '') == 'shift_rotation' ? 'selected' : '' }}>
                                            Dynamic Shift Rotation</option>
                                    </select>
                                </div>

                                <div id="rotation-fields-{{ $site->id }}"
                                    class="{{ ($site->schedulePattern->schedule_type ?? '') == 'shift_rotation' ? '' : 'hidden' }} grid grid-cols-2 gap-3">
                                    <div>
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Work
                                            Days</label>
                                        <input type="number" name="work_days"
                                            value="{{ $site->schedulePattern->work_days ?? 6 }}" min="1"
                                            class="w-full p-2 text-xs font-bold bg-white border outline-none border-slate-200 rounded-xl">
                                    </div>
                                    <div>
                                        <label
                                            class="text-[10px] font-bold text-slate-500 uppercase tracking-wider block mb-1">Off
                                            Days</label>
                                        <input type="number" name="off_days"
                                            value="{{ $site->schedulePattern->off_days ?? 2 }}" min="1"
                                            class="w-full p-2 text-xs font-bold bg-white border outline-none border-slate-200 rounded-xl">
                                    </div>
                                </div>

                                <button type="submit"
                                    class="w-full py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                                    Save Pattern for {{ $site->machine_name }}
                                </button>
                            </div>
                        </form>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 2: GENERATE TEAM ROTAS                                 --}}
    {{-- =========================================================== --}}
    <div id="modal-generate"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-generate')">
        <div
            class="w-full max-w-3xl bg-white rounded-2xl sm:rounded-3xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col">
            <div class="flex items-center justify-between p-5 border-b border-slate-100 bg-slate-50/50">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Generate Team Rota Schedule</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Complete the 3 steps below to automatically populate employee
                        work rotas.</p>
                </div>
                <button type="button" onclick="closeModal('modal-generate')"
                    class="p-2 transition-colors rounded-full text-slate-400 hover:text-slate-600 bg-slate-100">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('schedule.generate') }}" method="POST" class="p-6 space-y-5 overflow-y-auto">
                @csrf

                {{-- STEP 1 --}}
                <div class="p-4 border border-amber-200/80 bg-amber-50/40 rounded-2xl">
                    <div class="flex items-center gap-2 mb-3">
                        <span
                            class="flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-amber-500 rounded-full">1</span>
                        <span class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Set Target
                            Period</span>
                    </div>
                    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                        <div>
                            <label
                                class="block mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Month</label>
                            <select name="month" id="gen_month"
                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:border-amber-500">
                                @for ($m = 1; $m <= 12; $m++)
                                    <option value="{{ sprintf('%02d', $m) }}"
                                        {{ sprintf('%02d', $month) == sprintf('%02d', $m) ? 'selected' : '' }}>
                                        {{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label
                                class="block mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Year</label>
                            <select name="year" id="gen_year"
                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:border-amber-500">
                                @for ($y = date('Y') - 1; $y <= date('Y') + 2; $y++)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endfor
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-[10px] font-bold text-slate-700 uppercase tracking-wider">Start
                                Day</label>
                            <input type="number" name="start_day" value="{{ old('start_day', 1) }}" min="1"
                                max="31"
                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:border-amber-500"
                                required>
                        </div>
                        <div id="gen_shift_duration_wrapper">
                            <label class="block mb-1 text-[10px] font-bold text-slate-500 uppercase tracking-wider">Shift
                                Duration (Days)</label>
                            <input type="number" name="shift_duration" id="gen_shift_duration"
                                value="{{ old('shift_duration', 2) }}" min="1"
                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:border-amber-500">
                            <p class="mt-1 text-[9px] text-slate-400">Consecutive days in the same shift.</p>
                        </div>
                    </div>
                </div>

                {{-- STEP 2 --}}
                <div class="p-4 border border-blue-200/80 bg-blue-50/40 rounded-2xl">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-2">
                            <span
                                class="flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-blue-500 rounded-full">2</span>
                            <span class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Select
                                Staff</span>
                        </div>
                        <label class="flex items-center gap-1.5 text-xs font-bold text-slate-600 cursor-pointer">
                            <input type="checkbox" onchange="toggleAllEmployees(this.checked)"
                                class="w-3.5 h-3.5 text-blue-600 border-slate-300 rounded focus:ring-blue-500">
                            Select All
                        </label>
                    </div>

                    <input type="text" id="employee-search" oninput="filterEmployees()"
                        placeholder="Search employee name..."
                        class="w-full p-2.5 mb-3 text-xs font-medium bg-white border border-slate-200 rounded-xl outline-none focus:border-blue-500">

                    <div
                        class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 p-3 bg-white border border-slate-200 rounded-xl max-h-[180px] overflow-y-auto">
                        @foreach ($employees as $emp)
                            @if (Auth::user()->role === 'superadmin' ||
                                    (Auth::user()->role === 'admin_site' && Auth::user()->site_id === $emp->site_id))
                                <label data-name="{{ strtolower($emp->name) }}" data-site-id="{{ $emp->site_id }}"
                                    class="flex items-center gap-2 p-1.5 text-xs font-medium text-slate-700 rounded-lg cursor-pointer employee-option hover:bg-slate-50 border border-transparent hover:border-slate-100">
                                    <input type="checkbox" name="employee_ids[]" value="{{ $emp->id }}"
                                        class="w-4 h-4 text-blue-600 rounded border-slate-300 employee-checkbox focus:ring-blue-500"
                                        {{ in_array($emp->id, old('employee_ids', [])) ? 'checked' : '' }}>
                                    <div class="truncate">
                                        <span class="block font-bold text-slate-800">{{ $emp->name }}</span>
                                        <span class="text-[10px] block text-slate-400">Site:
                                            {{ $emp->site->machine_name ?? '-' }}</span>
                                    </div>
                                </label>
                            @endif
                        @endforeach
                    </div>
                    <p id="employee-count" class="mt-2 text-[10px] font-bold text-slate-500"></p>
                </div>

                {{-- STEP 3 --}}
                <div class="p-4 border border-purple-200/80 bg-purple-50/40 rounded-2xl">
                    <div class="flex items-center gap-2 mb-3">
                        <span
                            class="flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-purple-500 rounded-full">3</span>
                        <span class="text-xs font-extrabold tracking-wider uppercase text-slate-800">Set Shift
                            Patterns</span>
                    </div>

                    <!-- OFFICE HOURS FEEDBACK BANNER -->
                    <div id="oh-notice-banner"
                        class="hidden p-3 mb-3 text-xs text-blue-800 border border-blue-200/80 bg-blue-50 rounded-xl">
                        <i class="mr-1 fa-solid fa-circle-info"></i>
                        This site is configured for <strong>Office Hours</strong>. Schedules will automatically be generated
                        for normal work days (Monday–Friday) without shift rotations.
                    </div>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                        <div>
                            <label class="block mb-1 text-xs font-bold tracking-wider uppercase text-slate-700">Starting
                                Shift</label>
                            <select name="start_shift_id" id="gen_start_shift_id"
                                class="w-full p-2.5 text-xs font-bold bg-white border border-slate-200 rounded-xl outline-none focus:border-purple-500"
                                required>
                                @foreach (App\Models\Shift::where('is_off', false)->orderBy('start_time', 'asc')->get() as $sf)
                                    @php
                                        $sfNameLower = strtolower($sf->shift_name);
                                        $isOfficeHour =
                                            str_contains($sfNameLower, 'office') ||
                                            str_contains($sfNameLower, 'oh') ||
                                            str_contains($sfNameLower, 'normal');
                                    @endphp
                                    <option value="{{ $sf->id }}"
                                        data-is-oh="{{ $isOfficeHour ? 'true' : 'false' }}" class="shift-select-option"
                                        {{ old('start_shift_id') == $sf->id ? 'selected' : '' }}>
                                        {{ $sf->shift_name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block mb-1 text-xs font-bold tracking-wider uppercase text-slate-700">Active
                                Shifts in Rotation</label>
                            <div class="flex flex-wrap gap-2.5 p-2.5 bg-white border border-slate-200 rounded-xl">
                                @foreach (App\Models\Shift::where('is_off', false)->orderBy('start_time', 'asc')->get() as $sf)
                                    @php
                                        $sfNameLower = strtolower($sf->shift_name);
                                        $isOfficeHour =
                                            str_contains($sfNameLower, 'office') ||
                                            str_contains($sfNameLower, 'oh') ||
                                            str_contains($sfNameLower, 'normal');
                                    @endphp
                                    <label
                                        class="flex items-center gap-1.5 text-xs font-bold text-slate-700 cursor-pointer active-shift-label"
                                        data-is-oh="{{ $isOfficeHour ? 'true' : 'false' }}">
                                        <input type="checkbox" name="active_shifts[]" value="{{ $sf->id }}"
                                            data-is-oh="{{ $isOfficeHour ? 'true' : 'false' }}"
                                            class="w-3.5 h-3.5 text-blue-600 rounded active-shift-checkbox focus:ring-blue-500"
                                            {{ old('active_shifts') ? (in_array($sf->id, old('active_shifts')) ? 'checked' : '') : 'checked' }}>
                                        {{ $sf->shift_name }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-generate')"
                        class="px-4 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </button>
                    <button type="submit" id="btn-submit-generate"
                        class="px-5 py-2.5 text-xs font-bold text-white transition-all shadow-md bg-amber-600 hover:bg-amber-700 rounded-xl shadow-amber-600/20 active:scale-95 disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="mr-1 fa-solid fa-wand-magic-sparkles"></i> Generate Schedules
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 3: EDIT INDIVIDUAL SHIFT BY DATE                      --}}
    {{-- =========================================================== --}}
    <div id="modal-edit-shift"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-edit-shift')">
        <div class="w-full max-w-sm p-6 bg-white shadow-2xl rounded-2xl">
            <div class="flex items-start justify-between mb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-900">Change Employee Shift</h3>
                    <p id="edit-shift-subtitle" class="text-xs font-medium text-slate-500 mt-0.5"></p>
                </div>
                <button type="button" onclick="closeModal('modal-edit-shift')"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="form-quick-edit-shift" onsubmit="submitQuickEditShift(event)" class="space-y-4">
                @csrf
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <input type="hidden" id="edit_date" name="date">

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Select Shift</label>
                    <select id="edit_shift_id" name="shift_id"
                        class="w-full p-2.5 text-xs font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all"
                        required>
                        @foreach (App\Models\Shift::orderBy('start_time', 'asc')->get() as $sf)
                            <option value="{{ $sf->id }}">{{ $sf->shift_name }}
                                {{ $sf->is_off ? '(OFF DAY)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-edit-shift')"
                        class="px-4 py-2 text-xs font-bold transition-colors text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 text-xs font-bold text-white transition-all bg-blue-600 shadow-md rounded-xl hover:bg-blue-700 shadow-blue-600/20 active:scale-95">Save
                        Shift</button>
                </div>
            </form>
        </div>
    </div>

    {{-- =========================================================== --}}
    {{-- MODAL 4: RESET / CLEAR PERIOD SCHEDULE                      --}}
    {{-- =========================================================== --}}
    <div id="modal-clear"
        class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs modal-overlay"
        onclick="if(event.target===this) closeModal('modal-clear')">
        <div class="w-full max-w-md p-6 bg-white shadow-2xl rounded-2xl">
            <div class="flex items-start justify-between mb-3">
                <div class="flex items-center gap-2">
                    <div class="p-2 text-rose-600 bg-rose-50 rounded-xl shrink-0">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    </div>
                    <div>
                        <h3 class="text-base font-extrabold text-rose-600">Reset Period Schedule</h3>
                        <p class="text-xs text-slate-500">This action will delete all schedule data for the selected site
                            and month.</p>
                    </div>
                </div>
                <button type="button" onclick="closeModal('modal-clear')"
                    class="p-1.5 text-slate-400 hover:text-slate-600 rounded-lg">
                    <i class="text-xs fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="{{ route('schedule.clear') }}" method="POST" class="pt-2 space-y-4">
                @csrf
                @method('DELETE')

                <input type="hidden" name="month" value="{{ sprintf('%02d', $month) }}">
                <input type="hidden" name="year" value="{{ $year }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">

                <div class="p-3.5 text-xs text-rose-700 border border-rose-200/80 bg-rose-50/80 rounded-xl font-medium">
                    Are you sure you want to delete all schedule logs for
                    <strong>{{ date('F Y', mktime(0, 0, 0, $month, 1, $year)) }}</strong>?
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeModal('modal-clear')"
                        class="px-4 py-2 text-xs font-bold transition-colors text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                    <button type="submit"
                        class="px-5 py-2 text-xs font-bold text-white transition-all shadow-md bg-rose-600 rounded-xl hover:bg-rose-700 shadow-rose-600/20 active:scale-95">Yes,
                        Delete Schedule</button>
                </div>
            </form>
        </div>
    </div>

    <script>
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

        function toggleShiftInputs(selectElement, siteId) {
            const fields = document.getElementById(`rotation-fields-${siteId}`);
            if (fields) {
                if (selectElement.value === 'shift_rotation') {
                    fields.classList.remove('hidden');
                } else {
                    fields.classList.add('hidden');
                }
            }
        }

        function openPatternModal() {
            let activeSiteId = document.getElementById('main_site_select') ? document.getElementById('main_site_select')
                .value : null;

            document.querySelectorAll('.pattern-site-form').forEach(form => {
                let siteId = form.getAttribute('data-site-id');
                if (!activeSiteId || activeSiteId === 'all' || siteId == activeSiteId) {
                    form.classList.remove('hidden');
                } else {
                    form.classList.add('hidden');
                }
            });

            openModal('modal-pattern');
        }

        function syncPatternWithSite() {
            let activeSiteId = document.getElementById('main_site_select') ? document.getElementById('main_site_select')
                .value : null;
            let isOfficeHour = false;

            if (activeSiteId && activeSiteId !== 'all') {
                let patternSelect = document.getElementById(`pattern_type_${activeSiteId}`);
                if (patternSelect && patternSelect.value === 'office_hour') {
                    isOfficeHour = true;
                }
            }

            let startShiftSelect = document.getElementById('gen_start_shift_id');
            let ohNoticeBanner = document.getElementById('oh-notice-banner');

            if (ohNoticeBanner) {
                if (isOfficeHour) {
                    ohNoticeBanner.classList.remove('hidden');
                } else {
                    ohNoticeBanner.classList.add('hidden');
                }
            }

            document.querySelectorAll('.active-shift-label').forEach(label => {
                let isOh = label.getAttribute('data-is-oh') === 'true';
                let cb = label.querySelector('.active-shift-checkbox');

                if (isOfficeHour) {
                    if (isOh) {
                        label.classList.remove('hidden');
                        if (cb) cb.checked = true;
                    } else {
                        label.classList.add('hidden');
                        if (cb) cb.checked = false;
                    }
                } else {
                    if (isOh) {
                        label.classList.add('hidden');
                        if (cb) cb.checked = false;
                    } else {
                        label.classList.remove('hidden');
                        if (cb) cb.checked = true;
                    }
                }
            });

            if (startShiftSelect) {
                for (let i = 0; i < startShiftSelect.options.length; i++) {
                    let opt = startShiftSelect.options[i];
                    let isOh = opt.getAttribute('data-is-oh') === 'true';

                    if (isOfficeHour) {
                        opt.style.display = isOh ? '' : 'none';
                    } else {
                        opt.style.display = isOh ? 'none' : '';
                    }
                }

                for (let i = 0; i < startShiftSelect.options.length; i++) {
                    let opt = startShiftSelect.options[i];
                    if (opt.style.display !== 'none') {
                        startShiftSelect.selectedIndex = i;
                        break;
                    }
                }
            }

            let durationWrapper = document.getElementById('gen_shift_duration_wrapper');
            let durationInput = document.getElementById('gen_shift_duration');
            if (durationWrapper && durationInput) {
                if (isOfficeHour) {
                    durationWrapper.classList.add('hidden');
                    durationInput.removeAttribute('required');
                } else {
                    durationWrapper.classList.remove('hidden');
                    durationInput.setAttribute('required', 'required');
                }
            }

            document.querySelectorAll('.employee-option').forEach(el => {
                let empSiteId = el.getAttribute('data-site-id');
                if (!activeSiteId || activeSiteId === 'all' || empSiteId == activeSiteId) {
                    el.style.display = '';
                } else {
                    el.style.display = 'none';
                    let cb = el.querySelector('.employee-checkbox');
                    if (cb) cb.checked = false;
                }
            });
            updateEmployeeCount();
        }

        function openGenerateModal() {
            let mainMonth = document.getElementById('main_month_select') ? document.getElementById('main_month_select')
                .value : null;
            let mainYear = document.getElementById('main_year_select') ? document.getElementById('main_year_select').value :
                null;

            if (mainMonth && document.getElementById('gen_month')) {
                document.getElementById('gen_month').value = mainMonth;
            }
            if (mainYear && document.getElementById('gen_year')) {
                document.getElementById('gen_year').value = mainYear;
            }

            syncPatternWithSite();
            openModal('modal-generate');
        }

        function filterEmployees() {
            const q = document.getElementById('employee-search').value.toLowerCase();
            let activeSiteId = document.getElementById('main_site_select') ? document.getElementById('main_site_select')
                .value : null;

            document.querySelectorAll('.employee-option').forEach(function(el) {
                let empSiteId = el.getAttribute('data-site-id');
                let matchesName = el.dataset.name.includes(q);
                let matchesSite = (!activeSiteId || activeSiteId === 'all' || empSiteId == activeSiteId);

                el.style.display = (matchesName && matchesSite) ? '' : 'none';
            });
        }

        function toggleAllEmployees(checked) {
            document.querySelectorAll('.employee-option').forEach(function(el) {
                if (el.style.display !== 'none') {
                    let cb = el.querySelector('.employee-checkbox');
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

        document.addEventListener('DOMContentLoaded', function() {
            updateEmployeeCount();

            @if (
                $errors->has('employee_ids') ||
                    $errors->has('start_shift_id') ||
                    $errors->has('active_shifts') ||
                    $errors->has('start_day') ||
                    $errors->has('shift_duration'))
                openGenerateModal();
            @elseif ($errors->has('schedule_type') || $errors->has('work_days') || $errors->has('off_days'))
                openPatternModal();
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
