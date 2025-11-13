# EdQuill V2 Runbook

## Overview
This runbook covers deployment, operations, and maintenance for EdQuill V2 (School-Scoped, MySQL 5.7) features including:
- Event outbox system
- Registrar workspace
- Admin dashboard
- Messaging automation

---

## 1. Database Migrations

### Run Migrations
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark migrate
```

### Verify Migration Status
```bash
php spark migrate:status
```

### Rollback (if needed)
```bash
php spark migrate:rollback
```

---

## 2. Load Triggers

### Load Outbox Triggers
```bash
cd /Applications/MAMP/htdocs/rista_ci4
mysql -u root -p your_database < app/Database/SQL/triggers_outbox.sql
```

### Verify Triggers
```sql
SHOW TRIGGERS LIKE 'student_self_registrations';
```

### Drop Triggers (if needed)
```sql
DROP TRIGGER IF EXISTS trg_ssr_status_outbox;
DROP TRIGGER IF EXISTS trg_ssr_converted_outbox;
```

---

## 3. Outbox Worker

### Development (Manual Run)
```bash
cd /Applications/MAMP/htdocs/rista_ci4
php spark outbox:worker
```

### Production (Supervisor Configuration)

Create `/etc/supervisor/conf.d/edquill-outbox-worker.conf`:

```ini
[program:edquill-outbox-worker]
command=php /Applications/MAMP/htdocs/rista_ci4/spark outbox:worker --batch-size=50 --sleep-ms=250
directory=/Applications/MAMP/htdocs/rista_ci4
autostart=true
autorestart=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/edquill/outbox-worker.log
stdout_logfile_maxbytes=10MB
stdout_logfile_backups=5
```

### Supervisor Commands
```bash
# Reload supervisor config
sudo supervisorctl reread
sudo supervisorctl update

# Start/stop workers
sudo supervisorctl start edquill-outbox-worker:*
sudo supervisorctl stop edquill-outbox-worker:*

# Check status
sudo supervisorctl status edquill-outbox-worker:*

# View logs
tail -f /var/log/edquill/outbox-worker.log
```

---

## 4. Maintenance Tasks

### Reset Stuck Claims (Run via Cron - Every 15 minutes)

Create cron job:
```bash
*/15 * * * * mysql -u root -p'password' your_database -e "UPDATE t_event_outbox SET claimed_by=NULL, claimed_at=NULL WHERE processed_at IS NULL AND claimed_at < NOW() - INTERVAL 15 MINUTE;"
```

Or create a CI4 command:
```bash
php spark outbox:reset-stuck
```

### Monitor Outbox Queue
```sql
-- Check unprocessed events
SELECT COUNT(*) as unprocessed_count 
FROM t_event_outbox 
WHERE processed_at IS NULL;

-- Check stuck claims
SELECT COUNT(*) as stuck_count 
FROM t_event_outbox 
WHERE processed_at IS NULL 
AND claimed_at < NOW() - INTERVAL 15 MINUTE;

-- View recent events by type
SELECT event_type, COUNT(*) as count 
FROM t_event_outbox 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY event_type;
```

---

## 5. KPI Sink Updates

### Daily KPI Aggregation (Run via Cron - Daily at 2 AM)

```bash
0 2 * * * php /Applications/MAMP/htdocs/rista_ci4/spark kpi:daily-aggregate
```

### Manual KPI Update
```bash
php spark kpi:daily-aggregate --date=2025-11-13
```

---

## 6. Testing & Verification

### Test Status Change → Outbox → Worker Flow

1. **Update a registration status:**
```bash
curl -X POST http://localhost/api/admin/self-registration/status \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{
    "registration_id": 1,
    "status": "in_review"
  }'
```

2. **Check outbox:**
```sql
SELECT * FROM t_event_outbox 
WHERE event_type = 'selfreg.status.updated' 
ORDER BY created_at DESC LIMIT 5;
```

3. **Verify worker processed it:**
```sql
SELECT * FROM t_event_outbox 
WHERE processed_at IS NOT NULL 
ORDER BY processed_at DESC LIMIT 5;
```

### Test Conversion Flow

1. **Convert a registration:**
```bash
curl -X POST http://localhost/api/admin/self-registration/promote \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{
    "registration_id": 1,
    "send_welcome_email": true
  }'
```

2. **Check conversion event:**
```sql
SELECT * FROM t_event_outbox 
WHERE event_type = 'selfreg.converted' 
ORDER BY created_at DESC LIMIT 5;
```

### Test Class Assignment with Conflict Prevention

```bash
curl -X POST http://localhost/api/admin/self-registration/assign-class \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{
    "registration_id": 1,
    "teacher_id": 5,
    "schedule_id": 10
  }'
```

### Test Approval → Invoice Send

```bash
curl -X POST http://localhost/api/admin/self-registration/approve \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN" \
  -d '{
    "registration_id": 1,
    "send_invoice": true,
    "send_autopay_link": true
  }'
