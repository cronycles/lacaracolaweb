/**
 * Language switcher — updates locale via form POST and stores preference.
 */

export function initLangSwitcher(): void {
    const buttons = document.querySelectorAll<HTMLButtonElement>('[data-lang]');

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
}
