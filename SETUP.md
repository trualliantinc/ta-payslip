# Database Setup Guide

## Overview
The TA Payslip System has been restructured to use **MySQL via phpMyAdmin** running on **localhost** with the database name **payslip**.

## Prerequisites
- XAMPP installed and running (Apache + MySQL)
- phpMyAdmin accessible (usually at `http://localhost/phpmyadmin`)

## Setup Instructions

### Step 1: Create the Database
1. Open phpMyAdmin at `http://localhost/phpmyadmin`
2. Click on "Databases" tab
3. In "Create database" section, enter database name: `payslip`
4. Click "Create"

### Step 2: Import the Database Schema
1. In phpMyAdmin, select the `payslip` database
2. Click on "Import" tab
3. Click "Choose File" and select `database.sql` from your project root
4. Click "Import"

This will create two tables:
- `credentials` - Stores employee login credentials and password reset tokens
- `payslips` - Stores payslip records for employees

### Step 3: Configure Environment Variables
1. Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```

2. Edit `.env` with your database credentials (default for XAMPP):
```
DB_HOST=localhost
DB_USER=root
DB_PASS=
DB_NAME=payslip
```

### Step 4: Add Test Data (Optional)
Insert sample credentials in phpMyAdmin:
1. Select the `payslip` database
2. Click on the `credentials` table
3. Click "Insert"
4. Add a test employee:
   - **employee_id**: EMP001
   - **name**: John Doe
   - **email**: john@example.com
   - **password**: password123
   - **designation**: Sales Manager
5. Click "Insert"

### Step 5: Verify the Connection
Access the login page at `http://localhost/ta-payslip/login.php` and test with your credentials.

## Database Schema

### credentials table
```sql
- id (INT, PK)
- employee_id (VARCHAR, UNIQUE)
- name (VARCHAR)
- email (VARCHAR)
- password (VARCHAR)
- designation (VARCHAR)
- reset_token_hash (VARCHAR)
- reset_expires (DATETIME)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

### payslips table
```sql
- id (INT, PK)
- employee_id (VARCHAR, FK)
- name (VARCHAR)
- email (VARCHAR)
- payroll_date (DATE)
- gross_pay (DECIMAL)
- net_pay (DECIMAL)
- deductions (DECIMAL)
- benefits (DECIMAL)
- tax (DECIMAL)
- notes (TEXT)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
```

## Migrating from Google Sheets (if applicable)

If you were previously using Google Sheets:

1. Export your data from Google Sheets as CSV
2. In phpMyAdmin, use the "Import" feature to insert CSV data into the corresponding tables
3. Map CSV columns to database columns during import

## Troubleshooting

### Connection Error: "Database connection failed"
- Verify MySQL is running in XAMPP Control Panel
- Check DB_HOST, DB_USER, DB_PASS in `.env`
- Ensure the `payslip` database exists

### "Table not found" errors
- Run the `database.sql` import again to create tables
- Verify all tables exist in phpMyAdmin under the `payslip` database

### Login fails with correct credentials
- Check that employee_id exists in `credentials` table
- Verify passwords are stored correctly (currently plain text, consider using password_hash)
- Check MySQL error log for detailed errors

## Security Notes
- Current implementation stores passwords as plain text (not recommended for production)
- Consider implementing password_hash() with PASSWORD_BCRYPT for production use
- Update login.php to use password_verify() if switching to hashed passwords

## Maintenance

### Regular backups
Export the `payslip` database regularly:
1. In phpMyAdmin, select the `payslip` database
2. Click "Export"
3. Select "SQL" format
4. Click "Go"
