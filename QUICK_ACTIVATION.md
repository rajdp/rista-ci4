# Quick Activation Guide - EdQuill V2

## ✅ Files Are in Correct Locations

**Backend**: `/Applications/MAMP/htdocs/rista_ci4/` ✅  
**Frontend**: `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/` ✅

---

## 🚀 Quick Activation (3 Steps)

### Step 1: Activate Backend (Run Migrations & Triggers)

```bash
cd /Applications/MAMP/htdocs/rista_ci4

# Option A: Use activation script
./activate-v2.sh

# Option B: Manual steps
php spark migrate
mysql -u root -p your_database < app/Database/SQL/triggers_outbox.sql
```

**Verify:**
```bash
php spark migrate:status | grep "2025-11-13"
# Should show the 2 new migrations
```

### Step 2: Restart Angular Dev Server

```bash
cd /Applications/MAMP/htdocs/edquill-web_angupgrade/web

# Stop current server (Ctrl+C)
# Then restart:
npm start
```

**Wait for**: "Compiled successfully" message

### Step 3: Access Admin Dashboard

1. **Open browser**: `http://localhost:8211`
2. **Login** with admin credentials
3. **Navigate to**: `http://localhost:8211/#/admin/dashboard`

**OR** look for "Admin Dashboard" in the navigation menu

---

## 🔍 Verify Files Exist

### Backend Verification
```bash
cd /Applications/MAMP/htdocs/rista_ci4

# Check migrations
ls -la app/Database/Migrations/ | grep "2025-11-13"

# Check worker
ls -la app/Commands/OutboxWorker.php

# Check controllers
ls -la app/Controllers/Admin/Dashboard.php
ls -la app/Controllers/Admin/SelfRegistration.php

# Check services
ls -la app/Services/EventHandlers.php
ls -la app/Services/MessagingService.php
```

### Frontend Verification
```bash
cd /Applications/MAMP/htdocs/edquill-web_angupgrade/web

# Check admin dashboard
ls -la src/app/components/admin/admin-dashboard/

# Check service
ls -la src/app/shared/service/dashboard.service.ts

# Check routing
grep -A 5 "dashboard" src/app/components/admin/admin-routing.module.ts
```

---

## 🧪 Test Backend API

```bash
# Test dashboard endpoint (replace YOUR_TOKEN)
curl -X POST http://localhost/api/dashboard \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{"from":"2025-11-01","to":"2025-11-13"}'
```

**Expected**: JSON response with `IsSuccess: true` and dashboard data

---

## 🐛 Common Issues & Solutions

### Issue: "Migration not found"
**Solution**: Files are there, just need to run:
```bash
php spark migrate
```

### Issue: "Route not found" in Angular
**Solution**: 
1. Restart dev server: `npm start`
2. Clear browser cache
3. Check URL: `/admin/dashboard` (not `/admin-dashboard`)

### Issue: "Cannot GET /api/dashboard"
**Solution**: 
1. Check backend is running
2. Verify route in `app/Config/Routes.php`
3. Check authentication token

### Issue: Dashboard shows empty/loading
**Solution**:
1. Check browser console (F12) for errors
2. Check Network tab - is API call successful?
3. Verify backend API works (use curl test above)
4. Check backend logs: `tail -f writable/logs/log-*.php`

---

## 📍 Direct URLs After Activation

- **Admin Dashboard**: `http://localhost:8211/#/admin/dashboard`
- **CRM Registrations**: `http://localhost:8211/#/crm/registrations`
- **Backend API**: `POST http://localhost/api/dashboard`

---

## ✅ Success Indicators

### Backend Working When:
- ✅ Migrations show as "Migrated On" (not "---")
- ✅ Tables exist: `SHOW TABLES LIKE 't_%';` shows 7 tables
- ✅ Triggers exist: `SHOW TRIGGERS;` shows 2 triggers
- ✅ API responds: `/api/dashboard` returns data

### Frontend Working When:
- ✅ Page loads: `/admin/dashboard` shows dashboard
- ✅ No 404 errors in console
- ✅ API call succeeds (check Network tab)
- ✅ KPI tiles display with data

---

**Need Help?** See `VERIFY_AND_ACTIVATE.md` for detailed troubleshooting.

