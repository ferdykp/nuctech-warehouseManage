import { createLatestRequest } from '../ui/requests.js';
const loadList = createLatestRequest();
const config = JSON.parse(document.getElementById('spareparts-config').textContent);

// Import Excel Drag & Drop and File Selection Handler
function handleImportFileSelect(input) {
    const btnSubmit = document.getElementById('btn-submit-import');
    const dropzoneText = document.getElementById('dropzone-text');
    const dropzoneIcon = document.getElementById('dropzone-icon');
    const dropzone = document.getElementById('import-dropzone');

    if (input.files && input.files[0]) {
        const file = input.files[0];
        dropzoneText.innerText = "Dipilih: " + file.name;
        dropzoneIcon.className = "mb-2 text-3xl text-emerald-500 fa-solid fa-file-excel";
        dropzone.classList.add('border-emerald-500', 'bg-emerald-50/50');
        btnSubmit.classList.remove('hidden');
    } else {
        resetImportDropzone();
    }
}

function resetImportDropzone() {
    const fileInput = document.getElementById('import-file-input');
    const btnSubmit = document.getElementById('btn-submit-import');
    const dropzoneText = document.getElementById('dropzone-text');
    const dropzoneIcon = document.getElementById('dropzone-icon');
    const dropzone = document.getElementById('import-dropzone');

    if (fileInput) fileInput.value = '';
    if (dropzoneText) dropzoneText.innerText = "Klik atau tarik file Excel ke sini";
    if (dropzoneIcon) dropzoneIcon.className = "mb-2 text-3xl text-slate-400 fa-solid fa-cloud-arrow-up";
    if (dropzone) {
        dropzone.classList.remove('border-emerald-500', 'bg-emerald-50/50');
    }
    if (btnSubmit) btnSubmit.classList.add('hidden');
}

// Setup Drag & Drop Events for Import
document.addEventListener('DOMContentLoaded', function() {
    const dropzone = document.getElementById('import-dropzone');
    const fileInput = document.getElementById('import-file-input');

    if (dropzone && fileInput) {
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.add('border-blue-500', 'bg-blue-50/50');
            }, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            dropzone.addEventListener(eventName, () => {
                dropzone.classList.remove('border-blue-500', 'bg-blue-50/50');
            }, false);
        });

        dropzone.addEventListener('drop', (e) => {
            const dt = e.dataTransfer;
            const files = dt.files;
            if (files.length > 0) {
                fileInput.files = files;
                handleImportFileSelect(fileInput);
            }
        }, false);
    }
});

