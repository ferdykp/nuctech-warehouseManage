import assert from 'node:assert/strict';
import test from 'node:test';
import { validationMessage, uiLabel } from '../../resources/js/ui.js';

test('validation provides an actionable Indonesian error for required, email and bounds', () => {
    assert.equal(validationMessage({ validity: {valueMissing: true} }, 'Nama'), 'Nama wajib diisi.');
    assert.equal(validationMessage({ type: 'email', validity: {typeMismatch: true} }, 'Email'), 'Masukkan alamat email yang valid.');
    assert.equal(validationMessage({ min: 1, validity: {rangeUnderflow: true} }, 'Jumlah'), 'Jumlah minimal 1.');
});

test('stored statuses are translated for presentation while user-entered values remain intact', () => {
    assert.equal(uiLabel('pending_station'), 'Menunggu Kepala Stasiun');
    assert.equal(uiLabel('Permanent'), 'Tetap');
    assert.equal(uiLabel('used-good'), 'Bekas Baik');
    assert.equal(uiLabel('Mesin Jakarta A'), 'Mesin Jakarta A');
});
