/**
 * Mobile navigation — toggle mobile menu open/close.
 */

export function initMobileNav(): void {
    const burger = document.querySelector<HTMLButtonElement>('.nav__burger');
    const mobileMenu = document.querySelector<HTMLElement>('.nav-mobile');

    if (!burger || !mobileMenu) return;

    const burgerLines = burger.querySelectorAll<HTMLSpanElement>('span');

    const setBurgerState = (isOpen: boolean): void => {
        burger.setAttribute('aria-expanded', String(isOpen));
    };

    const close = (): void => {
        mobileMenu.classList.remove('open');
        burger.classList.remove('open');
        setBurgerState(false);
        document.body.style.overflow = '';
    };

    const toggle = (): void => {
        const isOpen = mobileMenu.classList.toggle('open');
        burger.classList.toggle('open', isOpen);
        setBurgerState(isOpen);
        // Prevent body scroll when menu is open
        document.body.style.overflow = isOpen ? 'hidden' : '';
    };

    burger.addEventListener('click', toggle);

    // Close button inside mobile menu
    const closeBtn = mobileMenu.querySelector<HTMLButtonElement>('.nav-mobile__close');
    if (closeBtn) {
        closeBtn.addEventListener('click', close);
    }

    // Close on link click
    mobileMenu.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', close);
    });
}
