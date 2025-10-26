# Quick Testing Guide - CI4 Backend

## ✅ Backend is Now Working!

The CI4 backend is now accessible at: `http://localhost:8888/rista_ci4/public/`

## Test the Backend (Without Frontend)

### 1. Test Admin Token Generation
```bash
curl http://localhost:8888/rista_ci4/public/auth/token
```

**Expected Response:**
```json
{
    "IsSuccess": true,
    "ResponseObject": {
        "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
        "user": {
            "id": 1,
            "role": "admin",
            "school_id": 1,
            "timestamp": 1761242117
        }
    },
    "ErrorObject": ""
}
```

### 2. Test with Full URL (What Your Frontend Uses)
```bash
curl http://localhost:8888/rista_ci4/public/auth/token
curl http://localhost:8888/rista_ci4/public/common/countries
curl http://localhost:8888/rista_ci4/public/common/states
```

## Test with Frontend

### Web Portal
```bash
cd /Applications/MAMP/htdocs/edquill-web/web
npm start
```

Then visit: `http://localhost:8211`

### Admin Portal
```bash
cd /Applications/MAMP/htdocs/edquill-web/admin
npm start
```

Then visit: `http://localhost:4211`

## What's Working

✅ **CI4 Backend** - Fully operational at `http://localhost:8888/rista_ci4/public/`
✅ **Admin Auth** - Token generation working
✅ **CORS** - Headers configured correctly
✅ **Routes** - Flat routing structure working
✅ **Controllers** - Admin controllers responding
✅ **JWT** - Authentication working

## Frontend Configuration Status

✅ **Web Portal** - Configured to use CI4 (`http://localhost:8888/rista_ci4/public/`)
✅ **Admin Portal** - Configured to use CI4 (`http://localhost:8888/rista_ci4/public/`)

**IMPORTANT:** Restart your Angular applications after environment file changes:
```bash
# Kill existing processes
lsof -ti:8211 | xargs kill -9  # Web portal
lsof -ti:4211 | xargs kill -9  # Admin portal

# Or use the restart script
cd /Applications/MAMP/htdocs/edquill-web
./restart-portals.sh
```

## Common Issues

### Issue: "404 Not Found" or requests going to wrong URL

**Solution:** 
1. Make sure you're using the FULL URL including `/index.php/`:
   - ✅ `http://localhost:8888/rista_ci4/public/index.php/auth/token`
   - ❌ `http://localhost:8888/rista_ci4/public/auth/token`

2. Or configure Apache to rewrite URLs (already done in `.htaccess`)

### Issue: Frontend still hitting CI3

**Solution:**
1. Check environment files are updated
2. **Restart Angular applications** (very important!)
3. Clear browser cache
4. Check Network tab in browser DevTools

### Issue: CORS errors

**Solution:**
1. Check allowed origins in `/Applications/MAMP/htdocs/rista_ci4/.env`:
   ```env
   cors.allowedOrigins = 'http://localhost:8211,http://localhost:4211'
   ```

2. Restart MAMP if you changed `.env`

## Next Steps

1. ✅ Backend is working - Test it with curl
2. 🔄 Restart your Angular applications
3. 🧪 Test login from the frontend
4. ✅ Monitor browser Network tab to see requests going to CI4
5. 📝 Report any issues

## Quick Commands

```bash
# Test backend
curl http://localhost:8888/rista_ci4/public/auth/token

# Check what backend frontend is pointing to
cd /Applications/MAMP/htdocs/edquill-web
./check-backend.sh

# Restart frontend portals
cd /Applications/MAMP/htdocs/edquill-web
./restart-portals.sh
```

## URL Structure

**CI3 (Old):**
- Web: `http://localhost:8888/rista/api/index.php/v1/user/login`
- Admin: `http://localhost:8888/rista/api/index.php/admin/auth/token`

**CI4 (New):**
- Web: `http://localhost:8888/rista_ci4/public/user/login`
- Admin: `http://localhost:8888/rista_ci4/public/auth/token`

## Success Indicators

When your frontend is working with CI4:
- ✅ Network tab shows requests to `/rista_ci4/public/`
- ✅ Login returns JWT token
- ✅ Protected endpoints require `Accesstoken` header
- ✅ No CORS errors in console
- ✅ Data loads correctly

## Support

If issues persist:
1. Check this guide
2. Review `/Applications/MAMP/htdocs/rista_ci4/writable/logs/`
3. Check Apache error logs
4. Verify database connection in `/Applications/MAMP/htdocs/rista_ci4/.env`

