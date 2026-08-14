/**
 * Gallery lightbox — clicking a gallery item opens a full-screen viewer.
 */

export function initGallery(): void {
    const items = document.querySelectorAll<HTMLElement>('.gallery__item');
    const lightbox = document.querySelector<HTMLElement>('.lightbox');
    const lightboxImg = lightbox?.querySelector<HTMLImageElement>('img');
    const closeBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__close');
    const backBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__back');
    const shareBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__share');
    const counter = lightbox?.querySelector<HTMLElement>('.lightbox__counter');
    const prevBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__nav--prev');
    const nextBtn = lightbox?.querySelector<HTMLButtonElement>('.lightbox__nav--next');

    if (!lightbox || !lightboxImg || items.length === 0) return;

    const images = Array.from(items)
        .map((item) => item.querySelector<HTMLImageElement>('img'))
        .filter((img): img is HTMLImageElement => img !== null);

    if (images.length === 0) return;

    let currentIndex = 0;
    let touchStartX = 0;

    const render = (index: number): void => {
        const safeIndex = (index + images.length) % images.length;
        currentIndex = safeIndex;
        lightboxImg.src = images[safeIndex].src;
        lightboxImg.alt = images[safeIndex].alt;
        if (counter) counter.textContent = `${safeIndex + 1} / ${images.length}`;
    };

    const open = (index: number): void => {
        render(index);
        lightbox.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    const close = (): void => {
        lightbox.classList.remove('open');
        document.body.style.overflow = '';
    };

    const prev = (): void => render(currentIndex - 1);
    const next = (): void => render(currentIndex + 1);

    items.forEach((item, index) => {
        item.addEventListener('click', () => {
            open(index);
        });
    });

    closeBtn?.addEventListener('click', close);
    backBtn?.addEventListener('click', close);
    shareBtn?.addEventListener('click', async (e) => {
        e.stopPropagation();
        const shareData = {
            title: document.title,
            url: window.location.href,
        };

        if (navigator.share) {
            await navigator.share(shareData).catch(() => undefined);
            return;
        }

        await navigator.clipboard?.writeText(shareData.url);
    });
    prevBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        prev();
    });
    nextBtn?.addEventListener('click', (e) => {
        e.stopPropagation();
        next();
    });

    lightbox.addEventListener('click', (e) => {
        if (e.target === lightbox) close();
    });

    lightbox.addEventListener('touchstart', (e) => {
        touchStartX = e.changedTouches[0]?.screenX ?? 0;
    }, { passive: true });

    lightbox.addEventListener('touchend', (e) => {
        const touchEndX = e.changedTouches[0]?.screenX ?? touchStartX;
        const distance = touchEndX - touchStartX;
        if (Math.abs(distance) < 50) return;
        if (distance > 0) prev();
        if (distance < 0) next();
    }, { passive: true });

    // Keyboard controls in lightbox
    document.addEventListener('keydown', (e) => {
        if (!lightbox.classList.contains('open')) return;

        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
    });
}
