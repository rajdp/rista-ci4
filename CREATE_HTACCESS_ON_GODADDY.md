# How to Create .htaccess File on GoDaddy

The `.htaccess` file is **critical** for CodeIgniter 4 to work. Without it, you'll get 403 Forbidden errors.

## Why .htaccess is Missing

Files starting with a dot (`.htaccess`) are often:
- Hidden files that don't show in file managers
- Filtered out during FTP uploads
- Not included in ZIP file extractions

## Solution: Create .htaccess on GoDaddy

### Method 1: Via cPanel File Manager (Easiest)

1. **Log into GoDaddy cPanel**

2. **Open File Manager**
   - Navigate to: `public_html/rista_ci4/public/` (or wherever your `public/` folder is)

3. **Create New File**
   - Click **+ File** or **New File** button
   - Name it: `.htaccess` (with the dot at the beginning)
   - **Important:** Some file managers require you to name it `htaccess` first, then rename it to `.htaccess`

4. **Edit the File**
   - Right-click `.htaccess` → **Edit**
   - Copy and paste the contents from the `.htaccess` file I created
   - Save

5. **Verify**
   - Make sure the file is named exactly `.htaccess` (starts with a dot)
   - File should be in: `public/` directory (same folder as `index.php`)

### Method 2: Via FTP/SFTP

1. **Create file locally**
   - Copy the `.htaccess` content I provided
   - Save it as `.htaccess` (make sure it starts with a dot)

2. **Upload via FTP**
   - Use an FTP client (FileZilla, WinSCP, etc.)
   - Navigate to: `public/` directory
   - Upload `.htaccess`
   - **Important:** Enable "Show hidden files" in your FTP client

3. **Set Permissions**
   - Right-click file → Properties/Permissions
   - Set to: `644`

### Method 3: Via SSH/Terminal

1. **SSH into GoDaddy server**
   ```bash
   ssh your_username@your_server
   ```

2. **Navigate to public directory**
   ```bash
   cd public_html/rista_ci4/public
   # OR wherever your public folder is
   ```

3. **Create .htaccess file**
   ```bash
   nano .htaccess
   # OR
   vi .htaccess
   ```

4. **Paste the contents** (copy from the `.htaccess` file I created)

5. **Save and exit**
   - In nano: `Ctrl+X`, then `Y`, then `Enter`
   - In vi: Press `Esc`, type `:wq`, press `Enter`

6. **Set permissions**
   ```bash
   chmod 644 .htaccess
   ```

### Method 4: Rename Existing File

If you have a file named `htaccess` (without the dot):

1. **Via File Manager:**
   - Right-click `htaccess` → **Rename**
   - Change to: `.htaccess`

2. **Via SSH:**
   ```bash
   cd public_html/rista_ci4/public
   mv htaccess .htaccess
   ```

## Verify .htaccess is Working

### Test 1: Check File Exists
```bash
# Via SSH
ls -la public/.htaccess
# Should show: -rw-r--r-- .htaccess
```

### Test 2: Access Your Site
```
https://edserver.edquillcrm.com/public/
```

**If it works:**
- ✅ `.htaccess` is working
- You should see CodeIgniter welcome page or your API response

**If you still get 403:**
- Check file permissions: `chmod 644 .htaccess`
- Check file location (must be in `public/` folder)
- Check if mod_rewrite is enabled on GoDaddy

## .htaccess File Location

**Correct location:**
```
rista_ci4/
└── public/
    ├── index.php    ← Entry point
    └── .htaccess    ← Must be here (same folder as index.php)
```

**Wrong locations:**
- ❌ `rista_ci4/.htaccess` (root folder)
- ❌ `public_html/.htaccess` (wrong level)

## Troubleshooting

### Issue: File Manager won't create .htaccess

**Solution:**
1. Create file named `htaccess` (without dot)
2. Add content
3. Rename to `.htaccess`

### Issue: File exists but still 403 error

**Check:**
1. File permissions: `chmod 644 .htaccess`
2. File location: Must be in `public/` folder
3. mod_rewrite enabled: Contact GoDaddy support

### Issue: Can't see .htaccess in File Manager

**Solution:**
- Enable "Show Hidden Files" in File Manager settings
- Or use SSH to verify: `ls -la public/`

## Quick Copy-Paste .htaccess Content

Here's the content to paste into your `.htaccess` file:

```apache
# CodeIgniter 4 .htaccess for GoDaddy
# This file routes all requests through index.php

# Disable directory browsing
Options -Indexes

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

# If mod_rewrite is not available
<IfModule !mod_rewrite.c>
    ErrorDocument 404 /index.php
</IfModule>

# CORS for static PDF files (if needed)
<IfModule mod_headers.c>
    <FilesMatch "\.(pdf)$">
        Header always set Access-Control-Allow-Origin "*"
        Header always set Access-Control-Allow-Methods "GET, OPTIONS"
        Header always set Access-Control-Allow-Headers "Content-Type, Accept, Origin, Range"
        Header always set Access-Control-Expose-Headers "Accept-Ranges, Content-Encoding, Content-Length, Content-Range"
    </FilesMatch>
</IfModule>
```

## After Creating .htaccess

1. **Test your site:**
   ```
   https://edserver.edquillcrm.com/public/
   ```

2. **If still 403, check:**
   - File permissions: `chmod 644 .htaccess`
   - mod_rewrite enabled (contact GoDaddy if needed)

3. **Check error logs:**
   - GoDaddy cPanel → Metrics → Errors
   - Or: `writable/logs/log-*.log`

---

**The .htaccess file is ESSENTIAL** - without it, CodeIgniter 4 cannot route requests properly and you'll get 403 errors.






