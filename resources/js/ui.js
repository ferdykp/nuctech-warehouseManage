// Shared presentation behaviour. Routes, stored enums and permissions stay in their modules.
export function validationMessage(field, label) {
    const v = field.validity;
    if (v.valueMissing) return `${label} wajib diisi.`;
    if (v.typeMismatch) return field.type === 'email' ? 'Masukkan alamat email yang valid.' : `${label} belum valid.`;
    if (v.rangeUnderflow) return `${label} minimal ${field.min}.`;
    if (v.rangeOverflow) return `${label} maksimal ${field.max}.`;
    if (v.tooShort) return `${label} minimal ${field.minLength} karakter.`;
    if (v.tooLong) return `${label} maksimal ${field.maxLength} karakter.`;
    if (v.badInput || v.stepMismatch) return `${label} harus berupa angka yang valid.`;
    if (v.patternMismatch) return `${label} belum sesuai format yang diminta.`;
    return `${label} belum valid. Periksa kembali isian Anda.`;
}

export function uiLabel(value) {
    const labels = {
        pending: 'Menunggu Tanda Tangan Pengaju', pending_leader: 'Menunggu Ketua Tim',
        pending_station: 'Menunggu Kepala Stasiun', pending_manager: 'Menunggu Manajer',
        approved: 'Disetujui', rejected: 'Ditolak', new: 'Baru', used: 'Bekas Baik',
        'used-good': 'Bekas Baik', used_good: 'Bekas Baik', damaged: 'Rusak', repaired: 'Hasil Perbaikan', repair: 'Perbaikan',
        transportation: 'Transportasi', delivery: 'Pengiriman', office: 'Perlengkapan Kantor',
        active: 'Aktif', inactive: 'Tidak Aktif', permanent: 'Tetap', contract: 'Kontrak',
        probation: 'Masa Percobaan', daily: 'Harian', resigned: 'Berhenti Bekerja',
        in: 'Stok Masuk', out: 'Stok Keluar', transfer: 'Pemindahan', adjustment: 'Penyesuaian',
        'regular salary': 'Gaji Reguler', '1st probation': 'Masa Percobaan 1', '2nd probation': 'Masa Percobaan 2', '3rd probation': 'Masa Percobaan 3',
    };
    return labels[String(value ?? '').toLowerCase()] ?? value ?? '-';
}

