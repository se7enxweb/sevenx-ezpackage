/*
 * ezwebin: the behavior of the page layout, in modern vanilla JavaScript (no jQuery, no YUI, no inline scripts or
 * handlers), so the design runs under a Content Security Policy of script-src 'self'. Printed once at the end of
 * the page by page_footer_script.tpl; it reads markup only, and every feature works without it (progressive
 * enhancement): the menus stay open, the gallery links open the image pages.
 *
 *   #ezwebin-theme-toggle          the color mode switch: light -> dark -> system (ezwebin-theme.js sets the mode)
 *   #ezwebin-menu-toggle           small screens: shows and hides the main navigation (#topmenu-position)
 *   #ezwebin-sidemenu-toggle       small screens: shows and hides the section navigation (#sidemenu)
 *   [data-ezwebin-keynav]          the arrow keys, Home and End move between the links of a menu
 *   .limitdisplay-user-<id>        shown to the signed-in user <id> only (body[data-exp-user]); replaces the
 *                                  inline <style> of page_head_displaystyles.tpl
 *   [data-ezwebin-gallery]         the gallery's slideshow: a native <dialog> with the large images, previous/next,
 *                                  play/pause, keyboard (arrows, Home, End, space, Escape) and swipe; it replaces
 *                                  the page-by-page "slideshow" and the old pop-ups; the close button, Escape, a
 *                                  click beside the picture and the browser's Back close it, and the focus goes
 *                                  back to the picture that opened it
 *   [data-ezwebin-slideshow-start] opens that slideshow at the first image and plays it
 *   [data-ezwebin-pager]           a page with rel=prev/next links (an image of a gallery): the left and right
 *                                  arrow keys go to the previous and next page
 */
