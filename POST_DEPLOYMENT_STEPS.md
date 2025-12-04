# Post-Deployment Activation Steps for CodeIgniter 4

After copying your backend files to GoDaddy, follow these steps to activate CodeIgniter 4.

## ✅ Required Steps After Uploading Files

### 1. Create and Configure `.env` File

**Location:** Root of `rista_ci4/` directory (same level as `app/`, `public/`, etc.)

1. **Create the `.env` file** on the server:
   - Via cPanel File Manager: Create new file named `.env`
   - Via SSH: `touch .env`

2. **Copy content from template:**
   - For TEST: Copy from `.env.test`
   - For PRODUCTION: Copy from `.env.production`

3. **Update with your GoDaddy credentials:**
   ```env
   CI_ENVIRONMENT = production
   app.baseURL = 'https://edserver.edquillcrm.com/public/'
   database.default.hostname = 'localhost'
   database.default.database = 'your_database_name'
   database.default.username = 'your_database_user'
   database.default.password = 'your_database_password'
   database.default.port = 3306
   database.default.DBDebug = false
   ```

### 2. Generate Encryption Key

**This is REQUIRED** - CodeIgniter 4 needs an encryption key for security.

**Option A: Via SSH (Recommended)**
```bash
# SSH into your GoDaddy server
cd /path/to/rista_ci4
php spark key:generate
```

**Option B: Via cPanel Terminal**
1. Log into cPanel
2. Open **Terminal** or **SSH Access**
3. Navigate to your CodeIgniter directory:
   ```bash
   cd public_html/rista_ci4
   ```
4. Run:
   ```bash
   php spark key:generate
   ```
5. Copy the generated key and add it to your `.env` file:
   ```env
   encryption.key = 'your-generated-key-here'
   ```

**Option C: Manual (If SSH not available)**
1. Generate a random 32-character hex string
2. Add to `.env`:
   ```env
   encryption.key = 'your-32-character-hex-string-here'
   ```

### 3. Set File Permissions

**Critical:** CodeIgniter needs write access to certain directories.

**Via SSH:**
```bash
cd /path/to/rista_ci4

# Set writable directory permissions
chmod -R 755 writable/
chmod -R 755 writable/cache/
chmod -R 755 writable/logs/
chmod -R 755 writable/session/
chmod -R 755 writable/uploads/

# Set .env file permissions (readable but not executable)
chmod 644 .env
```

**Via cPanel File Manager:**
1. Navigate to `writable/` directory
2. Right-click → **Change Permissions**
3. Set to **755** for directories
4. Set to **644** for files

### 4. Run Database Migrations (If Applicable)

If you have database migrations, run them:

**Via SSH:**
```bash
cd /path/to/rista_ci4
php spark migrate
```

**Note:** Only run migrations if:
- You have migration files in `app/Database/Migrations/`
- You need to update database schema
- This is a fresh deployment

**Skip this step if:**
- Database is already set up
- You imported a complete database dump

### 5. Verify Web Server Configuration

**Check `.htaccess` file** in `public/` directory:

The file should exist at: `public/.htaccess`

If missing, create it with:
```apache
# Disable directory browsing
Options -Indexes

# Custom error pages
ErrorDocument 404 /index.php

# Enable Rewrite Engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect to index.php if file doesn't exist
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>
```

### 6. Test the Installation

1. **Visit your backend URL:**
   ```
   https://edserver.edquillcrm.com/public/
   ```

2. **Expected Results:**
   - ✅ Should see CodeIgniter welcome page OR your API response
   - ❌ If you see errors, check the troubleshooting section below

3. **Check error logs:**
   - Location: `writable/logs/log-YYYY-MM-DD.log`
   - Check for any errors or warnings

---

## 🔧 Optional but Recommended Steps

### Clear Cache (If Issues Occur)

```bash
php spark cache:clear
```

### Check PHP Version

CodeIgniter 4 requires PHP 8.1 or higher:

```bash
php -v
```

### Verify Composer Dependencies

If you're using Composer dependencies:

```bash
cd /path/to/rista_ci4
composer install --no-dev --optimize-autoloader
```

---

## 🚨 Common Issues & Solutions

### Issue 1: "Encryption key not set" Error

**Solution:**
```bash
php spark key:generate
# Then add the key to .env file
```

### Issue 2: "Permission denied" Errors

**Solution:**
```bash
chmod -R 755 writable/
chown -R your_user:your_group writable/
```

### Issue 3: "Database connection failed"

**Solution:**
- Verify database credentials in `.env`
- Check database host (usually `localhost` on GoDaddy)
- Ensure database user has proper privileges
- Test connection via phpMyAdmin

### Issue 4: "404 Not Found" on Routes

**Solution:**
- Verify `.htaccess` exists in `public/` directory
- Check `app.baseURL` in `.env` matches your actual URL
- Ensure mod_rewrite is enabled on GoDaddy

### Issue 5: "Class not found" Errors

**Solution:**
```bash
# Regenerate autoload files
composer dump-autoload
```

---

## 📋 Quick Checklist

After uploading files, verify:

- [ ] `.env` file created and configured
- [ ] Encryption key generated and added to `.env`
- [ ] File permissions set (755 for writable/)
- [ ] Database credentials correct in `.env`
- [ ] `.htaccess` exists in `public/` directory
- [ ] Can access backend URL without errors
- [ ] Error logs are being created (check `writable/logs/`)
- [ ] Database connection works (test via API endpoint)

---

## 🎯 Minimum Required Commands

If you can only run a few commands, these are the **ESSENTIAL** ones:

```bash
# 1. Navigate to your CodeIgniter directory
cd /path/to/rista_ci4

# 2. Generate encryption key
php spark key:generate

# 3. Set permissions
chmod -R 755 writable/

# 4. Test (visit your URL in browser)
```

---

## 📞 Need Help?

1. Check error logs: `writable/logs/log-YYYY-MM-DD.log`
2. Enable debug mode temporarily in `.env`: `database.default.DBDebug = true`
3. Check GoDaddy error logs in cPanel
4. Verify PHP version meets requirements (8.1+)

---

**Note:** CodeIgniter 4 doesn't require a traditional "installation" - once files are uploaded, `.env` is configured, encryption key is set, and permissions are correct, it should work immediately.






