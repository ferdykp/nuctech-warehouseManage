const config = JSON.parse(document.getElementById('reimbursements-config').textContent);

function openImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        document.body.classList.add('overflow-hidden');
    }
}

function closeImportModal() {
    const modal = document.getElementById('importModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.classList.remove('overflow-hidden');
    }
}

// Fungsi JavaScript untuk Bulk Delete
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.rowCheckbox');
    checkboxes.forEach(cb => {
        cb.checked = master.checked;
    });
    updateBulkDeleteButton();
}

function updateBulkDeleteButton() {
    const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
    const bulkBtn = document.getElementById('bulkDeleteBtn');
    const countSpan = document.getElementById('selectedCount');

    if (checkboxes.length > 0) {
        bulkBtn.style.display = 'inline-flex';
        countSpan.innerText = checkboxes.length;
    } else {
        bulkBtn.style.display = 'none';
    }
}

function submitBulkDelete() {
    if (!confirm('Yakin ingin menghapus klaim reimbursement yang dipilih ke Sampah?')) {
        return;
    }

    const checkboxes = document.querySelectorAll('.rowCheckbox:checked');
    const container = document.getElementById('bulkDeleteInputs');
    container.innerHTML = '';

    checkboxes.forEach(cb => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = cb.value;
        container.appendChild(input);
    });

    document.getElementById('bulkDeleteForm').requestSubmit();
}

let debounceTimer;

let activeSearch;
let searchSequence = 0;

async function fetchReimbursementData(targetUrl = null, historyMode = 'replace') {
    clearTimeout(debounceTimer);
    activeSearch?.abort();
    const controller = new AbortController();
    activeSearch = controller;
    const sequence = ++searchSequence;
    const wrapper = document.getElementById('reimbursementDataWrapper');
    const searchInput = document.getElementById('reimburseSearchInput');
    const monthSelect = document.getElementById('reimburseMonthSelect');
    const statusSelect = document.getElementById('reimburseStatusSelect');
    const url = new URL(targetUrl || config.indexUrl, location.origin);
    if (historyMode !== 'none') {
        for (const [key, value] of [['search', searchInput?.value.trim()], ['month', monthSelect?.value], ['status', statusSelect?.value]]) {
            if (value) url.searchParams.set(key, value); else url.searchParams.delete(key);
        }
    } else {
        if (searchInput) searchInput.value = url.searchParams.get('search') || '';
        if (monthSelect) monthSelect.value = url.searchParams.get('month') || '';
        if (statusSelect) statusSelect.value = url.searchParams.get('status') || '';
    }
    if (wrapper) wrapper.setAttribute('aria-busy', 'true');
    window.announceUi?.('Memuat daftar klaim…');
    try {
        const response = await fetch(url, {
            signal: controller.signal, headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok || response.redirected) throw new Error('Daftar tidak dapat dimuat.');
        const html = await response.text();
        if (sequence !== searchSequence) return;
        const doc = new DOMParser().parseFromString(html, 'text/html');
        const content = doc.getElementById('reimbursementDataWrapper');
        if (!content || !wrapper) throw new Error('Daftar tidak tersedia.');
        wrapper.innerHTML = content.innerHTML;
        for (const id of ['totalApprovedAmountText', 'reimburseCountText', 'reimbursePeriodText']) {
            const next = doc.getElementById(id), current = document.getElementById(id);
            if (next && current) current.textContent = next.textContent;
        }
        document.getElementById('clearSearchBtn')?.classList.toggle('hidden', !url.searchParams.get('search'));
        const pdfLink = document.getElementById('pdfExportLink');
        if (pdfLink) {
            const pdfUrl = new URL(config.pdfUrl, location.origin);
            if (url.searchParams.get('month')) pdfUrl.searchParams.set('month', url.searchParams.get('month'));
            pdfLink.href = pdfUrl.href;
        }
        if (historyMode !== 'none') history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url);
        bindPaginationEvents();
        updateBulkDeleteButton();
        window.announceUi?.('Daftar klaim diperbarui.');
    } catch (error) {
        if (error.name === 'AbortError' || sequence !== searchSequence) return;
        window.showUiMessage?.('Daftar klaim gagal dimuat. Periksa koneksi lalu ubah filter atau coba lagi.');
    } finally {
        if (sequence === searchSequence) wrapper?.removeAttribute('aria-busy');
    }
}

