# Fix: CodeIgniter Framework Files Missing

## Error Analysis

**Error:** `Class "CodeIgniter\Exceptions\InvalidArgumentException" not found`

**Location:** `vendor/codeigniter4/framework/system/Config/DotEnv.php:179`

**Cause:** The CodeIgniter framework files in the `vendor/` directory are incomplete or corrupted. The framework can't find its own exception classes, which means the autoloader isn't working properly.

## Solution: Reinstall Composer Dependencies

### Step 1: Check if Composer is Available

**Via SSH:**
```bash
composer --version
```

If composer is not available, you'll need to install dependencies locally and upload the `vendor/` folder.

### Step 2: Reinstall Dependencies

#### Option A: Via Composer on Server (If Available)

```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/

# Remove existing vendor directory (backup first if needed)
mv vendor vendor_backup

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
```

#### Option B: Install Locally and Upload (Recommended for GoDaddy)

**On your local machine:**

1. **Navigate to your project:**
   ```bash
   cd /Applications/MAMP/htdocs/rista_ci4
   ```

2. **Remove existing vendor (if you want fresh install):**
   ```bash
   rm -rf vendor
   ```

3. **Reinstall dependencies:**
   ```bash
   composer install --no-dev --optimize-autoloader
   ```

4. **Verify vendor directory:**
   ```bash
   ls -la vendor/codeigniter4/framework/system/Exceptions/
   ```
   
   Should show exception files including `InvalidArgumentException.php`

5. **Upload vendor directory to GoDaddy:**
   - Use FTP/SFTP client
   - Upload entire `vendor/` folder to:
     `/home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/vendor/`
   - This may take a while (vendor is large)

#### Option C: Upload Pre-built Vendor (Fastest)

If you have a complete `vendor/` directory locally:

1. **Zip the vendor directory:**
   ```bash
   cd /Applications/MAMP/htdocs/rista_ci4
   zip -r vendor.zip vendor/
   ```

2. **Upload vendor.zip to GoDaddy**

3. **Extract on server:**
   ```bash
   cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
   unzip vendor.zip
   ```

4. **Set permissions:**
   ```bash
   chmod -R 755 vendor/
   ```

### Step 3: Verify Framework Files

**Check if exception classes exist:**
```bash
ls -la vendor/codeigniter4/framework/system/Exceptions/
```

**Should see files like:**
- `InvalidArgumentException.php`
- `FrameworkException.php`
- `PageNotFoundException.php`
- etc.

### Step 4: Check .env File Syntax

The error occurs when parsing `.env`, so also check for syntax errors:

**Common .env syntax issues:**
- Missing quotes around values with spaces
- Special characters not escaped
- Invalid variable names

**Check your .env file:**
```bash
cat .env | grep -v "^#" | grep -v "^$"
```

**Make sure values are properly formatted:**
```env
# Correct
app.baseURL = 'https://edserver.edquillcrm.com/public/'
database.default.hostname = 'localhost'

# Wrong (missing quotes for URLs)
app.baseURL = https://edserver.edquillcrm.com/public/
```

### Step 5: Clear Cache

After fixing vendor:

```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
rm -rf writable/cache/*
```

### Step 6: Test Again

1. **Test your API:**
   ```
   POST https://edserver.edquillcrm.com/public/user/login
   ```

2. **Check error logs if still failing:**
   ```bash
   tail -50 writable/logs/log-*.log
   ```

## Alternative: Check Specific Missing File

**Verify the exception class exists:**
```bash
ls -la vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php
```

**If missing, the vendor directory is incomplete.**

## Quick Fix Checklist

- [ ] `vendor/` directory exists on GoDaddy
- [ ] `vendor/codeigniter4/framework/system/Exceptions/` directory exists
- [ ] `InvalidArgumentException.php` file exists
- [ ] Run `composer install` or upload complete vendor directory
- [ ] Clear cache: `rm -rf writable/cache/*`
- [ ] Check `.env` file syntax
- [ ] Test API endpoint

## Why This Happened

1. **Incomplete upload** - vendor directory is large and may not have uploaded completely
2. **Corrupted files** - Files may have been corrupted during upload
3. **Missing dependencies** - Composer dependencies weren't installed
4. **Autoloader issue** - Composer autoloader not properly generated

## Prevention

**Always verify after upload:**
1. Check vendor directory size (should be large, several MB)
2. Verify key files exist
3. Run diagnostic script
4. Test API endpoints

---

**The vendor directory contains the CodeIgniter framework. It must be complete for the application to work!**






