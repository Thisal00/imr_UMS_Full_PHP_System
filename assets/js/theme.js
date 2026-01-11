(function () {
    const STORAGE_KEY = 'ums-theme';
    const THEMES = ['electric', 'pastel', 'glass'];
    const DEFAULT_THEME = 'electric';

    function normalize(theme) {
        return THEMES.includes(theme) ? theme : DEFAULT_THEME;
    }

    function updateActive(theme) {
        document.querySelectorAll('.theme-option').forEach(el => {
            el.classList.toggle('active-theme', el.dataset.theme === theme);
        });
    }

    function apply(theme) {
        const body = document.body;
        if (!body) return;
        const sanitized = normalize(theme);
        body.classList.remove(...THEMES.map(t => 'theme-' + t));
        body.classList.add('theme-' + sanitized);
        localStorage.setItem(STORAGE_KEY, sanitized);
        updateActive(sanitized);
    }

    function init() {
        apply(localStorage.getItem(STORAGE_KEY));
        document.querySelectorAll('.theme-option').forEach(el => {
            el.addEventListener('click', evt => {
                evt.preventDefault();
                apply(el.dataset.theme);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
