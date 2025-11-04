# Frontend Changes Required for CI4 Migration

This document outlines the specific changes needed in the `edquill-web` project to work with the migrated CI4 backend.

## Overview

The CI4 backend uses a flat URL structure without module prefixes (`/admin/` or `/v1/`). The main change required is updating the `apiHost` in environment files to point to the new CI4 backend.

## Required Changes

### 1. Web Portal (`edquill-web/web`)

#### Environment Files to Update
Update the following files in `edquill-web/web/src/environments/`:

**environment.ts** (Local Development)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'http://localhost:8888/rista/api/index.php/v1/',
  apiHost: 'http://localhost:8888/rista_ci4/public/',
  webHost: 'http://localhost:8211',
  imgUrl: 'http://localhost:8888/rista',
  version: '6.0.0',
  showStudent: true,
  sessionPrefix: 'rista_',
  envName: 'LOCAL'
};
```

**environment.dev.ts** (Development)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'https://dev-api.edquill.com/rista/api/index.php/v1/',
  apiHost: 'https://dev-api.edquill.com/rista_ci4/public/',
  webHost: 'https://dev.edquill.com',
  imgUrl: 'https://dev-api.edquill.com/rista',
  version: '6.0.0',
  showStudent: true,
  sessionPrefix: 'rista_',
  envName: 'DEV'
};
```

**environment.staging.ts** (Staging)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'https://staging-api.edquill.com/rista/api/index.php/v1/',
  apiHost: 'https://staging-api.edquill.com/rista_ci4/public/',
  webHost: 'https://staging.edquill.com',
  imgUrl: 'https://staging-api.edquill.com/rista',
  version: '6.0.0',
  showStudent: true,
  sessionPrefix: 'rista_',
  envName: 'STAGING'
};
```

**environment.prod.ts** (Production)
```typescript
export const environment = {
  production: true,
  // OLD: apiHost: 'https://api.edquill.com/rista/api/index.php/v1/',
  apiHost: 'https://api.edquill.com/rista_ci4/public/',
  webHost: 'https://edquill.com',
  imgUrl: 'https://api.edquill.com/rista',
  version: '6.0.0',
  showStudent: true,
  sessionPrefix: 'rista_',
  envName: 'PROD'
};
```

### 2. Admin Portal (`edquill-web/admin`)

#### Environment Files to Update
Update the following files in `edquill-web/admin/src/environments/`:

**environment.ts** (Local Development)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'http://localhost:8888/rista/api/index.php/admin/',
  apiHost: 'http://localhost:8888/rista_ci4/public/',
  webHost: 'http://localhost:4211',
  imgUrl: 'http://localhost:8888/rista',
  version: '1.0.1',
  sessionPrefix: 'rista_',
  envName: 'LOCAL'
};
```

**environment.dev.ts** (Development)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'https://dev-api.edquill.com/rista/api/index.php/admin/',
  apiHost: 'https://dev-api.edquill.com/rista_ci4/public/',
  webHost: 'https://dev-admin.edquill.com',
  imgUrl: 'https://dev-api.edquill.com/rista',
  version: '1.0.1',
  sessionPrefix: 'rista_',
  envName: 'DEV'
};
```

**environment.staging.ts** (Staging)
```typescript
export const environment = {
  production: false,
  // OLD: apiHost: 'https://staging-api.edquill.com/rista/api/index.php/admin/',
  apiHost: 'https://staging-api.edquill.com/rista_ci4/public/',
  webHost: 'https://staging-admin.edquill.com',
  imgUrl: 'https://staging-api.edquill.com/rista',
  version: '1.0.1',
  sessionPrefix: 'rista_',
  envName: 'STAGING'
};
```

**environment.prod.ts** (Production)
```typescript
export const environment = {
  production: true,
  // OLD: apiHost: 'https://api.edquill.com/rista/api/index.php/admin/',
  apiHost: 'https://api.edquill.com/rista_ci4/public/',
  webHost: 'https://admin.edquill.com',
  imgUrl: 'https://api.edquill.com/rista',
  version: '1.0.1',
  sessionPrefix: 'rista_',
  envName: 'PROD'
};
```

## What Stays the Same

### 1. API Endpoint Paths
The actual API endpoint paths remain the same:
- `user/login` ✅
- `student/list` ✅
- `teacher/add` ✅
- `school/list` ✅
- `content/add` ✅
- `settings/list` ✅
- `settings/update` ✅

### 2. HTTP Interceptor
The `http.interceptor.ts` file requires no changes:
- Still uses `Accesstoken` header ✅
- Still prepends `apiHost` to URLs ✅
- Still handles CORS properly ✅

### 3. Service Calls
All service calls in the frontend remain unchanged:
- `this.http.post('user/login', data)` ✅
- `this.http.post('student/list', data)` ✅
- `this.http.post('settings/update', data)` ✅

### 4. Authentication Flow
The authentication flow remains the same:
- Login returns JWT token ✅
- Token stored in session/localStorage ✅
- Token sent in `Accesstoken` header ✅
- Token validation works the same ✅

## New Features Available

### 1. AI Essay Grading
New endpoints available for AI-powered essay grading:
```typescript
// Grade essay
this.http.post('essaygrader/grade', {
  essay_prompt: 'Write about...',
  essay_content: 'Student essay content...',
  student_grade: '10th',
  model: 'gpt-4o-mini'
});

