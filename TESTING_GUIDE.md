# CI4 Backend Testing Guide

## Quick Start

### 1. Test CI4 Backend Without Frontend (Recommended First)

```bash
# Start CI4 server
cd /Applications/MAMP/htdocs/rista_ci4
php spark serve
# Server runs at http://localhost:8080

# Or use MAMP (point to rista_ci4/public)
# Access at http://localhost:8888/rista_ci4/public
```

### 2. Run Automated Tests

```bash
# Run the test script
cd /Applications/MAMP/htdocs/rista_ci4
./test-backend.sh

# Or run PHPUnit tests
php vendor/bin/phpunit
```

### 3. Test with Postman or curl

Import this collection or use these curl commands:

**Get Admin Token:**
```bash
curl -X GET http://localhost:8888/rista_ci4/public/auth/token
```

**Test User Login:**
```bash
curl -X POST http://localhost:8888/rista_ci4/public/user/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

**Test School List (with token):**
```bash
# Replace YOUR_TOKEN with actual token from above
curl -X POST http://localhost:8888/rista_ci4/public/school/list \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{}'
```

## Frontend Integration Testing

### Option A: Parallel Testing (Both CI3 and CI4 Running)

Keep CI3 running and test CI4 separately:

**CI3 (Old):**
- URL: `http://localhost:8888/rista/api/index.php/v1/`
- Frontend: Currently points here
- Status: Keep running as fallback

**CI4 (New):**
- URL: `http://localhost:8888/rista_ci4/public/`
- Frontend: Will switch to this
- Status: Ready for testing

### Option B: Switch Web Portal Only (Incremental)

**Step 1:** Test web portal with CI4

```bash
cd /Applications/MAMP/htdocs/edquill-web/web
```

Update `src/environments/environment.ts`:
```typescript
// Change this line:
apiHost: 'http://localhost:8888/rista_ci4/public/',
```

**Step 2:** Start web portal
```bash
npm start
# Access at http://localhost:8211
```

**Step 3:** Test functionality
- ✅ User login
- ✅ Student list
- ✅ Teacher list
- ✅ Content operations
- ✅ File uploads

**Keep admin portal on CI3** for now:
```typescript
// admin/src/environments/environment.ts - DON'T CHANGE YET
apiHost: 'http://localhost:8888/rista/api/index.php/admin/',
```

### Option C: Switch Both Portals (Full Migration)

**Update both environment files:**

**Web Portal:**
```bash
cd /Applications/MAMP/htdocs/edquill-web/web
```

Edit `src/environments/environment.ts`:
```typescript
apiHost: 'http://localhost:8888/rista_ci4/public/',
```

**Admin Portal:**
```bash
cd /Applications/MAMP/htdocs/edquill-web/admin
```

Edit `src/environments/environment.ts`:
```typescript
apiHost: 'http://localhost:8888/rista_ci4/public/',
```

## Recommended Testing Sequence

### Phase 1: Backend Only Testing (Day 1)
```bash
# 1. Run test script
./test-backend.sh

# 2. Manual API testing with Postman/curl
# Test all critical endpoints

# 3. Run unit tests
php vendor/bin/phpunit
```

### Phase 2: Web Portal Testing (Day 2-3)
```bash
# 1. Switch web portal to CI4
# Edit: edquill-web/web/src/environments/environment.ts

# 2. Start web portal
cd /Applications/MAMP/htdocs/edquill-web/web
npm start

# 3. Test all user flows:
- User registration
- User login
- Student management
- Teacher management
- Content viewing
- Assignment submission
```

### Phase 3: Admin Portal Testing (Day 4-5)
```bash
# 1. Switch admin portal to CI4
# Edit: edquill-web/admin/src/environments/environment.ts

# 2. Start admin portal
cd /Applications/MAMP/htdocs/edquill-web/admin
npm start

# 3. Test all admin flows:
- Admin login
- School management
- User management
- Settings management
- Content management
```

