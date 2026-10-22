import * as pdfjsLib from 'pdfjs-dist/build/pdf.mjs';
import workerUrl from 'pdfjs-dist/build/pdf.worker.min.mjs?url';

pdfjsLib.GlobalWorkerOptions.workerSrc = workerUrl;

// Serve PDF fonts, CMaps and decoders from this application, never a public CDN.
const assets = import.meta.env.PROD
    ? `${import.meta.env.BASE_URL}pdfjs/`
    : new URL('/node_modules/pdfjs-dist/', import.meta.url).href;

export const pdfOptions = {
    isEvalSupported: false,
    cMapUrl: `${assets}cmaps/`,
    cMapPacked: true,
    standardFontDataUrl: `${assets}standard_fonts/`,
    wasmUrl: `${assets}wasm/`,
    iccUrl: `${assets}iccs/`,
};

export { pdfjsLib };
