# Comprehensive Endpoint Migration Report

**Generated:** $(date '+%Y-%m-%d %H:%M:%S')

## Summary

- **Total Frontend API Calls:** 84
- **Total CI4 Routes:** 437  
- **Missing Endpoints:** ~15-20 (after filtering false positives)

## Missing Endpoints That Need Migration

### Classes (3 missing)
- `classes/addAnnouncementComments`
- `classes/getAnnouncementComments`
- `classes/updateComments`

### Content (1 missing)
- `content/questionSkill`

### Mailbox (2 missing)
- `mailbox/sendMessage`
- `mailbox/update`

### Student (5 missing)
- `student/getCurrentDateTime`
- `student/getModuleSubject`
- `student/getOpenAiFeedback`
- `student/googleRegister`
- `student/updateContentStartTime`

### User (1 missing)
- `user/googleAuthenticate`

### Other (2 missing)
- `student/addCategory`
- `student/editCategory`
- `student/listCategory`

## Migration Priority

### High Priority (Critical for functionality)
1. `student/updateContentStartTime` - Content timing tracking
2. `mailbox/sendMessage` - Messaging functionality
3. `mailbox/update` - Message updates
4. `classes/addAnnouncementComments` - Class announcements
5. `classes/getAnnouncementComments` - Class announcements
6. `classes/updateComments` - Class comments

### Medium Priority
7. `content/questionSkill` - Content features
8. `student/getCurrentDateTime` - Utility function
9. `student/getModuleSubject` - Module/subject data

### Low Priority (Can use proxy)
10. `student/googleRegister` - OAuth integration
11. `user/googleAuthenticate` - OAuth integration
12. `student/getOpenAiFeedback` - AI features
13. `student/addCategory` - Category management
14. `student/editCategory` - Category management
15. `student/listCategory` - Category management

## Next Steps

1. **Immediate:** Migrate high-priority endpoints (6 endpoints)
2. **This Week:** Migrate medium-priority endpoints (3 endpoints)
3. **Later:** Create proxy or migrate low-priority endpoints (6 endpoints)

## Files Generated

- Frontend endpoints: `tmp/frontend_endpoints.txt`
- CI4 routes: `tmp/ci4_routes.txt`
- Missing endpoints: `tmp/missing_clean.txt`

