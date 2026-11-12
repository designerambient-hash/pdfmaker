@extends('layouts.app')

@section('title', 'Templates Library')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- Header banner -->
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Free Document Templates</h1>
        <p class="text-sm text-slate-500 mt-1">Kickstart your invoices, resumes, certificates, and marketing materials with professionally crafted designs.</p>
    </div>

    <!-- Category Pills & Search -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-8">
        <div class="flex items-center space-x-2 overflow-x-auto pb-2 sm:pb-0">
            <a href="{{ route('templates.index') }}" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors shrink-0 {{ !request('category') || request('category') === 'All' ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                All Templates
            </a>
            @foreach($categories as $cat)
            <a href="{{ route('templates.index', ['category' => $cat]) }}" class="px-4 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors shrink-0 {{ request('category') === $cat ? 'bg-indigo-600 text-white shadow-xs' : 'bg-white border border-slate-200 text-slate-600 hover:bg-slate-50' }}">
                {{ $cat }}
            </a>
            @endforeach
        </div>

        <form action="{{ route('templates.index') }}" method="GET" class="relative">
            @if(request('category'))
                <input type="hidden" name="category" value="{{ request('category') }}">
            @endif
            <i data-lucide="search" class="w-4 h-4 absolute left-3 top-3 text-slate-400"></i>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search templates..." class="pl-9 pr-3.5 py-2 bg-white border border-slate-200 rounded-xl text-xs sm:text-sm focus:ring-2 focus:ring-indigo-500 w-full sm:w-64">
        </form>
    </div>

    <!-- Templates Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($templates as $tpl)
        <div class="bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:shadow-xl transition-all duration-200 flex flex-col justify-between overflow-hidden group">
            
            <!-- Template Visual Preview Box -->
            <div class="relative aspect-[4/5] bg-slate-50 border-b border-slate-100 flex items-center justify-center p-6 overflow-hidden">
                <div class="w-full h-full bg-white rounded-lg shadow-sm border border-slate-200 p-4 flex flex-col justify-between group-hover:scale-102 transition-transform duration-200">
                    <div class="flex items-center space-x-2">
                        <div class="w-6 h-6 rounded bg-indigo-500/20 flex items-center justify-center text-indigo-600">
                            <i data-lucide="layout" class="w-3.5 h-3.5"></i>
                        </div>
                        <div class="w-1/2 h-2 bg-indigo-100 rounded"></div>
                    </div>
                    <div class="space-y-1.5 my-auto">
                        <div class="w-full h-1 bg-slate-100 rounded"></div>
                        <div class="w-5/6 h-1 bg-slate-100 rounded"></div>
                        <div class="w-3/4 h-1 bg-slate-100 rounded"></div>
                        <div class="w-4/5 h-1 bg-slate-100 rounded"></div>
                    </div>
                    <div class="w-1/3 h-2 bg-slate-200 rounded self-end"></div>
                </div>

                <div class="absolute inset-0 bg-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center p-4">
                    <button type="button" onclick="useTemplate({{ $tpl->id }})" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl shadow-lg transform translate-y-1 group-hover:translate-y-0 transition-all flex items-center space-x-2">
                        <i data-lucide="wand-2" class="w-4 h-4"></i>
                        <span>Customize Template</span>
                    </button>
                </div>
            </div>

            <!-- Card Body -->
            <div class="p-5">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-[10px] uppercase font-bold tracking-wider px-2 py-0.5 rounded-full bg-indigo-50 text-indigo-700 border border-indigo-100">{{ $tpl->category }}</span>
                    <span class="text-[11px] font-medium text-slate-400">{{ $tpl->page_size }}</span>
                </div>
                <h3 class="text-sm font-bold text-slate-800 mt-2 truncate" title="{{ $tpl->title }}">{{ $tpl->title }}</h3>
                <p class="text-xs text-slate-500 line-clamp-2 mt-1">{{ $tpl->description }}</p>

                <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-[11px] text-slate-400">{{ $tpl->orientation === 'landscape' ? 'Landscape' : 'Portrait' }}</span>
                    <button type="button" onclick="useTemplate({{ $tpl->id }})" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1">
                        <span>Use This</span>
                        <i data-lucide="arrow-right" class="w-3.5 h-3.5"></i>
                    </button>
                </div>
            </div>
        </div>
        @empty
        <div class="col-span-full text-center py-16">
            <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-3">
                <i data-lucide="layout-template" class="w-6 h-6"></i>
            </div>
            <h3 class="text-sm font-bold text-slate-700">No templates found</h3>
            <p class="text-xs text-slate-400 mt-1">Try changing category or clearing your search.</p>
        </div>
        @endforelse
    </div>
</div>

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    async function useTemplate(templateId) {
        try {
            window.showToast('Preparing template...', 'info');
            const res = await fetch(`/templates/${templateId}/use`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            }
        } catch(e) {
            window.showToast('Failed to create document from template', 'error');
        }
    }
</script>
@endpush
@endsection

