import assert from 'node:assert/strict';
import test from 'node:test';
import { createLatestRequest } from '../../resources/js/ui/requests.js';

test('only the newest list request can render or finish the loading state', async () => {
    const calls = [], rendered = [], finished = [];
    const request = createLatestRequest((url, options) => new Promise(resolve => calls.push({url, options, resolve})));
    const first = request('/first', {onSuccess: value => rendered.push(value), onFinally: () => finished.push('first')});
    const second = request('/second', {onSuccess: value => rendered.push(value), onFinally: () => finished.push('second')});
    assert.equal(calls[0].options.signal.aborted, true);
    calls[0].resolve({ok:true, text:async () => 'old'});
    await first;
    assert.deepEqual(rendered, []);
    assert.deepEqual(finished, []);
    calls[1].resolve({ok:true, text:async () => 'new'});
    await second;
    assert.deepEqual(rendered, ['new']);
    assert.deepEqual(finished, ['second']);
});

test('an expired session never renders a login page as table rows', async () => {
    let error, finished = false, rendered = false;
    const request = createLatestRequest(async () => ({ok:true, redirected:true, text:async () => '<form>login</form>'}));
    await request('/list', {onSuccess: () => {rendered=true;}, onError: value => {error=value;}, onFinally: () => {finished=true;}});
    assert.equal(rendered, false);
    assert.ok(error instanceof Error);
    assert.equal(finished, true);
});
