@extends('layout.master')

@section('title', 'Edit Branch')

@section('content')
    <div class="w-full max-w-3xl mx-auto space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between gap-3">
            <div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Edit Branch</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Update configuration for
                    {{ $branch->branch_name }}.</p>
            </div>
            <a href="{{ route('branches.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2 text-xs font-bold transition-all bg-white border text-slate-600 border-slate-200 rounded-xl hover:bg-slate-50 active:scale-95 shrink-0">
                <i class="fa-solid fa-arrow-left"></i>
                <span>Back</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-sm border-slate-200/80 rounded-2xl">
            <form action="{{ route('branches.update', $branch->id) }}" method="POST" class="p-6 space-y-5 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Name</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}"
                            class="w-full px-4 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                            required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Code</label>
                        <input type="text" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}"
                            class="w-full px-4 py-2.5 text-xs sm:text-sm uppercase border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white"
                            required>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Address</label>
                    <textarea name="branch_address" rows="3"
                        class="w-full px-4 py-2.5 text-xs sm:text-sm border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-500/10 focus:border-blue-500 outline-none transition-all bg-slate-50 focus:bg-white">{{ old('branch_address', $branch->branch_address) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                    <a href="{{ route('branches.index') }}"
                        class="px-5 py-2.5 text-xs font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 shadow-md shadow-blue-600/20 active:scale-95 transition-all">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