function openImportModal() {
    resetImportDropzone();
    const modal = document.getElementById('modal-import');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeImportModal() {
    const modal = document.getElementById('modal-import');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    resetImportDropzone();
}

// Modal Handlers
function openEditModal(btn) {
    const item = JSON.parse(btn.getAttribute('data-item'));
    const modal = document.getElementById('modal-edit');
    const form = document.getElementById('form-edit');

    // Jika data item berasal dari SparepartStock, stok ada di item.qty
    const stockId = item.id;
    const sparepartId = item.sparepart_id ? item.sparepart_id : item.id;
    const itemName = item.sparepart ? item.sparepart.item_name : item.item_name;
    const categoryId = item.sparepart ? item.sparepart.category_id : item.category_id;
    const serialNumber = item.sparepart ? item.sparepart.serial_number : item.serial_number;
    const type = item.sparepart ? item.sparepart.type : item.type;
    const uom = item.sparepart ? item.sparepart.uom : item.uom;
    const note = item.sparepart ? item.sparepart.note : item.note;
    const currentQty = item.qty !== undefined ? item.qty : 0;

    form.action = `/sparepart/${config.slug}/` + stockId; // Kirim ID Stock / Sparepart

    document.getElementById('edit_item_name').value = itemName || '';
    document.getElementById('edit_category_id').value = categoryId || '';
    document.getElementById('edit_serial_number').value = serialNumber || '';
    document.getElementById('edit_type').value = type || '';
    document.getElementById('edit_uom').value = uom || 'PCS';
    document.getElementById('edit_note').value = note || '';

    // 🟢 SET VALUE QUANTITY
    if (document.getElementById('edit_qty')) {
        document.getElementById('edit_qty').value = currentQty;
    }

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeEditModal() {
    const modal = document.getElementById('modal-edit');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

function previewEditImage(input) {
    const previewContainer = document.getElementById('edit-preview-container');
    const previewImage = document.getElementById('edit-image-preview');
    const uploadLabel = document.getElementById('edit-upload-label');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('hidden');
            uploadLabel.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function resetEditImage() {
    const input = document.getElementById('edit-image-input');
    const previewContainer = document.getElementById('edit-preview-container');
    const uploadLabel = document.getElementById('edit-upload-label');
    const previewImage = document.getElementById('edit-image-preview');
    input.value = "";
    previewImage.src = "#";
    previewContainer.classList.add('hidden');
    uploadLabel.classList.remove('hidden');
}

function previewImage(input) {
    const previewContainer = document.getElementById('preview-container');
    const previewImage = document.getElementById('image-preview');
    const uploadLabel = document.getElementById('upload-label');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImage.src = e.target.result;
            previewContainer.classList.remove('hidden');
            uploadLabel.classList.add('hidden');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

function resetImage() {
    const input = document.getElementById('image-input');
    const previewContainer = document.getElementById('preview-container');
    const uploadLabel = document.getElementById('upload-label');
    const previewImage = document.getElementById('image-preview');
    if (input) input.value = "";
    if (previewImage) previewImage.src = "#";
    if (previewContainer) previewContainer.classList.add('hidden');
    if (uploadLabel) uploadLabel.classList.remove('hidden');
}

function openCreateModal() {
    const m = document.getElementById('modal-create');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeCreateModal() {
    const m = document.getElementById('modal-create');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
    resetImage();
}

let maxAvailableStock = 0;
let currentActiveCondition = '';

function openAdjustModal(id, name, qty, condition, uom = 'Unit') {
    const modal = document.getElementById('modal-adjust');
    const form = document.getElementById('form-adjust');
    const slug = `${config.slug}`;

    maxAvailableStock = parseInt(qty);
    currentActiveCondition = condition;

    form.action = "/inventory/" + slug + "/adjust/" + id;

    document.getElementById('adjust-item-name').innerText = name;
    document.getElementById('current-stock-display').innerText = qty + " " + uom;
    document.getElementById('adjust-uom-badge').innerText = uom;
    document.getElementById('display-current-condition').innerText = window.uiLabel(condition);
    document.getElementById('input-current-condition').value = condition;

    const qtyInput = document.getElementById('input-qty-adjust');
    qtyInput.value = qty;
    qtyInput.max = qty;

    document.getElementById('select-condition-adjust').value = condition;

    calculateLiveHint();
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeAdjustModal() {
    const modal = document.getElementById('modal-adjust');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

const qtyInput = document.getElementById('input-qty-adjust');
const conditionSelect = document.getElementById('select-condition-adjust');
const hintBox = document.getElementById('adjustment-hint-box');
const hintText = document.getElementById('adjustment-hint-text');
const btnSubmit = document.getElementById('btn-submit-adjust');

function calculateLiveHint() {
    if (!qtyInput) return;
    const enteredQty = parseInt(qtyInput.value) || 0;
    const selectedCondition = conditionSelect.value;

    if (enteredQty <= 0) {
        hintBox.className =
            "flex gap-2.5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800";
        hintText.innerHTML = "Jumlah penyesuaian minimal 1 unit.";
        btnSubmit.disabled = true;
        return;
    }

    if (enteredQty > maxAvailableStock) {
        hintBox.className =
            "flex gap-2.5 p-3.5 bg-rose-50 border border-rose-200 rounded-2xl text-xs text-rose-800";
        hintText.innerHTML = "Jumlah melebihi stok tersedia (maksimal: " + maxAvailableStock +
            " unit).";
        btnSubmit.disabled = true;
        return;
    }

    btnSubmit.disabled = false;

    if (currentActiveCondition === selectedCondition) {
        hintBox.className =
            "flex gap-2.5 p-3.5 bg-blue-50 border border-blue-200 rounded-2xl text-xs text-blue-800";
        hintText.innerHTML =
            "<b>Koreksi Jumlah Stok:</b> Jumlah stok pada kondisi ini akan menjadi <b>" + enteredQty +
            " unit</b>.";
    } else if (enteredQty === maxAvailableStock) {
        hintBox.className =
            "flex gap-2.5 p-3.5 bg-emerald-50 border border-emerald-200 rounded-2xl text-xs text-emerald-800";
        hintText.innerHTML = "<b>Perubahan Seluruh Stok:</b> Seluruh stok (" + enteredQty +
            " unit) akan dipindahkan seluruhnya ke kondisi <b>" + window.uiLabel(selectedCondition) + "</b>.";
    } else {
        hintBox.className =
            "flex gap-2.5 p-3.5 bg-amber-50 border border-amber-200 rounded-2xl text-xs text-amber-800";
        hintText.innerHTML = "<b>Perubahan Sebagian Stok:</b> <b>" + enteredQty +
            " unit</b> akan dipindahkan ke kondisi <b>" + window.uiLabel(selectedCondition) + "</b>, menyisakan <b>" + (
                maxAvailableStock - enteredQty) + " unit</b> pada kondisi sebelumnya.";
    }
}

if (qtyInput && conditionSelect) {
    qtyInput.addEventListener('input', calculateLiveHint);
    conditionSelect.addEventListener('change', calculateLiveHint);
}

function openDetailModal(item, sites) {
    document.getElementById('d_item_name').innerText = item.item_name;
    document.getElementById('d_type').innerText = "Jenis: " + (item.type || '-');
    document.getElementById('d_serial_number').innerText = "SN: " + (item.serial_number || '-');
    document.getElementById('d_source_data').innerText = "Asal: " + (item.source_data || 'Isi Manual');

    const imgElement = document.getElementById('d_image');
    const imgContainer = document.getElementById('d_image_container');
    const placeholder = document.getElementById('no-image-placeholder');

    if (item.image) {
        imgElement.src = `/storage/${item.image}`;
        imgContainer.classList.remove('hidden');
        imgElement.classList.remove('hidden');
        placeholder.classList.add('hidden');
        placeholder.classList.remove('flex');
    } else {
        imgContainer.classList.add('hidden');
        placeholder.classList.remove('hidden');
        placeholder.classList.add('flex');
    }

    const stockTable = document.getElementById('d_stock_table');
    stockTable.innerHTML = '';
    if (item.stocks && item.stocks.length > 0) {
        item.stocks.forEach(s => {
            stockTable.innerHTML += `
            <tr class="transition-colors hover:bg-slate-50">
                <td class="p-3 font-bold text-slate-800">${s.site?.machine_name || 'N/A'}</td>
                <td class="p-3 font-extrabold text-center text-blue-600">${s.qty}</td>
                <td class="p-3 text-center"><span class="text-xs px-2 py-0.5 rounded-md bg-slate-100 font-bold uppercase text-slate-700">${window.uiLabel(s.condition)}</span></td>
            </tr>`;
        });
    } else {
        stockTable.innerHTML =
            '<tr><td colspan="3" class="p-4 italic text-center text-slate-400">Belum ada stok aktif</td></tr>';
    }

    const historyContainer = document.getElementById('d_history');
    historyContainer.innerHTML = (item.histories && item.histories.length > 0) ? '' :
        '<p class="text-xs italic text-slate-400">Belum ada riwayat perpindahan stok.</p>';
    if (item.histories) {
        item.histories.forEach(h => {
            const date = new Date(h.created_at).toLocaleString('id-ID', {
                day: 'numeric',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
            historyContainer.innerHTML += `
            <div class="relative">
                <div class="absolute -left-[26px] mt-1.5 w-3.5 h-3.5 rounded-full bg-amber-500 border-2 border-white"></div>
                <p class="text-xs font-bold text-slate-400 uppercase">${date}</p>
                <p class="text-xs font-bold text-slate-900 mt-0.5">${window.uiLabel(h.action)}</p>
                <p class="text-xs font-medium text-slate-600">${h.from_site?.machine_name || 'Awal'} &rarr; ${h.to_site?.machine_name || 'Tidak Diketahui'}</p>
                <p class="text-xs italic font-semibold text-slate-400 mt-0.5">Jumlah: ${h.qty} | Kondisi: ${window.uiLabel(h.condition)}</p>
            </div>`;
        });
    }

    const modal = document.getElementById('detailModal');
    const wrapper = document.getElementById('modalWrapper');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => {
        wrapper.classList.replace('scale-95', 'scale-100');
        wrapper.classList.replace('opacity-0', 'opacity-100');
    }, 10);
}

function closeDetailModal() {
    const modal = document.getElementById('detailModal');
    const wrapper = document.getElementById('modalWrapper');
    wrapper.classList.replace('scale-100', 'scale-95');
    wrapper.classList.replace('opacity-100', 'opacity-0');
    document.body.classList.remove('overflow-hidden');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
}

function expandImage() {
    const imgElement = document.getElementById('d_image');
    if (imgElement.classList.contains('hidden')) return;

    const viewer = document.getElementById('image-viewer');
    const fullImg = document.getElementById('full-image');

    fullImg.src = imgElement.src;
    viewer.classList.remove('hidden');
    viewer.classList.add('flex');

    setTimeout(() => {
        fullImg.classList.replace('scale-95', 'scale-100');
    }, 10);
}

function closeImageViewer() {
    const viewer = document.getElementById('image-viewer');
    const fullImg = document.getElementById('full-image');
    fullImg.classList.replace('scale-100', 'scale-95');
    setTimeout(() => {
        viewer.classList.add('hidden');
        viewer.classList.remove('flex');
    }, 200);
}

function openDeleteModal(url, itemName) {
    document.getElementById('form-confirm-delete').action = url;
    document.getElementById('delete-item-name').innerText = itemName;
    const modal = document.getElementById('modal-delete');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
    setTimeout(() => {
        modal.querySelector('.modal-content').classList.replace('scale-95', 'scale-100');
        modal.querySelector('.modal-content').classList.replace('opacity-0', 'opacity-100');
    }, 10);
}

function closeDeleteModal() {
    const modal = document.getElementById('modal-delete');
    modal.querySelector('.modal-content').classList.replace('scale-100', 'scale-95');
    modal.querySelector('.modal-content').classList.replace('opacity-100', 'opacity-0');
    document.body.classList.remove('overflow-hidden');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 200);
}

function openMoveModal(stockId, itemName, currentQty, currentCondition) {
    const modal = document.getElementById('modal-move');
    document.getElementById('move-asset-tag').innerText = itemName + " (" + window.uiLabel(currentCondition) + ")";
    document.getElementById('form-move').action = "/movement/request/" + stockId;
    document.getElementById('target-condition').value = currentCondition;

    const qtyInput = document.getElementById('move-quantity');
    qtyInput.max = currentQty;
    qtyInput.value = 1;
    document.getElementById('max-info').innerText = "* Stok tersedia: " + currentQty + " pcs";

    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.classList.add('overflow-hidden');
}

function closeMoveModal() {
    const modal = document.getElementById('modal-move');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
    document.body.classList.remove('overflow-hidden');
}

window.onclick = function(event) {
    if (event.target.id === 'detailModal') closeDetailModal();
    if (event.target.id === 'modal-delete') closeDeleteModal();
    if (event.target.id === 'modal-move') closeMoveModal();
    if (event.target.id === 'image-viewer') closeImageViewer();
}

// Live Search & Filter AJAX
const searchInput = document.getElementById('search');
const conditionFilter = document.getElementById('filter-condition');
const tableContainer = document.getElementById('table-container');
let delayTimer;

function fetchFilteredSpareparts(targetUrl = null, historyMode = targetUrl ? 'push' : 'replace') {
    clearTimeout(delayTimer);
    const query = searchInput ? searchInput.value.trim() : '';
    const condition = conditionFilter ? conditionFilter.value : '';

    tableContainer.classList.add('opacity-50');

    let url = targetUrl ? new URL(targetUrl, window.location.origin) : new URL(
        config.indexUrl, window.location.origin);

    if (query) url.searchParams.set('search', query);
    else url.searchParams.delete('search');

    if (condition) url.searchParams.set('condition', condition);
    else url.searchParams.delete('condition');

    tableContainer.setAttribute('aria-busy', 'true');
    tableContainer.inert = true;
    return loadList(url.href, {
        onSuccess: html => { tableContainer.innerHTML = html; if (historyMode !== 'none') history[historyMode === 'push' ? 'pushState' : 'replaceState']({}, '', url.href); },
        onFinally: () => { tableContainer.inert = false; tableContainer.classList.remove('opacity-50'); tableContainer.removeAttribute('aria-busy'); },
    });
}

if (searchInput) {
    searchInput.addEventListener('input', function() {
        clearTimeout(delayTimer);
        delayTimer = setTimeout(fetchFilteredSpareparts, 350);
    });
}

if (conditionFilter) {
    conditionFilter.addEventListener('change', function() {
        fetchFilteredSpareparts();
    });
}

Object.assign(window, { handleImportFileSelect, resetImportDropzone, openImportModal, closeImportModal, openEditModal, closeEditModal, previewEditImage, resetEditImage, previewImage, resetImage, openCreateModal, closeCreateModal, openAdjustModal, closeAdjustModal, calculateLiveHint, openDetailModal, closeDetailModal, expandImage, closeImageViewer, openDeleteModal, closeDeleteModal, openMoveModal, closeMoveModal, fetchFilteredSpareparts });

document.getElementById('table-container')?.addEventListener('click', event => {
    const link = event.target.closest('nav a[href]');
    if (!link || event.ctrlKey || event.metaKey || event.shiftKey) return;
    event.preventDefault(); fetchFilteredSpareparts(link.href);
});

window.addEventListener('popstate', () => {
    const params = new URL(location.href).searchParams;
    if (searchInput) searchInput.value = params.get('search') || '';
    if (conditionFilter) conditionFilter.value = params.get('condition') || '';
    fetchFilteredSpareparts(location.href, 'none');
});
