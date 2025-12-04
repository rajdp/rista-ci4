# Fixing 500 Internal Server Error

A 500 error means CodeIgniter is running, but something is wrong. Let's find and fix it.

## Step 1: Check Error Logs

### Option A: CodeIgniter Logs (Most Detailed)

**Location:** `writable/logs/log-YYYY-MM-DD.log`

**Via SSH:**
```bash
cd /path/to/rista_ci4
tail -f writable/logs/log-*.log
```

**Via cPanel File Manager:**
1. Navigate to `writable/logs/`
2. Open the most recent log file (e.g., `log-2025-01-27.log`)
3. Look for error messages at the bottom

### Option B: GoDaddy Error Logs

**Via cPanel:**
1. Go to **Metrics** → **Errors**
2. Look for recent errors
3. Check the error message

### Option C: PHP Error Log

**Location:** `writable/logs/php_error.log`

**Via SSH:**
```bash
tail -f writable/logs/php_error.log
```

## Common Causes & Solutions

### Cause 1: Missing or Incorrect .env File

**Symptoms:**
- Error mentions "database connection" or "encryption key"
- Log shows: "Unable to connect to the database"

**Solution:**
1. Verify `.env` file exists in `rista_ci4/` root (not in `public/`)
2. Check database credentials:
   ```env
   database.default.hostname = 'localhost'
   database.default.database = 'your_database_name'
   database.default.username = 'your_database_user'
   database.default.password = 'your_database_password'
   database.default.port = 3306
   ```
3. Test database connection via phpMyAdmin

### Cause 2: Missing Encryption Key

**Symptoms:**
- Error: "Encryption key not set"
- Log shows encryption-related errors

**Solution:**
```bash
cd /path/to/rista_ci4
php spark key:generate
```

Then add the generated key to `.env`:
```env
encryption.key = 'your-generated-key-here'
```

### Cause 3: Database Connection Error

**Symptoms:**
- Error: "Unable to connect to the database"
- Log shows database connection failures

**Solution:**
1. **Verify credentials in `.env`:**
   ```env
   database.default.hostname = 'localhost'  # Usually 'localhost' on GoDaddy
   database.default.database = 'your_db_name'
   database.default.username = 'your_db_user'
   database.default.password = 'your_db_password'
   database.default.port = 3306
   ```

2. **Test connection:**
   - Use phpMyAdmin to verify credentials
   - Or create a test file: `public/test-db.php`
   ```php
   <?php
   $host = 'localhost';
   $db = 'your_database_name';
   $user = 'your_database_user';
   $pass = 'your_database_password';
   
   try {
       $conn = new mysqli($host, $user, $pass, $db);
       if ($conn->connect_error) {
           die("Connection failed: " . $conn->connect_error);
       }
       echo "Database connection successful!";
   } catch (Exception $e) {
       echo "Error: " . $e->getMessage();
   }
   ?>
   ```

### Cause 4: File Permission Issues

**Symptoms:**
- Error: "Permission denied"
- Log shows file access errors

**Solution:**
```bash
cd /path/to/rista_ci4

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Specifically for writable directory
chmod -R 755 writable/
chmod -R 755 writable/cache/
chmod -R 755 writable/logs/
chmod -R 755 writable/session/
chmod -R 755 writable/uploads/
```

### Cause 5: PHP Version Too Low

**Symptoms:**
- Error: "PHP version must be 8.1 or higher"
- CodeIgniter requires PHP 8.1+

**Solution:**
1. **Check PHP version:**
   ```bash
   php -v
   ```

2. **Change PHP version in cPanel:**
   - Go to **Select PHP Version**
   - Choose PHP 8.1 or higher
   - Click **Set as current**

### Cause 6: Missing Composer Dependencies

**Symptoms:**
- Error: "Class not found"
- Log shows autoload errors

**Solution:**
```bash
cd /path/to/rista_ci4
composer install --no-dev --optimize-autoloader
```

### Cause 7: Incorrect baseURL in .env

**Symptoms:**
- CORS errors
- Routing issues

**Solution:**
Check `.env` file:
```env
app.baseURL = 'https://edserver.edquillcrm.com/public/'
```

Make sure:
- URL ends with `/`
- Uses `https://` (not `http://`)
- Matches your actual domain

### Cause 8: Controller or Method Not Found

**Symptoms:**
- Error: "Controller not found" or "Method not found"
- Log shows routing errors

**Solution:**
1. **Check if controller exists:**
   ```bash
   ls -la app/Controllers/User.php
   ```

2. **Check if method exists:**
   - Open `app/Controllers/User.php`
   - Verify `login()` method exists

3. **Check route definition:**
   - Open `app/Config/Routes.php`
   - Verify: `$routes->post('user/login', 'User::login');`

## Quick Diagnostic Steps

### Step 1: Enable Debug Mode Temporarily

**Edit `.env`:**
```env
database.default.DBDebug = true
CI_ENVIRONMENT = development
```

**Warning:** Only enable in test environment, not production!

### Step 2: Check Basic Access

Try accessing:
```
https://edserver.edquillcrm.com/public/index.php
```

If this works but `/public/user/login` doesn't, it's a routing issue.

### Step 3: Test Simple Route

**Add to `app/Config/Routes.php`:**
```php
$routes->get('test', function() {
    return 'Test route works!';
});
```

Then access:
```
https://edserver.edquillcrm.com/public/test
```

If this works, the issue is with the `User::login` route or controller.

### Step 4: Check Controller File

**Verify file exists:**
```bash
ls -la app/Controllers/User.php
```

**Check file permissions:**
```bash
chmod 644 app/Controllers/User.php
```

## Step-by-Step Debugging

### 1. Check Error Logs First
```bash
tail -50 writable/logs/log-*.log
```

### 2. Check .env File
```bash
cat .env | grep -E "(database|encryption|baseURL)"
```

### 3. Test Database Connection
- Use phpMyAdmin
- Or create test file (see Cause 3)

### 4. Check File Permissions
```bash
ls -la writable/
ls -la app/Controllers/User.php
```

### 5. Verify Routes
```bash
grep "user/login" app/Config/Routes.php
```

## Most Common Fix

**90% of 500 errors are caused by:**

1. **Missing or incorrect `.env` file**
   - Create `.env` from template
   - Update database credentials
   - Generate encryption key

2. **Database connection failure**
   - Verify credentials
   - Test connection
   - Check database exists

3. **File permissions**
   - Set `writable/` to 755
   - Set files to 644

## Quick Fix Checklist

- [ ] `.env` file exists in `rista_ci4/` root
- [ ] Database credentials are correct in `.env`
- [ ] Encryption key is set in `.env`
- [ ] `writable/` directory has 755 permissions
- [ ] PHP version is 8.1 or higher
- [ ] Error logs checked for specific error message
- [ ] Database connection tested
- [ ] Controller file exists: `app/Controllers/User.php`
- [ ] Route exists: `$routes->post('user/login', 'User::login');`

## Get Specific Error Message

**The fastest way to fix this is to see the actual error:**

1. **Check CodeIgniter logs:**
   ```bash
   tail -100 writable/logs/log-*.log
   ```

2. **Or enable error display temporarily:**
   - Edit `public/index.php`
   - Ensure these lines are present:
   ```php
   error_reporting(E_ALL);
   ini_set('display_errors', 1);
   ```

3. **Check GoDaddy error logs in cPanel**

Once you have the specific error message, you can fix it precisely.

---

**Next Step:** Check the error logs and share the specific error message for targeted help!








