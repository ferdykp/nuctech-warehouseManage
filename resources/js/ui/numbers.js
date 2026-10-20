/** Indonesian presentation only: identifiers and submitted numeric values stay unchanged. */
export function formatNumber(value) {
    const number = Number(value);
    return Number.isFinite(number) ? new Intl.NumberFormat('id-ID', {minimumFractionDigits: Number.isInteger(Math.round(number * 100) / 100) ? 0 : 2, maximumFractionDigits: 2}).format(number) : '0';
}

export function formatRupiah(value) {
    return `Rp ${formatNumber(value)}`;
}

export function parseRupiahInput(value) {
    const text = String(value).trim().replace(/^Rp\s*/i, '').replace(/\s/g, '');
    if (!text) return '';
    if (!/^\d[\d.]*(,\d{0,2})?$/.test(text)) return null;
    const [whole, decimals] = text.replace(/\./g, '').split(',');
    return whole.replace(/^0+(?=\d)/, '') + (decimals !== undefined && decimals !== '' ? `.${decimals}` : '');
}

export function enhanceMoneyInputs(root = document) {
    root.querySelectorAll('[data-money-target]').forEach(input => {
        if (input.dataset.moneyReady) return;
        const raw = input.form?.querySelector(`#${input.dataset.moneyTarget}`);
        if (!raw) return;
        input.dataset.moneyReady = 'true';
        if (raw.value !== '') input.value = formatNumber(raw.value);
        input.addEventListener('input', () => {
            const caret = input.selectionStart ?? input.value.length;
            const digitsToRight = input.value.slice(caret).replace(/\D/g, '').length;
            const canonical = parseRupiahInput(input.value);
            raw.value = canonical ?? '';
            if (canonical === null || canonical === '') return;
            const comma = input.value.includes(',');
            const decimalPart = comma ? input.value.split(',')[1] : null;
            input.value = formatNumber(canonical.split('.')[0]) + (comma ? `,${decimalPart}` : '');
            let position = input.value.length;
            let remaining = digitsToRight;
            while (position > 0 && remaining > 0) {
                if (/\d/.test(input.value[--position])) remaining--;
            }
            input.setSelectionRange(position, position);
        });
        input.addEventListener('blur', () => {
            if (raw.value !== '') input.value = formatNumber(raw.value);
        });
    });
}
