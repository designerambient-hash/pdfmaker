/**
 * Properties Inspector Manager: Binds selected canvas element with UI controls
 */
export class PropertiesManager {
    constructor(canvas, history) {
        this.canvas = canvas;
        this.history = history;
        this.activeObject = null;
        this.initEventListeners();
    }

    initEventListeners() {
        this.canvas.on('selection:created', (e) => this.onSelection(e.selected[0]));
        this.canvas.on('selection:updated', (e) => this.onSelection(e.selected[0]));
        this.canvas.on('selection:cleared', () => this.onSelectionCleared());
        this.canvas.on('object:modified', () => {
            this.syncFromObject();
            this.history.recordState();
        });
    }

    onSelection(obj) {
        this.activeObject = obj;
        const pagePanel = document.getElementById('panel-props-page');
        const elementPanel = document.getElementById('panel-props-element');
        const textSection = document.getElementById('props-section-text');
        const shapeSection = document.getElementById('props-section-shape');
        const imageSection = document.getElementById('props-section-image');
        const tableSection = document.getElementById('props-section-table');

        if (pagePanel) pagePanel.classList.add('hidden');
        if (elementPanel) elementPanel.classList.remove('hidden');

        // Toggle context specific sections
        const isTable = !!obj.isTable;
        const isText = !isTable && (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox');
        const isImage = !isTable && obj.type === 'image';
        const isShape = !isTable && !isText && !isImage;

        if (tableSection) tableSection.classList.toggle('hidden', !isTable);
        if (textSection) textSection.classList.toggle('hidden', !isText);
        if (imageSection) imageSection.classList.toggle('hidden', !isImage);
        if (shapeSection) shapeSection.classList.toggle('hidden', !isShape);

        this.syncFromObject();
    }

    onSelectionCleared() {
        this.activeObject = null;
        const pagePanel = document.getElementById('panel-props-page');
        const elementPanel = document.getElementById('panel-props-element');

        if (elementPanel) elementPanel.classList.add('hidden');
        if (pagePanel) pagePanel.classList.remove('hidden');
    }

    syncFromObject() {
        const obj = this.activeObject;
        if (!obj) return;

        // Common dimensions & position
        const posX = document.getElementById('prop-pos-x');
        const posY = document.getElementById('prop-pos-y');
        const width = document.getElementById('prop-width');
        const height = document.getElementById('prop-height');
        const rotation = document.getElementById('prop-rotation');
        const opacity = document.getElementById('prop-opacity');

        if (posX) posX.value = Math.round(obj.left);
        if (posY) posY.value = Math.round(obj.top);
        if (width) width.value = Math.round(obj.getScaledWidth());
        if (height) height.value = Math.round(obj.getScaledHeight());
        if (rotation) rotation.value = Math.round(obj.angle % 360);
        if (opacity) opacity.value = Math.round((obj.opacity ?? 1) * 100);

        // Text specific
        if (obj.type === 'i-text' || obj.type === 'text' || obj.type === 'textbox') {
            const fontFamily = document.getElementById('prop-font-family');
            const fontSize = document.getElementById('prop-font-size');
            const textColor = document.getElementById('prop-text-color');
            const textBgColor = document.getElementById('prop-text-bg');
            const lineHeight = document.getElementById('prop-line-height');
            const lineHeightRange = document.getElementById('prop-line-height-range');
            const lineHeightLabel = document.getElementById('label-line-height-val');
            const charSpacing = document.getElementById('prop-char-spacing');
            const charSpacingRange = document.getElementById('prop-char-spacing-range');
            const charSpacingLabel = document.getElementById('label-char-spacing-val');

            if (fontFamily) fontFamily.value = obj.fontFamily || 'Inter';
            if (fontSize) fontSize.value = obj.fontSize || 16;
            if (textColor) textColor.value = obj.fill || '#000000';
            if (textBgColor) textBgColor.value = obj.textBackgroundColor || '#ffffff';
            
            const lh = obj.lineHeight || 1.2;
            if (lineHeight) lineHeight.value = lh;
            if (lineHeightRange) lineHeightRange.value = lh;
            if (lineHeightLabel) lineHeightLabel.textContent = lh;

            const cs = obj.charSpacing || 0;
            if (charSpacing) charSpacing.value = cs;
            if (charSpacingRange) charSpacingRange.value = cs;
            if (charSpacingLabel) charSpacingLabel.textContent = cs;

            // Style buttons state
            this.toggleBtnState('btn-bold', obj.fontWeight === 'bold' || obj.fontWeight >= 700);
            this.toggleBtnState('btn-italic', obj.fontStyle === 'italic');
            this.toggleBtnState('btn-underline', !!obj.underline);
            this.toggleBtnState('btn-strike', !!obj.linethrough);
        }

        // Table specific
        if (obj.isTable) {
            const headerBg = document.getElementById('prop-table-header-bg');
            const bodyBg = document.getElementById('prop-table-body-bg');
            const borderColor = document.getElementById('prop-table-border-color');
            const borderWidth = document.getElementById('prop-table-border-width');
            const headerText = document.getElementById('prop-table-header-text');
            const bodyText = document.getElementById('prop-table-body-text');
            const badge = document.getElementById('table-info-badge');

            if (headerBg) headerBg.value = obj.headerBg || '#4f46e5';
            if (bodyBg) bodyBg.value = obj.bodyBg || '#ffffff';
            if (borderColor) borderColor.value = obj.borderColor || '#cbd5e1';
            if (borderWidth) borderWidth.value = obj.borderWidth ?? 1;
            if (headerText) headerText.value = obj.headerTextColor || '#ffffff';
            if (bodyText) bodyText.value = obj.bodyTextColor || '#1e293b';
            if (badge) badge.textContent = `${obj.tableRows}×${obj.tableCols} Table`;
        }

        // Shape specific
        if (!obj.isTable && obj.type !== 'image' && obj.type !== 'i-text') {
            const shapeFill = document.getElementById('prop-shape-fill');
            const shapeStroke = document.getElementById('prop-shape-stroke');
            const shapeStrokeWidth = document.getElementById('prop-shape-stroke-width');
            const shapeRadius = document.getElementById('prop-shape-radius');

            if (shapeFill && typeof obj.fill === 'string') shapeFill.value = obj.fill || '#000000';
            if (shapeStroke) shapeStroke.value = obj.stroke || '#000000';
            if (shapeStrokeWidth) shapeStrokeWidth.value = obj.strokeWidth || 0;
            if (shapeRadius && obj.rx !== undefined) shapeRadius.value = obj.rx || 0;
        }
    }

    toggleBtnState(btnId, isActive) {
        const btn = document.getElementById(btnId);
        if (btn) {
            btn.classList.toggle('bg-indigo-100', isActive);
            btn.classList.toggle('text-indigo-700', isActive);
        }
    }

    // Direct property mutators from UI input events
    setProp(prop, value) {
        if (!this.activeObject) return;

        if (prop === 'width') {
            this.activeObject.scaleToWidth(parseFloat(value) || 10);
        } else if (prop === 'height') {
            this.activeObject.scaleToHeight(parseFloat(value) || 10);
        } else if (prop === 'opacity') {
            this.activeObject.set('opacity', (parseFloat(value) || 0) / 100);
        } else if (prop === 'angle') {
            this.activeObject.set('angle', parseFloat(value) || 0);
        } else {
            this.activeObject.set(prop, value);
        }

        if (this.activeObject.initDimensions) {
            this.activeObject.initDimensions();
        }

        this.activeObject.setCoords();
        this.canvas.requestRenderAll();
        if (window.editorApp) {
            window.editorApp.triggerAutosave();
        }
    }

    toggleTextStyle(style) {
        if (!this.activeObject) return;
        const obj = this.activeObject;

        if (style === 'bold') {
            const isBold = obj.fontWeight === 'bold' || obj.fontWeight >= 700;
            obj.set('fontWeight', isBold ? 'normal' : 'bold');
        } else if (style === 'italic') {
            obj.set('fontStyle', obj.fontStyle === 'italic' ? 'normal' : 'italic');
        } else if (style === 'underline') {
            obj.set('underline', !obj.underline);
        } else if (style === 'strike') {
            obj.set('linethrough', !obj.linethrough);
        }

        if (obj.initDimensions) {
            obj.initDimensions();
        }

        obj.setCoords();
        this.canvas.requestRenderAll();
        this.syncFromObject();
        this.history.recordState();
        if (window.editorApp) {
            window.editorApp.triggerAutosave();
        }
    }

    setTextAlign(align) {
        if (!this.activeObject) return;
        this.activeObject.set('textAlign', align);
        if (this.activeObject.initDimensions) {
            this.activeObject.initDimensions();
        }
        this.activeObject.setCoords();
        this.canvas.requestRenderAll();
        this.history.recordState();
        if (window.editorApp) {
            window.editorApp.triggerAutosave();
        }
    }

    setTextTransform(type) {
        if (!this.activeObject || !this.activeObject.text) return;
        const current = this.activeObject.text;
        if (type === 'uppercase') {
            this.activeObject.set('text', current.toUpperCase());
        } else if (type === 'lowercase') {
            this.activeObject.set('text', current.toLowerCase());
        } else if (type === 'capitalize') {
            this.activeObject.set('text', current.replace(/\b\w/g, l => l.toUpperCase()));
        }
        this.canvas.requestRenderAll();
        this.history.recordState();
    }

    flipImage(direction) {
        if (!this.activeObject || this.activeObject.type !== 'image') return;
        if (direction === 'x') {
            this.activeObject.set('flipX', !this.activeObject.flipX);
        } else {
            this.activeObject.set('flipY', !this.activeObject.flipY);
        }
        this.canvas.requestRenderAll();
        this.history.recordState();
    }

    // --- TABLE PROPERTY MUTATORS & CELL TOOLS ---
    setTableProp(prop, value) {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        table[prop] = value;

        const subObjects = table.getObjects ? table.getObjects() : (table._objects || []);

        if (prop === 'headerBg') {
            subObjects.filter(o => o.isTableCellBg && o.isHeaderCell).forEach(o => o.set('fill', value));
        } else if (prop === 'bodyBg') {
            subObjects.filter(o => o.isTableCellBg && !o.isHeaderCell).forEach(o => o.set('fill', value));
        } else if (prop === 'borderColor') {
            subObjects.filter(o => o.isTableCellBg).forEach(o => o.set('stroke', value));
        } else if (prop === 'borderWidth') {
            const bw = parseFloat(value) || 0;
            subObjects.filter(o => o.isTableCellBg).forEach(o => o.set('strokeWidth', bw));
        } else if (prop === 'headerTextColor') {
            subObjects.filter(o => o.isTableCellText && o.isHeaderCell).forEach(o => o.set('fill', value));
        } else if (prop === 'bodyTextColor') {
            subObjects.filter(o => o.isTableCellText && !o.isHeaderCell).forEach(o => o.set('fill', value));
        }

        this.canvas.requestRenderAll();
        this.history.recordState();
        if (window.editorApp) {
            window.editorApp.triggerAutosave();
        }
    }

    promptEditSelectedCell() {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        const subObjects = table.getObjects ? table.getObjects() : (table._objects || []);
        const textCells = subObjects.filter(o => o.isTableCellText);

        if (textCells.length === 0) return;

        const rowStr = prompt(`Enter Row number (1 to ${table.tableRows}):`, "1");
        if (!rowStr) return;
        const r = parseInt(rowStr) - 1;
        if (isNaN(r) || r < 0 || r >= table.tableRows) {
            alert('Invalid row number');
            return;
        }

        const colStr = prompt(`Enter Column number (1 to ${table.tableCols}):`, "1");
        if (!colStr) return;
        const c = parseInt(colStr) - 1;
        if (isNaN(c) || c < 0 || c >= table.tableCols) {
            alert('Invalid column number');
            return;
        }

        const targetCell = textCells.find(t => t.cellRow === r && t.cellCol === c);
        if (!targetCell) {
            alert('Cell not found');
            return;
        }

        const newText = prompt(`Edit Text for Row ${r + 1}, Col ${c + 1}:`, targetCell.text || '');
        if (newText !== null) {
            targetCell.set('text', newText);
            this.canvas.requestRenderAll();
            this.history.recordState();
            if (window.editorApp) {
                window.editorApp.triggerAutosave();
                window.editorApp.saveDocument(false);
            }
        }
    }

    addTableRow() {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        const newRows = (table.tableRows || 3) + 1;
        const cols = table.tableCols || 3;
        this.rebuildTable(newRows, cols);
    }

    removeTableRow() {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        if ((table.tableRows || 3) <= 2) {
            alert('A table must have at least 2 rows (header + 1 data row).');
            return;
        }
        const newRows = table.tableRows - 1;
        const cols = table.tableCols || 3;
        this.rebuildTable(newRows, cols);
    }

    addTableCol() {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        const rows = table.tableRows || 3;
        const newCols = (table.tableCols || 3) + 1;
        this.rebuildTable(rows, newCols);
    }

    removeTableCol() {
        if (!this.activeObject || !this.activeObject.isTable) return;
        const table = this.activeObject;
        if ((table.tableCols || 3) <= 1) {
            alert('A table must have at least 1 column.');
            return;
        }
        const rows = table.tableRows || 3;
        const newCols = table.tableCols - 1;
        this.rebuildTable(rows, newCols);
    }

    rebuildTable(newRows, newCols) {
        const table = this.activeObject;
        const left = table.left;
        const top = table.top;
        const angle = table.angle;
        const scaleX = table.scaleX;
        const scaleY = table.scaleY;

        const theme = {
            headerBg: table.headerBg,
            bodyBg: table.bodyBg,
            altBodyBg: table.altBodyBg,
            borderColor: table.borderColor,
            borderWidth: table.borderWidth,
            headerTextColor: table.headerTextColor,
            bodyTextColor: table.bodyTextColor,
        };

        const subObjects = table.getObjects ? table.getObjects() : (table._objects || []);
        const textMap = {};
        subObjects.filter(o => o.isTableCellText).forEach(o => {
            textMap[`${o.cellRow}_${o.cellCol}`] = o.text;
        });

        this.canvas.remove(table);

        const newTable = window.editorApp.elements.addTable({
            rows: newRows,
            cols: newCols,
            theme: 'indigo'
        });

        newTable.set({
            left: left,
            top: top,
            angle: angle,
            scaleX: scaleX,
            scaleY: scaleY,
            headerBg: theme.headerBg,
            bodyBg: theme.bodyBg,
            altBodyBg: theme.altBodyBg,
            borderColor: theme.borderColor,
            borderWidth: theme.borderWidth,
            headerTextColor: theme.headerTextColor,
            bodyTextColor: theme.bodyTextColor,
        });

        const newSubs = newTable.getObjects ? newTable.getObjects() : (newTable._objects || []);
        newSubs.forEach(o => {
            if (o.isTableCellBg) {
                o.set('stroke', theme.borderColor);
                o.set('strokeWidth', theme.borderWidth);
                o.set('fill', o.isHeaderCell ? theme.headerBg : (o.cellRow % 2 === 1 ? theme.bodyBg : theme.altBodyBg));
            } else if (o.isTableCellText) {
                o.set('fill', o.isHeaderCell ? theme.headerTextColor : theme.bodyTextColor);
                if (textMap[`${o.cellRow}_${o.cellCol}`] !== undefined) {
                    o.set('text', textMap[`${o.cellRow}_${o.cellCol}`]);
                }
            }
        });

        newTable.setCoords();
        this.canvas.setActiveObject(newTable);
        this.canvas.requestRenderAll();
        this.history.recordState();
        this.onSelection(newTable);
    }
}

