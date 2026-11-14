/* ChallengeChain — quiz player (start → answer → submit → result) */
(() => {
  'use strict';

  const root = document.getElementById('play-root');
  if (!root) return;

  const slug = root.dataset.slug;
  const csrf = window.CC?.csrfToken || document.querySelector('meta[name="csrf-token"]')?.content || '';

  const stepStart = document.getElementById('step-start');
  const stepQuiz = document.getElementById('step-quiz');
  const stepLoading = document.getElementById('step-loading');
  const btnStart = document.getElementById('btn-start');
  const btnPrev = document.getElementById('btn-prev');
  const btnNext = document.getElementById('btn-next');
  const btnSubmit = document.getElementById('btn-submit');
  const quizContent = document.getElementById('quiz-content');
  const qDots = document.getElementById('q-dots');
  const progressBar = document.getElementById('play-progress');
  const timerEl = document.getElementById('play-timer');

  let attemptId = null;
  let questions = [];
  let current = 0;
  const answers = {}; // question_id -> option_id
  let startedAt = null;
  let timerHandle = null;
  let submitting = false;

  function show(el) {
    [stepStart, stepQuiz, stepLoading].forEach((s) => s.classList.add('hidden'));
    el.classList.remove('hidden');
  }

  async function api(path, body) {
    const res = await fetch(window.CC.url(path), {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-Token': csrf,
        Accept: 'application/json',
      },
      body: JSON.stringify(body || {}),
      credentials: 'same-origin',
    });
    let data = null;
    try { data = await res.json(); } catch { /* noop */ }
    if (!res.ok) {
      const msg = (data && data.error) || `Request failed (${res.status})`;
      throw new Error(msg);
    }
    return data;
  }

  // ---------- Start ----------
  btnStart?.addEventListener('click', async () => {
    const nameInput = document.getElementById('participant-name');
    const name = nameInput ? nameInput.value.trim() : '';
    btnStart.disabled = true;
    btnStart.innerHTML = '<span class="spinner"></span> Loading…';
    try {
      const data = await api(`/api/challenges/${encodeURIComponent(slug)}/start`, {
        participant_name: name,
      });
      attemptId = data.attempt_id;
      questions = data.questions || [];
      if (!questions.length) throw new Error('This challenge has no questions.');
      startedAt = Date.now();
      show(stepQuiz);
      renderDots();
      renderQuestion(0);
      startTimer();
      document.getElementById('play-title').textContent = data.challenge?.title || root.dataset.title;
    } catch (err) {
      window.CC?.showToast(err.message, 'error');
      btnStart.disabled = false;
      btnStart.innerHTML = '<i data-lucide="play" width="18" height="18"></i> Start challenge';
      window.lucide?.createIcons();
    }
  });

  // ---------- Timer ----------
  function startTimer() {
    timerEl.classList.remove('hidden');
    const tick = () => {
      const elapsed = Math.floor((Date.now() - startedAt) / 1000);
      const m = Math.floor(elapsed / 60);
      const s = String(elapsed % 60).padStart(2, '0');
      timerEl.textContent = `${m}:${s}`;
    };
    tick();
    timerHandle = setInterval(tick, 1000);
  }

  // ---------- Rendering ----------
  function renderDots() {
    qDots.innerHTML = '';
    questions.forEach((q, i) => {
      const b = document.createElement('button');
      b.type = 'button';
      b.className = 'q-dot';
      b.textContent = String(i + 1);
      b.setAttribute('aria-label', `Question ${i + 1}${answers[q.id] != null ? ', answered' : ', unanswered'}`);
      if (i === current) b.setAttribute('aria-current', 'step');
      if (answers[q.id] != null) b.classList.add('is-answered');
      if (i === current) b.classList.add('is-current');
      b.addEventListener('click', () => renderQuestion(i));
      qDots.appendChild(b);
    });
  }

  function renderQuestion(index) {
    current = Math.max(0, Math.min(index, questions.length - 1));
    const q = questions[current];
    const selected = answers[q.id];

    quizContent.innerHTML = `
      <div class="caption muted mb-16">Question ${current + 1} of ${questions.length}</div>
      <h2 class="play-question" id="current-question" tabindex="-1">${escapeHtml(q.question)}</h2>
      <div class="option-list" role="radiogroup" aria-labelledby="current-question">
        ${q.options.map((opt, i) => `
          <label class="option ${selected === opt.id ? 'is-selected' : ''}" data-oid="${opt.id}">
            <input type="radio" name="q_${q.id}" value="${opt.id}" ${selected === opt.id ? 'checked' : ''}>
            <span><strong>${String.fromCharCode(65 + i)}.</strong> ${escapeHtml(opt.text)}</span>
          </label>
        `).join('')}
      </div>
    `;

    quizContent.querySelectorAll('.option').forEach((label) => {
      label.querySelector('input').addEventListener('change', () => {
        const oid = Number(label.dataset.oid);
        answers[q.id] = oid;
        quizContent.querySelectorAll('.option').forEach((l) => l.classList.remove('is-selected'));
        label.classList.add('is-selected');
        label.querySelector('input').checked = true;
        renderDots();
        updateProgress();
      });
    });

    btnPrev.disabled = current === 0;
    btnPrev.classList.toggle('hidden', questions.length <= 1);
    const isLast = current === questions.length - 1;
    btnNext.classList.toggle('hidden', isLast);
    btnSubmit.classList.toggle('hidden', !isLast);

    renderDots();
    updateProgress();
    document.getElementById('current-question').focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: window.CC.reduceMotion.matches ? 'instant' : 'smooth' });
  }

  function updateProgress() {
    const answered = Object.keys(answers).length;
    const pct = questions.length ? (answered / questions.length) * 100 : 0;
    progressBar.style.width = `${pct}%`;
    progressBar.parentElement.setAttribute('aria-valuenow', String(answered));
    document.getElementById('quiz-progress-label').textContent = `${answered} of ${questions.length} answered`;
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;');
  }

  btnPrev?.addEventListener('click', () => renderQuestion(current - 1));
  btnNext?.addEventListener('click', () => renderQuestion(current + 1));
  document.getElementById('participant-name')?.addEventListener('keydown', (event) => {
    if (event.key === 'Enter') { event.preventDefault(); btnStart.click(); }
  });

  // ---------- Submit ----------
  btnSubmit?.addEventListener('click', async () => {
    if (submitting) return;
    const unanswered = questions.filter((q) => answers[q.id] == null);
    if (unanswered.length > 0) {
      const ok = confirm(`You have ${unanswered.length} unanswered question(s). Submit anyway?`);
      if (!ok) {
        const firstIdx = questions.findIndex((q) => answers[q.id] == null);
        renderQuestion(firstIdx);
        return;
      }
    }
    submitting = true;
    btnSubmit.disabled = true;
    show(stepLoading);
    if (timerHandle) clearInterval(timerHandle);

    try {
      const data = await api(`/api/attempts/${encodeURIComponent(attemptId)}/submit`, {
        answers,
      });
      window.location.href = data.result_url;
    } catch (err) {
      submitting = false;
      show(stepQuiz);
      btnSubmit.disabled = false;
      window.CC?.showToast(err.message, 'error');
      startTimer();
    }
  });
})();
