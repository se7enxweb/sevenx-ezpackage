/*
 * ezwebin: the color mode (light, dark, or the system's), set before the stylesheets are drawn.
 *
 * Loaded in <head> by pagelayout.tpl as a file of its own, not an inline script, so the page runs under a Content
 * Security Policy of script-src 'self'. It sets the same attributes as the other Exponential designs (admin4, media):
 *
 *   html[data-exp-theme]         light|dark          what core.css reads
 *   html[data-exp-theme-choice]  light|dark|system   what the header's switch shows
 *
 * The choice is kept in this browser (localStorage "exp-ezwebin-theme", or a cookie of that name when the storage
 * is blocked); the server never reads it, so the cached page layout is the same for everyone. Without a choice the
 * system's mode is followed, also when it changes while the page is open. The switch (#ezwebin-theme-toggle in
 * page_header.tpl) is wired by ezwebin.js through window.ezwebinTheme. Printing uses the light mode.
 */
(() => {
    'use strict';
    const KEY = 'exp-ezwebin-theme';
    const CHOICES = ['light', 'dark', 'system'];
    const root = document.documentElement;
    root.classList.add('ezwebin-js');
    const mq = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;

    const valid = (value) => (CHOICES.includes(value) ? value : '');
    const readCookie = () => {
        const m = document.cookie.match(/(?:^|;\s*)exp_ezwebin_theme=([a-z]+)/);
        return m ? valid(m[1]) : '';
    };
    const stored = () => {
        let value = '';
        try { value = valid(window.localStorage.getItem(KEY) || ''); } catch (e) { /* blocked storage */ }
        return value || readCookie() || 'system';
    };
    const save = (choice) => {
        try { window.localStorage.setItem(KEY, choice); } catch (e) { /* blocked storage: the cookie keeps it */ }
        const secure = window.location.protocol === 'https:' ? '; Secure' : '';
        document.cookie = `exp_ezwebin_theme=${choice}; Max-Age=31536000; Path=/; SameSite=Lax${secure}`;
    };
    const apply = (value) => {
        const choice = valid(value) || 'system';
        const mode = choice === 'system' ? (mq && mq.matches ? 'dark' : 'light') : choice;
        root.setAttribute('data-exp-theme', mode);
        root.setAttribute('data-exp-theme-choice', choice);
        root.dispatchEvent(new CustomEvent('ezwebin:theme', { detail: { choice, mode } }));
        return choice;
    };
    const next = () => {
        const i = CHOICES.indexOf(root.getAttribute('data-exp-theme-choice'));
        const choice = CHOICES[(i + 1) % CHOICES.length];
        save(choice);
        return apply(choice);
    };

    apply(stored());
    if (mq) {
        mq.addEventListener('change', () => {
            if (root.getAttribute('data-exp-theme-choice') === 'system') { apply('system'); }
        });
    }
    window.addEventListener('storage', (e) => { if (e.key === KEY) { apply(e.newValue); } });
    window.addEventListener('pageshow', (e) => { if (e.persisted) { apply(stored()); } });
    window.addEventListener('beforeprint', () => root.setAttribute('data-exp-theme', 'light'));
    window.addEventListener('afterprint', () => apply(root.getAttribute('data-exp-theme-choice')));

    window.ezwebinTheme = { apply, next, choice: () => root.getAttribute('data-exp-theme-choice') };
})();
