@props(['id', 'name', 'value' => null, 'required' => false, 'min' => null])
@php
    $selectedYear = $value ? substr($value, 0, 4) : '';
    $selectedMonth = $value ? substr($value, 5, 2) : '';
    $firstYear = $min ? (int) substr($min, 0, 4) : min(2000, (int) ($selectedYear ?: 2000));
    $lastYear = max(now()->year + 20, (int) ($selectedYear ?: 0));
    $months = [1=>'Januari',2=>'Februari',3=>'Maret',4=>'April',5=>'Mei',6=>'Juni',7=>'Juli',8=>'Agustus',9=>'September',10=>'Oktober',11=>'November',12=>'Desember'];
@endphp
<div class="grid grid-cols-2 gap-2 min-w-0" data-month-picker data-required="{{ $required ? 'true' : 'false' }}" data-min="{{ $min }}">
    <select id="{{ $id }}" name="{{ $name }}_parts[month]" data-month-part="month" aria-label="Bulan" @required($required) class="w-full min-w-0 border-slate-300 rounded-xl">
        <option value="">Pilih bulan</option>
        @foreach($months as $number=>$label)<option value="{{ sprintf('%02d', $number) }}" @selected((int) $selectedMonth === $number)>{{ $label }}</option>@endforeach
    </select>
    <select name="{{ $name }}_parts[year]" data-month-part="year" aria-label="Tahun" @required($required) class="w-full min-w-0 border-slate-300 rounded-xl">
        <option value="">Pilih tahun</option>
        @for($year=$firstYear; $year<=$lastYear; $year++)<option value="{{ $year }}" @selected((string) $selectedYear === (string) $year)>{{ $year }}</option>@endfor
    </select>
    <input type="hidden" name="{{ $name }}" value="{{ $value }}" data-month-value>
</div>
