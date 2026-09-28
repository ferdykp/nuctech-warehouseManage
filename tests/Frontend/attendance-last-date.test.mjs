import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import assert from 'node:assert/strict';
import test from 'node:test';

function page() {
    const blade = readFileSync(new URL('../../resources/views/attendance/index.blade.php', import.meta.url), 'utf8');
    const script = blade.match(/<script>([\s\S]*?)<\/script>/)[1].replace('@json($holidays ?? [])', '{}');
    const classes = { add() {}, remove() {}, contains() { return true; } };
    const fields = Object.fromEntries(['filter_bulan', 'filter_tahun', 'auto_full_hidden', 'counter_s1_1', 'counter_s2_1', 'counter_s3_1', 'raw_data_1', 'modalEmployeeName', 'calendarGridBody', 'plotCalendarModal'].map(id => [id, { value: '', innerHTML: '', classList: classes }]));
    fields.filter_bulan.value = '09';
    fields.filter_tahun.value = '2026';
    const context = vm.createContext({ console, document: {
        getElementById: id => fields[id] ?? null,
        querySelectorAll: () => [],
        body: { classList: classes },
    }});
    vm.runInContext(script, context);
    context.attendanceState[1] = {
        name: 'Employee A', lastDate: '2026-09-11',
        scheduled: { 11: { s1: 0, s2: 1, s3: 0 }, 14: { s1: 0, s2: 1, s3: 0 } },
        shifts: Object.fromEntries(Array.from({length: 30}, (_, i) => [i + 1, { s1: 0, s2: 0, s3: 0 }])),
    };
    return { context, fields };
}

test('auto-fill keeps the final shift and leaves unscheduled/post-resignation dates empty', () => {
    const { context, fields } = page();
    context.toggleManualInput(true);
    const matrix = JSON.parse(fields.raw_data_1.value);
    assert.equal(matrix[11].s2, 1);
    assert.equal(matrix[10].s1, 0);
    assert.equal(matrix[14].s2, 0);
    assert.equal(fields.counter_s2_1.value, 1);
});

test('calendar marks dates after Last Date N/A and manual toggles cannot add attendance', () => {
    const { context, fields } = page();
    context.toggleManualInput(true);
    context.openPlotCalendar(1);
    assert.match(fields.calendarGridBody.innerHTML, /Date 12<\/td><td[^>]+>N\/A — After Last Date/);
    assert.match(fields.calendarGridBody.innerHTML, /toggleDateShift\(11, 's2'/);
    assert.doesNotMatch(fields.calendarGridBody.innerHTML, /toggleDateShift\(12,/);
    context.toggleDateShift(12, 's1', true);
    assert.equal(context.attendanceState[1].shifts[12].s1, 0);
    context.toggleDateShift(11, 's2', false);
    assert.equal(context.attendanceState[1].shifts[11].s2, 0);
});

test('employee without Last Date retains scheduled shifts later in the month', () => {
    const { context, fields } = page();
    context.attendanceState[1].lastDate = null;
    context.toggleManualInput(true);
    assert.equal(JSON.parse(fields.raw_data_1.value)[14].s2, 1);
    assert.equal(fields.counter_s2_1.value, 2);
});