function initReimbursementFilters() {
    const searchInput = document.getElementById('reimburseSearchInput');
    const monthSelect = document.getElementById('reimburseMonthSelect');
    const statusSelect = document.getElementById('reimburseStatusSelect');

    if (searchInput) {
        searchInput.removeEventListener('input', handleSearchInput);
        searchInput.addEventListener('input', handleSearchInput);
    }

    if (monthSelect) {
        monthSelect.removeEventListener('change', handleMonthChange);
        monthSelect.addEventListener('change', handleMonthChange);
    }

    statusSelect?.addEventListener('change', handleMonthChange);
    bindPaginationEvents();
}

function handleSearchInput() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(() => {
        fetchReimbursementData();
    }, 350);
}

function handleMonthChange() {
    fetchReimbursementData();
}

function clearSearch() {
    const input = document.getElementById('reimburseSearchInput');
    if (input) {
        input.value = '';
        fetchReimbursementData();
    }
}

function bindPaginationEvents() {
    const paginationLinks = document.querySelectorAll('.ajax-pagination a');
    paginationLinks.forEach(link => {
        if (link.dataset.uiPaginationBound) return;
        link.dataset.uiPaginationBound = 'true';
        link.addEventListener('click', function(e) {
            e.preventDefault();
            fetchReimbursementData(this.href, 'push');
        });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    initReimbursementFilters();
    window.addEventListener('popstate', () => fetchReimbursementData(location.href, 'none'));

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeDetailModal();
            closeCancelModal();
        }
    });
});

function exportExcelReport() {
    const url = new URL(config.excelUrl, window.location.origin);
    const urlParams = new URLSearchParams(window.location.search);
    const currentMonth = urlParams.get('month');
    const currentSearch = urlParams.get('search');
    const currentStatus = urlParams.get('status');

    if (currentMonth) url.searchParams.set('month', currentMonth);
    if (currentSearch) url.searchParams.set('search', currentSearch);
    if (currentStatus) url.searchParams.set('status', currentStatus);

    if (config.allSites) url.searchParams.set('all_site', '1');

    window.location.href = url.href;
}

