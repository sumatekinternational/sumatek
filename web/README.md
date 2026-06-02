# Web Admin (SPA)

Bilingual (Arabic RTL default / English LTR) admin front-end for the Kuwait
Manpower SaaS API. Vue 3 + Vite + Tailwind + Pinia + vue-i18n, Sanctum
token auth.

## Run

```bash
cd web
npm install
npm run dev      # http://localhost:3000 — proxies /api -> http://localhost:8000
```

Run the Laravel API alongside it (`php artisan serve` from the repo root).
Sign in with a seeded demo account, e.g. `a.admin@agency.test` / `password123`.

```bash
npm run build    # production bundle in web/dist
```

## What's here

- **Auth**: token login, `/auth/me` restore, route guard, logout.
- **i18n / RTL**: Arabic default; runtime language toggle flips `dir`/`lang`.
- **Screens**: Dashboard (KPIs), **Eligibility / Block Check** (the flagship —
  status-coloured result with prompt + masked Civil ID + disclaimer), Sponsors,
  Workers. Nav items are gated by the user's permissions.

## Scope

This is the foundational slice. Contracts, visa pipeline, billing, imports,
disputes and the public catalogue are exposed by the API and can be added as
further screens following the same page/store/i18n pattern.
