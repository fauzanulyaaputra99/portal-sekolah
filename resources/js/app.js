import * as Turbo from '@hotwired/turbo';

// Ensure dark mode is consistent on Turbo page visits
document.addEventListener('turbo:load', () => {
    if (localStorage.theme === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
});
