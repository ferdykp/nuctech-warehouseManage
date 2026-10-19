import * as pdfjsModule from 'pdfjs-dist/build/pdf.js';
const pdfjsLib = pdfjsModule.default || pdfjsModule;
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.js?url';
import SignaturePad from 'signature_pad';
const config = JSON.parse(document.getElementById('reimbursement-approval-config').textContent);

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

let signaturePad = null;
let loadedSignatureBase64 = null;
let activeStampsArray = [];

const pdfUrl = config.pdfUrl;
const isPdf = config.isPdf;

function initApprovalWorkspace() {
    const canvas = document.getElementById('signature-canvas');
    if (canvas) {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        const rect = canvas.getBoundingClientRect();

        if (rect.width > 0 && rect.height > 0) {
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            const ctx = canvas.getContext("2d");
            if (ctx) ctx.scale(ratio, ratio);
        }

        if (typeof SignaturePad !== 'undefined') {
            signaturePad = new SignaturePad(canvas, {
                minWidth: 1.5,
                maxWidth: 3.5,
                penColor: "rgb(30, 41, 59)"
            });
        }
    }

    if (isPdf) renderPdfWithPdfJs(pdfUrl);
    else {
        const image = document.querySelector('#invoice-target-img img');
        if (image?.complete) syncLayerHeight();
        else image?.addEventListener('load', syncLayerHeight, { once: true });
    }
    if (canvas && signaturePad && typeof ResizeObserver !== 'undefined') {
        let previousWidth = canvas.getBoundingClientRect().width;
        new ResizeObserver(() => {
            const rect = canvas.getBoundingClientRect();
            if (!rect.width || rect.width === previousWidth) return;
            const data = signaturePad.toData();
            const scale = rect.width / previousWidth;
            data.forEach(group => group.points.forEach(point => { point.x *= scale; }));
            const ratio = Math.max(devicePixelRatio || 1, 1);
            canvas.width = rect.width * ratio;
            canvas.height = rect.height * ratio;
            canvas.getContext('2d').scale(ratio, ratio);
            signaturePad.fromData(data);
            previousWidth = rect.width;
        }).observe(canvas);
    }
}

document.addEventListener("DOMContentLoaded", initApprovalWorkspace);

if (window.up) {
    up.compiler('#workspace-area', function(element) {
        initApprovalWorkspace();
    });
    up.on('up:fragment:inserted', function() {
        initApprovalWorkspace();
    });
}

function renderPdfWithPdfJs(url) {
    const container = document.getElementById('invoice-target-img');
    if (!container) return;

    container.innerHTML = '';

    const loadingTask = pdfjsLib.getDocument({ url, isEvalSupported: false });
    loadingTask.promise.then(async function(pdf) {
        const indicator = document.getElementById('pdf-loading-indicator');
        if (indicator) indicator.remove();

        // Render sequentially so long documents do not allocate all page canvases at once.
        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            await renderSinglePage(pdf, pageNum, container);
        }
        syncLayerHeight();
    }).catch(function(err) {
        console.error("PDF.js Error: ", err);
        const indicator = document.getElementById('pdf-loading-indicator');
        if (indicator) indicator.innerText = "Dokumen PDF gagal dimuat. Silakan coba lagi.";
    });
}

function renderSinglePage(pdf, pageNum, container) {
    return pdf.getPage(pageNum).then(function(page) {
        const pageWrapper = document.createElement('div');
        pageWrapper.className =
            'pdf-page-wrapper relative bg-white shadow-lg rounded-2xl overflow-hidden w-full max-w-full mb-4';
        pageWrapper.dataset.pageNum = pageNum;

        const canvas = document.createElement('canvas');
        canvas.className = 'block w-full h-auto';
        pageWrapper.appendChild(canvas);
        container.appendChild(pageWrapper);

        const containerWidth = container.clientWidth > 0 ? container.clientWidth : 650;
        const unscaledViewport = page.getViewport({
            scale: 1
        });
        const scale = containerWidth / unscaledViewport.width;
        const viewport = page.getViewport({
            scale: scale
        });

        const context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        const renderContext = {
            canvasContext: context,
            viewport: viewport
        };

        return page.render(renderContext).promise;
    });
}

