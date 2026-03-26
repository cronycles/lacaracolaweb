/**
 * Mobile navigation — toggle mobile menu open/close.
 */

export function initMobileNav(): void {
    const burger = document.querySelector<HTMLButtonElement>('.nav__burger');
    const mobileMenu = document.querySelector<HTMLElement>('.nav-mobile');

    if (!burger || !mobileMenu) return;

    const toggle = (): void => {
        const isOpen = mobileMenu.classList.toggle('open');
        burger.setAttribute('aria-expanded', String(isOpen));
        // Prevent body scroll when menu is open
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    burger.addEventListener('click', toggle);

    // Close on backdrop click or link click
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('open');
            document.body.style.overflow = '';
        });
    });
}
