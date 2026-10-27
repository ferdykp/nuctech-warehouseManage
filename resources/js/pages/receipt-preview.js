export function previewReceipt(root, url, loadPdf = () => import('../pdf.js')) {
    let closed = false;
    let task;
    let rendering;
    const status = document.createElement('p');
    status.setAttribute('role', 'status');
    status.className = 'p-3 text-slate-600';
    status.textContent = 'Memuat pratinjau PDF…';
    const link = document.createElement('a');
    link.href = url;
    link.target = '_blank';
    link.rel = 'noopener';
    link.className = 'block p-3 font-semibold text-blue-600 underline';
    link.textContent = 'Buka nota di tab baru';
    const pages = document.createElement('div');
    pages.className = 'space-y-3 p-2';
    root.replaceChildren(status, link, pages);
    root.style.maxHeight = '65vh';
    root.style.overflowY = 'auto';

    (async () => {
        try {
            const { pdfjsLib, pdfOptions } = await loadPdf();
            if (closed) return;
            task = pdfjsLib.getDocument({ url, ...pdfOptions });
            const pdf = await task.promise;
            if (closed) return;
            for (let number = 1; number <= pdf.numPages; number++) {
                const page = await pdf.getPage(number);
                if (closed) return;
                const size = page.getViewport({ scale: 1 });
                const width = Math.min(1200, Math.max(640, root.clientWidth * Math.min(window.devicePixelRatio || 1, 1.5)));
                const viewport = page.getViewport({ scale: width / size.width });
                const canvas = document.createElement('canvas');
                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                canvas.style.width = '100%';
                canvas.style.height = 'auto';
                canvas.setAttribute('role', 'img');
                canvas.setAttribute('aria-label', `Halaman ${number} dari ${pdf.numPages}`);
                rendering = page.render({ canvasContext: canvas.getContext('2d'), viewport });
                await rendering.promise;
                if (closed) return;
                pages.append(canvas);
                page.cleanup();
            }
            status.textContent = `${pdf.numPages} halaman. Gulir untuk membaca nota.`;
        } catch {
            if (!closed) status.textContent = 'PDF tidak dapat dimuat. Buka nota di tab baru untuk memeriksa file atau sesi login Anda.';
        }
    })();

    return () => {
        closed = true;
        rendering?.cancel();
        task?.destroy()?.catch(() => {});
    };
}
