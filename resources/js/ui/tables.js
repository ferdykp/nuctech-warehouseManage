function checkboxHitArea(input) {
    if (input.closest('label')) return;
    const label = document.createElement('label');
    label.className = 'ui-checkbox-hit';
    input.before(label);
    label.append(input);
}

export function enhanceTables() {
    document.querySelectorAll('#main-content table').forEach(table => {
        const headers = Array.from(table.tHead?.rows[0]?.cells || []).map(cell => cell.textContent.replace(/\s+/g, ' ').trim());
        table.tHead?.querySelectorAll('th').forEach(cell => { if (!cell.scope) cell.scope = cell.colSpan > 1 ? 'colgroup' : 'col'; });
        table.tHead?.querySelectorAll('input[type="checkbox"]').forEach(input => {
            if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', 'Pilih semua baris di halaman ini');
            checkboxHitArea(input);
        });
        Array.from(table.tBodies).forEach(body => Array.from(body.rows).forEach(row => {
            const identity = Array.from(row.cells).map(cell => cell.textContent.replace(/\s+/g, ' ').trim()).find(Boolean) || 'data ini';
            Array.from(row.cells).forEach((cell, index) => {
                cell.querySelectorAll('input[type="checkbox"]').forEach(input => {
                    if (!input.getAttribute('aria-label')) input.setAttribute('aria-label', `${headers[index] || 'Pilih baris'}: ${identity}`);
                    checkboxHitArea(input);
                });
                if (!table.hasAttribute('data-mobile-cards')) return;
                if (!cell.dataset.label) cell.dataset.label = headers[index] || 'Pilihan';
                if (cell.colSpan > 1) cell.dataset.fullWidth = 'true';
            });
        }));
        if (table.hasAttribute('data-mobile-cards')) table.dataset.uiCards = 'ready';
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
        container.prepend(hint);
    });
}