export function initUi() {
    window.uiLabel = uiLabel;
    let nextId = 0;
    const newId = prefix => `${prefix}-${++nextId}`;
    const visible = element => element.getClientRects().length > 0 && getComputedStyle(element).visibility !== 'hidden';
    const fieldLabel = field => (field.labels?.[0]?.textContent || field.getAttribute('aria-label') || 'Kolom ini').replace(/\s+/g, ' ').replace(/\*/g, '').trim();
    const clearError = field => {
        if (!field.matches('input, select, textarea')) return;
        field.setCustomValidity('');
        field.removeAttribute('aria-invalid');
        const errorId = field.dataset.uiError;
        if (errorId) {
            document.getElementById(errorId)?.remove();
            field.setAttribute('aria-describedby', (field.getAttribute('aria-describedby') || '').split(' ').filter(id => id !== errorId).join(' '));
            delete field.dataset.uiError;
        }
    };
    const showError = (field, message) => {
        clearError(field);
        const error = document.createElement('p');
        error.id = newId('field-error');
        error.className = 'ui-field-error';
        error.textContent = message;
        const anchor = field.parentElement.matches('.relative') ? field.parentElement : field;
        anchor.insertAdjacentElement('afterend', error);
        field.dataset.uiError = error.id;
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', `${field.getAttribute('aria-describedby') || ''} ${error.id}`.trim());
    };

    window.showUiMessage = (message, type = 'error') => {
        let panel = document.getElementById('ui-message');
        if (!panel) {
            panel = document.createElement('div');
            panel.id = 'ui-message';
            panel.className = 'ui-message';
            panel.setAttribute('role', 'alert');
            document.body.append(panel);
        }
        panel.replaceChildren();
        const text = document.createElement('span');
        text.textContent = message;
        const close = document.createElement('button');
        close.type = 'button'; close.textContent = 'Tutup';
        close.addEventListener('click', () => panel.remove());
        panel.dataset.type = type;
        panel.append(text, close);
    };

    function enhance() {
        document.querySelectorAll('form').forEach((form, index) => {
            if (!form.dataset.uiKey) form.dataset.uiKey = form.id || `form-${index}`;
            if (form.method.toLowerCase() === 'post' && !form.querySelector('[name="_ui_form"]')) {
                const marker = document.createElement('input');
                marker.type = 'hidden'; marker.name = '_ui_form'; marker.value = form.dataset.uiKey;
                form.append(marker);
            }
            form.querySelectorAll('input:not([type="hidden"]), select, textarea').forEach(field => {
                if (field.dataset.uiLabel) return;
                field.dataset.uiLabel = 'true';
                const label = field.closest('label')
                    || Array.from(field.parentElement.children).find(e => e.tagName === 'LABEL')
                    || Array.from(field.parentElement.parentElement?.children || []).find(e => e.tagName === 'LABEL');
                if (label && !label.contains(field) && !label.htmlFor) {
                    if (!field.id) field.id = newId('field');
                    label.htmlFor = field.id;
                }
                if (field.required && label && !label.textContent.includes('*')) {
                    const marker = document.createElement('span');
                    marker.className = 'ui-required'; marker.textContent = ' *'; marker.setAttribute('aria-hidden', 'true');
                    label.append(marker);
                }
            });

        });
        document.querySelectorAll('table[data-mobile-cards]').forEach(table => {
            const headers = Array.from(table.tHead?.rows[0]?.cells || []).map(cell => cell.textContent.replace(/\s+/g, ' ').trim());
            Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
                Array.from(row.cells).forEach((cell, index) => {
                    if (!cell.dataset.label) cell.dataset.label = headers[index] || 'Pilihan';
                    if (cell.colSpan > 1) cell.dataset.fullWidth = 'true';
                });
            }));
            table.dataset.uiCards = 'ready';
        });
        document.querySelectorAll('#main-content .overflow-x-auto').forEach(container => {
            const table = container.querySelector('table');
            if (!table || table.hasAttribute('data-mobile-cards') || container.dataset.uiScroll) return;
            container.dataset.uiScroll = 'true';
            container.tabIndex = 0;
            container.setAttribute('role', 'region');
            container.setAttribute('aria-label', 'Tabel yang dapat digeser ke samping');
            const hint = document.createElement('p');
            hint.className = 'ui-scroll-hint';
            hint.textContent = 'Geser tabel ke samping untuk melihat semua kolom.';
            container.before(hint);
        });
        document.querySelectorAll('.fixed.inset-0').forEach(overlay => {
            const panel = Array.from(overlay.children).find(child => child.matches('div') && child.querySelector('h3') && !child.classList.contains('fixed'));
            if (!panel || panel.dataset.uiDialog) return;
            panel.dataset.uiDialog = 'true';
            panel.setAttribute('role', 'dialog'); panel.setAttribute('aria-modal', 'true');
            const title = panel.querySelector('h3');
            if (!title.id) title.id = newId('dialog-title');
            panel.setAttribute('aria-labelledby', title.id);
            panel.querySelectorAll('button').forEach(button => {
                if (!button.textContent.trim() && !button.getAttribute('aria-label')) {
                    if (button.querySelector('.fa-xmark, .fa-times')) button.setAttribute('aria-label', 'Tutup dialog');
                }
            });
        });
    }
    enhance();
    const desktopFilters = window.matchMedia('(min-width: 768px)');
    const syncFilters = () => document.querySelectorAll('.ui-filter-details').forEach(details => {
        if (desktopFilters.matches) {
            details.dataset.mobileOpen = String(details.open);
            details.open = true;
        } else if (details.dataset.mobileOpen !== undefined) {
            details.open = details.dataset.mobileOpen === 'true';
        }
    });
    syncFilters();
    desktopFilters.addEventListener('change', syncFilters);
    // Keep only routing metadata in this tab; entered values come from Laravel's old input.
    let submittedForm;
    try {
        submittedForm = JSON.parse(sessionStorage.getItem('ui-submitted-form') || 'null');
        sessionStorage.removeItem('ui-submitted-form');
    } catch { /* Storage may be disabled by the browser. */ }
    let queued = false;
    new MutationObserver(records => {
        if (queued || !records.some(r => r.addedNodes.length)) return;
        queued = true;
        requestAnimationFrame(() => { queued = false; enhance(); });
    }).observe(document.body, { childList: true, subtree: true });

    document.addEventListener('invalid', event => {
        const field = event.target;
        const message = validationMessage(field, fieldLabel(field));
        showError(field, message);
        field.setCustomValidity(message);
    }, true);
    document.addEventListener('input', event => clearError(event.target));
    document.addEventListener('change', event => clearError(event.target));

    const errors = document.getElementById('validation-errors');
    if (errors) {
        const messages = JSON.parse(errors.textContent);
        const old = JSON.parse(document.getElementById('validation-old-input')?.textContent || '{}');
        let restoreForm = Array.from(document.forms).find(form => form.dataset.uiKey === old._ui_form);
        if (restoreForm && !restoreForm.getAttribute('action')) {
            const saved = submittedForm;
            if (saved?.page === location.href && saved?.key === old._ui_form
                && new URL(saved.action, location.href).origin === location.origin) {
                restoreForm.action = saved.action;
            } else {
                // An edit dialog with no record URL must be reopened from its own edit button.
                restoreForm = null;
            }
        }
        if (restoreForm) {
            restoreForm.querySelectorAll('input, select, textarea').forEach(field => {
                if (['password', 'file', 'hidden'].includes(field.type)) return;
                const parts = field.name.replace(/\]/g, '').split('[');
                let value = old;
                for (const part of parts) { if (part) value = value?.[part]; }
                if (value === undefined || value === null) return;
                if (['radio', 'checkbox'].includes(field.type)) field.checked = (Array.isArray(value) ? value.map(String) : [String(value)]).includes(field.value);
                else if (field.multiple) Array.from(field.options).forEach(option => { option.selected = (Array.isArray(value) ? value.map(String) : [String(value)]).includes(option.value); });
                else field.value = String(value);
                field.dispatchEvent(new Event('input', { bubbles: true }));
            });
            const overlay = restoreForm.closest('.fixed.inset-0');
            if (overlay) {
                const toggle = overlay.getAttribute('x-show');
                if (toggle && /^[a-zA-Z_$][\w$]*$/.test(toggle) && window.Alpine) window.Alpine.$data(overlay)[toggle] = true;
                else { overlay.classList.remove('hidden', 'invisible', 'opacity-0'); overlay.classList.add('flex'); }
                overlay.querySelectorAll('.scale-95, .translate-y-4, .opacity-0').forEach(element => {
                    element.classList.remove('scale-95', 'translate-y-4', 'opacity-0');
                });
            }
        }
        Object.entries(messages).forEach(([name, list]) => {
            const inputName = name.replace(/\.([^.]*)/g, '[$1]');
            const candidates = Array.from((restoreForm || document).querySelectorAll('input, select, textarea')).filter(field => field.name === inputName);
            const field = candidates.find(visible) || candidates[0];
            if (field) showError(field, list.join(' '));
        });
        if (restoreForm) restoreForm.querySelector('[aria-invalid="true"]')?.focus();
        else document.getElementById('validation-summary')?.focus();
    }

    // Native POST navigation only. AJAX forms retain their own loading/finally lifecycle.
    document.addEventListener('submit', event => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.method.toLowerCase() !== 'post') return;
        if (form.dataset.uiSubmitting === 'true') { event.preventDefault(); return; }
        try {
            sessionStorage.setItem('ui-submitted-form', JSON.stringify({
                page: location.href, key: form.dataset.uiKey, action: form.action,
            }));
        } catch { /* The form still works when browser storage is unavailable. */ }
        setTimeout(() => {
            if (event.defaultPrevented) return;
            form.dataset.uiSubmitting = 'true';
            form.querySelectorAll('button[type="submit"], input[type="submit"]').forEach(button => {
                button.dataset.uiOriginal = button.tagName === 'INPUT' ? button.value : button.innerHTML;
                button.disabled = true; button.setAttribute('aria-busy', 'true');
                if (button.tagName === 'INPUT') button.value = 'Memproses…'; else button.textContent = 'Memproses…';
            });
        }, 0);
    });
    window.addEventListener('pageshow', () => {
        document.querySelectorAll('[data-ui-submitting]').forEach(form => delete form.dataset.uiSubmitting);
        document.querySelectorAll('[data-ui-original]').forEach(button => {
            button.disabled = false; button.removeAttribute('aria-busy');
            if (button.tagName === 'INPUT') button.value = button.dataset.uiOriginal; else button.innerHTML = button.dataset.uiOriginal;
            delete button.dataset.uiOriginal;
        });
    });
    document.addEventListener('keydown', event => {
        if (event.key !== 'Tab') return;
        const panels = Array.from(document.querySelectorAll('[data-ui-dialog]')).filter(visible);
        const panel = panels.at(-1);
        if (!panel) return;
        const controls = Array.from(panel.querySelectorAll('a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]')).filter(visible);
        if (!controls.length) return;
        const first = controls[0], last = controls.at(-1);
        if (!panel.contains(document.activeElement) || (!event.shiftKey && document.activeElement === last)) { event.preventDefault(); first.focus(); }
        else if (event.shiftKey && document.activeElement === first) { event.preventDefault(); last.focus(); }
    });
}
