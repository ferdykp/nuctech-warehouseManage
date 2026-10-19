@props(['label', 'count', 'url', 'help'])
<a href="{{ $url }}" class="ui-task-card group block rounded-2xl border border-slate-200 bg-white p-4 transition-colors hover:border-blue-400 hover:bg-blue-50/50">
    <span class="block text-sm font-semibold text-slate-700">{{ $label }}</span>
    <span class="mt-2 block text-3xl font-extrabold text-slate-900">{{ number_format($count) }}</span>
    <span class="mt-2 block text-sm leading-relaxed text-slate-600">{{ $help }}</span>
    <span class="mt-3 inline-flex items-center gap-2 text-sm font-bold text-blue-700">Buka daftar <i class="fa-solid fa-arrow-right" aria-hidden="true"></i></span>
</a>
