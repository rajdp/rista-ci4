# EdQuill V2 Quick Start Guide

## 🚀 Quick Deployment (5 Minutes)

### Step 1: Database Setup
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark migrate
mysql -u root -p your_database < app/Database/SQL/triggers_outbox.sql
```

### Step 2: Start Worker (Development)
```bash
php spark outbox:worker
```

### Step 3: Verify Setup
```sql
-- Check tables created
SHOW TABLES LIKE 't_%';

-- Check triggers
SHOW TRIGGERS LIKE 'student_self_registrations';

-- Check outbox (should be empty initially)
SELECT COUNT(*) FROM t_event_outbox WHERE processed_at IS NULL;
```

### Step 4: Test Flow
1. Update a registration status via API
2. Check outbox: `SELECT * FROM t_event_outbox ORDER BY created_at DESC LIMIT 5;`
3. Verify worker processed it: `SELECT * FROM t_event_outbox WHERE processed_at IS NOT NULL;`

---

## 📍 Access Points

### Backend APIs
- **Registrar Workspace**: `POST /admin/self-registration/*`
- **Admin Dashboard**: `GET/POST /api/dashboard`
- **Assign Class**: `POST /admin/self-registration/assign-class`
- **Approve**: `POST /admin/self-registration/approve`

### Frontend Routes
- **Admin Dashboard**: `/admin/dashboard`
- **CRM Registrations**: `/crm/registrations`

---

## 🔧 Production Setup

### Supervisor Configuration
```ini
[program:edquill-outbox-worker]
command=php /Applications/MAMP/htdocs/rista_ci4/spark outbox:worker
autostart=true
autorestart=true
numprocs=2
```

### Cron Jobs
```bash
# Reset stuck claims (every 15 minutes)
*/15 * * * * mysql -u root -p'password' your_database -e "UPDATE t_event_outbox SET claimed_by=NULL, claimed_at=NULL WHERE processed_at IS NULL AND claimed_at < NOW() - INTERVAL 15 MINUTE;"

# Daily KPI aggregation (2 AM daily)
0 2 * * * php /Applications/MAMP/htdocs/rista_ci4/spark kpi:daily-aggregate
```

---

## ✅ Verification Checklist

- [ ] Migrations run successfully
- [ ] Triggers loaded
- [ ] Worker starts without errors
- [ ] API endpoints respond correctly
- [ ] Frontend routes accessible
- [ ] Dashboard loads data
- [ ] Status changes create outbox events
- [ ] Worker processes events

---

## 🐛 Troubleshooting

**Worker not processing?**
```sql
-- Check for stuck claims
SELECT * FROM t_event_outbox 
WHERE processed_at IS NULL 
AND claimed_at < NOW() - INTERVAL 15 MINUTE;

-- Reset stuck claims
UPDATE t_event_outbox 
SET claimed_by=NULL, claimed_at=NULL 
WHERE processed_at IS NULL 
AND claimed_at < NOW() - INTERVAL 15 MINUTE;
```

**Dashboard not loading?**
- Check browser console for errors
- Verify API endpoint: `curl -X POST http://localhost/api/dashboard -H "Accesstoken: YOUR_TOKEN"`
- Check KPI sinks have data

**Events not enqueueing?**
- Verify triggers exist: `SHOW TRIGGERS LIKE 'student_self_registrations';`
- Test trigger manually by updating a registration status

---

## 📚 Full Documentation

- **Operations**: `RUNBOOK_EDQUILL_V2.md`
- **Backend Details**: `EDQUILL_V2_IMPLEMENTATION_SUMMARY.md`
- **Frontend Guide**: `FRONTEND_IMPLEMENTATION_GUIDE.md`
- **Complete Summary**: `IMPLEMENTATION_COMPLETE.md`

---

**Status**: ✅ Ready for Production

