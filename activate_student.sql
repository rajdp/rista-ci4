-- SQL script to activate a student by email
-- Usage: Run this in your MySQL client, replacing 'stu@edquill.com' with the actual email

-- Step 1: Find the student's user_id
SELECT user_id, email_id, role_id, status, school_id 
FROM user 
WHERE email_id = 'stu@edquill.com' AND role_id = 5;

-- Step 2: Update user_profile_details status to 1 (active)
-- Replace <user_id> with the user_id from Step 1
UPDATE user_profile_details 
SET status = 1 
WHERE user_id = <user_id>;

-- If user_profile_details doesn't exist, create it:
-- Replace <user_id> and <school_id> with actual values
INSERT INTO user_profile_details (user_id, school_id, status, created_date) 
VALUES (<user_id>, <school_id>, 1, NOW())
ON DUPLICATE KEY UPDATE status = 1;

-- Step 3: Also ensure user table status is 1 (active)
-- Replace <user_id> with the user_id from Step 1
UPDATE user 
SET status = 1, modified_date = NOW() 
WHERE user_id = <user_id>;

-- Or run this all-in-one query (replace 'stu@edquill.com' with actual email):
UPDATE user_profile_details upd
INNER JOIN user u ON u.user_id = upd.user_id
SET upd.status = 1, u.status = 1, u.modified_date = NOW()
WHERE u.email_id = 'stu@edquill.com' AND u.role_id = 5;

