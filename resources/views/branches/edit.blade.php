@extends('layout.master')

@section('title', 'Edit Branch')

@section('content')
    <div class="w-full space-y-6">

        {{-- PAGE HEADER CARD --}}
        <div class="p-6 bg-white border shadow-xs sm:p-8 border-slate-200/80 rounded-3xl">
            <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                <div>
                    <div
                        class="inline-flex items-center gap-2 px-3 py-1 mb-2 text-xs font-bold text-blue-700 border border-blue-100 rounded-full bg-blue-50">
                        <i class="fa-solid fa-pen-to-square text-[10px]"></i> Edit Configuration
                    </div>
                    <h1 class="text-2xl font-extrabold tracking-tight sm:text-3xl text-slate-900">Edit Branch Details</h1>
                    <p class="text-xs sm:text-sm font-medium text-slate-500 mt-0.5">Updating configuration for <strong
                            class="text-slate-800">{{ $branch->branch_name }}</strong>.</p>
                </div>
                <a href="{{ route('branches.index') }}"
                    class="inline-flex items-center justify-center gap-2 px-4 py-2.5 text-xs font-bold text-slate-700 bg-slate-100 hover:bg-slate-200 active:scale-[0.98] transition-all shrink-0 rounded-xl cursor-pointer">
                    <i class="text-xs fa-solid fa-arrow-left"></i>
                    <span>Back to List</span>
                </a>
            </div>
        </div>

        {{-- FORM CARD --}}
        <div class="overflow-hidden bg-white border shadow-xs border-slate-200/80 rounded-3xl">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-xs font-extrabold tracking-wider uppercase text-slate-700">Branch Profile Form</h2>
            </div>

            <form action="{{ route('branches.update', $branch->id) }}" method="POST" class="p-6 space-y-6 sm:p-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                    {{-- BRANCH NAME --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Branch Name <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none z-10 text-xs">
                                <i class="fa-solid fa-building"></i>
                            </span>
                            <input type="text" name="branch_name" value="{{ old('branch_name', $branch->branch_name) }}"
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-semibold transition-all border text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                                required>
                        </div>
                        @error('branch_name')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- BRANCH CODE --}}
                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">
                            Branch Code <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative flex items-center">
                            <span
                                class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400 pointer-events-none z-10 text-xs">
                                <i class="fa-solid fa-hashtag"></i>
                            </span>
                            <input type="text" name="branch_code" value="{{ old('branch_code', $branch->branch_code) }}"
                                class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-bold uppercase transition-all border text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                                required>
                        </div>
                        @error('branch_code')
                            <p class="mt-1 text-xs font-bold text-rose-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- BRANCH ADDRESS --}}
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold tracking-wider uppercase text-slate-700">Detailed Address</label>
                    <div class="relative">
                        <span class="absolute top-3 left-3.5 text-slate-400 pointer-events-none z-10 text-xs">
                            <i class="fa-solid fa-location-dot"></i>
                        </span>
                        <textarea name="branch_address" rows="3"
                            class="w-full pl-10 pr-3.5 py-2.5 text-xs sm:text-sm font-medium transition-all border text-slate-800 bg-slate-50 border-slate-200 rounded-xl focus:bg-white focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10 focus:outline-none"
                            placeholder="Street name, Building, City...">{{ old('branch_address', $branch->branch_address) }}</textarea>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-3 pt-5 border-t border-slate-100">
                    <a href="{{ route('branches.index') }}"
                        class="px-5 py-2.5 text-xs font-bold text-slate-600 hover:text-slate-800 transition-colors cursor-pointer">
                        Cancel
                    </a>
                    <button type="submit"
                        class="px-6 py-2.5 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 active:scale-[0.98] transition-all rounded-xl shadow-md shadow-blue-600/20 cursor-pointer">
                        <i class="mr-1.5 fa-solid fa-floppy-disk"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection
