# Kuwait Manpower SaaS — Backend

Multi-tenant, subscription-based SaaS platform for Kuwait domestic-labour /
manpower recruitment agencies. This repository contains the **Laravel 11 API
backend** — the runnable core that the web admin (Vue/React) and Flutter mobile
apps build on.

> **Scope of this build.** The full product (per the master spec) is a
> multi-quarter program spanning backend + web admin + mobile apps. This commit
> delivers the **Phase-1 backend foundation**: the architecture and the
> genuinely differentiating pieces. See [Status](#status) for what is
> implemented vs. stubbed vs. not yet started.

---

## Status

| Area | State |
|------|-------|
| Multi-tenancy (tenant scope, deny-by-default isolation) | ✅ Implemented |
| RBAC (Spatie, teams = tenants, 8 roles, permission matrix) | ✅ Implemented |
| Subscription control plane (tenants, plans, renew/suspend, lifecycle) | ✅ Implemented |
| Subscription enforcement middleware (active → grace → suspended) | ✅ Implemented |
| **Flagship: cross-agency Sponsor Block Check** | ✅ Implemented |
| Block lifecycle (block → review → unblock, append-only events) | ✅ Implemented |
| Vendor moderation / governance of blocks | ✅ Implemented |
| **Identity auto-read** abstraction + 5 pluggable drivers | ✅ Implemented |
| ICAO 9303 MRZ parser (TD-1/TD-3, checksum validation) | ✅ Implemented |
| Sponsor & Worker modules (CRUD, dedupe, PII encryption) | ✅ Implemented |
| Immutable audit log of sensitive actions | ✅ Implemented |
| Bilingual AR/RTL + EN, Kuwait timezone/currency defaults | ✅ Implemented |
| Contracts + PAM warranty + Tanazul transfer (warranty void) | ✅ Implemented (Phase 2) |
| Visa 20 deployment pipeline (stages, SLA, doc-gated advance) | ✅ Implemented (Phase 2) |
| Invoicing + payments (gateway abstraction, manual functional) | ✅ Implemented (Phase 2) |
| Agency + vendor dashboards | ✅ Implemented (Phase 2) |
| Legacy migration tool (CSV, mapping, dry-run, dedupe, rollback) | ✅ Implemented (Phase 3) |
| Block dispute / governance workflow | ✅ Implemented (Phase 3) |
| Public consented worker catalogue | ✅ Implemented (Phase 3) |
| Notifications + in-app inbox + renewal reminders | ✅ Implemented (Phase 3) |
| Reporting + CSV export (Arabic-safe BOM) | ✅ Implemented (Phase 3) |
| PACI / Hawyti / smart-card identity drivers | 🟡 Integration seams (await gov. approval, §13) |
| KNET / MyFatoorah / SADAD payment gateways | 🟡 Integration seams (await merchant onboarding) |
| WhatsApp / FCM / APNs / SMS delivery | 🟡 WhatsApp channel wired (logs until token set); push/SMS TODO |
| XLSX / PDF report rendering (Arabic) | 🟡 Seam (CSV functional; phpspreadsheet/mpdf TODO) |
| Web admin SPA, Flutter apps | ⬜ Separate workstreams |

---

## Architecture

**Logical multi-tenancy** on a shared database. Every business table carries a
`tenant_id` and is auto-scoped by a global Eloquent scope:

- `App\Support\TenantContext` — request/job-scoped current tenant (singleton).
- `App\Models\Scopes\TenantScope` — applies `where tenant_id = …`, **deny-by-
  default** (returns nothing when no tenant is resolved and not bypassed).
- `App\Models\Concerns\BelongsToTenant` — trait that adds the scope and stamps
  `tenant_id` on create.
- `App\Http\Middleware\ResolveTenant` — pins agency users to their tenant; lets
  vendor control-plane users target a tenant via `X-Tenant`. Also sets the
  Spatie team id so RBAC is tenant-scoped.

**Two planes** (§2):
- *Vendor control plane* (`/api/v1/admin/*`) — not tenant-scoped. Manage
  tenants, plans, renewals, and block-registry moderation.
- *Agency plane* (`/api/v1/*`) — tenant-scoped + subscription-gated.

**PII handling** (§9): Civil ID / passport numbers and addresses are
`encrypted` at rest. A keyed `*_hash` column (`App\Support\Pii::hash`,
HMAC-SHA256 peppered with the app key) enables lookup/dedupe and the
cross-agency registry query without decrypting.

### Flagship — Sponsor Eligibility / Block Check (§4)

- `sponsor_block_registry` is a **central, cross-tenant** table (NOT
  tenant-scoped by design — that visibility is the moat). Each row records
  which agency asserted the block (`blocking_tenant_id`).
- `EligibilityService::check($civilId)` queries the registry across all
  agencies and returns `clear | caution | blocked`, the count of blocking
  agencies, a governance-filtered explanation, and the mandatory legal
  disclaimer.
- Governance is policy-driven via `config/blockregistry.php`
  (`visibility = full | anonymized | vendor_mediated`, mandatory structured
  reason, evidence requirement, review windows). Vendor moderation can
  remove/uphold blocks.

> ⚠️ **Legal.** Blocks are agency assertions, not government determinations.
> Storing Civil IDs and labelling people "blocked" carries Kuwait
> data-protection and defamation exposure — obtain counsel sign-off (§13) and
> keep consent capture + evidence + audit trail on by default.

### Identity auto-read (§5)

Pluggable via `App\Services\Identity\IdentityReaderInterface`, resolved by
`IdentityReaderManager` (Laravel Manager pattern). Drivers:

| Driver | Route | State |
|--------|-------|-------|
| `paci` | PACI API (verified) | Seam — needs data-sharing contract |
| `hawyti` | Mobile-ID consent QR | Seam — needs PACI approval |
| `smartcard` | Chip reader at desk | Validates client-parsed payload |
| `mrz_ocr` | Passport MRZ + OCR (all nationalities) | Real parser w/ checksums |
| `fake` | Dev/demo | Implemented (default) |

Each agency is restricted to its permitted drivers
(`tenants.allowed_identity_drivers`). Reads are audited (`identity_reads`)
without storing the raw image; raw captures are purged on a schedule.

---

## Getting started

```bash
composer install
cp .env.example .env
php artisan key:generate

# Postgres 16 (default) or MySQL 8 — set DB_* in .env, then:
php artisan migrate --seed
php artisan serve
```

Or with Docker: `docker compose up -d` then
`docker compose exec app php artisan migrate --seed`.

### Demo credentials (from `DemoSeeder`)

| Role | Email | Password |
|------|-------|----------|
| Vendor super-admin | `admin@vendor.test` | `password123` |
| Agency A admin | `a.admin@agency.test` | `password123` |
| Agency B admin | `b.admin@agency.test` | `password123` |

The seed has **Agency B block Civil ID `291010112345`**, so an eligibility
check from Agency A returns `blocked` — the flagship flow, end to end.

### Try the flagship check

```bash
TOKEN=$(curl -s localhost:8000/api/v1/auth/login \
  -H 'Accept: application/json' \
  -d 'email=a.admin@agency.test&password=password123' | jq -r .token)

curl -s localhost:8000/api/v1/eligibility/check \
  -H "Authorization: Bearer $TOKEN" -H 'Accept: application/json' \
  -d 'civil_id=291010112345' | jq
```

---

## API surface (v1)

```
POST   /auth/login            POST /auth/logout      GET /auth/me
# Vendor control plane (role: super-admin|vendor-support|vendor-billing)
GET/POST/…  /admin/tenants    POST /admin/tenants/{t}/suspend|renew
GET/POST/…  /admin/plans
GET    /admin/block-moderation  POST /admin/block-moderation/{b}/revoke|uphold
# Agency plane (tenant + subscription gated, permission-checked)
POST   /eligibility/check                 # FLAGSHIP
POST   /identity/read   POST /identity/hawyti/session
GET/POST/… /sponsors     POST /sponsors/{s}/block|unblock
GET/POST/… /workers
```

## Testing

```bash
php artisan test            # or: vendor/bin/phpunit
```

Includes the ICAO MRZ checksum unit test and feature tests proving
cross-agency block visibility and tenant isolation.

## Configuration reference

- `config/multitenancy.php` — tenant column, control-plane roles, header.
- `config/subscription.php` — grace window, reminder cadence, plan tiers.
- `config/identity.php` — default driver, per-driver creds, raw retention.
- `config/blockregistry.php` — **governance policy** (review with counsel).

## Open business inputs (§13)

Pricing tiers; which Civil ID route(s) get approved first; block-registry
visibility policy; legal sign-off on Civil ID storage; year-1 agency count
(infra sizing). These are encoded as config/data, not hard-coded.
```
