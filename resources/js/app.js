import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
        button.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'dark' : 'light');

            document.querySelectorAll('[data-theme-label]').forEach((label) => {
                label.textContent = isDark ? 'Tema terang' : 'Tema gelap';
            });
        });
    });

    document.querySelectorAll('[data-theme-label]').forEach((label) => {
        label.textContent = document.documentElement.classList.contains('dark') ? 'Tema terang' : 'Tema gelap';
    });
});
