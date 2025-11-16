# Course Documentation Requirements Feature

## Overview
This feature allows administrators to specify documentation requirements for courses, which students can then view and upload during self-registration.

## Implementation Summary

### 1. Database Changes
**File**: `app/Database/SQL/add_documentation_requirements_to_course.sql`
- Added `documentation_requirements` TEXT column to `tbl_course` table
- Column is placed after `other_details`
- Column is nullable to support existing courses

### 2. Backend API Changes
**File**: `app/Controllers/Course.php`

#### Added Methods:
- `add()` - Creates new courses with all fields including documentation_requirements
- `edit()` - Updates existing courses including documentation_requirements

#### Updated Methods:
- `list()` - Now includes documentation_requirements in the SELECT statement

#### Key Features:
- Handles JSON and POST data
- Converts array fields (category_id, subject_id, grade_id) to comma-separated values for storage
- Properly handles date fields
- Includes comprehensive error logging
- Returns standardized response format

### 3. Frontend Admin Changes
**Files**:
- `web/src/app/components/course/details/course-details-add/course-details-add.component.ts`
- `web/src/app/components/course/details/course-details-add/course-details-add.component.html`

**Changes**:
- Added `documentation_requirements` form control to the form group
- Added textarea input field in the UI
- Included helpful placeholder text and info icon
- Field is sent to backend in both add and edit operations
- Properly patched when editing existing courses

### 4. Frontend Student Self-Registration Changes
**Files**:
- `web/src/app/components/self-registration/self-registration.component.ts`
- `web/src/app/components/self-registration/self-registration.component.html`
- `web/src/app/components/self-registration/self-registration.component.scss`

**Changes**:
- Updated `CourseOption` interface to include `documentation_requirements` field
- Updated `DocumentPayload` interface to associate documents with specific courses
- Added methods:
  - `getDocumentsForCourse(courseId)` - Filter documents by course
  - `getGeneralDocuments()` - Get documents not tied to any course
  - Updated `onDocumentsSelected()` to accept course ID and name
- UI displays documentation requirements for each selected course
- Course-specific upload buttons with clear labeling
- Visual alerts (blue info boxes) for documentation requirements
- Uploaded documents displayed separately per course

## How It Works

### Admin Workflow:
1. Admin navigates to Course → Details → Add/Edit
2. Fills in course information including the new "Documentation Requirements" field
3. Enters requirements like:
   ```
   Birth Certificate
   Previous School Report Card
   Medical Records
   Proof of Address
   ```
4. Saves the course

### Student Workflow:
1. Student accesses self-registration portal
2. Selects course(s) to enroll in
3. For each selected course with documentation requirements:
   - Sees an info box displaying the requirements
   - Can upload documents specifically for that course
   - Uploaded files are tagged with course ID and name
4. Can also upload general documents not specific to any course
5. Submits registration with all documents

## Database Schema

```sql
ALTER TABLE `tbl_course` 
ADD COLUMN `documentation_requirements` TEXT NULL 
AFTER `other_details`
COMMENT 'Documentation requirements for student registration';
```

## API Endpoints

### POST /course/add
Creates a new course including documentation requirements.

**Request Body**:
```json
{
  "course_name": "Introduction to Programming",
  "short_description": "Learn programming basics",
  "documentation_requirements": "Birth Certificate\nPrevious School Report Card",
  "category_id": [1, 2],
  "subject_id": [5],
  "status": "A",
  ...
}
```

### POST /course/edit
Updates an existing course.

**Request Body**:
```json
{
  "course_id": 123,
  "course_name": "Updated Course Name",
  "documentation_requirements": "Updated requirements",
  ...
}
```

### POST /course/list
Returns course list including documentation requirements.

**Response**:
```json
{
  "IsSuccess": true,
  "ResponseObject": [
    {
      "course_id": 123,
      "course_name": "Introduction to Programming",
      "documentation_requirements": "Birth Certificate\nReport Card",
      ...
    }
  ]
}
```

## Testing

### Test Backend API:
```bash
# Test adding a course
curl -X POST http://localhost:8888/rista_ci4/public/course/add \
  -H "Content-Type: application/json" \
  -d '{
    "course_name": "Test Course",
    "documentation_requirements": "Test document requirement",
    "school_id": 1,
    "user_id": 1
  }'

# Test editing a course
curl -X POST http://localhost:8888/rista_ci4/public/course/edit \
  -H "Content-Type: application/json" \
  -d '{
    "course_id": 1,
    "documentation_requirements": "Updated requirements"
  }'
```

### Test Frontend:
1. Navigate to `http://localhost:8211/#/course/details/add`
2. Fill in course details including documentation requirements
3. Save the course
4. Navigate to `http://localhost:8211/#/course/details/edit`
5. Verify documentation requirements are loaded
6. Update and save

### Test Self-Registration:
1. Access self-registration portal
2. Select a course with documentation requirements
3. Verify requirements are displayed
4. Upload documents for the course
5. Verify documents are tagged correctly

## Files Modified

### Backend (CodeIgniter 4):
- ✅ `/app/Controllers/Course.php` - Added add() and edit() methods
- ✅ `/app/Database/Migrations/2025-11-06-000001_AddDocumentationRequirementsToCourse.php` - Migration file
- ✅ `/app/Database/SQL/add_documentation_requirements_to_course.sql` - SQL script

### Frontend (Angular):
- ✅ `/web/src/app/components/course/details/course-details-add/course-details-add.component.ts`
- ✅ `/web/src/app/components/course/details/course-details-add/course-details-add.component.html`
- ✅ `/web/src/app/components/self-registration/self-registration.component.ts`
- ✅ `/web/src/app/components/self-registration/self-registration.component.html`
- ✅ `/web/src/app/components/self-registration/self-registration.component.scss`

## Notes

- The `documentation_requirements` field is optional (NULL allowed)
- Existing courses without this field will show an empty value
- Students can upload multiple documents per course
- Documents are base64 encoded for storage
- The feature supports multi-line requirements (use line breaks in the textarea)

## Future Enhancements

Potential improvements:
1. Add document type validation (PDF, JPG, PNG only)
2. Add file size limits and validation
3. Allow admins to mark specific documents as required vs optional
4. Add document verification workflow for admins
5. Store documents in cloud storage instead of database
6. Add document preview functionality
7. Email notifications when students upload documents

## Troubleshooting

### Error: 501 Not Implemented
- **Cause**: Backend endpoints were missing
- **Solution**: Added `add()` and `edit()` methods to Course controller

### Error: Column not found
- **Cause**: Database column doesn't exist
- **Solution**: Run the SQL migration script

### Documents not showing
- **Cause**: CourseOption interface missing field
- **Solution**: Added documentation_requirements to interface

## Support

For issues or questions, please check:
1. Database column exists: `DESCRIBE tbl_course;`
2. Backend endpoints respond: Test with curl
3. Frontend console for JavaScript errors
4. Backend logs in CodeIgniter for PHP errors





