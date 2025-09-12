# TA Payslip System

A PHP web app for managing and viewing employee payslips using **Google Sheets** as the backend database, with password reset via email.

---

## 🚀 Features

* Employee login with credentials stored in Google Sheets (`CREDENTIALS` sheet).
* Fetches payslip data from multiple sheets (`TA_MS`, `TA_AGENTS`).
* Reset password flow with secure token + email notification.
* PDF generation for payslips.
* Ready to deploy on **Render**.

---

## 🗂 Project Structure

```
assets/                 # Static files (CSS, JS, images)
credentials/            # Google Service Account JSON (ignored in git)
lib/                    # PHP classes (GoogleSheets, PayslipService, Mailer, PdfService)
storage/cache/payslips/ # Cached PDFs
vendor/                 # Composer dependencies
views/                  # Optional HTML partials

.env                    # Local env variables (ignored in git)
config.php              # App config loader (uses dotenv or Render env)
login.php               # Login page
logout.php              # Logout script
payslip.php             # Payslip viewer
dockerfile              # For container deployment on Render
```

---

## ⚙️ Setup (Local)

1. **Clone repo & install deps**

   ```bash
   git clone https://github.com/<your-org>/ta-payslip.git
   cd ta-payslip
   composer install
   ```

2. **Create `.env`** (for local only)

   ```ini
   # Google Sheets
   GOOGLE_SERVICE_JSON=credentials/google-service-account.json
   GOOGLE_SHEET_ID=<your-sheet-id>
   SHEET_TA_MS=TA_MS
   SHEET_TA_AGENTS=TA_AGENTS
   SHEET_CREDENTIALS=CREDENTIALS

   # Mail (SMTP)
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=youremail@gmail.com
   MAIL_PASS=your-app-password
   MAIL_FROM=youremail@gmail.com
   MAIL_FROM_NAME="Payslip"

   # App
   APP_URL=http://localhost/ta-payslip
   RESET_TOKEN_TTL_MIN=35
   RESET_SECRET=change-this-to-a-long-random-string
   APP_LOGO_PATH=assets/ta_logo.png
   ```

3. **Run locally (with XAMPP/WAMP/Laragon)**

   * Place project in `htdocs`.
   * Visit [http://localhost/ta-payslip/login.php](http://localhost/ta-payslip/login.php).

---

## ☁️ Deploy on Render

1. **Push repo to GitHub**

   ```bash
   git init
   git add .
   git commit -m "initial commit"
   git branch -M main
   git remote add origin https://github.com/<your-org>/ta-payslip.git
   git push -u origin main
   ```

2. **Create Render Web Service**

   * Runtime: `PHP 8.2`
   * Build Command: `composer install`
   * Start Command: `php -S 0.0.0.0:10000 -t .`

3. **Add Environment Variables (Render Dashboard)**

   * `GOOGLE_SHEET_ID=<your-sheet-id>`
   * `SHEET_TA_MS=TA_MS`
   * `SHEET_TA_AGENTS=TA_AGENTS`
   * `SHEET_CREDENTIALS=CREDENTIALS`
   * `MAIL_HOST=smtp.gmail.com`
   * `MAIL_PORT=587`
   * `MAIL_USER=youremail@gmail.com`
   * `MAIL_PASS=your-app-password`
   * `MAIL_FROM=youremail@gmail.com`
   * `MAIL_FROM_NAME=Payslip`
   * `APP_URL=https://<your-service>.onrender.com`
   * `RESET_TOKEN_TTL_MIN=35`
   * `RESET_SECRET=<long-random-string>`
   * Either:

     * `GOOGLE_SERVICE_JSON=/opt/render/project/src/credentials/google-service-account.json` (if using **Secret File**)
     * OR `GOOGLE_APPLICATION_CREDENTIALS_JSON=<paste full JSON>`

4. **Deploy** → Render will build and launch your payslip system.

---

## 🔒 Security Notes

* **Never commit** `.env` or service account JSON → already ignored in `.gitignore`.
* Always use Gmail **App Password** instead of real password.
* Change `RESET_SECRET` to a strong random string.

---

## ✅ Troubleshooting

* **session\_start() warnings**: make sure nothing `echo`s before `session_start()`. Fixed in `config.php`.
* **Dotenv not found on Render**: safe, Render uses real env vars. `.env` is only for local.
* **Invalid credentials**: check your `CREDENTIALS` sheet header spelling (`EMPLOYEE ID`, `PASSWORD`, `NAME`, `EMAIL`).
* **Forgot password not updating**: ensure `RESET_TOKEN_HASH` and `RESET_EXPIRES` columns exist in the `CREDENTIALS` sheet.

---

## 📜 License

MIT (or your chosen license)
