import { pdfjsLib, pdfOptions } from '../pdf.js';

const fileInput = document.getElementById('fileInput');
const dropzone = document.getElementById('dropzone');
const previewContainer = document.getElementById('filePreviewContainer');
const placeholder = document.getElementById('emptyPreviewPlaceholder');
const pageCountBadge = document.getElementById('pageCountBadge');
const renderingLoader = document.getElementById('rendering-loader');
const excludedInput = document.getElementById('excludedPagesInput');
let excludedPages = [];

if (fileInput && dropzone) {
    ['dragenter', 'dragover'].forEach(eventName => {
        fileInput.addEventListener(eventName, () => {
            dropzone.classList.add('border-amber-500', 'bg-amber-50/20');
        }, false);
    });
    ['dragleave', 'drop'].forEach(eventName => {
        fileInput.addEventListener(eventName, () => {
            dropzone.classList.remove('border-amber-500', 'bg-amber-50/20');
        }, false);
    });

    fileInput.addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;

        previewContainer.innerHTML = '';
        previewContainer.classList.add('hidden');
        placeholder.classList.add('hidden');
        pageCountBadge.classList.add('hidden');
        excludedPages = [];
        excludedInput.value = JSON.stringify(excludedPages);

        if (file.type === 'application/pdf') {
            renderingLoader.classList.replace('hidden', 'flex');
            renderPdfPreview(file);
        } else if (file.type.startsWith('image/')) {
            renderImagePreview(file);
        }
    });
}

function renderImagePreview(file) {
    const reader = new FileReader();
    reader.onload = function(e) {
        renderingLoader.classList.add('hidden');
        previewContainer.classList.replace('hidden', 'grid');
        pageCountBadge.classList.replace('hidden', 'inline-flex');
        pageCountBadge.innerHTML = `<i class="fa-solid fa-image mr-1 mt-0.5"></i> File Gambar`;

        previewContainer.innerHTML = `
            <div class="relative p-2 overflow-hidden bg-white border md:col-span-2 border-slate-200 rounded-xl shadow-2xs">
                <img src="${e.target.result}" class="w-full h-auto max-h-[400px] object-contain rounded-lg bg-slate-50">
                <div class="absolute top-3 left-3 bg-slate-900 text-white text-[8px] px-2 py-0.5 rounded-md font-bold uppercase">Satu Gambar</div>
            </div>
        `;
    }
    reader.readAsDataURL(file);
}

