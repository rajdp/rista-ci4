# Fix: InvalidArgumentException Class Not Found

## Problem

Even after uploading vendor directory, you're still getting:
```
Class "CodeIgniter\Exceptions\InvalidArgumentException" not found
```

This suggests the **autoloader** is not working properly or needs to be regenerated.

## Solution 1: Regenerate Autoloader

### Via SSH on GoDaddy:

```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/

# Regenerate autoloader
composer dump-autoload --optimize

# If that doesn't work, reinstall
composer install --no-dev --optimize-autoloader
```

### If Composer Not Available on Server:

**On your local machine:**

1. **Regenerate autoloader:**
   ```bash
   cd /Applications/MAMP/htdocs/rista_ci4
   composer dump-autoload --optimize
   ```

2. **Upload these files to GoDaddy:**
   - `vendor/autoload.php`
   - `vendor/composer/` directory (entire folder)

## Solution 2: Check .env File Syntax

The error occurs when parsing `.env`, so check for syntax errors:

### Common Issues:

1. **Unquoted URLs:**
   ```env
   # Wrong
   app.baseURL = https://edserver.edquillcrm.com/public/
   
   # Correct
   app.baseURL = 'https://edserver.edquillcrm.com/public/'
   ```

2. **Unquoted values with spaces:**
   ```env
   # Wrong
   database.default.DBCollat = utf8mb4_general_ci
   
   # Correct (if it has spaces, quote it)
   database.default.DBCollat = 'utf8mb4_general_ci'
   ```

3. **Special characters not escaped**

### Check Your .env File:

**Via SSH:**
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
cat .env
```

**Look for:**
- URLs without quotes
- Values with spaces without quotes
- Invalid characters

## Solution 3: Verify Exception File Exists

**Check if the file actually exists:**
```bash
ls -la vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php
```

**If it doesn't exist:**
- The vendor directory is still incomplete
- Re-upload the complete vendor directory

**If it exists but class can't be loaded:**
- Autoloader issue (see Solution 1)

## Solution 4: Manual Class Loading (Temporary Fix)

As a temporary workaround, you can manually load the exception class before CodeIgniter tries to use it.

**Edit `public/index.php`** - Add this at the very top (after opening PHP tag):

```php
<?php

// Temporary fix: Load exception class manually
$exceptionPath = __DIR__ . '/../vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php';
if (file_exists($exceptionPath)) {
    require_once $exceptionPath;
}

// Rest of your index.php code...
```

**Note:** This is a temporary workaround. The real fix is to fix the autoloader.

## Solution 5: Check File Permissions

**Set correct permissions:**
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/

# Vendor directory
chmod -R 755 vendor/
find vendor -type f -exec chmod 644 {} \;

# Composer autoload files
chmod 644 vendor/autoload.php
chmod -R 755 vendor/composer/
```

## Diagnostic Script

I've created `check_vendor.php` - upload it to `public/` and access:
```
https://edserver.edquillcrm.com/public/check_vendor.php
```

This will show:
- If vendor files exist
- If exception classes exist
- If autoloader works
- .env file syntax issues

## Step-by-Step Fix

### Step 1: Run Diagnostic
```
https://edserver.edquillcrm.com/public/check_vendor.php
```

### Step 2: Based on Results

**If exception file doesn't exist:**
- Re-upload complete vendor directory

**If exception file exists but class can't load:**
- Regenerate autoloader: `composer dump-autoload --optimize`
- Or reinstall: `composer install --no-dev`

**If .env has syntax errors:**
- Fix .env file syntax (quote URLs, etc.)

### Step 3: Clear Cache
```bash
rm -rf writable/cache/*
```

### Step 4: Test
```
POST https://edserver.edquillcrm.com/public/user/login
```

## Most Likely Fix

**90% of the time, this is an autoloader issue:**

```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
composer dump-autoload --optimize
```

If composer isn't available, regenerate locally and upload:
- `vendor/autoload.php`
- `vendor/composer/` directory

---

**Run the diagnostic script first to identify the exact issue!**






