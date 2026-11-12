import * as fabric from 'fabric';

/**
 * Elements Factory & Manipulation Helper
 */
export class ElementManager {
    constructor(canvas, history) {
        this.canvas = canvas;
        this.history = history;
        this.counter = 1;
    }

    generateId(prefix = 'elem') {
        return `${prefix}_${Date.now()}_${this.counter++}`;
    }

    getCanvasCenter() {
        if (this.canvas.getCenter) return this.canvas.getCenter();
        if (this.canvas.getCenterPoint) {
            const pt = this.canvas.getCenterPoint();
            return { left: pt.x ?? pt.left, top: pt.y ?? pt.top };
        }
        return { left: (this.canvas.width || 800) / 2, top: (this.canvas.height || 1100) / 2 };
    }

    // 1. TEXT ELEMENTS
    addText(type = 'paragraph', customText = null) {
        let text = 'Click to edit text';
        let fontSize = 16;
        let fontWeight = 'normal';

        if (type === 'heading') {
            text = 'Add a Heading';
            fontSize = 32;
            fontWeight = 'bold';
        } else if (type === 'subheading') {
            text = 'Add a Subheading';
            fontSize = 22;
            fontWeight = '600';
        } else if (customText) {
            text = customText;
        }

        const center = this.getCanvasCenter();
        const textElem = new fabric.IText(text, {
            id: this.generateId('text'),
            layerName: type.charAt(0).toUpperCase() + type.slice(1) + ' Text',
            left: center.left,
            top: center.top,
            originX: 'center',
            originY: 'center',
            fontFamily: 'Inter',
            fontSize: fontSize,
            fontWeight: fontWeight,
            fill: '#1e293b',
            lineHeight: 1.2,
            charSpacing: 0,
            padding: 8,
            transparentCorners: false,
            cornerColor: '#6366f1',
            cornerStrokeColor: '#ffffff',
            borderColor: '#6366f1',
            cornerSize: 8,
        });

        this.canvas.add(textElem);
        this.canvas.setActiveObject(textElem);
        this.canvas.renderAll();
        this.history.recordState();
        return textElem;
    }

    // 2. SHAPE ELEMENTS
    addShape(shapeType, options = {}) {
        const center = this.getCanvasCenter();
        let shape;
        const defaultStyle = {
            id: this.generateId(shapeType),
            layerName: shapeType.charAt(0).toUpperCase() + shapeType.slice(1),
            left: center.left,
            top: center.top,
            originX: 'center',
            originY: 'center',
            fill: options.fill || '#6366f1',
            stroke: options.stroke || '#4338ca',
            strokeWidth: options.strokeWidth ?? 0,
            transparentCorners: false,
            cornerColor: '#6366f1',
            borderColor: '#6366f1',
            cornerSize: 8,
            ...options
        };

        switch (shapeType) {
            case 'rect':
                shape = new fabric.Rect({
                    ...defaultStyle,
                    width: options.width || 160,
                    height: options.height || 100,
                    rx: options.rx || 0,
                    ry: options.ry || 0,
                });
                break;
            case 'rounded-rect':
                shape = new fabric.Rect({
                    ...defaultStyle,
                    width: 160,
                    height: 100,
                    rx: 16,
                    ry: 16,
                    layerName: 'Rounded Rectangle',
                });
                break;
            case 'circle':
                shape = new fabric.Circle({
                    ...defaultStyle,
                    radius: options.radius || 60,
                });
                break;
            case 'ellipse':
                shape = new fabric.Ellipse({
                    ...defaultStyle,
                    rx: 80,
                    ry: 50,
                });
                break;
            case 'triangle':
                shape = new fabric.Triangle({
                    ...defaultStyle,
                    width: 140,
                    height: 120,
                });
                break;
            case 'line':
                shape = new fabric.Line([center.left - 100, center.top, center.left + 100, center.top], {
                    ...defaultStyle,
                    stroke: '#1e293b',
                    strokeWidth: 3,
                    fill: '#1e293b',
                    layerName: 'Horizontal Line',
                });
                break;
            case 'star':
                const points = this.calculateStarPoints(5, 60, 30);
                shape = new fabric.Polygon(points, {
                    ...defaultStyle,
                    layerName: 'Star Shape',
                });
                break;
            default:
                shape = new fabric.Rect({ ...defaultStyle, width: 100, height: 100 });
        }

        this.canvas.add(shape);
        this.canvas.setActiveObject(shape);
        this.canvas.renderAll();
        this.history.recordState();
        return shape;
    }

    calculateStarPoints(arms, outerRadius, innerRadius) {
        const results = [];
        const angle = Math.PI / arms;
        for (let i = 0; i < 2 * arms; i++) {
            const r = (i & 1) === 0 ? outerRadius : innerRadius;
            const currAngle = i * angle - Math.PI / 2;
            results.push({
                x: Math.round(r * Math.cos(currAngle)),
                y: Math.round(r * Math.sin(currAngle))
            });
        }
        return results;
    }

