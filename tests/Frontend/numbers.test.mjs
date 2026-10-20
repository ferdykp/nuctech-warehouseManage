import assert from 'node:assert/strict';
import test from 'node:test';
import { formatNumber, formatRupiah, parseRupiahInput } from '../../resources/js/ui/numbers.js';

test('Indonesian display formats thousands, zero, decimals and currency', () => {
    assert.equal(formatRupiah(1000000), 'Rp 1.000.000');
    assert.equal(formatNumber(1234567.5), '1.234.567,50');
    assert.equal(formatNumber(0), '0');
    assert.equal(formatRupiah(0.01), 'Rp 0,01');
});

test('formatted rupiah input submits unformatted canonical amounts without losing cents', () => {
    assert.equal(parseRupiahInput('Rp 1.000.000,50'), '1000000.50');
    assert.equal(parseRupiahInput('1000000'), '1000000');
    assert.equal(parseRupiahInput('0,01'), '0.01');
    assert.equal(parseRupiahInput('1.000,'), '1000');
    assert.equal(parseRupiahInput(''), '');
    assert.equal(parseRupiahInput('00010'), '10');
    assert.equal(parseRupiahInput('100,123'), null);
    assert.equal(parseRupiahInput('abc'), null);
    assert.equal(parseRupiahInput('-1000'), null);
});
