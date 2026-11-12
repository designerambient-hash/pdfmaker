@extends('layouts.app')

@section('title', 'Free Online PDF Utilities')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-black text-slate-900 tracking-tight">Free Online PDF Tools</h1>
        <p class="text-sm text-slate-500 mt-1">Merge, split, rotate, watermark, page-number, and convert images into PDFs directly in your browser without file restrictions.</p>
    </div>

    <!-- Tools Navigation Tabs -->
    <div class="flex items-center space-x-2 border-b border-slate-200 pb-px mb-8 overflow-x-auto">
        <button type="button" onclick="switchToolTab('merge')" id="tab-btn-merge" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-indigo-600 text-indigo-600 flex items-center space-x-2">
            <i data-lucide="merge" class="w-4 h-4"></i>
            <span>Merge PDF</span>
        </button>
        <button type="button" onclick="switchToolTab('split')" id="tab-btn-split" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
            <i data-lucide="scissors" class="w-4 h-4"></i>
            <span>Split PDF</span>
        </button>
        <button type="button" onclick="switchToolTab('rotate')" id="tab-btn-rotate" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
            <i data-lucide="rotate-cw" class="w-4 h-4"></i>
            <span>Rotate PDF</span>
        </button>
        <button type="button" onclick="switchToolTab('watermark')" id="tab-btn-watermark" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
            <i data-lucide="stamp" class="w-4 h-4"></i>
            <span>Add Watermark</span>
        </button>
        <button type="button" onclick="switchToolTab('page-numbers')" id="tab-btn-page-numbers" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
            <i data-lucide="hash" class="w-4 h-4"></i>
            <span>Add Page Numbers</span>
        </button>
        <button type="button" onclick="switchToolTab('images-to-pdf')" id="tab-btn-images-to-pdf" class="tool-tab-btn px-4 py-2.5 text-sm font-semibold border-b-2 border-transparent text-slate-500 hover:text-slate-700 flex items-center space-x-2">
            <i data-lucide="image" class="w-4 h-4"></i>
            <span>Images to PDF</span>
        </button>
    </div>

    <!-- 1. MERGE PDF PANEL -->
    <div id="panel-merge" class="tool-panel">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="merge" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Merge Multiple PDFs into One</h2>
                <p class="text-xs text-slate-400 mt-1">Select 2 or more PDF documents to combine in sequential order.</p>
            </div>

            <form id="form-merge" onsubmit="handleToolSubmit(event, '{{ route('tools.merge') }}', 'form-merge', 'merge-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('merge-files').click()">
                    <i data-lucide="file-plus-2" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Choose 2 or more PDF files</p>
                    <input type="file" id="merge-files" name="pdf_files[]" accept="application/pdf" multiple required class="hidden" onchange="showSelectedFiles(this, 'merge-file-list')">
                    <div id="merge-file-list" class="mt-3 text-xs text-indigo-600 space-y-1"></div>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="layers" class="w-4 h-4"></i>
                    <span>Merge PDFs Now</span>
                </button>
            </form>

            <div id="merge-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>

    <!-- 2. SPLIT PDF PANEL -->
    <div id="panel-split" class="tool-panel hidden">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="scissors" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Split PDF into Pages</h2>
                <p class="text-xs text-slate-400 mt-1">Extract specific pages or break a PDF into separate files.</p>
            </div>

            <form id="form-split" onsubmit="handleToolSubmit(event, '{{ route('tools.split') }}', 'form-split', 'split-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('split-file').click()">
                    <i data-lucide="file-up" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Choose PDF to split</p>
                    <input type="file" id="split-file" name="pdf_file" accept="application/pdf" required class="hidden" onchange="showSelectedFiles(this, 'split-file-name')">
                    <div id="split-file-name" class="mt-2 text-xs font-semibold text-indigo-600"></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Pages to Extract (Optional)</label>
                    <input type="text" name="pages" placeholder="e.g. 1, 2-4 (leave empty to split all pages)" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:bg-white focus:ring-2 focus:ring-indigo-500">
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="scissors" class="w-4 h-4"></i>
                    <span>Split PDF</span>
                </button>
            </form>

            <div id="split-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>

    <!-- 3. ROTATE PDF PANEL -->
    <div id="panel-rotate" class="tool-panel hidden">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="rotate-cw" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Rotate PDF Document</h2>
                <p class="text-xs text-slate-400 mt-1">Rotate all pages in your PDF clockwise.</p>
            </div>

            <form id="form-rotate" onsubmit="handleToolSubmit(event, '{{ route('tools.rotate') }}', 'form-rotate', 'rotate-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('rotate-file').click()">
                    <i data-lucide="file-up" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Choose PDF to rotate</p>
                    <input type="file" id="rotate-file" name="pdf_file" accept="application/pdf" required class="hidden" onchange="showSelectedFiles(this, 'rotate-file-name')">
                    <div id="rotate-file-name" class="mt-2 text-xs font-semibold text-indigo-600"></div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Rotation Angle</label>
                    <select name="angle" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                        <option value="90">90° Clockwise</option>
                        <option value="180">180° Upside Down</option>
                        <option value="270">270° Counter-Clockwise</option>
                    </select>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="rotate-cw" class="w-4 h-4"></i>
                    <span>Rotate PDF</span>
                </button>
            </form>

            <div id="rotate-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>

    <!-- 4. WATERMARK PANEL -->
    <div id="panel-watermark" class="tool-panel hidden">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="stamp" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Add Text Watermark</h2>
                <p class="text-xs text-slate-400 mt-1">Stamp CONFIDENTIAL, DRAFT, or custom watermark text onto pages.</p>
            </div>

            <form id="form-watermark" onsubmit="handleToolSubmit(event, '{{ route('tools.watermark') }}', 'form-watermark', 'watermark-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('watermark-file').click()">
                    <i data-lucide="file-up" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Choose PDF to watermark</p>
                    <input type="file" id="watermark-file" name="pdf_file" accept="application/pdf" required class="hidden" onchange="showSelectedFiles(this, 'watermark-file-name')">
                    <div id="watermark-file-name" class="mt-2 text-xs font-semibold text-indigo-600"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Watermark Text</label>
                        <input type="text" name="watermark_text" value="CONFIDENTIAL" required class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Position</label>
                        <select name="position" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="center" selected>Center of Page</option>
                            <option value="top">Top Header</option>
                            <option value="bottom">Bottom Footer</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="stamp" class="w-4 h-4"></i>
                    <span>Apply Watermark</span>
                </button>
            </form>

            <div id="watermark-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>

    <!-- 5. PAGE NUMBERS PANEL -->
    <div id="panel-page-numbers" class="tool-panel hidden">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="hash" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Insert Automatic Page Numbers</h2>
                <p class="text-xs text-slate-400 mt-1">Number pages automatically across your entire PDF document.</p>
            </div>

            <form id="form-page-numbers" onsubmit="handleToolSubmit(event, '{{ route('tools.pageNumbers') }}', 'form-page-numbers', 'page-numbers-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('pn-file').click()">
                    <i data-lucide="file-up" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Choose PDF to number</p>
                    <input type="file" id="pn-file" name="pdf_file" accept="application/pdf" required class="hidden" onchange="showSelectedFiles(this, 'pn-file-name')">
                    <div id="pn-file-name" class="mt-2 text-xs font-semibold text-indigo-600"></div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Format Pattern</label>
                        <select name="format" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="Page {page} of {total}" selected>Page {page} of {total}</option>
                            <option value="{page} / {total}">{page} / {total}</option>
                            <option value="{page}">{page}</option>
                            <option value="- {page} -">- {page} -</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Position</label>
                        <select name="position" class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs">
                            <option value="bottom-center" selected>Bottom Center</option>
                            <option value="bottom-right">Bottom Right</option>
                            <option value="top-center">Top Center</option>
                            <option value="top-right">Top Right</option>
                        </select>
                    </div>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="hash" class="w-4 h-4"></i>
                    <span>Add Numbers</span>
                </button>
            </form>

            <div id="page-numbers-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>

    <!-- 6. IMAGES TO PDF PANEL -->
    <div id="panel-images-to-pdf" class="tool-panel hidden">
        <div class="bg-white rounded-2xl border border-slate-200 p-8 shadow-xs max-w-2xl mx-auto">
            <div class="text-center mb-6">
                <div class="w-12 h-12 bg-indigo-50 text-indigo-600 rounded-2xl flex items-center justify-center mx-auto mb-3">
                    <i data-lucide="image" class="w-6 h-6"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">Convert Images to PDF</h2>
                <p class="text-xs text-slate-400 mt-1">Convert JPG, PNG, or WEBP photos into a clean multi-page PDF.</p>
            </div>

            <form id="form-images-to-pdf" onsubmit="handleToolSubmit(event, '{{ route('tools.imagesToPdf') }}', 'form-images-to-pdf', 'images-to-pdf-result')" class="space-y-4">
                @csrf
                <div class="border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center hover:border-indigo-500 transition-colors cursor-pointer" onclick="document.getElementById('img-files').click()">
                    <i data-lucide="image-plus" class="w-8 h-8 text-indigo-500 mx-auto mb-2"></i>
                    <p class="text-xs font-semibold text-slate-700">Select images (JPG, PNG, WEBP)</p>
                    <input type="file" id="img-files" name="images[]" accept="image/jpeg,image/png,image/webp" multiple required class="hidden" onchange="showSelectedFiles(this, 'img-file-list')">
                    <div id="img-file-list" class="mt-3 text-xs text-indigo-600 space-y-1"></div>
                </div>

                <button type="submit" class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition shadow-sm flex items-center justify-center space-x-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i>
                    <span>Convert to PDF</span>
                </button>
            </form>

            <div id="images-to-pdf-result" class="hidden mt-6 pt-6 border-t border-slate-100 text-center"></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function switchToolTab(tabKey) {
        document.querySelectorAll('.tool-panel').forEach(p => p.classList.add('hidden'));
        document.querySelectorAll('.tool-tab-btn').forEach(b => {
            b.classList.remove('border-indigo-600', 'text-indigo-600');
            b.classList.add('border-transparent', 'text-slate-500');
        });

        const targetPanel = document.getElementById(`panel-${tabKey}`);
        const targetBtn = document.getElementById(`tab-btn-${tabKey}`);
        if (targetPanel) targetPanel.classList.remove('hidden');
        if (targetBtn) {
            targetBtn.classList.add('border-indigo-600', 'text-indigo-600');
            targetBtn.classList.remove('border-transparent', 'text-slate-500');
        }
    }

    function showSelectedFiles(input, containerId) {
        const c = document.getElementById(containerId);
        c.innerHTML = '';
        if (input.files) {
            Array.from(input.files).forEach((f, idx) => {
                const el = document.createElement('div');
                el.textContent = `✓ ${f.name} (${Math.round(f.size / 1024)} KB)`;
                c.appendChild(el);
            });
        }
    }

    async function handleToolSubmit(event, url, formId, resultId) {
        event.preventDefault();
        const form = document.getElementById(formId);
        const resultContainer = document.getElementById(resultId);
        const submitBtn = form.querySelector('button[type="submit"]');

        const originalBtnHtml = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<span class="animate-spin inline-block mr-2">◌</span> Processing...';
        resultContainer.classList.add('hidden');

        try {
            const formData = new FormData(form);
            const res = await fetch(url, {
                method: 'POST',
                body: formData,
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (data.success) {
                window.showToast(data.message || 'Operation succeeded!', 'success');
                if (data.file_url) {
                    resultContainer.innerHTML = `
                        <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl text-emerald-800">
                            <p class="font-bold text-sm mb-2">Ready to download!</p>
                            <a href="${data.file_url}" download="${data.filename || 'document.pdf'}" class="inline-flex items-center space-x-2 px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition">
                                <i data-lucide="download" class="w-4 h-4"></i>
                                <span>Download PDF</span>
                            </a>
                        </div>
                    `;
                } else if (data.files && data.files.length) {
                    let links = data.files.map((f, i) => `<a href="${f}" download="page_${i+1}.pdf" class="inline-block px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold text-xs rounded-lg border border-indigo-200 m-1">Page ${i+1}</a>`).join('');
                    resultContainer.innerHTML = `
                        <div class="p-4 bg-indigo-50 border border-indigo-200 rounded-xl text-indigo-900">
                            <p class="font-bold text-sm mb-2">Split complete! Download pages:</p>
                            <div class="flex flex-wrap justify-center">${links}</div>
                        </div>
                    `;
                }
                resultContainer.classList.remove('hidden');
                if (window.lucide) lucide.createIcons();
            } else {
                window.showToast(data.message || 'Error executing tool', 'error');
            }
        } catch (e) {
            window.showToast('Request failed. Check file size or format.', 'error');
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalBtnHtml;
            if (window.lucide) lucide.createIcons();
        }
    }
</script>
@endpush
@endsection