function openDetailModal(buttonElement) {
    const rawData = buttonElement.getAttribute('data-reimbursement');
    let data;
    try {
        data = typeof rawData === 'string' ? JSON.parse(rawData) : rawData;
    } catch (e) {
        console.error("Failed to parse reimbursement json:", e);
        (window.showUiMessage || window.alert)("Rincian gagal dimuat. Silakan coba lagi.");
        return;
    }

    document.getElementById('modal-name').innerText = data.person_name || '-';
    document.getElementById('modal-category').innerText = window.uiLabel(data.category || 'Klaim');
    document.getElementById('modal-comment').innerText = data.comment ? `"${data.comment}"` :
        "Belum ada keterangan.";

    const dateObj = new Date(data.date);
    document.getElementById('modal-date').innerText = isNaN(dateObj) ? '-' : dateObj.toLocaleDateString('id-ID', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });

    document.getElementById('modal-amount').innerText = new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0
    }).format(data.amount || 0);

    const escapeText = (value) => {
        const node = document.createElement('span');
        node.textContent = String(value ?? '-');
        return node.innerHTML;
    };

    if (data.category === 'transportation' || data.category === 'delivery') {
        document.getElementById('modal-route').innerHTML =
            `<span class="font-semibold text-slate-900">${escapeText(data.from_location || '-')}</span> <i class="mx-1 fa-solid fa-arrow-right text-slate-300"></i> <span class="font-semibold text-slate-900">${escapeText(data.to_location || '-')}</span>`;
    } else {
        document.getElementById('modal-route').innerText = "Rute Tidak Diperlukan";
    }

    let signatures = [];
    if (data.signatures_json) {
        try {
            signatures = typeof data.signatures_json === 'string' ? JSON.parse(data.signatures_json) : data
                .signatures_json;
            if (!Array.isArray(signatures)) signatures = [];
        } catch (e) {
            signatures = [];
        }
    }

    function renderSignBadge(elementId, isSigned, fallbackText = "Belum Ditandatangani") {
        const el = document.getElementById(elementId);
        if (!el) return;
        if (isSigned) {
            el.innerText = "✓ Ditandatangani";
            el.className =
                "px-2 py-0.5 rounded text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200";
        } else {
            el.innerText = fallbackText;
            el.className =
                "px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 text-slate-400 border border-slate-200";
        }
    }

    const hasStaff = signatures.some(s => s && (s.role === 'employee_role' || s.level === 'employee_role')) || !!
        data.person_name;
    const hasLeader = signatures.some(s => s && (s.role === 'leader' || s.level === 'leader')) || (data.status !==
        'pending' && data.status !== 'pending_leader');
    const hasStation = signatures.some(s => s && (s.role === 'station_master' || s.role === 'station')) || (data
        .status === 'approved' || data.status === 'pending_manager');
    const hasManager = signatures.some(s => s && s.role === 'manager') || data.status === 'approved';

    renderSignBadge('sign-status-staff', hasStaff, "Menunggu Tanda Tangan");
    renderSignBadge('sign-status-leader', hasLeader, "Menunggu Pemeriksaan");
    renderSignBadge('sign-status-station', hasStation, "Menunggu Persetujuan");
    renderSignBadge('sign-status-manager', hasManager, "Menunggu Persetujuan Akhir");

    if (data.status === 'rejected') {
        if (!hasLeader) renderSignBadge('sign-status-leader', false, "✖ Ditolak");
        else if (!hasStation) renderSignBadge('sign-status-station', false, "✖ Ditolak");
        else if (!hasManager) renderSignBadge('sign-status-manager', false, "✖ Ditolak");
    }

    const frame = document.getElementById('modal-attachment-frame');
    frame.innerHTML = '';

    if (data.receipt_attachment) {
        const fileExt = data.receipt_attachment.split('.').pop().toLowerCase();
        const fullUrl = `/reimbursements/${encodeURIComponent(data.id)}/receipt`;

        if (fileExt === 'pdf') {
            frame.innerHTML =
                `<object data="${fullUrl}#toolbar=0" type="application/pdf" title="Pratinjau bukti nota PDF" class="block w-full h-full min-h-[280px]"></object>`;
        } else {
            frame.innerHTML =
                `<div class="flex items-center justify-center w-full h-full p-2 bg-slate-50"><img src="${fullUrl}" alt="Bukti nota klaim" class="object-contain max-w-full max-h-full rounded-lg" /></div>`;
        }
    } else {
        frame.innerHTML =
            `<div class="flex items-center justify-center w-full h-full p-8 text-xs italic text-slate-400">Belum ada bukti nota yang dilampirkan.</div>`;
    }

    const m = document.getElementById('detailModal');
    if (m) {
        m.classList.remove('hidden');
        m.classList.add('flex');
    }
    document.body.classList.add('overflow-hidden');
}

function closeDetailModal() {
    const m = document.getElementById('detailModal');
    if (m) {
        m.classList.add('hidden');
        m.classList.remove('flex');
    }
    document.body.classList.remove('overflow-hidden');
}

function confirmCancel(id, personName = '', amount = '') {
    const modal = document.getElementById('cancelModal');
    if (!modal) return;

    const form = modal.querySelector('form');
    if (form) form.action = `/reimbursements/${id}`;

    const nameEl = document.getElementById('cancel_person_name');
    const amountEl = document.getElementById('cancel_amount');
    if (nameEl) nameEl.innerText = personName;
    if (amountEl) amountEl.innerText = amount;

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeCancelModal() {
    const modal = document.getElementById('cancelModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }
    document.body.classList.remove('overflow-hidden');
}

Object.assign(window, { openImportModal, closeImportModal, toggleSelectAll, updateBulkDeleteButton, submitBulkDelete, fetchReimbursementData, initReimbursementFilters, handleSearchInput, handleMonthChange, clearSearch, bindPaginationEvents, exportExcelReport, openDetailModal, closeDetailModal, confirmCancel, closeCancelModal });
