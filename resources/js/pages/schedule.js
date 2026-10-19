const config = JSON.parse(document.getElementById('schedule-config').textContent);

const SITE_PATTERNS = config.sitePatterns;

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

function toggleAllGenSites(checked) {
    document.querySelectorAll('.gen-site-checkbox').forEach(cb => {
        cb.checked = checked;
    });
    onGenSiteChange();
}

function onGenSiteChange() {
    const selectedSiteIds = Array.from(document.querySelectorAll('.gen-site-checkbox:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.employee-option').forEach(el => {
        const empSiteId = el.getAttribute('data-site-id');
        const isMatched = selectedSiteIds.includes(empSiteId);

        el.style.display = isMatched ? '' : 'none';

        if (!isMatched) {
            const cb = el.querySelector('.employee-checkbox');
            if (cb) cb.checked = false;
        }
    });

    const emptyNotice = document.getElementById('employee-empty-notice');
    if (emptyNotice) {
        emptyNotice.classList.toggle('hidden', selectedSiteIds.length > 0);
    }

    if (selectedSiteIds.length > 0) {
        const firstSiteId = selectedSiteIds[0];
        const pattern = SITE_PATTERNS[firstSiteId] ?? {
            schedule_type: 'office_hour',
            work_days: 6,
            off_days: 2
        };
        document.getElementById('gen_schedule_type').value = pattern.schedule_type;
        document.getElementById('gen_work_days').value = pattern.work_days;
        document.getElementById('gen_off_days').value = pattern.off_days;
    }

    onScheduleTypeChange();
    updateEmployeeCount();
}

function moveShiftItem(button, direction) {
    const row = button.closest('.shift-item-row');
    if (!row) return;

    const container = document.getElementById('shift-sequence-container');
    const visibleRows = Array.from(container.children).filter(r => r.style.display !== 'none');
    const currentIndex = visibleRows.indexOf(row);

    if (currentIndex === -1) return;

    if (direction === 'up' && currentIndex > 0) {
        const targetRow = visibleRows[currentIndex - 1];
        container.insertBefore(row, targetRow);
    } else if (direction === 'down' && currentIndex < visibleRows.length - 1) {
        const targetRow = visibleRows[currentIndex + 1];
        container.insertBefore(targetRow, row);
    } else {
        updateStartingShift();
        return;
    }

    row.classList.add('ring-2', 'ring-purple-400', 'ring-offset-1');
    setTimeout(() => row.classList.remove('ring-2', 'ring-purple-400', 'ring-offset-1'), 260);

    updateStartingShift();
}

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
        if (startShiftDisplayText) startShiftDisplayText.textContent = 'Belum ada shift yang dipilih';
    }
}

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

    document.querySelectorAll('.shift-item-row').forEach(row => {
        const isOh = row.getAttribute('data-is-oh') === 'true';
        const isOffShift = row.getAttribute('data-is-off') === 'true';
        const cb = row.querySelector('.active-shift-checkbox');

        if (isOfficeHour) {
            row.style.display = isOh ? '' : 'none';
            if (cb) cb.checked = isOh;
        } else {
            // Dinamis Shift Rotation: Sembunyikan Office Hour DAN Shift OFF otomatis
            const isWorkShift = !isOh && !isOffShift;
            row.style.display = isWorkShift ? '' : 'none';
            if (cb) cb.checked = isWorkShift;
        }
    });

    updateStartingShift();
}

function filterEmployees() {
    const q = document.getElementById('employee-search').value.toLowerCase();
    const selectedSiteIds = Array.from(document.querySelectorAll('.gen-site-checkbox:checked'))
        .map(cb => cb.value);

    document.querySelectorAll('.employee-option').forEach(function(el) {
        const empSiteId = el.getAttribute('data-site-id');
        const matchesName = el.dataset.name.includes(q);
        const matchesSite = selectedSiteIds.includes(empSiteId);

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
        countEl.textContent = total > 0 ? `${total} karyawan dipilih` : 'Belum ada karyawan yang dipilih';
    }

    if (btnSubmit) {
        btnSubmit.disabled = total === 0;
    }
}

document.querySelectorAll('.employee-checkbox').forEach(function(cb) {
    cb.addEventListener('change', updateEmployeeCount);
});

function openGenerateModal() {
    const mainSiteSelect = document.getElementById('main_site_select');
    const mainMonth = document.getElementById('main_month_select');
    const mainYear = document.getElementById('main_year_select');

    if (mainMonth && document.getElementById('gen_month')) {
        document.getElementById('gen_month').value = mainMonth.value;
    }
    if (mainYear && document.getElementById('gen_year')) {
        document.getElementById('gen_year').value = mainYear.value;
    }

    if (mainSiteSelect) {
        if (mainSiteSelect.value !== 'all') {
            document.querySelectorAll('.gen-site-checkbox').forEach(cb => {
                cb.checked = (cb.value === mainSiteSelect.value);
            });
        } else {
            document.querySelectorAll('.gen-site-checkbox').forEach(cb => {
                cb.checked = true;
            });
            const checkAll = document.getElementById('check-all-sites');
            if (checkAll) checkAll.checked = true;
        }
    }

    onGenSiteChange();
    openModal('modal-generate');
}

document.addEventListener('DOMContentLoaded', function() {
    updateEmployeeCount();
    onGenSiteChange();
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

async function submitQuickEditShift(event) {
    event.preventDefault();
    const form = document.getElementById('form-quick-edit-shift');
    if (form.dataset.busy) return;
    const button = form.querySelector('button[type="submit"]');
    const label = button?.textContent;
    form.dataset.busy = 'true';
    form.setAttribute('aria-busy', 'true');
    if (button) { button.disabled = true; button.textContent = 'Menyimpan…'; }
    try {
        const response = await fetch(config.updateUrl, {
            method: 'POST', headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
            }, body: new FormData(form),
        });
        if (response.redirected) throw new Error('Sesi berakhir. Muat ulang halaman dan login kembali.');
        const data = await response.json().catch(() => { throw new Error('Respons penyimpanan tidak dapat dibaca. Muat ulang halaman dan coba lagi.'); });
        if (!response.ok || !data.success) throw new Error(data.message || 'Giliran kerja gagal diperbarui.');
        window.location.reload();
    } catch (error) {
        window.showUiMessage?.(error.message || 'Koneksi terputus. Periksa jaringan dan coba lagi.');
    } finally {
        delete form.dataset.busy;
        form.removeAttribute('aria-busy');
        if (button) { button.disabled = false; button.textContent = label; }
    }
}

Object.assign(window, { openModal, closeModal, toggleAllGenSites, onGenSiteChange, moveShiftItem, updateStartingShift, onScheduleTypeChange, filterEmployees, toggleAllEmployees, updateEmployeeCount, openGenerateModal, openEditShiftModal, submitQuickEditShift });
