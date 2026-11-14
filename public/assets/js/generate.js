/* Generate → review → publish. Guests and signed-in players use the same flow. */
(() => {
  'use strict';
  const form = document.getElementById('gen-form');
  if (!form) return;
  const { csrfToken, showToast, url, icons, reduceMotion } = window.CC;
  const button = document.getElementById('btn-generate');
  const regen = document.getElementById('btn-regen');
  const publishButton = document.getElementById('btn-publish');
  const back = document.getElementById('btn-back-preview');
  const regions = {
    empty: document.getElementById('preview-empty'), loading: document.getElementById('preview-loading'),
    panel: document.getElementById('preview-panel'), error: document.getElementById('preview-error'),
  };
  const actions = document.getElementById('preview-actions');
  const remaining = document.getElementById('gen-remaining');
  const preview = document.getElementById('gen-preview');
  let payload = null;
  let busy = false;
  let publishing = false;
  function stage(name) {
    Object.entries(regions).forEach(([key, element]) => { element.hidden = key !== name; });
    actions.hidden = name !== 'panel';
    preview.setAttribute('aria-busy', String(name === 'loading'));
    button.disabled = busy || publishing;
    regen.disabled = busy || publishing;
    publishButton.disabled = busy || publishing;
    document.getElementById('workflow-preview').classList.toggle('is-active', name === 'panel');
    document.getElementById('workflow-share').classList.toggle('is-active', publishing);
    button.innerHTML = name === 'loading' ? '<span class="spinner"></span> Generating…' : `<i data-lucide="sparkles" width="18" height="18"></i> ${escapeHtml(form.dataset.generateLabel)}`;
    icons();
  }
  async function request(path, data) {
    const response = await fetch(url(path), {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrfToken, Accept: 'application/json' },
      body: JSON.stringify(data),
    });
    const result = await response.json().catch(() => ({}));
    if (!response.ok) throw new Error(result.error || (response.status === 419 ? 'Your page has expired. Refresh and try again.' : `Something went wrong (${response.status}). Please try again.`));
    return result;
  }
  function params() {
    const data = new FormData(form);
    return Object.fromEntries(['category', 'topic', 'difficulty', 'question_count', 'language', 'is_public'].map((key) => [key, String(data.get(key) ?? '').trim()]));
  }
  function bringIntoView(element) {
    element.scrollIntoView({ behavior: reduceMotion.matches ? 'instant' : 'smooth', block: 'start' });
  }
  async function generate() {
    if (busy || publishing || !form.reportValidity()) return;
    const settings = params();
    if (!settings.topic) { document.getElementById('topic').focus(); showToast('Give your challenge a topic or name.', 'error'); return; }
    busy = true;
    document.getElementById('generation-detail').textContent = `Putting ${settings.question_count} questions together${form.dataset.demo === 'true' ? ' from the demo question bank' : ''}.`;
    stage('loading');
    if (matchMedia('(max-width: 860px)').matches) bringIntoView(preview);
    try {
      const data = await request('/api/generate', settings);
      if (!data.challenge?.questions?.length) throw new Error('No questions were returned. Please try again.');
      payload = data.challenge;
      payload.is_public = settings.is_public === '1';
      if (typeof data.remaining === 'number') remaining.textContent = `${data.remaining} ${data.remaining === 1 ? 'quiz' : 'quizzes'} left to generate this hour.`;
      render(payload, data.demo);
      busy = false;
      stage('panel');
      const heading = document.getElementById('preview-title');
      heading.focus({ preventScroll: true });
      showToast('Your quiz is ready. Review it, then publish.');
    } catch (error) {
      busy = false;
      stage('error');
      regions.error.querySelector('.alert').textContent = error.message;
      back.hidden = !payload;
      regions.error.focus({ preventScroll: true });
      bringIntoView(preview);
    }
  }
  function render(data, demo) {
    const category = [...document.getElementById('category').options].find((option) => option.value === data.category)?.text || data.category;
    document.getElementById('preview-chips').innerHTML = `<span class="chip chip--sky">${escapeHtml(category)}</span><span class="chip chip--lavender">${escapeHtml(data.difficulty)}</span><span class="chip chip--outline">${data.questions.length} questions</span><span class="chip ${demo ? 'chip--sunburst' : 'chip--mint'}">${demo ? 'Demo question bank' : 'AI generated'}</span>`;
    document.getElementById('preview-title').textContent = data.title;
    document.getElementById('preview-desc').textContent = data.description || '';
    document.getElementById('preview-questions').innerHTML = data.questions.map((q, i) => `<div class="preview-q"><h4 class="preview-q__text">${i + 1}. ${escapeHtml(q.question)}</h4>${q.options.map((option, index) => `<div class="preview-opt ${index === q.correct_index ? 'is-correct' : ''}">${String.fromCharCode(65 + index)}. ${escapeHtml(option)}${index === q.correct_index ? ' ✓ Correct answer' : ''}</div>`).join('')}${q.explanation ? `<p class="caption muted mt-8">${escapeHtml(q.explanation)}</p>` : ''}</div>`).join('');
  }
  async function publish() {
    if (!payload || busy || publishing) return;
    publishing = true;
    stage('panel');
    form.querySelectorAll('input, select, [data-topic]').forEach((element) => { element.disabled = true; });
    publishButton.innerHTML = '<span class="spinner"></span> Publishing…';
    try {
      const result = await request('/api/challenges', { ...payload, is_public: document.getElementById('is_public').value === '1' });
      if (!result.url) throw new Error('The share link could not be created. Please try again.');
      window.location.assign(result.url);
    } catch (error) {
      publishing = false;
      form.querySelectorAll('input, select, [data-topic]').forEach((element) => { element.disabled = false; });
      stage('panel');
      publishButton.innerHTML = '<i data-lucide="rocket" width="15" height="15"></i> Publish &amp; get link';
      icons();
      showToast(error.message, 'error');
    }
  }
  function escapeHtml(value) {
    return String(value ?? '').replaceAll('&', '&amp;').replaceAll('<', '&lt;').replaceAll('>', '&gt;').replaceAll('"', '&quot;');
  }
  form.addEventListener('submit', (event) => { event.preventDefault(); generate(); });
  regen.addEventListener('click', generate);
  publishButton.addEventListener('click', publish);
  back.addEventListener('click', () => stage('panel'));
  form.querySelectorAll('[data-topic]').forEach((idea) => idea.addEventListener('click', () => {
    document.getElementById('topic').value = idea.dataset.topic;
    document.getElementById('category').value = idea.dataset.category;
    document.getElementById('topic').focus();
  }));
  form.addEventListener('keydown', (event) => {
    if ((event.ctrlKey || event.metaKey) && event.key === 'Enter') { event.preventDefault(); generate(); }
  });
})();
