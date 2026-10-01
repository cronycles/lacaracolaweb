import Quill from 'quill';
import type { BlockEmbed } from 'quill/blots/block';
import type Toolbar from 'quill/modules/toolbar';
import 'quill/dist/quill.snow.css';

type CtaValue = { label: string; url: string };

const WIDTH_PRESETS = ['25%', '50%', '75%', '100%'] as const;
const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/png', 'image/webp', 'image/gif', 'image/bmp'];
const MAX_IMAGE_BYTES = 10 * 1024 * 1024;

// Registers the CTA "button" and horizontal-rule embeds Quill doesn't ship with.
function registerCustomFormats(): void {
    if (Quill.imports['formats/ctaButton']) return;

    // Plain Parchment EmbedBlot (same base as Quill's own Image format) — NOT
    // Quill's 'blots/embed', which wraps content in zero-width-space guard nodes
    // meant for embeds with internally-editable text and would swallow our label.
    const { EmbedBlot: Embed } = Quill.import('parchment');
    class CtaButtonBlot extends Embed {
        static blotName = 'ctaButton';
        static tagName = 'a';

        static create(value: CtaValue): HTMLElement {
            const node = super.create() as HTMLAnchorElement;
            node.setAttribute('href', value.url);
            node.setAttribute('class', 'btn');
            node.textContent = value.label;
            return node;
        }

        static value(node: HTMLElement): CtaValue {
            return { label: node.textContent ?? '', url: node.getAttribute('href') ?? '' };
        }
    }

    const BlockEmbedBase = Quill.import('blots/block/embed') as typeof BlockEmbed;
    class DividerBlot extends BlockEmbedBase {
        static blotName = 'divider';
        static tagName = 'hr';
    }

    Quill.register({ 'formats/ctaButton': CtaButtonBlot, 'formats/divider': DividerBlot });
}

function csrfToken(): string {
    return document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
}

// Quill's toolbar CSS forces a fixed 28px icon-button width and groups buttons
// into separate ".ql-formats" spans (15px gap between groups, no gap within one).
function createToolbarGroup(): HTMLSpanElement {
    const group = document.createElement('span');
    group.className = 'ql-formats';
    return group;
}

function createLabelButton(label: string): HTMLButtonElement {
    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = label;
    button.style.cssText = 'width:auto;padding:3px 8px;margin-right:4px;font-size:12px;white-space:nowrap';
    return button;
}

// A click on an image can leave the cursor either right on it or right after it;
// check content (not blot-tree position, which is ambiguous at blot boundaries).
function findSelectedImageIndex(quill: Quill, range: { index: number; length: number }): number | null {
    for (const index of [range.index, range.index - 1]) {
        if (index < 0) continue;
        const insert = quill.getContents(index, 1).ops[0]?.insert;
        if (insert && typeof insert === 'object' && 'image' in insert) return index;
    }
    return null;
}

