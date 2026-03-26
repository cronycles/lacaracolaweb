/**
 * Gallery lightbox — clicking a gallery item opens a full-screen viewer.
 */

export function initGallery(): void {
    const items = document.querySelectorAll<HTMLElement>('.gallery__item');
    const lightbox = document.querySelector<HTMLElement>('.lightbox');
    const lightboxImg = lightbox?.querySelector<HTMLImageElement>('img');
    const closeBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__close');

    if (!lightbox || !lightboxImg) return;

    const open = (src: string, alt: string): void => {
        lightboxImg.src = src;
        lightboxImg.alt = alt;
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    const close = (): void => {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    };

    items.forEach(item => {
        item.addEventListener('click', () => {
            const img = item.querySelector<HTMLImageElement>('img');
            if (img) open(img.src, img.alt);
        });
    });

    closeBtn?.addEventListener('click', close);
    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) close();
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') close();
    });
}
