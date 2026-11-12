<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Free PDF Editor & Creator') | PDFCraft</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Caveat:wght@600&family=Dancing+Script:wght@700&family=Fira+Code:wght@400;600&family=Inter:wght@300;400;500;600;700;800;900&family=Merriweather:ital,wght@0,400;0,700;1,400&family=Montserrat:wght@400;600;800&family=Orbitron:wght@600&family=Oswald:wght@500;700&family=Outfit:wght@400;600;700&family=Playfair+Display:ital,wght@0,600;0,800;1,600&family=Poppins:wght@400;600;700&family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">

    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        body {
            font-family: 'Inter', system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        /* Custom scrollbars */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
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
    </style>
    @stack('styles')
</head>
<body class="h-full flex flex-col text-slate-800 antialiased selection:bg-indigo-500 selection:text-white">

    <!-- Navigation Header -->
    <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-xs">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo & Brand -->
                <div class="flex items-center space-x-3">
                    <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 group">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-blue-600 flex items-center justify-center text-white shadow-md shadow-indigo-200 group-hover:scale-105 transition-transform duration-200">
                            <i data-lucide="file-pen" class="w-5 h-5"></i>
                        </div>
                        <div>
                            <span class="text-xl font-extrabold tracking-tight bg-gradient-to-r from-indigo-700 via-indigo-600 to-blue-600 bg-clip-text text-transparent">PDFCraft</span>
                            <span class="ml-1.5 px-2 py-0.5 text-[10px] font-semibold tracking-wide uppercase bg-emerald-50 text-emerald-700 rounded-full border border-emerald-200">100% Free</span>
                        </div>
                    </a>

                    <!-- Nav Links -->
                    <nav class="hidden md:flex items-center space-x-1 ml-8">
                        <a href="{{ route('dashboard') }}" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('dashboard*') && !request()->routeIs('tools*') && !request()->routeIs('templates*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="layout-dashboard" class="w-4 h-4"></i>
                                <span>Documents</span>
                            </div>
                        </a>
                        <a href="{{ route('templates.index') }}" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('templates*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="layout-template" class="w-4 h-4"></i>
                                <span>Templates</span>
                            </div>
                        </a>
                        <a href="{{ route('tools.index') }}" class="px-3.5 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tools*') ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' }}">
                            <div class="flex items-center space-x-2">
                                <i data-lucide="wrench" class="w-4 h-4"></i>
                                <span>PDF Tools</span>
                            </div>
                        </a>
                    </nav>
                </div>

                <!-- Right Header Actions -->
                <div class="flex items-center space-x-3">
                    <button type="button" onclick="window.openImportModal()" class="hidden sm:inline-flex items-center space-x-2 px-3.5 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors shadow-2xs">
                        <i data-lucide="upload-cloud" class="w-4 h-4 text-indigo-600"></i>
                        <span>Import PDF</span>
                    </button>

                    <button type="button" onclick="window.openCreateModal()" class="inline-flex items-center space-x-2 px-4 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition-colors shadow-sm shadow-indigo-200">
                        <i data-lucide="plus" class="w-4 h-4"></i>
                        <span>Create PDF</span>
                    </button>

                    @auth
                        <!-- User Profile & Logout -->
                        <div class="flex items-center border-l border-slate-200 pl-3 ml-1 space-x-2.5">
                            <div class="flex items-center space-x-2">
                                <div class="w-8 h-8 rounded-full bg-gradient-to-tr from-indigo-600 to-blue-500 text-white flex items-center justify-center font-bold text-xs shadow-xs">
                                    {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                                </div>
                                <div class="hidden lg:block text-left leading-tight">
                                    <div class="text-xs font-semibold text-slate-800">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] text-slate-400 truncate max-w-[120px]">{{ Auth::user()->email }}</div>
                                </div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" title="Logout" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors cursor-pointer" onclick="return confirm('Are you sure you want to log out?');">
                                    <i data-lucide="log-out" class="w-4 h-4"></i>
                                </button>
                            </form>
                        </div>
                    @else
                        <div class="flex items-center space-x-2 border-l border-slate-200 pl-3 ml-1">
                            <a href="{{ route('login') }}" class="px-3 py-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800">Log In</a>
                            <a href="{{ route('register') }}" class="px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700">Sign Up</a>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content Area -->
    <main class="flex-1">
        @yield('content')
    </main>

    <!-- Global Toast Container -->
    <div id="toast-container" class="fixed bottom-5 right-5 z-50 flex flex-col space-y-2 pointer-events-none"></div>

    <!-- Create Document Modal -->
    <div id="create-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200 animate-in fade-in zoom-in duration-150">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="file-plus" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Create New Document</h3>
                </div>
                <button type="button" onclick="window.closeCreateModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('documents.store') }}" method="POST" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Document Title</label>
                    <input type="text" name="title" value="Untitled Document" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm font-medium transition-all" placeholder="Enter document title...">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Page Size</label>
                        <select id="modal-page-size" name="page_size" onchange="window.toggleCustomDims()" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium focus:ring-2 focus:ring-indigo-500">
                            <option value="A4" selected>A4 (Standard 210 × 297 mm)</option>
                            <option value="Letter">US Letter (8.5 × 11 in)</option>
                            <option value="A3">A3 (Large 297 × 420 mm)</option>
                            <option value="A5">A5 (Half A4 148 × 210 mm)</option>
                            <option value="Legal">Legal (8.5 × 14 in)</option>
                            <option value="Tabloid">Tabloid (11 × 17 in)</option>
                            <option value="Business Card">Business Card (3.5 × 2 in)</option>
                            <option value="Custom">Custom Dimensions</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Orientation</label>
                        <div class="grid grid-cols-2 gap-1.5 p-1 bg-slate-100 rounded-xl">
                            <label class="flex items-center justify-center py-1.5 text-xs font-medium rounded-lg cursor-pointer transition-all has-checked:bg-white has-checked:text-indigo-700 has-checked:shadow-xs text-slate-600">
                                <input type="radio" name="orientation" value="portrait" checked class="sr-only">
                                <span>Portrait</span>
                            </label>
                            <label class="flex items-center justify-center py-1.5 text-xs font-medium rounded-lg cursor-pointer transition-all has-checked:bg-white has-checked:text-indigo-700 has-checked:shadow-xs text-slate-600">
                                <input type="radio" name="orientation" value="landscape" class="sr-only">
                                <span>Landscape</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div id="custom-dims-container" class="hidden grid grid-cols-3 gap-3 p-3 bg-slate-50 border border-slate-200 rounded-xl">
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Width</label>
                        <input type="number" name="custom_width" value="794" min="50" max="5000" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Height</label>
                        <input type="number" name="custom_height" value="1123" min="50" max="5000" class="w-full px-2.5 py-1.5 bg-white border border-slate-200 rounded-lg text-xs">
                    </div>
                    <div>
                        <label class="block text-[11px] font-medium text-slate-500 mb-1">Unit</label>
                        <select name="unit" class="w-full px-2 py-1.5 bg-white border border-slate-200 rounded-lg text-xs">
                            <option value="px" selected>Pixels (px)</option>
                            <option value="mm">Millimeters (mm)</option>
                        </select>
                    </div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-2 border-t border-slate-100">
                    <button type="button" onclick="window.closeCreateModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm shadow-indigo-200 flex items-center space-x-2">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>Start Creating</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Import PDF Modal -->
    <div id="import-modal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-slate-900/50 backdrop-blur-xs flex items-center justify-center p-4">
        <div class="bg-white rounded-2xl max-w-lg w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                <div class="flex items-center space-x-3">
                    <div class="w-9 h-9 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center">
                        <i data-lucide="upload-cloud" class="w-5 h-5"></i>
                    </div>
                    <h3 class="text-lg font-bold text-slate-800">Import Existing PDF</h3>
                </div>
                <button type="button" onclick="window.closeImportModal()" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-100">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form action="{{ route('pdf.import') }}" method="POST" enctype="multipart/form-data" class="mt-5 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Document Title (Optional)</label>
                    <input type="text" name="title" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:bg-white text-sm" placeholder="Defaults to file name...">
                </div>

                <div class="border-2 border-dashed border-slate-300 hover:border-indigo-500 rounded-2xl p-8 text-center transition-colors bg-slate-50/50 cursor-pointer" onclick="document.getElementById('pdf-file-input').click()">
                    <i data-lucide="file-up" class="w-10 h-10 mx-auto text-indigo-500 mb-3"></i>
                    <p class="text-sm font-semibold text-slate-700">Click to choose a PDF or drag and drop here</p>
                    <p class="text-xs text-slate-400 mt-1">Supports up to 50MB documents</p>
                    <input type="file" id="pdf-file-input" name="pdf_file" accept="application/pdf" required class="hidden" onchange="window.handlePdfSelect(this)">
                    <div id="pdf-chosen-name" class="mt-3 text-xs font-semibold text-indigo-600 hidden"></div>
                </div>

                <div class="pt-4 flex items-center justify-end space-x-2 border-t border-slate-100">
                    <button type="button" onclick="window.closeImportModal()" class="px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-100 rounded-xl transition-colors">Cancel</button>
                    <button type="submit" id="btn-submit-import" class="px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition-all shadow-sm shadow-indigo-200 flex items-center space-x-2">
                        <i data-lucide="arrow-right" class="w-4 h-4"></i>
                        <span>Import & Open</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Global App Helpers & Toast Script -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            if (window.lucide) {
                lucide.createIcons();
            }
        });

        window.openCreateModal = function() {
            document.getElementById('create-modal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        };
        window.closeCreateModal = function() {
            document.getElementById('create-modal').classList.add('hidden');
        };
        window.openImportModal = function() {
            document.getElementById('import-modal').classList.remove('hidden');
            if (window.lucide) lucide.createIcons();
        };
        window.closeImportModal = function() {
            document.getElementById('import-modal').classList.add('hidden');
        };
        window.toggleCustomDims = function() {
            const select = document.getElementById('modal-page-size');
            const custom = document.getElementById('custom-dims-container');
            if (select.value === 'Custom') {
                custom.classList.remove('hidden');
            } else {
                custom.classList.add('hidden');
            }
        };
        window.handlePdfSelect = function(input) {
            const label = document.getElementById('pdf-chosen-name');
            if (input.files && input.files[0]) {
                label.textContent = 'Selected: ' + input.files[0].name;
                label.classList.remove('hidden');
            }
        };

        window.showToast = function(message, type = 'success') {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const isSuccess = type === 'success';
            const isError = type === 'error';
            const isInfo = type === 'info';

            const bgClass = isSuccess ? 'bg-slate-900 text-white' : (isError ? 'bg-rose-600 text-white' : 'bg-indigo-600 text-white');
            const iconName = isSuccess ? 'check-circle' : (isError ? 'alert-triangle' : 'info');

            toast.className = `pointer-events-auto flex items-center space-x-2.5 px-4 py-3 rounded-xl shadow-xl text-sm font-medium ${bgClass} transform transition-all duration-300 translate-y-3 opacity-0`;
            toast.innerHTML = `
                <i data-lucide="${iconName}" class="w-4 h-4 shrink-0"></i>
                <span>${message}</span>
            `;
            container.appendChild(toast);
            if (window.lucide) lucide.createIcons();

            requestAnimationFrame(() => {
                toast.classList.remove('translate-y-3', 'opacity-0');
            });

            setTimeout(() => {
                toast.classList.add('translate-y-3', 'opacity-0');
                setTimeout(() => toast.remove(), 300);
            }, 3500);
        };
    </script>
    @stack('scripts')
</body>
</html>

