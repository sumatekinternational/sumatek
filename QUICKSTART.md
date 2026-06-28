# Quick start — run it on your PC

The easy path uses **SQLite**, so you don't need to install a database server.
You need **PHP 8.3+**, **Composer**, and **Node.js** already installed.

## Windows (no command-line knowledge needed)

1. **Download the code** — on the GitHub branch page, click the green **Code**
   button → **Download ZIP**. Unzip it somewhere like your Desktop.
2. Open the unzipped folder and **double-click `setup.bat`**. It installs
   everything and creates demo data. Wait for "Setup complete!".
3. **Double-click `start-api.bat`** and leave that window open.
4. **Double-click `start-web.bat`** and leave that window open too.
5. Open **http://localhost:3000** in your browser.

Sign in with **`a.admin@agency.test`** / **`password123`**.
Go to **Eligibility Check**, enter Civil ID **`291010112345`** → it shows
**BLOCKED**. Any other number shows **CLEAR**. Use the 🌐 button to switch
Arabic ⇄ English.

> To stop: click each black window and press `Ctrl + C`, or just close them.

## macOS / Linux

```bash
composer install
cp .env.sqlite .env
touch database/database.sqlite
php artisan key:generate
php artisan migrate --seed
php artisan serve            # http://localhost:8000  (leave running)
```

In a second terminal:

```bash
cd web
npm install
npm run dev                  # http://localhost:3000
```

## Logins (demo data)

| Who | Email | Password |
|---|---|---|
| Agency A admin | `a.admin@agency.test` | `password123` |
| Agency B admin | `b.admin@agency.test` | `password123` |
| Vendor super-admin | `admin@vendor.test` | `password123` |

## If something goes wrong

- **"php / composer / npm is not recognized"** — that tool isn't installed or
  not on your PATH. Reinstall it and reopen the terminal.
- **Port already in use** — close other apps using 8000 or 3000, or change the
  port (`php artisan serve --port=8001`).
- Copy any red error text and send it over — it usually points straight at the
  fix.