### Phase 4: Integration Testing (Day 6-7)
```bash
# Test complete workflows:
- Admin creates school → School appears in web portal
- Admin creates student → Student can login
- Teacher creates assignment → Student receives it
- Student submits work → Teacher can grade it
```

## Testing Checklist

### Backend API Tests
- [ ] GET /auth/token returns valid token
- [ ] POST /user/login works with valid credentials
- [ ] POST /user/login fails with invalid credentials
- [ ] Protected endpoints require token
- [ ] Admin endpoints require admin role
- [ ] CORS headers are present
- [ ] File upload works
- [ ] Database queries execute

### Frontend Integration Tests

**Web Portal:**
- [ ] User can login
- [ ] User can view profile
- [ ] Student list loads
- [ ] Teacher list loads
- [ ] Content displays correctly
- [ ] Forms submit successfully
- [ ] File uploads work
- [ ] Logout works

**Admin Portal:**
- [ ] Admin can login
- [ ] Dashboard loads
- [ ] School CRUD works
- [ ] Student CRUD works
- [ ] Teacher CRUD works
- [ ] Settings update works
- [ ] Reports generate
- [ ] Logout works

## Rollback Plan

If issues occur, quickly rollback:

**1. Revert Frontend Environment Files:**
```bash
# Web portal
cd /Applications/MAMP/htdocs/edquill-web/web
# Change back to: apiHost: 'http://localhost:8888/rista/api/index.php/v1/'

# Admin portal
cd /Applications/MAMP/htdocs/edquill-web/admin
# Change back to: apiHost: 'http://localhost:8888/rista/api/index.php/admin/'
```

**2. Restart Frontend:**
```bash
# Kill and restart both portals
npm start
```

**3. CI3 is still running** - No backend changes needed!

## Common Issues & Solutions

### Issue: "404 Not Found"
**Solution:** Check that you're using the correct URL:
- CI4: `http://localhost:8888/rista_ci4/public/`
- Not: `http://localhost:8888/rista_ci4/`

### Issue: "CORS Error"
**Solution:** Check `.env` file has correct origins:
```env
cors.allowedOrigins = 'http://localhost:8211,http://localhost:4211'
```

### Issue: "Token Invalid"
**Solution:** 
1. Get fresh token from `/auth/token`
2. Check JWT key matches in `.env`
3. Verify token timeout hasn't expired

### Issue: "Database Connection Failed"
**Solution:** Check `.env` database credentials:
```env
database.default.hostname = localhost
database.default.database = edquill_production
database.default.username = root
database.default.password = root
database.default.port = 8889
```

## Performance Testing

### Load Test with Apache Bench
```bash
# Test login endpoint
ab -n 100 -c 10 \
   -T "application/json" \
   -p login.json \
   http://localhost:8888/rista_ci4/public/user/login

# Test school list with token
ab -n 100 -c 10 \
   -H "Accesstoken: YOUR_TOKEN" \
   -T "application/json" \
   -p empty.json \
   http://localhost:8888/rista_ci4/public/school/list
```

## Monitoring

### Check Logs
```bash
# CI4 logs
tail -f /Applications/MAMP/htdocs/rista_ci4/writable/logs/log-*.log

# PHP errors
tail -f /Applications/MAMP/logs/php_error.log

# Apache errors
tail -f /Applications/MAMP/logs/apache_error.log
```

### Database Queries
```bash
# Enable query logging in .env
database.default.DBDebug = true

# Check slow queries
tail -f /Applications/MAMP/htdocs/rista_ci4/writable/logs/log-*.log | grep "Query took"
```

## Next Steps After Testing

1. ✅ All backend tests pass
2. ✅ Web portal works with CI4
3. ✅ Admin portal works with CI4
4. ✅ Integration tests pass
5. ✅ Performance acceptable
6. → Deploy to staging
7. → Production deployment

## Support

If you encounter issues:
1. Check this guide first
2. Review CI4 logs
3. Test with curl to isolate frontend vs backend issues
4. Check the MIGRATION_COMPLETE.md for API changes
5. Verify database connection and data

