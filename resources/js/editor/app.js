import * as fabric from 'fabric';
import { HistoryManager } from './history.js';
import { ElementManager } from './elements.js';
import { PropertiesManager } from './properties.js';
import { LayerManager } from './layers.js';
import { PageManager } from './pages.js';
import { ExportManager } from './export.js';

// Fabric 7 backwards-compatibility polyfills & custom properties
export const TABLE_CUSTOM_PROPERTIES = [
    'id', 'name', 'locked', 'layerName', 'hasControls', 'selectable',
    'rx', 'ry', 'strokeDashArray',
    'isTable', 'tableRows', 'tableCols', 'headerBg', 'bodyBg', 'altBodyBg',
    'borderColor', 'borderWidth', 'headerTextColor', 'bodyTextColor',
    'isTableCellBg', 'isTableCellText', 'isHeaderCell', 'cellRow', 'cellCol',
    'charSpacing', 'lineHeight', 'subTargetCheck'
];

if (fabric.FabricObject) {
    fabric.FabricObject.customProperties = TABLE_CUSTOM_PROPERTIES;
}
if (fabric.Group) {
    fabric.Group.customProperties = TABLE_CUSTOM_PROPERTIES;
}
if (fabric.FabricText && fabric.FabricText.ownDefaults) {
    fabric.FabricText.ownDefaults.padding = 8;
}
if (fabric.IText && fabric.IText.ownDefaults) {
    fabric.IText.ownDefaults.padding = 8;
}
if (fabric.Textbox && fabric.Textbox.ownDefaults) {
    fabric.Textbox.ownDefaults.padding = 8;
}

if (fabric.Canvas) {
    const proto = fabric.Canvas.prototype;

    if (!proto.setWidth) {
        proto.setWidth = function(w) {
            return this.setDimensions ? this.setDimensions({ width: w }) : (this.width = w);
        };
    }
    if (!proto.setHeight) {
        proto.setHeight = function(h) {
            return this.setDimensions ? this.setDimensions({ height: h }) : (this.height = h);
        };
    }
    if (!proto.getCenter) {
        proto.getCenter = function() {
            const pt = this.getCenterPoint ? this.getCenterPoint() : null;
            return {
                left: pt ? (pt.x ?? pt.left) : (this.width || 800) / 2,
                top: pt ? (pt.y ?? pt.top) : (this.height || 1100) / 2,
            };
        };
    }
    if (!proto.bringForward) {
        proto.bringForward = function(obj) {
            return this.bringObjectForward ? this.bringObjectForward(obj) : null;
        };
    }
    if (!proto.sendBackwards) {
        proto.sendBackwards = function(obj) {
            return this.sendObjectBackwards ? this.sendObjectBackwards(obj) : null;
        };
    }
    if (!proto.bringToFront) {
        proto.bringToFront = function(obj) {
            return this.bringObjectToFront ? this.bringObjectToFront(obj) : null;
        };
    }
    if (!proto.sendToBack) {
        proto.sendToBack = function(obj) {
            return this.sendObjectToBack ? this.sendObjectToBack(obj) : null;
        };
    }
}

export class PdfEditorApp {
    constructor(config) {
        this.documentId = config.documentId;
        this.csrfToken = config.csrfToken;
        this.documentData = config.document;
        this.zoomLevel = 1.0;
        this.copiedObject = null;
        this.autosaveTimer = null;
        this.isSaving = false;

        this.init();
    }

    async init() {
        // 1. Initialize Fabric Canvas
        const canvasEl = document.getElementById('main-editor-canvas');
        this.canvas = new fabric.Canvas(canvasEl, {
            preserveObjectStacking: true,
            selection: true,
            uniformScaling: false,
            stopContextMenu: true,
            fireRightClick: true,
        });

        // 2. Initialize Subsystems
        this.history = new HistoryManager(this.canvas, () => {
            this.triggerAutosave();
        });

        this.elements = new ElementManager(this.canvas, this.history);
        this.properties = new PropertiesManager(this.canvas, this.history);
        this.layers = new LayerManager(this.canvas, this.history);
        this.pageManager = new PageManager(this, this.documentData.pages || []);
        this.exportManager = new ExportManager(this);

        // 3. Load first page
        if (this.pageManager.pages.length > 0) {
            await this.loadPageIntoCanvas(this.pageManager.pages[0]);
            this.pageManager.renderThumbnails();
        }

        // 4. Setup Event Listeners & UI Controls
        this.initCanvasEvents();
        this.initKeyboardShortcuts();
        this.initToolbarControls();
        this.initSidebarTabs();
        this.initSignaturePad();
        this.initAssetUploads();

        // 5. Auto zoom to fit viewport
        setTimeout(() => this.zoomToFit(), 200);

        // 6. Recalculate text dimensions once webfonts load to prevent character clipping
        if (document.fonts) {
            document.fonts.ready.then(() => {
                if (this.canvas) {
                    this.canvas.getObjects().forEach(obj => {
                        if (obj.initDimensions) {
                            obj.padding = 8;
                            obj.initDimensions();
                            obj.setCoords();
                        }
                    });
                    this.canvas.requestRenderAll();
                }
            });
        }

        // Expose instance globally for inline callbacks if needed
        window.editorApp = this;
    }

