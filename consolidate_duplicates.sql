-- Student Content Consolidation Script
-- This script consolidates duplicate student_content records by keeping the one with most progress
-- and transferring class access to the kept record

-- Create a temporary table to identify which records to keep
CREATE TEMPORARY TABLE temp_keep_records AS
SELECT 
    sc1.id as keep_id,
    sc1.student_id,
    sc1.content_id,
    sc1.status,
    sc1.modified_date,
    sc1.class_content_id as keep_class_content_id
FROM student_content sc1
INNER JOIN (
    SELECT 
        student_id, 
        content_id,
        MAX(status) as max_status,
        MAX(modified_date) as max_date
    FROM student_content
    GROUP BY student_id, content_id
    HAVING COUNT(*) > 1
) sc2 ON sc1.student_id = sc2.student_id 
    AND sc1.content_id = sc2.content_id
    AND sc1.status = sc2.max_status
    AND sc1.modified_date = sc2.max_date;

-- Transfer class access from duplicate records to kept records
INSERT IGNORE INTO student_content_class_access (student_content_id, class_id, class_content_id, created_date, created_by)
SELECT DISTINCT
    tkr.keep_id,
    scca.class_id,
    scca.class_content_id,
    scca.created_date,
    scca.created_by
FROM temp_keep_records tkr
INNER JOIN student_content sc ON sc.student_id = tkr.student_id AND sc.content_id = tkr.content_id
INNER JOIN student_content_class_access scca ON scca.student_content_id = sc.id
WHERE sc.id != tkr.keep_id;

-- Remove duplicate records (keep the ones identified in temp table)
DELETE sc FROM student_content sc
INNER JOIN temp_keep_records tkr ON sc.student_id = tkr.student_id AND sc.content_id = tkr.content_id
WHERE sc.id != tkr.keep_id;

-- Clean up
DROP TEMPORARY TABLE temp_keep_records;

-- Verify consolidation
SELECT 'After consolidation:' as status;
SELECT student_id, content_id, COUNT(*) as count
FROM student_content
GROUP BY student_id, content_id
HAVING count > 1
LIMIT 5;
