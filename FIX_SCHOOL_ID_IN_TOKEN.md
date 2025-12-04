# Fix: School ID Not in Token

The school record exists, but your token doesn't have `school_id`. Here are the solutions:

## Solution 1: Re-Login to Get New Token (Recommended)

If the token was generated before `school_id = 59` was set, it won't have `school_id`.

**Steps:**
1. **Log out** from your application
2. **Log in again** with the same credentials
3. The new token will include `school_id = 59`
4. Use the new token for API calls

## Solution 2: Pass school_id in Request Payload

The updated Dashboard controller accepts `school_id` in the request body:

```json
POST https://edserver.edquillcrm.com/public/api/dashboard
Headers:
  Accesstoken: your-token-here

Body:
{
    "from": "2025-10-24",
    "to": "2025-11-23",
    "timezone": "America/New_York",
    "school_id": 59
}
```

## Solution 3: Verify Token Contains school_id

**Decode your token** to check if it has `school_id`:

You can use an online JWT decoder or check via API:

```bash
# Check token payload
curl -X POST https://edserver.edquillcrm.com/public/auth/token \
  -H "Accesstoken: your-token-here"
```

**If token doesn't have `school_id`:**
- The token was generated before school_id was set
- Re-login to get a new token

## Solution 4: Verify Updated Dashboard.php is on Server

Make sure the updated `Dashboard.php` is uploaded to GoDaddy. It should have the fallback logic:

```php
// Get school_id from token, payload, or user profile
$schoolId = $this->getSchoolId($token);

// Fallback 1: Check if school_id is in request payload
if (!$schoolId && isset($payload['school_id'])) {
    $schoolId = (int) $payload['school_id'];
}

// Fallback 2: Get from user profile if available
if (!$schoolId) {
    $userId = $this->getUserId($token);
    if ($userId) {
        // ... get from database
    }
}
```

## Quick Test

**Test with school_id in payload:**
```json
POST https://edserver.edquillcrm.com/public/api/dashboard
{
    "from": "2025-10-24",
    "to": "2025-11-23",
    "timezone": "America/New_York",
    "school_id": 59
}
```

This should work even if the token doesn't have `school_id`.

## Most Likely Fix

**Re-login** to get a fresh token that includes `school_id = 59`. The login process in `UserModel.php` includes `school_id` in the token payload (line 103), so a new login will fix it.

---

**Try Solution 2 first (pass school_id in payload) - it should work immediately!**






