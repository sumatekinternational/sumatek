# Delivery Plan & Status — Kuwait Manpower SaaS

Honest snapshot of what exists, what remains, and what depends on inputs only
the business owner can provide. This is a large platform (cloud backend + web
admin + native mobile apps + regulated integrations); it is delivered in
phases, not one drop.

---

## 1. What is DONE and verified

| Area | State |
|------|-------|
| Multi-tenant Laravel 11 API (tenant isolation, RBAC, subscriptions) | ✅ Running, tested |
| Flagship cross-agency Sponsor Block Check + governance/disputes | ✅ Running, tested |
| Identity-read abstraction + ICAO MRZ parser (fake/OCR drivers) | ✅ Running, tested |
| Sponsors, Workers/CV bank (PII encryption, dedupe) | ✅ Running, tested |
| Contracts + PAM warranty + Tanazul transfer | ✅ Running, tested |
| Visa 20 pipeline (stages, SLA, doc gating, SADAD) | ✅ Running, tested |
| Invoicing + payments (manual gateway) + WPS awareness | ✅ Running, tested |
| Legacy CSV import (preview, dedupe, rollback) | ✅ Running, tested |
| Notifications + in-app inbox + WhatsApp channel | ✅ Running (delivery stubbed) |
| Reporting + Arabic-safe CSV export | ✅ Running, tested |
| Bilingual AR(RTL)/EN, Kuwait TZ/currency | ✅ |
| **Web admin** (login, dashboard, eligibility, sponsors, workers) | ✅ Running |
| Automated test suite | ✅ 11 tests passing |

## 2. In progress / partial

| Area | State |
|------|-------|
| **Mobile app (Flutter, Android + iOS)** | 🟡 Foundation in `mobile/` (auth, eligibility, lists) |
| Web admin — remaining modules (contracts, visa, billing, imports, disputes) | 🟡 API ready; UI pending |

## 3. Not started / integration seams (need external inputs)

These are wired as replaceable "seams" — the code path exists and returns a
clear "not configured" until the credential/approval below is supplied.

| Seam | Blocked on (your input) |
|------|------------------------|
| PACI Civil-ID API / Hawyti consent | Government data-sharing contract & credentials |
| Smart-card reader | PACI approval + desk hardware |
| KNET / MyFatoorah / Tap payments | Payment-merchant account + API keys |
| WhatsApp / SMS / push (FCM/APNs) | WhatsApp Business API token, Firebase project |
| Arabic PDF (contracts/reports) | none — engineering only (mpdf/phpspreadsheet) |
| Mobile app store release | Apple Developer + Google Play accounts |
| Block-registry go-live | **Legal sign-off** on Civil-ID storage & block data |

## 4. What I can finish here vs. what I cannot

**Can build/verify in this environment:** all backend code, the web admin, the
Flutter source, PDF generation, and any seam for which you provide test
credentials.

**Cannot do from here:** obtain PACI/government approval; open payment-merchant
or Apple/Google developer accounts; run phone emulators or publish to app
stores; provide legal sign-off. These are business/legal steps.

## 5. Suggested sequence to production

1. **Finish the web admin** so every module is usable in a browser (fastest way
   to a demoable, sellable product).
2. **Flutter mobile app** to feature parity with the web (field staff + desk).
3. **Wire integrations as credentials arrive** — payments first (revenue), then
   identity (PACI/Hawyti), then messaging.
4. **PDF/Arabic documents** for contracts and reports.
5. **Hardening**: security review, load test, backups/PITR, WAF, CDN, deploy to
   a Kuwait-region host.
6. **Legal + PACI approvals in parallel** (longest lead time — start now).

## 6. Open business inputs (blocking items, start these now)

- Subscription tiers & yearly pricing (Basic/Pro/Enterprise).
- Which Civil-ID route to pursue first (PACI API / Hawyti / smart-card / OCR).
- Block-registry governance policy (visible reasons vs. anonymized vs. mediated).
- **Legal counsel sign-off** on Civil-ID storage + the block registry.
- Payment-merchant, Firebase, and Apple/Google developer accounts.
- Target agency count for year 1 (drives infra sizing).