async function renderPdfPreview(file) {
    try {
        const arrayBuffer = await file.arrayBuffer();
        const pdf = await pdfjsLib.getDocument({
            data: arrayBuffer, ...pdfOptions
        }).promise;

        previewContainer.classList.replace('hidden', 'grid');
        pageCountBadge.classList.replace('hidden', 'inline-flex');
        pageCountBadge.innerHTML = `<i class="fa-solid fa-file-pdf mr-1 mt-0.5"></i> ${pdf.numPages} Halaman`;

        for (let i = 1; i <= pdf.numPages; i++) {
            const page = await pdf.getPage(i);
            const unscaledViewport = page.getViewport({
                scale: 1
            });
            const scale = 250 / unscaledViewport.width;
            const viewport = page.getViewport({
                scale: scale
            });

            const canvas = document.createElement('canvas');
            const context = canvas.getContext('2d');
            canvas.height = viewport.height;
            canvas.width = viewport.width;
            canvas.className = "w-full h-auto rounded-lg";

            await page.render({
                canvasContext: context,
                viewport: viewport
            }).promise;

            const wrapper = document.createElement('div');
            wrapper.className =
                "relative border border-slate-200 rounded-xl p-2 bg-white shadow-2xs transition-all";
            wrapper.id = `page-wrapper-${i}`;
            wrapper.innerHTML = `
                <div class="absolute top-3 left-3 z-10 bg-slate-900 text-white text-[8px] px-2 py-0.5 rounded-md font-bold uppercase">Halaman ${i}</div>
                <button type="button" onclick="togglePage(${i})" class="absolute z-10 flex items-center justify-center w-6 h-6 transition-colors bg-white border rounded-full cursor-pointer border-slate-200 shadow-2xs text-slate-400 top-3 right-3 hover:bg-rose-50 hover:text-rose-600 hover:border-rose-200" title="Kecualikan Halaman">
                    <i class="fa-solid fa-trash-can text-xs"></i>
                </button>
                <div class="overflow-hidden rounded-lg bg-slate-50">
                    <img src="${canvas.toDataURL()}" class="object-contain w-full h-auto">
                </div>
                <div id="overlay-${i}" class="absolute inset-0 bg-white/95 hidden flex-col gap-1.5 items-center justify-center rounded-xl z-20">
                    <div class="flex items-center justify-center w-6 h-6 border rounded-full bg-rose-50 border-rose-200 text-rose-500"><i class="fa-solid fa-eye-slash text-xs"></i></div>
                    <span class="text-rose-600 font-bold text-xs uppercase">Halaman Dikecualikan</span>
                    <button type="button" onclick="togglePage(${i})" class="mt-1 bg-slate-900 hover:bg-slate-800 text-white text-[8px] px-2 py-1 rounded-md font-bold uppercase cursor-pointer">Restore Page</button>
                </div>
            `;
            previewContainer.appendChild(wrapper);
        }
    } catch (err) {
        console.error("PDF Parsing Exception:", err);
    } finally {
        renderingLoader.classList.replace('flex', 'hidden');
    }
}

function togglePage(pageNum) {
    const overlay = document.getElementById(`overlay-${pageNum}`);
    const wrapper = document.getElementById(`page-wrapper-${pageNum}`);
    const idx = excludedPages.indexOf(pageNum);

    if (idx === -1) {
        excludedPages.push(pageNum);
        overlay?.classList.replace('hidden', 'flex');
        wrapper?.classList.add('border-rose-200', 'bg-rose-50/20');
    } else {
        excludedPages.splice(idx, 1);
        overlay?.classList.replace('flex', 'hidden');
        wrapper?.classList.remove('border-rose-200', 'bg-rose-50/20');
    }
    excludedInput.value = JSON.stringify(excludedPages);
}

function handleCategoryChange() {
    const categorySelect = document.getElementById('categorySelect');
    if (!categorySelect) return;

    const category = categorySelect.value;
    const routingFields = document.getElementById('routingFields');
    const fromInput = document.getElementById('fromLocation');
    const toInput = document.getElementById('toLocation');

    if (category === 'transportation' || category === 'delivery') {
        routingFields?.classList.replace('hidden', 'grid');
        fromInput?.setAttribute('required', 'required');
        toInput?.setAttribute('required', 'required');
    } else {
        routingFields?.classList.replace('grid', 'hidden');
        fromInput?.removeAttribute('required');
        toInput?.removeAttribute('required');
        if (fromInput) fromInput.value = '';
        if (toInput) toInput.value = '';
    }
}

const maskInput = document.getElementById('currencyMaskInput');
const actualInput = document.getElementById('actualAmountInput');

maskInput?.addEventListener('input', function(e) {
    let rawValue = e.target.value.replace(/\D/g, '');
    if (rawValue === '') {
        maskInput.value = '';
        if (actualInput) actualInput.value = '';
        return;
    }
    if (actualInput) actualInput.value = rawValue;
    e.target.value = new Intl.NumberFormat('id-ID').format(rawValue);
});

document.addEventListener("DOMContentLoaded", function() {
    handleCategoryChange();

    if (actualInput && actualInput.value) {
        if (maskInput) maskInput.value = new Intl.NumberFormat('id-ID').format(actualInput.value);
    }
});

Object.assign(window, { renderImagePreview, renderPdfPreview, togglePage, handleCategoryChange });
