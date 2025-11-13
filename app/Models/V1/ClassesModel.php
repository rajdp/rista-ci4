<?php

namespace App\Models\V1;

class ClassesModel extends BaseModel
{
    protected $table = 'class';
    protected $primaryKey = 'class_id';
    protected $allowedFields = [
        'class_name', 'school_id', 'subject', 'grade', 'batch_id', 
        'start_date', 'end_date', 'status', 'class_status', 'class_type',
        'announcement_type', 'tags', 'created_by', 'created_date', 'modified_date'
    ];

    /**
     * Get teacher list for a school
     */
    public function getTeacherList($schoolId)
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT DISTINCT u.user_id AS teacher_id, 
                    CONCAT_WS(' ', up.first_name, up.last_name) AS teacher_name,
                    up.profile_url
                    FROM user u
                    INNER JOIN user_profile up ON u.user_id = up.user_id
                    WHERE u.role_id = 2 
                    AND u.school_id = ? 
                    AND u.status = 1
                    ORDER BY up.first_name";
        
        return $db->query($query, [$schoolId])->getResultArray();
    }

    /**
     * Get class list
     */
    public function getClassList($data)
    {
        $schoolId = $data->school_id ?? 0;
        
        $builder = $this->getBuilder('class c');
        $builder->select('c.class_id, c.class_name, c.subject, c.grade, c.start_date, c.end_date, 
                         c.status, c.class_status, c.class_type, c.tags');
        $builder->where('c.school_id', $schoolId);
        $builder->where('c.status', 1);
        $builder->orderBy('c.class_id', 'DESC');
        
        return $this->getResult($builder);
    }

    /**
     * Create new class
     */
    public function createClass($data)
    {
        $payload = $this->decodePayload($data);

        $className = trim($payload['class_name'] ?? ($payload['name'] ?? ''));
        if ($className === '') {
            throw new \InvalidArgumentException('Class name is required.');
        }

        $schoolId = (int)($payload['school_id'] ?? 0);
        if ($schoolId <= 0) {
            throw new \InvalidArgumentException('School ID is required.');
        }

        $classData = [
            'class_name' => $className,
            'school_id' => $schoolId,
            'teacher_id' => $this->normalizeIdList($payload['teacher_id'] ?? ($payload['teacherid'] ?? [])),
            'subject' => $this->normalizeIdList($payload['subject'] ?? []),
            'grade' => $this->normalizeIdList($payload['grade'] ?? []),
            'batch_id' => $this->normalizeIdList($payload['batch_id'] ?? []),
            'start_date' => $this->normalizeDate($payload['start_date'] ?? null),
            'end_date' => $this->normalizeDate($payload['end_date'] ?? null),
            'status' => (string)($payload['status'] ?? '1'),
            'class_status' => (string)($payload['class_status'] ?? '0'),
            'class_type' => (string)($payload['class_type'] ?? '1'),
            'announcement_type' => (string)($payload['announcement_type'] ?? '2'),
            'tags' => $this->normalizeTags($payload['tags'] ?? []),
            'profile_url' => $payload['profile_url'] ?? '',
            'profile_thumb_url' => $payload['profile_thumb_url'] ?? '',
            'video_link' => $this->normalizeVideoLinks($payload['video_link'] ?? []),
            'meeting_link' => $payload['meeting_link'] ?? '',
            'meeting_id' => $payload['meeting_id'] ?? '',
            'passcode' => $payload['passcode'] ?? '',
            'telephone_number' => $payload['telephone_number'] ?? '',
            'course_id' => $this->normalizeScalar($payload['course_id'] ?? null),
            'registration_start_date' => $this->normalizeDate($payload['registration_start_date'] ?? null),
            'registration_end_date' => $this->normalizeDate($payload['registration_end_date'] ?? null),
            'payment_type' => $payload['payment_type'] ?? '',
            'payment_sub_type' => $payload['payment_sub_type'] ?? '',
            'cost' => $this->normalizeNumber($payload['cost'] ?? null),
            'discount_amount' => $this->normalizeNumber($payload['discount'] ?? null),
            'actual_cost' => $this->calculateActualCost($payload['cost'] ?? null, $payload['discount'] ?? null),
            'total_slots' => $this->normalizeNumber($payload['total_slots'] ?? $payload['slot_available'] ?? null),
            'add_course' => $this->normalizeBooleanFlag($payload['add_course'] ?? null),
            'allow_zoom_api' => $payload['allow_zoom_api'] ?? '',
            'class_code' => $this->generateClassCode(),
            'created_by' => (int)($payload['user_id'] ?? 0),
            'created_date' => date('Y-m-d H:i:s'),
        ];

        $classFieldNames = array_flip($this->getTableFields('class'));
        $insertData = array_intersect_key($classData, $classFieldNames);

        if (empty($insertData)) {
            throw new \RuntimeException('No valid class data to insert.');
        }

        if (!$this->db->table('class')->insert($insertData)) {
            return false;
        }

        $classId = (int)$this->db->insertID();
        if ($classId <= 0) {
            return false;
        }

        $payload['class_id'] = $classId;

        $this->saveClassNotes($classId, $payload);
        $this->saveClassSchedule($classId, $payload);

        $classDetails = $this->getClassDetails($classId);

        return !empty($classDetails) ? $classDetails : [['class_id' => $classId]];
    }

    /**
     * Update class
     */
    public function updateClass($classId, $data)
    {
        $classId = (int)$classId;
        if ($classId <= 0) {
            throw new \InvalidArgumentException('Invalid class ID provided.');
        }

        $payload = $this->decodePayload($data);
        if (empty($payload)) {
            throw new \InvalidArgumentException('Invalid payload.');
        }

        $className = trim($payload['class_name'] ?? ($payload['name'] ?? ''));
        if ($className === '') {
            throw new \InvalidArgumentException('Class name is required.');
        }

        $schoolId = (int)($payload['school_id'] ?? 0);
        if ($schoolId <= 0) {
            throw new \InvalidArgumentException('School ID is required.');
        }

        $classData = [
            'class_name' => $className,
            'school_id' => $schoolId,
            'teacher_id' => $this->normalizeIdList($payload['teacher_id'] ?? ($payload['teacherid'] ?? [])),
            'subject' => $this->normalizeIdList($payload['subject'] ?? []),
            'grade' => $this->normalizeIdList($payload['grade'] ?? []),
            'batch_id' => $this->normalizeIdList($payload['batch_id'] ?? []),
            'start_date' => $this->normalizeDate($payload['start_date'] ?? null),
            'end_date' => $this->normalizeDate($payload['end_date'] ?? null),
            'status' => (string)($payload['status'] ?? '1'),
            'class_status' => (string)($payload['class_status'] ?? '0'),
            'class_type' => (string)($payload['class_type'] ?? '1'),
            'announcement_type' => (string)($payload['announcement_type'] ?? '2'),
            'tags' => $this->normalizeTags($payload['tags'] ?? []),
            'profile_url' => $payload['profile_url'] ?? '',
            'profile_thumb_url' => $payload['profile_thumb_url'] ?? '',
            'video_link' => $this->normalizeVideoLinks($payload['video_link'] ?? []),
            'meeting_link' => $payload['meeting_link'] ?? '',
            'meeting_id' => $payload['meeting_id'] ?? '',
            'passcode' => $payload['passcode'] ?? '',
            'telephone_number' => $payload['telephone_number'] ?? '',
            'course_id' => $this->normalizeScalar($payload['course_id'] ?? null),
            'registration_start_date' => $this->normalizeDate($payload['registration_start_date'] ?? null),
            'registration_end_date' => $this->normalizeDate($payload['registration_end_date'] ?? null),
            'payment_type' => $payload['payment_type'] ?? '',
            'payment_sub_type' => $payload['payment_sub_type'] ?? '',
            'cost' => $this->normalizeNumber($payload['cost'] ?? null),
            'discount_amount' => $this->normalizeNumber($payload['discount'] ?? null),
            'actual_cost' => $this->calculateActualCost($payload['cost'] ?? null, $payload['discount'] ?? null),
            'total_slots' => $this->normalizeNumber($payload['total_slots'] ?? $payload['slot_available'] ?? null),
            'add_course' => $this->normalizeBooleanFlag($payload['add_course'] ?? null),
            'allow_zoom_api' => $payload['allow_zoom_api'] ?? '',
            'modified_by' => (int)($payload['user_id'] ?? 0),
            'modified_date' => date('Y-m-d H:i:s'),
        ];

        $classFields = array_flip($this->getTableFields('class'));
        $updateData = array_intersect_key($classData, $classFields);

        if (empty($updateData)) {
            throw new \RuntimeException('No valid class data to update.');
        }

        $this->db->table('class')->where('class_id', $classId)->update($updateData);

        // Update notes if provided
        if (isset($payload['notes']) && is_array($payload['notes'])) {
            $this->db->table('class_notes')->where('class_id', $classId)->delete();
            $this->saveClassNotes($classId, $payload);
        }

        // Update schedules if provided
        if (!empty($payload['availabilityDate']) && is_array($payload['availabilityDate'])) {
            $this->db->table('class_schedule')->where('class_id', $classId)->delete();
            $this->saveClassSchedule($classId, $payload);
        }

        return $this->getClassDetails($classId);
    }

    /**
     * Get comment count for classes
     */
    public function getCommentCount($data)
    {
        // Return empty array for now - this is typically used for class notes/comments
        // which may not be critical for initial testing
        return [];
    }

    /**
     * Get class list based on type
     * Type 8 = Grade report classes (active classes that have started)
     */
    public function classList($params)
    {
        $db = \Config\Database::connect();
        
        $type = $params['type'] ?? 1;
        $schoolId = $params['school_id'] ?? 0;
        
        // Build query - ALWAYS use user's school(s) from their profile first
        $whereClause = "";
        if (isset($params['user_id']) && $params['user_id'] > 0) {
            // Get user's school_id - user.school_id can be comma-separated
            $userSchools = $db->query("SELECT school_id FROM user WHERE user_id = {$params['user_id']}")->getRowArray();
            
            if ($userSchools && !empty($userSchools['school_id'])) {
                // User table has school_id
                $userSchoolId = $userSchools['school_id'];
                
                // Check if comma-separated list
                if (strpos($userSchoolId, ',') !== false) {
                    $whereClause = "FIND_IN_SET(c.school_id, '$userSchoolId')";
                } else {
                    $whereClause = "c.school_id = $userSchoolId";
                }
            } else {
                // No user school found - use any school with classes (for development/testing)
                $whereClause = "1=1";
            }
        } else {
            // No user_id - use provided school_id or get any classes
            $whereClause = ($schoolId > 0) ? "c.school_id = $schoolId" : "1=1";
        }
        
        // Query for active classes
        $query = "SELECT c.class_id, c.school_id, c.class_name, c.batch_id, c.class_type,
                    c.subject, c.start_date, c.end_date, c.tags, c.class_status, c.grade,
                    COALESCE((SELECT GROUP_CONCAT(batch_name) FROM batch WHERE FIND_IN_SET(batch_id, c.batch_id)),'-') as classroom_name,
                    COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject)),'') AS subject_name,
                    COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)),'') AS grade_name,
                    (CASE WHEN c.start_date > CURRENT_DATE() THEN 2 
                    WHEN c.end_date >= CURRENT_DATE() THEN 3
                    WHEN c.end_date < CURRENT_DATE() THEN 4
                    ELSE 1 END) AS classDate_status
                    FROM class c
                    WHERE $whereClause AND c.status = 1
                    ORDER BY c.start_date DESC, c.class_name";
        
        $result = $db->query($query)->getResultArray();
        
        return $result;
    }

    /**
     * View assignments for a class
     * Based on CI3 Classes_model->viewAssignments()
     */
    public function viewAssignments($params)
    {
        $db = \Config\Database::connect();
        
        $schoolAutoReview = '';
        $condition = '';
        $condition1 = "cc.start_date ASC";
        
        // Filter by assignment type
        if (isset($params['type'])) {
            if ($params['type'] == 0) {
                $condition = '';
            } elseif ($params['type'] == 1) {
                // Upcoming
                $condition = "AND cc.start_date > CURRENT_DATE()";
            } elseif ($params['type'] == 2) {
                // In progress
                $condition = "AND cc.start_date <= CURRENT_DATE() AND (cc.end_date >= CURRENT_DATE() OR cc.end_date = '0000-00-00')";
            } elseif ($params['type'] == 3) {
                // Completed
                $condition = "AND cc.end_date < CURRENT_DATE() AND cc.end_date != '0000-00-00'";
                $condition1 = "cc.start_date DESC";
            }
        }
        
        // Add school auto-review setting if school_id provided
        if (isset($params['school_id']) && $params['school_id'] > 0) {
            $schoolAutoReview = ", COALESCE((SELECT value FROM admin_settings_school WHERE name = 'allow_autograde_settings' AND school_id = {$params['school_id']}),'') as allow_autograde";
        }
        
        $query = "SELECT cnt.content_id, cnt.name AS content_name, c.class_name, c.class_id,
                    COALESCE(cnt.description, '') AS description,
                    COALESCE(cnt.profile_url, '') AS profile_url, 
                    COALESCE(cnt.profile_thumb_url, '') AS profile_thumb_url,
                    COALESCE(cnt.file_path, '') AS file_path, 
                    COALESCE(cnt.teacher_version, '') AS teacher_version_path,
                    COALESCE(cnt.links, '') AS links,
                    COALESCE(cnt.file_text, '') AS file_text,
                    COALESCE(cnt.tags,'') AS tags, cnt.content_type, cnt.content_format, 
                    cc.start_date, cc.end_date,
                    TIME_FORMAT(cc.start_time, '%h:%i %p') as start_time,
                    TIME_FORMAT(cc.end_time, '%h:%i %p') as end_time, cc.id as class_content_id,
                    (CASE WHEN cc.start_date > CURRENT_DATE() THEN 2
                    WHEN cc.end_date >= CURRENT_DATE() THEN 3
                    WHEN cc.end_date < CURRENT_DATE() THEN 4 ELSE 1 END) as content_date_status,
                    cc.topic_id, COALESCE(t.topic, '') as topic, cc.auto_review $schoolAutoReview
                    FROM class AS c 
                    LEFT JOIN class_content AS cc ON c.class_id = cc.class_id
                    LEFT JOIN content AS cnt ON cc.content_id = cnt.content_id
                    LEFT JOIN topic AS t ON t.topic_id = cc.topic_id
                    WHERE c.class_id = {$params['class_id']} AND cnt.content_type = 2
                    AND cnt.status = 1 AND c.status = 1 AND cc.status = 1 $condition 
                    ORDER BY CASE WHEN cc.start_date <= CURRENT_DATE()
                    AND cc.end_date != '0000-00-00' AND cc.end_date >= CURRENT_DATE() THEN 1
                    WHEN cc.start_date <= CURRENT_DATE() AND cc.end_date = '0000-00-00' THEN 2
                    WHEN cc.start_date >= CURRENT_DATE() THEN 3 ELSE 4 END, $condition1";
        
        log_message('debug', '🔍 ClassesModel::viewAssignments query: ' . $query);
        
        $result = $db->query($query)->getResultArray();
        
        log_message('debug', '✅ ClassesModel::viewAssignments found ' . count($result) . ' assignments');
        
        return $result;
    }

    /**
     * Update the display order for topics
     */
    public function updateTopicOrder(array $topicIds, int $userId = null): bool
    {
        $db = \Config\Database::connect();

        $normalizedIds = [];
        foreach ($topicIds as $id) {
            $id = (int) $id;
            if ($id > 0) {
                $normalizedIds[] = $id;
            }
        }

        if (empty($normalizedIds)) {
            return false;
        }

        $db->transBegin();

        try {
            $builder = $db->table('topic');
            foreach ($normalizedIds as $index => $topicId) {
                $data = [
                    'display_order' => $index + 1,
                    'modified_date' => date('Y-m-d H:i:s'),
                ];

                if ($userId !== null && $userId > 0) {
                    $data['modified_by'] = $userId;
                }

                $builder->where('topic_id', $topicId)->update($data);

                if ($db->affectedRows() === 0) {
                    // Ensure the topic exists; if it does not, rollback
                    $exists = $db->table('topic')->where('topic_id', $topicId)->countAllResults();
                    if ($exists === 0) {
                        throw new \RuntimeException('Topic ID ' . $topicId . ' not found');
                    }
                }
            }

            $db->transCommit();
            return true;
        } catch (\Throwable $e) {
            $db->transRollback();
            log_message('error', 'ClassesModel::updateTopicOrder failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Decode payload into an associative array
     */
    private function decodePayload($data): array
    {
        if (is_array($data)) {
            return $data;
        }

        if (is_object($data)) {
            return json_decode(json_encode($data), true) ?? [];
        }

        return [];
    }

    private function normalizeIdList($value): string
    {
        if (is_array($value)) {
            $items = [];
            foreach ($value as $item) {
                if (is_array($item)) {
                    $candidate = $item['value'] ?? $item['id'] ?? null;
                } elseif (is_object($item)) {
                    $objectArray = (array)$item;
                    $candidate = $objectArray['value'] ?? $objectArray['id'] ?? reset($objectArray);
                } else {
                    $candidate = $item;
                }
                $candidate = trim((string)$candidate);
                if ($candidate !== '' && $candidate !== '0') {
                    $items[] = $candidate;
                }
            }
            $items = array_unique($items);
            return implode(',', $items);
        }

        if ($value === null) {
            return '';
        }

        $value = trim((string)$value);
        if ($value === '' || $value === '0') {
            return '';
        }

        if (strpos($value, ',') !== false) {
            $parts = array_filter(array_map('trim', explode(',', $value)), static function ($item) {
                return $item !== '' && $item !== '0';
            });
            $parts = array_unique($parts);
            return implode(',', $parts);
        }

        return $value;
    }

    private function normalizeTags($value): string
    {
        if (is_array($value)) {
            $tags = array_filter(array_map(static function ($tag) {
                return trim(is_array($tag) ? ($tag['value'] ?? '') : (string)$tag);
            }, $value));
            $tags = array_unique($tags);
            return implode(',', $tags);
        }

        $value = trim((string)$value);
        if (strpos($value, ',') !== false) {
            $parts = array_filter(array_map('trim', explode(',', $value)));
            $parts = array_unique($parts);
            return implode(',', $parts);
        }

        return $value;
    }

    private function normalizeDate($value): ?string
    {
        if ($value === null || $value === '' || $value === '0000-00-00') {
            return null;
        }

        if (is_array($value)) {
            if (isset($value['singleDate']['jsDate'])) {
                $timestamp = strtotime($value['singleDate']['jsDate']);
                return $timestamp ? date('Y-m-d', $timestamp) : null;
            }
            if (isset($value['date'])) {
                $timestamp = strtotime($value['date']);
                return $timestamp ? date('Y-m-d', $timestamp) : null;
            }
        }

        $timestamp = strtotime((string)$value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    private function normalizeScalar($value): ?string
    {
        if ($value === null || $value === '' || $value === '0') {
            return null;
        }
        return trim((string)$value);
    }

    private function normalizeNumber($value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float)$value;
        }

        $clean = preg_replace('/[^0-9.\-]/', '', (string)$value);
        if ($clean === '' || $clean === '.' || $clean === '-.' || $clean === '-0') {
            return null;
        }

        return is_numeric($clean) ? (float)$clean : null;
    }

    private function calculateActualCost($cost, $discount): ?float
    {
        $costValue = $this->normalizeNumber($cost);
        $discountValue = $this->normalizeNumber($discount);

        if ($costValue === null) {
            return null;
        }

        if ($discountValue === null) {
            return $costValue;
        }

        $actual = $costValue - $discountValue;
        return $actual >= 0 ? $actual : 0.0;
    }

    private function normalizeVideoLinks($videoLinks): ?string
    {
        if (empty($videoLinks)) {
            return null;
        }

        if (is_string($videoLinks)) {
            return $videoLinks;
        }

        if (!is_array($videoLinks)) {
            return null;
        }

        $filtered = array_values(array_filter($videoLinks, static function ($item) {
            if (is_array($item)) {
                return !empty($item['link']);
            }
            if (is_object($item)) {
                return !empty($item->link ?? null);
            }
            return trim((string)$item) !== '';
        }));

        if (empty($filtered)) {
            return null;
        }

        return json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function normalizeBooleanFlag($value): int
    {
        if (is_bool($value)) {
            return $value ? 1 : 0;
        }

        $value = strtolower(trim((string)$value));
        return in_array($value, ['1', 'true', 'yes'], true) ? 1 : 0;
    }

    private function generateClassCode(): string
    {
        $attempts = 0;
        do {
            $attempts++;
            try {
                $code = strtoupper(bin2hex(random_bytes(4)));
            } catch (\Throwable $e) {
                $code = strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 8));
            }

            $exists = $this->db->table('class')
                ->where('class_code', $code)
                ->countAllResults();

            if ($exists === 0 || $attempts > 5) {
                return $code;
            }
        } while (true);
    }

    private function getTableFields(string $table): array
    {
        try {
            return $this->db->getFieldNames($table);
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function saveClassNotes(int $classId, array $payload): void
    {
        $notes = $payload['notes'] ?? [];
        if (!is_array($notes) || empty($notes)) {
            return;
        }

        $noteFields = array_flip($this->getTableFields('class_notes'));
        if (empty($noteFields)) {
            return;
        }

        $rows = [];
        $userId = (int)($payload['user_id'] ?? 0);

        foreach ($notes as $note) {
            if (is_object($note)) {
                $note = (array)$note;
            }
            if (!is_array($note)) {
                continue;
            }

            $noteText = trim((string)($note['note'] ?? ''));
            if ($noteText === '') {
                continue;
            }

            $rows[] = array_intersect_key([
                'class_id' => $classId,
                'note' => $noteText,
                'add_date' => $this->normalizeDate($note['add_date'] ?? null) ?? date('Y-m-d'),
                'status' => (string)($note['status'] ?? '1'),
                'created_by' => $userId,
                'created_date' => date('Y-m-d H:i:s'),
            ], $noteFields);
        }

        if (!empty($rows)) {
            $this->db->table('class_notes')->insertBatch($rows);
        }
    }

    private function normalizeScheduleTime($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            if (isset($value['hour'])) {
                $hour = (int)$value['hour'];
                $minute = (int)($value['minute'] ?? 0);
                $meridian = strtoupper(trim($value['meridian'] ?? ''));
                $time = sprintf('%02d:%02d', $hour, $minute);
                return $meridian !== '' ? "{$time} {$meridian}" : $time;
            }
            if (isset($value['time'])) {
                return trim((string)$value['time']);
            }
        }

        $valueStr = trim((string)$value);
        return $valueStr === '' ? null : $valueStr;
    }

    private function saveClassSchedule(int $classId, array $payload): void
    {
        $availability = $payload['availabilityDate'] ?? [];
        if (!is_array($availability) || empty($availability)) {
            return;
        }

        $scheduleFields = array_flip($this->getTableFields('class_schedule'));
        if (empty($scheduleFields)) {
            return;
        }

        $allowZoom = (string)($payload['allow_zoom_api'] ?? '');
        $schoolId = (int)($payload['school_id'] ?? 0);
        $userId = (int)($payload['user_id'] ?? 0);

        $rows = [];
        foreach ($availability as $item) {
            if (is_object($item)) {
                $item = (array)$item;
            }
            if (!is_array($item)) {
                continue;
            }

            $slotDay = $item['slotday'] ?? $item['slot_day'] ?? null;
            $start = $this->normalizeScheduleTime($item['slotstarttime'] ?? $item['start_time'] ?? null);
            $end = $this->normalizeScheduleTime($item['slotendtime'] ?? $item['end_time'] ?? null);

            if ($slotDay === null || $start === null || $end === null) {
                continue;
            }

            $teacherId = $this->normalizeIdList($item['teacher_id'] ?? $item['teacherid'] ?? []);

            $rows[] = array_intersect_key([
                'class_id' => $classId,
                'teacher_id' => $teacherId,
                'start_time' => $start,
                'end_time' => $end,
                'slot_days' => (int)$slotDay,
                'slotselected' => 1,
                'meeting_link' => $allowZoom === '0' ? ($item['meeting_link'] ?? '') : '',
                'meeting_id' => $allowZoom === '0' ? ($item['meeting_id'] ?? '') : '',
                'passcode' => $allowZoom === '0' ? ($item['passcode'] ?? '') : '',
                'telephone_number' => $item['telephone_number'] ?? '',
                'school_id' => $schoolId,
                'created_by' => $userId,
                'created_date' => date('Y-m-d H:i:s'),
            ], $scheduleFields);
        }

        if (!empty($rows)) {
            $this->db->table('class_schedule')->insertBatch($rows);
        }
    }

    private function getClassSchedule(int $classId): array
    {
        try {
            $builder = $this->db->table('class_schedule cs');
        } catch (\Throwable $e) {
            return [];
        }

        $builder->select('cs.*, GROUP_CONCAT(DISTINCT CONCAT(up.first_name, " ", up.last_name) SEPARATOR ",") as teacher_names');
        $builder->join('user_profile up', 'FIND_IN_SET(up.user_id, cs.teacher_id)', 'left');
        $builder->where('cs.class_id', $classId);
        $builder->orderBy('cs.slot_days', 'ASC');
        $builder->groupBy('cs.id');

        $results = $builder->get()->getResultArray();
        $schedules = [];

        foreach ($results as $index => $row) {
            $teacherIds = $this->explodeList($row['teacher_id'] ?? '');
            $teacherNames = [];
            if (!empty($row['teacher_names'])) {
                $teacherNames = array_values(array_filter(array_map('trim', explode(',', $row['teacher_names']))));
            }

            $schedules[] = [
                'slotday' => isset($row['slot_days']) ? (int)$row['slot_days'] : null,
                'slotstarttime' => $this->formatScheduleTime($row['start_time'] ?? ''),
                'slotendtime' => $this->formatScheduleTime($row['end_time'] ?? ''),
                'teacher_id' => $teacherIds,
                'teacher_name' => $teacherNames,
                'meeting_link' => $row['meeting_link'] ?? '',
                'meeting_id' => $row['meeting_id'] ?? '',
                'passcode' => $row['passcode'] ?? '',
                'telephone_number' => $row['telephone_number'] ?? '',
                'shechdule_id' => $row['id'] ?? null,
                'slotselected' => $row['slotselected'] ?? 1,
                'uniqueKey' => $index,
            ];
        }

        return $schedules;
    }

    private function formatScheduleTime($value): string
    {
        $valueStr = trim((string)$value);
        if ($valueStr === '') {
            return '';
        }

        $timestamp = strtotime($valueStr);
        return $timestamp ? date('h:i A', $timestamp) : $valueStr;
    }

    private function getClassNotes(int $classId): array
    {
        try {
            $builder = $this->db->table('class_notes');
        } catch (\Throwable $e) {
            return [];
        }

        $builder->select('note, add_date, status');
        $builder->where('class_id', $classId);
        $builder->orderBy('id', 'DESC');

        $notes = $builder->get()->getResultArray();

        return array_map(function ($note) {
            $note['add_date'] = isset($note['add_date']) && $note['add_date'] !== '0000-00-00'
                ? date('Y-m-d', strtotime($note['add_date']))
                : date('Y-m-d');
            return $note;
        }, $notes);
    }

    private function getClassDetails(int $classId): array
    {
        $builder = $this->db->table('class c');
        $builder->select(
            "c.*,
            (CASE 
                WHEN c.start_date > CURRENT_DATE() THEN 2
                WHEN c.end_date >= CURRENT_DATE() THEN 3
                WHEN c.end_date < CURRENT_DATE() THEN 4
                ELSE 1
            END) AS classDate_status,
            COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject)), '') AS subject_name,
            COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)), '') AS grade_name"
        );
        $builder->where('c.class_id', $classId);

        $result = $builder->get()->getResultArray();
        if (empty($result)) {
            return [];
        }

        $class = $result[0];

        $originalBatchId = $class['batch_id'] ?? '';
        $class['batch_id_string'] = $originalBatchId;
        $class['batch_id'] = $this->explodeList($originalBatchId);
        $class['tags'] = $this->explodeList($class['tags'] ?? '', false);
        $class['grade_list'] = $this->explodeList($class['grade'] ?? '');
        $class['subject_list'] = $this->explodeList($class['subject'] ?? '');
        $class['video_link'] = !empty($class['video_link']) ? json_decode($class['video_link'], true) ?? [] : [];
        $class['notes'] = $this->getClassNotes($classId);
        $class['availabilityDate'] = $this->getClassSchedule($classId);
        $class['course_id'] = $class['course_id'] ?? '';
        $class['class_type'] = (string)($class['class_type'] ?? '1');
        $class['announcement_type'] = (string)($class['announcement_type'] ?? '2');
        $class['add_course'] = isset($class['add_course']) ? (string)$class['add_course'] : '0';

        return [$class];
    }

    private function explodeList($value, bool $convertToInt = true): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_array($value)) {
            return $value;
        }

        $parts = array_filter(array_map('trim', explode(',', (string)$value)), static function ($item) {
            return $item !== '';
        });

        if ($convertToInt) {
            return array_values(array_map('strval', $parts));
        }

        return array_values($parts);
    }
}
