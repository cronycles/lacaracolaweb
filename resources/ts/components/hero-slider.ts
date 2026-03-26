/**
 * Hero slider — auto-rotating background images for home hero section.
 * Respects prefers-reduced-motion.
 */

const INTERVAL_MS = 5000;

export function initHeroSlider(): void {
    const slider = document.querySelector<HTMLElement>('.hero-slider');
    if (!slider) return;

    const slides = Array.from(slider.querySelectorAll<HTMLElement>('.hero-slider__slide'));
    const dots = Array.from(document.querySelectorAll<HTMLButtonElement>('.hero-dots button'));

    if (slides.length <= 1) return;

    let current = 0;
    let timer: ReturnType<typeof setInterval> | null = null;

    const goTo = (index: number): void => {
        slides[current].classList.remove('active');
        dots[current]?.classList.remove('active');
        current = (index + slides.length) % slides.length;
        slides[current].classList.add('active');
        dots[current]?.classList.add('active');
    };

    const start = (): void => {
        timer = setInterval(() => goTo(current + 1), INTERVAL_MS);
    };

    const stop = (): void => {
        if (timer) clearInterval(timer);
    };

    // Initialise first slide
    slides[0].classList.add('active');
    dots[0]?.classList.add('active');

    // Respect reduced motion preference
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    if (!reducedMotion) {
        start();
        slider.addEventListener('mouseenter', stop);
        slider.addEventListener('mouseleave', start);
    }

    // Dot navigation
    dots.forEach((dot, i) => {
        dot.addEventListener('click', () => {
            stop();
            goTo(i);
        });
    });
}
