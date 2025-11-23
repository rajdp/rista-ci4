# Check Error Logs to Find 500 Error

The 500 error means CodeIgniter is running but something is failing. We need to see the actual error message.

## Step 1: Check CodeIgniter Error Logs

### Via SSH:
```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/
tail -100 writable/logs/log-*.log
```

### Via cPanel File Manager:
1. Navigate to `writable/logs/`
2. Open the most recent log file (e.g., `log-2025-01-27.log`)
3. Scroll to the **bottom** - errors are at the end
4. Look for the most recent error

## Step 2: Enable Error Display (Temporary)

**Edit `public/index.php`** - Make sure these lines are at the very top:

```php
<?php

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/writable/logs/php_error.log');
```

**Then refresh your page** - you should see the actual error message on screen.

**⚠️ IMPORTANT:** Disable this in production after debugging!

## Step 3: Check GoDaddy Error Logs

**Via cPanel:**
1. Go to **Metrics** → **Errors**
2. Look for recent errors
3. Check the error message

## Step 4: Verify All Fixes

### Checklist:

- [ ] `.env` file exists in `rista_ci4/` root
- [ ] `.env` has `session.savePath = null` (not `WRITEPATH . 'session'`)
- [ ] `.env` has `encryption.key` set (not placeholder)
- [ ] `app/Config/App.php` has been updated (upload the fixed version)
- [ ] `app/` directory is complete on GoDaddy
- [ ] `vendor/` directory is complete on GoDaddy
- [ ] `public/.htaccess` exists
- [ ] File permissions are correct

## Step 5: Test Direct Access

Try accessing the endpoint directly in browser:
```
https://edserver.edquillcrm.com/public/user/login
```

If you see an error message, that's the actual error.

## Step 6: Common Remaining Issues

### Issue 1: App.php Not Updated on Server

**Fix:** Upload the updated `app/Config/App.php` file to GoDaddy.

The file should have:
- `public string $baseURL;` (no initialization)
- `public bool $forceGlobalSecureRequests;` (no initialization)
- Constructor that initializes both

### Issue 2: Encryption Key Still Placeholder

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

### Issue 3: Missing Files

**Check if these exist:**
```bash
ls -la app/Config/App.php
ls -la app/Config/Routes.php
ls -la app/Controllers/User.php
ls -la vendor/autoload.php
```

## Quick Diagnostic Commands

```bash
# 1. Check error logs
tail -50 writable/logs/log-*.log

# 2. Check .env syntax
grep "session.savePath" .env
grep "encryption.key" .env

# 3. Check App.php was updated
grep "forceGlobalSecureRequests" app/Config/App.php
# Should show: public bool $forceGlobalSecureRequests; (no = env(...))

# 4. Test database
php -r "new mysqli('localhost', 'edquill_demo', 'edquill_demo2025', 'edquill_demo'); echo 'OK';"
```

## Most Important: See the Actual Error

**The fastest way to fix this is to see the actual error message:**

1. **Check error logs** (Step 1) - This will show the exact error
2. **Enable error display** (Step 2) - See error on screen
3. **Share the specific error message** - Then we can fix it precisely

---

**Next Step:** Check the error logs and share the specific error message!

