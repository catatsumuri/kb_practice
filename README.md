# Laravel + React Starter Kit — i18n Edition

A fork of the official [Laravel React starter kit](https://laravel.com/docs/starter-kits) with internationalization (i18n) built in.

## What's different from the official kit

- **Japanese pre-configured** — `ja` locale ships ready to use alongside `en`
- **Translation pipeline** — `lang/vendor-patches/` controls which locales are generated; add a JSON file, run `lang:update`, done
- **Translations in React** — `__()` helper available in all React components via Inertia shared props
- **Session-based locale persistence** — active locale survives page reloads via `LocalizationBySession` middleware

## Requirements

- PHP 8.2+
- Node 20+
- A database supported by Laravel (SQLite works for local dev)

## Adding a locale

Strings that only exist in this fork's React UI (login/2FA/profile screens, etc.) aren't part of the upstream `laravel-lang/starter-kits` package, so they're layered on top of it via `lang/vendor-patches/`:

- `lang/vendor-patches/source/react.json` — the English source strings this fork adds on top of the upstream `react.json`.
- `lang/vendor-patches/locales/{locale}.json` — translations of those strings for one locale. See `lang/vendor-patches/locales/ja.json` for a complete example.

To add a new locale (e.g. `ko`):

1. If your components use custom strings not already listed in `lang/vendor-patches/source/react.json`, add them there first (English key/value pairs).
2. Create `lang/vendor-patches/locales/ko.json` and translate every key from `source/react.json`, following the same structure as `lang/vendor-patches/locales/ja.json`. A key can be left out only if upstream already ships a translation for it in that locale (`ja.json` skips `"Warning"` for this reason) — when in doubt, translate it anyway; the patch just overrides the upstream value.
3. Merge the patches into the vendor package and regenerate the translation files:
   ```bash
   composer patch-vendor
   php artisan lang:update
   ```

`composer patch-vendor` (`scripts/patch-vendor.php`) merges everything under `lang/vendor-patches/source/` and `lang/vendor-patches/locales/` into the matching files inside `vendor/laravel-lang/starter-kits/`, taking your patch values over the upstream ones. `lang:update` then regenerates `lang/{locale}.json` and `lang/{locale}/*.php` for every locale it finds. `patch-vendor` also runs automatically after `composer install`/`composer update`, and `lang:update` runs automatically after `composer update` — but for a newly added locale, run both explicitly so the new locale is generated immediately.

## Installation

```bash
composer create-project your-vendor/react-starter-kit-i18n my-app
cd my-app
composer setup
```

`composer setup` does the following in one shot:

```
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

## Development

```bash
composer dev
```

Starts four processes concurrently:

| Process | Command |
|---------|---------|
| HTTP server | `php artisan serve` |
| Queue worker | `php artisan queue:listen` |
| Log viewer | `php artisan pail` |
| Vite dev server | `npm run dev` |

## Architecture

### Backend packages

| Package | Role |
|---------|------|
| `laravel-lang/common` | Core translation pipeline (`lang:update` command) |
| `laravel-lang/routes` | `LocalizationBySession` middleware |
| `erag/laravel-lang-sync-inertia` | Syncs `lang/{locale}.json` to Inertia shared props as `lang` |
| `laravel/wayfinder` | Type-safe route references on the frontend |

### Frontend

- React 19 + TypeScript + Vite
- [shadcn/ui](https://ui.shadcn.com) + [Radix UI](https://www.radix-ui.com) components
- Tailwind CSS
- `@erag/lang-sync-inertia` — `lang()` / `__()` hook for translations in React components

### How translations reach the frontend

```
lang/ja.json
    ↓  HandleInertiaRequests::share()
Inertia shared prop: { lang: { "Log in": "ログイン", ... } }
    ↓  @erag/lang-sync-inertia
const { __ } = lang()
    ↓
__('Log in')  →  "ログイン"
```

## Code quality

```bash
composer lint          # PHP (Pint)
npm run lint           # JS/TS (ESLint)
npm run types:check    # TypeScript
composer test          # PHPUnit / Pest
composer ci:check      # all of the above
```

## License

MIT
