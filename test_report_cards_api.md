# Report Cards API Testing Guide

## Base URL
```
http://localhost:8888/rista_ci4/public
```

## Authentication
All endpoints require an `Accesstoken` header with a valid JWT.

```
Accesstoken: your_jwt_token_here
```

## Test Endpoints

### 1. List Grading Scales
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/scale/list \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{"school_id": 1}'
```

**Expected Response:**
```json
{
  "IsSuccess": true,
  "Data": [
    {
      "scale_id": 1,
      "name": "Elementary Standard Scale",
      "scale_json": "[{...}]",
      "is_active": 1
    }
  ],
  "Message": "Scales retrieved successfully"
}
```

### 2. List Templates
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/template/list \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{"school_id": 1, "active": 1}'
```

**Expected Response:**
```json
{
  "IsSuccess": true,
  "Data": [
    {
      "template_id": 1,
      "name": "Elementary Report Card",
      "version": 1,
      "is_active": 1,
      "schema_json": "{...}"
    }
  ],
  "Message": "Templates retrieved successfully"
}
```

### 3. Get Template Detail
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/template/detail \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{"template_id": 1}'
```

### 4. Generate Report Cards (Bulk)
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/generate \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{
    "template_id": 1,
    "student_ids": [123, 124, 125],
    "term": "Fall 2025",
    "academic_year": "2025-26"
  }'
```

**Expected Response:**
```json
{
  "IsSuccess": true,
  "Data": {
    "total": 3,
    "created": 3,
    "skipped": 0,
    "failed": 0,
    "errors": []
  }
}
```

### 5. List Report Cards
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/list \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{
    "school_id": 1,
    "term": "Fall 2025",
    "status": "draft",
    "limit": 20,
    "offset": 0
  }'
```

### 6. Get Report Card Detail
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/detail \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{"rc_id": 1}'
```

### 7. Update Report Card Status
```bash
# Mark as ready
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/status \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{
    "rc_id": 1,
    "status": "ready"
  }'

# Issue report card
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/status \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{
    "rc_id": 1,
    "status": "issued"
  }'
```

### 8. Send Email
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/email \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_JWT" \
  -d '{
    "rc_id": 1,
    "recipients": ["parent@example.com"],
    "include_pdf": true
  }'
```

### 9. Student Portal - View My Report Cards
```bash
curl -X POST http://localhost:8888/rista_ci4/public/student/reportCards \
  -H "Content-Type: application/json" \
  -H "Accesstoken: STUDENT_JWT" \
  -d '{}'
```

### 10. Get Analytics
```bash
curl -X POST http://localhost:8888/rista_ci4/public/reportcard/analytics \
  -H "Content-Type: application/json" \
  -H "Accesstoken: ADMIN_JWT" \
  -d '{
    "from": "2025-01-01",
    "to": "2025-12-31"
  }'
```

## Testing with Postman

1. Import these requests into Postman
2. Set up environment variables:
   - `base_url`: http://localhost:8888/rista_ci4/public
   - `access_token`: Your JWT token
   - `school_id`: Your school ID (e.g., 1)

3. Create a collection with all endpoints

## Common Response Codes

- `200 OK` - Success
- `400 Bad Request` - Validation error
- `401 Unauthorized` - Missing or invalid token
- `403 Forbidden` - Insufficient permissions
- `404 Not Found` - Resource not found
- `500 Internal Server Error` - Server error

## Error Response Format
```json
{
  "IsSuccess": false,
  "Message": "Error message here",
  "ErrorObject": {
    "field": ["error details"]
  }
}
```

## Quick Test Sequence

1. **List scales** → Verify seeded data exists
2. **List templates** → Verify templates exist
3. **Generate report cards** → Create test data
4. **List report cards** → See generated cards
5. **Update status to ready** → Change status
6. **Update status to issued** → Make visible to students
7. **View as student** → Test portal access

## Notes

- Replace `YOUR_JWT` with actual JWT token from login
- Replace IDs with actual IDs from your database
- School ID should match your authenticated user's school
- Student IDs must exist in the `user` table
