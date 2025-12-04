# Final Endpoint Migration Inventory

**Generated:** 2025-11-22

## Methodology

1. Extracted all API endpoint calls from frontend TypeScript files
2. Extracted all routes from CI4 Routes.php
3. Compared to identify missing endpoints
4. Verified against actual controller methods

## Summary

- **Total Frontend API Calls:** 84
- **Total CI4 Routes:** 437
- **Actually Missing Endpoints:** ~12-15

## Missing Endpoints Requiring Migration

### Classes (3 missing)
- `classes/addAnnouncementComments` - Add comments to announcements
- `classes/getAnnouncementComments` - Get announcement comments
- `classes/updateComments` - Update class comments

### Content (1 missing)
- `content/questionSkill` - Get question skill information

### Mailbox (2 missing)
- `mailbox/sendMessage` - Send message (Note: `mailbox/send` exists, may need alias)
- `mailbox/update` - Update message

### Student (6-8 missing)
- `student/getCurrentDateTime` - Get server current date/time
- `student/getModuleSubject` - Get module/subject information
- `student/getOpenAiFeedback` - Get OpenAI feedback
- `student/googleRegister` - Google OAuth registration
- `student/addCategory` - Add category
- `student/editCategory` - Edit category
- `student/listCategory` - List categories
- `student/updateContentStartTime` - **EXISTS in controller but may need route**

### User (1 missing)
- `user/googleAuthenticate` - Google OAuth authentication
- `user/logout` - User logout

### Other (1 missing)
- `studentlogin/class-detail` - Student login class detail

## Migration Priority

### High Priority (Core Functionality)
1. `student/updateContentStartTime` - **Check if route exists**
2. `mailbox/sendMessage` - May need alias to `mailbox/send`
3. `mailbox/update` - Message updates
4. `classes/addAnnouncementComments` - Class announcements
5. `classes/getAnnouncementComments` - Class announcements
6. `classes/updateComments` - Class comments

### Medium Priority
7. `content/questionSkill` - Content features
8. `student/getCurrentDateTime` - Utility function
9. `student/getModuleSubject` - Module/subject data

### Low Priority (Can use proxy or defer)
10. `student/googleRegister` - OAuth integration
11. `user/googleAuthenticate` - OAuth integration
12. `user/logout` - Logout functionality
13. `student/getOpenAiFeedback` - AI features
14. `student/addCategory` - Category management
15. `student/editCategory` - Category management
16. `student/listCategory` - Category management
17. `studentlogin/class-detail` - Login flow

## Next Steps

1. **Verify existing methods:**
   - Check if `student/updateContentStartTime` has a route
   - Check if `mailbox/sendMessage` can alias to `mailbox/send`

2. **Immediate migration (High Priority):**
   - Add routes for existing controller methods
   - Create missing controller methods for high-priority endpoints

3. **This week (Medium Priority):**
   - Migrate 3 medium-priority endpoints

4. **Later (Low Priority):**
   - Create proxy or migrate low-priority endpoints

## Files Generated

- Frontend endpoints: `tmp/frontend_endpoints.txt`
- CI4 routes: `tmp/ci4_routes.txt`
- Missing endpoints: `tmp/missing_clean.txt`
- Detailed report: `MISSING_ENDPOINTS_FINAL.md`