export function initNewsletterEditor(): void {
    registerCustomFormats();

    document.querySelectorAll<HTMLElement>('[data-newsletter-editor]').forEach((editor) => {
        const form = editor.closest('form');
        const uploadUrl = editor.dataset.uploadUrl ?? '';
        let pendingUploads = 0;
        const saveButtons = Array.from(form?.querySelectorAll<HTMLButtonElement>('button[type="submit"]') ?? []);
        const saveButtonLabels = saveButtons.map((button) => button.textContent ?? 'Salva template');
        const updateSaveState = (): void => {
            saveButtons.forEach((button, index) => {
                const busy = pendingUploads > 0;
                button.disabled = busy;
                button.setAttribute('aria-busy', busy ? 'true' : 'false');
                button.textContent = busy ? 'Caricamento immagini...' : saveButtonLabels[index];
            });
        };

        ['it', 'en'].forEach((locale) => {
            const mount = editor.querySelector<HTMLElement>(`[data-newsletter-quill="${locale}"]`);
            const input = editor.querySelector<HTMLInputElement>(`[data-newsletter-html="${locale}"]`);
            const status = editor.querySelector<HTMLElement>(`[data-newsletter-status="${locale}"]`);
            if (!mount || !input) return;

            const quill = new Quill(mount, {
                theme: 'snow',
                formats: ['bold', 'italic', 'underline', 'header', 'list', 'align', 'link', 'image', 'ctaButton', 'divider'],
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline'],
                        [{ header: 1 }, { header: 2 }, { header: 3 }],
                        [{ list: 'ordered' }, { list: 'bullet' }],
                        [{ align: [] }],
                        ['link', 'image'],
                        ['clean'],
                    ],
                },
            });
            quill.root.innerHTML = input.value;

            const toolbar = quill.getModule('toolbar') as Toolbar;

            toolbar.addHandler('image', (): void => {
                if (!uploadUrl) return;
                const file = document.createElement('input');
                file.type = 'file';
                file.accept = ALLOWED_IMAGE_TYPES.join(',');
                file.onchange = async (): Promise<void> => {
                    const selected = file.files?.[0];
                    if (!selected) return;
                    if (!ALLOWED_IMAGE_TYPES.includes(selected.type)) {
                        if (status) status.textContent = locale === 'it' ? 'Formato non supportato: usa JPG, PNG, WebP, GIF o BMP.' : 'Unsupported format: use JPG, PNG, WebP, GIF, or BMP.';
                        return;
                    }
                    if (selected.size > MAX_IMAGE_BYTES) {
                        if (status) status.textContent = locale === 'it' ? 'File troppo grande: massimo 10 MB.' : 'File too large: maximum 10 MB.';
                        return;
                    }
                    const range = quill.getSelection(true);
                    pendingUploads += 1;
                    updateSaveState();
                    if (status) status.textContent = locale === 'it' ? 'Caricamento immagine...' : 'Uploading image...';
                    const formData = new FormData();
                    formData.append('image', selected);
                    try {
                        const response = await fetch(uploadUrl, {
                            method: 'POST',
                            body: formData,
                            credentials: 'same-origin',
                            headers: { 'X-CSRF-TOKEN': csrfToken(), Accept: 'application/json' },
                        });
                        if (!response.ok) {
                            if (response.status === 422) {
                                const errorPayload = await response.json().catch(() => null) as { errors?: { image?: string[] } } | null;
                                throw new Error(errorPayload?.errors?.image?.[0] ?? `HTTP ${response.status}`);
                            }
                            throw new Error(`HTTP ${response.status}`);
                        }
                        const payload = await response.json() as { url: string };
                        if (!payload.url) throw new Error('Missing image url');
                        quill.insertEmbed(range.index, 'image', payload.url, 'user');
                        quill.setSelection(range.index + 1, 0, 'user');
                        if (status) status.textContent = locale === 'it' ? 'Immagine caricata.' : 'Image uploaded.';
                    } catch (error) {
                        const message = error instanceof Error ? error.message : 'unknown error';
                        if (status) status.textContent = locale === 'it' ? `Upload non riuscito: ${message}` : `Upload failed: ${message}`;
                    } finally {
                        pendingUploads -= 1;
                        updateSaveState();
                    }
                };
                file.click();
            });

            const actionsGroup = createToolbarGroup();

            const ctaButton = createLabelButton(locale === 'it' ? 'Bottone CTA' : 'CTA button');
            ctaButton.onclick = (): void => {
                const label = window.prompt(locale === 'it' ? 'Testo del bottone' : 'Button text');
                if (!label) return;
                const url = window.prompt(locale === 'it' ? 'URL di destinazione (https://...)' : 'Target URL (https://...)');
                if (!url) return;
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'ctaButton', { label, url }, 'user');
                quill.setSelection(range.index + 1, 0, 'user');
            };
            actionsGroup.append(ctaButton);

            const separatorButton = createLabelButton(locale === 'it' ? 'Separatore' : 'Separator');
            separatorButton.onclick = (): void => {
                const range = quill.getSelection(true);
                quill.insertEmbed(range.index, 'divider', true, 'user');
                quill.setSelection(range.index + 1, 0, 'user');
            };
            actionsGroup.append(separatorButton);
            toolbar.container?.append(actionsGroup);

            const sizeGroup = createToolbarGroup();
            WIDTH_PRESETS.forEach((preset) => {
                const sizeButton = createLabelButton(preset);
                sizeButton.title = locale === 'it' ? 'Ridimensiona immagine selezionata' : 'Resize selected image';
                sizeButton.onclick = (): void => {
                    const range = quill.getSelection();
                    if (!range) return;
                    const imageIndex = findSelectedImageIndex(quill, range);
                    if (imageIndex === null) {
                        if (status) status.textContent = locale === 'it' ? 'Seleziona prima un\u2019immagine.' : 'Select an image first.';
                        return;
                    }
                    quill.formatText(imageIndex, 1, 'width', preset, 'user');
                };
                sizeGroup.append(sizeButton);
            });
            toolbar.container?.append(sizeGroup);

            quill.on('text-change', () => {
                input.value = quill.root.innerHTML;
            });
        });
    });
}
