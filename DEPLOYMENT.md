# Deployment & operations guide

## 1. Local setup

1. Copy `.env.example` to `.env` (already present locally).
2. Set `APP_ENV=local`, `APP_DEBUG=true` only while developing, `APP_URL` to your local path.
3. Default `DB_DRIVER=sqlite` uses `data/app.sqlite`. The folder `data/` must be writable by PHP.
4. Visit `/setup` once to create the first admin account, then sign in.

No Composer, npm, or build step is required.

## 2. Production checklist

- `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`.
- `SESSION_SECURE=true` and `SESSION_SAMESITE=Lax` (or `Strict`).
- Set `APP_URL` to the real HTTPS URL.
- Cloudflare Turnstile: create a widget at Cloudflare, put the site key and secret key in `TURNSTILE_SITE_KEY` / `TURNSTILE_SECRET_KEY`. Leave both blank to disable captcha.
- Keep `.env` private. Prefer placing it **outside the document root** and pointing `APP_ENV_FILE` at it; otherwise `.htaccess` denies dotfiles.
- Back up `data/app.sqlite` (SQLite) or the MySQL database **and** `assets/uploads/` together.
- Remove or protect any leftover test accounts and test sales before launch.
- Verify `/setup` cannot create another admin once one exists (it redirects to login).

## 3. Database choice

| Driver | When to use | Notes |
|---|---|---|
| `sqlite` (default) | Local, small single-instance sites | Zero setup; file at `DB_PATH`; needs writable directory. |
| `mysql` | Shared hosting, multi-instance, serious production | Import `database/mysql.sql` once, then set `DB_*`. Schema auto-init is skipped for MySQL. |

Uploads are stored on disk, not in the database. On serverless/container platforms the filesystem is ephemeral, so uploads need external object storage or they will disappear on redeploy.

## 4. Where to deploy this PHP + database app

| Option | Fits this app? | Reality check |
|---|---|---|
| **InfinityFree** | Yes, easiest free shared hosting | A 2026 provider-by-provider catalogue reports PHP 8.3, MySQL/MariaDB, 5 GB, free SSL on own domain, no card and no forced ads. Good staging choice; no uptime guarantee. https://www.infinityfree.com/ |
| **ByetHost** | Yes, close alternative | Same source reports 5 GB NVMe, PHP 8.3, MySQL/MariaDB, no card; free SSL is on their subdomain, own-domain SSL you install yourself. https://byet.host/free-hosting |
| **Cloudflare Pages** | No, not for this PHP app | Cloudflare Pages is for static sites and framework/Worker builds. Use Cloudflare for DNS, WAF, HTTPS and Turnstile, while PHP runs elsewhere. https://www.cloudflare.com/en-gb/developer-platform/products/pages/ |
| **Vercel** | Possible, but not "upload and go" | Vercel documents PHP through **container-backed functions** (FrankenPHP + Caddy) with `Dockerfile.vercel`, `Caddyfile`, `vercel.json`, external database, and external storage for uploads. Free-tier suitability was not verified. https://vercel.com/kb/guide/laravel-php-with-docker |
| **Oracle Cloud free ARM VPS** | Yes, best free "real server" | Enough to self-host PHP + DB with a control panel; more setup work than shared hosting. https://github.com/build996/actually-free-hosting |
| Static hosts (GitHub/Neocities) | No | No server-side PHP or database. |

Source for the shared-hosting comparison: https://klymentiev.com/blog/free-hosting-list (read 11 Sep 2026). Offers and free tiers change — re-check the provider's own page before committing.

**Recommendation:** stay on InfinityFree/ByetHost while the system is small and free; put Cloudflare in front for DNS/WAF and Turnstile. Move to a paid host or a small VPS when the system handles real money, because free hosts publish no uptime guarantee.

### InfinityFree-specific notes
- **Not serverless.** It is classic shared PHP hosting: upload files with FTP to `htdocs/` (not `public_html/`), and open the site in a browser. There is no build, Node, Docker, or Composer step for this app.
- Upload the project files into `htdocs/` (either directly or in a subfolder such as `htdocs/lite/`). The root `.htaccess` denies `.env`, dotfiles, `src/`, `templates/`, `data/`, and `database/`; verify `yoursite/.env` returns **403** after upload.
- **Use MySQL.** Create the database in the control panel, import `database/mysql.sql` via phpMyAdmin, then set `.env`: `DB_DRIVER=mysql`, `DB_HOST=sqlXXX.infinityfree.com` (**not** `localhost`), `DB_NAME=if0_XXXXXX_...`, `DB_USER`, `DB_PASS`. The host/name/user are shown in the MySQL Databases page.
- Skip `data/app.sqlite` when using MySQL. Set `assets/uploads/` writable (chmod 755/777 via File Manager) so product photos can be saved.
- Set `APP_ENV=production`, `APP_DEBUG=false`, `FORCE_HTTPS=true`, `SESSION_SECURE=true`, and the real `APP_URL`. Free SSL is available in the control panel.
- Known free-tier limits: daily visit/traffic caps, file-size/file-count caps, no PHP `mail()`, no SSH, and no uptime guarantee. Fine for staging/MVP, not for a money-handling production system without backup planning.

## 5. Security audit — implemented

| ID | Severity | Confidence | Location | Evidence | Impact / mitigation | Verification |
|---|---|---|---|---|---|---|
| A1 | High | Confirmed | `src/bootstrap.php` | `.env` was a flat file, now denies dotfiles via `.htaccess` and can live outside docroot via `APP_ENV_FILE` | Prevents secret disclosure | Lint + HTTP check |
| A2 | High | Confirmed | `src/bootstrap.php`, `.env` | Debug output disabled unless `APP_DEBUG=true`; security headers added | Reduces info leak / clickjacking | HTTP header check |
| A3 | High | Confirmed | `src/Auth.php`, `login.php`, `AdminController@login` | Optional Turnstile server-side verify + session-based login throttling | Reduces automated login attempts | Lint; captcha path needs live keys to fully verify |
| A4 | Medium | Confirmed | `src/Router.php`, `Http.php` | CSRF on every POST, `httponly` + `SameSite` + optional `Secure` cookies | CSRF/session hardening | Existing E2E + browser checks |
| A5 | Medium | Confirmed | `src/Database.php` | PDO prepared statements throughout; centavo integers for money | SQL injection / float errors | Code inspection |
| A6 | Medium | Confirmed | `data/`, `src/`, `templates/`, `database/` | Direct web access denied by `.htaccess` | No direct source/DB download | HTTP check |

**Residual risks / not assessed:** no penetration test, no dependency scanner (none used), no independent code review, no backup automation, no uptime guarantee on free hosting. File uploads are not virus-scanned. A full production sign-off needs live HTTPS, real Turnstile keys, and a restore drill.

Verdict: **Ready for staging; production-ready after the checklist in section 2 is completed.**
