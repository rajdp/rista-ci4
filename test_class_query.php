<?php
/**
 * Test script to check if a class is being returned by the query
 * Run this from command line: php test_class_query.php
 */

// Include CodeIgniter bootstrap
require_once __DIR__ . '/vendor/autoload.php';

$pathsConfig = FCPATH . '../app/Config/Paths.php';
require realpath($pathsConfig) ?: $pathsConfig;

$paths = new Config\Paths();
require rtrim($paths->systemDirectory, '\\/ ') . '/bootstrap.php';

$db = \Config\Database::connect();

// Test parameters - adjust these based on your created class
$schoolId = 3; // Your school_id
$classId = null; // Set this to your class_id if you know it

echo "=== Testing Class Query ===\n\n";

// First, let's see all classes for this school
echo "1. All classes for school_id = $schoolId:\n";
$builder = $db->table('class c');
$builder->select('c.class_id, c.class_name, c.school_id, c.status, c.class_status, c.subject, c.grade, c.start_date, c.end_date');
$builder->where('c.school_id', $schoolId);
$allClasses = $builder->get()->getResultArray();

echo "Found " . count($allClasses) . " classes:\n";
foreach ($allClasses as $class) {
    echo "  - Class ID: {$class['class_id']}, Name: {$class['class_name']}, Status: {$class['status']}, Class Status: {$class['class_status']}\n";
    echo "    Subject: {$class['subject']}, Grade: {$class['grade']}\n";
    echo "    Dates: {$class['start_date']} to {$class['end_date']}\n\n";
}

// Now test the actual list query
echo "\n2. Testing the list() query with filters:\n";
$params = [
    'school_id' => $schoolId,
    'type' => '1', // Default type
    'page_no' => 1,
    'records_per_page' => 10
];

$builder = $db->table('class c');
$builder->select('c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date,
                 c.status, c.class_status, c.class_type, c.tags, c.class_code, c.batch_id,
                 c.meeting_link, c.meeting_id, c.passcode, c.announcement_type,
                 s.subject_name, g.grade_name,
                 (SELECT COUNT(*) FROM student_class sc 
                  WHERE sc.class_id = c.class_id AND sc.status = 1) as no_of_students,
                 (SELECT GROUP_CONCAT(DISTINCT teacher_id) FROM class_schedule 
                  WHERE class_id = c.class_id) as teacher_ids,
                 (SELECT CONCAT_WS(", ", GROUP_CONCAT(DISTINCT CONCAT_WS(" ", up.first_name, up.last_name)))
                  FROM class_schedule cs2
                  LEFT JOIN user_profile up ON FIND_IN_SET(up.user_id, cs2.teacher_id) > 0
                  WHERE cs2.class_id = c.class_id) as teacher_name');
$builder->join('subject s', 'c.subject = s.subject_id', 'left');
$builder->join('grade g', 'c.grade = g.grade_id', 'left');

// Apply filters
if (isset($params['school_id']) && !empty($params['school_id'])) {
    $builder->where('c.school_id', $params['school_id']);
}

// Always filter by active status
$builder->where('c.status', '1');

if (isset($params['type']) && !empty($params['type'])) {
    // Type filtering logic
    if ($params['type'] == 2) {
        $builder->where('c.start_date >', date('Y-m-d'));
    }
    elseif ($params['type'] == 3) {
        $builder->where('c.start_date <=', date('Y-m-d'));
        $builder->where('c.end_date >=', date('Y-m-d'));
    }
    elseif ($params['type'] == 4) {
        $builder->where('c.end_date <', date('Y-m-d'));
    }
}

$builder->distinct();
$builder->orderBy('c.class_id', 'DESC');

if (isset($params['page_no']) && isset($params['records_per_page'])) {
    $offset = ($params['page_no'] - 1) * $params['records_per_page'];
    $builder->limit($params['records_per_page'], $offset);
}

echo "SQL Query:\n";
echo $builder->getCompiledSelect(false) . "\n\n";

$classes = $builder->get()->getResultArray();

echo "Found " . count($classes) . " classes with the list() query:\n";
foreach ($classes as $class) {
    echo "  - Class ID: {$class['class_id']}, Name: {$class['class_name']}\n";
    echo "    Subject: {$class['subject']} ({$class['subject_name']}), Grade: {$class['grade']} ({$class['grade_name']})\n";
    echo "    Status: {$class['status']}, Class Status: {$class['class_status']}\n\n";
}

// Check if subject exists
echo "\n3. Checking if subject exists:\n";
if (!empty($allClasses)) {
    $firstClass = $allClasses[0];
    $subjectId = $firstClass['subject'];
    if ($subjectId) {
        $subject = $db->table('subject')->where('subject_id', $subjectId)->get()->getRowArray();
        if ($subject) {
            echo "Subject ID $subjectId exists: {$subject['subject_name']}\n";
        } else {
            echo "WARNING: Subject ID $subjectId does NOT exist in subject table!\n";
        }
    } else {
        echo "Class has no subject_id\n";
    }
}

echo "\n=== Test Complete ===\n";

