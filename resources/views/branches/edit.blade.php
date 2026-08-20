@extends('layout.master')

@section('title', 'Edit Branch')

@section('content')
    <div class="max-w-3xl mx-auto space-y-6">

        {{-- PAGE HEADER --}}
        <div class="flex items-center justify-between gap-4">
            <div>
                <div
                    class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                    <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit Configuration
                </div>
                <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Edit Branch</h1>
                <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Update details for <strong
                        class="text-slate-800">{{ $branch->branch_name }}</strong>.</p>
            </div>
            <a href="{{ route('branches.index') }}"
                class="inline-flex items-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 active:scale-[0.98] transition-all shrink-0 shadow-2xs">
                <i class="text-xs fa-solid fa-arrow-left"></i>
                <span>Back to List</span>
            </a>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <form action="{{ route('branches.update', $branch->id) }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Name</label>
                        <input type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}"
                            class="w-full px-4 py-3 text-xs font-semibold transition-all border sm:text-sm text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                            required>
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Branch Code</label>
                        <input type="text" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}"
                            class="w-full px-4 py-3 text-xs font-bold uppercase transition-all border sm:text-sm text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                            required>
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Detailed Address</label>
                    <textarea name="branch_address" rows="4"
                        class="w-full px-4 py-3 text-xs font-medium transition-all border sm:text-sm text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none">{{ old('branch_address', $branch->branch_address) }}</textarea>
                </div>

                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">
                    <a href="{{ route('branches.index') }}"
                        class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-blue-600/20">
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
