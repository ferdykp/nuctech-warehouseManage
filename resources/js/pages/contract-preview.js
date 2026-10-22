import { pdfjsLib, pdfOptions } from '../pdf.js';

async function preview(root) {
    const status = root.querySelector('[role="status"]');
    const pages = root.querySelector('[data-contract-pages]');
    const url = new URL(root.dataset.pdfUrl, location.origin);
    if (url.origin !== location.origin) return;
    let documentTask;
    let observer;
    window.addEventListener('pagehide', () => {
        observer?.disconnect();
        documentTask?.destroy();
    }, { once: true });
    try {
        documentTask = pdfjsLib.getDocument({ url: url.href, ...pdfOptions });
        const pdf = await documentTask.promise;
        if (pdf.numPages > 100) throw new Error('Too many pages');
        const firstPage = await pdf.getPage(1);
        const size = firstPage.getViewport({ scale: 1 });
        const pixelWidth = Math.min(1200, Math.max(640, root.clientWidth * Math.min(devicePixelRatio || 1, 1.5)));
        const scale = pixelWidth / size.width;
        let chain = Promise.resolve();
        async function render(wrapper) {
            try {
                const number = Number(wrapper.dataset.page);
                const page = number === 1 ? firstPage : await pdf.getPage(number);
                const viewport = page.getViewport({ scale });
                const canvas = document.createElement('canvas');
                canvas.width = Math.ceil(viewport.width);
                canvas.height = Math.ceil(viewport.height);
                canvas.style.width = '100%';
                canvas.style.height = 'auto';
                canvas.setAttribute('role', 'img');
                canvas.setAttribute('aria-label', `Halaman ${number} dari ${pdf.numPages}`);
                await page.render({ canvasContext: canvas.getContext('2d'), viewport }).promise;
                wrapper.replaceChildren(canvas);
                wrapper.setAttribute('aria-busy', 'false');
            } catch {
                wrapper.textContent = 'Halaman ini gagal dimuat. Gunakan Unduh PDF untuk membukanya.';
            }
        }
        observer = new IntersectionObserver(entries => {
            entries.filter(entry => entry.isIntersecting).forEach(({ target }) => {
                observer.unobserve(target);
                chain = chain.then(() => render(target));
            });
        }, { root, rootMargin: '300px 0px' });
        for (let number = 1; number <= pdf.numPages; number++) {
            const wrapper = document.createElement('div');
            wrapper.dataset.page = number;
            wrapper.style.aspectRatio = `${size.width} / ${size.height}`;
            wrapper.style.background = '#fff';
            wrapper.style.marginBottom = '12px';
            wrapper.setAttribute('aria-busy', 'true');
            wrapper.textContent = `Halaman ${number}…`;
            pages.append(wrapper);
            observer.observe(wrapper);
        }
        status.textContent = `${pdf.numPages} halaman. Gulir untuk membaca dokumen.`;
    } catch {
        status.textContent = 'Pratinjau belum dapat dimuat. Gunakan tombol Unduh PDF untuk membuka dokumen.';
    }
}

document.querySelectorAll('[data-contract-preview]').forEach(preview);
