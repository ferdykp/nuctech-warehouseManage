export function initDialogs() {
    const visible = element => element.isConnected && element.getClientRects().length > 0
        && getComputedStyle(element).visibility !== 'hidden';
    const opened = new Map();
    const panels = new Set();
    let sequence = 0, queued = false, previousTop = null;
    let isolated = new Map();
    const controls = panel => Array.from(panel.querySelectorAll(
        'a[href], button:not([disabled]), input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'
    )).filter(element => visible(element) && !element.closest('[inert]'));
    const zIndex = panel => {
        let z = 0;
        for (let node = panel; node && node !== document.body; node = node.parentElement) {
            z = Math.max(z, parseInt(getComputedStyle(node).zIndex) || 0);
        }
        return z;
    };
    const release = () => {
        for (const [node, inert] of isolated) if (node.isConnected) node.inert = inert;
        isolated = new Map();
    };
    const isolate = top => {
        release();
        if (!top) return;
        let branch = top.closest('.ui-dialog-overlay') || top;
        while (branch && branch !== document.body) {
            for (const sibling of branch.parentElement.children) {
                if (sibling === branch || sibling.matches('script, style, link, #ui-live-status, #ui-message')) continue;
                isolated.set(sibling, sibling.inert);
                sibling.inert = true;
            }
            branch = branch.parentElement;
        }
    };
    const activePanels = () => Array.from(panels).filter(visible).sort((a, b) => zIndex(a) - zIndex(b));
    const sync = () => {
        queued = false;
        for (const panel of panels) if (!panel.isConnected) panels.delete(panel);
        const active = activePanels();
        const top = active.at(-1);
        // Release background before testing a previous opener's visibility/focusability.
        if (top !== previousTop) release();
        for (const [panel, opener] of opened) {
            if (active.includes(panel)) continue;
            opened.delete(panel);
            if (opener?.isConnected && (!top || top.contains(opener)) && visible(opener)) opener.focus({ preventScroll: true });
        }
        for (const panel of active) {
            if (opened.has(panel)) continue;
            opened.set(panel, document.activeElement);
            if (!matchMedia('(prefers-reduced-motion: reduce)').matches && panel.animate) {
                panel.animate([{ opacity: 0 }, { opacity: 1 }], { duration: 160, easing: 'ease-out' });
            }
        }
        if (top !== previousTop) {
            isolate(top);
            if (top && !top.contains(document.activeElement)) {
                // Start at the title so a phone keyboard does not obscure a newly opened modal.
                top.focus({ preventScroll: true });
            }
            previousTop = top;
        }
        if (document.body.classList.contains('ui-modal-open') !== Boolean(top)) {
            document.body.classList.toggle('ui-modal-open', Boolean(top));
        }
    };
    const queue = () => {
        if (queued) return;
        queued = true;
        requestAnimationFrame(sync);
    };
    const enhance = () => {
        document.querySelectorAll('[data-ui-dialog]').forEach(panel => { if (!panels.has(panel)) panels.add(panel); });
        document.querySelectorAll('.fixed.inset-0').forEach(overlay => {
            const panel = Array.from(overlay.children).find(child => child.matches('div')
                && child.querySelector('h2, h3, h4, h5') && !child.classList.contains('fixed'));
            if (!panel || panels.has(panel)) return;
            panels.add(panel);
            panel.dataset.uiDialog = 'true';
            overlay.classList.add('ui-dialog-overlay');
            panel.tabIndex = -1;
            if (overlay.getAttribute('role') === 'dialog') {
                overlay.removeAttribute('role'); overlay.removeAttribute('aria-modal');
            }
            panel.setAttribute('role', 'dialog');
            panel.setAttribute('aria-modal', 'true');
            const title = panel.querySelector('h2, h3, h4, h5');
            if (!title.id) title.id = `ui-dialog-title-${++sequence}`;
            panel.setAttribute('aria-labelledby', title.id);
        });
        queue();
    };
    new MutationObserver(records => {
        if (records.some(record => record.target instanceof Element &&
            (record.target.matches('.ui-dialog-overlay, [data-ui-dialog]')
                || record.target.closest('.ui-dialog-overlay')))) queue();
    }).observe(document.body, { subtree: true, attributes: true, attributeFilter: ['class', 'style', 'hidden'] });
    document.addEventListener('keydown', event => {
        const panel = activePanels().at(-1);
        if (!panel) return;
        if (event.key === 'Escape' && !event.defaultPrevented) {
            const close = Array.from(panel.querySelectorAll('button')).find(button =>
                /^(tutup|batal)/i.test(button.getAttribute('aria-label') || button.textContent.trim())
                || /close/i.test(button.getAttribute('onclick') || '')
            );
            if (close && !close.disabled) { event.preventDefault(); close.click(); }
        }
        if (event.key !== 'Tab') return;
        const items = controls(panel), first = items[0], last = items.at(-1);
        if (!first) { event.preventDefault(); panel.focus(); return; }
        if (!panel.contains(document.activeElement) || document.activeElement === panel) {
            event.preventDefault(); (event.shiftKey ? last : first).focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault(); first.focus();
        } else if (event.shiftKey && document.activeElement === first) {
            event.preventDefault(); last.focus();
        }
    });
    enhance();
    return enhance;
}
