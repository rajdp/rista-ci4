# Course Enrollment System - Retrofit to Existing Schema

## Overview

The course enrollment system has been retrofitted to work with the existing `tbl_course`, `class`, and `student_class` tables instead of creating redundant `course_fee_plans` table.

## Database Changes

### New Tables (Only 2 Required)

**Run this script:** `/Applications/MAMP/htdocs/rista_ci4/create_course_enrollment_tables.sql`

#### 1. `student_courses`
Tracks course-level enrollment with student-specific fees.

| Column | Purpose |
|--------|---------|
| `student_id` | Links to user table |
| `course_id` | Links to tbl_course |
| `fee_amount` | **Student-specific fee** (can override tbl_course.fees) |
| `status` | active, completed, dropped, suspended |
| `enrollment_date` | When student enrolled in course |
| `student_fee_plan_id` | Links to billing system |
| `registration_id` | Links to self-registration if applicable |

#### 2. `course_class_mapping`
Links courses to classes for automatic enrollment.

| Column | Purpose |
|--------|---------|
| `course_id` | Course ID |
| `class_id` | Class ID |
| `auto_enroll` | 1 = Auto-enroll students when course added |
| `is_active` | Enable/disable this mapping |

### Existing Tables Used

#### `tbl_course`
- **`fee_amount` (DECIMAL)** - Default fee amount for the course
- **`fee_term` (INT)** - Billing frequency: 1 = one-time, 2 = recurring monthly
- **`billing_cycle_days` (INT)** - Billing frequency in days (null = one-time, positive = recurring)
- **`entity_id` (INT)** - School ID for filtering courses by school
- Used as the source of default fee values and billing terms
- **Note:** The `course_fee_plans` table has been removed. All billing cycle information is now stored directly in `tbl_course.billing_cycle_days`

#### `class`
- **`course_id` (INT)** - Links class to course (existing column)
- **`cost`, `discount_amount`, `actual_cost`** - Class-level fees (existing)
- **`payment_type`, `payment_sub_type`** - Payment settings (existing)

#### `student_class`
- **Existing enrollment tracking** at class level
- Automatically populated when courses auto-enroll students in classes

## Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                     COURSE ENROLLMENT FLOW                   │
└─────────────────────────────────────────────────────────────┘

1. Admin adds course to student
   ↓
2. CourseEnrollmentService.enrollStudentInCourse()
   ↓
3. Reads default fee from tbl_course.fee_amount and billing term from fee_term
   ↓
4. Creates student_courses record (with student-specific fee)
   ↓
5. Queries course_class_mapping for linked classes
   ↓
6. Auto-creates student_class records for each linked class
   ↓
7. Creates student_fee_plan if fee > 0

┌─────────────────────────────────────────────────────────────┐
│              REGISTRATION → STUDENT CONVERSION               │
└─────────────────────────────────────────────────────────────┘

1. Student submits self-registration with courses
   ↓
2. Admin reviews, makes course decisions (approve/decline)
   ↓
3. Admin sets approved_fee_amount (optional override)
   ↓
4. Admin clicks "Convert to Student"
   ↓
5. SelfRegistrationPromotionService.promote()
   ├── Creates user record
   ├── Links guardians
   └── Calls enrollApprovedCourses()
       ↓
       For each approved course:
       - Uses CourseEnrollmentService
       - Applies fee from approved_fee_amount OR tbl_course.fees
       - Creates student_courses record
       - Auto-enrolls in linked classes
```

## Model Changes

### `CourseFeePlanModel.php` - Retrofitted

**Old:** Queried `course_fee_plans` table
**New:** Queries `tbl_course.fee_amount` and `tbl_course.fee_term` columns

**Key Methods:**
- `getFeeForCourse($courseId, $schoolId)` - Returns fee from tbl_course.fee_amount, filters by entity_id
- `calculateFeeAmount($courseId, $schoolId)` - Returns fee_amount as float
- `setCourseFee($courseId, $schoolId, $feeData)` - Updates tbl_course.fee_amount and fee_term
- `getCoursesWithFees($schoolId, $filters)` - Lists courses for school using entity_id filter

**Fee Term Mapping:**
- `fee_term = 1` → One-time fee (billing_cycle_days = null)
- `fee_term = 2` → Monthly recurring (billing_cycle_days = 30)

**Note:** The model maintains the same interface, so no changes needed in services that use it.

## Service Integration

### `CourseEnrollmentService.php` - No Changes Required

The service continues to use `CourseFeePlanModel` methods. Since the model now reads from `tbl_course`, the service automatically gets fees from the right place.

### `SelfRegistrationPromotionService.php` - Already Integrated

The `enrollApprovedCourses()` method uses `CourseEnrollmentService` for:
- Automatic fee calculation from tbl_course.fees
- Admin fee overrides via `approved_fee_amount`
- Automatic class enrollment via course_class_mapping

## API Endpoints

### Course Fee Management

**All endpoints continue to work without changes.**

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/course-fees/list` | POST | List courses with fees from tbl_course |
| `/admin/course-fees/get` | POST | Get fee for specific course |
| `/admin/course-fees/save` | POST | Update tbl_course.fees |
| `/admin/course-fees/delete` | POST | Clear tbl_course.fees (sets to null) |