    async loadTemplate(templateId) {
        try {
            window.showToast('Applying template layout...', 'info');
            const res = await fetch(`/templates/${templateId}/use`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' }
            });
            const data = await res.json();
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            }
        } catch (e) {
            window.showToast('Failed to apply template', 'error');
        }
    }

    async loadPageIntoCanvas(pageModel) {
        // Set canvas viewport dimensions to match page dimensions (Fabric 7 setDimensions)
        if (this.canvas.setDimensions) {
            this.canvas.setDimensions({ width: pageModel.width, height: pageModel.height });
        } else {
            if (this.canvas.setWidth) this.canvas.setWidth(pageModel.width);
            if (this.canvas.setHeight) this.canvas.setHeight(pageModel.height);
        }

        // Update container sizing
        const canvasWrapper = document.getElementById('canvas-wrapper');
        if (canvasWrapper) {
            canvasWrapper.style.width = `${pageModel.width}px`;
            canvasWrapper.style.height = `${pageModel.height}px`;
            canvasWrapper.style.backgroundColor = pageModel.background_color || '#ffffff';
        }

        this.canvas.clear();

        if (pageModel.canvas_data) {
            let json;
            try {
                json = typeof pageModel.canvas_data === 'string'
                    ? JSON.parse(pageModel.canvas_data)
                    : pageModel.canvas_data;
            } catch (e) {
                json = { objects: [] };
            }

            try {
                await this.canvas.loadFromJSON(json);
                // Ensure revived table objects retain subTargetCheck and text objects don't clip
                const objs = this.canvas.getObjects();
                objs.forEach(o => {
                    if (o.isTable) {
                        o.subTargetCheck = true;
                    }
                    if (o.initDimensions) {
                        o.padding = 8;
                        o.initDimensions();
                        o.setCoords();
                    }
                });
            } catch (e) {
                console.warn('Canvas JSON load fallback:', e);
            }

            this.canvas.renderAll();
            this.history.reset(JSON.stringify(this.canvas.toJSON()));
            this.layers.refresh();
        } else {
            this.history.reset(JSON.stringify(this.canvas.toJSON()));
            this.layers.refresh();
        }
    }

    async saveCurrentPageState() {
        const activePage = this.pageManager.getActivePage();
        if (!activePage) return;

        // Serialize current fabric objects
        activePage.canvas_data = JSON.stringify(this.canvas.toJSON([
            'id', 'name', 'locked', 'layerName', 'hasControls', 'selectable',
            'rx', 'ry', 'strokeDashArray',
            'isTable', 'tableRows', 'tableCols', 'headerBg', 'bodyBg', 'altBodyBg',
            'borderColor', 'borderWidth', 'headerTextColor', 'bodyTextColor',
            'isTableCellBg', 'isTableCellText', 'isHeaderCell', 'cellRow', 'cellCol',
            'charSpacing', 'lineHeight'
        ]));

        // Generate thumbnail
        activePage.thumbnail = this.canvas.toDataURL({
            format: 'jpeg',
            quality: 0.6,
            multiplier: 0.25,
        });
    }

    triggerAutosave() {
        const statusEl = document.getElementById('save-status-text');
        if (statusEl) statusEl.textContent = 'Unsaved changes...';

        clearTimeout(this.autosaveTimer);
        this.autosaveTimer = setTimeout(() => {
            this.saveDocument(false);
        }, 2500);
    }

    async saveDocument(isManual = true) {
        if (this.isSaving) return;
        this.isSaving = true;

        const statusEl = document.getElementById('save-status-text');
        if (statusEl) statusEl.textContent = 'Saving...';

        await this.saveCurrentPageState();

        // Main document preview thumbnail from page 1
        const mainThumbnail = this.pageManager.pages[0]?.thumbnail || null;

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.csrfToken;
            const res = await fetch(`/documents/${this.documentId}/save`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({
                    title: this.documentData.title,
                    document_thumbnail: mainThumbnail,
                    create_version: isManual,
                    pages: this.pageManager.pages.map(p => ({
                        id: p.id,
                        canvas_data: p.canvas_data,
                        thumbnail: p.thumbnail,
                        width: p.width,
                        height: p.height,
                        background_color: p.background_color,
                    }))
                })
            });

            const data = await res.json();
            if (data.success) {
                if (statusEl) statusEl.textContent = 'Saved at ' + new Date().toLocaleTimeString();
                if (isManual) window.showToast('Document saved successfully!', 'success');
            } else {
                if (statusEl) statusEl.textContent = 'Save failed';
            }
        } catch (e) {
            if (statusEl) statusEl.textContent = 'Save error';
        } finally {
            this.isSaving = false;
        }
    }

    initCanvasEvents() {
        this.canvas.on('object:added', () => this.triggerAutosave());
        this.canvas.on('object:modified', () => this.triggerAutosave());
        this.canvas.on('object:removed', () => this.triggerAutosave());

        // Contextual floating action toolbar for active element
        this.canvas.on('selection:created', (e) => this.updateFloatingToolbar(e.selected[0]));
        this.canvas.on('selection:updated', (e) => this.updateFloatingToolbar(e.selected[0]));
        this.canvas.on('selection:cleared', () => this.updateFloatingToolbar(null));

        // Quick double-click cell text editing for Tables
        this.canvas.on('mouse:dblclick', async (e) => {
            const target = e.target;
            if (!target) return;
            if (target.isTable) {
                const sub = (e.subTargets && e.subTargets.length > 0) ? e.subTargets[0] : null;
                const cellText = (sub && sub.isTableCellText) ? sub : target.getObjects().find(o => o.isTableCellText);
                if (cellText) {
                    const newText = prompt(`Edit Cell Text (Row ${cellText.cellRow + 1}, Col ${cellText.cellCol + 1}):`, cellText.text || '');
                    if (newText !== null) {
                        cellText.set('text', newText);
                        this.canvas.requestRenderAll();
                        this.history.recordState();
                        this.triggerAutosave();
                        await this.saveDocument(false);
                    }
                }
            }
        });

        // Ensure unsaved changes are saved if user refreshes page (F5)
        window.addEventListener('beforeunload', () => {
            if (this.autosaveTimer) {
                this.saveCurrentPageState();
                const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || this.csrfToken;
                const mainThumbnail = this.pageManager.pages[0]?.thumbnail || null;
                const payload = JSON.stringify({
                    title: this.documentData.title,
                    document_thumbnail: mainThumbnail,
                    create_version: false,
                    pages: this.pageManager.pages.map(p => ({
                        id: p.id,
                        canvas_data: p.canvas_data,
                        thumbnail: p.thumbnail,
                        width: p.width,
                        height: p.height,
                        background_color: p.background_color,
                    }))
                });
                const blob = new Blob([payload], { type: 'application/json' });
                navigator.sendBeacon(`/documents/${this.documentId}/save`, blob);
            }
        });
    }

    updateFloatingToolbar(obj) {
        const toolbar = document.getElementById('canvas-floating-toolbar');
        if (!toolbar) return;
        if (!obj) {
            toolbar.classList.add('hidden');
            return;
        }
        toolbar.classList.remove('hidden');
    }

    // ZOOM CONTROLS
    setZoom(scale) {
        this.zoomLevel = Math.max(0.2, Math.min(3.0, scale));
        const viewport = document.getElementById('canvas-viewport');
        const wrapper = document.getElementById('canvas-wrapper');
        const zoomText = document.getElementById('zoom-percentage-text');

        if (wrapper) {
            wrapper.style.transform = `scale(${this.zoomLevel})`;
            wrapper.style.transformOrigin = 'center center';
        }
        if (zoomText) {
            zoomText.textContent = `${Math.round(this.zoomLevel * 100)}%`;
        }
    }

    zoomIn() {
        this.setZoom(this.zoomLevel + 0.15);
    }

    zoomOut() {
        this.setZoom(this.zoomLevel - 0.15);
    }

    zoomReset() {
        this.setZoom(1.0);
    }

    zoomToFit() {
        const container = document.getElementById('canvas-scroll-container');
        const activePage = this.pageManager.getActivePage();
        if (!container || !activePage) return;

        const availableW = container.clientWidth - 100;
        const availableH = container.clientHeight - 100;
        const scale = Math.min(availableW / activePage.width, availableH / activePage.height);
        this.setZoom(scale);
    }

    initKeyboardShortcuts() {
        window.addEventListener('keydown', (e) => {
            // Ignore when typing inside input or textarea
            const tag = e.target.tagName.toLowerCase();
            if (tag === 'input' || tag === 'textarea' || e.target.isContentEditable) return;

            const isCtrl = e.ctrlKey || e.metaKey;

            if (isCtrl && e.key.toLowerCase() === 's') {
                e.preventDefault();
                this.saveDocument(true);
            } else if (isCtrl && e.key.toLowerCase() === 'z') {
                e.preventDefault();
                if (e.shiftKey) this.history.redo();
                else this.history.undo();
            } else if (isCtrl && e.key.toLowerCase() === 'y') {
                e.preventDefault();
                this.history.redo();
            } else if (isCtrl && e.key.toLowerCase() === 'c') {
                const active = this.canvas.getActiveObject();
                if (active) {
                    active.clone((cloned) => { this.copiedObject = cloned; });
                }
            } else if (isCtrl && e.key.toLowerCase() === 'v') {
                if (this.copiedObject) {
                    this.copiedObject.clone((cloned) => {
                        this.canvas.discardActiveObject();
                        cloned.set({
                            left: cloned.left + 24,
                            top: cloned.top + 24,
                            evented: true,
                        });
                        this.canvas.add(cloned);
                        this.canvas.setActiveObject(cloned);
                        this.canvas.requestRenderAll();
                        this.history.recordState();
                    });
                }
            } else if (isCtrl && e.key.toLowerCase() === 'd') {
                e.preventDefault();
                this.elements.duplicateSelected();
            } else if (e.key === 'Delete' || e.key === 'Backspace') {
                this.elements.deleteSelected();
            } else if (isCtrl && e.key.toLowerCase() === 'a') {
                e.preventDefault();
                this.canvas.discardActiveObject();
                const sel = new fabric.ActiveSelection(this.canvas.getObjects(), { canvas: this.canvas });
                this.canvas.setActiveObject(sel);
                this.canvas.requestRenderAll();
            } else if (e.key === 'Escape') {
                this.canvas.discardActiveObject();
                this.canvas.requestRenderAll();
            } else if (e.key.startsWith('Arrow')) {
                const active = this.canvas.getActiveObject();
                if (!active) return;
                e.preventDefault();
                const step = e.shiftKey ? 10 : 1;
                if (e.key === 'ArrowLeft') active.set('left', active.left - step);
                if (e.key === 'ArrowRight') active.set('left', active.left + step);
                if (e.key === 'ArrowUp') active.set('top', active.top - step);
                if (e.key === 'ArrowDown') active.set('top', active.top + step);
                active.setCoords();
                this.canvas.requestRenderAll();
                this.history.recordState();
            }
        });
    }

    initToolbarControls() {
        // Document Title Renaming
        const titleInput = document.getElementById('input-doc-title');
        if (titleInput) {
            titleInput.addEventListener('change', () => {
                this.documentData.title = titleInput.value.trim() || 'Untitled Document';
                this.triggerAutosave();
            });
        }

        // Undo & Redo buttons
        const btnUndo = document.getElementById('btn-undo');
        const btnRedo = document.getElementById('btn-redo');
        if (btnUndo) btnUndo.onclick = () => this.history.undo();
        if (btnRedo) btnRedo.onclick = () => this.history.redo();

        // Save button
        const btnSave = document.getElementById('btn-save');
        if (btnSave) btnSave.onclick = () => this.saveDocument(true);

        // Zoom buttons
        const btnZoomIn = document.getElementById('btn-zoom-in');
        const btnZoomOut = document.getElementById('btn-zoom-out');
        const btnZoomFit = document.getElementById('btn-zoom-fit');
        if (btnZoomIn) btnZoomIn.onclick = () => this.zoomIn();
        if (btnZoomOut) btnZoomOut.onclick = () => this.zoomOut();
        if (btnZoomFit) btnZoomFit.onclick = () => this.zoomToFit();

        // Full-screen mode toggle
        const btnFullscreen = document.getElementById('btn-fullscreen');
        if (btnFullscreen) {
            btnFullscreen.onclick = () => {
                if (!document.fullscreenElement) {
                    document.documentElement.requestFullscreen().catch(() => {});
                } else {
                    document.exitFullscreen().catch(() => {});
                }
            };
        }

        // Export PDF
        const btnExport = document.getElementById('btn-export-pdf');
        if (btnExport) {
            btnExport.onclick = () => this.exportManager.exportPdf({ quality: 'high', scope: 'all' });
        }
    }

    initSidebarTabs() {
        const tabs = ['pages', 'templates', 'text', 'shapes', 'images', 'signatures', 'backgrounds', 'layers', 'tools'];
        tabs.forEach(tabKey => {
            const btn = document.getElementById(`tab-btn-${tabKey}`);
            const panel = document.getElementById(`tab-panel-${tabKey}`);
            if (btn && panel) {
                btn.onclick = () => {
                    // Reset all
                    tabs.forEach(t => {
                        const b = document.getElementById(`tab-btn-${t}`);
                        const p = document.getElementById(`tab-panel-${t}`);
                        if (b) {
                            b.classList.remove('bg-indigo-600', 'text-white');
                            b.classList.add('text-slate-400', 'hover:bg-slate-100');
                        }
                        if (p) p.classList.add('hidden');
                    });
                    // Activate selected
                    btn.classList.add('bg-indigo-600', 'text-white');
                    btn.classList.remove('text-slate-400', 'hover:bg-slate-100');
                    panel.classList.remove('hidden');

                    if (tabKey === 'layers') this.layers.refresh();
                };
            }
        });
    }

    initSignaturePad() {
        const padCanvas = document.getElementById('signature-draw-canvas');
        if (!padCanvas) return;
        const ctx = padCanvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0, lastY = 0;

        ctx.strokeStyle = '#000000';
        ctx.lineWidth = 2.5;
        ctx.lineCap = 'round';
        ctx.lineJoin = 'round';

        padCanvas.addEventListener('mousedown', (e) => {
            isDrawing = true;
            const rect = padCanvas.getBoundingClientRect();
            lastX = e.clientX - rect.left;
            lastY = e.clientY - rect.top;
        });

        padCanvas.addEventListener('mousemove', (e) => {
            if (!isDrawing) return;
            const rect = padCanvas.getBoundingClientRect();
            const x = e.clientX - rect.left;
            const y = e.clientY - rect.top;

            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(x, y);
            ctx.stroke();
            lastX = x;
            lastY = y;
        });

        const stopDrawing = () => { isDrawing = false; };
        padCanvas.addEventListener('mouseup', stopDrawing);
        padCanvas.addEventListener('mouseleave', stopDrawing);

        // Clear pad
        const clearBtn = document.getElementById('btn-clear-sig');
        if (clearBtn) {
            clearBtn.onclick = () => {
                ctx.clearRect(0, 0, padCanvas.width, padCanvas.height);
            };
        }

        // Insert drawn signature
        const insertBtn = document.getElementById('btn-insert-sig');
        if (insertBtn) {
            insertBtn.onclick = () => {
                const dataUrl = padCanvas.toDataURL('image/png');
                this.elements.addSignature(dataUrl, 'Handwritten Signature');
                window.showToast('Signature added to page', 'success');
            };
        }
    }

    initAssetUploads() {
        const imgInput = document.getElementById('input-image-upload');
        if (imgInput) {
            imgInput.addEventListener('change', async () => {
                if (!imgInput.files || !imgInput.files[0]) return;
                const file = imgInput.files[0];
                const formData = new FormData();
                formData.append('image', file);
                formData.append('document_id', this.documentId);

                window.showToast('Uploading image...', 'info');

                try {
                    const res = await fetch('/assets/upload-image', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: formData,
                    });
                    const data = await res.json();
                    if (data.success && data.url) {
                        this.elements.addImageFromUrl(data.url, file.name);
                        window.showToast('Image inserted!', 'success');
                    } else {
                        window.showToast('Failed to upload image', 'error');
                    }
                } catch (e) {
                    window.showToast('Upload error', 'error');
                }
            });
        }
    }
}

window.PdfEditorApp = PdfEditorApp;

export function bootEditor() {
    const configEl = document.getElementById('editor-config');
    if (!configEl) {
        console.warn('No #editor-config found on page.');
        return;
    }
    if (window.editorApp) return;

    try {
        const config = JSON.parse(configEl.textContent);
        window.editorApp = new PdfEditorApp(config);
        console.log('PDF Editor successfully initialized:', window.editorApp);
    } catch (e) {
        console.error('Error booting PDF Editor:', e);
    }

    if (window.lucide) {
        window.lucide.createIcons();
    }
}

// Auto boot
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootEditor);
} else {
    bootEditor();
}

window.bootEditor = bootEditor;

