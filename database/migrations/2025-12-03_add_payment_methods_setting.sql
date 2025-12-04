-- Migration: Add payment_methods setting to admin_settings_school
-- Date: 2025-12-03
-- Description: Creates configurable payment methods setting for each school
-- Default values: Autopay, Zelle, Cash, Check, Other

-- Insert default payment_methods setting for all existing schools
-- Only insert if the setting doesn't already exist for the school
INSERT INTO admin_settings_school (name, description, value, school_id, settings, status, modified_date, sys_time)
SELECT 
    'payment_methods' as name,
    'Comma-separated list of available payment methods' as description,
    'Autopay, Zelle, Cash, Check, Other' as value,
    s.school_id,
    0 as settings,
    1 as status,
    NOW() as modified_date,
    NOW() as sys_time
FROM school s
WHERE NOT EXISTS (
    SELECT 1 
    FROM admin_settings_school ass 
    WHERE ass.name = 'payment_methods' 
    AND ass.school_id = s.school_id
);

-- Verify the insert
SELECT 
    ass.id,
    ass.name,
    ass.value,
    ass.school_id,
    s.name as school_name
FROM admin_settings_school ass
INNER JOIN school s ON s.school_id = ass.school_id
WHERE ass.name = 'payment_methods'
ORDER BY ass.school_id;

