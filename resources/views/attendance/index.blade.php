@extends('layout.master')

@section('title', 'Attendance Recap Dashboard')

@section('content')
    <div class="w-full space-y-6">

        {{-- ============ 1. PAGE HEADER CARD ============ --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <nav class="flex items-center gap-2 mb-1.5 text-xs font-bold tracking-wider text-slate-400 uppercase">
                        <span class="transition-colors cursor-pointer hover:text-blue-600">Attendance & Roster</span>
                        <i class="fa-solid fa-chevron-right text-[9px]"></i>
                        <span class="font-extrabold text-blue-600">Attendance Recap</span>
                    </nav>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">
                        Attendance Recap
                    </h1>
                    <p class="mt-1 text-xs font-semibold sm:text-sm text-slate-500">
                        Manage and monitor employee attendance sessions per site location and operational period.
                    </p>
                    @if (Auth::user()?->role === 'admin_site')
                        <p
                            class="mt-2 text-xs font-bold text-blue-700 bg-blue-50 border border-blue-200/80 px-3 py-1 rounded-full inline-flex items-center gap-1.5">
                            <i class="fa-solid fa-building-user"></i> Site Admin:
                            {{ Auth::user()->site->machine_name ?? '-' }}
                        </p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ============ STEP 1: FILTER / WORKSPACE CARD ============ --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h5 class="flex items-center gap-2.5 text-xs font-extrabold tracking-wider uppercase text-slate-700">
                    <span
                        class="flex items-center justify-center w-5 h-5 text-[10px] font-black text-white bg-blue-600 rounded-full shrink-0">1</span>
                    Select Site &amp; Period
                </h5>
            </div>

            <div class="p-6">
                <form action="{{ route('attendance.index') }}" method="GET" id="mainWorkspaceFilterForm"
                    class="grid items-end grid-cols-1 gap-4 md:grid-cols-12">

                    <div class="md:col-span-5 space-y-1.5">
                        <label for="main_branch_select"
                            class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Target Site Location / Branch
                        </label>

                        @if (Auth::user()?->role === 'superadmin')
                            <select name="site_id" id="main_branch_select"
                                class="w-full p-2.5 text-xs sm:text-sm font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 outline-none transition-all"
                                required onchange="handleFilterChange()">
                                <option value="">-- Select Branch Site --</option>
                                <option value="all" {{ request('site_id', $siteId) === 'all' ? 'selected' : '' }}>
                                    🌐 -- Display All Sites / Branches --
                                </option>
                                @foreach ($sites as $site)
                                    <option value="{{ $site->id }}"
                                        {{ request('site_id', $siteId) == $site->id ? 'selected' : '' }}>
                                        {{ $site->machine_name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            <input type="hidden" name="site_id" id="main_branch_select"
                                value="{{ Auth::user()->site_id }}">
                            <input type="text" value="{{ Auth::user()->site->machine_name ?? 'Registered Site' }}"
                                class="w-full p-2.5 text-xs sm:text-sm font-bold text-slate-700 bg-slate-100 border border-slate-200 rounded-xl cursor-not-allowed"
                                readonly>
                        @endif
                    </div>

                    <div class="md:col-span-4 space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Recap Period</label>
                        <div class="flex gap-2">
                            @php
                                $requestMonthRaw = request('month', date('Y-m'));
                                $selectedMonth = date('m', strtotime($requestMonthRaw . '-01'));
                                $selectedYear = date('Y', strtotime($requestMonthRaw . '-01'));
                                $monthList = [
                                    '01' => 'January',
                                    '02' => 'February',
                                    '03' => 'March',
                                    '04' => 'April',
                                    '05' => 'May',
                                    '06' => 'June',
                                    '07' => 'July',
                                    '08' => 'August',
                                    '09' => 'September',
                                    '10' => 'October',
                                    '11' => 'November',
                                    '12' => 'December',
                                ];
                            @endphp
                            <select id="filter_bulan" aria-label="Month"
                                class="w-full p-2.5 text-xs sm:text-sm font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 outline-none transition-all"
                                onchange="handleFilterChange()" required>
                                @foreach ($monthList as $code => $name)
                                    <option value="{{ $code }}" {{ $selectedMonth == $code ? 'selected' : '' }}>
                                        {{ $name }}
                                    </option>
                                @endforeach
                            </select>
                            <select id="filter_tahun" aria-label="Year"
                                class="w-full p-2.5 text-xs sm:text-sm font-bold bg-slate-50 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 text-slate-800 outline-none transition-all"
                                onchange="handleFilterChange()" required>
                                @for ($year = date('Y') - 2; $year <= date('Y') + 2; $year++)
                                    <option value="{{ $year }}" {{ $selectedYear == $year ? 'selected' : '' }}>
                                        {{ $year }}
                                    </option>
                                @endfor
                            </select>
                        </div>
                        <input type="hidden" name="month" id="main_month_hidden" value="{{ $requestMonthRaw }}">
                    </div>

                    <div class="flex gap-2 md:col-span-3">
                        <button type="submit"
                            class="flex-grow flex items-center justify-center gap-1.5 px-4 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95">
                            <i class="fa-solid fa-folder-open"></i> Load Data
                        </button>
                        @php $activeSiteId = request('site_id', $siteId); @endphp
                        <a id="exportBtn"
                            href="{{ route('attendance.export', ['site_id' => $activeSiteId, 'month' => request('month', date('Y-m'))]) }}"
                            title="Export to Excel"
                            class="px-4 py-2.5 rounded-xl font-bold text-xs flex items-center justify-center transition-all active:scale-95 {{ empty($activeSiteId) ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed pointer-events-none' : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-md shadow-emerald-600/20' }}">
                            <i class="mr-1 fa-solid fa-file-excel"></i> Export
                        </a>
                    </div>
                </form>
            </div>
        </div>

        {{-- ============ NATIONAL HOLIDAYS BANNER ============ --}}
        @if (!empty($holidays))
            <div class="p-4 border border-rose-200/80 rounded-2xl bg-rose-50/60">
                <h6 class="flex items-center gap-2 mb-2 text-xs font-extrabold tracking-wider uppercase text-rose-700">
                    <i class="fa-solid fa-circle-exclamation"></i> National Holidays & Red Dates This Month
                </h6>
                <ul class="grid grid-cols-1 gap-x-6 gap-y-1 sm:grid-cols-2 lg:grid-cols-3">
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

        {{-- ============ STEP 2: MASS ATTENDANCE INPUT CARD ============ --}}
        @php $currentSiteId = request('site_id', $siteId); @endphp
        @if ($currentSiteId)
            <div class="p-6 space-y-5 bg-white border shadow-xs border-slate-200/80 rounded-3xl">
                <div
                    class="flex flex-col gap-3 pb-4 border-b border-slate-100 sm:flex-row sm:justify-between sm:items-center">
                    <div>
                        <h5 class="flex items-center gap-2.5 text-sm sm:text-base font-extrabold text-slate-800">
                            <span
                                class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-blue-600 rounded-full shrink-0">2</span>
                            Record Employee Attendance {{ $currentSiteId === 'all' ? '(All Sites)' : '' }}
                        </h5>
                        <p class="mt-1 text-xs font-medium text-slate-500 ms-8">
                            Check working sessions per date, or toggle automatic schedule alignment below.
                        </p>
                    </div>
                    <div
                        class="flex items-center gap-3 px-3.5 py-2 border border-slate-200 rounded-2xl bg-slate-50 shrink-0">
                        <label for="autoFullAttendance" class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="autoFullAttendance"
                                {{ request('auto_full', 'true') === 'true' ? 'checked' : '' }}
                                onchange="toggleManualInput(this.checked)" class="sr-only peer">
                            <div
                                class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600">
                            </div>
                            <span class="text-xs font-bold select-none text-slate-700 ms-2">
                                Auto-Fill From Schedule
                            </span>
                        </label>
                    </div>
                </div>

                <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-2">
                    <p class="text-xs font-semibold text-slate-500 flex items-center gap-1.5">
                        <i class="text-blue-600 fa-solid fa-circle-info"></i>
                        When enabled: employees are automatically marked present according to the shift rotas set in the
                        Schedule module.
                    </p>

                    <div class="flex flex-wrap items-center gap-4 pt-1 text-xs font-extrabold text-slate-600">
                        <span class="flex items-center gap-1.5"><span class="font-black text-blue-600">S1</span> = Shift
                            1</span>
                        <span class="flex items-center gap-1.5"><span class="font-black text-amber-600">S2</span> = Shift
                            2</span>
                        <span class="flex items-center gap-1.5"><span class="font-black text-rose-600">S3</span> = Shift
                            3</span>
                        <span class="flex items-center gap-1.5 text-rose-600">
                            <i class="fa-solid fa-circle-exclamation"></i> National Holiday / Off
                        </span>
                    </div>
                </div>

                <form action="{{ route('attendance.store') }}" method="POST" id="massAttendanceForm">
                    @csrf
                    <input type="hidden" name="month" id="form_month_hidden"
                        value="{{ request('month', date('Y-m')) }}">
                    <input type="hidden" name="site_id" value="{{ $currentSiteId }}">
                    <input type="hidden" name="auto_full" id="auto_full_hidden"
                        value="{{ request('auto_full', 'true') }}">

                    <div id="employee_loading"
                        class="flex items-center justify-center gap-2 py-12 text-xs font-bold sm:text-sm text-slate-400">
                        <i class="text-base text-blue-600 animate-spin fa-solid fa-spinner"></i> Loading employee data...
                    </div>

                    <div id="employee_fields" class="space-y-3"></div>

                    <div class="flex items-center justify-between pt-4 mt-6 border-t border-slate-100">
                        <span class="text-xs font-bold text-slate-400" id="employeeCountLabel"></span>
                        <button type="submit"
                            class="flex items-center gap-2 px-6 py-2.5 ml-auto text-xs font-bold text-white transition-all bg-blue-600 rounded-xl shadow-md shadow-blue-600/20 hover:bg-blue-700 active:scale-95">
                            <i class="fa-solid fa-cloud-arrow-up"></i> Save &amp; Update Attendance
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- ============ CALENDAR PLOT MODAL ============ --}}
        <div id="plotCalendarModal"
            class="fixed inset-0 z-50 items-center justify-center hidden p-4 transition-all duration-200 bg-slate-900/60 backdrop-blur-xs">
            <div class="w-full max-w-md mx-auto">
                <div class="overflow-hidden bg-white border shadow-2xl border-slate-100 rounded-3xl">
                    <div class="flex items-center justify-between px-6 py-5 text-white bg-slate-900">
                        <h5 class="flex items-center gap-2 text-xs font-extrabold tracking-wide uppercase sm:text-sm">
                            <i class="text-blue-400 fa-solid fa-calendar-days"></i> Plot Sessions:
                            <span id="modalEmployeeName" class="text-blue-200"></span>
                        </h5>
                        <button type="button"
                            class="flex items-center justify-center w-8 h-8 transition-colors rounded-lg text-slate-400 hover:text-white bg-slate-800 hover:bg-slate-700"
                            onclick="closeModal('plotCalendarModal')" aria-label="Close">&times;</button>
                    </div>
                    <div class="p-5 bg-slate-50">
                        <div
                            class="bg-white border border-slate-200/80 rounded-2xl overflow-y-auto max-h-[380px] shadow-2xs">
                            <table class="w-full text-xs text-center border-collapse">
                                <thead
                                    class="sticky top-0 font-extrabold tracking-wider uppercase border-b text-slate-600 bg-slate-100/90 border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left">Date</th>
                                        <th class="px-3 py-3 font-black text-blue-600">S1</th>
                                        <th class="px-3 py-3 font-black text-amber-600">S2</th>
                                        <th class="px-3 py-3 font-black text-rose-600">S3</th>
                                    </tr>
                                </thead>
                                <tbody id="calendarGridBody"
                                    class="font-semibold divide-y text-slate-700 divide-slate-100"></tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-6 py-4 border-t border-slate-100 bg-slate-50">
                        <button type="button"
                            class="flex items-center justify-center w-full gap-2 py-2.5 text-xs font-bold text-white transition-all bg-blue-600 rounded-xl hover:bg-blue-700 active:scale-95 shadow-md shadow-blue-600/20"
                            onclick="closeModal('plotCalendarModal')">
                            <i class="fa-solid fa-check"></i> Done Plotting
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- ============ STEP 3: REPORT TABLE CARD ============ --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div
                class="flex flex-col gap-4 p-5 border-b sm:p-6 border-slate-100 bg-slate-50/50 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <span
                        class="flex items-center justify-center w-6 h-6 text-xs font-black text-white bg-blue-600 rounded-full shadow-2xs shrink-0">
                        3
                    </span>
                    <div>
                        <h5 class="text-base font-extrabold text-slate-900">Review Attendance Summary</h5>
                        <p class="text-xs font-medium text-slate-500">Overview of effective working days and total attended
                            sessions.</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative min-w-[200px]">
                        <select id="recapMonthFilter" onchange="filterRecapTable()"
                            class="block w-full py-2.5 pl-3.5 pr-8 text-xs font-bold transition-all bg-white border cursor-pointer text-slate-800 border-slate-200 rounded-xl focus:outline-none focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 shadow-2xs">
                            <option value="all">-- All Months --</option>
                            @php
                                $availableMonths = $attendances->pluck('month')->unique()->sortDesc();
                            @endphp
                            @foreach ($availableMonths as $m)
                                <option value="{{ $m }}"
                                    {{ request('month', date('Y-m')) == $m ? 'selected' : '' }}>
                                    {{ \Carbon\Carbon::parse($m)->format('F Y') }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <span id="recapCountBadge"
                        class="items-center hidden px-3 py-2 text-xs font-extrabold text-blue-800 border md:inline-flex bg-blue-50 border-blue-200/80 rounded-xl">
                        0 Records
                    </span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse min-w-[850px]">
                    <thead>
                        <tr
                            class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider bg-slate-50 border-b border-slate-100">
                            <th scope="col" class="px-6 py-4">Branch / Site Location</th>
                            <th scope="col" class="px-6 py-4">Employee Name</th>
                            <th scope="col" class="px-6 py-4 text-center">Month Period</th>
                            <th scope="col" class="px-6 py-4 text-center">Effective Work Days</th>
                            <th scope="col" class="px-6 py-4 text-center">Total Attended Sessions</th>
                            <th scope="col" class="px-6 py-4 text-right">Attendance Ratio</th>
                            <th scope="col" class="w-24 px-6 py-4 text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="recapTableBody" class="text-xs font-medium divide-y divide-slate-100 text-slate-700">
                        @forelse($attendances as $row)
                            @if (Auth::user()?->role === 'superadmin' ||
                                    (Auth::user()?->role === 'admin_site' && Auth::user()->site_id === $row->employee->site_id))
                                @php
                                    $percentage =
                                        $row->working_days > 0
                                            ? round(($row->attendance_count / $row->working_days) * 100)
                                            : 0;

                                    $progressBg = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                    if ($percentage < 50) {
                                        $progressBg = 'bg-rose-50 text-rose-800 border-rose-200';
                                    } elseif ($percentage < 80) {
                                        $progressBg = 'bg-amber-50 text-amber-800 border-amber-200';
                                    }
                                @endphp
                                <tr class="transition-colors hover:bg-slate-50/60 recap-row"
                                    data-month="{{ $row->month }}">
                                    <td class="px-6 py-4 font-bold text-slate-800">
                                        <span
                                            class="inline-flex items-center gap-1.5 px-3 py-1 text-[11px] font-bold bg-slate-100 text-slate-700 border border-slate-200/80 rounded-lg">
                                            <i class="fa-solid fa-location-dot text-slate-400 text-[10px]"></i>
                                            {{ $row->employee->site->machine_name ?? '-' }}
                                        </span>
                                    </td>

                                    <td class="px-6 py-4">
                                        <div class="text-xs font-extrabold sm:text-sm text-slate-900">
                                            {{ $row->employee->name ?? 'Deleted Employee' }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 font-semibold mt-0.5">
                                            {{ $row->employee->position ?? 'Staff' }}
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 font-bold text-center text-slate-600">
                                        {{ \Carbon\Carbon::parse($row->month)->format('F Y') }}
                                    </td>

                                    <td class="px-6 py-4 font-bold text-center text-slate-600">
                                        {{ $row->working_days }} Days
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <span
                                            class="px-3 py-1 text-xs font-bold text-blue-800 border border-blue-200 rounded-lg bg-blue-50">
                                            {{ $row->attendance_count }} Sessions
                                        </span>
                                    </td>

                                    <td class="px-6 py-4 text-right">
                                        <div class="inline-flex items-center gap-2">
                                            <span class="font-extrabold text-slate-900">
                                                {{ $row->attendance_count }}/{{ $row->working_days }}
                                            </span>
                                            <span
                                                class="px-2.5 py-0.5 text-[10px] font-extrabold border rounded-full uppercase {{ $progressBg }}">
                                                {{ $percentage }}%
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 text-center">
                                        <form action="{{ route('attendance.destroy', $row->id) }}" method="POST"
                                            onsubmit="return confirm('Are you sure you want to delete this attendance recap log?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                class="flex items-center justify-center w-8 h-8 mx-auto transition-all border rounded-xl text-rose-600 bg-rose-50 border-rose-100 hover:bg-rose-600 hover:text-white active:scale-95"
                                                title="Delete Attendance Record">
                                                <i class="text-xs fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr id="emptyRecapRow">
                                <td colspan="7" class="p-12 text-center text-slate-400">
                                    <div
                                        class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                        <i class="fa-solid fa-inbox"></i>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Attendance Logs Recorded Yet</p>
                                    <p class="mt-1 text-xs text-slate-400">Use the panel above to submit attendance
                                        records.</p>
                                </td>
                            </tr>
                        @endforelse

                        <tr id="noFilteredRecapRow" class="hidden">
                            <td colspan="7" class="p-12 text-center text-slate-400">
                                <div
                                    class="flex items-center justify-center w-12 h-12 mx-auto mb-3 text-xl rounded-2xl bg-slate-100 text-slate-400">
                                    <i class="fa-solid fa-filter-circle-xmark"></i>
                                </div>
                                <p class="text-sm font-bold text-slate-800">No Attendance Data Found for Month Filter</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        let attendanceState = {};
        let currentActiveEmployeeId = null;
        let employeeSchedulesState = {};
        let indonesianHolidays = @json($holidays ?? []);

        function getFormattedMonth() {
            let bulan = document.getElementById('filter_bulan') ? document.getElementById('filter_bulan').value : '01';
            let tahun = document.getElementById('filter_tahun') ? document.getElementById('filter_tahun').value : new Date()
                .getFullYear();
            return tahun + '-' + String(bulan).padStart(2, '0');
        }

        function updateHiddenMonth() {
            let monthVal = getFormattedMonth();
            let hiddenInput = document.getElementById('main_month_hidden');
            let formHiddenInput = document.getElementById('form_month_hidden');
            if (hiddenInput) hiddenInput.value = monthVal;
            if (formHiddenInput) formHiddenInput.value = monthVal;

            let activeSiteSelect = document.getElementById('main_branch_select');
            let activeSiteId = activeSiteSelect ? activeSiteSelect.value : null;
            let exportBtn = document.getElementById('exportBtn');
            if (exportBtn && activeSiteId) {
                exportBtn.href = `/attendance/export?site_id=${activeSiteId}&month=${monthVal}`;
            }
        }

        function handleFilterChange() {
            updateHiddenMonth();
        }

        function openModal(modalId) {
            let el = document.getElementById(modalId);
            if (!el) return;
            el.classList.remove('hidden');
            el.classList.add('flex');
            document.body.classList.add('overflow-hidden');
        }

        function closeModal(modalId) {
            let el = document.getElementById(modalId);
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('flex');
            document.body.classList.remove('overflow-hidden');
        }

        function parseMonthRaw() {
            let monthRaw = getFormattedMonth();
            let parts = (monthRaw || '').split('-').map(Number);
            let year = parts[0] || new Date().getFullYear();
            let month = parts[1] || (new Date().getMonth() + 1);
            let days = new Date(year, month, 0).getDate();
            return {
                year,
                month,
                days
            };
        }

        function getDaysInMonth() {
            return parseMonthRaw().days;
        }

        function isWeekendDay(day) {
            let {
                year,
                month
            } = parseMonthRaw();
            let dow = new Date(year, month - 1, day).getDay();
            return dow === 0 || dow === 6;
        }

        function getHolidayName(day) {
            let {
                year,
                month
            } = parseMonthRaw();
            let dateStr = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            return indonesianHolidays.hasOwnProperty(dateStr) ? indonesianHolidays[dateStr] : null;
        }

        function isHoliday(day) {
            return !!getHolidayName(day);
        }

        document.addEventListener("DOMContentLoaded", function() {
            updateHiddenMonth();
            let activeSiteSelect = document.getElementById('main_branch_select');
            let activeSiteId = activeSiteSelect ? activeSiteSelect.value : null;
            if (activeSiteId) {
                fetchEmployees(activeSiteId);
            }
            filterRecapTable();
        });

        function setLoading(isLoading) {
            let loadingEl = document.getElementById('employee_loading');
            let fieldContainer = document.getElementById('employee_fields');
            if (!loadingEl || !fieldContainer) return;
            loadingEl.classList.toggle('hidden', !isLoading);
            fieldContainer.classList.toggle('hidden', isLoading);
        }

        function toggleManualInput(isChecked) {
            document.getElementById('auto_full_hidden').value = isChecked ? 'true' : 'false';
            let totalDays = getDaysInMonth();
            let {
                year,
                month
            } = parseMonthRaw();

            Object.keys(attendanceState).forEach(empId => {
                let schedules = employeeSchedulesState[empId] || [];

                for (let d = 1; d <= totalDays; d++) {
                    let dateStr = `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                    let sched = schedules.find(s => s.date === dateStr);

                    if (isChecked) {
                        if (sched && sched.shift) {
                            let shiftName = (sched.shift.shift_name || '').toLowerCase();
                            let isOff = sched.shift.is_off;

                            if (isOff) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 0
                                };
                            } else if (shiftName.includes('2')) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 1,
                                    s3: 0
                                };
                            } else if (shiftName.includes('3')) {
                                attendanceState[empId].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 1
                                };
                            } else {
                                attendanceState[empId].shifts[d] = {
                                    s1: 1,
                                    s2: 0,
                                    s3: 0
                                };
                            }
                        } else {
                            let shouldBeOff = isWeekendDay(d) || isHoliday(d);
                            attendanceState[empId].shifts[d] = {
                                s1: shouldBeOff ? 0 : 1,
                                s2: 0,
                                s3: 0
                            };
                        }
                    } else {
                        attendanceState[empId].shifts[d] = {
                            s1: 0,
                            s2: 0,
                            s3: 0
                        };
                    }
                }
                updateLiveCounters(empId);
                syncCalendarCheckboxesIfOpen(empId);
            });
        }

        function fetchEmployees(siteId) {
            let fieldContainer = document.getElementById('employee_fields');
            if (!fieldContainer) return;

            updateHiddenMonth();
            let monthVal = getFormattedMonth();
            let totalDays = getDaysInMonth();
            let {
                year,
                month
            } = parseMonthRaw();
            let countLabel = document.getElementById('employeeCountLabel');

            setLoading(true);

            fetch('/api/branches/' + siteId + '/employees?month=' + monthVal)
                .then(async response => {
                    if (!response.ok) {
                        let errText = 'Failed to load employee data (' + response.status + ')';
                        try {
                            let errJson = await response.json();
                            if (errJson.message) errText = errJson.message;
                        } catch (e) {}
                        throw new Error(errText);
                    }
                    return response.json();
                })
                .then(data => {
                    fieldContainer.innerHTML = '';
                    attendanceState = {};
                    employeeSchedulesState = {};

                    if (!data || data.length === 0) {
                        fieldContainer.innerHTML = `
                        <div class="py-12 text-center text-slate-400">
                            <i class="block mb-2 text-3xl opacity-50 fa-solid fa-user-xmark"></i>
                            <span class="text-xs font-bold sm:text-sm text-slate-700">No employees registered yet.</span>
                        </div>`;
                        if (countLabel) countLabel.innerText = '';
                        return;
                    }

                    let isAutoFull = document.getElementById('autoFullAttendance') ?
                        document.getElementById('autoFullAttendance').checked : false;

                    data.forEach(emp => {
                        employeeSchedulesState[emp.id] = emp.schedules || [];

                        let hasSavedData = emp.attendances && emp.attendances.length > 0 && emp.attendances[0]
                            .matrix_details;
                        let savedShifts = null;

                        if (hasSavedData) {
                            try {
                                savedShifts = typeof emp.attendances[0].matrix_details === 'string' ?
                                    JSON.parse(emp.attendances[0].matrix_details) :
                                    emp.attendances[0].matrix_details;
                            } catch (e) {
                                savedShifts = null;
                            }
                        }

                        attendanceState[emp.id] = {
                            name: emp.name,
                            shifts: {}
                        };

                        for (let d = 1; d <= totalDays; d++) {
                            if (savedShifts && savedShifts[d] !== undefined) {
                                attendanceState[emp.id].shifts[d] = {
                                    s1: savedShifts[d].s1 !== undefined ? parseInt(savedShifts[d].s1) : 0,
                                    s2: savedShifts[d].s2 !== undefined ? parseInt(savedShifts[d].s2) : 0,
                                    s3: savedShifts[d].s3 !== undefined ? parseInt(savedShifts[d].s3) : 0
                                };
                            } else if (isAutoFull) {
                                let dateStr =
                                    `${year}-${String(month).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                                let sched = (emp.schedules || []).find(s => s.date === dateStr);

                                if (sched && sched.shift) {
                                    let shiftName = (sched.shift.shift_name || '').toLowerCase();
                                    let isOff = sched.shift.is_off;

                                    if (isOff) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 0,
                                            s3: 0
                                        };
                                    } else if (shiftName.includes('2')) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 1,
                                            s3: 0
                                        };
                                    } else if (shiftName.includes('3')) {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 0,
                                            s2: 0,
                                            s3: 1
                                        };
                                    } else {
                                        attendanceState[emp.id].shifts[d] = {
                                            s1: 1,
                                            s2: 0,
                                            s3: 0
                                        };
                                    }
                                } else {
                                    let shouldBeOff = isWeekendDay(d) || isHoliday(d);
                                    attendanceState[emp.id].shifts[d] = {
                                        s1: shouldBeOff ? 0 : 1,
                                        s2: 0,
                                        s3: 0
                                    };
                                }
                            } else {
                                attendanceState[emp.id].shifts[d] = {
                                    s1: 0,
                                    s2: 0,
                                    s3: 0
                                };
                            }
                        }

                        let siteBadge = emp.site ?
                            `<span class="px-2 py-0.5 text-[10px] font-extrabold bg-slate-100 text-slate-700 rounded-md border border-slate-200 me-2">${emp.site.machine_name}</span>` :
                            '';

                        fieldContainer.insertAdjacentHTML('beforeend', `
                        <div class="grid items-center grid-cols-1 gap-3 p-4 transition-all border border-slate-200/80 rounded-2xl md:grid-cols-12 hover:border-slate-300 bg-slate-50/50">
                            <div class="md:col-span-4">
                                <span class="flex items-center gap-2 text-xs font-extrabold truncate text-slate-900 sm:text-sm" title="${emp.name}">
                                    <i class="text-sm text-slate-400 fa-solid fa-circle-user"></i>${siteBadge}${emp.name}
                                </span>
                            </div>
                            <div class="grid items-center grid-cols-4 gap-2 md:col-span-8">
                                <div class="flex overflow-hidden border rounded-xl shadow-2xs border-slate-200">
                                    <span class="flex items-center px-2 text-xs font-black border-r text-slate-500 bg-slate-100 border-slate-200">S1</span>
                                    <input type="text" id="counter_s1_${emp.id}" class="w-full py-1 text-xs font-bold text-center text-blue-600 bg-white focus:outline-none" readonly>
                                </div>
                                <div class="flex overflow-hidden border rounded-xl shadow-2xs border-slate-200">
                                    <span class="flex items-center px-2 text-xs font-black border-r text-slate-500 bg-slate-100 border-slate-200">S2</span>
                                    <input type="text" id="counter_s2_${emp.id}" class="w-full py-1 text-xs font-bold text-center bg-white text-amber-600 focus:outline-none" readonly>
                                </div>
                                <div class="flex overflow-hidden border rounded-xl shadow-2xs border-slate-200">
                                    <span class="flex items-center px-2 text-xs font-black border-r text-slate-500 bg-slate-100 border-slate-200">S3</span>
                                    <input type="text" id="counter_s3_${emp.id}" class="w-full py-1 text-xs font-bold text-center bg-white text-rose-600 focus:outline-none" readonly>
                                </div>
                                <button type="button" class="w-full px-2 py-2 text-xs font-bold text-blue-600 transition-colors border bg-blue-50 border-blue-200/80 rounded-xl hover:bg-blue-600 hover:text-white" onclick="openPlotCalendar(${emp.id})">
                                    <i class="fa-solid fa-pen-to-square"></i> Plot / Edit
                                </button>
                            </div>
                            <input type="hidden" name="calendar_raw_data[${emp.id}]" id="raw_data_${emp.id}">
                        </div>`);

                        updateLiveCounters(emp.id);
                    });

                    if (countLabel) countLabel.innerText = data.length + ' employee(s) loaded';
                })
                .catch(err => {
                    fieldContainer.innerHTML = `
                    <div class="flex items-center gap-2.5 p-4 text-xs sm:text-sm font-semibold text-rose-800 border border-rose-200 bg-rose-50 rounded-2xl">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> ${err.message || 'Failed to load employee records.'}
                    </div>`;
                })
                .finally(() => setLoading(false));
        }

        function updateLiveCounters(employeeId) {
            let empData = attendanceState[employeeId];
            if (!empData) return;
            let totalDays = getDaysInMonth();
            let s1 = 0,
                s2 = 0,
                s3 = 0;
            for (let d = 1; d <= totalDays; d++) {
                if (empData.shifts[d].s1 === 1) s1++;
                if (empData.shifts[d].s2 === 1) s2++;
                if (empData.shifts[d].s3 === 1) s3++;
            }
            let s1El = document.getElementById('counter_s1_' + employeeId);
            let s2El = document.getElementById('counter_s2_' + employeeId);
            let s3El = document.getElementById('counter_s3_' + employeeId);
            let rawEl = document.getElementById('raw_data_' + employeeId);
            if (s1El) s1El.value = s1;
            if (s2El) s2El.value = s2;
            if (s3El) s3El.value = s3;
            if (rawEl) rawEl.value = JSON.stringify(empData.shifts);
        }

        function toggleDateShift(day, shiftKey, isChecked) {
            if (currentActiveEmployeeId && attendanceState[currentActiveEmployeeId]) {
                attendanceState[currentActiveEmployeeId].shifts[day][shiftKey] = isChecked ? 1 : 0;
                updateLiveCounters(currentActiveEmployeeId);
            }
        }

        function syncCalendarCheckboxesIfOpen(employeeId) {
            if (currentActiveEmployeeId !== employeeId) return;
            let modalEl = document.getElementById('plotCalendarModal');
            if (!modalEl || modalEl.classList.contains('hidden')) return;
            openPlotCalendar(employeeId);
        }

        function openPlotCalendar(employeeId) {
            if (!attendanceState[employeeId]) return;
            currentActiveEmployeeId = employeeId;
            document.getElementById('modalEmployeeName').innerText = attendanceState[employeeId].name;
            let gridBody = document.getElementById('calendarGridBody');
            gridBody.innerHTML = '';
            let totalDays = getDaysInMonth();
            let rows = '';
            for (let d = 1; d <= totalDays; d++) {
                let dayData = attendanceState[employeeId].shifts[d];
                let holidayName = getHolidayName(d);
                let isOff = isWeekendDay(d) || holidayName;
                let weekendBadge = isOff ?
                    `<span class="ml-1 text-[10px] text-rose-500 font-bold">(Off${holidayName ? ' - ' + holidayName : ''})</span>` :
                    '';
                rows += `
                <tr class="border-b border-slate-100 hover:bg-slate-50">
                    <td class="px-4 py-2.5 text-xs font-bold text-left text-slate-700">Date ${d}${weekendBadge}</td>
                    <td class="px-3 py-2.5"><input type="checkbox" class="w-4 h-4 text-blue-600 rounded border-slate-300 focus:ring-blue-500" onchange="toggleDateShift(${d}, 's1', this.checked)" ${dayData.s1 ? 'checked' : ''}></td>
                    <td class="px-3 py-2.5"><input type="checkbox" class="w-4 h-4 rounded text-amber-600 border-slate-300 focus:ring-amber-500" onchange="toggleDateShift(${d}, 's2', this.checked)" ${dayData.s2 ? 'checked' : ''}></td>
                    <td class="px-3 py-2.5"><input type="checkbox" class="w-4 h-4 rounded text-rose-600 border-slate-300 focus:ring-rose-500" onchange="toggleDateShift(${d}, 's3', this.checked)" ${dayData.s3 ? 'checked' : ''}></td>
                </tr>`;
            }
            gridBody.innerHTML = rows;
            openModal('plotCalendarModal');
        }

        function filterRecapTable() {
            const selectedMonth = document.getElementById('recapMonthFilter') ? document.getElementById('recapMonthFilter')
                .value : 'all';
            const rows = document.querySelectorAll('.recap-row');
            const noFilteredRow = document.getElementById('noFilteredRecapRow');
            const countBadge = document.getElementById('recapCountBadge');

            let visibleCount = 0;

            rows.forEach(row => {
                const rowMonth = row.getAttribute('data-month');

                if (selectedMonth === 'all' || rowMonth === selectedMonth) {
                    row.classList.remove('hidden');
                    visibleCount++;
                } else {
                    row.classList.add('hidden');
                }
            });

            if (noFilteredRow) {
                if (visibleCount === 0 && rows.length > 0) {
                    noFilteredRow.classList.remove('hidden');
                } else {
                    noFilteredRow.classList.add('hidden');
                }
            }

            if (countBadge) {
                countBadge.textContent = `${visibleCount} Records Displayed`;
            }
        }
    </script>
@endpush
