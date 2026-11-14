# ⚡ ChallengeChain

**AI-powered viral challenge platform** — built for HackOnVibe November 2026.

**Live demo:** [cc.tendry.mg](http://cc.tendry.mg) · [Start a challenge](http://cc.tendry.mg/create)

Create an AI-generated challenge → share one link → friends play instantly → scores & leaderboards → they share their result → new users join. The viral loop, end to end.

> Stack: **PHP 8.3+ (native MVC) · SQLite 3 (PDO) · Vanilla JS (Fetch API) · No frameworks**

---

## Features

| Priority | Feature |
|----------|---------|
| P1 | Registration / login / logout / password reset (secure tokens) |
| P1 | OpenRouter free AI challenge generator (OpenAI-compatible REST via PHP cURL) + **demo mode** without a key |
| P1 | Challenge engine: start, answer, server-side scoring, retry, attempt history |
| P1 | Unique public URLs `/challenge/{slug}` + shareable `/result/{publicId}` pages |
| P2 | Leaderboards (score, speed tie-break, best-per-player), personal rank |
| P2 | Dashboard: created / completed / participants / views / referral visits / activity |
| P2 | Explore: search, categories, trending / recent / popular sorts |
| P2 | Open Graph metadata, WhatsApp / Facebook / X / native share, copy-link |
| P2 | Referral tracking (`?ref=CODE`) + referral analytics |
| P3 | XP, badges, achievements |

**Security:** PDO prepared statements everywhere · CSRF tokens (forms + `X-CSRF-Token`) · output escaping `e()` · secure session cookies (`HttpOnly`, `SameSite=Lax`, `Secure` under HTTPS) · `session_regenerate_id()` on login · login rate limiting · AI generation rate limiting · server-side score calculation · duplicate-submission protection (409) · answers **never** sent to the browser before submission · safe error pages (no internal paths/credentials exposed).

---

## Requirements

- PHP **8.3+** with `pdo_sqlite`, `sqlite3`, `curl`, `mbstring`, `openssl`
- Apache with `mod_rewrite` (or Nginx equivalent)
- HTTPS in production
- Writable `storage/` directory

---

## Installation

```bash
# 1. Clone / upload the project, then:
composer install          # generates the autoloader (no external packages needed)

# 2. Configure environment
cp .env.example .env
# Edit .env: APP_URL, AI_API_KEY (optional), etc.

# 3. Create the SQLite database (outside public/)
php scripts/migrate.php

# 4. Optional: demo user + demo challenges + achievements
php scripts/seed.php

# 5. Run locally
composer serve            # → http://localhost:8000
```

### Demo credentials (after seeding)

| Field | Value |
|-------|-------|
| Email | `demo@challengechain.dev` |
| Password | `demo1234` |

4 public demo challenges are seeded (clearly labelled **Demo**).

---

## Configuration (`.env`)

| Key | Default | Description |
|-----|---------|-------------|
| `APP_URL` | `http://localhost:8000` | Public base URL — used for share links & OG tags (no trailing slash) |
| `DB_PATH` | `storage/database.sqlite` | SQLite file (must stay outside the web root) |
| `AI_API_KEY` | *(empty)* | OpenRouter API key, stored server-side in `.env`. **Empty = DEMO MODE** (labelled in UI) |
| `AI_BASE_URL` | `https://openrouter.ai/api/v1` | OpenRouter endpoint (OpenAI-compatible) |
| `AI_MODEL` | `openrouter/free` | Routes to free models only; no paid fallback configured |
| `LOGIN_MAX_ATTEMPTS` | `5` | Per 15 min window, per IP+email |
| `AI_MAX_PER_HOUR` | `6` | Per user |
| `MAIL_ENABLED` | `false` | Password-reset email delivery (falls back to debug link) |

### Demo mode

With no `AI_API_KEY`, generation uses a built-in question bank (8 categories × ~12 questions) and every generated challenge is flagged **Demo mode** in the UI. Add a key to enable real AI generation — the JSON is validated (`AIService::validatePayload`) before any DB insert, with one stricter retry on invalid output.

---

## Commands

```bash
php scripts/migrate.php   # apply migrations (idempotent)
php scripts/seed.php      # demo data (idempotent)
php tests/run.php         # 127 checks: auth, AI, scoring, URLs, referrals, rate limits, XSS, SQLite
php scripts/check-ai.php  # generate and validate 3 questions using the configured AI (no publishing)
composer serve            # PHP built-in server on :8000
```

---

## Project structure

```text
challengechain/
├── app/
│   ├── Controllers/       # Home, Auth, Explore, Challenge, Create, Dashboard, Profile, Api
│   ├── Models/            # User, Challenge, Question, Attempt, Achievement, PasswordReset
│   ├── Services/          # AIService, ChallengeService, AttemptService, ScoringService,
│   │                      # ReferralService, GamificationService, DemoQuestionBank
│   ├── Core/              # Database (PDO+WAL+FK), Router, Auth, Csrf, View, RateLimiter
│   ├── Views/             # layouts (main/auth/bare) + templates + partials
│   ├── helpers.php        # env(), config(), e(), url(), csrf(), flash…
│   └── auth_helpers.php
├── config/config.php      # reads .env
├── database/migrations/   # 001_initial_schema (11 tables, FKs, indexes, uniques)
├── database/seeds/
├── public/                # front controller index.php, .htaccess, assets/
├── routes/web.php         # all web + API routes
├── scripts/               # migrate.php, seed.php
├── storage/               # database.sqlite + logs/ (never web-accessible)
├── tests/run.php
├── .env.example
├── bootstrap.php
└── composer.json
```

---

## Production deployment

The interactive app uses PHP and SQLite and is hosted on a PHP-capable VPS. Cloudflare Pages can publish a static page from this repository, but it cannot execute `public/index.php`. Judges should use the live demo URL above.

### Docker (optional)

The Docker setup runs PHP 8.4 with Apache and stores SQLite data in a named volume. It is independent of the existing VPS installation.

```bash
cp .env.example .env
docker compose up --build -d
docker compose ps
```

Open `http://localhost:8080`. The container applies database migrations when it starts. Set `AI_API_KEY` in the untracked `.env` file to use live AI; without it, the app uses demo mode. `docker compose down` stops the app while keeping the database volume.

For a reverse proxy or another port, set `DOCKER_APP_URL` to the public URL and `CHALLENGECHAIN_PORT` to the local listening port before starting Compose. The Compose port binds to `127.0.0.1` so a reverse proxy on the host can serve it.

### Manual PHP deployment

1. Upload the project **above** the web root; point the vhost document root at `public/`.
2. `composer install --no-dev` and set `APP_ENV=production`, `APP_DEBUG=false` in `.env`.
3. Set `APP_URL=https://your-domain.com` (required for correct share/OG links).
4. Run `php scripts/migrate.php` (and `seed.php` only if you want demo data).
5. Ensure `storage/` is writable by the PHP user and **not** served by the web server.
6. Enforce HTTPS + HSTS at the server level (session cookies are marked `Secure` automatically).
7. Apache: `mod_rewrite` + `AllowOverride All` (rules are in `public/.htaccess`).
   Nginx equivalent:

```nginx
root /var/www/challengechain/public;
index index.php;
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
location ~ \.php$ {
    fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
}
location ~ /storage { deny all; }
```

---

## Customer journey (implemented end-to-end)

1. Visit `/` → explore public challenges at `/explore`
2. Create a challenge at `/create` or play as a guest; registration is optional
3. Generate a challenge at `/create` (category · topic · difficulty · count · language)
4. Publish → unique link `/challenge/{slug}`
5. Share: copy link, native share, WhatsApp, Facebook, X (+ OG previews)
6. Friend opens the link (no account required)
7. Plays at `/challenge/{slug}/play` (name prompt for guests)
8. Score calculated **server-side** (100 pts/correct, +25 perfect bonus, time tie-break)
9. Views the leaderboard at `/challenge/{slug}/leaderboard`
10. Shares their result at `/result/{publicId}` → new users arrive → loop repeats

---

## Testing

```bash
php tests/run.php
```

Covers: environment, all 11 tables + pragmas, registration/login (`password_hash`/`password_verify`), demo AI generation, invalid AI payload rejection, challenge creation + slug uniqueness, **no-answer-leak before submission**, perfect/partial/zero scoring, duplicate submission (409), foreign-attempt authorization (403), leaderboard ordering, public URLs, share/referral tracking, rate limiting, router matching, XSS escaping, transaction rollback + FK enforcement, seed data, gamification.

Manual E2E verified: register → dashboard → generate → publish → public page → guest start → submit → result → leaderboard → duplicate 409 → missing CSRF 419 → explore search → profile → share counter → referral link → demo login.

---

## License

MIT — HackOnVibe November 2026.
