export function enhanceMonthPickers(root = document) {
    root.querySelectorAll('[data-month-picker]').forEach(picker => {
        if (picker.dataset.monthReady) return;
        picker.dataset.monthReady = 'true';
        const month = picker.querySelector('[data-month-part="month"]');
        const year = picker.querySelector('[data-month-part="year"]');
        const raw = picker.querySelector('[data-month-value]');
        const sync = () => {
            const required = picker.dataset.required === 'true' || Boolean(month.value || year.value);
            month.required = year.required = required;
            for (const option of month.options) {
                option.disabled = Boolean(option.value && year.value && picker.dataset.min && `${year.value}-${option.value}` < picker.dataset.min);
            }
            if (month.selectedOptions[0]?.disabled) month.value = '';
            raw.value = month.value && year.value ? `${year.value}-${month.value}` : '';
        };
        picker.addEventListener('change', sync);
        picker.addEventListener('input', sync);
        sync();
    });
}
