
(() => {
  const key = 'aircms-docs-theme';
  const getStored = () => localStorage.getItem(key) || 'auto';
  const getSystem = () => window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
  const apply = (theme) => {
    document.documentElement.setAttribute('data-bs-theme', theme === 'auto' ? getSystem() : theme);
    document.documentElement.dataset.themeMode = theme;
    const updateButtons = () => {
      document.querySelectorAll('[data-theme-value]').forEach(btn => {
        btn.classList.toggle('active', btn.dataset.themeValue === theme);
        btn.setAttribute('aria-pressed', btn.dataset.themeValue === theme ? 'true' : 'false');
      });
    };
    if (document.readyState === 'loading') {
      window.addEventListener('DOMContentLoaded', updateButtons, { once: true });
    } else {
      updateButtons();
    }
  };
  apply(getStored());
  window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (getStored() === 'auto') apply('auto');
  });
  window.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-value]').forEach(btn => btn.addEventListener('click', () => {
      const theme = btn.dataset.themeValue;
      localStorage.setItem(key, theme);
      apply(theme);
    }));
  });
})();
