type NewsletterBlock = {
    type: string;
    text?: string;
    level?: number;
    bold?: boolean;
    italic?: boolean;
    underline?: boolean;
    items?: string[];
    path?: string;
    alt?: string;
    url?: string;
};

export function initNewsletterEditor(): void {
    document.querySelectorAll<HTMLElement>('[data-newsletter-editor]').forEach((editor) => {
        ['it', 'en'].forEach((locale) => {
            const input = editor.querySelector<HTMLInputElement>(`[data-newsletter-json="${locale}"]`);
            const container = editor.querySelector<HTMLElement>(`[data-newsletter-blocks="${locale}"]`);
            const add = editor.querySelector<HTMLButtonElement>(`[data-add-block="${locale}"]`);
            const uploadUrl = editor.dataset.uploadUrl;
            if (!input || !container || !add) return;
            let blocks: NewsletterBlock[] = [];
            try { blocks = JSON.parse(input.value || '[]') as NewsletterBlock[]; } catch { blocks = []; }
            const sync = () => { input.value = JSON.stringify(blocks); };
            const render = () => {
                container.innerHTML = '';
                blocks.forEach((block, index) => {
                    const row = document.createElement('div');
                    row.style.cssText = 'border:1px solid #dde3e6;border-radius:4px;padding:.75rem;margin:.5rem 0;display:grid;gap:.5rem';
                    const type = document.createElement('select');
                    type.className = 'form-select';
                    ['paragraph', 'heading', 'list', 'separator', 'image', 'button'].forEach((value) => {
                        const labels: Record<string, string> = {
                            paragraph: locale === 'it' ? 'Testo' : 'Text',
                            heading: locale === 'it' ? 'Titolo' : 'Heading',
                            list: locale === 'it' ? 'Elenco' : 'List',
                            separator: locale === 'it' ? 'Separatore' : 'Separator',
                            image: locale === 'it' ? 'Immagine' : 'Image',
                            button: locale === 'it' ? 'Link / bottone' : 'Link / button',
                        };
                        type.add(new Option(labels[value], value, value === block.type, value === block.type));
                    });
                    type.onchange = () => { blocks[index] = { type: type.value, text: '' }; sync(); render(); };
                    row.append(type);
                    if (block.type === 'separator') {
                        row.append(document.createTextNode('Separatore'));
                    } else if (block.type === 'image') {
                        const file = document.createElement('input'); file.type = 'file'; file.accept = 'image/*';
                        const alt = document.createElement('input'); alt.className = 'form-input'; alt.placeholder = 'Testo alternativo'; alt.value = block.alt ?? '';
                        alt.oninput = () => { block.alt = alt.value; sync(); };
                        file.onchange = async () => {
                            const selected = file.files?.[0]; if (!selected || !uploadUrl) return;
                            const formData = new FormData(); formData.append('image', selected);
                            const response = await fetch(uploadUrl, { method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '', Accept: 'application/json' } });
                            if (!response.ok) {
                                status.textContent = locale === 'it' ? 'Upload non riuscito.' : 'Upload failed.';
                                return;
                            }
                            const payload = await response.json() as { path: string };
                            block.path = payload.path; status.textContent = locale === 'it' ? 'Immagine caricata.' : 'Image uploaded.'; sync();
                        };
                        const status = document.createElement('span');
                        status.style.cssText = 'color:#6b7f89;font-size:.8rem';
                        status.textContent = block.path ? `Caricata: ${block.path}` : (locale === 'it' ? 'Seleziona un immagine' : 'Choose an image');
                        row.append(file, alt, status);
                    } else if (block.type === 'button') {
                        const label = document.createElement('input'); label.className = 'form-input'; label.placeholder = 'Testo bottone'; label.value = block.text ?? '';
                        const url = document.createElement('input'); url.className = 'form-input'; url.placeholder = 'https://...'; url.value = block.url ?? '';
                        label.oninput = () => { block.text = label.value; sync(); }; url.oninput = () => { block.url = url.value; sync(); }; row.append(label, url);
                    } else if (block.type === 'list') {
                        const textarea = document.createElement('textarea');
                        textarea.className = 'form-input'; textarea.rows = 3; textarea.placeholder = 'Un elemento per riga'; textarea.value = (block.items ?? []).join('\n');
                        textarea.oninput = () => { block.items = textarea.value.split('\n').filter(Boolean); sync(); };
                        row.append(textarea);
                    } else {
                        const inputText = document.createElement('textarea');
                        inputText.className = 'form-input'; inputText.rows = block.type === 'heading' ? 2 : 4; inputText.value = block.text ?? ''; inputText.placeholder = block.type === 'heading' ? 'Titolo' : 'Testo';
                        inputText.oninput = () => { block.text = inputText.value; sync(); }; row.append(inputText);
                        if (block.type === 'heading') {
                            const level = document.createElement('select'); level.className = 'form-select';
                            [1, 2, 3].forEach((value) => level.add(new Option(`Titolo ${value}`, String(value), value === (block.level ?? 2), value === (block.level ?? 2))));
                            level.onchange = () => { block.level = Number(level.value); sync(); }; row.append(level);
                        } else {
                            ['bold', 'italic', 'underline'].forEach((mark) => {
                                const label = document.createElement('label'); const checkbox = document.createElement('input');
                                checkbox.type = 'checkbox'; checkbox.checked = Boolean(block[mark as keyof NewsletterBlock]);
                                checkbox.onchange = () => { block[mark as keyof NewsletterBlock] = checkbox.checked as never; sync(); };
                                label.append(checkbox, ` ${mark}`); row.append(label);
                            });
                        }
                    }
                    const remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn btn--danger btn--sm'; remove.textContent = locale === 'it' ? 'Rimuovi' : 'Remove'; remove.onclick = () => { blocks.splice(index, 1); sync(); render(); }; row.append(remove);
                    container.append(row);
                });
                sync();
            };
            add.onclick = () => { blocks.push({ type: 'paragraph', text: '' }); render(); };
            render();
        });
    });
}
