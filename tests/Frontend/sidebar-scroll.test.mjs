import assert from 'node:assert/strict';
import test from 'node:test';
import { readFileSync } from 'node:fs';
import { runInNewContext } from 'node:vm';

const view = readFileSync(new URL('../../resources/views/layout/aside.blade.php', import.meta.url), 'utf8');
const script = view.match(/<script>\s*\/\/ Restore before[\s\S]*?<\/script>/)[0].replace(/<\/?script>/g, '');
function mount(storage) {
    const events = {};
    const menu = { dataset: { scrollKey: 'sidebar-scroll-1' }, scrollTop: 0,
        addEventListener: (name, handler) => { events[name] = handler; } };
    const lifecycle = {};
    runInNewContext(script, {
        document: { getElementById: () => menu }, sessionStorage: storage,
        window: { addEventListener: (name, handler) => { lifecycle[name] = handler; } },
    });
    return { menu, events, lifecycle };
}
test('sidebar scroll survives page navigation and saves the latest position before leaving', () => {
    const data = new Map([['sidebar-scroll-1', '240']]);
    const storage = { getItem: key => data.get(key) ?? null, setItem: (key, value) => data.set(key, value) };
    const first = mount(storage);
    assert.equal(first.menu.scrollTop, 240);
    first.menu.scrollTop = 380;
    first.events.scroll();
    assert.equal(mount(storage).menu.scrollTop, 380);
    first.menu.scrollTop = 410;
    first.lifecycle.pagehide();
    assert.equal(mount(storage).menu.scrollTop, 410);
});
test('sidebar tolerates unavailable storage and ignores invalid positions', () => {
    const blocked = { getItem() { throw Error('blocked'); }, setItem() { throw Error('blocked'); } };
    const instance = mount(blocked);
    assert.doesNotThrow(() => instance.events.click());
    for (const saved of [null, 'NaN', '-10', 'Infinity']) {
        assert.equal(mount({ getItem: () => saved }).menu.scrollTop, 0);
    }
});
