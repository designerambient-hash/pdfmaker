/**
 * Multi-Page Manager: Handles switching, adding, duplicating, reordering, styling pages
 */
export class PageManager {
    constructor(editor, initialPages) {
        this.editor = editor;
        this.pages = [...initialPages];
        this.activePageIndex = 0;
        this.container = document.getElementById('pages-thumbnails-list');
    }

    getActivePage() {
        return this.pages[this.activePageIndex];
    }

    renderThumbnails() {
        if (!this.container) return;
        this.container.innerHTML = '';

        this.pages.forEach((page, index) => {
            const card = document.createElement('div');
            const isActive = index === this.activePageIndex;

            card.className = `p-2.5 rounded-2xl border transition-all cursor-pointer relative group ${
                isActive ? 'bg-indigo-50/70 border-indigo-500 shadow-xs' : 'bg-white border-slate-200 hover:border-indigo-200'
            }`;

            card.innerHTML = `
                <div class="flex items-center justify-between mb-2">
                    <span class="text-[11px] font-bold ${isActive ? 'text-indigo-700' : 'text-slate-600'}">Page ${index + 1}</span>
                    <div class="flex items-center space-x-1 opacity-0 group-hover:opacity-100 transition-opacity">
                        <button type="button" class="btn-dup-page p-1 hover:bg-slate-100 rounded text-slate-400 hover:text-indigo-600" title="Duplicate Page">
                            <i data-lucide="copy" class="w-3 h-3"></i>
                        </button>
                        <button type="button" class="btn-del-page p-1 hover:bg-rose-50 rounded text-slate-400 hover:text-rose-600" title="Delete Page">
                            <i data-lucide="trash" class="w-3 h-3"></i>
                        </button>
                    </div>
                </div>

                <!-- Page Preview Mini Box -->
                <div class="aspect-[4/5] bg-white rounded-lg border border-slate-200 shadow-2xs overflow-hidden flex items-center justify-center relative p-1" style="background-color: ${page.background_color || '#ffffff'}">
                    ${page.thumbnail ? `<img src="${page.thumbnail}" class="w-full h-full object-contain">` : `<span class="text-[10px] text-slate-300 font-mono">${Math.round(page.width)}×${Math.round(page.height)}</span>`}
                </div>
            `;

            card.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                this.switchToPage(index);
            });

            card.querySelector('.btn-dup-page').onclick = (e) => {
                e.stopPropagation();
                this.duplicatePage(page.id);
            };

            card.querySelector('.btn-del-page').onclick = (e) => {
                e.stopPropagation();
                this.deletePage(page.id);
            };

            this.container.appendChild(card);
        });

        // Update bottom page counter
        const counterEl = document.getElementById('bottom-page-counter');
        if (counterEl) {
            counterEl.textContent = `Page ${this.activePageIndex + 1} of ${this.pages.length}`;
        }

        if (window.lucide) lucide.createIcons();
    }

    async switchToPage(index) {
        if (index < 0 || index >= this.pages.length || index === this.activePageIndex) return;

        // 1. Save current page canvas state
        await this.editor.saveCurrentPageState();

        // 2. Set new active index
        this.activePageIndex = index;
        const newPage = this.pages[index];

        // 3. Load new page into Fabric canvas
        await this.editor.loadPageIntoCanvas(newPage);

        // 4. Update UI
        this.renderThumbnails();
    }

    async addNewPage() {
        try {
            await this.editor.saveCurrentPageState();
        } catch(e) {
            console.warn('saveCurrentPageState warning before add:', e);
        }

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.editor.csrfToken;
            const res = await fetch(`/documents/${this.editor.documentId}/pages`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    width: this.editor.documentData.width,
                    height: this.editor.documentData.height,
                    background_color: '#ffffff',
                })
            });

            if (!res.ok) {
                const errText = await res.text();
                console.error('Add page error:', res.status, errText);
                window.showToast('Failed to add page (HTTP ' + res.status + ')', 'error');
                return;
            }

            const data = await res.json();
            if (data.success) {
                this.pages = data.pages;
                this.activePageIndex = this.pages.length - 1;
                await this.editor.loadPageIntoCanvas(this.pages[this.activePageIndex]);
                this.renderThumbnails();
                window.showToast('New page added', 'success');
            } else {
                window.showToast(data.message || 'Failed to add page', 'error');
            }
        } catch (e) {
            console.error('addNewPage catch:', e);
            window.showToast('Failed to add page: ' + e.message, 'error');
        }
    }

    async duplicatePage(pageId) {
        try {
            await this.editor.saveCurrentPageState();
        } catch(e) {}

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.editor.csrfToken;
            const res = await fetch(`/documents/${this.editor.documentId}/pages/${pageId}/duplicate`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!res.ok) {
                window.showToast('Failed to duplicate page (HTTP ' + res.status + ')', 'error');
                return;
            }

            const data = await res.json();
            if (data.success) {
                this.pages = data.pages;
                // Switch to duplicated page
                const dupIndex = this.pages.findIndex(p => p.id === data.page.id);
                if (dupIndex !== -1) {
                    this.activePageIndex = dupIndex;
                    await this.editor.loadPageIntoCanvas(this.pages[dupIndex]);
                }
                this.renderThumbnails();
                window.showToast('Page duplicated', 'success');
            }
        } catch (e) {
            window.showToast('Failed to duplicate page: ' + e.message, 'error');
        }
    }

    async deletePage(pageId) {
        if (this.pages.length <= 1) {
            window.showToast('Document must contain at least one page.', 'error');
            return;
        }

        if (!confirm('Are you sure you want to delete this page?')) return;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.editor.csrfToken;
            const res = await fetch(`/documents/${this.editor.documentId}/pages/${pageId}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!res.ok) {
                window.showToast('Failed to delete page (HTTP ' + res.status + ')', 'error');
                return;
            }

            const data = await res.json();
            if (data.success) {
                this.pages = data.pages;
                if (this.activePageIndex >= this.pages.length) {
                    this.activePageIndex = this.pages.length - 1;
                }
                await this.editor.loadPageIntoCanvas(this.pages[this.activePageIndex]);
                this.renderThumbnails();
                window.showToast('Page deleted', 'info');
            }
        } catch (e) {
            window.showToast('Failed to delete page: ' + e.message, 'error');
        }
    }

    prevPage() {
        if (this.activePageIndex > 0) {
            this.switchToPage(this.activePageIndex - 1);
        }
    }

    nextPage() {
        if (this.activePageIndex < this.pages.length - 1) {
            this.switchToPage(this.activePageIndex + 1);
        }
    }
}