```

---

## 7. Performance Monitoring

### Check Query Performance

```sql
-- Explain query for lead queue
EXPLAIN SELECT * FROM student_self_registrations 
WHERE school_id = 1 
AND status = 'pending' 
ORDER BY submitted_at DESC 
LIMIT 25;

-- Check index usage
SHOW INDEX FROM student_self_registrations;
SHOW INDEX FROM t_event_outbox;
```

### Monitor Slow Queries

Enable slow query log in MySQL:
```sql
SET GLOBAL slow_query_log = 'ON';
SET GLOBAL long_query_time = 0.2; -- Log queries > 200ms
```

Check slow queries:
```bash
tail -f /var/log/mysql/slow-query.log
```

---

## 8. Troubleshooting

### Worker Not Processing Events

1. **Check worker is running:**
```bash
ps aux | grep outbox:worker
```

2. **Check for errors:**
```bash
tail -f /var/log/edquill/outbox-worker.log
```

3. **Reset stuck claims:**
```sql
UPDATE t_event_outbox 
SET claimed_by=NULL, claimed_at=NULL 
WHERE processed_at IS NULL 
AND claimed_at < NOW() - INTERVAL 15 MINUTE;
```

### Events Not Being Enqueued

1. **Verify triggers exist:**
```sql
SHOW TRIGGERS LIKE 'student_self_registrations';
```

2. **Test trigger manually:**
```sql
-- Update a registration status
UPDATE student_self_registrations 
SET status = 'in_review' 
WHERE id = 1;

-- Check if event was created
SELECT * FROM t_event_outbox 
WHERE event_type = 'selfreg.status.updated' 
ORDER BY created_at DESC LIMIT 1;
```

### Dashboard Not Loading

1. **Check KPI sinks have data:**
```sql
SELECT * FROM t_marketing_kpi_daily 
WHERE school_id = 1 
ORDER BY day DESC LIMIT 10;

SELECT * FROM t_revenue_daily 
WHERE school_id = 1 
ORDER BY day DESC LIMIT 10;
```

2. **Verify API endpoint:**
```bash
curl -X GET http://localhost/api/dashboard \
  -H "Accesstoken: YOUR_TOKEN" \
  -H "Content-Type: application/json"
```

---

## 9. Demo Script

### 5-8 Minute Demo Flow

1. **Show Registrar Workspace:**
   - Navigate to `/admin/registrar`
   - Show stage board (pending, in_review, approved, etc.)
   - Open a registration detail panel
   - Show Overview, Docs, Class, Fees, History tabs

2. **Change Status → Trigger → Outbox:**
   - Change status from `pending` to `in_review`
   - Show event in `t_event_outbox` table
   - Show worker processing the event
   - Show message sent (if configured)

3. **Assign Class with Conflict Check:**
   - Try to assign teacher to conflicting schedule
   - Show conflict warning
   - Assign to non-conflicting schedule
   - Show success

4. **Approve → Invoice:**
   - Approve registration
   - Show invoice draft created
   - Show invoice email sent
   - Show autopay link (if enabled)

5. **Show Admin Dashboard:**
   - Navigate to `/admin/dashboard`
   - Show KPI tiles (leads, enrollments, conversion rate, etc.)
   - Show action rail
   - Show data from KPI sinks

---

## 10. Environment Variables

Ensure these are set in your `.env`:

```env
# Database
database.default.hostname = localhost
database.default.database = your_database
database.default.username = root
database.default.password = your_password

# Email (for messaging)
email.SMTPHost = smtp.example.com
email.SMTPUser = noreply@example.com
email.SMTPPass = your_password
email.fromEmail = noreply@example.com
email.fromName = EdQuill
```

---

## 11. Backup & Recovery

### Backup New Tables

```bash
mysqldump -u root -p your_database \
  t_event_outbox \
  t_audit_log \
  t_feature_flag \
  t_message_template \
  t_message_log \
  t_marketing_kpi_daily \
  t_revenue_daily > edquill_v2_backup_$(date +%Y%m%d).sql
```

### Restore

```bash
mysql -u root -p your_database < edquill_v2_backup_20251113.sql
```

---

## 12. Acceptance Criteria Checklist

- [ ] Status changes on `student_self_registrations` enqueue events
- [ ] Worker processes events < 1s avg
- [ ] Registrar can approve, needs_info, convert, assign class, approve/send invoice
- [ ] No double-booking after constraints
- [ ] Conflict warnings inline
- [ ] Reminders respect consent/quiet hours
- [ ] All messages logged
- [ ] Dashboard loads < 2.5s
- [ ] Lighthouse A11y ≥ 90
- [ ] Lighthouse Perf ≥ 85
- [ ] Index on lead queue reduces list load time under 300ms

---

## Support

For issues or questions:
- Check logs: `/var/log/edquill/`
- Review database: `t_event_outbox`, `t_audit_log`
- Contact: [Your Support Contact]