// Get available models
this.http.get('essaygrader/models');

// Get grading history
this.http.post('essaygrader/history', {
  student_id: 123,
  content_id: 456
});
```

### 2. LMS Integration
New endpoints for LMS integration:
```typescript
// Get LMS integrations
this.http.post('lms/integrations', {});

// Add LMS integration
this.http.post('lms/add-integration', {
  lms_type: 'canvas',
  api_url: 'https://canvas.example.com/api',
  api_key: 'your-api-key',
  school_id: 1
});

// Test LMS connection
this.http.post('lms/test-connection', {
  integration_id: 1
});
```

### 3. Model Configuration
New endpoints for AI model configuration:
```typescript
// Get model configurations
this.http.post('modelconfig/configs', {});

// Update model configuration
this.http.post('modelconfig/update', {
  model_name: 'gpt-4o',
  config: {
    max_tokens: 2000,
    temperature: 0.7
  }
});
```

## Testing the Changes

### 1. Local Testing
1. Start the CI4 backend: `php spark serve`
2. Update environment files as shown above
3. Start the frontend applications
4. Test login functionality
5. Test CRUD operations
6. Test admin functionality

### 2. API Testing
Test the following endpoints manually:
```bash
# Test user login
curl -X POST http://localhost:8888/rista_ci4/public/user/login \
  -H "Content-Type: application/json" \
  -d '{"email":"test@example.com","password":"password123"}'

# Test admin token
curl -X GET http://localhost:8888/rista_ci4/public/auth/token

# Test school list (with token)
curl -X POST http://localhost:8888/rista_ci4/public/school/list \
  -H "Content-Type: application/json" \
  -H "Accesstoken: YOUR_TOKEN_HERE" \
  -d '{}'
```

### 3. Frontend Testing
1. Test user login/logout
2. Test student management
3. Test teacher management
4. Test school management
5. Test content management
6. Test admin settings
7. Test new AI features

## Rollback Plan

If issues arise, you can quickly rollback by:

1. **Revert Environment Files**: Change `apiHost` back to CI3 URLs
2. **Switch Backend**: Point to CI3 backend
3. **No Code Changes**: No frontend code changes required

## Deployment Checklist

### Pre-Deployment
- [ ] Update all environment files
- [ ] Test locally with CI4 backend
- [ ] Verify all API endpoints work
- [ ] Test authentication flow
- [ ] Test admin functionality

### Deployment
- [ ] Deploy CI4 backend
- [ ] Update frontend environment files
- [ ] Deploy frontend applications
- [ ] Test in staging environment
- [ ] Deploy to production

### Post-Deployment
- [ ] Monitor for errors
- [ ] Check authentication logs
- [ ] Verify all functionality works
- [ ] Monitor performance
- [ ] Check cron jobs

## Support

If you encounter issues:

1. **Check API Endpoints**: Verify the CI4 backend is running and accessible
2. **Check CORS**: Ensure CORS is properly configured in CI4
3. **Check Authentication**: Verify JWT tokens are being generated and validated
4. **Check Logs**: Review CI4 logs for any errors
5. **Test Manually**: Use curl or Postman to test API endpoints directly

## Conclusion

The frontend changes are minimal and primarily involve updating the `apiHost` in environment files. The API endpoints, authentication flow, and service calls remain the same, ensuring a smooth transition to the CI4 backend.

The new CI4 backend provides enhanced functionality including AI essay grading, LMS integration, and improved security while maintaining full compatibility with the existing frontend codebase.
