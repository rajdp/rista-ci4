# Check School Dependencies for school_id 59

The Dashboard API queries several tables that reference `school_id`. Here's what to check:

## Required: School Table Record

**Most Important:** The `school` table must have a record with `school_id = 59`.

### Check if school exists:
```sql
SELECT * FROM school WHERE school_id = 59;
```

### If missing, create it:
```sql
INSERT INTO school (
    school_id,
    name,
    status,
    institution_type,
    created_date,
    created_by
) VALUES (
    59,
    'Your School Name',
    1,  -- Active
    1,  -- Institution type (adjust as needed)
    NOW(),
    1   -- Created by user_id
);
```

## Tables Referenced by Dashboard

The Dashboard queries these tables (they can be empty, but school_id 59 should exist):

### 1. `student_self_registrations`
- Used for: Leads and enrollments count
- **Can be empty** - Dashboard will return 0 if no records

### 2. `t_revenue_daily`
- Used for: Revenue metrics (MRR, ARR, overdue)
- **Can be empty** - Dashboard will return 0 if no records

### 3. `t_marketing_kpi_daily`
- Used for: Marketing KPIs
- **Can be empty** - Dashboard will return 0 if no records

### 4. `t_message_log`
- Used for: Messaging metrics
- **Can be empty** - Dashboard will return 0 if no records

## Quick Check Script

Run this SQL to check all dependencies:

```sql
-- 1. Check if school exists
SELECT 
    'school' as table_name,
    COUNT(*) as record_count
FROM school 
WHERE school_id = 59

UNION ALL

-- 2. Check user has school_id
SELECT 
    'user' as table_name,
    COUNT(*) as record_count
FROM user 
WHERE school_id = 59

UNION ALL

-- 3. Check dashboard tables (optional - can be empty)
SELECT 
    'student_self_registrations' as table_name,
    COUNT(*) as record_count
FROM student_self_registrations 
WHERE school_id = 59

UNION ALL

SELECT 
    't_revenue_daily' as table_name,
    COUNT(*) as record_count
FROM t_revenue_daily 
WHERE school_id = 59

UNION ALL

SELECT 
    't_marketing_kpi_daily' as table_name,
    COUNT(*) as record_count
FROM t_marketing_kpi_daily 
WHERE school_id = 59;
```

## Most Likely Issue

**The `school` table is missing a record for school_id = 59.**

### Fix:

1. **Check if school exists:**
   ```sql
   SELECT * FROM school WHERE school_id = 59;
   ```

2. **If missing, create it:**
   ```sql
   INSERT INTO school (
       school_id,
       name,
       status,
       institution_type,
       created_date,
       created_by
   ) VALUES (
       59,
       'Your School Name',  -- Replace with actual name
       1,                    -- Active status
       1,                    -- Institution type
       NOW(),
       1                     -- Created by user_id
   );
   ```

3. **Verify user is linked:**
   ```sql
   SELECT user_id, email_id, school_id 
   FROM user 
   WHERE school_id = 59;
   ```

## Other Optional Tables (Can Be Empty)

These tables are queried by Dashboard but **can be empty**:
- `student_self_registrations` - Will return 0 leads/enrollments
- `t_revenue_daily` - Will return 0 revenue
- `t_marketing_kpi_daily` - Will return 0 marketing data
- `t_message_log` - Will return 0 messaging metrics

The Dashboard will return empty/zero values if these tables don't have data, which is fine for a new tenant.

## Summary

**Required:**
- ✅ `school` table must have record with `school_id = 59`
- ✅ `user` table should have `school_id = 59` (you confirmed this exists)

**Optional (can be empty):**
- `student_self_registrations`
- `t_revenue_daily`
- `t_marketing_kpi_daily`
- `t_message_log`

---

**Check the `school` table first - that's the most likely missing dependency!**






