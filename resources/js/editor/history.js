const HISTORY_PROPERTIES = [
    'id', 'name', 'locked', 'layerName', 'hasControls', 'selectable',
    'rx', 'ry', 'strokeDashArray',
    'isTable', 'tableRows', 'tableCols', 'headerBg', 'bodyBg', 'altBodyBg',
    'borderColor', 'borderWidth', 'headerTextColor', 'bodyTextColor',
    'isTableCellBg', 'isTableCellText', 'isHeaderCell', 'cellRow', 'cellCol',
    'charSpacing', 'lineHeight', 'subTargetCheck'
];

/**
 * History Manager for Undo & Redo functionality
 */
export class HistoryManager {
    constructor(canvas, onStateChange) {
        this.canvas = canvas;
        this.onStateChange = onStateChange;
        this.undoStack = [];
        this.redoStack = [];
        this.maxStackSize = 40;
        this.isProcessing = false;
    }

    recordState() {
        if (this.isProcessing) return;
        const json = JSON.stringify(this.canvas.toJSON(HISTORY_PROPERTIES));

        // Don't push duplicate states
        if (this.undoStack.length > 0 && this.undoStack[this.undoStack.length - 1] === json) {
            return;
        }

        this.undoStack.push(json);
        if (this.undoStack.length > this.maxStackSize) {
            this.undoStack.shift();
        }

        // Clear redo stack on new user modification
        this.redoStack = [];
        this.updateButtons();
        if (this.onStateChange) this.onStateChange();
    }

    canUndo() {
        return this.undoStack.length > 1;
    }

    canRedo() {
        return this.redoStack.length > 0;
    }

    async undo() {
        if (!this.canUndo()) return;
        this.isProcessing = true;

        const currentState = this.undoStack.pop();
        this.redoStack.push(currentState);

        const previousState = this.undoStack[this.undoStack.length - 1];
        try {
            await this.canvas.loadFromJSON(previousState);
            this.canvas.getObjects().forEach(o => {
                if (o.isTable) o.subTargetCheck = true;
            });
        } catch (e) {
            console.warn('Undo load error:', e);
        }
        this.canvas.renderAll();
        this.isProcessing = false;
        this.updateButtons();
        if (this.onStateChange) this.onStateChange();
    }

    async redo() {
        if (!this.canRedo()) return;
        this.isProcessing = true;

        const nextState = this.redoStack.pop();
        this.undoStack.push(nextState);

        try {
            await this.canvas.loadFromJSON(nextState);
            this.canvas.getObjects().forEach(o => {
                if (o.isTable) o.subTargetCheck = true;
            });
        } catch (e) {
            console.warn('Redo load error:', e);
        }
        this.canvas.renderAll();
        this.isProcessing = false;
        this.updateButtons();
        if (this.onStateChange) this.onStateChange();
    }

    reset(initialJson) {
        this.undoStack = [initialJson];
        this.redoStack = [];
        this.updateButtons();
    }

    updateButtons() {
        const undoBtn = document.getElementById('btn-undo');
        const redoBtn = document.getElementById('btn-redo');
        if (undoBtn) undoBtn.disabled = !this.canUndo();
        if (redoBtn) redoBtn.disabled = !this.canRedo();
    }
}
