# Migration Guide: Google Sheets → MySQL

This guide helps you migrate from the Google Sheets-based system to the new MySQL database system.

## What Changed?

### Before (Google Sheets)
- Employee credentials stored in a Google Sheet (`CREDENTIALS` sheet)
- Payslip data fetched from multiple Google Sheets (`TA_MS`, `TA_AGENTS`)
- Google Sheets API integration required
- Service account credentials needed

### After (MySQL)
- Employee credentials stored in MySQL `credentials` table
- Payslip data stored in MySQL `payslips` table
- Simple direct database queries
- No external API dependencies

## Step-by-Step Migration

### 1. Set Up MySQL Database

Follow the instructions in [SETUP.md](SETUP.md) to:
- Create the `payslip` database
- Import `database.sql` to create tables
- Configure `.env` with database credentials

### 2. Export Data from Google Sheets

#### Credentials Data
1. Open your Google Sheet with the `CREDENTIALS` sheet
2. Select all data (Ctrl+A)
3. Copy (Ctrl+C)
4. Create a text file and paste the data
5. Save as `credentials_export.csv`

#### Payslips Data
1. Do the same for payslip data from your sheets
2. Save as `payslips_export.csv`

### 3. Import Data into MySQL

#### Via phpMyAdmin (Recommended)

**For Credentials:**
1. Open phpMyAdmin → Select `payslip` database → Click `credentials` table
2. Go to "Import" tab
3. Upload your `credentials_export.csv`
4. Map columns:
   - CSV "EMPLOYEE ID" → Database "employee_id"
   - CSV "NAME" → Database "name"
   - CSV "EMAIL" → Database "email"
   - CSV "PASSWORD" → Database "password"
   - CSV "DESIGNATION" → Database "designation"
5. Click "Import"

**For Payslips:**
1. Click on `payslips` table
2. Go to "Insert" tab (to manually add records)
3. Or use "Import" to upload CSV with columns mapped to:
   - employee_id, name, email, payroll_date, gross_pay, deductions, net_pay, benefits, tax, notes

#### Via MySQL Command Line
```bash
# Connect to MySQL
mysql -u root -p

# Use the payslip database
USE payslip;

# Load CSV data
LOAD DATA LOCAL INFILE '/path/to/credentials_export.csv'
INTO TABLE credentials
FIELDS TERMINATED BY ','
LINES TERMINATED BY '\n'
(employee_id, name, email, password, designation);
```

### 4. Test the Application

1. Remove or rename your old Google Sheets configuration from `.env`
2. Add the new MySQL configuration to `.env`:
   ```ini
   DB_HOST=localhost
   DB_USER=root
   DB_PASS=
   DB_NAME=payslip
   ```
3. Visit `http://localhost/ta-payslip/login.php`
4. Test login with one of your migrated employee accounts
5. Verify payslips display correctly

### 5. Update Deployment Configuration

If you're deploying to Render or another hosting:

1. **Create a remote MySQL database** (e.g., on AWS RDS, PlanetScale, or DigitalOcean)
2. **Import the schema** by running `database.sql` on the remote database
3. **Migrate production data** using the same CSV import steps above
4. **Update environment variables** on Render/hosting:
   ```
   DB_HOST=your-remote-host.com
   DB_USER=your-db-user
   DB_PASS=your-db-password
   DB_NAME=payslip
   ```

## Code Changes Summary

### Files Modified
- **config.php** - Added `DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME` constants
- **lib/PayslipService.php** - Changed from `GoogleSheets` to `Database` class
- **reset_process.php** - Uses Database instead of GoogleSheets
- **forgot_process.php** - Uses Database for credential lookups
- **reset.php** - Uses Database for token validation
- **README.md** - Updated with MySQL setup instructions

### New Files
- **lib/Database.php** - New MySQL database abstraction class
- **database.sql** - MySQL schema definition
- **SETUP.md** - Detailed database setup instructions
- **.env.example** - Environment configuration template

### Files No Longer Used (Optional)
- **lib/GoogleSheets.php** - Can be deleted (Google Sheets integration removed)
- **credentials/** folder - No longer needed (remove if not using Google integration)

## Column Mapping Reference

### From Google Sheets to MySQL

**CREDENTIALS Sheet → credentials Table:**
```
EMPLOYEE ID         → employee_id
NAME                → name
EMAIL               → email
PASSWORD            → password
DESIGNATION         → designation
RESET_TOKEN_HASH    → reset_token_hash
RESET_EXPIRES       → reset_expires
```

**Payslips (TA_MS/TA_AGENTS) → payslips Table:**
```
EMPLOYEE ID         → employee_id
NAME                → name
EMAIL               → email
PAYROLL DATE        → payroll_date
GROSS_PAY           → gross_pay
DEDUCTIONS          → deductions
NET_PAY             → net_pay
(benefits, tax, notes - as needed)
```

## Troubleshooting Migration Issues

### "Login fails with correct credentials"
- Verify employee_id values match exactly (case-sensitive)
- Check that passwords were imported correctly
- Confirm employee_id values don't have leading/trailing spaces

### "Payslips not showing"
- Verify employee_id in payslips table matches credentials table
- Check that payroll_date is in YYYY-MM-DD format
- Ensure the employee has at least one payslip record

### "Import shows wrong number of rows"
- Check for blank rows in your CSV file
- Verify column headers match MySQL table structure
- Test with a smaller subset of data first

### "Database character encoding issues"
- Ensure your CSV is UTF-8 encoded
- MySQL table charset should be `utf8mb4`
- Check SETUP.md for database creation steps

## Rollback Plan

If something goes wrong:

1. Keep a backup of your Google Sheets data
2. Database backups are easy in phpMyAdmin:
   - Select `payslip` database → Click "Export"
3. To revert: Restore Google Sheets configuration in `.env` and update code back to Google Sheets classes

## Performance Improvements

MySQL is **much faster** than Google Sheets:
- Direct database queries vs. API calls
- No rate limiting concerns
- Instant credential validation
- Better for concurrent users

## Next Steps

1. ✅ Complete data migration
2. ✅ Test thoroughly in local environment
3. ✅ Set up remote MySQL database
4. ✅ Update production environment variables
5. ✅ Deploy to hosting platform
6. ✅ Monitor for any issues

See [README.md](README.md) for deployment instructions on Render or other platforms.