(() => {
    'use strict';
    if (window.ezwebinPage) { return; }
    window.ezwebinPage = true;

    const doc = document;
    const root = doc.documentElement;
    const narrow = window.matchMedia('(max-width: 47.99em)');
    const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const isTyping = (el) => !!el && (el.isContentEditable || /^(INPUT|TEXTAREA|SELECT)$/.test(el.tagName));

    /* Color mode switch */
    const themeToggle = () => {
        const button = doc.getElementById('ezwebin-theme-toggle');
        const theme = window.ezwebinTheme;
        if (!button || !theme) { return; }
        const status = doc.getElementById('ezwebin-theme-status');
        const label = button.querySelector('.header-theme-label');
        const show = () => {
            const text = button.getAttribute(`data-label-${theme.choice()}`) || '';
            if (label) { label.textContent = text; }
            button.title = text;
            button.dataset.choice = theme.choice();
        };
        button.hidden = false;
        show();
        root.addEventListener('ezwebin:theme', show);
        button.addEventListener('click', () => {
            theme.next();
            if (status) { status.textContent = button.getAttribute(`data-label-${theme.choice()}`) || ''; }
        });
    };

    /* Disclosure buttons of the menus (small screens only; the stylesheet shows the buttons below 48em) */
    const disclosure = (buttonId, panel, onClose) => {
        const button = doc.getElementById(buttonId);
        if (!button || !panel) { return; }
        const set = (open) => {
            button.setAttribute('aria-expanded', String(open));
            panel.classList.toggle('is-open', open);
        };
        button.hidden = false;
        set(false);
        button.addEventListener('click', () => set(button.getAttribute('aria-expanded') !== 'true'));
        panel.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && narrow.matches && button.getAttribute('aria-expanded') === 'true') {
                set(false);
                button.focus();
                if (onClose) { onClose(); }
            }
        });
        narrow.addEventListener('change', () => { if (!narrow.matches) { set(false); } });
    };

    /* Arrow keys in the menus */
    const keynav = () => {
        doc.addEventListener('keydown', (e) => {
            const list = e.target.closest && e.target.closest('[data-ezwebin-keynav]');
            if (!list || e.target.tagName !== 'A' || e.altKey || e.ctrlKey || e.metaKey) { return; }
            const vertical = list.dataset.ezwebinKeynav === 'vertical' || narrow.matches;
            const prevKey = vertical ? 'ArrowUp' : 'ArrowLeft';
            const nextKey = vertical ? 'ArrowDown' : 'ArrowRight';
            if (![prevKey, nextKey, 'Home', 'End'].includes(e.key)) { return; }
            const links = [...list.querySelectorAll('a[href]')].filter((a) => a.offsetParent !== null);
            const i = links.indexOf(e.target);
            if (i < 0) { return; }
            let to = i;
            if (e.key === prevKey) { to = (i - 1 + links.length) % links.length; }
            else if (e.key === nextKey) { to = (i + 1) % links.length; }
            else if (e.key === 'Home') { to = 0; }
            else { to = links.length - 1; }
            e.preventDefault();
            links[to].focus();
        });
    };

    /* The signed-in user's own elements (comments and the like) */
    const limitDisplay = () => {
        const id = (doc.body.dataset.expUser || '').replace(/\D/g, '');
        if (!id) { return; }
        doc.querySelectorAll(`.limitdisplay-user-${id}`).forEach((el) => el.classList.add('limitdisplay-visible'));
        doc.querySelectorAll(`.limitdisplay-user-block-${id}`).forEach((el) => el.classList.add('limitdisplay-visible-block'));
    };

    /* Gallery slideshow */
    class Slideshow {
        constructor(gallery) {
            this.gallery = gallery;
            this.slides = [...gallery.querySelectorAll('a[data-ezwebin-slide][data-ezwebin-src]')];
            this.index = 0;
            this.timer = 0;
            this.label = (name) => gallery.getAttribute(`data-label-${name}`) || name;
            if (!this.slides.length || typeof HTMLDialogElement !== 'function') { return; }
            this.slides.forEach((link, i) => link.addEventListener('click', (e) => {
                if (e.button !== 0 || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) { return; }
                e.preventDefault();
                this.open(i, false);
            }));
            this.ready = true;
        }

        build() {
            const d = doc.createElement('dialog');
            d.className = 'ezwebin-slideshow';
            d.setAttribute('aria-roledescription', 'carousel');
            const button = (cls, name, icon) => {
                const b = doc.createElement('button');
                b.type = 'button';
                b.className = `slideshow-button ${cls}`;
                b.setAttribute('aria-label', this.label(name));
                b.title = this.label(name);
                b.innerHTML = `<svg aria-hidden="true" focusable="false" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${icon}</svg>`;
                return b;
            };
            this.closeButton = button('slideshow-close', 'close', '<path d="M6 6l12 12M18 6L6 18"/>');
            this.prevButton = button('slideshow-prev', 'previous', '<path d="M15 5l-7 7 7 7"/>');
            this.nextButton = button('slideshow-next', 'next', '<path d="M9 5l7 7-7 7"/>');
            this.playButton = button('slideshow-play', 'play', '<path class="icon-play" d="M8 5v14l11-7z"/><path class="icon-pause" d="M8 5v14M16 5v14"/>');
            this.figure = doc.createElement('figure');
            this.figure.className = 'slideshow-figure';
            this.image = doc.createElement('img');
            this.image.className = 'slideshow-image';
            this.caption = doc.createElement('figcaption');
            this.caption.className = 'slideshow-caption';
            this.title = doc.createElement('a');
            this.title.className = 'slideshow-title';
            this.position = doc.createElement('p');
            this.position.className = 'slideshow-position';
            this.position.setAttribute('aria-live', 'polite');
            this.text = doc.createElement('div');
            this.text.className = 'slideshow-text';
            this.caption.append(this.title, this.text, this.position);
            this.figure.append(this.image, this.caption);
            const bar = doc.createElement('div');
            bar.className = 'slideshow-bar';
            bar.append(this.prevButton, this.playButton, this.nextButton, this.closeButton);
            d.append(this.figure, bar);

            this.closeButton.addEventListener('click', () => d.close());
            this.prevButton.addEventListener('click', () => this.go(this.index - 1));
            this.nextButton.addEventListener('click', () => this.go(this.index + 1));
            this.playButton.addEventListener('click', () => this.play(!this.timer));
            d.addEventListener('close', () => {
                this.play(false);
                // closed by the button, Escape or the backdrop: drop the history entry that opening added
                if (this.pushed) { this.pushed = false; window.history.back(); }
                if (this.opener && this.opener.isConnected) { this.opener.focus(); }
            });
            // the dialog fills the window: a click beside the picture, its caption and the buttons is a click on the
            // backdrop and closes it
            d.addEventListener('click', (e) => {
                if (e.target === d || !e.target.closest('.slideshow-image, .slideshow-caption, .slideshow-bar')) { d.close(); }
            });
            // the browser's Back closes it too
            window.addEventListener('popstate', () => { if (d.open) { this.pushed = false; d.close(); } });
            d.addEventListener('keydown', (e) => {
                if (e.target.tagName === 'A' && e.key === ' ') { return; }
                const keys = { ArrowLeft: -1, ArrowRight: 1 };
                if (e.key in keys) { e.preventDefault(); this.go(this.index + keys[e.key]); }
                else if (e.key === 'Home') { e.preventDefault(); this.go(0); }
                else if (e.key === 'End') { e.preventDefault(); this.go(this.slides.length - 1); }
                else if (e.key === ' ' && e.target === d) { e.preventDefault(); this.play(!this.timer); }
            });
            let startX = null;
            d.addEventListener('pointerdown', (e) => { if (e.pointerType !== 'mouse') { startX = e.clientX; } });
            d.addEventListener('pointerup', (e) => {
                if (startX === null) { return; }
                const dx = e.clientX - startX;
                startX = null;
                if (Math.abs(dx) > 50) { this.go(this.index + (dx < 0 ? 1 : -1)); }
            });
            doc.body.append(d);
            this.dialog = d;
        }

        open(i, autoplay) {
            if (!this.dialog) { this.build(); }
            this.go(i);
            if (!this.dialog.open) {
                this.opener = doc.activeElement instanceof HTMLElement && doc.activeElement !== doc.body ? doc.activeElement : this.slides[i];
                this.dialog.showModal();
                try { window.history.pushState({ ezwebinSlideshow: true }, ''); this.pushed = true; } catch (err) { this.pushed = false; }
            }
            this.play(autoplay);
            (autoplay ? this.playButton : this.nextButton).focus();
        }

        go(i) {
            const n = this.slides.length;
            this.index = ((i % n) + n) % n;
            const link = this.slides[this.index];
            const thumb = link.querySelector('img');
            this.image.src = link.dataset.ezwebinSrc;
            if (link.dataset.ezwebinWidth) { this.image.width = Number(link.dataset.ezwebinWidth); }
            if (link.dataset.ezwebinHeight) { this.image.height = Number(link.dataset.ezwebinHeight); }
            this.image.alt = thumb ? thumb.alt : '';
            const item = link.closest('.content-view-galleryline');
            const name = item && item.querySelector('.attribute-name');
            this.title.textContent = thumb ? thumb.alt : (name ? name.textContent.trim() : '');
            this.title.href = link.href;
            const caption = item && item.querySelector('.gallery-caption');
            this.text.replaceChildren(...(caption ? [...caption.cloneNode(true).childNodes] : []));
            this.position.textContent = `${this.index + 1} / ${n}`;
            this.dialog.setAttribute('aria-label', `${this.title.textContent} (${this.position.textContent})`);
            // the next image is fetched while this one is looked at
            const next = this.slides[(this.index + 1) % n];
            if (next) { new Image().src = next.dataset.ezwebinSrc; }
        }

        play(on) {
            window.clearInterval(this.timer);
            this.timer = 0;
            if (on && this.slides.length > 1) {
                this.timer = window.setInterval(() => this.go(this.index + 1), reducedMotion.matches ? 8000 : 5000);
            }
            if (this.playButton) {
                const name = this.timer ? 'pause' : 'play';
                this.playButton.setAttribute('aria-label', this.label(name));
                this.playButton.title = this.label(name);
                this.playButton.classList.toggle('is-playing', !!this.timer);
            }
        }
    }

    const galleries = () => {
        const shows = [...doc.querySelectorAll('[data-ezwebin-gallery]')].map((g) => new Slideshow(g)).filter((s) => s.ready);
        doc.querySelectorAll('[data-ezwebin-slideshow-start]').forEach((link) => {
            const show = shows[0];
            if (!show) { return; }
            link.addEventListener('click', (e) => {
                e.preventDefault();
                show.open(0, true);
            });
        });
    };

    /* Previous / next pages with the arrow keys */
    const pager = () => {
        const nav = doc.querySelector('[data-ezwebin-pager]');
        if (!nav) { return; }
        doc.addEventListener('keydown', (e) => {
            if (e.defaultPrevented || e.altKey || e.ctrlKey || e.metaKey || e.shiftKey || isTyping(e.target)
                || doc.querySelector('dialog[open]')) { return; }
            const rel = { ArrowLeft: 'prev', ArrowRight: 'next' }[e.key];
            const link = rel && nav.querySelector(`a[rel="${rel}"]`);
            if (link) { window.location.assign(link.href); }
        });
    };

    const start = () => {
        themeToggle();
        disclosure('ezwebin-menu-toggle', doc.getElementById('topmenu-position'));
        disclosure('ezwebin-sidemenu-toggle', doc.getElementById('sidemenu'));
        keynav();
        limitDisplay();
        galleries();
        pager();
    };
    if (doc.readyState === 'loading') { doc.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
