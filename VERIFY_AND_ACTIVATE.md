# Verify & Activate EdQuill V2 Changes

## ✅ File Locations Confirmed

### Backend Files (rista_ci4 folder)
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Commands/OutboxWorker.php`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Controllers/Admin/Dashboard.php`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Controllers/Admin/SelfRegistration.php` (enhanced)
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Database/Migrations/2025-11-13-000000_CreateEdQuillV2SchoolScopedTables.php`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Database/Migrations/2025-11-13-000001_AddEdQuillV2Indexes.php`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Database/SQL/triggers_outbox.sql`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Services/EventHandlers.php`
- ✅ `/Applications/MAMP/htdocs/rista_ci4/app/Services/MessagingService.php`

### Frontend Files (edquill-web_angupgrade/web folder)
- ✅ `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/components/admin/admin-dashboard/admin-dashboard.component.ts`
- ✅ `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/components/admin/admin-dashboard/admin-dashboard.component.html`
- ✅ `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/components/admin/admin-dashboard/admin-dashboard.component.scss`
- ✅ `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/components/admin/admin-dashboard/admin-dashboard.routes.ts`
- ✅ `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/shared/service/dashboard.service.ts`

---

## 🔧 Steps to See the Changes

### Step 1: Activate Backend Changes

#### A. Run Database Migrations
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark migrate
```

**Expected Output:**
```
Running: App\Database\Migrations\2025-11-13-000000_CreateEdQuillV2SchoolScopedTables
Running: App\Database\Migrations\2025-11-13-000001_AddEdQuillV2Indexes
```

#### B. Load Triggers
```bash
cd /Applications/MAMP/htdocs/rista_ci4
mysql -u root -p your_database_name < app/Database/SQL/triggers_outbox.sql
```

**Verify triggers loaded:**
```sql
SHOW TRIGGERS LIKE 'student_self_registrations';
```
You should see:
- `trg_ssr_status_outbox`
- `trg_ssr_converted_outbox`

#### C. Verify Tables Created
```sql
SHOW TABLES LIKE 't_%';
```

You should see:
- `t_event_outbox`
- `t_audit_log`
- `t_feature_flag`
- `t_message_template`
- `t_message_log`
- `t_marketing_kpi_daily`
- `t_revenue_daily`

#### D. Test API Endpoint
```bash
# Test dashboard endpoint (replace YOUR_TOKEN with actual token)
curl -X POST http://localhost/api/dashboard \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{"from":"2025-11-01","to":"2025-11-13"}'
```

**Expected Response:**
```json
{
  "IsSuccess": true,
  "ResponseObject": {
    "tiles": { ... },
    "revenue": { ... },
    "period": { ... }
  }
}
```

---

### Step 2: Activate Frontend Changes

#### A. Restart Angular Dev Server
```bash
cd /Applications/MAMP/htdocs/edquill-web_angupgrade/web

# Stop current server (Ctrl+C if running)

# Start fresh
npm start
# or
npm run start:local
```

**Wait for compilation to complete** (look for "Compiled successfully")

#### B. Navigate to Admin Dashboard
Once the dev server is running:

1. **Open browser**: `http://localhost:8211` (or your configured port)
2. **Login** with admin credentials
3. **Navigate to**: `http://localhost:8211/#/admin/dashboard`

**Alternative navigation:**
- Look for "Admin" menu item in sidebar
- Click "Admin Dashboard" or navigate directly to `/admin/dashboard`

#### C. Verify Component Loads
You should see:
- Header: "Admin Dashboard"
- Date range picker (From/To)
- 14 KPI tiles in a grid
- Revenue summary card
- Quick Actions section with 4 action buttons

---

## 🧪 Quick Verification Tests

### Test 1: Verify Backend API Works
```bash
# From terminal
curl -X POST http://localhost/api/dashboard \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{}'
```

### Test 2: Verify Frontend Route
1. Open browser console (F12)
2. Navigate to `/admin/dashboard`
3. Check for:
   - No 404 errors
   - Component loads
   - API call made to `/api/dashboard`

### Test 3: Verify Outbox Worker
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark outbox:worker
```

You should see:
```
Outbox Worker started (ID: hostname-pid)
Batch size: 50, Sleep: 250ms
```

---

## 🐛 Troubleshooting

### Issue: "Cannot find module" error in Angular
**Solution:**
```bash
cd /Applications/MAMP/htdocs/edquill-web_angupgrade/web
rm -rf node_modules/.cache
npm start
```

### Issue: Dashboard shows "Unable to load dashboard data"
**Check:**
1. Backend API is running
2. Migrations have been run
3. Token is valid
4. Check browser console for API errors
5. Check backend logs: `tail -f writable/logs/log-*.php`

### Issue: Route not found (404)
**Check:**
1. Admin routing module has dashboard route (✅ already added)
2. Dev server was restarted after adding route
3. URL is correct: `/admin/dashboard` (not `/admin-dashboard`)

### Issue: Tables don't exist
**Solution:**
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark migrate
php spark migrate:status  # Verify migrations ran
```

### Issue: Triggers not working
**Solution:**
```bash
# Reload triggers
mysql -u root -p your_database < app/Database/SQL/triggers_outbox.sql

# Verify
mysql -u root -p your_database -e "SHOW TRIGGERS LIKE 'student_self_registrations';"
```

---

## 📍 Direct Access URLs

After setup, you can access:

### Frontend
- **Admin Dashboard**: `http://localhost:8211/#/admin/dashboard`
- **CRM Registrations**: `http://localhost:8211/#/crm/registrations`

### Backend APIs
- **Dashboard API**: `POST http://localhost/api/dashboard`
- **Registrar APIs**: `POST http://localhost/admin/self-registration/*`

---

## ✅ Verification Checklist

### Backend
- [ ] Migrations run: `php spark migrate`
- [ ] Tables exist: `SHOW TABLES LIKE 't_%';`
- [ ] Triggers loaded: `SHOW TRIGGERS;`
- [ ] API responds: Test `/api/dashboard` endpoint
- [ ] Worker starts: `php spark outbox:worker`

### Frontend
- [ ] Dev server restarted: `npm start`
- [ ] Route accessible: Navigate to `/admin/dashboard`
- [ ] Component loads: No console errors
- [ ] API calls work: Check Network tab in browser
- [ ] Data displays: KPI tiles show values

---

## 🎯 Expected Results

### Admin Dashboard Page Should Show:
1. **Header**: "Admin Dashboard" with date range picker
2. **14 KPI Tiles**: Leads, Enrollments, Conversion Rate, etc.
3. **Revenue Summary**: MRR, ARR, Overdue amounts
4. **Quick Actions**: 4 action buttons (Run Autopay, Nudge Idle, etc.)

### Backend Should Have:
1. **7 New Tables**: All `t_*` tables created
2. **2 Triggers**: On `student_self_registrations`
3. **New API Endpoint**: `/api/dashboard` returns data
4. **Worker Command**: `php spark outbox:worker` runs

---

## 📞 Still Not Working?

1. **Check file locations** - Verify files exist in correct folders
2. **Check console logs** - Browser console and backend logs
3. **Verify database** - Tables and triggers exist
4. **Restart services** - Both Angular dev server and PHP backend
5. **Clear caches** - Browser cache, Angular build cache

If issues persist, check:
- `RUNBOOK_EDQUILL_V2.md` for detailed troubleshooting
- Browser console for JavaScript errors
- Backend logs: `writable/logs/log-*.php`
- Network tab for API call failures

