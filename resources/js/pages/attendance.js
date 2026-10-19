const config = JSON.parse(document.getElementById('attendance-config').textContent);

var attendanceState = attendanceState || {};
var currentActiveEmployeeId = currentActiveEmployeeId || null;
var indonesianHolidays = config.holidays;
let employeesRequest;
let employeesSequence = 0;

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

function initAttendancePage() {
    updateHiddenMonth();
    let activeSiteSelect = document.getElementById('main_branch_select');
    let activeSiteId = activeSiteSelect ? activeSiteSelect.value : null;
    if (activeSiteId) {
        fetchEmployees(activeSiteId);
    }
    filterRecapTable();
}

function setLoading(isLoading) {
    let loadingEl = document.getElementById('employee_loading');
    let fieldContainer = document.getElementById('employee_fields');
    if (!loadingEl || !fieldContainer) return;
    loadingEl.classList.toggle('hidden', !isLoading);
    fieldContainer.classList.toggle('hidden', isLoading);
}

function isAfterLastDate(employeeId, day) {
    const lastDate = attendanceState[employeeId]?.lastDate;
    if (!lastDate) return false;
    const { year, month } = parseMonthRaw();
    const date = `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
    return date > lastDate;
}

function scheduledSessions(employeeId, day) {
    if (isAfterLastDate(employeeId, day)) return { s1: 0, s2: 0, s3: 0 };
    return { ...(attendanceState[employeeId].scheduled[day] || { s1: 0, s2: 0, s3: 0 }) };
}

function toggleManualInput(isChecked) {
    document.getElementById('auto_full_hidden').value = isChecked ? 'true' : 'false';
    Object.keys(attendanceState).forEach(empId => {
        for (let d = 1; d <= getDaysInMonth(); d++) {
            attendanceState[empId].shifts[d] = isChecked ? scheduledSessions(empId, d) : { s1: 0, s2: 0, s3: 0 };
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

    employeesRequest?.abort();
    employeesRequest = new AbortController();
    const sequence = ++employeesSequence;
    setLoading(true);

    fetch('/api/branches/' + siteId + '/employees?month=' + monthVal, { signal: employeesRequest.signal })
        .then(async response => {
            if (!response.ok) {
                let errText = 'Data karyawan gagal dimuat (' + response.status + ')';
                try {
                    let errJson = await response.json();
                    if (errJson.message) errText = errJson.message;
                } catch (e) {}
                throw new Error(errText);
            }
            return response.json();
        })
        .then(data => {
            if (sequence !== employeesSequence) return;
            fieldContainer.innerHTML = '';
            attendanceState = {};

            if (!data || data.length === 0) {
                fieldContainer.innerHTML = `
                    <div class="py-12 text-center text-slate-400">
                        <i class="block mb-2 text-3xl opacity-50 fa-solid fa-user-xmark"></i>
                        <span class="text-xs font-bold sm:text-sm text-slate-700">Belum ada karyawan yang terdaftar.</span>
                    </div>`;
                if (countLabel) countLabel.innerText = '';
                return;
            }

            let isAutoFull = document.getElementById('autoFullAttendance') ?
                document.getElementById('autoFullAttendance').checked : false;

            const escapeEmployeeHtml = value => String(value ?? '').replace(/[&<>"']/g, char => ({
                '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'
            }[char]));
            const employeeRows = [];
            data.forEach(emp => {

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
                    lastDate: emp.last_working_date,
                    scheduled: emp.scheduled_attendance || {},
                    shifts: {}
                };

                for (let d = 1; d <= totalDays; d++) {
                    if (isAfterLastDate(emp.id, d)) {
                        attendanceState[emp.id].shifts[d] = { s1: 0, s2: 0, s3: 0 };
                    } else if (savedShifts) {
                        attendanceState[emp.id].shifts[d] = {
                            s1: Number(savedShifts[d]?.s1 || 0),
                            s2: Number(savedShifts[d]?.s2 || 0),
                            s3: Number(savedShifts[d]?.s3 || 0)
                        };
                    } else {
                        attendanceState[emp.id].shifts[d] = isAutoFull ? scheduledSessions(emp.id, d) : { s1: 0, s2: 0, s3: 0 };
                    }
                }

                let siteBadge = emp.site ?
                    `<span class="px-2 py-0.5 text-xs font-extrabold bg-slate-100 text-slate-700 rounded-md border border-slate-200 me-2">${escapeEmployeeHtml(emp.site.machine_name)}</span>` :
                    '';

                employeeRows.push( `
                    <div class="grid items-center grid-cols-1 gap-3 p-4 transition-all border border-slate-200/80 rounded-2xl md:grid-cols-12 hover:border-slate-300 bg-slate-50/50">
                        <div class="md:col-span-4">
                            <span class="flex items-center gap-2 text-xs font-extrabold truncate text-slate-900 sm:text-sm" title="${escapeEmployeeHtml(emp.name)}">
                                <i class="text-sm text-slate-400 fa-solid fa-circle-user"></i>${siteBadge}${escapeEmployeeHtml(emp.name)}
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
                                <i class="fa-solid fa-pen-to-square"></i> Isi / Ubah
                            </button>
                        </div>
                        <input type="hidden" name="calendar_raw_data[${emp.id}]" id="raw_data_${emp.id}">
                    </div>`);

            });
            fieldContainer.innerHTML = employeeRows.join('');
            data.forEach(emp => updateLiveCounters(emp.id));

            if (countLabel) countLabel.innerText = data.length + ' karyawan dimuat';
        })
        .catch(err => {
            if (err.name === 'AbortError' || sequence !== employeesSequence) return;
            fieldContainer.innerHTML = `
                <div class="flex items-center gap-2.5 p-4 text-xs sm:text-sm font-semibold text-rose-800 border border-rose-200 bg-rose-50 rounded-2xl">
                    <i class="fa-solid fa-triangle-exclamation text-rose-600"></i> Data karyawan gagal dimuat. Periksa koneksi lalu pilih ulang lokasi untuk mencoba lagi.
                </div>`;
        })
        .finally(() => { if (sequence === employeesSequence) setLoading(false); });
}

function updateLiveCounters(employeeId) {
    let empData = attendanceState[employeeId];
    if (!empData) return;
    let totalDays = getDaysInMonth();
    let s1 = 0,
        s2 = 0,
        s3 = 0;
    for (let d = 1; d <= totalDays; d++) {
        if (isAfterLastDate(employeeId, d)) empData.shifts[d] = { s1: 0, s2: 0, s3: 0 };
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
    if (isAfterLastDate(currentActiveEmployeeId, day)) return;
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
        if (isAfterLastDate(employeeId, d)) {
            rows += `<tr class="border-b border-slate-100 bg-slate-100"><td class="px-4 py-2.5 text-xs text-slate-400">Tanggal ${d}</td><td colspan="3" class="px-3 py-2.5 text-xs font-bold text-slate-400">Tidak tersedia — setelah hari terakhir bekerja</td></tr>`;
            continue;
        }
        let holidayName = getHolidayName(d);
        let isOff = isWeekendDay(d) || holidayName;
        let weekendBadge = isOff ?
            `<span class="ml-1 text-xs text-rose-500 font-bold">(Off${holidayName ? ' - ' + holidayName : ''})</span>` :
            '';
        rows += `
            <tr class="border-b border-slate-100 hover:bg-slate-50">
                <td class="px-4 py-2.5 text-xs font-bold text-left text-slate-700">Tanggal ${d}${weekendBadge}</td>
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
        countBadge.textContent = `${visibleCount} data ditampilkan`;
    }
}

initAttendancePage();

Object.assign(window, { getFormattedMonth, updateHiddenMonth, handleFilterChange, openModal, closeModal, parseMonthRaw, getDaysInMonth, isWeekendDay, getHolidayName, isHoliday, initAttendancePage, setLoading, isAfterLastDate, scheduledSessions, toggleManualInput, fetchEmployees, updateLiveCounters, toggleDateShift, syncCalendarCheckboxesIfOpen, openPlotCalendar, filterRecapTable });
