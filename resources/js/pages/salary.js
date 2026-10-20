import { createLatestRequest } from '../ui/requests.js';
const loadList = createLatestRequest();
const config = JSON.parse(document.getElementById('salary-config').textContent);

var debounceTimer = debounceTimer || null;

function openGenerateModal() {
    const monthEl = document.getElementById('filter-month');
    const yearEl = document.getElementById('filter-year');

    if (monthEl) document.getElementById('modal_gen_month').value = monthEl.value;
    if (yearEl) document.getElementById('modal_gen_year').value = yearEl.value;

    let modal = document.getElementById('modalGenerateSalary');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}

function closeGenerateModal() {
    let modal = document.getElementById('modalGenerateSalary');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.classList.remove('overflow-hidden');
}

function openResetModal() {
    const monthEl = document.getElementById('filter-month');
    const yearEl = document.getElementById('filter-year');

    if (monthEl) document.getElementById('modal_reset_month').value = monthEl.value;
    if (yearEl) document.getElementById('modal_reset_year').value = yearEl.value;

    let modal = document.getElementById('modalResetSalary');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}

function closeResetModal() {
    let modal = document.getElementById('modalResetSalary');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.classList.remove('overflow-hidden');
}

function fetchSalaries(targetUrl = null, historyMode = targetUrl ? 'push' : 'replace') {
    clearTimeout(debounceTimer);
    const tableContainer = document.getElementById('table-container');
    if (!tableContainer) return;

    tableContainer.style.opacity = '0.5';

    const month = document.getElementById('filter-month')?.value || '';
    const year = document.getElementById('filter-year')?.value || '';
    const search = document.getElementById('filter-search')?.value || '';
    const branchId = document.getElementById('filter-branch')?.value || '';
    const information = document.getElementById('filter-information')?.value || '';
    const bank = document.getElementById('filter-bank')?.value || '';

    const url = targetUrl ||
        `${config.indexUrl}?month=${month}&year=${year}&search=${encodeURIComponent(search)}&branch_id=${encodeURIComponent(branchId)}&information=${encodeURIComponent(information)}&bank=${encodeURIComponent(bank)}`;

    tableContainer.setAttribute('aria-busy', 'true');
    tableContainer.inert = true;
    return loadList(url, {
        onSuccess: html => {
            tableContainer.innerHTML = html;
            if (historyMode !== 'none') history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url);
            applySalaryVisibility();
        },
        onFinally: () => { tableContainer.inert = false; tableContainer.style.opacity = '1'; tableContainer.removeAttribute('aria-busy'); },
    });
}

function initSalaryPageScripts() {
    const filterMonth = document.getElementById('filter-month');
    const filterYear = document.getElementById('filter-year');
    const filterBranch = document.getElementById('filter-branch');
    const filterInfo = document.getElementById('filter-information');
    const filterBank = document.getElementById('filter-bank');
    const filterSearch = document.getElementById('filter-search');

    if (filterMonth) filterMonth.onchange = () => fetchSalaries();
    if (filterYear) filterYear.onchange = () => fetchSalaries();
    if (filterBranch) filterBranch.onchange = () => fetchSalaries();
    if (filterInfo) filterInfo.onchange = () => fetchSalaries();
    if (filterBank) filterBank.onchange = () => fetchSalaries();

    if (filterSearch) {
        filterSearch.oninput = function() {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchSalaries();
            }, 300);
        };
    }

    const container = document.getElementById('table-container');
    if (container) {
        container.onclick = function(e) {
            const pageLink = e.target.closest('a');
            if (pageLink && pageLink.href && (pageLink.closest('nav') || pageLink.closest('.pagination') ||
                    pageLink.getAttribute('rel'))) {
                e.preventDefault();
                fetchSalaries(pageLink.href);
            }
        };
    }
}

