import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;
Alpine.start();

document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy-link], [data-copy-value]');
    if (!button) return;
    const input = button.dataset.copyLink ? document.getElementById(button.dataset.copyLink) : null;
    const value = input ? input.value : button.dataset.copyValue;
    const feedback = button.parentElement.querySelector('[role="status"]');
    try {
        await navigator.clipboard.writeText(value);
        feedback.textContent = 'Berhasil disalin.';
    } catch {
        if (input) { input.focus(); input.select(); }
        feedback.textContent = 'Salin link yang dipilih secara manual (Ctrl+C).';
    }
});

document.addEventListener('submit', (event) => {
    const button = event.submitter;
    if (!button || event.defaultPrevented) return;
    window.setTimeout(() => {
        button.disabled = true;
        button.setAttribute('aria-busy', 'true');
        button.textContent = 'Menyimpan…';
    }, 0);
});
