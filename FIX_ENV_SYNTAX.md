# Fix: .env File Syntax Error

## Problem

Your `.env` file has this line:
```env
session.savePath = WRITEPATH . 'session'
```

**This has unquoted spaces**, which triggers the error:
```
.env values containing spaces must be surrounded by quotes.
```

## Solution: Fix .env File

### Option 1: Quote the Entire Value (Recommended)

Change:
```env
session.savePath = WRITEPATH . 'session'
```

To:
```env
session.savePath = 'WRITEPATH . session'
```

### Option 2: Use Null (Let CodeIgniter Use Default)

Change:
```env
session.savePath = WRITEPATH . 'session'
```

To:
```env
session.savePath = null
```

CodeIgniter will automatically use `WRITEPATH . 'session'` as the default.

### Option 3: Use Actual Path

Change:
```env
session.savePath = WRITEPATH . 'session'
```

To:
```env
session.savePath = '/home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/writable/session'
```

## Corrected .env File

Here's your corrected `.env` file:

```env
# TEST Environment

app.baseURL = 'https://edserver.edquillcrm.com/public/'
app.forceGlobalSecureRequests = true

database.default.hostname = 'localhost'
database.default.database = 'edquill_demo'
database.default.username = 'edquill_demo'
database.default.password = 'edquill_demo2025'
database.default.DBDriver = 'MySQLi'
database.default.port = 3306
database.default.DBDebug = true
database.default.charset = 'utf8mb4'
database.default.DBCollat = 'utf8mb4_general_ci'

# Generate key using: php spark key:generate
encryption.key = 'your-generated-key-here'

session.driver = 'CodeIgniter\Session\Handlers\FileHandler'
session.cookieName = 'edquill_test_session'
session.expiration = 7200
session.savePath = null
session.matchIP = false
session.timeToUpdate = 300
session.regenerateDestroy = false

cors.allowedOrigins = 'https://demo.edquillcrm.com'
logger.threshold = 4
```

**Key change:** `session.savePath = null` (or quote it if you want to specify)

## How to Fix

### Via cPanel File Manager:

1. Navigate to `rista_ci4/` directory
2. Open `.env` file
3. Find the line: `session.savePath = WRITEPATH . 'session'`
4. Change to: `session.savePath = null`
5. Save

### Via SSH:

```bash
cd /home/gy8f5ipoapsn/public_html/edquillcrmlms/rista_ci4/

# Edit .env file
nano .env
# OR
vi .env

# Find and change:
# session.savePath = WRITEPATH . 'session'
# To:
# session.savePath = null

# Save and exit
```

## Also Check Encryption Key

Make sure you've replaced:
```env
encryption.key = 'your-generated-key-here'
```

With an actual generated key:
```env
encryption.key = 'your-actual-64-character-hex-key'
```

## After Fixing

1. **Save the .env file**

2. **Clear cache:**
   ```bash
   rm -rf writable/cache/*
   ```

3. **Test your API:**
   ```
   POST https://edserver.edquillcrm.com/public/user/login
   ```

## Why This Happened

The DotEnv parser in CodeIgniter 4 is strict:
- Values with spaces **must** be quoted
- Unquoted values cannot contain spaces
- `WRITEPATH . 'session'` has spaces, so it needs quotes or should be `null`

## Summary

**Change this line:**
```env
session.savePath = WRITEPATH . 'session'
```

**To:**
```env
session.savePath = null
```

**Or:**
```env
session.savePath = 'WRITEPATH . session'
```

Then save and test again!








