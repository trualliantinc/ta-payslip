# Quick Start Guide - TA Payslip System

Get the system running in 5 minutes!

## Prerequisites
- XAMPP installed and running (Apache + MySQL)
- phpMyAdmin access at `http://localhost/phpmyadmin`

## Step 1: Database Setup (2 minutes)

1. **Create Database:**
   - Open `http://localhost/phpmyadmin`
   - Click "Databases"
   - Name: `payslip` → Create

2. **Import Schema:**
   - Select the `payslip` database
   - Click "Import"
   - Upload `database.sql`
   - Click "Import"

## Step 2: Environment Configuration (1 minute)

1. **Copy Configuration:**
   ```bash
   cp .env.example .env
   ```

2. **Edit `.env`** (if needed - defaults work for XAMPP):
   ```ini
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=payslip
   ```

## Step 3: Add Test Data (1 minute)

1. In phpMyAdmin, select `credentials` table
2. Click "Insert"
3. Add a test employee:
   - employee_id: `EMP001`
   - name: `Test User`
   - email: `test@example.com`
   - password: `password123`
4. Click "Insert"

## Step 4: Test the System (1 minute)

1. Visit: `http://localhost/ta-payslip/login.php`
2. Login with:
   - Employee ID: `EMP001`
   - Password: `password123`
3. You should see the payslip viewer

## Next Steps

- **Add more employees:** Use phpMyAdmin to add records to `credentials` table
- **Add payslips:** Insert records into `payslips` table
- **Configure email:** Update `.env` with Gmail SMTP credentials
- **For production:** See [SETUP.md](SETUP.md) for deployment

## Quick Commands

```bash
# Install dependencies
composer install

# Run local server (if not using XAMPP)
php -S localhost:8000

# Generate test PDF
# (Automatic when viewing payslips)
```

## Troubleshooting Quick Fixes

| Problem | Solution |
|---------|----------|
| Database connection error | Check MySQL is running in XAMPP Control Panel |
| Login fails | Verify employee_id and password in credentials table |
| Payslips not showing | Ensure payslips exist in payslips table for that employee |
| Blank page | Check `storage/cache/payslips` directory exists and is writable |

## File Structure

```
ta-payslip/
├── login.php          # Login page (start here)
├── payslip.php        # Payslip viewer
├── lib/
│   ├── Database.php   # MySQL connection
│   ├── PayslipService.php
│   ├── Mailer.php
│   └── PdfService.php
├── config.php         # Configuration
├── .env              # Environment variables (create from .env.example)
├── database.sql      # Database schema
└── SETUP.md          # Detailed setup guide
```

## For More Help

- **Database issues:** See [SETUP.md](SETUP.md)
- **Migrating from Google Sheets:** See [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
- **All changes made:** See [RESTRUCTURING_CHECKLIST.md](RESTRUCTURING_CHECKLIST.md)
- **Detailed setup:** See [README.md](README.md)

---

**You're all set!** 🎉 The system is ready to use with localhost phpMyAdmin.
