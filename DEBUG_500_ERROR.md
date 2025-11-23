# Debug 500 Internal Server Error

You're still getting 500 errors. Let's find the exact cause.

## Step 1: Check Error Logs

### Option A: CodeIgniter Logs (Most Detailed)

**Via SSH:**
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
tail -100 writable/logs/log-*.log
```

**Via cPanel File Manager:**
1. Navigate to `writable/logs/`
2. Open the most recent log file (e.g., `log-2025-01-27.log`)
3. Scroll to the bottom - errors are at the end

### Option B: PHP Error Log

**Via SSH:**
```bash
tail -50 writable/logs/php_error.log
```

### Option C: GoDaddy Error Logs

**Via cPanel:**
1. Go to **Metrics** → **Errors**
2. Look for recent errors
3. Check the error message

## Step 2: Enable Error Display (Temporary)

**Edit `public/index.php`** - Make sure these lines are at the top:

```php
<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/writable/logs/php_error.log');
```

**Then refresh your page** - you should see the actual error message.

**⚠️ IMPORTANT:** Disable this in production after debugging!

## Step 3: Verify All Fixes Are Applied

### Checklist:

- [ ] `.env` file exists in `rista_ci4/` root
- [ ] `.env` file has `session.savePath = null` (not `WRITEPATH . 'session'`)
- [ ] `encryption.key` is set (not 'your-generated-key-here')
- [ ] `app/` directory is uploaded completely
- [ ] `vendor/` directory is uploaded completely
- [ ] `public/.htaccess` exists
- [ ] File permissions are correct (755 for dirs, 644 for files)
- [ ] `writable/` directory is writable

## Step 4: Common Remaining Issues

### Issue 1: Encryption Key Not Set

**Check:**
```bash
grep "encryption.key" .env
```

**Should show:**
```env
encryption.key = 'actual-64-character-hex-key'
```

**Not:**
```env
encryption.key = 'your-generated-key-here'
```

**Fix:** Generate and set the key:
```bash
php spark key:generate
# Copy the output key to .env
```

### Issue 2: Missing Files Still

**Check if these exist:**
```bash
ls -la app/Config/Paths.php
ls -la app/Config/Routes.php
ls -la app/Controllers/User.php
ls -la vendor/codeigniter4/framework/system/Exceptions/InvalidArgumentException.php
```

**If any are missing, upload them.**

### Issue 3: Autoloader Not Working

**Regenerate autoloader:**
```bash
composer dump-autoload --optimize
```

**Or if composer not available, upload from local:**
- `vendor/autoload.php`
- `vendor/composer/` directory

### Issue 4: Database Connection

**Test database connection:**
```bash
# Create test file: public/test-db-simple.php
```

```php
<?php
$host = 'localhost';
$db = 'edquill_demo';
$user = 'edquill_demo';
$pass = 'edquill_demo2025';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
echo "Database connection successful!";
?>
```

Access: `https://edserver.edquillcrm.com/public/test-db-simple.php`

### Issue 5: File Permissions

**Set correct permissions:**
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/

# Directories
find . -type d -exec chmod 755 {} \;

# Files
find . -type f -exec chmod 644 {} \;

# Writable directory
chmod -R 755 writable/
```

## Step 5: Test Basic Access

### Test 1: Direct index.php
```
https://edserver.edquillcrm.com/public/index.php
```

**If this works:** Routing issue
**If this fails:** Core issue

### Test 2: Simple PHP
Create `public/test.php`:
```php
<?php
phpinfo();
?>
```

Access: `https://edserver.edquillcrm.com/public/test.php`

**If this works:** PHP is working
**If this fails:** PHP configuration issue

### Test 3: CodeIgniter Bootstrap
Create `public/test-bootstrap.php`:
```php
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
require FCPATH . '../app/Config/Paths.php';

$paths = new Config\Paths();
echo "Paths loaded successfully!<br>";
echo "App Directory: " . $paths->appDirectory . "<br>";
echo "System Directory: " . $paths->systemDirectory . "<br>";
?>
```

Access: `https://edserver.edquillcrm.com/public/test-bootstrap.php`

## Step 6: Get Specific Error

**The fastest way to fix this is to see the actual error message:**

1. **Enable error display** (see Step 2)
2. **Check error logs** (see Step 1)
3. **Run diagnostic script:**
   ```
   https://edserver.edquillcrm.com/public/diagnose.php
   ```

## Quick Diagnostic Commands

```bash
# 1. Check .env syntax
cat .env | grep -v "^#" | grep -v "^$"

# 2. Check encryption key
grep "encryption.key" .env

# 3. Check error logs
tail -50 writable/logs/log-*.log

# 4. Check file permissions
ls -la app/Config/Paths.php
ls -la vendor/autoload.php

# 5. Test database
php -r "new mysqli('localhost', 'edquill_demo', 'edquill_demo2025', 'edquill_demo'); echo 'OK';"
```

## Most Common Remaining Issues

1. **Encryption key not set** - Still has placeholder value
2. **Missing files** - app/ or vendor/ still incomplete
3. **Autoloader issue** - Needs regeneration
4. **Database connection** - Credentials wrong or database doesn't exist
5. **File permissions** - Files not readable

## Next Steps

1. **Check error logs first** - This will show the exact error
2. **Enable error display** - See error on screen
3. **Run diagnostic script** - Get comprehensive check
4. **Share the specific error message** - Then we can fix it precisely

---

**The key is to see the actual error message. Check the logs or enable error display!**

