@extends('layouts.app')

@section('title', 'Documents Dashboard')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- Top Banner / Quick Start Templates Bar -->
    <div class="mb-10">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h1 class="text-2xl font-black text-slate-900 tracking-tight">Create & Design</h1>
                <p class="text-sm text-slate-500">Start with a blank canvas or jump straight in with a free template.</p>
            </div>
            <a href="{{ route('templates.index') }}" class="text-xs font-semibold text-indigo-600 hover:text-indigo-700 flex items-center space-x-1 group">
                <span>View all templates</span>
                <i data-lucide="chevron-right" class="w-3.5 h-3.5 group-hover:translate-x-0.5 transition-transform"></i>
            </a>
        </div>

        <!-- Quick Start Cards Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-3.5">
            <!-- Blank Canvas Card -->
            <button type="button" onclick="window.openCreateModal()" class="flex flex-col items-center justify-center p-4 bg-white hover:bg-indigo-50/50 border-2 border-dashed border-indigo-200 hover:border-indigo-400 rounded-2xl group transition-all text-center">
                <div class="w-12 h-12 rounded-xl bg-indigo-50 group-hover:bg-indigo-600 group-hover:text-white text-indigo-600 flex items-center justify-center mb-3 transition-colors">
                    <i data-lucide="plus" class="w-6 h-6"></i>
                </div>
                <span class="text-xs font-bold text-slate-800">Blank Document</span>
                <span class="text-[10px] text-slate-400 mt-0.5">Custom or Preset</span>
            </button>

            <!-- Preloaded Templates -->
            @foreach($templates->take(5) as $tpl)
            <div class="bg-white rounded-2xl border border-slate-200 p-3 hover:shadow-md hover:border-indigo-200 transition-all flex flex-col justify-between group">
                <div class="h-20 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center p-2 mb-2 relative">
                    <div class="w-full h-full bg-white rounded shadow-2xs border border-slate-200/80 p-1 flex flex-col justify-between">
                        <div class="w-2/3 h-1 bg-indigo-200 rounded"></div>
                        <div class="space-y-1">
                            <div class="w-full h-0.5 bg-slate-200 rounded"></div>
                            <div class="w-4/5 h-0.5 bg-slate-200 rounded"></div>
                        </div>
                        <div class="w-1/3 h-1 bg-slate-200 rounded self-end"></div>
                    </div>
                </div>
                <div>
                    <h4 class="text-xs font-bold text-slate-800 truncate" title="{{ $tpl->title }}">{{ $tpl->title }}</h4>
                    <span class="text-[10px] text-slate-400">{{ $tpl->category }}</span>
                </div>
                <button type="button" onclick="useTemplate({{ $tpl->id }})" class="mt-2.5 w-full py-1.5 text-[11px] font-semibold text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors flex items-center justify-center space-x-1">
                    <span>Use Design</span>
                </button>
            </div>
            @endforeach
        </div>
    </div>

    <!-- Main Workspace Section: Tabs + Filter Toolbar + Documents List -->
    <div class="bg-white rounded-2xl border border-slate-200 shadow-2xs overflow-hidden">

        <!-- Tabs & Search Toolbar -->
        <div class="p-4 sm:p-6 border-b border-slate-200 flex flex-col w-full lg:items-center justify-between gap-4">
            
            <!-- Document Categories Navigation -->
            <div class="flex items-center space-x-1 sm:space-x-2 overflow-x-auto pb-1 lg:pb-0">
                <a href="{{ route('dashboard', ['tab' => 'all', 'view' => $view, 'sort' => $sort, 'order' => $order]) }}" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors flex items-center space-x-2 shrink-0 {{ $tab === 'all' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="files" class="w-4 h-4"></i>
                    <span>All Documents</span>
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'all' ? 'bg-indigo-500 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $totalActive }}</span>
                </a>

                <a href="{{ route('dashboard', ['tab' => 'recent', 'view' => $view, 'sort' => $sort, 'order' => $order]) }}" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors flex items-center space-x-2 shrink-0 {{ $tab === 'recent' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="clock" class="w-4 h-4"></i>
                    <span>Recent</span>
                </a>

                <a href="{{ route('dashboard', ['tab' => 'starred', 'view' => $view, 'sort' => $sort, 'order' => $order]) }}" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors flex items-center space-x-2 shrink-0 {{ $tab === 'starred' ? 'bg-indigo-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="star" class="w-4 h-4"></i>
                    <span>Starred</span>
                    @if($totalStarred > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'starred' ? 'bg-indigo-500 text-white' : 'bg-amber-100 text-amber-700' }}">{{ $totalStarred }}</span>
                    @endif
                </a>

                <a href="{{ route('dashboard', ['tab' => 'trash', 'view' => $view, 'sort' => $sort, 'order' => $order]) }}" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold transition-colors flex items-center space-x-2 shrink-0 {{ $tab === 'trash' ? 'bg-rose-600 text-white shadow-xs' : 'text-slate-600 hover:bg-slate-100' }}">
                    <i data-lucide="trash-2" class="w-4 h-4"></i>
                    <span>Trash</span>
                    @if($totalTrash > 0)
                    <span class="px-1.5 py-0.2 rounded-full text-[10px] {{ $tab === 'trash' ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-700' }}">{{ $totalTrash }}</span>
                    @endif
                </a>

                @if($tab === 'trash' && $totalTrash > 0)
                <button type="button" onclick="emptyTrashConfirm()" class="px-3.5 py-2 rounded-xl text-xs sm:text-sm font-semibold text-rose-700 bg-rose-50 hover:bg-rose-100 border border-rose-200 transition-colors flex items-center space-x-1.5 shadow-2xs shrink-0">
                    <i data-lucide="trash-2" class="w-4 h-4 text-rose-600"></i>
                    <span>Empty Trash</span>
                </button>
                @endif
            </div>

            <!-- Filters, Search & View Controls -->
            <div class="flex items-center flex-wrap gap-2.5">
                <!-- Search Input Form -->
                <form action="{{ route('dashboard') }}" method="GET" class="relative">
                    <input type="hidden" name="tab" value="{{ $tab }}">
                    <input type="hidden" name="view" value="{{ $view }}">
                    <input type="hidden" name="sort" value="{{ $sort }}">
                    <input type="hidden" name="order" value="{{ $order }}">
                    <i data-lucide="search" class="w-4 h-4 absolute left-3 top-3 text-slate-400"></i>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search documents..." class="pl-9 pr-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl text-xs sm:text-sm focus:bg-white focus:ring-2 focus:ring-indigo-500 w-48 sm:w-60">
                </form>

                <!-- Sorting Dropdown -->
                <div class="flex items-center space-x-1 bg-slate-50 p-1 border border-slate-200 rounded-xl">
                    <a href="{{ route('dashboard', ['tab' => $tab, 'view' => $view, 'sort' => 'updated_at', 'order' => $sort === 'updated_at' && $order === 'desc' ? 'asc' : 'desc', 'search' => $search]) }}" title="Sort by Modified Date" class="p-1.5 rounded-lg text-xs font-medium {{ $sort === 'updated_at' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                        <span class="hidden sm:inline">Modified</span>
                        <i data-lucide="{{ $sort === 'updated_at' && $order === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3.5 h-3.5 inline"></i>
                    </a>
                    <a href="{{ route('dashboard', ['tab' => $tab, 'view' => $view, 'sort' => 'title', 'order' => $sort === 'title' && $order === 'asc' ? 'desc' : 'asc', 'search' => $search]) }}" title="Sort by Title" class="p-1.5 rounded-lg text-xs font-medium {{ $sort === 'title' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                        <span class="hidden sm:inline">Name</span>
                        <i data-lucide="{{ $sort === 'title' && $order === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3.5 h-3.5 inline"></i>
                    </a>
                    <a href="{{ route('dashboard', ['tab' => $tab, 'view' => $view, 'sort' => 'created_at', 'order' => $sort === 'created_at' && $order === 'desc' ? 'asc' : 'desc', 'search' => $search]) }}" title="Sort by Creation Date" class="p-1.5 rounded-lg text-xs font-medium {{ $sort === 'created_at' ? 'bg-white text-indigo-700 shadow-2xs' : 'text-slate-500 hover:text-slate-800' }}">
                        <span class="hidden sm:inline">Created</span>
                        <i data-lucide="{{ $sort === 'created_at' && $order === 'asc' ? 'arrow-up' : 'arrow-down' }}" class="w-3.5 h-3.5 inline"></i>
                    </a>
                </div>

                <!-- View Switcher (Grid vs List) -->
                <div class="flex items-center space-x-1 bg-slate-50 p-1 border border-slate-200 rounded-xl">
                    <a href="{{ route('dashboard', ['tab' => $tab, 'view' => 'grid', 'sort' => $sort, 'order' => $order, 'search' => $search]) }}" class="p-1.5 rounded-lg {{ $view === 'grid' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-400 hover:text-slate-600' }}" title="Grid View">
                        <i data-lucide="layout-grid" class="w-4 h-4"></i>
                    </a>
                    <a href="{{ route('dashboard', ['tab' => $tab, 'view' => 'list', 'sort' => $sort, 'order' => $order, 'search' => $search]) }}" class="p-1.5 rounded-lg {{ $view === 'list' ? 'bg-white text-indigo-600 shadow-2xs' : 'text-slate-400 hover:text-slate-600' }}" title="List View">
                        <i data-lucide="list" class="w-4 h-4"></i>
                    </a>
                </div>

                @if($documents->isNotEmpty())
                <!-- Select All Control -->
                <div class="flex items-center pl-1 sm:pl-2 sm:border-l sm:border-slate-200">
                    <label class="flex items-center space-x-1.5 text-xs font-semibold text-slate-600 cursor-pointer select-none bg-slate-50 hover:bg-slate-100 border border-slate-200 px-2.5 py-1.5 rounded-xl transition">
                        <input type="checkbox" id="select-all-toggle" onchange="toggleSelectAllDocs(this.checked)" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer">
                        <span class="hidden sm:inline">Select All</span>
                    </label>
                </div>
                @endif
            </div>
        </div>

        <!-- Documents Container -->
        <div class="p-6">
            @if($documents->isEmpty())
                <!-- Empty State -->
                <div class="text-center py-16 px-4">
                    <div class="w-16 h-16 rounded-2xl bg-indigo-50 text-indigo-500 flex items-center justify-center mx-auto mb-4">
                        <i data-lucide="{{ $tab === 'trash' ? 'trash' : 'file-question' }}" class="w-8 h-8"></i>
                    </div>
                    <h3 class="text-base font-bold text-slate-800">
                        @if($tab === 'trash')
                            Your trash is empty
                        @elseif($tab === 'starred')
                            No starred documents yet
                        @elseif(!empty($search))
                            No documents match "{{ $search }}"
                        @else
                            No documents created yet
                        @endif
                    </h3>
                    <p class="text-sm text-slate-500 max-w-sm mx-auto mt-1 mb-6">
                        @if($tab === 'trash')
                            Items moved to trash will appear here before being permanently removed.
                        @elseif($tab === 'starred')
                            Star important documents in your workspace for one-click access.
                        @else
                            Start building your first custom design or import an existing PDF file.
                        @endif
                    </p>
                    @if($tab !== 'trash')
                    <div class="flex items-center justify-center space-x-3">
                        <button type="button" onclick="window.openCreateModal()" class="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
                            Create First Document
                        </button>
                        <button type="button" onclick="window.openImportModal()" class="px-4 py-2 text-sm font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                            Import PDF
                        </button>
                    </div>
                    @endif
                </div>
            @else
                <!-- Grid View -->
                @if($view === 'grid')
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    @foreach($documents as $doc)
                    <div id="doc-card-{{ $doc->id }}" class="doc-card bg-white rounded-2xl border border-slate-200 hover:border-indigo-300 hover:shadow-lg transition-all duration-200 flex flex-col justify-between overflow-hidden group relative">
                        
                        <!-- Star Toggle Badge -->
                        <div class="absolute top-3 left-3 z-20">
                            <button type="button" onclick="toggleStar('{{ $doc->id }}', this);" class="p-1.5 rounded-lg bg-white/95 backdrop-blur-xs text-slate-400 hover:text-amber-500 shadow-sm transition-all border border-slate-200/80 hover:scale-105" title="Star document">
                                <i data-lucide="star" class="w-4 h-4 {{ $doc->is_starred ? 'text-amber-400 fill-amber-400' : '' }}"></i>
                            </button>
                        </div>

                        <!-- Selection Checkbox -->
                        <div class="absolute top-3 right-3 z-20">
                            <label for="check-doc-{{ $doc->id }}" class="cursor-pointer flex items-center justify-center p-1.5 rounded-lg bg-white/95 backdrop-blur-xs text-slate-700 shadow-sm hover:bg-white transition-all border border-slate-200/80 hover:scale-105" title="Select document">
                                <input type="checkbox" id="check-doc-{{ $doc->id }}" value="{{ $doc->id }}" onchange="handleDocCheckboxChange(this)" class="doc-select-checkbox w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer">
                            </label>
                        </div>

                        <!-- Thumbnail Preview Area -->
                        <a href="{{ route('documents.edit', $doc->id) }}" class="relative block aspect-[4/5] bg-slate-50 border-b border-slate-100 overflow-hidden group-hover:opacity-95 transition-opacity">
                            @if($doc->thumbnail)
                                <img src="{{ $doc->thumbnail }}" alt="{{ $doc->title }}" class="w-full h-full object-contain p-2">
                            @else
                                <div class="w-full h-full flex flex-col items-center justify-center p-6 text-slate-300">
                                    <div class="w-14 h-18 rounded-lg bg-white border border-slate-200 shadow-2xs flex flex-col justify-between p-2">
                                        <div class="w-2/3 h-1 bg-slate-200 rounded"></div>
                                        <div class="space-y-1">
                                            <div class="w-full h-0.5 bg-slate-200 rounded"></div>
                                            <div class="w-4/5 h-0.5 bg-slate-200 rounded"></div>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-medium text-slate-400 mt-2">{{ $doc->page_size }} ({{ $doc->pages_count }} {{ Str::plural('page', $doc->pages_count) }})</span>
                                </div>
                            @endif

                            <!-- Hover Overlay with Open Action -->
                            <div class="absolute inset-0 bg-indigo-900/10 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                <span class="px-3.5 py-1.5 bg-white text-indigo-700 text-xs font-bold rounded-xl shadow-md pointer-events-auto">Open Editor</span>
                            </div>
                        </a>

                        <!-- Card Footer Details & Actions -->
                        <div class="p-4">
                            <div class="flex items-start justify-between">
                                <div class="truncate pr-2">
                                    <h3 class="text-sm font-bold text-slate-800 truncate" title="{{ $doc->title }}">{{ $doc->title }}</h3>
                                    <div class="flex items-center space-x-2 text-[11px] text-slate-400 mt-1">
                                        <span>{{ $doc->pages_count }} {{ Str::plural('page', $doc->pages_count) }}</span>
                                        <span>•</span>
                                        <span>{{ $doc->updated_at->diffForHumans() }}</span>
                                    </div>
                                </div>

                                <!-- Action Dropdown Trigger -->
                                <div class="relative shrink-0" onclick="event.stopPropagation()">
                                    <button type="button" onclick="toggleCardMenu('menu-{{ $doc->id }}')" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg">
                                        <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                    </button>

                                    <!-- Dropdown Menu -->
                                    <div id="menu-{{ $doc->id }}" class="hidden absolute right-0 bottom-full mb-1 w-44 bg-white border border-slate-200 rounded-xl shadow-xl py-1 z-20 text-xs font-medium text-slate-700">
                                        <a href="{{ route('documents.edit', $doc->id) }}" class="flex items-center space-x-2 px-3 py-2 hover:bg-indigo-50 hover:text-indigo-600">
                                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                                            <span>Open Editor</span>
                                        </a>
                                        <button type="button" onclick="duplicateDoc('{{ $doc->id }}')" class="w-full flex items-center space-x-2 px-3 py-2 hover:bg-slate-50 text-left">
                                            <i data-lucide="copy" class="w-3.5 h-3.5"></i>
                                            <span>Duplicate</span>
                                        </button>
                                        <button type="button" onclick="renameDocPrompt('{{ $doc->id }}', '{{ addslashes($doc->title) }}')" class="w-full flex items-center space-x-2 px-3 py-2 hover:bg-slate-50 text-left">
                                            <i data-lucide="pencil" class="w-3.5 h-3.5"></i>
                                            <span>Rename</span>
                                        </button>
                                        
                                        @if($tab === 'trash')
                                        <div class="border-t border-slate-100 my-1"></div>
                                        <button type="button" onclick="restoreDoc('{{ $doc->id }}')" class="w-full flex items-center space-x-2 px-3 py-2 hover:bg-emerald-50 text-emerald-600 text-left">
                                            <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                                            <span>Restore</span>
                                        </button>
                                        <button type="button" onclick="forceDeleteDoc('{{ $doc->id }}')" class="w-full flex items-center space-x-2 px-3 py-2 hover:bg-rose-50 text-rose-600 text-left">
                                            <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                                            <span>Permanently Delete</span>
                                        </button>
                                        @else
                                        <div class="border-t border-slate-100 my-1"></div>
                                        <button type="button" onclick="trashDoc('{{ $doc->id }}')" class="w-full flex items-center space-x-2 px-3 py-2 hover:bg-rose-50 text-rose-600 text-left">
                                            <i data-lucide="trash" class="w-3.5 h-3.5"></i>
                                            <span>Move to Trash</span>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <!-- List View -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-700">
                        <thead class="bg-slate-50 text-slate-500 text-xs uppercase font-semibold border-b border-slate-200">
                            <tr>
                                <th class="px-4 py-3 w-10">
                                    <input type="checkbox" id="select-all-header" onchange="toggleSelectAllDocs(this.checked)" class="w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer">
                                </th>
                                <th class="px-4 py-3">Document</th>
                                <th class="px-4 py-3">Page Preset</th>
                                <th class="px-4 py-3">Pages</th>
                                <th class="px-4 py-3">Created</th>
                                <th class="px-4 py-3">Last Modified</th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($documents as $doc)
                            <tr id="doc-row-{{ $doc->id }}" class="hover:bg-indigo-50/30 transition-colors group">
                                <td class="px-4 py-3 w-10">
                                    <label for="check-doc-list-{{ $doc->id }}" class="cursor-pointer flex items-center justify-center p-1">
                                        <input type="checkbox" id="check-doc-list-{{ $doc->id }}" value="{{ $doc->id }}" onchange="handleDocCheckboxChange(this)" class="doc-select-checkbox w-4 h-4 rounded text-indigo-600 border-slate-300 focus:ring-indigo-500 cursor-pointer">
                                    </label>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center space-x-3">
                                        <button type="button" onclick="toggleStar('{{ $doc->id }}', this);" class="text-slate-300 hover:text-amber-500">
                                            <i data-lucide="star" class="w-4 h-4 {{ $doc->is_starred ? 'text-amber-400 fill-amber-400' : '' }}"></i>
                                        </button>
                                        <a href="{{ route('documents.edit', $doc->id) }}" class="font-bold text-slate-800 hover:text-indigo-600">
                                            {{ $doc->title }}
                                        </a>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    <span class="px-2 py-0.5 bg-slate-100 rounded-md font-medium">{{ $doc->page_size }} ({{ $doc->orientation }})</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-500">
                                    {{ $doc->pages_count }} {{ Str::plural('page', $doc->pages_count) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">
                                    {{ $doc->created_at->format('M j, Y') }}
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-400">
                                    {{ $doc->updated_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end space-x-1">
                                        <a href="{{ route('documents.edit', $doc->id) }}" class="p-1.5 text-indigo-600 hover:bg-indigo-50 rounded-lg" title="Open Editor">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </a>
                                        <button type="button" onclick="duplicateDoc('{{ $doc->id }}')" class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg" title="Duplicate">
                                            <i data-lucide="copy" class="w-4 h-4"></i>
                                        </button>
                                        @if($tab === 'trash')
                                        <button type="button" onclick="restoreDoc('{{ $doc->id }}')" class="p-1.5 text-emerald-600 hover:bg-emerald-50 rounded-lg" title="Restore">
                                            <i data-lucide="rotate-ccw" class="w-4 h-4"></i>
                                        </button>
                                        <button type="button" onclick="forceDeleteDoc('{{ $doc->id }}')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg" title="Permanently Delete">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                        @else
                                        <button type="button" onclick="trashDoc('{{ $doc->id }}')" class="p-1.5 text-rose-600 hover:bg-rose-50 rounded-lg" title="Trash">
                                            <i data-lucide="trash" class="w-4 h-4"></i>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @endif

                <!-- Pagination Links -->
                <div class="mt-6">
                    {{ $documents->links() }}
                </div>
            @endif
        </div>
    </div>

    <!-- Floating Bulk Action Bar -->
    <div id="bulk-action-bar" class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50 hidden transition-all duration-300">
        <div class="bg-slate-900/95 backdrop-blur-md text-white px-5 py-3 rounded-2xl shadow-2xl border border-slate-700/80 flex items-center space-x-3 sm:space-x-4">
            <div class="flex items-center space-x-2">
                <span id="bulk-count-badge" class="px-2.5 py-0.5 bg-indigo-600 text-white rounded-full text-xs font-bold shadow-xs">0</span>
                <span class="text-xs font-medium text-slate-300 hidden sm:inline">selected</span>
            </div>

            <div class="h-4 w-px bg-slate-700"></div>

            <button type="button" onclick="toggleSelectAllDocs(true)" class="text-xs text-indigo-300 hover:text-indigo-200 font-medium hover:underline">
                Select All
            </button>

            <div class="h-4 w-px bg-slate-700"></div>

            <div class="flex items-center space-x-2">
                @if($tab === 'trash')
                <button type="button" onclick="bulkAction('restore')" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-xl flex items-center space-x-1.5 transition shadow-sm">
                    <i data-lucide="rotate-ccw" class="w-3.5 h-3.5"></i>
                    <span>Restore</span>
                </button>
                <button type="button" onclick="bulkAction('forceDelete')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl flex items-center space-x-1.5 transition shadow-sm">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Delete Permanently</span>
                </button>
                @else
                <button type="button" onclick="bulkAction('trash')" class="px-3 py-1.5 bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold rounded-xl flex items-center space-x-1.5 transition shadow-sm">
                    <i data-lucide="trash-2" class="w-3.5 h-3.5"></i>
                    <span>Move to Trash</span>
                </button>
                <button type="button" onclick="bulkAction('star')" class="px-3 py-1.5 bg-slate-800 hover:bg-slate-700 text-amber-400 text-xs font-semibold rounded-xl flex items-center space-x-1.5 transition border border-slate-700 shadow-sm" title="Star Selected">
                    <i data-lucide="star" class="w-3.5 h-3.5 fill-amber-400"></i>
                    <span>Star</span>
                </button>
                @endif
            </div>

            <div class="h-4 w-px bg-slate-700"></div>

            <button type="button" onclick="clearAllSelections()" class="p-1.5 text-slate-400 hover:text-white rounded-lg hover:bg-slate-800 transition" title="Clear selection">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

    function toggleCardMenu(menuId) {
        document.querySelectorAll('[id^="menu-"]').forEach(el => {
            if (el.id !== menuId) el.classList.add('hidden');
        });
        const m = document.getElementById(menuId);
        if (m) m.classList.toggle('hidden');
    }

    document.addEventListener('click', () => {
        document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
    });

    async function toggleStar(docId, btn) {
        try {
            const res = await fetch(`/documents/${docId}/star`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                const icon = btn.querySelector('svg') || btn.querySelector('i');
                if (data.is_starred) {
                    btn.innerHTML = '<i data-lucide="star" class="w-4 h-4 text-amber-400 fill-amber-400"></i>';
                    window.showToast('Document starred', 'success');
                } else {
                    btn.innerHTML = '<i data-lucide="star" class="w-4 h-4 text-slate-400"></i>';
                    window.showToast('Document unstarred', 'info');
                }
                if (window.lucide) lucide.createIcons();
            }
        } catch(e) {
            window.showToast('Failed to update star', 'error');
        }
    }

    async function duplicateDoc(docId) {
        try {
            const res = await fetch(`/documents/${docId}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Document duplicated successfully!', 'success');
                setTimeout(() => window.location.reload(), 500);
            }
        } catch(e) {
            window.showToast('Failed to duplicate document', 'error');
        }
    }

    async function renameDocPrompt(docId, oldTitle) {
        const newTitle = prompt('Enter new document title:', oldTitle);
        if (!newTitle || newTitle.trim() === '' || newTitle === oldTitle) return;

        try {
            const res = await fetch(`/documents/${docId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ title: newTitle.trim() })
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Document renamed', 'success');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch(e) {
            window.showToast('Failed to rename document', 'error');
        }
    }

    async function trashDoc(docId) {
        if (!confirm('Move this document to trash?')) return;
        try {
            const res = await fetch(`/documents/${docId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Moved to trash', 'info');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch(e) {
            window.showToast('Failed to delete document', 'error');
        }
    }

    async function restoreDoc(docId) {
        try {
            const res = await fetch(`/documents/${docId}/restore`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Document restored', 'success');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch(e) {
            window.showToast('Failed to restore document', 'error');
        }
    }

    async function forceDeleteDoc(docId) {
        if (!confirm('Permanently delete this document? This cannot be undone.')) return;
        try {
            const res = await fetch(`/documents/${docId}/force`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast('Document permanently deleted', 'info');
                setTimeout(() => window.location.reload(), 400);
            }
        } catch(e) {
            window.showToast('Failed to permanently delete', 'error');
        }
    }

    async function useTemplate(templateId) {
        try {
            const res = await fetch(`/templates/${templateId}/use`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            }
        } catch(e) {
            window.showToast('Failed to load template', 'error');
        }
    }

    // --- Multi-select & Bulk Actions ---
    function getSelectedDocIds() {
        const checkboxes = document.querySelectorAll('.doc-select-checkbox:checked');
        return Array.from(checkboxes).map(cb => cb.value);
    }

    function updateBulkActionBar() {
        const ids = getSelectedDocIds();
        const bar = document.getElementById('bulk-action-bar');
        const badge = document.getElementById('bulk-count-badge');
        const headerCb = document.getElementById('select-all-header');
        const toggleCb = document.getElementById('select-all-toggle');
        const allCheckboxes = document.querySelectorAll('.doc-select-checkbox');

        if (badge) badge.textContent = ids.length;

        if (ids.length > 0) {
            if (bar) bar.classList.remove('hidden');
        } else {
            if (bar) bar.classList.add('hidden');
        }

        const allChecked = (allCheckboxes.length > 0 && ids.length === allCheckboxes.length);
        const isIndeterminate = (ids.length > 0 && ids.length < allCheckboxes.length);

        if (headerCb) {
            headerCb.checked = allChecked;
            headerCb.indeterminate = isIndeterminate;
        }
        if (toggleCb) {
            toggleCb.checked = allChecked;
            toggleCb.indeterminate = isIndeterminate;
        }

        updateDocCardVisuals();

        if (window.lucide) lucide.createIcons();
    }

    function updateDocCardVisuals() {
        document.querySelectorAll('.doc-select-checkbox').forEach(cb => {
            const card = document.getElementById('doc-card-' + cb.value);
            if (card) {
                if (cb.checked) {
                    card.classList.add('ring-2', 'ring-indigo-600', 'border-indigo-400');
                } else {
                    card.classList.remove('ring-2', 'ring-indigo-600', 'border-indigo-400');
                }
            }
            const row = document.getElementById('doc-row-' + cb.value);
            if (row) {
                if (cb.checked) {
                    row.classList.add('bg-indigo-50/70');
                } else {
                    row.classList.remove('bg-indigo-50/70');
                }
            }
        });
    }

    function handleDocCheckboxChange() {
        updateBulkActionBar();
    }

    function toggleSelectAllDocs(checked) {
        document.querySelectorAll('.doc-select-checkbox').forEach(cb => {
            cb.checked = checked;
        });
        updateBulkActionBar();
    }

    function clearAllSelections() {
        document.querySelectorAll('.doc-select-checkbox').forEach(cb => {
            cb.checked = false;
        });
        updateBulkActionBar();
    }

    async function bulkAction(action) {
        const ids = getSelectedDocIds();
        if (ids.length === 0) {
            window.showToast('Please select at least one document', 'warning');
            return;
        }

        if (action === 'forceDelete') {
            if (!confirm(`Are you sure you want to permanently delete ${ids.length} selected document(s)? This cannot be undone.`)) {
                return;
            }
        } else if (action === 'trash') {
            if (!confirm(`Move ${ids.length} selected document(s) to trash?`)) {
                return;
            }
        }

        try {
            const res = await fetch('/documents/bulk-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ action: action, ids: ids })
            });
            const data = await res.json();
            if (data.success) {
                window.showToast(data.message || 'Action completed successfully', 'success');
                setTimeout(() => window.location.reload(), 400);
            } else {
                window.showToast(data.message || 'Action failed', 'error');
            }
        } catch(e) {
            window.showToast('Bulk action request failed', 'error');
        }
    }

    // --- Empty Trash ---
    async function emptyTrashConfirm() {
        if (!confirm('Are you sure you want to permanently delete ALL items in Trash? This cannot be undone!')) {
            return;
        }
        try {
            const res = await fetch('/documents/empty-trash', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                }
            });
            const data = await res.json();
            if (data.success) {
                window.showToast(data.message || 'Trash emptied successfully', 'info');
                setTimeout(() => window.location.reload(), 400);
            } else {
                window.showToast(data.message || 'Failed to empty trash', 'error');
            }
        } catch(e) {
            window.showToast('Failed to empty trash', 'error');
        }
    }
</script>
@endpush
@endsection