function showSalaryDetail(id) {
    const month = document.getElementById('filter-month')?.value || '';
    const year = document.getElementById('filter-year')?.value || '';

    fetch(`/salary/${id}?month=${month}&year=${year}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async res => {
            if (!res.ok) {
                let errMsg = 'Rincian gaji gagal dimuat (' + res.status + ')';
                try {
                    let errJson = await res.json();
                    if (errJson.message) errMsg = errJson.message;
                } catch (e) {}
                throw new Error(errMsg);
            }
            return res.json();
        })
        .then(data => {
            document.getElementById('detail_name').innerText = data.name || '-';
            document.getElementById('detail_position').innerText = data.position || '-';
            document.getElementById('detail_placement').innerText = data.placement || '-';
            document.getElementById('detail_bank').innerText = data.bank || '-';
            document.getElementById('detail_account_no').innerText = data.account_no || '-';
            document.getElementById('detail_amount').innerText = data.amount_formatted || 'Rp 0';
            const allowanceList = document.getElementById('detail_allowances');
            if (allowanceList) {
                allowanceList.replaceChildren();
                const items = data.calculation?.allowances || [];
                for (const item of items) {
                    const row = document.createElement('li');
                    row.className = 'flex flex-wrap justify-between gap-2 break-words';
                    const name = document.createElement('span');
                    name.textContent = item.name;
                    const amount = document.createElement('strong');
                    amount.textContent = new Intl.NumberFormat('id-ID', {style: 'currency', currency: 'IDR', maximumFractionDigits: 0}).format(Number(item.amount));
                    row.append(name, amount);
                    allowanceList.append(row);
                }
                if (!items.length) allowanceList.textContent = 'Tidak ada tunjangan pada payroll ini.';
            }
            const allowanceTotal = document.getElementById('detail_allowance_total');
            if (allowanceTotal) allowanceTotal.textContent = data.calculation?.allowance_total || 'Rp 0';

            document.getElementById('detail_before_after').innerText = data.before_after || '-';
            document.getElementById('detail_more_information').innerText = data.more_information ||
                'Belum ada keterangan tambahan.';
            document.getElementById('detail_get_information').innerText = data.get_information ||
                'Belum ada catatan.';

            document.getElementById('detail_period_label').innerText = month + '/' + year;

            document.getElementById('detail_effective_days').innerText = (data.calculation ? data.calculation
                .effective_days : 0) + ' Hari';
            document.getElementById('detail_holiday_days').innerText = (data.calculation ? data.calculation
                .holiday_overtime_days : 0) + ' Hari';
            document.getElementById('detail_holiday_pay').innerText = data.calculation ? data.calculation
                .holiday_overtime_pay : 'Rp 0';
            document.getElementById('detail_total_pay').innerText = data.calculation ? data.calculation
                .total_salary_to_pay : 'Rp 0';

            let infoText = window.uiLabel(data.information || '-');
            let isProbation = (data.information || '').includes('probation');
            let badgeClass = isProbation ?
                'px-2.5 py-1 text-xs font-extrabold text-amber-800 bg-amber-50 border border-amber-200 rounded-full' :
                'px-2.5 py-1 text-xs font-extrabold text-blue-800 bg-blue-50 border border-blue-200 rounded-full';

            document.getElementById('detail_information_badge').innerHTML =
                `<span class="${badgeClass}">${infoText}</span>`;

            let modal = document.getElementById('salaryDetailModal');
            if (modal) {
                modal.classList.remove('hidden');
                modal.classList.add('flex');
                document.body.classList.add('overflow-hidden');
            }
        })
        .catch(err => (window.showUiMessage || window.alert)(err.message));
}

function closeSalaryModal() {
    let modal = document.getElementById('salaryDetailModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.classList.remove('overflow-hidden');
}

function exportExcel() {
    const month = document.getElementById('filter-month')?.value || '';
    const year = document.getElementById('filter-year')?.value || '';
    const search = document.getElementById('filter-search')?.value || '';
    const branchId = document.getElementById('filter-branch')?.value || '';
    const information = document.getElementById('filter-information')?.value || '';
    const bank = document.getElementById('filter-bank')?.value || '';

    const url =
        `${config.exportUrl}?month=${month}&year=${year}&search=${encodeURIComponent(search)}&branch_id=${encodeURIComponent(branchId)}&information=${encodeURIComponent(information)}&bank=${encodeURIComponent(bank)}`;

    window.location.href = url;
}

let isSalaryHidden = localStorage.getItem('isSalaryHidden') !== 'false';

function toggleTableSalaryVisibility() {
    isSalaryHidden = !isSalaryHidden;
    localStorage.setItem('isSalaryHidden', isSalaryHidden);
    applySalaryVisibility();
}

function applySalaryVisibility() {
    const icon = document.getElementById('table_salary_toggle_icon');
    const salaryElements = document.querySelectorAll('.salary-amount');

    if (icon) {
        if (isSalaryHidden) {
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    salaryElements.forEach(el => {
        const originalSalary = el.getAttribute('data-salary');
        if (isSalaryHidden) {
            el.innerText = '••••••••';
        } else {
            el.innerText = originalSalary;
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initSalaryPageScripts();
    applySalaryVisibility();

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeGenerateModal();
            closeResetModal();
            closeSalaryModal();
        }
    });
});

Object.assign(window, { openGenerateModal, closeGenerateModal, openResetModal, closeResetModal, fetchSalaries, initSalaryPageScripts, showSalaryDetail, closeSalaryModal, exportExcel, toggleTableSalaryVisibility, applySalaryVisibility });

window.addEventListener('popstate', () => {
    const params = new URL(location.href).searchParams;
    for (const [id, key, fallback] of [
        ['filter-month', 'month', String(new Date().getMonth() + 1).padStart(2, '0')],
        ['filter-year', 'year', String(new Date().getFullYear())], ['filter-search', 'search', ''],
        ['filter-branch', 'branch_id', ''], ['filter-information', 'information', ''], ['filter-bank', 'bank', ''],
    ]) { const field = document.getElementById(id); if (field) field.value = params.get(key) || fallback; }
    fetchSalaries(location.href, 'none');
});
