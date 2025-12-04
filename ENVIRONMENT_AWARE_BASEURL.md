# Environment-Aware BaseURL Configuration

## What Changed

The `App.php` config file now automatically detects the base URL based on the server environment, eliminating the need for hardcoded localhost defaults.

## How It Works

### Priority Order:

1. **`.env` file** - If `app.baseURL` is set in `.env`, it uses that (highest priority)
2. **Auto-detection** - If not in `.env`, it automatically detects from server:
   - Protocol (http/https)
   - Hostname (from HTTP_HOST or SERVER_NAME)
   - Base path (extracted from script path)
3. **Fallback** - Falls back to localhost only for CLI commands

### Benefits:

- ✅ Works automatically on any server (GoDaddy, local, staging, production)
- ✅ No need to change code when deploying
- ✅ Still allows override via `.env` file
- ✅ Detects HTTPS automatically
- ✅ Handles different directory structures

## Configuration

### Option 1: Let It Auto-Detect (Recommended)

**Remove or comment out `app.baseURL` in `.env`:**
```env
# app.baseURL = 'https://edserver.edquillcrm.com/public/'
# Let it auto-detect
```

The system will automatically detect:
- `https://edserver.edquillcrm.com/public/` (on GoDaddy)
- `http://localhost:8888/rista_ci4/public/` (on local MAMP)

### Option 2: Explicitly Set in .env (For Control)

**Set it in `.env` if you want explicit control:**
```env
# Test environment
app.baseURL = 'https://edserver.edquillcrm.com/public/'

# Production environment  
app.baseURL = 'https://yourdomain.com/rista_ci4/public/'
```

## How Auto-Detection Works

The `detectBaseURL()` method:

1. **Checks if CLI** - Returns localhost for command-line operations
2. **Detects Protocol** - Checks HTTPS headers and server variables
3. **Gets Hostname** - From `HTTP_HOST` or `SERVER_NAME`
4. **Extracts Path** - From script path (`/edquillcrmlms/rista_ci4/public/`)
5. **Builds URL** - Combines: `protocol://hostname/path`

### Example Detection:

**On GoDaddy:**
- Script: `/edquillcrmlms/rista_ci4/public/index.php`
- Detects: `https://edserver.edquillcrm.com/edquillcrmlms/rista_ci4/public/`

**On Local MAMP:**
- Script: `/rista_ci4/public/index.php`
- Detects: `http://localhost:8888/rista_ci4/public/`

## Testing

### Test Auto-Detection:

1. **Remove `app.baseURL` from `.env`** (or comment it out)
2. **Access your site** - It should auto-detect
3. **Check logs** - Should work without errors

### Verify It's Working:

Create a test route or check the baseURL in your controller:
```php
$baseURL = config('App')->baseURL;
echo $baseURL; // Should show the detected URL
```

## Troubleshooting

### Issue: Wrong URL Detected

**Solution:** Set it explicitly in `.env`:
```env
app.baseURL = 'https://edserver.edquillcrm.com/public/'
```

### Issue: Still Using Localhost

**Check:**
1. `.env` file has `app.baseURL` set correctly
2. `.env` file is being read (check `CI_ENVIRONMENT` is set)
3. Clear cache: `rm -rf writable/cache/*`

### Issue: Path Detection Wrong

**Solution:** The auto-detection looks for `/public/` in the script path. If your structure is different, set it explicitly in `.env`.

## Migration Guide

### Before (Hardcoded):
```php
public string $baseURL = env('app.baseURL', 'http://localhost:8888/rista_ci4/public/');
```

### After (Auto-Detecting):
```php
public string $baseURL;

public function __construct()
{
    parent::__construct();
    $this->baseURL = env('app.baseURL', $this->detectBaseURL());
}
```

## Best Practices

1. **For Production:** Set explicitly in `.env` for reliability
2. **For Development:** Let it auto-detect for convenience
3. **For Multiple Environments:** Use different `.env` files per environment

## Summary

- ✅ Auto-detects base URL from server
- ✅ Works on any environment (local, test, production)
- ✅ Can still override via `.env`
- ✅ No more hardcoded localhost defaults
- ✅ Handles HTTPS automatically

---

**Your baseURL will now automatically adapt to the environment!**






