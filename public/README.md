# FCMS — Public Directory

This package adapts the `public/` architecture of the EDMS reference repository to the current PHP version of **Fitness-Club-Management-System (FCMS)**.

## What's included

```text
public/
├── .htaccess
├── index.php                    # front controller / allow-listed router
├── favicon.svg
├── README.md
├── assets/
│   ├── app.js                   # compatibility loader for current config.php
│   ├── style.css                # compatibility stylesheet entrypoint
│   ├── css/
│   │   └── app.css              # FCMS design tokens + responsive UI styles
│   ├── js/
│   │   └── app.js               # progressive enhancements
│   └── images/
│       └── fitness-mark.svg     # editable vector brand mark
└── static/
    ├── dashboard-preview.html   # visual admin dashboard sample
    ├── schedule-preview.html    # weekly schedule sample
    └── client-portal-preview.html
```

## Important compatibility decision

The current FCMS repository is not yet organized as a full MVC application. Its live PHP pages (`index.php`, `login.php`, `register.php`, `admin.php`, `schedule.php`, `account.php`, `trainer.php`, and `logout.php`) and `config.php` currently live in the repository root. Those files are **not moved or rewritten** by this package.

The new `public/index.php` maps only known page names to those existing root-level PHP files. It also calls `chdir($appRoot)` before including them, so existing statements such as `require 'config.php'` continue to work.

The existing `config.php` outputs these paths:

- `assets/style.css`
- `assets/app.js`

Therefore this package includes two compatibility files under `public/assets/`. They load the new, organized files in `assets/css/app.css` and `assets/js/app.js` without requiring changes to `config.php`.

## Installation — recommended layout

1. Copy this `public/` directory into the root of the FCMS repository.
2. Configure the web server's **document root** to the `public/` directory.
3. Keep the existing PHP application files and `config.php` one directory above `public/`.
4. Enable Apache `mod_rewrite` and allow `.htaccess` overrides (`AllowOverride All`). The included `.htaccess` routes legacy PHP URLs through the front controller while serving real assets directly.
5. Check the database connection in the existing root `config.php` and set the correct credentials for the target hosting environment. This package intentionally does not change database credentials or business logic.
6. Test `/`, `/login.php`, `/register.php`, `/schedule.php`, `/account.php`, `/trainer.php`, and `/admin.php` after deployment.

### Routes supported by the front controller

Both clean and legacy URLs are supported:

| Clean URL | Existing root PHP page |
|---|---|
| `/` or `/home` | `index.php` |
| `/login` or `/login.php` | `login.php` |
| `/register` or `/register.php` | `register.php` |
| `/logout` or `/logout.php` | `logout.php` |
| `/schedule` or `/schedule.php` | `schedule.php` |
| `/account` or `/account.php` | `account.php` |
| `/trainer` or `/trainer.php` | `trainer.php` |
| `/admin` or `/admin.php` | `admin.php` |

Query strings such as `?w=1` and `?tab=cls` are preserved by Apache's rewrite rule.

## If the hosting provider cannot set `public/` as document root

Do not deploy this package as-is with the repository root still exposed as the web root. In that case, either:

- keep the current root deployment and copy only the styles/scripts from `public/assets/css/app.css` and `public/assets/js/app.js` into the paths used by the existing `config.php`; or
- move the application into a non-public directory and configure a supported public document root.

The recommended arrangement keeps `config.php`, `database.sql`, and the PHP business pages outside the publicly served directory.

## Design / behavior notes

- The CSS keeps existing class names such as `.nav`, `.hero`, `.card`, `.grid2`, `.grid3`, `.grid5`, `.stat`, `.btn`, `.form`, `.tbl`, `.badge`, `.cls`, `.tabs`, `.bar`, `.tl`, `.flash`, `.reveal`, and `.tilt` so current screens remain styled.
- Theme variables retain the project's navy / violet / lime visual identity.
- Responsive styles support narrow screens; `.table-cards` can turn tables into labeled cards when cells use `data-label`.
- Focus indicators, skip-link support, status labels, and reduced-motion behavior are included.
- JavaScript provides optional reveal/count effects, password visibility, confirmation hooks, and opt-in local list filtering. It does not add new server-side features or call invented APIs.
- Static HTML pages in `static/` are **visual previews only**. Their sample numbers and people are fictional demo content; they are not connected to MySQL.

## Security notes

- The front controller uses an allow-list of existing application pages; it does not accept arbitrary PHP file paths from a URL parameter.
- Keep secrets and database credentials outside `public/`.
- The current FCMS `config.php` contains hard-coded local database connection settings. Update those for the production environment before deploying; never commit real production passwords.
- Keep CSRF validation, role checks, and all server-side authorization in the existing PHP application. Front-end styling is not a security boundary.

