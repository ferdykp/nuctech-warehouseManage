import assert from 'node:assert/strict';
import test from 'node:test';
import vm from 'node:vm';
import { readFileSync } from 'node:fs';

const script = readFileSync(new URL('../../resources/js/pages/reimbursements.js', import.meta.url), 'utf8');
test('a slow earlier search cannot replace newer results or their URL', async () => {
    const calls = [], history = [], attributes = new Map();
    const wrapper = { innerHTML: '', setAttribute: (key, value) => attributes.set(key, value), removeAttribute: key => attributes.delete(key) };
    const fields = {
        'reimbursements-config': { textContent: JSON.stringify({ indexUrl: '/reimbursements', pdfUrl: '/pdf', excelUrl: '/excel' }) },
        'reimbursementDataWrapper': wrapper, 'reimburseSearchInput': { value: 'first' },
        'reimburseMonthSelect': { value: '' }, 'reimburseStatusSelect': { value: '' },
        'bulkDeleteBtn': { style: {} }, 'selectedCount': {},
    };
    const context = vm.createContext({ URL, AbortController, clearTimeout, setTimeout,
        location: {origin:'https://warehouse.test'},
        window: { announceUi() {}, showUiMessage() { assert.fail('Unexpected search failure'); } },
        history: { replaceState: (_, __, url) => history.push(url.href) },
        document: { getElementById: id => fields[id] ?? null, querySelectorAll: () => [], addEventListener() {} },
        fetch: (url, options) => new Promise(resolve => calls.push({ url, options, resolve })),
        DOMParser: class { parseFromString(html) { return { getElementById: id => id === 'reimbursementDataWrapper' ? { innerHTML: html } : null }; } },
    });
    vm.runInContext(script, context);
    const first = context.fetchReimbursementData();
    fields.reimburseSearchInput.value = 'second';
    const second = context.fetchReimbursementData();
    assert.equal(calls[0].options.signal.aborted, true);
    calls[1].resolve({ok:true, text:async () => 'new results'});
    await second;
    calls[0].resolve({ok:true, text:async () => 'stale results'});
    await first;
    assert.equal(wrapper.innerHTML, 'new results');
    assert.equal(history.length, 1);
    assert.match(history[0], /search=second/);
    assert.equal(attributes.has('aria-busy'), false);
});
