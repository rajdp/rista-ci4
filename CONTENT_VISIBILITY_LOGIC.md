# Content Visibility Logic

## Overview
This document explains how content visibility is handled for students and teachers based on assignment type.

## Content Assignment Types

### 1. Class-Wide Content (`all_student = 1`)
Content assigned to the entire class becomes visible to all students in the class.

**Behavior:**
- All current students in the class can see the content
- When new students are added to the class, they automatically see:
  - **In-progress content**: `start_date <= CURDATE() AND end_date >= CURDATE()`
  - **Future content**: `start_date > CURDATE()`
  - **Past/completed content**: NOT visible to new students (`end_date < CURDATE()`)
- No `student_content` records are created for class-wide content
- Visibility is determined by class membership (`student_class` table) and date filtering

**Implementation:**
- Query filters: `(cc.all_student = 1 AND cc.end_date >= CURDATE())`
- Location: `Student.php::curriculumList()`, `assessmentList()`, `assignmentList()`, `resourcesList()`

### 2. Student-Specific Content (`all_student = 0`)
Content assigned to specific students is only visible to those students.

**Behavior:**
- Only the assigned students can see and work on the content
- All other students have NO visibility to student-specific content
- `student_content` records are created for each assigned student
- Visibility is determined by the existence of a `student_content` record
- No date restrictions - students see all their assigned content (past, in-progress, and future)

**Implementation:**
- Query filters: `(cc.all_student = 0 AND sc.id IS NOT NULL)`
- Location: `Student.php::curriculumList()`, `assessmentList()`, `assignmentList()`, `resourcesList()`

### 3. Teacher Visibility
Teachers see ALL content in their classes, regardless of assignment type.

**Behavior:**
- Teachers see both class-wide and student-specific content
- Teachers see all content regardless of date (past, in-progress, and future)
- Teachers can see which students are assigned to student-specific content
- No filtering by `all_student` flag or date

**Implementation:**
- Query: Shows all `class_content` records for the class where `cc.status = 1`
- Location: `Classes.php::curriculumList()`

## Database Structure

### Key Tables
- `class_content`: Stores content assignments to classes
  - `all_student`: 1 = class-wide, 0 = student-specific
  - `start_date`, `end_date`: Date range for content availability
- `student_content`: Stores individual student assignments (only for `all_student = 0`)
- `student_class`: Stores class membership (students enrolled in classes)

### Query Logic

**For Students:**
```sql
WHERE scs.student_id = ?
AND cc.status = 1
AND (
    -- Class-wide: in-progress and future only
    (cc.all_student = 1 AND cc.end_date >= CURDATE())
    OR
    -- Student-specific: only if student_content record exists
    (cc.all_student = 0 AND sc.id IS NOT NULL)
)
```

**For Teachers:**
```sql
WHERE cc.class_id = ?
AND cc.status = 1
-- No filtering by all_student or date - shows everything
```

## Files Modified

1. **`app/Controllers/Student.php`**
   - `curriculumList()`: Updated to check `all_student` flag and filter dates for class-wide content
   - `assessmentList()`: Same logic applied
   - `assignmentList()`: Same logic applied
   - `resourcesList()`: Same logic applied

2. **`app/Controllers/Classes.php`**
   - `curriculumList()`: Already shows all content (no changes needed for teachers)

3. **Frontend Components**
   - `answering.component.html`: Added optional chaining for null safety
   - `answering.component.ts`: Added null checks and fallback logic

## Testing Checklist

- [ ] Class-wide content is visible to all students in the class
- [ ] New students see in-progress and future class-wide content
- [ ] New students do NOT see past class-wide content
- [ ] Student-specific content is only visible to assigned students
- [ ] Other students cannot see student-specific content not assigned to them
- [ ] Teachers see all content (class-wide and student-specific)
- [ ] Teachers see all content regardless of date

## Notes

- When content is assigned to all students (`all_student = 1`), any existing `student_content` records for that content are deleted (see `Content.php::add()` line 1139-1143)
- When content is assigned to specific students (`all_student = 0`), `student_content` records are created for each assigned student
- The date filter `end_date >= CURDATE()` ensures new students only see relevant content, not historical assignments