### Class Mapping

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/course-fees/link-classes` | POST | Link classes to course |
| `/admin/course-fees/get-linked-classes` | POST | Get linked classes |
| `/admin/course-fees/unlink-class` | POST | Unlink specific class |

### Student Courses

| Endpoint | Method | Purpose |
|----------|--------|---------|
| `/admin/student-courses/list` | POST | Get student's enrolled courses |
| `/admin/student-courses/add` | POST | Enroll student in course |
| `/admin/student-courses/fee-preview` | POST | Preview fee before enrollment |
| `/admin/student-courses/update-status` | POST | Change course status |

## Frontend Integration

### Student Profile - Courses Tab

**Location:** `web/src/app/components/studentlogin/student-overall-profile-details/`

**Features:**
- View enrolled courses with fees and status
- "Add Course" button with modal
- Automatic fee preview from tbl_course.fees
- Fee override capability
- Auto-enrollment preview (shows how many classes will be assigned)

**Services Used:**
- `StudentCourseService` - API calls for student course operations
- `CourseFeeService` - API calls for course fee configuration (BillingManager)

### CRM Registrations

**Location:** `web/src/app/components/crm/registrations/`

**Enhanced Features:**
- Course fee preview during registration review
- Admin can override fee with `approved_fee_amount`
- "Convert to Student" automatically enrolls approved courses with fees

## Migration Path

### For Existing Courses

1. **Set Fees:** Update `tbl_course.fee_amount` and `fee_term` for courses that have fees
   ```sql
   -- One-time fee
   UPDATE tbl_course SET fee_amount = 99.99, fee_term = 1 WHERE course_id = 123;

   -- Monthly recurring fee
   UPDATE tbl_course SET fee_amount = 49.99, fee_term = 2 WHERE course_id = 456;
   ```

2. **Link Classes:** Use `/admin/course-fees/link-classes` API or BillingManager UI to link courses to classes

3. **Test Enrollment:** Add course to a test student to verify:
   - Fee is calculated correctly from tbl_course.fee_amount
   - Billing term is read from tbl_course.fee_term
   - Student is auto-enrolled in linked classes
   - student_courses record is created
   - student_class records are created

### For New Courses

When creating courses:
1. Set the `fee_amount` and `fee_term` columns in tbl_course
   - `fee_term = 1` for one-time fees
   - `fee_term = 2` for monthly recurring fees
2. Set the `entity_id` column to match the school's ID
3. After creating classes for the course, link them by setting `class.course_id`
4. Classes with matching `course_id` will automatically enroll students when they join the course

## Benefits of This Approach

1. **No Duplicate Fee Storage** - Uses existing tbl_course.fee_amount and fee_term columns
2. **School Filtering** - Uses entity_id to ensure courses belong to correct school
3. **Backward Compatible** - Existing courses continue to work
4. **Course-Level Tracking** - student_courses provides enrollment tracking beyond just classes
5. **Flexible Class Assignment** - class.course_id enables 1-to-many course→class relationships
6. **Student-Specific Fees** - student_courses.fee_amount allows per-student overrides
7. **Billing Term Support** - fee_term enables one-time vs. recurring billing
8. **Automatic Workflows** - Registration approval → course enrollment → class assignment → fee assignment

## Testing Checklist

- [ ] Run SQL script to create student_courses table
- [ ] Set fee_amount and fee_term for test courses in tbl_course
- [ ] Verify entity_id is set correctly for courses
- [ ] Link test course to classes by setting class.course_id
- [ ] Add course to student from profile page
- [ ] Verify student_courses record created with correct fee_amount
- [ ] Verify billing_cycle_days calculated from fee_term
- [ ] Verify student_class records created for linked classes
- [ ] Test registration → student conversion workflow
- [ ] Verify fee overrides work during registration approval
- [ ] Check that billing snapshot reflects course fees with correct billing terms

## Files Modified

### Backend
- `/Applications/MAMP/htdocs/rista_ci4/create_course_enrollment_tables.sql` - **Simplified** (removed course_fee_plans)
- `/Applications/MAMP/htdocs/rista_ci4/app/Models/CourseFeePlanModel.php` - **Retrofitted** to use tbl_course
- `/Applications/MAMP/htdocs/rista_ci4/app/Controllers/Admin/CourseFees.php` - **Updated** delete() method
- `/Applications/MAMP/htdocs/rista_ci4/app/Services/SelfRegistrationPromotionService.php` - **Integrated** CourseEnrollmentService

### Frontend
- `/Applications/MAMP/htdocs/edquill-web_angupgrade/web/src/app/components/studentlogin/student-overall-profile-details/*` - **Added** Courses tab

### Files NOT Modified (No Changes Needed)
- `CourseEnrollmentService.php` - Uses CourseFeePlanModel interface (unchanged)
- `StudentCourseModel.php` - Already designed for student_courses table
- `CourseClassMappingModel.php` - Already designed for course_class_mapping table
- `StudentCourses.php` controller - No changes needed
