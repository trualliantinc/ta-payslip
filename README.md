# TA Payslip System

A PHP web app for managing and viewing employee payslips using **MySQL** via phpMyAdmin as the backend database, with password reset via email.

---

## 🚀 Features

* Employee login with credentials stored in MySQL `credentials` table.
* Fetches payslip data from MySQL `payslips` table.
* Reset password flow with secure token + email notification.
* PDF generation for payslips.
* Localhost phpMyAdmin integration for easy data management.

---

## 🗂 Project Structure

```
assets/                 # Static files (CSS, JS, images)
lib/                    # PHP classes (Database, PayslipService, Mailer, PdfService)
storage/cache/payslips/ # Cached PDFs
vendor/                 # Composer dependencies
views/                  # Optional HTML partials

.env                    # Local env variables (ignored in git)
.env.example            # Example environment configuration
config.php              # App config loader
login.php               # Login page
logout.php              # Logout script
payslip.php             # Payslip viewer
database.sql            # MySQL database schema
dockerfile              # For container deployment
SETUP.md                # Database setup instructions
```

---

## ⚙️ Setup (Local)

1. **Clone repo & install deps**

   ```bash
   git clone https://github.com/<your-org>/ta-payslip.git
   cd ta-payslip
   composer install
   ```

2. **Create MySQL database**

   * Open phpMyAdmin at `http://localhost/phpmyadmin`
   * Create a new database named `payslip`
   * Import `database.sql` to create tables

3. **Create `.env`** (copy from `.env.example`)

   ```bash
   cp .env.example .env
   ```

   Then edit `.env` with your configuration:

   ```ini
   # Database (defaults for XAMPP)
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=payslip

   # Mail (SMTP)
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=youremail@gmail.com
   MAIL_PASS=your-app-password
   MAIL_FROM=youremail@gmail.com
   MAIL_FROM_NAME="Payslip System"

   # App
   APP_URL=http://localhost/ta-payslip/
   RESET_TOKEN_TTL_MIN=30
   RESET_SECRET=change-this-to-a-long-random-string
   APP_LOGO_PATH=assets/ta_logo.png
   ```

4. **Add test data** (optional)

   * Open phpMyAdmin and select the `payslip` database
   * Insert test records into the `credentials` table
   * See `SETUP.md` for detailed instructions

5. **Run locally (with XAMPP)**

   * Place project in `htdocs`.
   * Visit [http://localhost/ta-payslip/login.php](http://localhost/ta-payslip/login.php).

---

## 🗄️ Database Schema

### credentials table
Stores employee login credentials and password reset information:
```sql
- employee_id (VARCHAR, UNIQUE) - Login identifier
- name (VARCHAR) - Employee name
- email (VARCHAR) - Email address
- password (VARCHAR) - Password (currently plaintext)
- designation (VARCHAR) - Job title
- reset_token_hash (VARCHAR) - For password reset flow
- reset_expires (DATETIME) - Token expiration
```

### payslips table
Stores payslip records for employees:
```sql
- employee_id (VARCHAR, FK) - Link to credentials
- name (VARCHAR) - Employee name
- email (VARCHAR) - Email address
- payroll_date (DATE) - Pay period date
- gross_pay (DECIMAL) - Gross salary
- net_pay (DECIMAL) - Net salary
- deductions (DECIMAL) - Total deductions
- benefits (DECIMAL) - Benefits amount
- tax (DECIMAL) - Tax amount
- notes (TEXT) - Additional notes
```

See [SETUP.md](SETUP.md) for detailed database setup instructions.

---

## ☁️ Deploy on Render (With External MySQL)

For Render deployments, you'll need an external MySQL database (e.g., AWS RDS, PlanetScale):

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

   ```
   DB_HOST=<external-database-host>
   DB_USER=<database-user>
   DB_PASS=<database-password>
   DB_NAME=payslip
   
   MAIL_HOST=smtp.gmail.com
   MAIL_PORT=587
   MAIL_USER=youremail@gmail.com
   MAIL_PASS=your-app-password
   MAIL_FROM=youremail@gmail.com
   MAIL_FROM_NAME=Payslip System
   
   APP_URL=https://<your-service>.onrender.com/
   RESET_TOKEN_TTL_MIN=30
   RESET_SECRET=<long-random-string>
   APP_LOGO_PATH=assets/ta_logo.png
   ```

4. **Initialize remote database**

   * Connect to your remote MySQL database
   * Import `database.sql` to create tables

5. **Deploy** → Render will build and launch your payslip system.

---

## 🔒 Security Notes

* **Never commit** `.env` → already ignored in `.gitignore`.
* Always use Gmail **App Password** instead of your real password.
* Change `RESET_SECRET` to a strong random string.
* **Passwords**: Currently stored as plaintext. For production, implement password hashing using `password_hash()` and `password_verify()`.

---

## ✅ Troubleshooting

* **Database connection failed**: Ensure MySQL is running and `payslip` database exists. Check `.env` credentials.
* **Table not found**: Import `database.sql` into the `payslip` database via phpMyAdmin.
* **Login not working**: Verify employee credentials exist in the `credentials` table and passwords match exactly.
* **Forgot password not working**: Ensure `reset_token_hash` and `reset_expires` columns exist in the `credentials` table.
* **Email not sending**: Check SMTP credentials in `.env` and verify Gmail App Password is correct.

---

## 📜 License

MIT (or your chosen license)

