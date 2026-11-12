import { PDFDocument, rgb } from 'pdf-lib';
import * as fabric from 'fabric';


/**
 * PDF Export Engine: High-fidelity PDF generation using pdf-lib and canvas vectors
 */
export class ExportManager {
    constructor(editor) {
        this.editor = editor;
    }

    /**
     * Export the document as PDF
     * @param {Object} options - { quality: 'standard'|'high'|'print', scope: 'all'|'current' }
     */
    async exportPdf(options = { quality: 'high', scope: 'all' }) {
        window.showToast('Generating high-quality PDF...', 'info');

        try {
            // First ensure current canvas changes are committed to the active page state
            await this.editor.saveCurrentPageState();

            const pdfDoc = await PDFDocument.create();
            const pagesToExport = options.scope === 'current'
                ? [this.editor.pageManager.getActivePage()]
                : this.editor.pageManager.pages;

            // Multiplier for raster density: standard (1.5x), high (2.5x), print (4x)
            let multiplier = 2.0;
            if (options.quality === 'print') multiplier = 3.5;
            else if (options.quality === 'standard') multiplier = 1.2;

            for (let i = 0; i < pagesToExport.length; i++) {
                const pageModel = pagesToExport[i];

                // PDF point dimensions (1 pt = 1/72 inch; 1 px at 96 DPI = 0.75 pt)
                const ptWidth = pageModel.width * 0.75;
                const ptHeight = pageModel.height * 0.75;

                const pdfPage = pdfDoc.addPage([ptWidth, ptHeight]);

                // Render page background color
                if (pageModel.background_color && pageModel.background_color !== 'transparent') {
                    const bg = this.hexToRgb(pageModel.background_color);
                    if (bg) {
                        pdfPage.drawRectangle({
                            x: 0,
                            y: 0,
                            width: ptWidth,
                            height: ptHeight,
                            color: rgb(bg.r, bg.g, bg.b),
                        });
                    }
                }

                // If page is currently active, render directly from current fabric canvas
                // If not, spin up a temporary offscreen fabric canvas to render objects
                let dataUrl;
                if (pageModel.id === this.editor.pageManager.getActivePage().id) {
                    dataUrl = this.editor.canvas.toDataURL({
                        format: 'png',
                        multiplier: multiplier,
                        enableRetinaScaling: true,
                    });
                } else {
                    dataUrl = await this.renderOffscreenPage(pageModel, multiplier);
                }

                if (dataUrl) {
                    const pngImageBytes = await fetch(dataUrl).then(res => res.arrayBuffer());
                    const embeddedImage = await pdfDoc.embedPng(pngImageBytes);

                    pdfPage.drawImage(embeddedImage, {
                        x: 0,
                        y: 0,
                        width: ptWidth,
                        height: ptHeight,
                    });
                }
            }

            // Save PDF bytes
            const pdfBytes = await pdfDoc.save();
            const blob = new Blob([pdfBytes], { type: 'application/pdf' });
            const downloadUrl = URL.createObjectURL(blob);

            const docTitle = (this.editor.documentData.title || 'document').replace(/[^a-zA-Z0-9_-]/g, '_');
            const link = document.createElement('a');
            link.href = downloadUrl;
            link.download = `${docTitle}.pdf`;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            URL.revokeObjectURL(downloadUrl);

            window.showToast('PDF exported successfully!', 'success');
        } catch (e) {
            console.error('PDF Export Error:', e);
            window.showToast('PDF generation failed: ' + e.message, 'error');
        }
    }

    async renderOffscreenPage(pageModel, multiplier) {
        const tempCanvasEl = document.createElement('canvas');
        tempCanvasEl.width = pageModel.width;
        tempCanvasEl.height = pageModel.height;

        const tempFabric = new fabric.Canvas(tempCanvasEl, {
            width: pageModel.width,
            height: pageModel.height,
        });

        const canvasJson = pageModel.canvas_data ? JSON.parse(pageModel.canvas_data) : { objects: [] };
        try {
            await tempFabric.loadFromJSON(canvasJson);
        } catch (e) {
            console.warn('Offscreen load warning:', e);
        }
        tempFabric.renderAll();
        const url = tempFabric.toDataURL({
            format: 'png',
            multiplier: multiplier,
            enableRetinaScaling: true,
        });
        tempFabric.dispose();
        return url;
    }

    hexToRgb(hex) {
        let clean = hex.replace('#', '');
        if (clean.length === 3) {
            clean = clean.split('').map(c => c + c).join('');
        }
        if (clean.length !== 6) return null;
        const num = parseInt(clean, 16);
        return {
            r: ((num >> 16) & 255) / 255,
            g: ((num >> 8) & 255) / 255,
            b: (num & 255) / 255,
        };
    }
}
