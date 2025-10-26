# Role ID Mapping Reference

## User Role IDs

The application uses the following role ID mapping:

| Role ID | Role Name | Description |
|---------|-----------|-------------|
| 1 | Super Admin | System administrator |
| 2 | School Admin | School administrator |
| 3 | Content Creator | Creates educational content |
| 4 | Teacher | Teaches classes |
| 5 | Student | Enrolled student |

## Implementation Notes

### Student List Endpoint: `/student/list`

The endpoint now returns users with roles 3, 4, and 5 (Content Creators, Teachers, and Students) to support the user-list component's three tabs:
- Tab 1: Students (role_id = 5)
- Tab 2: Teachers (role_id = 4)
- Tab 3: Content Creators (role_id = 3)

### Frontend Filtering

The frontend user-list component filters the results by role_id to populate each tab appropriately.

### Usage Example

```json
// Request
POST /student/list
{
  "platform": "web",
  "school_id": 12,
  "role_id": 2,
  "user_id": 845
}

// Response includes all three roles
{
  "IsSuccess": true,
  "ResponseObject": [
    {"role_id": "5", "first_name": "John", ...},    // Student
    {"role_id": "4", "first_name": "Jane", ...},    // Teacher
    {"role_id": "3", "first_name": "Bob", ...}      // Content Creator
  ]
}
```

The frontend then filters this data into the three tabs based on `role_id`.

