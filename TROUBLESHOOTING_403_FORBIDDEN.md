# Fixing 403 Forbidden Error on GoDaddy

## Common Causes & Solutions

### Solution 1: Check File Permissions (Most Common)

**Problem:** GoDaddy requires specific file permissions.

**Fix via SSH:**
```bash
cd /path/to/rista_ci4

# Set directory permissions
find . -type d -exec chmod 755 {} \;

# Set file permissions
find . -type f -exec chmod 644 {} \;

# Make index.php executable (if needed)
chmod 644 public/index.php

# Ensure public directory is accessible
chmod 755 public/
```

**Fix via cPanel File Manager:**
1. Navigate to `public/` directory
2. Right-click `index.php` → **Change Permissions**
3. Set to **644**
4. Navigate to `public/` folder itself
5. Set folder permissions to **755**

### Solution 2: Verify Directory Structure

**Check your GoDaddy file structure:**

```
public_html/
  └── rista_ci4/
      ├── app/
      ├── public/
      │   ├── index.php  ← Must exist
      │   └── .htaccess  ← Must exist
      ├── writable/
      └── .env
```

**If files are in wrong location:**
- Ensure `public/index.php` exists
- Ensure `public/.htaccess` exists
- Your URL should point to `public/` directory

### Solution 3: Update .htaccess for GoDaddy

GoDaddy sometimes has issues with certain .htaccess directives. Try this simplified version:

**Create/Update `public/.htaccess`:**
```apache
# Disable directory browsing
Options -Indexes

# Enable Rewrite Engine
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Set base path (adjust if your public folder is in a subdirectory)
    # RewriteBase /rista_ci4/public/
    
    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    
    # Redirect to index.php if file doesn't exist
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ index.php/$1 [L]
</IfModule>

# If mod_rewrite is not available
<IfModule !mod_rewrite.c>
    ErrorDocument 404 /index.php
</IfModule>
```

### Solution 4: Check GoDaddy Directory Index Settings

**In cPanel:**
1. Go to **Directory Privacy** or **Indexes**
2. Ensure directory listing is enabled for `public/` folder
3. Or ensure `index.php` is set as default index file

### Solution 5: Verify index.php Path

**Check if `public/index.php` exists and is accessible:**

Try accessing directly:
```
https://edserver.edquillcrm.com/public/index.php
```

If this works but `/public/` doesn't, it's an .htaccess issue.

### Solution 6: Temporary .htaccess Bypass (For Testing)

**Temporarily rename `.htaccess` to test:**
```bash
cd public/
mv .htaccess .htaccess.backup
```

Then try accessing:
```
https://edserver.edquillcrm.com/public/
```

If it works, the issue is with `.htaccess`. Restore it and fix the rewrite rules.

### Solution 7: Check GoDaddy Security Settings

**In cPanel:**
1. Go to **Security** → **Hotlink Protection**
2. Ensure your domain is not blocked
3. Check **IP Deny Manager** - ensure your IP isn't blocked

### Solution 8: Verify Document Root

**Check if your domain points to correct directory:**

In cPanel → **File Manager**:
- Check where your domain's document root is
- If it's `public_html/`, then your path should be:
  - `public_html/rista_ci4/public/`
- If it's `public_html/rista_ci4/`, adjust accordingly

---

## Step-by-Step Diagnostic Process

### Step 1: Test Basic Access
```bash
# Try accessing index.php directly
https://edserver.edquillcrm.com/public/index.php
```

**If this works:**
- Issue is with `.htaccess` or directory routing
- Proceed to Solution 3

**If this doesn't work:**
- Issue is with file permissions or path
- Proceed to Solution 1

### Step 2: Check File Permissions
```bash
# Via SSH, check permissions
ls -la public/
ls -la public/index.php
```

**Should show:**
```
drwxr-xr-x  public/
-rw-r--r--  index.php
```

### Step 3: Check Error Logs
```bash
# Check GoDaddy error logs in cPanel
# Or check CodeIgniter logs
cat writable/logs/log-*.log
```

### Step 4: Test with Simple PHP File

**Create `public/test.php`:**
```php
<?php
phpinfo();
?>
```

Access: `https://edserver.edquillcrm.com/public/test.php`

**If this works:**
- PHP is working
- Issue is with CodeIgniter configuration

**If this doesn't work:**
- PHP might not be enabled
- Check PHP version in cPanel

---

## Quick Fix Checklist

Try these in order:

- [ ] Check file permissions (755 for dirs, 644 for files)
- [ ] Verify `public/index.php` exists
- [ ] Verify `public/.htaccess` exists
- [ ] Try accessing `index.php` directly
- [ ] Temporarily disable `.htaccess` to test
- [ ] Check GoDaddy error logs
- [ ] Verify PHP version (8.1+)
- [ ] Check directory structure matches expected path

---

## GoDaddy-Specific Issues

### Issue: "Options -Indexes" causing problems

**Fix:** Remove or comment out:
```apache
# Options -Indexes
```

### Issue: mod_rewrite not enabled

**Fix:** Contact GoDaddy support or use alternative routing method.

### Issue: PHP version mismatch

**Fix:** 
1. cPanel → **Select PHP Version**
2. Choose PHP 8.1 or higher
3. Ensure `index.php` is in handler list

---

## Alternative: Use Subdirectory Structure

If the above doesn't work, you might need to adjust your structure:

**Option A: Move public contents to root**
```
public_html/
  ├── index.php (from public/)
  ├── .htaccess (from public/)
  └── assets/ (if any)
```

**Option B: Adjust baseURL in .env**
```env
app.baseURL = 'https://edserver.edquillcrm.com/'
```

And update `public/index.php` path reference if needed.

---

## Still Not Working?

1. **Check GoDaddy Error Logs:**
   - cPanel → **Metrics** → **Errors**
   - Look for specific error messages

2. **Contact GoDaddy Support:**
   - Ask about mod_rewrite availability
   - Ask about PHP version
   - Ask about directory permissions

3. **Enable Debug Mode:**
   - In `.env`: `database.default.DBDebug = true`
   - Check `writable/logs/` for detailed errors

---

## Quick Test Commands (SSH)

```bash
# 1. Check if files exist
ls -la public/index.php
ls -la public/.htaccess

# 2. Check permissions
stat public/index.php
stat public/

# 3. Test PHP
php -v
php public/index.php

# 4. Check error logs
tail -f writable/logs/log-*.log
```

---

**Most Common Fix:** File permissions (Solution 1) resolves 90% of 403 errors on GoDaddy.