    // 3. TABLE ELEMENTS
    addTable(options = {}) {
        const rows = Math.max(1, Math.min(20, parseInt(options.rows) || 3));
        const cols = Math.max(1, Math.min(10, parseInt(options.cols) || 3));
        const theme = options.theme || 'indigo';
        const customHeaders = options.headers || null;

        const themes = {
            indigo: {
                headerBg: '#4f46e5',
                bodyBg: '#ffffff',
                altBodyBg: '#f8fafc',
                borderColor: '#cbd5e1',
                borderWidth: 1,
                headerTextColor: '#ffffff',
                bodyTextColor: '#1e293b'
            },
            invoice: {
                headerBg: '#1e293b',
                bodyBg: '#ffffff',
                altBodyBg: '#f8fafc',
                borderColor: '#94a3b8',
                borderWidth: 1,
                headerTextColor: '#ffffff',
                bodyTextColor: '#0f172a'
            },
            emerald: {
                headerBg: '#059669',
                bodyBg: '#ffffff',
                altBodyBg: '#f0fdf4',
                borderColor: '#86efac',
                borderWidth: 1,
                headerTextColor: '#ffffff',
                bodyTextColor: '#064e3b'
            },
            minimal: {
                headerBg: '#f1f5f9',
                bodyBg: '#ffffff',
                altBodyBg: '#ffffff',
                borderColor: '#e2e8f0',
                borderWidth: 1,
                headerTextColor: '#334155',
                bodyTextColor: '#475569'
            }
        };

        const themeConfig = themes[theme] || themes.indigo;
        const center = this.getCanvasCenter();
        const tableId = this.generateId('table');

        // Sizing
        const canvasW = this.canvas.width || 800;
        const totalW = Math.min(canvasW * 0.85, Math.max(cols * 120, 360));
        const colW = Math.round(totalW / cols);
        const headerH = 40;
        const rowH = 36;

        const elements = [];
        let currentY = 0;

        // Generate cells: Rect backgrounds first, then IText labels with matched top/left origins
        for (let r = 0; r < rows; r++) {
            const isHeader = (r === 0);
            const currentH = isHeader ? headerH : rowH;
            const fontSize = isHeader ? 13 : 12;
            const textTop = currentY + Math.max(2, Math.round((currentH - (fontSize * 1.35)) / 2));

            for (let c = 0; c < cols; c++) {
                const currentX = c * colW;
                const cellFill = isHeader ? themeConfig.headerBg : (r % 2 === 1 ? themeConfig.bodyBg : themeConfig.altBodyBg);

                const cellRect = new fabric.Rect({
                    id: `${tableId}_bg_${r}_${c}`,
                    left: currentX,
                    top: currentY,
                    width: colW,
                    height: currentH,
                    originX: 'left',
                    originY: 'top',
                    fill: cellFill,
                    stroke: themeConfig.borderColor,
                    strokeWidth: themeConfig.borderWidth,
                    strokeUniform: true,
                    selectable: false,
                    evented: false,
                    isTableCellBg: true,
                    isHeaderCell: isHeader,
                    cellRow: r,
                    cellCol: c,
                });
                elements.push(cellRect);

                let textStr = '';
                if (isHeader) {
                    textStr = (customHeaders && customHeaders[c]) ? customHeaders[c] : `Header ${c + 1}`;
                } else {
                    textStr = `Data ${r},${c + 1}`;
                }

                const cellText = new fabric.IText(textStr, {
                    id: `${tableId}_txt_${r}_${c}`,
                    left: currentX + 12,
                    top: textTop,
                    originX: 'left',
                    originY: 'top',
                    fontFamily: 'Inter',
                    fontSize: fontSize,
                    fontWeight: isHeader ? '600' : 'normal',
                    fill: isHeader ? themeConfig.headerTextColor : themeConfig.bodyTextColor,
                    charSpacing: 0,
                    selectable: false,
                    evented: false,
                    isTableCellText: true,
                    isHeaderCell: isHeader,
                    cellRow: r,
                    cellCol: c,
                });
                elements.push(cellText);
            }

            currentY += currentH;
        }

        const tableGroup = new fabric.Group(elements, {
            id: tableId,
            layerName: `Table (${rows}×${cols})`,
            isTable: true,
            tableRows: rows,
            tableCols: cols,
            headerBg: themeConfig.headerBg,
            bodyBg: themeConfig.bodyBg,
            altBodyBg: themeConfig.altBodyBg,
            borderColor: themeConfig.borderColor,
            borderWidth: themeConfig.borderWidth,
            headerTextColor: themeConfig.headerTextColor,
            bodyTextColor: themeConfig.bodyTextColor,
            left: center.left,
            top: center.top,
            originX: 'center',
            originY: 'center',
            subTargetCheck: true,
            transparentCorners: false,
            cornerColor: '#6366f1',
            borderColor: '#6366f1',
            cornerSize: 8,
        });

        this.canvas.add(tableGroup);
        this.canvas.setActiveObject(tableGroup);
        this.canvas.renderAll();
        this.history.recordState();
        return tableGroup;
    }

