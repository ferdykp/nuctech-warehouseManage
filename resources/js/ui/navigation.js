const prefix = 'ui-list-context:';
const listPath = /\/(?:dashboard|employee|salary|attendance|schedules|leave|shift|profile\/profileList|reimbursements|daily-reports|report|branches|sites|site|categories|spareparts\/all|inventory\/[^/]+)\/?$/;

export function readListContext(storage, path, account = '', allowAlias = false) {
    try {
        const context = JSON.parse(storage.getItem(prefix + account + path) || 'null');
        const url = new URL(context?.url, location.origin);
        const reportAlias = allowAlias && path === '/report' && /^\/report\/(?!create$|export$|search$|global$)[a-z0-9_-]+$/i.test(url.pathname);
        return url.origin === location.origin && (url.pathname === path || reportAlias) && !url.username && !url.password ? context : null;
    } catch { return null; }
}

export function initNavigation() {
    const main = document.getElementById('main-content');
    if (!main || !document.getElementById('sidebar-menu')) return;
    const account = document.getElementById('sidebar-menu').dataset.scrollKey + ':';
    const sidebar = document.getElementById('main-sidebar');
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab' || !matchMedia('(max-width: 1023px)').matches || (!sidebar || sidebar.inert)) return;
        const items = Array.from(sidebar.querySelectorAll('a[href], button:not([disabled])')).filter(item => item.getClientRects().length);
        const first = items[0], last = items.at(-1);
        if (!first) return;
        if (!sidebar.contains(document.activeElement) || (!event.shiftKey && document.activeElement === last)) {
            event.preventDefault(); first.focus();
        } else if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    });

    const storage = (() => { try { return sessionStorage; } catch { return null; } })();
    if (!storage) return;
    const listRoot = document.querySelector('[data-ui-list-root]')?.dataset.uiListRoot;
    const save = () => {
        if (!listPath.test(location.pathname) && !listRoot) return;
        try {
            const context = JSON.stringify({ url: location.pathname + location.search, top: main.scrollTop });
            storage.setItem(prefix + account + location.pathname, context);
            if (listRoot) storage.setItem(prefix + account + new URL(listRoot, location.origin).pathname, context);
        }
        catch { /* Browsing still works with disabled storage. */ }
    };
    const restore = () => {
        const context = readListContext(storage, location.pathname, account);
        if (context && context.url === location.pathname + location.search) main.scrollTop = Number(context.top) || 0;
    };
    restore();
    window.addEventListener('pageshow', restore);
    window.addEventListener('pagehide', save);
    document.addEventListener('click', event => {
        const link = event.target.closest('a[href]');
        if (!link || event.defaultPrevented || event.button !== 0) return;
        save();
        if (!/\b(kembali|batal)\b/i.test(link.textContent)) return;
        const url = new URL(link.href, location.href);
        if (url.origin !== location.origin) return;
        const context = readListContext(storage, url.pathname, account, true);
        if (context && !url.search) link.href = context.url;
    }, true);
    // The server only accepts this context when it matches its own redirect path.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (form.method?.toLowerCase() !== 'post') return;
        const candidates = Array.from(document.querySelectorAll('a[href]'))
            .filter(link => /\b(kembali|batal)\b/i.test(link.textContent))
            .map(link => new URL(link.href, location.href));
        const url = listPath.test(location.pathname) ? new URL(location.href)
            : candidates.find(url => url.origin === location.origin && listPath.test(url.pathname));
        const context = url && (readListContext(storage, url.pathname, account, true) || { url: url.pathname + url.search });
        if (!context) return;
        let marker = form.querySelector('[name="_ui_return"]');
        if (!marker) { marker = document.createElement('input'); marker.type = 'hidden'; marker.name = '_ui_return'; form.append(marker); }
        marker.value = context.url;
        save();
    }, true);
}
