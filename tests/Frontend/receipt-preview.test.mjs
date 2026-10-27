import assert from 'node:assert/strict';
import test from 'node:test';
import { previewReceipt } from '../../resources/js/pages/receipt-preview.js';

function element() {
    return {
        style: {}, children: [], clientWidth: 400,
        setAttribute() {}, getContext: () => ({}),
        replaceChildren(...children) { this.children = children; },
        append(child) { this.children.push(child); },
    };
}

test('receipt preview renders PDF pages and provides a direct link', async () => {
    globalThis.document = { createElement: element };
    globalThis.window = { devicePixelRatio: 1 };
    const root = element();
    let destroyed = false;
    const dispose = previewReceipt(root, '/receipt', async () => ({
        pdfOptions: {}, pdfjsLib: { getDocument: () => ({
            destroy: async () => { destroyed = true; },
            promise: Promise.resolve({ numPages: 2, getPage: async () => ({
                getViewport: () => ({ width: 600, height: 800 }),
                render: () => ({ promise: Promise.resolve(), cancel() {} }), cleanup() {},
            }) }),
        }) },
    }));
    await new Promise(resolve => setImmediate(resolve));
    assert.equal(root.children[1].href, '/receipt');
    assert.equal(root.children[2].children.length, 2);
    assert.match(root.children[0].textContent, /2 halaman/);
    dispose();
    assert.equal(destroyed, true);
});

test('receipt preview shows an actionable error instead of a blank frame', async () => {
    globalThis.document = { createElement: element };
    const root = element();
    previewReceipt(root, '/missing', async () => { throw new Error('404'); });
    await new Promise(resolve => setImmediate(resolve));
    assert.match(root.children[0].textContent, /PDF tidak dapat dimuat/);
    assert.equal(root.children[1].href, '/missing');
});

test('closing preview before the PDF library loads prevents stale rendering', async () => {
    globalThis.document = { createElement: element };
    const root = element();
    let resolve;
    const dispose = previewReceipt(root, '/receipt', () => new Promise(done => { resolve = done; }));
    dispose();
    resolve({ pdfjsLib: { getDocument() { assert.fail('Closed preview started loading'); } } });
    await new Promise(done => setImmediate(done));
    assert.equal(root.children[2].children.length, 0);
});