    // 4. IMAGE ELEMENTS
    async addImageFromUrl(url, name = 'Image') {
        const center = this.getCanvasCenter();
        const ImageClass = fabric.FabricImage || fabric.Image;

        try {
            const img = await ImageClass.fromURL(url, { crossOrigin: 'anonymous' });
            if (!img) return;

            // Constrain maximum initial size so it doesn't overflow page
            const maxW = this.canvas.width * 0.7;
            const maxH = this.canvas.height * 0.7;
            if (img.width > maxW || img.height > maxH) {
                const scale = Math.min(maxW / img.width, maxH / img.height);
                img.scale(scale);
            }

            img.set({
                id: this.generateId('image'),
                layerName: name,
                left: center.left,
                top: center.top,
                originX: 'center',
                originY: 'center',
                cornerColor: '#6366f1',
                borderColor: '#6366f1',
                cornerSize: 8,
                transparentCorners: false,
            });

            this.canvas.add(img);
            this.canvas.setActiveObject(img);
            this.canvas.renderAll();
            this.history.recordState();
        } catch (e) {
            console.error('Failed to load image:', e);
        }
    }

    // 4. SIGNATURE ELEMENTS
    addSignature(dataUrl, name = 'Signature') {
        this.addImageFromUrl(dataUrl, name);
    }

    // 5. DRAWING MODE
    toggleDrawingMode(enable = true, color = '#1e293b', width = 3) {
        this.canvas.isDrawingMode = enable;
        if (enable) {
            this.canvas.freeDrawingBrush.color = color;
            this.canvas.freeDrawingBrush.width = width;
        }
    }

    // 6. ELEMENT TRANSFORMATIONS & ACTIONS
    duplicateSelected() {
        const active = this.canvas.getActiveObject();
        if (!active) return;

        active.clone((cloned) => {
            this.canvas.discardActiveObject();
            cloned.set({
                id: this.generateId('clone'),
                left: cloned.left + 20,
                top: cloned.top + 20,
                evented: true,
            });
            if (cloned.type === 'activeSelection') {
                cloned.canvas = this.canvas;
                cloned.forEachObject((obj) => {
                    this.canvas.add(obj);
                });
                cloned.setCoords();
            } else {
                this.canvas.add(cloned);
            }
            this.canvas.setActiveObject(cloned);
            this.canvas.requestRenderAll();
            this.history.recordState();
        });
    }

    deleteSelected() {
        const active = this.canvas.getActiveObjects();
        if (!active || active.length === 0) return;

        active.forEach((obj) => {
            this.canvas.remove(obj);
        });
        this.canvas.discardActiveObject();
        this.canvas.requestRenderAll();
        this.history.recordState();
    }

    bringForward() {
        const active = this.canvas.getActiveObject();
        if (active) {
            this.canvas.bringForward(active);
            this.canvas.renderAll();
            this.history.recordState();
        }
    }

    sendBackward() {
        const active = this.canvas.getActiveObject();
        if (active) {
            this.canvas.sendBackwards(active);
            this.canvas.renderAll();
            this.history.recordState();
        }
    }

    bringToFront() {
        const active = this.canvas.getActiveObject();
        if (active) {
            this.canvas.bringToFront(active);
            this.canvas.renderAll();
            this.history.recordState();
        }
    }

    sendToBack() {
        const active = this.canvas.getActiveObject();
        if (active) {
            this.canvas.sendToBack(active);
            this.canvas.renderAll();
            this.history.recordState();
        }
    }

    toggleLockSelected() {
        const active = this.canvas.getActiveObject();
        if (!active) return;

        const isLocked = !active.lockMovementX;
        active.set({
            lockMovementX: isLocked,
            lockMovementY: isLocked,
            lockRotation: isLocked,
            lockScalingX: isLocked,
            lockScalingY: isLocked,
            hasControls: !isLocked,
            locked: isLocked,
        });
        this.canvas.renderAll();
        return isLocked;
    }

    // ALIGNMENT TOOLS
    align(type) {
        const active = this.canvas.getActiveObject();
        if (!active) return;

        const cWidth = this.canvas.width;
        const cHeight = this.canvas.height;
        const objWidth = active.getScaledWidth();
        const objHeight = active.getScaledHeight();

        switch (type) {
            case 'left':
                active.set({ left: active.originX === 'center' ? objWidth / 2 : 0 });
                break;
            case 'center-h':
                active.set({ left: active.originX === 'center' ? cWidth / 2 : (cWidth - objWidth) / 2 });
                break;
            case 'right':
                active.set({ left: active.originX === 'center' ? cWidth - objWidth / 2 : cWidth - objWidth });
                break;
            case 'top':
                active.set({ top: active.originY === 'center' ? objHeight / 2 : 0 });
                break;
            case 'center-v':
                active.set({ top: active.originY === 'center' ? cHeight / 2 : (cHeight - objHeight) / 2 });
                break;
            case 'bottom':
                active.set({ top: active.originY === 'center' ? cHeight - objHeight / 2 : cHeight - objHeight });
                break;
        }

        active.setCoords();
        this.canvas.renderAll();
        this.history.recordState();
    }
}