function syncLayerHeight() {
    const workspace = document.getElementById('workspace-area');
    const layer = document.getElementById('stamps-rendering-layer');
    if (workspace && layer) {
        const documentArea = document.getElementById('invoice-target-img');
        layer.style.height = Math.max(workspace.clientHeight, (documentArea?.offsetTop || 0) + (documentArea?.offsetHeight || 0) + 16) + 'px';
        layer.style.width = workspace.scrollWidth + 'px';
    }
}

function clearSignature() {
    if (signaturePad) signaturePad.clear();
}

function switchSignatureTab(type) {
    const btnDraw = document.getElementById('btn-tab-draw');
    const btnUpload = document.getElementById('btn-tab-upload');
    const panelDraw = document.getElementById('panel-signature-draw');
    const panelUpload = document.getElementById('panel-signature-upload');
    if (!btnDraw || !btnUpload) return;

    if (type === 'draw') {
        btnDraw.className =
            "flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all bg-white text-slate-800 shadow-2xs cursor-pointer";
        btnUpload.className =
            "flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700 cursor-pointer";
        panelDraw.classList.remove('hidden');
        panelUpload.classList.add('hidden');
    } else {
        btnUpload.className =
            "flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all bg-white text-slate-800 shadow-2xs cursor-pointer";
        btnDraw.className =
            "flex-1 py-2 font-bold text-xs uppercase rounded-lg transition-all text-slate-500 hover:text-slate-700 cursor-pointer";
        panelUpload.classList.remove('hidden');
        panelDraw.classList.add('hidden');
    }
}

function handleSignatureDrop(event) {
    event.preventDefault();
    if (event.dataTransfer.files.length > 0) processFile(event.dataTransfer.files[0]);
}

function handleSignatureFileSelect(event) {
    if (event.target.files.length > 0) processFile(event.target.files[0]);
}

function processFile(file) {
    if (!file.type.match('image.*')) {
        (window.showUiMessage || window.alert)("Gunakan gambar PNG atau JPG yang valid.");
        return;
    }
    const reader = new FileReader();
    reader.onload = function(e) {
        loadedSignatureBase64 = e.target.result;
        document.getElementById('dropzone-text').innerText = "✓ Diunggah: " + file.name;
        document.getElementById('dropzone').classList.add('border-emerald-500', 'bg-emerald-50/50');
    };
    reader.readAsDataURL(file);
}

function applySignatureToPreview() {
    let base64Image = null;
    const drawPanel = document.getElementById('panel-signature-draw');
    const isDrawPanel = drawPanel ? !drawPanel.classList.contains('hidden') : true;

    if (isDrawPanel) {
        if (signaturePad && !signaturePad.isEmpty()) {
            base64Image = signaturePad.toDataURL("image/png");
        }
    } else {
        if (loadedSignatureBase64) base64Image = loadedSignatureBase64;
    }

    if (!base64Image) {
        (window.showUiMessage || window.alert)("Gambar atau unggah tanda tangan terlebih dahulu.");
        return;
    }

    const name = (document.getElementById('signer-name')?.value || '').trim();
    const dateVal = document.getElementById('signer-date')?.value || '';

    if (!name) {
        (window.showUiMessage || window.alert)("Nama penanda tangan wajib diisi.");
        return;
    }

    generateStampMarkup(base64Image, name, dateVal);
}

function duplicateLastStamp() {
    if (activeStampsArray.length === 0) {
        (window.showUiMessage || window.alert)("Belum ada tanda tangan yang dapat digandakan.");
        return;
    }
    const lastStamp = activeStampsArray[activeStampsArray.length - 1];
    const lastEl = lastStamp.stampEl;

    generateStampMarkup(lastStamp.imgData, lastStamp.signerName, lastStamp.signerDate, {
        left: lastEl.offsetLeft + 25,
        top: lastEl.offsetTop + 25,
        w: lastEl.offsetWidth,
        h: lastEl.offsetHeight
    });
}

