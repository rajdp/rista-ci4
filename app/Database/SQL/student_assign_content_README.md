# Student Assign Content Table

## Overview
The `student_assign_content` table stores assignments of educational content to classes with scheduling constraints. This allows administrators and teachers to assign content to specific classes with defined start/end dates and daily time windows for access.

## Table Schema

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | BIGINT(20) UNSIGNED | NO | AUTO_INCREMENT | Primary key |
| `class_id` | BIGINT(20) UNSIGNED | YES | NULL | Foreign key to classes table |
| `content_id` | BIGINT(20) UNSIGNED | YES | NULL | Foreign key to content table |
| `start_date` | DATE | NO | - | Assignment start date |
| `end_date` | DATE | NO | '0000-00-00' | Assignment end date (0000-00-00 = no end date) |
| `start_time` | TIME | NO | '00:00:00' | Daily start time for content access |
| `end_time` | TIME | NO | '23:59:00' | Daily end time for content access |
| `status` | TINYINT(1) | NO | 1 | 1=Active, 0=Inactive |
| `created_by` | BIGINT(20) UNSIGNED | YES | NULL | User ID who created the assignment |
| `created_date` | DATETIME | NO | - | Record creation timestamp |

## Indexes

- **PRIMARY KEY**: `id`
- **INDEX**: `idx_class_id` on `class_id`
- **INDEX**: `idx_content_id` on `content_id`
- **INDEX**: `idx_status` on `status`
- **COMPOSITE INDEX**: `idx_class_content` on `(class_id, content_id)`

## Usage in Code

This table is used in `/app/Controllers/Content.php` around line 600-620 to store content assignments:

```php
$assignmentData = [
    'class_id' => $details['class_id'] ?? null,
    'content_id' => $details['content_id'] ?? null,
    'start_date' => $startDate,
    'end_date' => $endDate,
    'start_time' => $details['start_time'] ?? '00:00:00',
    'end_time' => $details['end_time'] ?? '23:59:00',
    'status' => 1,
    'created_by' => $params['user_id'] ?? null,
    'created_date' => date('Y-m-d H:i:s')
];
```

## Installation

### Option 1: Using CodeIgniter Migration (Recommended)

```bash
# Run the migration
php spark migrate

# Or run specific migration
php spark migrate --all
```

### Option 2: Direct SQL Execution

```bash
# Using MySQL command line
mysql -u your_username -p your_database < app/Database/SQL/create_student_assign_content_table.sql

# Or using phpMyAdmin or any MySQL client
# Copy and paste the SQL from create_student_assign_content_table.sql
```

## Rollback

To remove the table:

```bash
# Using migration
php spark migrate:rollback

# Or using SQL
DROP TABLE IF EXISTS `student_assign_content`;
```

## Business Logic

1. **Assignment Scheduling**: Content can be assigned to a class with specific date ranges
2. **Time Windows**: Daily access can be restricted to specific time periods (e.g., 08:00-17:00)
3. **Flexible End Dates**: Use '0000-00-00' for assignments with no end date
4. **Status Control**: Active/Inactive flag for enabling/disabling assignments
5. **Audit Trail**: Tracks who created the assignment and when

## Example Data

```sql
INSERT INTO `student_assign_content` 
  (`class_id`, `content_id`, `start_date`, `end_date`, `start_time`, `end_time`, `status`, `created_by`, `created_date`)
VALUES
  (1, 101, '2025-10-30', '2025-12-31', '08:00:00', '17:00:00', 1, 5, '2025-10-30 10:00:00'),
  (1, 102, '2025-10-30', '0000-00-00', '00:00:00', '23:59:00', 1, 5, '2025-10-30 10:05:00'),
  (2, 101, '2025-11-01', '2025-11-30', '09:00:00', '18:00:00', 1, 5, '2025-10-30 10:10:00');
```

## Related Tables

- `classes` - Contains class information
- `content` - Contains educational content
- `users` - Contains user information (for created_by)
- `student_content_class_access` - Related table for student-specific access control

## Notes

- Foreign key constraints are commented out in the migration by default. Uncomment them if you want strict referential integrity.
- The `end_date` uses '0000-00-00' as a sentinel value for "no end date". Ensure your MySQL mode allows this or use NULL instead.
- Consider adding an `updated_date` and `updated_by` column if you need to track modifications.








