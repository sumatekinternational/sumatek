# Manpower SaaS — Mobile App (Flutter)

Bilingual (Arabic RTL / English LTR) Android + iOS app for field staff and the
agency desk, wired to the same v1 API as the web admin.

## What's here

- **Auth** — token login, session persistence, logout.
- **Dashboard** — agency KPIs.
- **Eligibility / Block Check** (flagship) — enter a Civil ID → instant
  cross-agency result (Clear / Caution / Blocked) with masked ID + disclaimer,
  plus a **Scan ID** button (camera/MRZ OCR is the wired next step, §5).
- **Sponsors** and **Workers** lists (pull-to-refresh).
- Arabic-default UI that flips fully right-to-left.

## Run it

This folder contains the Dart source. The native Android/iOS shells are
generated (they're git-ignored), so first time:

```bash
cd mobile
flutter create .          # generates android/ ios/ platform folders
flutter pub get
```

Point the app at your running API. On the Android emulator, `10.0.2.2` is your
PC's `localhost`:

```bash
# Android emulator (API on your PC:8000)
flutter run --dart-define=API_BASE_URL=http://10.0.2.2:8000

# Real phone on the same Wi-Fi (use your PC's LAN IP)
flutter run --dart-define=API_BASE_URL=http://192.168.1.186:8000
```

Login: `a.admin@agency.test` / `password123`. Eligibility demo Civil ID
`291010112345` → **BLOCKED**.

## Not yet wired (needs external inputs)

- Camera MRZ/Civil-ID scanning (add `google_mlkit_text_recognition` +
  camera permissions; parse MRZ and call `/identity/read`).
- Push notifications (Firebase project → FCM/APNs).
- App Store / Play Store release (Apple + Google developer accounts).