function generateStampMarkup(imgData, signerName, signerDate, opts) {
    const layer = document.getElementById('stamps-rendering-layer');
    if (!layer) return;

    const leftPos = opts?.left ?? 30;
    const topPos = opts?.top ?? 30;
    const widthSize = opts?.w ?? 140;
    const heightSize = opts?.h ?? 85;

    let formattedDate = signerDate;
    if (signerDate) {
        const d = new Date(signerDate);
        formattedDate = d.toLocaleDateString('id-ID', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    }

    const stampEl = document.createElement('div');
    stampEl.className = 'absolute cursor-move select-none';
    stampEl.style.cssText =
        `left: ${leftPos}px; top: ${topPos}px; width: ${widthSize}px; height: ${heightSize}px; pointer-events: all; z-index: 50;`;

    stampEl.innerHTML = `
        <div class="relative w-full h-full transition-all border-2 border-dashed rounded-lg border-amber-500 hover:bg-amber-500/5" style="padding: 2px;">
            <img src="${imgData}" class="block w-full pointer-events-none drop-shadow-sm" style="height: calc(100% - 22px); object-fit: contain;" />
            <div class="absolute bottom-0 left-0 right-0 leading-tight text-center text-white bg-slate-900/60 rounded-b-md" style="padding: 2px 4px;">
                <div class="font-black" style="font-size: 8px;"><span data-stamp-name></span></div>
                <div class="font-bold text-slate-300" style="font-size: 7px;">${formattedDate}</div>
            </div>
            <span class="absolute -top-4 left-0 bg-amber-500 text-white font-black rounded px-1.5 uppercase text-[7px] tracking-wider pointer-events-none shadow-xs">
                Geser &bull; <span data-stamp-name></span>
            </span>
            <button type="button" class="stamp-delete-btn flex items-center justify-center bg-rose-500 hover:bg-rose-600 text-white border-none rounded-full absolute -top-3.5 -right-1.5 transition-colors cursor-pointer" aria-label="Hapus tanda tangan" style="width: 32px; height: 32px; font-size: 14px; font-weight: 900; line-height: 1;" onclick="removeTargetStamp(this)">✕</button>
            <div class="absolute bg-blue-500 border-2 border-white rounded shadow-xs stamp-resize-handle bottom-1 right-1 cursor-se-resize" style="width: 12px; height: 12px; z-index: 60;"></div>
        </div>
    `;

    stampEl.querySelectorAll('[data-stamp-name]').forEach(label => { label.textContent = signerName; });
    stampEl.tabIndex = 0;
    stampEl.setAttribute('role', 'group');
    stampEl.setAttribute('aria-label', `Tanda tangan ${signerName}. Tombol panah untuk memindahkan, Shift untuk langkah lebih besar.`);
    stampEl.addEventListener('keydown', event => {
        if (event.target !== stampEl || !['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const step = event.shiftKey ? 10 : 1;
        const dx = event.key === 'ArrowLeft' ? -step : event.key === 'ArrowRight' ? step : 0;
        const dy = event.key === 'ArrowUp' ? -step : event.key === 'ArrowDown' ? step : 0;
        stampEl.style.left = Math.max(0, Math.min(stampEl.offsetLeft + dx, layer.clientWidth - stampEl.offsetWidth)) + 'px';
        stampEl.style.top = Math.max(0, Math.min(stampEl.offsetTop + dy, layer.clientHeight - stampEl.offsetHeight)) + 'px';
    });
    const resizeHandle = stampEl.querySelector('.stamp-resize-handle');
    resizeHandle.tabIndex = 0;
    resizeHandle.setAttribute('role', 'button');
    resizeHandle.setAttribute('aria-label', 'Ubah ukuran tanda tangan dengan tombol panah');
    resizeHandle.addEventListener('keydown', event => {
        if (!['ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault(); event.stopPropagation();
        const dx = event.key === 'ArrowLeft' ? -5 : event.key === 'ArrowRight' ? 5 : 0;
        const dy = event.key === 'ArrowUp' ? -5 : event.key === 'ArrowDown' ? 5 : 0;
        stampEl.style.width = Math.min(Math.max(80, layer.clientWidth - stampEl.offsetLeft), Math.max(80, stampEl.offsetWidth + dx)) + 'px';
        stampEl.style.height = Math.min(Math.max(60, layer.clientHeight - stampEl.offsetTop), Math.max(60, stampEl.offsetHeight + dy)) + 'px';
    });
    layer.appendChild(stampEl);
    syncLayerHeight();

    activeStampsArray.push({
        stampEl: stampEl,
        imgData: imgData,
        signerName: signerName,
        signerDate: signerDate
    });

    const infoText = document.getElementById('stamp-count-info');
    if (infoText) infoText.textContent = `${activeStampsArray.length} tanda tangan ditempatkan pada dokumen`;

    bindDragEvents(stampEl);
    bindResizeEvents(stampEl);
}

function removeTargetStamp(btn) {
    const stampWrapper = btn.closest('.absolute.cursor-move');
    if (!stampWrapper) return;
    activeStampsArray = activeStampsArray.filter(function(item) {
        return item.stampEl !== stampWrapper;
    });
    const infoText = document.getElementById('stamp-count-info');
    if (infoText) infoText.textContent = `${activeStampsArray.length} tanda tangan ditempatkan pada dokumen`;
    stampWrapper.remove();
}

function bindDragEvents(el) {
    const workspace = document.getElementById('workspace-area');
    let dragging = false,
        sX, sY, iL, iT;

    el.addEventListener('mousedown', startDrag);
    el.addEventListener('touchstart', startDrag, {
        passive: true
    });

    function startDrag(e) {
        if (e.target.classList.contains('stamp-resize-handle') || e.target.classList.contains('stamp-delete-btn'))
            return;
        dragging = true;
        sX = e.clientX ?? e.touches[0].clientX;
        sY = e.clientY ?? e.touches[0].clientY;
        iL = el.offsetLeft;
        iT = el.offsetTop;

        document.addEventListener('mousemove', processDrag);
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchmove', processDrag, {
            passive: false
        });
        document.addEventListener('touchend', stopDrag);
        document.addEventListener('touchcancel', stopDrag);
    }

    function processDrag(e) {
        if (!dragging) return;
        if (e.cancelable) e.preventDefault();
        const cX = e.clientX ?? e.touches[0].clientX;
        const cY = e.clientY ?? e.touches[0].clientY;

        let nL = iL + (cX - sX);
        let nT = iT + (cY - sY);

        const maxL = workspace.scrollWidth - el.offsetWidth;
        const maxT = workspace.scrollHeight - el.offsetHeight;

        nL = Math.max(0, Math.min(nL, maxL));
        nT = Math.max(0, Math.min(nT, maxT));

        el.style.left = nL + 'px';
        el.style.top = nT + 'px';
    }

    function stopDrag() {
        dragging = false;
        document.removeEventListener('mousemove', processDrag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchmove', processDrag);
        document.removeEventListener('touchend', stopDrag);
        document.removeEventListener('touchcancel', stopDrag);
    }
}

function bindResizeEvents(el) {
    const handle = el.querySelector('.stamp-resize-handle');
    if (!handle) return;
    let resizing = false,
        sX, sY, sW, sH;

    handle.addEventListener('mousedown', startResize);
    handle.addEventListener('touchstart', startResize, {
        passive: true
    });

    function startResize(e) {
        e.stopPropagation();
        resizing = true;
        sX = e.clientX ?? e.touches[0].clientX;
        sY = e.clientY ?? e.touches[0].clientY;
        sW = el.offsetWidth;
        sH = el.offsetHeight;

        document.addEventListener('mousemove', processResize);
        document.addEventListener('mouseup', stopResize);
        document.addEventListener('touchmove', processResize, {
            passive: false
        });
        document.addEventListener('touchend', stopResize);
        document.addEventListener('touchcancel', stopResize);
    }

    function processResize(e) {
        if (!resizing) return;
        if (e.cancelable) e.preventDefault();
        const cX = e.clientX ?? e.touches[0].clientX;
        const cY = e.clientY ?? e.touches[0].clientY;

        el.style.width = Math.max(80, sW + (cX - sX)) + 'px';
        el.style.height = Math.max(60, sH + (cY - sY)) + 'px';
    }

    function stopResize() {
        resizing = false;
        document.removeEventListener('mousemove', processResize);
        document.removeEventListener('mouseup', stopResize);
        document.removeEventListener('touchmove', processResize);
        document.removeEventListener('touchend', stopResize);
        document.removeEventListener('touchcancel', stopResize);
    }
}

function prepareFinalApproval(event) {
    if (activeStampsArray.length === 0) {
        event.preventDefault();
        (window.showUiMessage || window.alert)("Letakkan setidaknya satu tanda tangan pada dokumen nota.");
        return false;
    }

    const signaturesMap = activeStampsArray.map(function(item) {
        const stampEl = item.stampEl;
        const imgData = item.imgData;
        const signerName = item.signerName;
        const signerDate = item.signerDate;

        const stampRect = stampEl.getBoundingClientRect();

        let detectedPageNum = 1;
        let finalRelLeft = 0;
        let finalRelTop = 0;
        let baseWidth = 100;
        let baseHeight = 100;

        if (isPdf) {
            const pageElements = document.querySelectorAll('.pdf-page-wrapper');
            let matched = false;
            const stampCenterY = stampRect.top + (stampRect.height / 2);

            for (let i = 0; i < pageElements.length; i++) {
                const pRect = pageElements[i].getBoundingClientRect();
                if (stampCenterY >= pRect.top && stampCenterY <= pRect.bottom) {
                    detectedPageNum = parseInt(pageElements[i].dataset.pageNum);
                    finalRelLeft = stampRect.left - pRect.left;
                    finalRelTop = stampRect.top - pRect.top;
                    baseWidth = pRect.width;
                    baseHeight = pRect.height;
                    matched = true;
                    break;
                }
            }

            if (!matched && pageElements.length > 0) {
                const pRect = pageElements[0].getBoundingClientRect();
                finalRelLeft = stampRect.left - pRect.left;
                finalRelTop = stampRect.top - pRect.top;
                baseWidth = pRect.width;
                baseHeight = pRect.height;
            }
        } else {
            const imgTarget = document.querySelector('#invoice-target-img img');
            if (imgTarget) {
                const imgRect = imgTarget.getBoundingClientRect();
                finalRelLeft = stampRect.left - imgRect.left;
                finalRelTop = stampRect.top - imgRect.top;
                baseWidth = imgRect.width;
                baseHeight = imgRect.height;
            }
        }

        // Normalisasi persentase posisi agar aman dari batas overflow
        const posXPercent = Math.max(0, Math.min((finalRelLeft / baseWidth) * 100, 100));
        const posYPercent = Math.max(0, Math.min((finalRelTop / baseHeight) * 100, 100));
        const scaleWPercent = Math.max(1, Math.min((stampRect.width / baseWidth) * 100, 100));
        const scaleHPercent = Math.max(1, Math.min((stampRect.height / baseHeight) * 100, 100));

        return {
            image: imgData,
            page: detectedPageNum,
            pos_x: posXPercent,
            pos_y: posYPercent,
            scale_w: scaleWPercent,
            scale_h: scaleHPercent,
            signer_name: signerName,
            signer_date: signerDate
        };
    });

    document.getElementById('signatures-json-input').value = JSON.stringify(signaturesMap);
    document.getElementById('signature-input').value = signaturesMap[0].image;
    document.getElementById('pos-x-input').value = signaturesMap[0].pos_x;
    document.getElementById('pos-y-input').value = signaturesMap[0].pos_y;
    document.getElementById('scale-w-input').value = signaturesMap[0].scale_w;
    document.getElementById('scale-h-input').value = signaturesMap[0].scale_h;

    if (document.getElementById('page-input')) {
        document.getElementById('page-input').value = signaturesMap[0].page;
    }

    // Continue the original submit event so loading feedback and return context also apply.
}

Object.assign(window, { initApprovalWorkspace, renderPdfWithPdfJs, renderSinglePage, syncLayerHeight, clearSignature, switchSignatureTab, handleSignatureDrop, handleSignatureFileSelect, processFile, applySignatureToPreview, duplicateLastStamp, generateStampMarkup, removeTargetStamp, bindDragEvents, bindResizeEvents, prepareFinalApproval });
