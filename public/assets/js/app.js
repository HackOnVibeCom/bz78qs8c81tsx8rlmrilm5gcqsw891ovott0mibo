/* Shared, accessible UI behavior. Loaded after the local icon renderer. */
(() => {
  'use strict';
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const base = new URL(document.body.dataset.appUrl || '/', location.origin).href;
  const url = (path) => new URL(path.replace(/^\//, ''), base.endsWith('/') ? base : `${base}/`).href;
  const icons = () => window.lucide?.createIcons();
  const reduceMotion = matchMedia('(prefers-reduced-motion: reduce)');

  function ensureToastRoot() {
    let root = document.getElementById('toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'toast-root';
      root.setAttribute('role', 'status');
      root.setAttribute('aria-live', 'polite');
      document.body.appendChild(root);
    }
    return root;
  }
  function showToast(message, type = 'success', duration = 4000) {
    const root = ensureToastRoot();
    const toast = document.createElement('div');
    toast.className = `toast toast--${type}`;
    toast.textContent = message;
    root.appendChild(toast);
    while (root.children.length > 3) root.firstElementChild.remove();
    setTimeout(() => toast.remove(), duration);
  }
  window.showToast = showToast;

  const nav = document.querySelector('.nav');
  const toggle = nav?.querySelector('.nav__toggle');
  function closeNav(restoreFocus = false) {
    if (!nav || !toggle) return;
    nav.classList.remove('is-open');
    toggle.setAttribute('aria-expanded', 'false');
    toggle.setAttribute('aria-label', 'Open navigation');
    if (restoreFocus) toggle.focus();
  }
  toggle?.addEventListener('click', () => {
    const open = nav.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', String(open));
    toggle.setAttribute('aria-label', open ? 'Close navigation' : 'Open navigation');
  });
  nav?.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => closeNav()));
  matchMedia('(min-width: 861px)').addEventListener('change', (event) => { if (event.matches) closeNav(); });

  document.querySelectorAll('[data-disclosure]').forEach((disclosure) => {
    const button = disclosure.querySelector('[aria-controls]');
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    const close = () => { panel.hidden = true; button.setAttribute('aria-expanded', 'false'); };
    button.addEventListener('click', () => {
      panel.hidden = !panel.hidden;
      button.setAttribute('aria-expanded', String(!panel.hidden));
    });
    document.addEventListener('click', (event) => { if (!disclosure.contains(event.target)) close(); });
    disclosure.addEventListener('keydown', (event) => {
      if (event.key === 'Escape') { event.stopPropagation(); close(); button.focus(); }
    });
    disclosure.addEventListener('focusout', (event) => { if (!disclosure.contains(event.relatedTarget)) close(); });
  });
  document.addEventListener('click', (event) => { if (nav && !nav.contains(event.target)) closeNav(); });
  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && nav?.classList.contains('is-open')) closeNav(true);
  });

  async function copyText(text) {
    try {
      if (!navigator.clipboard?.writeText) throw new Error('Use clipboard fallback');
      await navigator.clipboard.writeText(text);
    } catch {
      const active = document.activeElement;
      const input = document.createElement('textarea');
      input.value = text;
      input.style.cssText = 'position:fixed;left:-9999px;top:0;opacity:0';
      input.setAttribute('aria-label', 'Link to copy');
      document.body.appendChild(input);
      input.select();
      let copied = false;
      try { copied = document.execCommand('copy'); }
      finally { input.remove(); active?.focus({ preventScroll: true }); }
      if (!copied) throw new Error('Could not copy automatically. Please copy the link from the address bar.');
    }
  }
  function trackShare(slug) {
    if (!slug) return;
    fetch(url(`/challenge/${encodeURIComponent(slug)}/share`), {
      method: 'POST', headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken, Accept: 'application/json' },
      body: '{}', credentials: 'same-origin', keepalive: true,
    }).catch(() => {});
  }
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-copy]');
    if (!button) return;
    event.preventDefault();
    if (button.disabled) return;
    const original = button.innerHTML;
    button.disabled = true;
    try {
      await copyText(button.dataset.copy);
      button.innerHTML = '<i data-lucide="check" width="15" height="15"></i> Copied!';
      icons();
      showToast('Link copied. Pass it on!');
      trackShare(button.dataset.slug);
      setTimeout(() => { button.innerHTML = original; }, 1800);
    } catch (error) { showToast(error.message, 'error'); }
    finally { button.disabled = false; }
  });
  document.addEventListener('click', async (event) => {
    const button = event.target.closest('[data-share-native]');
    if (!button) return;
    event.preventDefault();
    const shareUrl = button.dataset.url || location.href;
    const text = button.dataset.text || document.title;
    try {
      if (navigator.share) await navigator.share({ title: document.title, text, url: shareUrl });
      else { await copyText(shareUrl); showToast('Link copied. Share it with your friends!'); }
      trackShare(button.dataset.slug);
    } catch (error) { if (error.name !== 'AbortError') showToast('Sharing failed. Try the Copy link button.', 'error'); }
  });
  document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-share]');
    if (link) trackShare(link.dataset.slug);
    // Social anchors keep their normal browser behavior: one click, one tab.
  });

  window.CC = { csrfToken, showToast, url, copyText, icons, reduceMotion };
  icons();
  ensureToastRoot();
  document.querySelectorAll('[data-flash]').forEach((element) => {
    showToast(element.dataset.flash, element.dataset.flashType || 'success');
    element.remove();
  });
})();
