import './bootstrap';

const loginErrorPopup = document.querySelector('[data-login-error]');

if (loginErrorPopup) {
    const closeButton = loginErrorPopup.querySelector('[data-close-login-error]');
    const closePopup = () => loginErrorPopup.remove();

    closeButton?.addEventListener('click', closePopup);
    loginErrorPopup.addEventListener('click', (event) => {
        if (event.target === loginErrorPopup) {
            closePopup();
        }
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') {
            closePopup();
        }
    });
    closeButton?.focus();
}

const loadingOverlay = document.querySelector('[data-page-loading]');

if (loadingOverlay) {
    const showLoading = () => {
        loadingOverlay.classList.remove('hidden');
        loadingOverlay.classList.add('flex');
        loadingOverlay.setAttribute('aria-hidden', 'false');
    };

    const hideLoading = () => {
        loadingOverlay.classList.add('hidden');
        loadingOverlay.classList.remove('flex');
        loadingOverlay.setAttribute('aria-hidden', 'true');
    };

    document.addEventListener('click', (event) => {
        if (event.defaultPrevented || !(event.target instanceof Element) || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
        }

        const link = event.target.closest('a[href]');

        if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) {
            return;
        }

        const destination = new URL(link.href, window.location.href);
        const isSamePageAnchor = destination.pathname === window.location.pathname
            && destination.search === window.location.search
            && destination.hash;

        if (destination.origin === window.location.origin && destination.href !== window.location.href && !isSamePageAnchor) {
            showLoading();
        }
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;

        if (event.defaultPrevented || !(form instanceof HTMLFormElement) || (form.target && form.target !== '_self')) {
            return;
        }

        showLoading();
    });

    window.addEventListener('pageshow', hideLoading);

    const navigation = performance.getEntriesByType('navigation')[0];
    const domReady = navigation?.domContentLoadedEventEnd > 0;

    if (document.readyState === 'complete' || domReady) {
        hideLoading();
    } else {
        showLoading();
        document.addEventListener('DOMContentLoaded', hideLoading, { once: true });
        window.setTimeout(hideLoading, 10000);
    }
}
