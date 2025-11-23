# Actually Missing Endpoints - Migration Required

Generated: 2025-11-22 21:41:52

## Summary

- **Total Checked:** 16
- **Found in CI4:** 0
- **Actually Missing:** 16

## Missing Endpoints by Category

### Classes (3)

- `classes/addAnnouncementComments`
- `classes/getAnnouncementComments`
- `classes/updateComments`

### Content (1)

- `content/questionSkill`

### Mailbox (2)

- `mailbox/sendMessage`
- `mailbox/update`

### Student (8)

- `student/addCategory`
- `student/editCategory`
- `student/getCurrentDateTime`
- `student/getModuleSubject`
- `student/getOpenAiFeedback`
- `student/googleRegister`
- `student/listCategory`
- `student/updateContentStartTime`

### Studentlogin (1)

- `studentlogin/class-detail`

### User (1)

- `user/googleAuthenticate`

## Migration Priority

### High Priority (Core Functionality)
- `student/updateContentStartTime`
- `mailbox/sendMessage`
- `mailbox/update`
- `classes/addAnnouncementComments`
- `classes/getAnnouncementComments`
- `classes/updateComments`

### Medium Priority
- `content/questionSkill`
- `student/getCurrentDateTime`
- `student/getModuleSubject`

### Low Priority (Can use proxy)
- `student/googleRegister`
- `user/googleAuthenticate`
- `student/getOpenAiFeedback`
- `student/addCategory`
- `student/editCategory`
- `student/listCategory`
