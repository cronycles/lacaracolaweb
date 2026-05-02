/**
 * Language switcher dropdown — updates locale via form POST and stores preference.
 */

export function initLangSwitcher(): void {
    // Language dropdown toggle buttons
    const toggles = document.querySelectorAll<HTMLButtonElement>('.lang-dropdown__toggle');
    
    // Language selection buttons (in both dropdowns)
    const buttons = document.querySelectorAll<HTMLButtonElement>('[data-lang]');

    // Close all dropdowns
    function closeAllDropdowns(): void {
        toggles.forEach(toggle => {
            toggle.setAttribute('aria-expanded', 'false');
            const menu = toggle.nextElementSibling as HTMLElement | null;
            if (menu) {
                menu.classList.remove('show');
            }
        });
    }

    // Toggle dropdown on toggle button click
    toggles.forEach(toggle => {
        toggle.addEventListener('click', (e: Event) => {
            e.stopPropagation();
            const isExpanded = toggle.getAttribute('aria-expanded') === 'true';
            
            // Close all other dropdowns
            closeAllDropdowns();
            
            // Toggle this one
            if (!isExpanded) {
                toggle.setAttribute('aria-expanded', 'true');
                const menu = toggle.nextElementSibling as HTMLElement | null;
                if (menu) {
                    menu.classList.add('show');
                }
            }
        });
    });

    // Handle language selection
    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const locale = btn.dataset['lang'];
            if (!locale) return;

            // POST to Laravel locale switch route
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '/locale';
            form.style.display = 'none';

            // CSRF token from meta tag
            const csrf = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = csrf;

            const localeInput = document.createElement('input');
            localeInput.type = 'hidden';
            localeInput.name = 'locale';
            localeInput.value = locale;

            form.appendChild(csrfInput);
            form.appendChild(localeInput);
            document.body.appendChild(form);
            form.submit();
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e: Event) => {
        const target = e.target as HTMLElement;
        const isDropdown = target.closest('.lang-dropdown');
        
        if (!isDropdown) {
            closeAllDropdowns();
        }
    });

    // Close dropdown on Escape key
    document.addEventListener('keydown', (e: KeyboardEvent) => {
        if (e.key === 'Escape') {
            closeAllDropdowns();
        }
    });
}

