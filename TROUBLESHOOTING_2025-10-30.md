# Troubleshooting Guide - Database Issues (2025-10-30)

## Issues Identified

### Issue 1: Missing `student_assign_content` Table ✅ FIXED
**Error**: `Table 'edquill_production.student_assign_content' doesn't exist`

**Location**: `/app/Controllers/Content.php` line 600

**Fix Applied**:
- Created migration: `/app/Database/Migrations/2025-10-30-000000_CreateStudentAssignContentTable.php`
- Created SQL file: `/app/Database/SQL/create_student_assign_content_table.sql`
- Created documentation: `/app/Database/SQL/student_assign_content_README.md`

---

### Issue 2: Missing `modified_date` Column in `user_token` Table ✅ FIXED
**Error**: `Unknown column 'modified_date' in 'field list'`

**Location**: 
- `/app/Controllers/User.php` line 456 (logout function)
- `/app/Models/V1/UserModel.php` line 114 (login function)

**Problem**: 
The `user_token` table doesn't have a `modified_date` column, but the code tries to update it when:
1. User logs out
2. Student logs in (invalidates previous tokens)

**Fix Applied**:
- Created migration: `/app/Database/Migrations/2025-10-30-100000_AddModifiedDateToUserToken.php`
- Created SQL file: `/app/Database/SQL/add_modified_date_to_user_token.sql`

---

### Issue 3: Token Expired (401 Error)
**Error**: `Token has expired`

**Location**: When trying to POST to `/content/add`

**Explanation**: 
This is **expected behavior**, not a bug. The authentication token has expired and needs to be refreshed.

**Solution**: 
The frontend should:
1. Detect 401 errors with "Token has expired" message
2. Redirect user to login page
3. Request a new token
4. Retry the original request with the new token

**Note**: The logout error (Issue 2) was preventing proper cleanup when the token expires. After fixing Issue 2, the logout should work correctly.

---

## How to Apply Fixes

### Option 1: Run Migrations (Recommended)

```bash
cd /Applications/MAMP/htdocs/rista_ci4

# Run all pending migrations
php spark migrate

# Verify migrations were applied
php spark migrate:status
```

### Option 2: Run SQL Directly

If you prefer to run SQL directly in phpMyAdmin or MySQL client:

#### Fix for Issue 1 (student_assign_content table):
```sql
-- Run the SQL from:
app/Database/SQL/create_student_assign_content_table.sql
```

#### Fix for Issue 2 (modified_date column):
```sql
-- Run the SQL from:
app/Database/SQL/add_modified_date_to_user_token.sql
```

Or simply execute:
```sql
ALTER TABLE `user_token` 
ADD COLUMN `modified_date` DATETIME NULL COMMENT 'Last modification timestamp' 
AFTER `created_date`;

UPDATE `user_token` 
SET `modified_date` = `created_date` 
WHERE `modified_date` IS NULL;
```

---

## Verification Steps

### 1. Verify `student_assign_content` table exists:
```sql
DESCRIBE student_assign_content;
```

Expected output:
```
+-------------+---------------------+------+-----+---------+----------------+
| Field       | Type                | Null | Key | Default | Extra          |
+-------------+---------------------+------+-----+---------+----------------+
| id          | bigint(20) unsigned | NO   | PRI | NULL    | auto_increment |
| class_id    | bigint(20) unsigned | YES  | MUL | NULL    |                |
| content_id  | bigint(20) unsigned | YES  | MUL | NULL    |                |
| start_date  | date                | NO   |     | NULL    |                |
| end_date    | date                | NO   |     | 0000-00-00 |             |
| start_time  | time                | NO   |     | 00:00:00 |               |
| end_time    | time                | NO   |     | 23:59:00 |               |
| status      | tinyint(1)          | NO   | MUL | 1       |                |
| created_by  | bigint(20) unsigned | YES  |     | NULL    |                |
| created_date| datetime            | NO   |     | NULL    |                |
+-------------+---------------------+------+-----+---------+----------------+
```

### 2. Verify `modified_date` column in `user_token`:
```sql
DESCRIBE user_token;
```

Should include:
```
| modified_date | datetime | YES  |     | NULL    |                |
```

### 3. Test logout functionality:
```bash
# Try logging out via the frontend
# Should no longer show: "Unknown column 'modified_date' in 'field list'"
```

### 4. Test content assignment:
```bash
# Try assigning content to a class via the frontend
# Should no longer show: "Table 'student_assign_content' doesn't exist"
```

---

## Additional Notes

### Token Expiration Handling (Frontend)
The frontend (`auth.service.ts`) should implement proper token expiration handling:

```typescript
// Example implementation
private handleTokenExpiration(error: HttpErrorResponse): void {
  if (error.status === 401 && error.error?.ErrorObject === 'Token has expired') {
    // Clear local storage
    localStorage.removeItem('accessToken');
    
    // Redirect to login
    this.router.navigate(['/login'], {
      queryParams: { returnUrl: this.router.url, reason: 'session_expired' }
    });
  }
}
```

### Database Schema Best Practices
Going forward, consider:
1. Adding `updated_at` columns to all tables (using CI4 conventions)
2. Using CI4 timestamps: `created_at` and `updated_at` instead of `created_date` and `modified_date`
3. Enabling CI4 model timestamps feature for automatic handling

---

## Files Created/Modified

### New Migration Files:
1. `/app/Database/Migrations/2025-10-30-000000_CreateStudentAssignContentTable.php`
2. `/app/Database/Migrations/2025-10-30-100000_AddModifiedDateToUserToken.php`

### New SQL Files:
1. `/app/Database/SQL/create_student_assign_content_table.sql`
2. `/app/Database/SQL/add_modified_date_to_user_token.sql`

### New Documentation:
1. `/app/Database/SQL/student_assign_content_README.md`
2. `/TROUBLESHOOTING_2025-10-30.md` (this file)

### Files with Database Dependencies (No changes needed):
- `/app/Controllers/Content.php` (line 600) - uses `student_assign_content`
- `/app/Controllers/User.php` (line 456) - uses `modified_date`
- `/app/Models/V1/UserModel.php` (line 114) - uses `modified_date`

---

## Summary

✅ **Issue 1**: `student_assign_content` table - Migration created  
✅ **Issue 2**: `modified_date` column - Migration created  
ℹ️ **Issue 3**: Token expiration - This is expected behavior, no fix needed

**Next Step**: Run `php spark migrate` to apply all fixes.






