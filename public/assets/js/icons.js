/* Small local SVG line-icon set. No CDN, font, network or runtime dependency.
   Preserves the existing data-lucide/createIcons interface used by the views. */
(() => {
  'use strict';
  const circle = '<circle cx="12" cy="12" r="9"/>';
  const shapes = {
    zap: '<path d="m13 2-9 12h7l-1 8 10-13h-7z"/>',
    sparkles: '<path d="m12 3 2.4 6.6L21 12l-6.6 2.4L12 21l-2.4-6.6L3 12l6.6-2.4zM20 2v4m-2-2h4M3 19v3m-1.5-1.5h3"/>',
    menu: '<path d="M4 6h16M4 12h16M4 18h16"/>',
    x: '<path d="m6 6 12 12M6 18 18 6"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    'arrow-right': '<path d="M4 12h16m-6-6 6 6-6 6"/>',
    'arrow-left': '<path d="M20 12H4m6-6-6 6 6 6"/>',
    'arrow-up-right': '<path d="M6 18 18 6M6 6h12v12"/>',
    'corner-up-right': '<path d="M4 19v-7a5 5 0 0 1 5-5h11m-5-5 5 5-5 5"/>',
    'chevron-down': '<path d="m6 9 6 6 6-6"/>',
    check: '<path d="m5 12 4 4L19 6"/>',
    'check-circle': circle + '<path d="m8 12 3 3 5-6"/>',
    'x-circle': circle + '<path d="m9 9 6 6m-6 0 6-6"/>',
    circle,
    info: circle + '<path d="M12 11v6m0-10v.01"/>',
    'help-circle': circle + '<path d="M9.5 8a2.5 2.5 0 0 1 5 0c0 2-2.5 2-2.5 4m0 4v.01"/>',
    play: '<path d="m8 4 13 8-13 8z"/>',
    trophy: '<path d="M8 3h8v6a4 4 0 0 1-8 0V3ZM8 5H4v2a4 4 0 0 0 4 4m8-6h4v2a4 4 0 0 1-4 4m-4 2v6m-4 2h8m-6-2h4"/>',
    layers: '<path d="m12 3 10 5-10 5L2 8zm-9 9 9 5 9-5M3 16l9 5 9-5"/>',
    users: '<circle cx="9" cy="8" r="3"/><path d="M3 21v-3a6 6 0 0 1 12 0v3M17 5a3 3 0 0 1 0 6m1 4a5 5 0 0 1 3 5v1"/>',
    user: '<circle cx="12" cy="7" r="4"/><path d="M4 21v-2a8 8 0 0 1 16 0v2"/>',
    'external-link': '<path d="M13 3h8v8m0-8L10 14m-1-9H4a1 1 0 0 0-1 1v14a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"/>',
    'log-out': '<path d="M9 3H4v18h5m1-9h12m-5-5 5 5-5 5"/>',
    link: '<path d="m10 14 4-4m-6 6-1 1a3.5 3.5 0 0 1-5-5l5-5a3.5 3.5 0 0 1 5 0m0 10a3.5 3.5 0 0 0 5 0l5-5a3.5 3.5 0 0 0-5-5l-1 1"/>',
    'share-2': '<circle cx="18" cy="5" r="3"/><circle cx="6" cy="12" r="3"/><circle cx="18" cy="19" r="3"/><path d="m8.5 10.5 7-4m-7 7 7 4"/>',
    'message-circle': '<path d="M21 11.5a9 9 0 0 1-13.5 8L3 21l1.5-4.5A9 9 0 1 1 21 11.5Z"/>',
    facebook: '<path d="M15 22v-9h3l.5-4H15V7c0-1 .5-2 2-2h2V1h-3c-4 0-6 2-6 6v2H7v4h3v9"/>',
    twitter: '<path d="m4 3 16 18h-5L3 3h5l13 18M20 3 4 21"/>',
    eye: '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    search: '<circle cx="10.5" cy="10.5" r="7"/><path d="m16 16 5 5"/>',
    'refresh-cw': '<path d="M21 3v6h-6M3 21v-6h6M4 8a8 8 0 0 1 13-4l4 5M3 15l4 5a8 8 0 0 0 13-4"/>',
    repeat: '<path d="M17 2l4 4-4 4M3 11V8a2 2 0 0 1 2-2h16M7 22l-4-4 4-4m14-1v3a2 2 0 0 1-2 2H3"/>',
    asterisk: '<path d="M12 3v18M4.2 7.5l15.6 9M4.2 16.5l15.6-9"/>',
    code: '<path d="m8 6-6 6 6 6m8-12 6 6-6 6m-3-15-2 18"/>',
    'flask-conical': '<path d="M9 3h6m-5 0v6l-6 10a1.3 1.3 0 0 0 1 2h14a1.3 1.3 0 0 0 1-2L14 9V3M7 15h10"/>',
    landmark: '<path d="m3 8 9-5 9 5H3Zm0 13h18M5 11v7m7-7v7m7-7v7"/>',
    globe: circle + '<ellipse cx="12" cy="12" rx="4" ry="9"/><path d="M3 12h18"/>',
    clapperboard: '<rect x="3" y="8" width="18" height="13" rx="1"/><path d="m3 8-1-4 18-3 1 5-18 2Zm3-5 3 4m3-5 3 4M3 12h18"/>',
    music: '<path d="M9 18V5l12-2v13M9 9l12-2"/><ellipse cx="6" cy="18" rx="3" ry="3"/><ellipse cx="18" cy="16" rx="3" ry="3"/>',
    brain: '<path d="M12 4v16m0-14C9 0 4 3 5 7c-4 0-4 6-1 7-2 4 2 7 5 6 1 2 3 1 3-1m0-13c3-6 8-3 7 1 4 0 4 6 1 7 2 4-2 7-5 6-1 2-3 1-3-1M5 7l3 2m-4 5 4-1m11-6-3 2m4 5-4-1"/>',
    rocket: '<path d="M14 4c3-2 7-2 7-2s0 4-2 7l-7 8-5-5 7-8ZM7 12H3l4-6h5m0 11v4l6-4v-5M6 16l-3 5 5-3"/><circle cx="16" cy="7" r="1"/>',
    clock: circle + '<path d="M12 6v6l4 2"/>',
    compass: circle + '<path d="m16 8-2 6-6 2 2-6z"/>',
    medal: '<circle cx="12" cy="15" r="6"/><path d="m8 10-4-8h5l3 7 3-7h5l-4 8m-4 2v5m-2-1h4"/>',
    award: '<circle cx="12" cy="8" r="6"/><path d="m8 13-2 9 6-3 6 3-2-9"/>',
    flame: '<path d="M12 2c2 6 7 6 7 12a7 7 0 0 1-14 0c0-3 1-5 4-7 0 3 1 4 2 4 2-2 2-5 1-9Z"/>',
    target: circle + '<circle cx="12" cy="12" r="5"/><circle cx="12" cy="12" r="1"/>',
    star: '<path d="m12 2 3 6 7 1-5 5 1 8-6-4-6 4 1-8-5-5 7-1z"/>',
  };
  shapes['wand-2'] = shapes.sparkles;
  shapes['wand-sparkles'] = shapes.sparkles;
  shapes['share'] = shapes['share-2'];
  shapes['link-2'] = shapes.link;
  window.lucide = {
    createIcons() {
      document.querySelectorAll('i[data-lucide]').forEach((element) => {
        const name = element.dataset.lucide;
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        for (const attribute of element.attributes) svg.setAttribute(attribute.name, attribute.value);
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('fill', 'none');
        svg.setAttribute('stroke', 'currentColor');
        svg.setAttribute('stroke-width', '1.8');
        svg.setAttribute('stroke-linecap', 'round');
        svg.setAttribute('stroke-linejoin', 'round');
        svg.setAttribute('aria-hidden', 'true');
        svg.setAttribute('focusable', 'false');
        svg.innerHTML = shapes[name] || shapes.star;
        element.replaceWith(svg);
      });
    },
  };
})();
