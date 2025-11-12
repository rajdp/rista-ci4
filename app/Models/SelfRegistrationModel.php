<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Data access for the public self-registration portal.
 */
class SelfRegistrationModel extends Model
{
    protected $table = 'student_self_registrations';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = false; // dynamic inserts handled manually
    protected $useTimestamps = false;

    /**
     * Fetch school information and portal settings using a school key, portal domain, or ID.
     */
    public function getSchoolByIdentifier(string $identifier): ?array
    {
        $identifier = trim(strtolower($identifier));
        $db = $this->db;

        $builder = $db->table('school s');
        $builder->select("
            s.school_id,
            s.name AS school_name,
            s.school_key,
            s.portal_domain,
            s.profile_url,
            s.profile_thumb_url,
            s.address1,
            s.address2,
            s.city,
            s.state,
            s.country,
            s.postal_code,
            COALESCE(ps.portal_enabled, 1) AS portal_enabled,
            ps.primary_color,
            ps.secondary_color,
            ps.accent_color,
            ps.hero_title,
            ps.hero_subtitle,
            ps.support_email,
            ps.support_phone,
            ps.terms_url,
            ps.privacy_url,
            ps.options,
            s.portal_contact_email,
            s.portal_contact_phone
        ");
        $builder->join('school_portal_settings ps', 'ps.school_id = s.school_id', 'left');
        $builder->where('s.status', 1);
        $builder->groupStart()
            ->where('LOWER(s.school_key)', $identifier)
            ->orWhere('LOWER(s.portal_domain)', $identifier);

        if (is_numeric($identifier)) {
            $builder->orWhere('s.school_id', (int) $identifier);
        }

        $builder->groupEnd();

        $result = $builder->get(1)->getRowArray();

        if (!$result) {
            return null;
        }

        // Decode JSON options if present
        if (!empty($result['options']) && is_string($result['options'])) {
            $decoded = json_decode($result['options'], true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $result['options'] = $decoded;
            }
        }

        return $result;
    }

    /**
     * Fetch a lightweight list of active courses for a school.
     */
    public function getActiveCourses(int $schoolId): array
    {
        $builder = $this->db->table('tbl_course c');
        $builder->select("
            c.course_id,
            c.course_name,
            COALESCE(c.short_description, '') AS short_description,
            COALESCE(c.description, '') AS description,
            COALESCE(c.fees, '') AS fees,
            COALESCE(c.multiple_schedule, 0) AS multiple_schedule,
            COALESCE(c.schedule, 0) AS schedule,
            COALESCE(c.path, '') AS image_url,
            COALESCE(c.button_name, '') AS button_label,
            COALESCE(c.contact_info, '') AS contact_info
        ");
        $builder->where('c.status', 'A');
        $builder->where('c.entity_id', $schoolId);
        $builder->orderBy('c.display_order', 'ASC');
        $builder->orderBy('c.course_name', 'ASC');

        $courses = $builder->get()->getResultArray();

        if (empty($courses)) {
            return [];
        }

        $courseIds = array_column($courses, 'course_id');
        $schedulesByCourse = [];

        if (!empty($courseIds)) {
            $scheduleBuilder = $this->db->table('tbl_course_schedule sc');
            $scheduleBuilder->select("
                sc.schedule_id,
                sc.course_id,
                sc.schedule_title,
                sc.course_start_date,
                sc.course_end_date,
                sc.registration_start_date,
                sc.registration_end_date,
                sc.course_type,
                sc.payment_type,
                sc.payment_sub_type,
                sc.location_id,
                sc.cost,
                sc.discount_amount,
                sc.actual_cost,
                sc.total_slots,
                sc.slots_booked,
                sc.status
            ");
            $scheduleBuilder->whereIn('sc.course_id', $courseIds);
            $scheduleBuilder->where('sc.status', 'A');
            $scheduleBuilder->orderBy('sc.course_start_date', 'ASC');

            $schedules = $scheduleBuilder->get()->getResultArray();

            foreach ($schedules as $schedule) {
                $courseId = (int) ($schedule['course_id'] ?? 0);
                if ($courseId === 0) {
                    continue;
                }

                $schedule['cost'] = isset($schedule['cost']) ? (float) $schedule['cost'] : null;
                $schedule['discount_amount'] = isset($schedule['discount_amount']) ? (float) $schedule['discount_amount'] : null;
                $schedule['actual_cost'] = isset($schedule['actual_cost']) ? (float) $schedule['actual_cost'] : null;

                $schedulesByCourse[$courseId][] = $schedule;
            }
        }

        foreach ($courses as &$course) {
            $course['fees'] = $course['fees'] !== '' ? (float) $course['fees'] : null;
            $courseId = (int) $course['course_id'];
            $course['schedules'] = $schedulesByCourse[$courseId] ?? [];
        }
        unset($course);

        return $courses;
    }

    /**
     * Create a new self-registration record and its related entities.
     *
     * @throws \RuntimeException When the transaction fails.
     */
    public function createRegistration(array $registration, array $courses, array $documents): array
    {
        $db = $this->db;
        $db->transStart();

        $db->table('student_self_registrations')->insert($registration);
        $registrationId = (int) $db->insertID();

        if (!empty($courses)) {
            $courseRows = [];
            foreach ($courses as $course) {
                $courseRows[] = [
                    'registration_id' => $registrationId,
                    'course_id' => $course['course_id'] ?? null,
                    'schedule_id' => $course['schedule_id'] ?? null,
                    'course_name' => $course['course_name'] ?? null,
                    'schedule_title' => $course['schedule_title'] ?? null,
                    'fee_amount' => isset($course['fee_amount']) ? $course['fee_amount'] : null,
                    'created_at' => date('Y-m-d H:i:s')
                ];
            }

            if (!empty($courseRows)) {
                $db->table('student_self_registration_courses')->insertBatch($courseRows);
            }
        }

        if (!empty($documents)) {
            foreach ($documents as &$document) {
                $document['registration_id'] = $registrationId;
                $document['uploaded_at'] = date('Y-m-d H:i:s');
            }
            unset($document);

            $db->table('student_self_registration_documents')->insertBatch($documents);
        }

        $db->transComplete();

        if (! $db->transStatus()) {
            throw new \RuntimeException('Unable to save registration.');
        }

        return $this->getRegistrationById($registrationId);
    }

    /**
     * Retrieve a registration record by ID.
     */
    public function getRegistrationById(int $registrationId): array
    {
        return $this->db->table('student_self_registrations')
            ->where('id', $registrationId)
            ->get()
            ->getRowArray() ?? [];
    }

    /**
     * Load registration along with selected courses and uploaded documents.
     */
    public function getRegistrationWithRelations(int $registrationId): array
    {
        $registration = $this->getRegistrationById($registrationId);

        if (empty($registration)) {
            return [];
        }

        $courses = $this->db->table('student_self_registration_courses')
            ->where('registration_id', $registrationId)
            ->get()
            ->getResultArray();

        $documents = $this->db->table('student_self_registration_documents')
            ->where('registration_id', $registrationId)
            ->get()
            ->getResultArray();

        return [
            'registration' => $registration,
            'courses' => $courses,
            'documents' => $documents,
        ];
    }

    /**
     * Persist conversion metadata after promotion.
     */
    public function markConverted(int $registrationId, array $data): bool
    {
        return (bool) $this->db->table('student_self_registrations')
            ->where('id', $registrationId)
            ->update($data);
    }

    /**
     * Find a registration by its public code.
     */
    public function findByCode(string $code): array
    {
        return $this->db->table('student_self_registrations')
            ->where('registration_code', $code)
            ->get()
            ->getRowArray() ?? [];
    }

    /**
     * List registrations with filters and summary data.
     */
    public function listRegistrations(array $filters, int $limit, int $offset): array
    {
        $builder = $this->db->table('student_self_registrations sr');
        $builder->select(
            "sr.*, 
            COALESCE(doc_counts.document_count, 0) AS document_count,
            COALESCE(course_counts.course_count, 0) AS course_count,
            assigned.email_id AS assigned_email,
            CONCAT_WS(' ', assigned_profile.first_name, assigned_profile.last_name) AS assigned_name"
        );

        $builder->join(
            '(SELECT registration_id, COUNT(*) AS document_count FROM student_self_registration_documents GROUP BY registration_id) doc_counts',
            'doc_counts.registration_id = sr.id',
            'left'
        );

        $builder->join(
            '(SELECT registration_id, COUNT(*) AS course_count FROM student_self_registration_courses GROUP BY registration_id) course_counts',
            'course_counts.registration_id = sr.id',
            'left'
        );

        $builder->join('user assigned', 'assigned.user_id = sr.assigned_to_user_id', 'left');
        $builder->join('user_profile assigned_profile', 'assigned_profile.user_id = assigned.user_id', 'left');

        if (!empty($filters['school_id'])) {
            $builder->where('sr.school_id', (int) $filters['school_id']);
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', (string) $filters['status']);
            $builder->whereIn('sr.status', array_map('trim', $statuses));
        }

        if (!empty($filters['assigned_to_user_id'])) {
            $builder->where('sr.assigned_to_user_id', (int) $filters['assigned_to_user_id']);
        }

        if (!empty($filters['priority'])) {
            $builder->where('sr.priority', $filters['priority']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('sr.student_first_name', $search)
                ->orLike('sr.student_last_name', $search)
                ->orLike('sr.email', $search)
                ->orLike('sr.mobile', $search)
                ->orLike('sr.guardian1_email', $search)
                ->orLike('sr.guardian1_phone', $search)
                ->groupEnd();
        }

        if (!empty($filters['submitted_from'])) {
            $builder->where('sr.submitted_at >=', $filters['submitted_from']);
        }

        if (!empty($filters['submitted_to'])) {
            $builder->where('sr.submitted_at <=', $filters['submitted_to']);
        }

        if (!empty($filters['course_id'])) {
            $builder->join('student_self_registration_courses course_filter', 'course_filter.registration_id = sr.id', 'inner');
            $builder->where('course_filter.course_id', (int) $filters['course_id']);
        }

        $orderBy = $filters['order_by'] ?? 'sr.submitted_at';
        $direction = strtoupper($filters['order_direction'] ?? 'DESC');
        $direction = in_array($direction, ['ASC', 'DESC'], true) ? $direction : 'DESC';

        $builder->orderBy($orderBy, $direction);
        $builder->limit($limit, $offset);

        return $builder->get()->getResultArray();
    }

    public function countRegistrations(array $filters): int
    {
        $builder = $this->db->table('student_self_registrations sr');

        if (!empty($filters['school_id'])) {
            $builder->where('sr.school_id', (int) $filters['school_id']);
        }

        if (!empty($filters['status'])) {
            $statuses = is_array($filters['status']) ? $filters['status'] : explode(',', (string) $filters['status']);
            $builder->whereIn('sr.status', array_map('trim', $statuses));
        }

        if (!empty($filters['assigned_to_user_id'])) {
            $builder->where('sr.assigned_to_user_id', (int) $filters['assigned_to_user_id']);
        }

        if (!empty($filters['priority'])) {
            $builder->where('sr.priority', $filters['priority']);
        }

        if (!empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $builder->groupStart()
                ->like('sr.student_first_name', $search)
                ->orLike('sr.student_last_name', $search)
                ->orLike('sr.email', $search)
                ->orLike('sr.mobile', $search)
                ->orLike('sr.guardian1_email', $search)
                ->orLike('sr.guardian1_phone', $search)
                ->groupEnd();
        }

        if (!empty($filters['submitted_from'])) {
            $builder->where('sr.submitted_at >=', $filters['submitted_from']);
        }

        if (!empty($filters['submitted_to'])) {
            $builder->where('sr.submitted_at <=', $filters['submitted_to']);
        }

        if (!empty($filters['course_id'])) {
            $builder->join('student_self_registration_courses course_filter', 'course_filter.registration_id = sr.id', 'inner');
            $builder->where('course_filter.course_id', (int) $filters['course_id']);
        }

        return (int) $builder->countAllResults();
    }

    public function getRegistrationDetail(int $registrationId): array
    {
        $relations = $this->getRegistrationWithRelations($registrationId);
        if (empty($relations)) {
            return [];
        }

        $relations['notes'] = $this->getRegistrationNotes($registrationId);
        $relations['messages'] = $this->getRegistrationMessages($registrationId);
        $relations['courses'] = $this->enrichCourseSelections(
            $relations['courses'] ?? [],
            (int) ($relations['registration']['school_id'] ?? 0)
        );

        return $relations;
    }

    public function getRegistrationNotes(int $registrationId): array
    {
        return $this->db->table('student_self_registration_notes')
            ->where('registration_id', $registrationId)
            ->orderBy('created_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function createRegistrationNote(array $data): int
    {
        $this->db->table('student_self_registration_notes')->insert($data);
        return (int) $this->db->insertID();
    }

    public function updateRegistration(int $registrationId, array $data): bool
    {
        return (bool) $this->db->table('student_self_registrations')
            ->where('id', $registrationId)
            ->update($data);
    }

    /**
     * Persist course-level decisions that are made during conversion review.
     *
     * @param array<int,array<string,mixed>> $decisions
     */
    public function updateCourseDecisions(int $registrationId, array $decisions): void
    {
        if (empty($decisions)) {
            return;
        }

        foreach ($decisions as $decision) {
            $rowId = isset($decision['registration_course_id']) ? (int) $decision['registration_course_id'] : 0;
            if ($rowId <= 0) {
                continue;
            }

            $payload = [
                'decision_status' => $decision['decision_status'] ?? 'pending',
                'approved_schedule_id' => $decision['approved_schedule_id'] ?? null,
                'approved_schedule_title' => $decision['approved_schedule_title'] ?? null,
                'approved_schedule_start' => $decision['approved_schedule_start'] ?? null,
                'approved_schedule_end' => $decision['approved_schedule_end'] ?? null,
                'approved_fee_amount' => $decision['approved_fee_amount'] ?? null,
                'decision_notes' => $decision['decision_notes'] ?? null,
            ];

            if (array_key_exists('approved_schedule_id', $decision)) {
                $payload['schedule_id'] = $decision['approved_schedule_id'] ?: null;
            }

            if (array_key_exists('approved_schedule_title', $decision)) {
                $payload['schedule_title'] = $decision['approved_schedule_title'] ?? null;
            }

            if (array_key_exists('approved_fee_amount', $decision)) {
                $payload['fee_amount'] = $decision['approved_fee_amount'] ?? null;
            }

            $this->db->table('student_self_registration_courses')
                ->where('id', $rowId)
                ->where('registration_id', $registrationId)
                ->update($payload);
        }
    }

    public function updateDocumentReview(int $documentId, array $data): bool
    {
        return (bool) $this->db->table('student_self_registration_documents')
            ->where('id', $documentId)
            ->update($data);
    }

    public function getDocumentById(int $documentId): array
    {
        return $this->db->table('student_self_registration_documents')
            ->where('id', $documentId)
            ->get()
            ->getRowArray() ?? [];
    }

    /**
     * Retrieve assignable users (registrars, school admins/staff, corporate admins).
     *
     * @return array<int,array<string,mixed>>
     */
    public function getAssignableUsers(?int $schoolId = null): array
    {
        $eligibleRoles = [2, 4, 6, 7];

        $builder = $this->db->table('user u');
        $builder->select("
            u.user_id,
            u.role_id,
            u.email_id,
            u.school_id,
            COALESCE(up.first_name, '') AS first_name,
            COALESCE(up.last_name, '') AS last_name
        ");
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        $builder->whereIn('u.role_id', $eligibleRoles);
        $builder->where('u.status', 1);
        $builder->orderBy('up.first_name', 'ASC');
        $builder->orderBy('up.last_name', 'ASC');
        $builder->orderBy('u.email_id', 'ASC');

        $users = $builder->get()->getResultArray();

        if (empty($users)) {
            return [];
        }

        $schoolId = (int) ($schoolId ?? 0);

        $filtered = array_filter($users, static function (array $user) use ($schoolId): bool {
            $roleId = (int) ($user['role_id'] ?? 0);

            if ($roleId === 6) {
                // Corporate admins can always be assigned.
                return true;
            }

            if ($schoolId <= 0) {
                return true;
            }

            $schoolField = (string) ($user['school_id'] ?? '');
            if ($schoolField === '') {
                return false;
            }

            $schools = array_filter(array_map('trim', explode(',', $schoolField)));
            return in_array((string) $schoolId, $schools, true);
        });

        return array_values(array_map(static function (array $user): array {
            $firstName = trim((string) ($user['first_name'] ?? ''));
            $lastName = trim((string) ($user['last_name'] ?? ''));
            $name = trim(trim($firstName . ' ' . $lastName));

            if ($name === '') {
                $name = $user['email_id'] ?? ('User #' . $user['user_id']);
            }

            return [
                'user_id' => (int) $user['user_id'],
                'role_id' => (int) $user['role_id'],
                'name' => $name,
                'email' => $user['email_id'] ?? null,
            ];
        }, $filtered));
    }

    public function logCommunication(array $data): int
    {
        $this->db->table('student_self_registration_messages')->insert($data);
        return (int) $this->db->insertID();
    }

    public function getRegistrationMessages(int $registrationId): array
    {
        return $this->db->table('student_self_registration_messages')
            ->where('registration_id', $registrationId)
            ->orderBy('sent_at', 'DESC')
            ->get()
            ->getResultArray();
    }

    public function getCommunicationProvider(int $schoolId, string $channel): ?array
    {
        return $this->db->table('school_communication_providers')
            ->where('school_id', $schoolId)
            ->where('channel', $channel)
            ->where('status', 'active')
            ->orderBy('id', 'DESC')
            ->get()
            ->getRowArray() ?: null;
    }

    /**
     * @param array<int,array<string,mixed>> $courses
     * @return array<int,array<string,mixed>>
     */
    private function enrichCourseSelections(array $courses, int $schoolId): array
    {
        if (empty($courses) || $schoolId <= 0) {
            return $courses;
        }

        $courseIds = array_unique(array_filter(array_map(static function ($course) {
            return isset($course['course_id']) ? (int) $course['course_id'] : 0;
        }, $courses)));

        if (empty($courseIds)) {
            return $courses;
        }

        $catalogById = [];
        $catalog = $this->getActiveCourses($schoolId);
        foreach ($catalog as $course) {
            $catalogById[(int) ($course['course_id'] ?? 0)] = $course;
        }

        foreach ($courses as &$course) {
            $courseId = isset($course['course_id']) ? (int) $course['course_id'] : 0;
            $catalogEntry = $catalogById[$courseId] ?? null;

            $course['available_schedules'] = $catalogEntry['schedules'] ?? [];
            $course['catalog_course_name'] = $catalogEntry['course_name'] ?? $course['course_name'] ?? null;
            $course['catalog_description'] = $catalogEntry['description'] ?? null;
            $course['catalog_fees'] = $catalogEntry['fees'] ?? null;
        }
        unset($course);

        return $courses;
    }
}
