const iconLabels = [
    ['.fa-eye', 'Lihat rincian'], ['.fa-pen-to-square, .fa-edit, .fa-pencil', 'Edit'],
    ['.fa-trash, .fa-trash-can', 'Hapus'], ['.fa-file-pdf', 'Unduh PDF'],
    ['.fa-file-excel', 'Unduh Excel'], ['.fa-download', 'Unduh'],
    ['.fa-rotate-left, .fa-trash-arrow-up', 'Pulihkan'],
    ['.fa-xmark, .fa-times', 'Tutup dialog'], ['.fa-check-double', 'Setujui'],
    ['.fa-pen-nib', 'Tanda tangani'],
];

export function enhanceActions(root = document) {
    root.querySelectorAll('button, a').forEach(control => {
        if (control.dataset.uiAction) return;
        control.dataset.uiAction = 'true';
        control.querySelectorAll('i[class*="fa-"]').forEach(icon => icon.setAttribute('aria-hidden', 'true'));
        if (control.textContent.trim()) return;
        const label = control.getAttribute('aria-label') || control.getAttribute('title')
            || iconLabels.find(([selector]) => control.querySelector(selector))?.[1];
        if (!label) return;
        control.setAttribute('aria-label', label);
        if (!control.title) control.title = label;
        control.classList.add('ui-icon-action');
        // Keep close and navigation controls compact; row actions get readable touch labels.
        if (control.closest('td, [data-ui-row-actions]') && label !== 'Tutup dialog') {
            const text = document.createElement('span');
            text.className = 'ui-action-text';
            text.textContent = label;
            control.append(text);
        }
    });
}
