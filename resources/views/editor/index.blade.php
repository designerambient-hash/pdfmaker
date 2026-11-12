<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-100">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $document->title }} - PDFCraft Studio</title>

    <!-- Google Fonts for Typography Library -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Dancing+Script:wght@700&family=Fira+Code:wght@400;600&family=Inter:wght@300;400;500;600;700;800;900&family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;600;800&family=Orbitron:wght@600&family=Oswald:wght@500;700&family=Outfit:wght@400;600;700&family=Playfair+Display:ital,wght@0,600;0,800;1,600&family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) lucide.createIcons();
        });
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/editor/app.js'])

    <style>
        body {
            font-family: 'Inter', system-ui, sans-serif;
            overflow: hidden;
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        .canvas-container {
            margin: 0 auto;
            box-shadow: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            border-radius: 4px;
        }
    </style>
</head>
<body class="h-full flex flex-col antialiased text-slate-800 select-none">

    <!-- 1. TOP TOOLBAR -->
    <header class="h-14 bg-white border-b border-slate-200 px-4 flex items-center justify-between z-30 shrink-0 shadow-2xs">
        
        <!-- Left: Logo & Document Title & Status -->
        <div class="flex items-center space-x-3">
            <a href="{{ route('dashboard') }}" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition" title="Back to Dashboard">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>

            <div class="h-5 w-px bg-slate-200"></div>

            <div class="flex items-center space-x-2">
                <input type="text" id="input-doc-title" value="{{ $document->title }}" class="font-bold text-sm text-slate-800 bg-transparent hover:bg-slate-50 focus:bg-white px-2 py-1 rounded-lg border border-transparent focus:border-indigo-500 transition-colors w-48 sm:w-64 truncate">
                <span id="save-status-text" class="text-[11px] font-medium text-slate-400 hidden sm:inline">Saved</span>
            </div>
        </div>

        <!-- Center: Undo/Redo & Zoom & Fullscreen -->
        <div class="flex items-center space-x-1 sm:space-x-2">
            <button type="button" id="btn-undo" disabled class="p-2 text-slate-400 disabled:opacity-40 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Undo (Ctrl+Z)">
                <i data-lucide="undo-2" class="w-4 h-4"></i>
            </button>
            <button type="button" id="btn-redo" disabled class="p-2 text-slate-400 disabled:opacity-40 hover:text-slate-800 hover:bg-slate-100 rounded-lg transition" title="Redo (Ctrl+Y)">
                <i data-lucide="redo-2" class="w-4 h-4"></i>
            </button>

            <div class="h-4 w-px bg-slate-200"></div>

            <!-- Zoom Controls -->
            <button type="button" id="btn-zoom-out" class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg" title="Zoom Out (Ctrl+-)">
                <i data-lucide="minus" class="w-3.5 h-3.5"></i>
            </button>
            <span id="zoom-percentage-text" class="text-xs font-semibold text-slate-600 w-12 text-center">100%</span>
            <button type="button" id="btn-zoom-in" class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg" title="Zoom In (Ctrl++)">
                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
            </button>
            <button type="button" id="btn-zoom-fit" class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-lg" title="Zoom to Fit">
                <i data-lucide="maximize-2" class="w-3.5 h-3.5"></i>
            </button>

            <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>

            <button type="button" id="btn-fullscreen" class="p-2 text-slate-500 hover:bg-slate-100 rounded-lg hidden sm:block" title="Toggle Fullscreen">
                <i data-lucide="expand" class="w-4 h-4"></i>
            </button>
        </div>

        <!-- Right: Version History & Save & Export -->
        <div class="flex items-center space-x-2">
            <button type="button" onclick="openVersionsModal()" class="p-2 text-slate-500 hover:text-slate-800 hover:bg-slate-100 rounded-xl transition" title="Version History">
                <i data-lucide="history" class="w-4 h-4"></i>
            </button>

            <button type="button" id="btn-save" class="inline-flex items-center space-x-1.5 px-3.5 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-xl transition shadow-2xs">
                <i data-lucide="save" class="w-3.5 h-3.5"></i>
                <span>Save</span>
            </button>

            <button type="button" id="btn-export-pdf" class="inline-flex items-center space-x-2 px-4 py-1.5 text-xs font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm shadow-indigo-200">
                <i data-lucide="download" class="w-4 h-4"></i>
                <span>Export PDF</span>
            </button>

            @auth
            <div class="h-4 w-px bg-slate-200 hidden sm:block"></div>
            <div class="hidden sm:flex items-center space-x-2" title="{{ Auth::user()->name }} ({{ Auth::user()->email }})">
                <div class="w-7 h-7 rounded-full bg-indigo-100 border border-indigo-200 text-indigo-700 font-bold text-xs flex items-center justify-center">
                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                </div>
            </div>
            @endauth
        </div>
    </header>

    <!-- 2. MAIN WORKSPACE CONTAINER (Sidebar + Canvas + Properties) -->
    <div class="flex-1 flex overflow-hidden relative">

        <!-- 2A. LEFT SIDEBAR (ICONS TAB BAR) -->
        <aside class="w-16 bg-white border-r border-slate-200 flex flex-col items-center py-3 space-y-2 z-20 shrink-0">
            <button type="button" id="tab-btn-pages" onclick="window.switchEditorTab('pages')" class="p-2.5 rounded-xl bg-indigo-600 text-white transition flex flex-col items-center text-[10px] font-medium" title="Pages">
                <i data-lucide="files" class="w-4 h-4 mb-1"></i>
                <span>Pages</span>
            </button>
            <button type="button" id="tab-btn-templates" onclick="window.switchEditorTab('templates')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Templates">
                <i data-lucide="layout-template" class="w-4 h-4 mb-1"></i>
                <span>Layouts</span>
            </button>
            <button type="button" id="tab-btn-text" onclick="window.switchEditorTab('text')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Text">
                <i data-lucide="type" class="w-4 h-4 mb-1"></i>
                <span>Text</span>
            </button>
            <button type="button" id="tab-btn-shapes" onclick="window.switchEditorTab('shapes')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Shapes">
                <i data-lucide="shapes" class="w-4 h-4 mb-1"></i>
                <span>Shapes</span>
            </button>
            <button type="button" id="tab-btn-tables" onclick="window.switchEditorTab('tables')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Tables">
                <i data-lucide="table" class="w-4 h-4 mb-1"></i>
                <span>Tables</span>
            </button>
            <button type="button" id="tab-btn-images" onclick="window.switchEditorTab('images')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Images">
                <i data-lucide="image" class="w-4 h-4 mb-1"></i>
                <span>Images</span>
            </button>
            <button type="button" id="tab-btn-signatures" onclick="window.switchEditorTab('signatures')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Signatures">
                <i data-lucide="signature" class="w-4 h-4 mb-1"></i>
                <span>Sign</span>
            </button>
            <button type="button" id="tab-btn-backgrounds" onclick="window.switchEditorTab('backgrounds')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Backgrounds">
                <i data-lucide="paint-bucket" class="w-4 h-4 mb-1"></i>
                <span>Canvas</span>
            </button>
            <button type="button" id="tab-btn-layers" onclick="window.switchEditorTab('layers')" class="p-2.5 rounded-xl text-slate-400 hover:bg-slate-100 hover:text-slate-700 transition flex flex-col items-center text-[10px] font-medium" title="Layers">
                <i data-lucide="layers" class="w-4 h-4 mb-1"></i>
                <span>Layers</span>
            </button>
        </aside>

        <!-- 2B. LEFT SIDEBAR CONTENT DRAWER (72 rem) -->
        <div id="sidebar-drawer" class="w-72 bg-white border-r border-slate-200 flex flex-col z-10 shrink-0 overflow-y-auto">
            
            <!-- TAB 1: PAGES -->
            <div id="tab-panel-pages" class="p-4 space-y-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Pages</h3>
                    <button type="button" onclick="window.editorApp.pageManager.addNewPage()" class="inline-flex items-center space-x-1 px-2.5 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg transition">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Page</span>
                    </button>
                </div>
                <div id="pages-thumbnails-list" class="space-y-3"></div>
            </div>

            <!-- TAB 2: TEMPLATES -->
            <div id="tab-panel-templates" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Templates</h3>
                <div class="space-y-3">
                    @foreach($templates as $tpl)
                    <div class="border border-slate-200 rounded-xl p-3 hover:border-indigo-400 hover:shadow-xs transition cursor-pointer" onclick="window.editorApp.loadTemplate('{{ $tpl->id }}')">
                        <div class="h-20 bg-slate-50 rounded-lg mb-2 flex items-center justify-center p-2">
                            <span class="text-[10px] font-bold text-slate-400 uppercase">{{ $tpl->category }}</span>
                        </div>
                        <h4 class="text-xs font-bold text-slate-800">{{ $tpl->title }}</h4>
                        <span class="text-[10px] text-slate-400">{{ $tpl->page_size }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            <!-- TAB 3: TEXT -->
            <div id="tab-panel-text" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Add Text</h3>
                <div class="space-y-2">
                    <button type="button" onclick="window.editorApp.elements.addText('heading')" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-slate-50 transition group">
                        <span class="text-lg font-black text-slate-800 group-hover:text-indigo-600">Add a Heading</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addText('subheading')" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-slate-50 transition group">
                        <span class="text-sm font-bold text-slate-700 group-hover:text-indigo-600">Add a Subheading</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addText('paragraph')" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-slate-50 transition group">
                        <span class="text-xs text-slate-600 group-hover:text-indigo-600">Add a little bit of body text</span>
                    </button>
                </div>
            </div>

            <!-- TAB 4: SHAPES -->
            <div id="tab-panel-shapes" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Shapes</h3>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="window.editorApp.elements.addShape('rect')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Rectangle">
                        <div class="w-7 h-5 bg-indigo-600 rounded-xs mb-1"></div>
                        <span>Rectangle</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('rounded-rect')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Rounded Rect">
                        <div class="w-7 h-5 bg-indigo-600 rounded-md mb-1"></div>
                        <span>Rounded</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('circle')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Circle">
                        <div class="w-6 h-6 bg-indigo-600 rounded-full mb-1"></div>
                        <span>Circle</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('ellipse')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Ellipse">
                        <div class="w-7 h-4 bg-indigo-600 rounded-full mb-1"></div>
                        <span>Ellipse</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('triangle')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Triangle">
                        <i data-lucide="triangle" class="w-5 h-5 text-indigo-600 mb-1"></i>
                        <span>Triangle</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('star')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Star">
                        <i data-lucide="star" class="w-5 h-5 text-indigo-600 mb-1"></i>
                        <span>Star</span>
                    </button>
                    <button type="button" onclick="window.editorApp.elements.addShape('line')" class="p-3 bg-slate-50 hover:bg-indigo-50 border border-slate-200 rounded-xl flex flex-col items-center text-[10px] font-medium transition" title="Line">
                        <i data-lucide="minus" class="w-5 h-5 text-indigo-600 mb-1"></i>
                        <span>Line</span>
                    </button>
                </div>
            </div>

            <!-- TAB: TABLES -->
            <div id="tab-panel-tables" class="hidden p-4 space-y-5">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Tables</h3>
                </div>

                <!-- Custom Grid Generator -->
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3.5 space-y-3">
                    <span class="text-xs font-bold text-slate-700 block">Create Custom Table</span>
                    <div class="grid grid-cols-2 gap-2.5">
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Rows</label>
                            <input type="number" id="table-input-rows" min="1" max="15" value="3" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-[10px] font-semibold text-slate-500 mb-1">Columns</label>
                            <input type="number" id="table-input-cols" min="1" max="8" value="3" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs font-medium text-slate-700 focus:ring-2 focus:ring-indigo-500">
                        </div>
                    </div>

                    <button type="button" onclick="insertCustomTable()" class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-bold transition flex items-center justify-center space-x-1.5 shadow-sm">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Insert Table</span>
                    </button>
                </div>

                <!-- Table Presets -->
                <div class="space-y-2.5">
                    <span class="text-xs font-bold text-slate-700 block">Quick Table Presets</span>
                    
                    <!-- Preset 1: 3x3 Standard Indigo -->
                    <button type="button" onclick="window.editorApp.elements.addTable({ rows: 3, cols: 3, theme: 'indigo' })" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-slate-50 transition group">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600">Standard Grid (3 × 3)</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-indigo-50 text-indigo-700 rounded font-semibold">Indigo</span>
                        </div>
                        <div class="border border-indigo-200 rounded overflow-hidden">
                            <div class="grid grid-cols-3 bg-indigo-600 h-3"></div>
                            <div class="grid grid-cols-3 bg-white h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-3 bg-slate-50 h-2.5 border-t border-slate-200"></div>
                        </div>
                    </button>

                    <!-- Preset 2: Invoice Items Table (4 cols: Item, Qty, Price, Total) -->
                    <button type="button" onclick="window.editorApp.elements.addTable({ rows: 4, cols: 4, theme: 'invoice', headers: ['Item Description', 'Qty', 'Unit Price', 'Amount'] })" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-slate-50 transition group">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-indigo-600">Invoice Items (4 × 4)</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-700 rounded font-semibold">Business</span>
                        </div>
                        <div class="border border-slate-300 rounded overflow-hidden">
                            <div class="grid grid-cols-4 bg-slate-800 h-3"></div>
                            <div class="grid grid-cols-4 bg-white h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-4 bg-slate-50 h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-4 bg-white h-2.5 border-t border-slate-200"></div>
                        </div>
                    </button>

                    <!-- Preset 3: Modern Emerald (3 cols: Feature, Plan A, Plan B) -->
                    <button type="button" onclick="window.editorApp.elements.addTable({ rows: 4, cols: 3, theme: 'emerald', headers: ['Feature / Plan', 'Standard', 'Premium'] })" class="w-full text-left p-3 rounded-xl border border-emerald-200 hover:border-emerald-400 hover:bg-slate-50 transition group">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-emerald-600">Comparison (4 × 3)</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-emerald-50 text-emerald-700 rounded font-semibold">Emerald</span>
                        </div>
                        <div class="border border-emerald-200 rounded overflow-hidden">
                            <div class="grid grid-cols-3 bg-emerald-600 h-3"></div>
                            <div class="grid grid-cols-3 bg-white h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-3 bg-emerald-50/40 h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-3 bg-white h-2.5 border-t border-slate-200"></div>
                        </div>
                    </button>

                    <!-- Preset 4: Clean Minimalist Table (2 cols: Key, Value) -->
                    <button type="button" onclick="window.editorApp.elements.addTable({ rows: 4, cols: 2, theme: 'minimal', headers: ['Specification', 'Details'] })" class="w-full text-left p-3 rounded-xl border border-slate-200 hover:border-slate-400 hover:bg-slate-50 transition group">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-xs font-bold text-slate-800 group-hover:text-slate-900">Summary List (4 × 2)</span>
                            <span class="text-[10px] px-1.5 py-0.5 bg-slate-100 text-slate-600 rounded font-semibold">Minimal</span>
                        </div>
                        <div class="border border-slate-200 rounded overflow-hidden">
                            <div class="grid grid-cols-2 bg-slate-100 h-3"></div>
                            <div class="grid grid-cols-2 bg-white h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-2 bg-white h-2.5 border-t border-slate-200"></div>
                            <div class="grid grid-cols-2 bg-white h-2.5 border-t border-slate-200"></div>
                        </div>
                    </button>
                </div>
            </div>

            <!-- TAB 5: IMAGES & UPLOADS -->
            <div id="tab-panel-images" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Images</h3>
                
                <button type="button" onclick="document.getElementById('input-image-upload').click()" class="w-full py-3 border-2 border-dashed border-indigo-200 hover:border-indigo-500 rounded-xl text-center bg-indigo-50/50 hover:bg-indigo-50 transition cursor-pointer">
                    <i data-lucide="upload" class="w-5 h-5 text-indigo-600 mx-auto mb-1"></i>
                    <span class="text-xs font-bold text-indigo-700">Upload Image</span>
                    <input type="file" id="input-image-upload" accept="image/*" class="hidden">
                </button>

                <!-- Library Assets -->
                <div class="space-y-2">
                    <span class="text-[11px] font-bold text-slate-500">Stock & Sample Assets</span>
                    <div class="grid grid-cols-2 gap-2">
                        <div class="aspect-square bg-slate-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition" onclick="window.editorApp.elements.addImageFromUrl('https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=500&auto=format&fit=crop', 'Gradient Art')">
                            <img src="https://images.unsplash.com/photo-1579546929518-9e396f3cc809?w=200&auto=format&fit=crop" class="w-full h-full object-cover">
                        </div>
                        <div class="aspect-square bg-slate-100 rounded-lg overflow-hidden cursor-pointer hover:opacity-80 transition" onclick="window.editorApp.elements.addImageFromUrl('https://images.unsplash.com/photo-1557683316-973673baf926?w=500&auto=format&fit=crop', 'Color Wave')">
                            <img src="https://images.unsplash.com/photo-1557683316-973673baf926?w=200&auto=format&fit=crop" class="w-full h-full object-cover">
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 6: SIGNATURES -->
            <div id="tab-panel-signatures" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Draw Signature</h3>
                
                <div class="border border-slate-300 rounded-xl bg-white overflow-hidden">
                    <canvas id="signature-draw-canvas" width="250" height="120" class="w-full touch-none cursor-crosshair bg-white"></canvas>
                    <div class="flex items-center justify-between p-2 bg-slate-50 border-t border-slate-200">
                        <button type="button" id="btn-clear-sig" class="px-2.5 py-1 text-xs text-slate-500 hover:text-slate-800">Clear</button>
                        <button type="button" id="btn-insert-sig" class="px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-lg shadow-xs">Insert</button>
                    </div>
                </div>
            </div>

            <!-- TAB 7: BACKGROUNDS -->
            <div id="tab-panel-backgrounds" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Page Background</h3>
                <div class="grid grid-cols-4 gap-2">
                    <button type="button" onclick="setPageBg('#ffffff')" class="w-10 h-10 rounded-xl border border-slate-300 bg-white" title="White"></button>
                    <button type="button" onclick="setPageBg('#f8fafc')" class="w-10 h-10 rounded-xl border border-slate-300 bg-slate-50" title="Slate 50"></button>
                    <button type="button" onclick="setPageBg('#f1f5f9')" class="w-10 h-10 rounded-xl border border-slate-300 bg-slate-100" title="Slate 100"></button>
                    <button type="button" onclick="setPageBg('#0f172a')" class="w-10 h-10 rounded-xl border border-slate-300 bg-slate-900" title="Slate 900"></button>
                    <button type="button" onclick="setPageBg('#e0e7ff')" class="w-10 h-10 rounded-xl border border-slate-300 bg-indigo-100" title="Indigo 100"></button>
                    <button type="button" onclick="setPageBg('#fef3c7')" class="w-10 h-10 rounded-xl border border-slate-300 bg-amber-100" title="Amber 100"></button>
                    <button type="button" onclick="setPageBg('#dcfce7')" class="w-10 h-10 rounded-xl border border-slate-300 bg-emerald-100" title="Emerald 100"></button>
                    <button type="button" onclick="setPageBg('#fce7f3')" class="w-10 h-10 rounded-xl border border-slate-300 bg-pink-100" title="Pink 100"></button>
                </div>
            </div>

            <!-- TAB 8: LAYERS -->
            <div id="tab-panel-layers" class="hidden p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Layers</h3>
                <div id="layers-list" class="space-y-1"></div>
            </div>
        </div>

        <!-- 2C. CENTER WORKSPACE (CANVAS VIEWPORT) -->
        <main class="flex-1 flex flex-col bg-slate-200/70 overflow-hidden relative" id="canvas-main-area">
            
            <!-- Canvas Scrollable Viewport -->
            <div id="canvas-scroll-container" class="flex-1 overflow-auto flex items-center justify-center p-12">
                <div id="canvas-wrapper" class="relative transition-transform duration-75">
                    <canvas id="main-editor-canvas"></canvas>
                </div>
            </div>

            <!-- Floating Action Bar for Selected Canvas Element -->
            <div id="canvas-floating-toolbar" class="hidden absolute top-4 left-1/2 -translate-x-1/2 bg-white/95 backdrop-blur-xs border border-slate-200 rounded-2xl shadow-xl px-2.5 py-1.5 flex items-center space-x-1 z-30 animate-in fade-in zoom-in duration-100 text-slate-700">
                <button type="button" onclick="window.editorApp.elements.duplicateSelected()" class="p-1.5 hover:bg-slate-100 rounded-lg" title="Duplicate (Ctrl+D)">
                    <i data-lucide="copy" class="w-4 h-4"></i>
                </button>
                <button type="button" onclick="window.editorApp.elements.bringForward()" class="p-1.5 hover:bg-slate-100 rounded-lg" title="Bring Forward">
                    <i data-lucide="bring-to-front" class="w-4 h-4"></i>
                </button>
                <button type="button" onclick="window.editorApp.elements.sendBackward()" class="p-1.5 hover:bg-slate-100 rounded-lg" title="Send Backward">
                    <i data-lucide="send-to-back" class="w-4 h-4"></i>
                </button>
                <button type="button" onclick="window.editorApp.elements.toggleLockSelected()" class="p-1.5 hover:bg-slate-100 rounded-lg" title="Lock / Unlock">
                    <i data-lucide="lock" class="w-4 h-4"></i>
                </button>
                <div class="h-4 w-px bg-slate-200"></div>
                <button type="button" onclick="window.editorApp.elements.deleteSelected()" class="p-1.5 hover:bg-rose-50 text-rose-600 rounded-lg" title="Delete (Del)">
                    <i data-lucide="trash" class="w-4 h-4"></i>
                </button>
            </div>

            <!-- Bottom Navigation Bar -->
            <footer class="h-11 bg-white border-t border-slate-200 px-4 flex items-center justify-between z-20 shrink-0">
                <div class="flex items-center space-x-2">
                    <button type="button" onclick="window.editorApp.pageManager.prevPage()" class="p-1 text-slate-500 hover:bg-slate-100 rounded-lg" title="Previous Page">
                        <i data-lucide="chevron-left" class="w-4 h-4"></i>
                    </button>
                    <span id="bottom-page-counter" class="text-xs font-semibold text-slate-700">Page 1 of 1</span>
                    <button type="button" onclick="window.editorApp.pageManager.nextPage()" class="p-1 text-slate-500 hover:bg-slate-100 rounded-lg" title="Next Page">
                        <i data-lucide="chevron-right" class="w-4 h-4"></i>
                    </button>
                </div>

                <div class="flex items-center space-x-2">
                    <button type="button" onclick="window.editorApp.pageManager.addNewPage()" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 flex items-center space-x-1">
                        <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                        <span>Add Page</span>
                    </button>
                </div>
            </footer>
        </main>

        <!-- 2D. RIGHT SIDEBAR (INSPECTOR / PROPERTIES) -->
        <aside class="w-72 bg-white border-l border-slate-200 flex flex-col z-20 shrink-0 overflow-y-auto">
            
            <!-- Context 1: Page Properties (when no object selected) -->
            <div id="panel-props-page" class="p-4 space-y-4">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Page Properties</h3>
                
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Page Size</label>
                    <input type="text" readonly value="{{ $document->page_size }} ({{ round($document->width) }} × {{ round($document->height) }} px)" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-slate-600">
                </div>

                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1">Background Color</label>
                    <input type="color" id="page-bg-color-picker" value="#ffffff" onchange="setPageBg(this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                </div>
            </div>

            <!-- Context 2: Element Properties (when object selected) -->
            <div id="panel-props-element" class="hidden p-4 space-y-5">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400">Element Properties</h3>

                <!-- Alignment to Page Buttons -->
                <div>
                    <label class="block text-[11px] font-semibold text-slate-600 mb-1.5">Align to Page</label>
                    <div class="grid grid-cols-6 gap-1 bg-slate-50 p-1 border border-slate-200 rounded-xl text-slate-600">
                        <button type="button" onclick="window.editorApp.elements.align('left')" class="p-1 hover:bg-white rounded" title="Align Left"><i data-lucide="align-start-vertical" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.elements.align('center-h')" class="p-1 hover:bg-white rounded" title="Align Center H"><i data-lucide="align-center-vertical" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.elements.align('right')" class="p-1 hover:bg-white rounded" title="Align Right"><i data-lucide="align-end-vertical" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.elements.align('top')" class="p-1 hover:bg-white rounded" title="Align Top"><i data-lucide="align-start-horizontal" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.elements.align('center-v')" class="p-1 hover:bg-white rounded" title="Align Center V"><i data-lucide="align-center-horizontal" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.elements.align('bottom')" class="p-1 hover:bg-white rounded" title="Align Bottom"><i data-lucide="align-end-horizontal" class="w-3.5 h-3.5 mx-auto"></i></button>
                    </div>
                </div>

                <!-- Position & Size -->
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">X Position</label>
                        <input type="number" id="prop-pos-x" onchange="window.editorApp.properties.setProp('left', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Y Position</label>
                        <input type="number" id="prop-pos-y" onchange="window.editorApp.properties.setProp('top', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Width</label>
                        <input type="number" id="prop-width" onchange="window.editorApp.properties.setProp('width', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Height</label>
                        <input type="number" id="prop-height" onchange="window.editorApp.properties.setProp('height', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                    </div>
                </div>

                <!-- SECTION: TEXT SETTINGS -->
                <div id="props-section-text" class="space-y-3 pt-3 border-t border-slate-200">
                    <span class="text-[11px] font-bold text-slate-600 block">Typography</span>
                    
                    <!-- Font Family Selector -->
                    <div>
                        <select id="prop-font-family" onchange="window.editorApp.properties.setProp('fontFamily', this.value)" class="w-full px-2.5 py-1.5 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium">
                            <option value="Inter">Inter (Sans-Serif)</option>
                            <option value="Roboto">Roboto</option>
                            <option value="Montserrat">Montserrat</option>
                            <option value="Poppins">Poppins</option>
                            <option value="Playfair Display">Playfair Display (Serif)</option>
                            <option value="Merriweather">Merriweather (Serif)</option>
                            <option value="Fira Code">Fira Code (Monospace)</option>
                            <option value="Caveat">Caveat (Handwriting)</option>
                            <option value="Dancing Script">Dancing Script</option>
                            <option value="Oswald">Oswald (Display)</option>
                            <option value="Orbitron">Orbitron (Modern)</option>
                        </select>
                    </div>

                    <!-- Font Size & Weight -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <input type="number" id="prop-font-size" min="6" max="250" onchange="window.editorApp.properties.setProp('fontSize', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs" placeholder="Size">
                        </div>
                        <div class="flex items-center space-x-1 border border-slate-200 rounded-lg p-0.5 bg-slate-50">
                            <button type="button" id="btn-bold" onclick="window.editorApp.properties.toggleTextStyle('bold')" class="flex-1 p-1 hover:bg-white rounded text-xs font-bold text-slate-700">B</button>
                            <button type="button" id="btn-italic" onclick="window.editorApp.properties.toggleTextStyle('italic')" class="flex-1 p-1 hover:bg-white rounded text-xs italic text-slate-700">I</button>
                            <button type="button" id="btn-underline" onclick="window.editorApp.properties.toggleTextStyle('underline')" class="flex-1 p-1 hover:bg-white rounded text-xs underline text-slate-700">U</button>
                            <button type="button" id="btn-strike" onclick="window.editorApp.properties.toggleTextStyle('strike')" class="flex-1 p-1 hover:bg-white rounded text-xs line-through text-slate-700">S</button>
                        </div>
                    </div>

                    <!-- Alignment -->
                    <div class="flex items-center space-x-1 border border-slate-200 rounded-lg p-1 bg-slate-50">
                        <button type="button" onclick="window.editorApp.properties.setTextAlign('left')" class="flex-1 p-1 hover:bg-white rounded" title="Align Left"><i data-lucide="align-left" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.properties.setTextAlign('center')" class="flex-1 p-1 hover:bg-white rounded" title="Align Center"><i data-lucide="align-center" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.properties.setTextAlign('right')" class="flex-1 p-1 hover:bg-white rounded" title="Align Right"><i data-lucide="align-right" class="w-3.5 h-3.5 mx-auto"></i></button>
                        <button type="button" onclick="window.editorApp.properties.setTextAlign('justify')" class="flex-1 p-1 hover:bg-white rounded" title="Justify"><i data-lucide="align-justify" class="w-3.5 h-3.5 mx-auto"></i></button>
                    </div>

                    <!-- Text Colors -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Color</label>
                            <input type="color" id="prop-text-color" onchange="window.editorApp.properties.setProp('fill', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Highlight</label>
                            <input type="color" id="prop-text-bg" onchange="window.editorApp.properties.setProp('textBackgroundColor', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                    </div>

                    <!-- Letter Spacing & Line Height -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <div>
                            <div class="flex items-center justify-between text-[10px] font-medium text-slate-500 mb-1">
                                <span>Letter Spacing</span>
                                <span id="label-char-spacing-val" class="font-bold text-slate-700">0</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="range" id="prop-char-spacing-range" min="-50" max="600" step="10" value="0" oninput="document.getElementById('label-char-spacing-val').textContent = this.value; document.getElementById('prop-char-spacing').value = this.value; window.editorApp.properties.setProp('charSpacing', parseInt(this.value));" class="flex-1 accent-indigo-600">
                                <input type="number" id="prop-char-spacing" min="-100" max="1000" value="0" onchange="document.getElementById('label-char-spacing-val').textContent = this.value; document.getElementById('prop-char-spacing-range').value = this.value; window.editorApp.properties.setProp('charSpacing', parseInt(this.value));" class="w-14 px-1.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs text-center font-medium">
                            </div>
                        </div>

                        <div>
                            <div class="flex items-center justify-between text-[10px] font-medium text-slate-500 mb-1">
                                <span>Line Height</span>
                                <span id="label-line-height-val" class="font-bold text-slate-700">1.2</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <input type="range" id="prop-line-height-range" min="0.8" max="3.0" step="0.1" value="1.2" oninput="document.getElementById('label-line-height-val').textContent = this.value; document.getElementById('prop-line-height').value = this.value; window.editorApp.properties.setProp('lineHeight', parseFloat(this.value));" class="flex-1 accent-indigo-600">
                                <input type="number" id="prop-line-height" min="0.8" max="3.0" step="0.1" value="1.2" onchange="document.getElementById('label-line-height-val').textContent = this.value; document.getElementById('prop-line-height-range').value = this.value; window.editorApp.properties.setProp('lineHeight', parseFloat(this.value));" class="w-14 px-1.5 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs text-center font-medium">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- SECTION: SHAPE SETTINGS -->
                <div id="props-section-shape" class="space-y-3 pt-3 border-t border-slate-200">
                    <span class="text-[11px] font-bold text-slate-600 block">Shape & Border</span>
                    
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Fill</label>
                            <input type="color" id="prop-shape-fill" onchange="window.editorApp.properties.setProp('fill', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Border</label>
                            <input type="color" id="prop-shape-stroke" onchange="window.editorApp.properties.setProp('stroke', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Border Width</label>
                            <input type="number" id="prop-shape-stroke-width" min="0" max="30" onchange="window.editorApp.properties.setProp('strokeWidth', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Corner Radius</label>
                            <input type="number" id="prop-shape-radius" min="0" max="100" onchange="window.editorApp.properties.setProp('rx', parseFloat(this.value)); window.editorApp.properties.setProp('ry', parseFloat(this.value));" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs">
                        </div>
                    </div>
                </div>

                <!-- SECTION: IMAGE SETTINGS -->
                <div id="props-section-image" class="space-y-3 pt-3 border-t border-slate-200">
                    <span class="text-[11px] font-bold text-slate-600 block">Image Controls</span>
                    <div class="grid grid-cols-2 gap-2">
                        <button type="button" onclick="window.editorApp.properties.flipImage('x')" class="p-2 border border-slate-200 rounded-lg text-xs font-medium hover:bg-slate-50">Flip Horizontal</button>
                        <button type="button" onclick="window.editorApp.properties.flipImage('y')" class="p-2 border border-slate-200 rounded-lg text-xs font-medium hover:bg-slate-50">Flip Vertical</button>
                    </div>
                </div>

                <!-- SECTION: TABLE SETTINGS -->
                <div id="props-section-table" class="hidden space-y-4 pt-3 border-t border-slate-200">
                    <div class="flex items-center justify-between">
                        <span class="text-[11px] font-bold text-slate-700 block">Table Styling</span>
                        <span id="table-info-badge" class="text-[10px] px-2 py-0.5 bg-indigo-50 text-indigo-700 rounded-md font-semibold">Table</span>
                    </div>

                    <!-- Header & Body Background Colors -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Header Fill</label>
                            <input type="color" id="prop-table-header-bg" onchange="window.editorApp.properties.setTableProp('headerBg', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Header Text</label>
                            <input type="color" id="prop-table-header-text" onchange="window.editorApp.properties.setTableProp('headerTextColor', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Body Fill</label>
                            <input type="color" id="prop-table-body-bg" onchange="window.editorApp.properties.setTableProp('bodyBg', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Body Text</label>
                            <input type="color" id="prop-table-body-text" onchange="window.editorApp.properties.setTableProp('bodyTextColor', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                    </div>

                    <!-- Table Borders -->
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Border Color</label>
                            <input type="color" id="prop-table-border-color" onchange="window.editorApp.properties.setTableProp('borderColor', this.value)" class="w-full h-8 rounded-lg border border-slate-200 cursor-pointer">
                        </div>
                        <div>
                            <label class="block text-[10px] font-medium text-slate-500 mb-0.5">Border Width</label>
                            <input type="number" id="prop-table-border-width" min="0" max="10" step="0.5" onchange="window.editorApp.properties.setTableProp('borderWidth', parseFloat(this.value))" class="w-full px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium">
                        </div>
                    </div>

                    <!-- Row & Column Modification -->
                    <div class="space-y-2 pt-2 border-t border-slate-100">
                        <span class="text-[10px] font-semibold text-slate-500 block">Manage Grid Size</span>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="window.editorApp.properties.addTableRow()" class="px-2.5 py-1.5 bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1 transition">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Row</span>
                            </button>
                            <button type="button" onclick="window.editorApp.properties.removeTableRow()" class="px-2.5 py-1.5 bg-slate-50 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1 transition">
                                <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                <span>Remove Row</span>
                            </button>
                        </div>
                        <div class="grid grid-cols-2 gap-2">
                            <button type="button" onclick="window.editorApp.properties.addTableCol()" class="px-2.5 py-1.5 bg-slate-50 hover:bg-indigo-50 hover:text-indigo-600 border border-slate-200 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1 transition">
                                <i data-lucide="plus" class="w-3.5 h-3.5"></i>
                                <span>Add Col</span>
                            </button>
                            <button type="button" onclick="window.editorApp.properties.removeTableCol()" class="px-2.5 py-1.5 bg-slate-50 hover:bg-rose-50 hover:text-rose-600 border border-slate-200 rounded-lg text-xs font-semibold flex items-center justify-center space-x-1 transition">
                                <i data-lucide="minus" class="w-3.5 h-3.5"></i>
                                <span>Remove Col</span>
                            </button>
                        </div>
                    </div>

                    <!-- Cell Text Tool -->
                    <div class="pt-2 border-t border-slate-100">
                        <button type="button" onclick="window.editorApp.properties.promptEditSelectedCell()" class="w-full py-2 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-bold rounded-lg transition flex items-center justify-center space-x-1.5">
                            <i data-lucide="edit-3" class="w-3.5 h-3.5"></i>
                            <span>Edit Cell Text</span>
                        </button>
                        <p class="text-[10px] text-slate-400 mt-1.5 text-center">Tip: Double-click any cell on the canvas to quickly edit its content.</p>
                    </div>
                </div>

                <!-- Opacity & Rotation -->
                <div class="pt-3 border-t border-slate-200 space-y-3">
                    <div>
                        <div class="flex justify-between text-[10px] font-medium text-slate-500 mb-1">
                            <span>Opacity</span>
                            <span id="label-opacity-val">100%</span>
                        </div>
                        <input type="range" id="prop-opacity" min="0" max="100" value="100" oninput="document.getElementById('label-opacity-val').textContent = this.value + '%'; window.editorApp.properties.setProp('opacity', parseFloat(this.value));" class="w-full accent-indigo-600">
                    </div>
                </div>
            </div>
        </aside>
    </div>

    <!-- 3. VERSION HISTORY MODAL -->
    <div id="versions-modal" class="fixed inset-0 z-50 hidden bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                <h3 class="text-base font-bold text-slate-800">Version History</h3>
                <button type="button" onclick="closeVersionsModal()" class="p-1 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100">
                    <i data-lucide="x" class="w-4 h-4"></i>
                </button>
            </div>
            <div id="versions-container" class="mt-4 max-h-72 overflow-y-auto space-y-2"></div>
        </div>
    </div>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Embedded JSON Document Configuration -->
    <script id="editor-config" type="application/json">
    {!! json_encode([
        'documentId' => $document->id,
        'csrfToken' => csrf_token(),
        'document' => $document
    ]) !!}
    </script>

    <!-- Global App Bootloader & Resilient Poller -->
    <script>
        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            if (!container) return;
            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            const isError = type === 'error';

            const bgClass = isSuccess ? 'bg-slate-900 text-white' : (isError ? 'bg-rose-600 text-white' : 'bg-indigo-600 text-white');
            const iconName = isSuccess ? 'check-circle' : (isError ? 'alert-triangle' : 'info');

            toast.className = `pointer-events-auto flex items-center space-x-2 px-4 py-2.5 rounded-xl shadow-xl text-xs font-semibold ${bgClass} transform transition-all duration-300 translate-y-3 opacity-0`;
            toast.innerHTML = `<i data-lucide="${iconName}" class="w-4 h-4 shrink-0"></i><span>${message}</span>`;
            container.appendChild(toast);
            if (window.lucide) lucide.createIcons();

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-3', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-y-3', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3000);
        };

        window.switchEditorTab = function(tabKey) {
            const tabs = ['pages', 'templates', 'text', 'shapes', 'tables', 'images', 'signatures', 'backgrounds', 'layers', 'tools'];
            tabs.forEach(t => {
                const b = document.getElementById(`tab-btn-${t}`);
                const p = document.getElementById(`tab-panel-${t}`);
                if (b) {
                    b.classList.remove('bg-indigo-600', 'text-white');
                    b.classList.add('text-slate-400', 'hover:bg-slate-100');
                }
                if (p) p.classList.add('hidden');
            });
            const targetBtn = document.getElementById(`tab-btn-${tabKey}`);
            const targetPanel = document.getElementById(`tab-panel-${tabKey}`);
            if (targetBtn) {
                targetBtn.classList.add('bg-indigo-600', 'text-white');
                targetBtn.classList.remove('text-slate-400', 'hover:bg-slate-100');
            }
            if (targetPanel) {
                targetPanel.classList.remove('hidden');
            }
            if (tabKey === 'layers' && window.editorApp && window.editorApp.layers) {
                window.editorApp.layers.refresh();
            }
            if (window.lucide) lucide.createIcons();
        };

        window.insertCustomTable = function() {
            const rowsInput = document.getElementById('table-input-rows');
            const colsInput = document.getElementById('table-input-cols');
            const rows = parseInt(rowsInput ? rowsInput.value : 3) || 3;
            const cols = parseInt(colsInput ? colsInput.value : 3) || 3;
            if (window.editorApp && window.editorApp.elements) {
                window.editorApp.elements.addTable({ rows, cols, theme: 'indigo' });
                window.showToast(`Inserted ${rows}×${cols} Table`, 'success');
            }
        };

        // Try boot with 50ms polling until modules finish loading
        let bootAttempts = 0;
        function tryBoot() {
            if (window.editorApp) {
                if (window.lucide) lucide.createIcons();
                return;
            }

            if (typeof window.bootEditor === 'function') {
                window.bootEditor();
            } else if (window.PdfEditorApp) {
                const configEl = document.getElementById('editor-config');
                const config = JSON.parse(configEl.textContent);
                window.editorApp = new window.PdfEditorApp(config);
            } else if (bootAttempts < 100) {
                bootAttempts++;
                setTimeout(tryBoot, 50);
            }

            if (window.lucide) lucide.createIcons();
        }

        document.addEventListener('DOMContentLoaded', tryBoot);
        window.addEventListener('load', tryBoot);
        tryBoot();

        window.setPageBg = function(color) {
            if (window.editorApp) {
                const activePage = window.editorApp.pageManager.getActivePage();
                if (activePage) {
                    activePage.background_color = color;
                    const wrapper = document.getElementById('canvas-wrapper');
                    if (wrapper) wrapper.style.backgroundColor = color;
                    window.editorApp.pageManager.renderThumbnails();
                    window.editorApp.triggerAutosave();
                }
            }
        };

        window.openVersionsModal = async function() {
            if (!window.editorApp) return;
            const modal = document.getElementById('versions-modal');
            const container = document.getElementById('versions-container');
            container.innerHTML = '<p class="text-xs text-slate-400">Loading versions...</p>';
            modal.classList.remove('hidden');

            try {
                const res = await fetch(`/documents/${window.editorApp.documentId}/versions`);
                const data = await res.json();
                if (data.success && data.versions) {
                    if (data.versions.length === 0) {
                        container.innerHTML = '<p class="text-xs text-slate-400">No version checkpoints yet. Click Save to create one.</p>';
                    } else {
                        container.innerHTML = data.versions.map(v => `
                            <div class="p-3 border border-slate-200 rounded-xl flex items-center justify-between hover:bg-slate-50">
                                <div>
                                    <h4 class="text-xs font-bold text-slate-800">${v.name || 'Version ' + v.version_number}</h4>
                                    <span class="text-[10px] text-slate-400">${new Date(v.created_at).toLocaleString()}</span>
                                </div>
                                <button type="button" onclick="restoreVersion(${v.id})" class="px-2.5 py-1 text-xs font-semibold text-indigo-600 bg-indigo-50 hover:bg-indigo-100 rounded-lg">Restore</button>
                            </div>
                        `).join('');
                    }
                }
            } catch(e) {
                container.innerHTML = '<p class="text-xs text-rose-500">Failed to load versions.</p>';
            }
            if (window.lucide) lucide.createIcons();
        };

        window.closeVersionsModal = function() {
            document.getElementById('versions-modal').classList.add('hidden');
        };

        window.restoreVersion = async function(versionId) {
            if (!confirm('Restore document to this previous version? Current unsaved work will be replaced.')) return;
            try {
                const res = await fetch(`/documents/${window.editorApp.documentId}/versions/${versionId}/restore`, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'), 'Accept': 'application/json' }
                });
                const data = await res.json();
                if (data.success) {
                    window.location.reload();
                }
            } catch(e) {
                window.showToast('Failed to restore version', 'error');
            }
        };
    </script>
</body>
</html>
