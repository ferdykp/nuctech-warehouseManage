import { createLatestRequest } from '../ui/requests.js';
const loadList = createLatestRequest();
const config = JSON.parse(document.getElementById('reports-config').textContent);

// Checkbox Select All Logic
const selectAll = document.getElementById('select_all_id');
if (selectAll) {
    selectAll.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.sub_chk');
        checkboxes.forEach(cb => cb.checked = this.checked);
    });
}

function confirmDeleteSelected() {
    const selected = Array.from(document.querySelectorAll('.sub_chk:checked')).map(input => input.value);
    if (!selected.length) { window.showUiMessage?.('Pilih minimal satu laporan terlebih dahulu.'); return; }
    const form = document.getElementById('form-delete-report');
    form.querySelectorAll('[data-bulk-id]').forEach(input => input.remove());
    for (const id of selected) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = 'ids[]'; input.value = id; input.dataset.bulkId = 'true';
        form.append(input);
    }
    form.action = config.bulkUrl;
    document.getElementById('delete-report-name').textContent = `${selected.length} laporan yang dipilih beserta lampirannya`;
    const modal = document.getElementById('modal-delete-report');
    modal.classList.remove('hidden'); modal.classList.add('flex');
}

// Open Detail Modal Handler
function openDetailReportModal(item) {
    document.getElementById('detail-report-code').innerText = `#REP-${String(item.id).padStart(4, '0')}`;
    document.getElementById('detail-site-machine').innerText = item.site_machine || '-';
    document.getElementById('detail-series-machine').innerText = item.series_machine || 'N/A';
    document.getElementById('detail-attendant').innerText = item.attendant || '-';
    document.getElementById('detail-failure-date').innerText = item.failure_date ? new Date(item.failure_date)
        .toLocaleDateString('id-ID', {
            day: '2-digit',
            month: 'short',
            year: 'numeric'
        }) : '-';

    document.getElementById('detail-subsystem').innerText = item.failed_subsystem ||
        'Komponen yang rusak belum diisi.';
    document.getElementById('detail-phenomenon').innerText = item.failure_phenomenon ||
        'Gejala kerusakan belum diisi.';
    document.getElementById('detail-procedure').innerText = item.ts_procedure ||
        'Langkah penanganan belum diisi.';

    // Handle Image Evidence
    const imgWrapper = document.getElementById('detail-image-wrapper');
    const imgEl = document.getElementById('detail-image');
    if (item.image) {
        imgEl.src = `/storage/${item.image}`;
        imgWrapper.classList.remove('hidden');
    } else {
        imgWrapper.classList.add('hidden');
        imgEl.src = '';
    }

    const m = document.getElementById('modal-detail-report');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeDetailReportModal() {
    const m = document.getElementById('modal-detail-report');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function openDeleteReportModal(url, name) {
    document.getElementById('form-delete-report').querySelectorAll('[data-bulk-id]').forEach(input => input.remove());
    const m = document.getElementById('modal-delete-report');
    document.getElementById('form-delete-report').action = url;
    document.getElementById('delete-report-name').innerText = name;
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeDeleteReportModal() {
    const m = document.getElementById('modal-delete-report');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

const searchInput = document.getElementById('search');
const searchLoader = document.getElementById('search-loader');
const tableBody = document.getElementById('table-body');
let searchTimer;
function fetchReportRows(targetUrl = null, historyMode = targetUrl ? 'push' : 'replace') {
    clearTimeout(searchTimer);
    const url = new URL(targetUrl || config.indexUrl, location.origin);
    if (searchInput?.value.trim()) url.searchParams.set('search', searchInput.value.trim());
    else url.searchParams.delete('search');
    tableBody?.setAttribute('aria-busy', 'true');
    if (tableBody) tableBody.inert = true;
    searchLoader?.classList.remove('hidden');
    return loadList(url.href, {
        json: true,
        onSuccess: data => {
            if (tableBody) tableBody.innerHTML = data.html;
            const pagination = document.getElementById('report-pagination');
            if (pagination) pagination.innerHTML = data.pagination;
            const total = document.getElementById('report-total');
            if (total) total.textContent = data.total;
            const master = document.getElementById('select_all_id');
            if (master) master.checked = false;
            if (historyMode !== 'none') history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url.href);
            window.announceUi?.('Daftar laporan diperbarui.');
        },
        onFinally: () => { if (tableBody) tableBody.inert = false; searchLoader?.classList.add('hidden'); tableBody?.removeAttribute('aria-busy'); },
    });
}
searchInput?.addEventListener('input', () => { clearTimeout(searchTimer); searchTimer = setTimeout(() => fetchReportRows(), 350); });
document.getElementById('report-pagination')?.addEventListener('click', event => {
    const link = event.target.closest('a[href]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault(); fetchReportRows(link.href);
});

Object.assign(window, { fetchReportRows, confirmDeleteSelected, openDetailReportModal, closeDetailReportModal, openDeleteReportModal, closeDeleteReportModal });

window.addEventListener('popstate', () => {
    if (searchInput) searchInput.value = new URL(location.href).searchParams.get('search') || '';
    fetchReportRows(location.href, 'none');
});
