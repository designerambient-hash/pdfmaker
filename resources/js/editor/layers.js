/**
 * Layer Management Manager: Dedicated layer tree with stacking, visibility, locking
 */
export class LayerManager {
    constructor(canvas, history) {
        this.canvas = canvas;
        this.history = history;
        this.container = document.getElementById('layers-list');
        this.initEventListeners();
    }

    initEventListeners() {
        this.canvas.on('object:added', () => this.refresh());
        this.canvas.on('object:removed', () => this.refresh());
        this.canvas.on('selection:created', () => this.highlightSelected());
        this.canvas.on('selection:updated', () => this.highlightSelected());
        this.canvas.on('selection:cleared', () => this.highlightSelected());
    }

    refresh() {
        if (!this.container) return;
        this.container.innerHTML = '';

        // Canvas objects from top to bottom (reversed array)
        const objects = [...this.canvas.getObjects()].reverse();

        if (objects.length === 0) {
            this.container.innerHTML = `
                <div class="text-center py-8 text-slate-400 text-xs">
                    <p>No elements on this page.</p>
                </div>
            `;
            return;
        }

        objects.forEach((obj, idx) => {
            const actualIndex = this.canvas.getObjects().indexOf(obj);
            const row = document.createElement('div');
            const isActive = this.canvas.getActiveObject() === obj;
            const isLocked = !!obj.lockMovementX;
            const isVisible = obj.visible !== false;

            let iconName = 'square';
            if (obj.type === 'i-text' || obj.type === 'text') iconName = 'type';
            else if (obj.type === 'circle') iconName = 'circle';
            else if (obj.type === 'image') iconName = 'image';
            else if (obj.type === 'line') iconName = 'minus';
            else if (obj.type === 'polygon') iconName = 'star';

            const name = obj.layerName || `${obj.type.charAt(0).toUpperCase() + obj.type.slice(1)} ${actualIndex + 1}`;

            row.className = `flex items-center justify-between p-2 rounded-xl text-xs transition-colors cursor-pointer border ${
                isActive ? 'bg-indigo-50 border-indigo-200 text-indigo-700 font-semibold' : 'hover:bg-slate-50 border-transparent text-slate-700'
            }`;

            row.innerHTML = `
                <div class="flex items-center space-x-2 truncate">
                    <i data-lucide="${iconName}" class="w-3.5 h-3.5 shrink-0 ${isActive ? 'text-indigo-600' : 'text-slate-400'}"></i>
                    <span class="truncate">${name}</span>
                </div>
                <div class="flex items-center space-x-1 shrink-0">
                    <button type="button" class="btn-layer-up p-1 hover:bg-slate-200 rounded text-slate-400 hover:text-slate-700" title="Move Up">
                        <i data-lucide="chevron-up" class="w-3 h-3"></i>
                    </button>
                    <button type="button" class="btn-layer-down p-1 hover:bg-slate-200 rounded text-slate-400 hover:text-slate-700" title="Move Down">
                        <i data-lucide="chevron-down" class="w-3 h-3"></i>
                    </button>
                    <button type="button" class="btn-layer-lock p-1 hover:bg-slate-200 rounded ${isLocked ? 'text-amber-500' : 'text-slate-400 hover:text-slate-700'}" title="${isLocked ? 'Unlock' : 'Lock'}">
                        <i data-lucide="${isLocked ? 'lock' : 'unlock'}" class="w-3 h-3"></i>
                    </button>
                    <button type="button" class="btn-layer-vis p-1 hover:bg-slate-200 rounded ${!isVisible ? 'text-rose-500' : 'text-slate-400 hover:text-slate-700'}" title="${isVisible ? 'Hide' : 'Show'}">
                        <i data-lucide="${isVisible ? 'eye' : 'eye-off'}" class="w-3 h-3"></i>
                    </button>
                    <button type="button" class="btn-layer-del p-1 hover:bg-rose-100 rounded text-slate-400 hover:text-rose-600" title="Delete">
                        <i data-lucide="trash" class="w-3 h-3"></i>
                    </button>
                </div>
            `;

            // Row click: Select object on canvas
            row.addEventListener('click', (e) => {
                if (e.target.closest('button')) return;
                this.canvas.setActiveObject(obj);
                this.canvas.renderAll();
            });

            // Action buttons
            row.querySelector('.btn-layer-up').onclick = (e) => {
                e.stopPropagation();
                this.canvas.bringForward(obj);
                this.canvas.renderAll();
                this.refresh();
                this.history.recordState();
            };
            row.querySelector('.btn-layer-down').onclick = (e) => {
                e.stopPropagation();
                this.canvas.sendBackwards(obj);
                this.canvas.renderAll();
                this.refresh();
                this.history.recordState();
            };
            row.querySelector('.btn-layer-lock').onclick = (e) => {
                e.stopPropagation();
                const lockState = !obj.lockMovementX;
                obj.set({
                    lockMovementX: lockState,
                    lockMovementY: lockState,
                    lockRotation: lockState,
                    lockScalingX: lockState,
                    lockScalingY: lockState,
                    hasControls: !lockState,
                });
                this.canvas.renderAll();
                this.refresh();
            };
            row.querySelector('.btn-layer-vis').onclick = (e) => {
                e.stopPropagation();
                obj.set('visible', !obj.visible);
                this.canvas.renderAll();
                this.refresh();
            };
            row.querySelector('.btn-layer-del').onclick = (e) => {
                e.stopPropagation();
                this.canvas.remove(obj);
                this.canvas.discardActiveObject();
                this.canvas.renderAll();
                this.refresh();
                this.history.recordState();
            };

            this.container.appendChild(row);
        });

        if (window.lucide) lucide.createIcons();
    }

    highlightSelected() {
        this.refresh();
    }
}

