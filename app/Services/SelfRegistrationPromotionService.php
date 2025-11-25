<?php

namespace App\Services;

use App\Models\SelfRegistrationModel;
use App\Services\CourseEnrollmentService;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class SelfRegistrationPromotionService
{
    private SelfRegistrationModel $registrations;
    private BaseConnection $db;
    private CourseEnrollmentService $courseEnrollmentService;
    private array $tableColumnCache = [];

    public function __construct(
        ?SelfRegistrationModel $registrations = null,
        ?BaseConnection $db = null,
        ?CourseEnrollmentService $courseEnrollmentService = null
    ) {
        $this->registrations = $registrations ?? new SelfRegistrationModel();
        $this->db = $db ?? Database::connect();
        $this->courseEnrollmentService = $courseEnrollmentService ?? new CourseEnrollmentService();
    }

    /**
     * Promote a pending registration into active LMS entities.
     *
     * @return array Summary of conversion artefacts.
     */
    public function promote(int $registrationId, int $actorId, array $options = []): array
    {
        $payload = $this->registrations->getRegistrationWithRelations($registrationId);
        if (empty($payload)) {
            throw new RuntimeException('Registration not found');
        }

        if (!empty($payload['courses'])) {
            foreach ($payload['courses'] as $course) {
                $status = strtolower((string) ($course['decision_status'] ?? 'pending'));
                if ($status === 'pending') {
                    $label = $course['course_name'] ?? ('#' . ($course['course_id'] ?? ''));
                    throw new RuntimeException(sprintf('Finalize course decision for %s before conversion', $label));
                }
            }
        }

        $registration = $payload['registration'];
        if ($registration['status'] === 'converted') {
            throw new RuntimeException('Registration already converted');
        }

        if (!in_array($registration['status'], ['pending', 'in_review', 'approved'], true)) {
            throw new RuntimeException('Registration must be pending or approved before conversion');
        }

        $now = date('Y-m-d H:i:s');

        $this->db->transBegin();

        try {
            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::promote - Starting promotion for registration_id=%d, actor_id=%d',
                $registrationId,
                $actorId
            ));
            
            [$studentUserId, $studentSummary] = $this->promoteStudent($registration, $actorId, $now);
            
            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::promote - Student promoted: user_id=%d, created=%s',
                $studentUserId,
                $studentSummary['created'] ? 'yes' : 'no'
            ));
            
            $guardianSummary = $this->promoteGuardians($registration, $studentUserId, $actorId, $now);
            $enrolledClasses = $this->enrollApprovedCourses($payload['courses'] ?? [], $studentUserId, $actorId, $now);

            $conversionPayload = [
                'student' => $studentSummary,
                'guardians' => $guardianSummary,
                'courses' => $payload['courses'],
                'enrolled_classes' => $enrolledClasses,
                'documents' => array_map(static function ($doc) {
                    return [
                        'id' => $doc['id'],
                        'path' => $doc['storage_path'],
                        'original_name' => $doc['original_name'],
                        'mime_type' => $doc['mime_type'],
                        'file_size' => $doc['file_size'],
                    ];
                }, $payload['documents']),
            ];

            $updateData = [
                'status' => 'converted',
                'converted_student_user_id' => $studentUserId,
                'converted_primary_guardian_id' => $guardianSummary['primary']['id'] ?? null,
                'converted_secondary_guardian_id' => $guardianSummary['secondary']['id'] ?? null,
                'converted_at' => $now,
                'converted_by' => $actorId,
                'conversion_notes' => $options['notes'] ?? null,
                'conversion_payload' => json_encode($conversionPayload),
                'updated_at' => $now,
            ];

            $this->registrations->markConverted($registrationId, $updateData);

            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::promote - Marking registration_id=%d as converted with student_user_id=%d',
                $registrationId,
                $studentUserId
            ));

            $this->db->transCommit();
            
            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::promote - Transaction committed successfully. Registration_id=%d converted to student_user_id=%d',
                $registrationId,
                $studentUserId
            ));

            return [
                'status' => 'converted',
                'student_user_id' => $studentUserId,
                'student_created' => $studentSummary['created'],
                'student_generated_password' => $studentSummary['generated_password'] ?? null,
                'primary_guardian_id' => $guardianSummary['primary']['id'] ?? null,
                'secondary_guardian_id' => $guardianSummary['secondary']['id'] ?? null,
                'enrolled_classes' => $enrolledClasses,
            ];
        } catch (\Throwable $e) {
            $errorDetails = [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'registration_id' => $registrationId,
                'actor_id' => $actorId,
            ];
            
            log_message('error', sprintf(
                'SelfRegistrationPromotionService::promote - Error during promotion: %s',
                json_encode($errorDetails, JSON_PRETTY_PRINT)
            ));
            
            // Check if transaction is still active before rolling back
            if ($this->db->transStatus() !== false) {
                $this->db->transRollback();
                log_message('debug', 'SelfRegistrationPromotionService::promote - Transaction rolled back');
            }
            
            // Provide more detailed error message
            $errorMessage = $e->getMessage();
            if (strpos($errorMessage, 'getRowArray()') !== false) {
                $errorMessage = 'Database query failed. Please check the logs for details.';
            } elseif (strpos($errorMessage, 'create student user') !== false) {
                $errorMessage = 'Failed to create student account. ' . $errorMessage;
            }
            
            throw $e instanceof RuntimeException ? new RuntimeException($errorMessage, $e->getCode(), $e) : new RuntimeException($errorMessage, 0, $e);
        }
    }

    /**
     * Locate or create the student user along with profile metadata.
     *
     * @return array<int,mixed> [userId, summary]
     */
    private function promoteStudent(array $registration, int $actorId, string $now): array
    {
        $email = strtolower($registration['email'] ?? '');
        $mobile = $registration['mobile'] ?? '';
        
        log_message('debug', sprintf(
            'SelfRegistrationPromotionService::promoteStudent - Searching for existing student email=%s mobile=%s',
            $email,
            $mobile
        ));
        
        $student = $this->findStudent($registration);

        $studentCreated = false;
        $generatedPassword = null;

        // Check if found user is actually a student (role_id = 5)
        // If not, or if email doesn't match, create a new student
        if ($student) {
            $foundRoleId = (int) ($student['role_id'] ?? 0);
            $foundEmail = strtolower($student['email_id'] ?? '');
            $registrationEmail = strtolower($registration['email'] ?? '');
            
            // Only reuse if it's a student role AND email matches
            if ($foundRoleId === 5 && $foundEmail === $registrationEmail) {
                $userId = (int) $student['user_id'];
                log_message('debug', sprintf(
                    'SelfRegistrationPromotionService::promoteStudent - Found existing student user_id=%d, email=%s, role_id=%d',
                    $userId,
                    $foundEmail,
                    $foundRoleId
                ));
                $this->ensureStudentSchoolMapping($student, (int) $registration['school_id'], $now);
            } else {
                // Found user but wrong role or email mismatch - create new student
                log_message('debug', sprintf(
                    'SelfRegistrationPromotionService::promoteStudent - Found user but wrong role (found=%d, required=5) or email mismatch (found=%s, required=%s). Creating new student.',
                    $foundRoleId,
                    $foundEmail,
                    $registrationEmail
                ));
                $student = null; // Force creation of new student
            }
        }

        if (! $student) {
            log_message('debug', 'SelfRegistrationPromotionService::promoteStudent - No existing student found, creating new user');
            $generatedPassword = $this->generatePassword();
            $userId = $this->createStudentUser($registration, $generatedPassword, $actorId, $now);
            log_message('debug', sprintf('SelfRegistrationPromotionService::promoteStudent - Created new student user_id=%d', $userId));
            
            $result = $this->db->table('user')
                ->where('user_id', $userId)
                ->get();
            
            if ($result === false || $result->getNumRows() === 0) {
                throw new RuntimeException(sprintf('Failed to retrieve created student user_id=%d', $userId));
            }
            
            $student = $result->getRowArray();
            
            if (empty($student)) {
                throw new RuntimeException(sprintf('Failed to retrieve created student user_id=%d', $userId));
            }
            
            $studentCreated = true;
        }

        $this->syncUserProfile($userId, $registration, $actorId, $now);
        $this->syncUserProfileDetails($userId, $registration, $actorId, $now);

        return [
            $userId,
            [
                'created' => $studentCreated,
                'generated_password' => $generatedPassword,
                'email' => strtolower($registration['email']),
                'mobile' => $registration['mobile'],
            ],
        ];
    }

    /**
     * Create or update guardian contacts and link them to the student.
     */
    private function promoteGuardians(array $registration, int $studentUserId, int $actorId, string $now): array
    {
        $summary = [
            'primary' => null,
            'secondary' => null,
        ];

        $primary = $this->upsertGuardian([
            'school_id' => (int) $registration['school_id'],
            'name' => $registration['guardian1_name'] ?? null,
            'email' => $registration['guardian1_email'] ?? null,
            'phone' => $registration['guardian1_phone'] ?? null,
            'relationship' => 'Primary',
            'actor_id' => $actorId,
            'timestamp' => $now,
        ]);

        if ($primary) {
            $this->linkGuardianToStudent($studentUserId, $primary['id'], true, $actorId, $now);
            $summary['primary'] = $primary;
        }

        $secondary = $this->upsertGuardian([
            'school_id' => (int) $registration['school_id'],
            'name' => $registration['guardian2_name'] ?? null,
            'email' => $registration['guardian2_email'] ?? null,
            'phone' => $registration['guardian2_phone'] ?? null,
            'relationship' => 'Secondary',
            'actor_id' => $actorId,
            'timestamp' => $now,
        ]);

        if ($secondary) {
            $this->linkGuardianToStudent($studentUserId, $secondary['id'], false, $actorId, $now);
            $summary['secondary'] = $secondary;
        }

        return $summary;
    }

    /**
     * Enroll approved courses into the student's active classes with automatic fee assignment.
     *
     * @param array<int,array<string,mixed>> $courses
     * @return array<int,mixed> List of enrollment results with course and fee details.
     */
    private function enrollApprovedCourses(array $courses, int $studentUserId, int $actorId, string $now): array
    {
        if (empty($courses)) {
            return [];
        }

        $enrolled = [];
        $schoolId = null;

        foreach ($courses as $course) {
            $status = strtolower((string) ($course['decision_status'] ?? 'pending'));
            if ($status !== 'approved') {
                continue;
            }

            $courseId = (int) ($course['course_id'] ?? 0);
            if (!$courseId) {
                log_message('warning', '[SelfRegistration] Missing course_id in approved course');
                continue;
            }

            // Get school_id from course or student
            if (!$schoolId) {
                $schoolId = $this->getStudentSchoolId($studentUserId);
            }

            if (!$schoolId) {
                log_message('error', sprintf('[SelfRegistration] Unable to determine school_id for student %d', $studentUserId));
                continue;
            }

            // Prepare enrollment options
            $startDate = $course['start_date'] ?? date('Y-m-d');
            $enrollmentOptions = [
                'registration_id' => $course['registration_id'] ?? null,
                'enrollment_date' => $startDate,
                'added_by' => $actorId,
                'notes' => $course['decision_notes'] ?? null,
            ];

            // Use custom fee if admin set one during approval
            if (isset($course['approved_fee_amount']) && $course['approved_fee_amount'] > 0) {
                $enrollmentOptions['fee_amount'] = (float) $course['approved_fee_amount'];
            }

            // Add new billing fields
            if (isset($course['fee_term'])) {
                $enrollmentOptions['fee_term'] = $course['fee_term'];
            }
            if (isset($course['next_billing_date'])) {
                $enrollmentOptions['next_billing_date'] = $course['next_billing_date'];
            }
            if (isset($course['deposit'])) {
                $enrollmentOptions['deposit'] = (float) $course['deposit'];
            }
            if (isset($course['onboarding_fee']) || isset($course['registration_fee'])) {
                $enrollmentOptions['onboarding_fee'] = (float) ($course['onboarding_fee'] ?? $course['registration_fee'] ?? 0);
            }
            if (isset($course['prorated_fee'])) {
                $enrollmentOptions['prorated_fee'] = (float) $course['prorated_fee'];
            }

            // Get approved class IDs if specified
            $classIds = $this->resolveClassIdsForApprovedCourse($course);
            if (!empty($classIds)) {
                $enrollmentOptions['class_ids'] = $classIds;
                // Ensure student_class entries are created for each class
                foreach ($classIds as $classId) {
                    $this->ensureStudentClassEnrollment($studentUserId, (int) $classId, $actorId, $now);
                }
            }

            try {
                // Use CourseEnrollmentService to enroll with automatic fee calculation and class assignment
                $result = $this->courseEnrollmentService->enrollStudentInCourse(
                    $studentUserId,
                    $courseId,
                    $schoolId,
                    $enrollmentOptions
                );

                if ($result['success']) {
                    $enrolled[] = [
                        'course_id' => $courseId,
                        'course_name' => $course['course_name'] ?? null,
                        'student_course_id' => $result['student_course_id'],
                        'fee_amount' => $result['fee_amount'],
                        'student_fee_plan_id' => $result['student_fee_plan_id'],
                        'class_enrollments' => $result['class_enrollments'] ?? [],
                    ];

                    log_message('info', sprintf(
                        '[SelfRegistration] Enrolled student %d in course %d with fee $%s',
                        $studentUserId,
                        $courseId,
                        number_format($result['fee_amount'] ?? 0, 2)
                    ));
                } else {
                    log_message('error', sprintf(
                        '[SelfRegistration] Failed to enroll student %d in course %d: %s',
                        $studentUserId,
                        $courseId,
                        $result['message'] ?? 'Unknown error'
                    ));
                }
            } catch (\Exception $e) {
                log_message('error', sprintf(
                    '[SelfRegistration] Exception enrolling student %d in course %d: %s',
                    $studentUserId,
                    $courseId,
                    $e->getMessage()
                ));
            }
        }

        return $enrolled;
    }

    /**
     * Get student's school ID
     */
    private function getStudentSchoolId(int $studentUserId): ?int
    {
        $result = $this->db->table('user')
            ->select('school_id')
            ->where('user_id', $studentUserId)
            ->get();
        
        if ($result === false || $result->getNumRows() === 0) {
            return null;
        }
        
        $student = $result->getRowArray();

        if (empty($student) || empty($student['school_id'])) {
            return null;
        }

        $schoolId = $student['school_id'];
        
        // Handle comma-separated list (take first school_id)
        if (is_string($schoolId) && strpos($schoolId, ',') !== false) {
            $schoolIds = explode(',', $schoolId);
            $schoolId = trim($schoolIds[0] ?? '');
        }
        
        // Ensure we have a valid numeric value
        if ($schoolId === '' || $schoolId === null) {
            return null;
        }
        
        $intValue = (int) $schoolId;
        
        // Return null if conversion resulted in 0 and original wasn't numeric
        if ($intValue === 0 && $schoolId !== '0' && $schoolId !== 0) {
            return null;
        }
        
        return $intValue;
    }

    /**
     * Resolve class IDs for an approved course
     */
    private function resolveClassIdsForApprovedCourse(array $course): array
    {
        $classIds = [];

        // Check for explicitly approved class
        $candidates = [
            $course['approved_class_id'] ?? null,
            $course['class_id'] ?? null,
            $course['schedule_class_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                $classIds[] = (int) $candidate;
            }
        }

        // Check for class from approved schedule
        if (!empty($course['approved_schedule_id'])) {
            // Try multiple table names for schedule/class mapping
            $schedule = null;
            
            // Try class_schedule table first
            if ($this->db->tableExists('class_schedule')) {
                $result = $this->db->table('class_schedule')
                    ->select('class_id')
                    ->where('id', (int) $course['approved_schedule_id'])
                    ->get();
                
                if ($result !== false && $result->getNumRows() > 0) {
                    $schedule = $result->getRowArray();
                }
            }
            
            // If not found, try course_schedules table
            if (!$schedule && $this->db->tableExists('course_schedules')) {
                $result = $this->db->table('course_schedules')
                    ->select('class_id')
                    ->where('id', (int) $course['approved_schedule_id'])
                    ->get();
                
                if ($result !== false && $result->getNumRows() > 0) {
                    $schedule = $result->getRowArray();
                }
            }
            
            // If still not found, try class table directly (schedule_id might be class_id)
            if (!$schedule && $this->db->tableExists('class')) {
                $result = $this->db->table('class')
                    ->select('class_id')
                    ->where('class_id', (int) $course['approved_schedule_id'])
                    ->get();
                
                if ($result !== false && $result->getNumRows() > 0) {
                    $classRow = $result->getRowArray();
                    if ($classRow) {
                        $schedule = ['class_id' => $classRow['class_id']];
                    }
                }
            }

            if (!empty($schedule['class_id'])) {
                $classIds[] = (int) $schedule['class_id'];
            }
        }

        return array_values(array_unique($classIds));
    }

    private function resolveClassIdForApprovedCourse(array $course): ?int
    {
        $candidates = [
            $course['class_id'] ?? null,
            $course['approved_class_id'] ?? null,
            $course['schedule_class_id'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== null && $candidate !== '') {
                return (int) $candidate;
            }
        }

        if (!empty($course['approved_schedule_id'])) {
            $result = $this->db->table('class_schedule')
                ->select('class_id')
                ->where('id', (int) $course['approved_schedule_id'])
                ->get();
            
            if ($result !== false && $result->getNumRows() > 0) {
                $schedule = $result->getRowArray();
                if (!empty($schedule['class_id'])) {
                    return (int) $schedule['class_id'];
                }
            }
        }

        if (!empty($course['course_id'])) {
            $result = $this->db->table('class')
                ->select('class_id')
                ->where('course_id', (int) $course['course_id'])
                ->orderBy('start_date', 'ASC')
                ->limit(1)
                ->get();
            
            if ($result !== false && $result->getNumRows() > 0) {
                $class = $result->getRowArray();
                if (!empty($class['class_id'])) {
                    return (int) $class['class_id'];
                }
            }
        }

        return null;
    }

    private function ensureStudentClassEnrollment(int $studentUserId, int $classId, int $actorId, string $now): bool
    {
        $result = $this->db->table('class')
            ->select('class_id, class_name, end_date, status')
            ->where('class_id', $classId)
            ->get();
        
        if ($result === false || $result->getNumRows() === 0) {
            log_message('warning', sprintf('[SelfRegistration] Class %d not found while enrolling student %d', $classId, $studentUserId));
            return false;
        }
        
        $class = $result->getRowArray();

        if (empty($class)) {
            log_message('warning', sprintf('[SelfRegistration] Class %d not found while enrolling student %d', $classId, $studentUserId));
            return false;
        }

        $validity = (!empty($class['end_date']) && $class['end_date'] !== '0000-00-00')
            ? $class['end_date']
            : '2099-12-31';

        $result = $this->db->table('student_class')
            ->where('student_id', $studentUserId)
            ->where('class_id', $classId)
            ->get();
        
        $existing = ($result !== false && $result->getNumRows() > 0) ? $result->getRowArray() : null;

        if ($existing) {
            $needsUpdate = ($existing['status'] ?? '0') !== '1';
            if ($needsUpdate) {
                $this->db->table('student_class')
                    ->where('student_id', $studentUserId)
                    ->where('class_id', $classId)
                    ->update([
                        'status' => '1',
                        'joining_date' => $existing['joining_date'] ?? date('Y-m-d'),
                        'validity' => $validity,
                        'modified_by' => $actorId,
                        'modified_date' => $now,
                    ]);
            }
            return true;
        }

        $this->db->table('student_class')->insert([
            'class_id' => $classId,
            'student_id' => $studentUserId,
            'status' => '1',
            'joining_date' => date('Y-m-d'),
            'validity' => $validity,
            'class_type' => 1,
            'created_by' => $actorId,
            'created_date' => $now,
        ]);

        return true;
    }

    private function findStudent(array $registration): ?array
    {
        $email = strtolower($registration['email'] ?? '');
        
        // First, try to find by email (most reliable)
        if (!empty($email)) {
            $builder = $this->db->table('user');
            $builder->select('*');
            $builder->where('LOWER(email_id)', $email);
            $result = $builder->get();
            
            if ($result !== false && $result->getNumRows() > 0) {
                $existing = $result->getRowArray();
                if ($existing) {
                    log_message('debug', sprintf(
                        'SelfRegistrationPromotionService::findStudent - Found existing user by email: user_id=%d, email=%s, role_id=%s',
                        $existing['user_id'] ?? 'N/A',
                        $existing['email_id'] ?? 'N/A',
                        $existing['role_id'] ?? 'N/A'
                    ));
                    return $existing;
                }
            }
        }

        // Only search by mobile if email didn't match AND we have mobile
        // But we'll validate the role in promoteStudent
        $mobile = $this->normaliseDigits($registration['mobile'] ?? '');
        if ($mobile === '') {
            log_message('debug', 'SelfRegistrationPromotionService::findStudent - No email or mobile provided, returning null');
            return null;
        }

        $builder = $this->db->table('user');
        $builder->select('*');
        $builder->groupStart()
            ->where('REPLACE(REPLACE(REPLACE(mobile, "-", ""), " ", ""), "+", "")', $mobile)
            ->orWhere('mobile', $mobile)
            ->groupEnd();

        $result = $builder->get();
        
        if ($result !== false && $result->getNumRows() > 0) {
            $existing = $result->getRowArray();
            if ($existing) {
                log_message('debug', sprintf(
                    'SelfRegistrationPromotionService::findStudent - Found existing user by mobile: user_id=%d, mobile=%s, role_id=%s, email=%s',
                    $existing['user_id'] ?? 'N/A',
                    $existing['mobile'] ?? 'N/A',
                    $existing['role_id'] ?? 'N/A',
                    $existing['email_id'] ?? 'N/A'
                ));
                return $existing;
            }
        }
        
        log_message('debug', 'SelfRegistrationPromotionService::findStudent - No existing student found');
        return null;
    }

    private function createStudentUser(array $registration, string $plainPassword, int $actorId, string $now): int
    {
        $passwordHash = md5($plainPassword);
        $schoolId = (int) $registration['school_id'];
        $email = strtolower($registration['email'] ?? '');
        $mobile = $this->normaliseDigits($registration['mobile'] ?? '');

        $insertData = [
            'email_id' => $email,
            'password' => $passwordHash,
            'default_password' => $plainPassword,
            'role_id' => 5,
            'school_id' => (string) $schoolId,
            'status' => 1,
            'mobile' => $mobile,
            'created_by' => $actorId,
            'created_date' => $now,
            'modified_date' => $now,
            'login_type' => 'WEB',
        ];

        log_message('debug', sprintf(
            'SelfRegistrationPromotionService::createStudentUser - Attempting to create user with email=%s, school_id=%d, role_id=5',
            $email,
            $schoolId
        ));

        $insert = $this->filterColumns('user', $insertData);
        
        log_message('debug', sprintf(
            'SelfRegistrationPromotionService::createStudentUser - Original insert data: %s',
            json_encode($insertData)
        ));
        
        log_message('debug', sprintf(
            'SelfRegistrationPromotionService::createStudentUser - Filtered insert data: %s',
            json_encode($insert)
        ));
        
        // Validate required fields are present
        $requiredFields = ['email_id', 'password', 'role_id', 'school_id', 'status'];
        $missingFields = [];
        foreach ($requiredFields as $field) {
            if (!isset($insert[$field]) || $insert[$field] === '') {
                $missingFields[] = $field;
            }
        }
        
        if (!empty($missingFields)) {
            log_message('error', sprintf(
                'SelfRegistrationPromotionService::createStudentUser - Missing required fields: %s',
                implode(', ', $missingFields)
            ));
            throw new RuntimeException('Missing required fields for student creation: ' . implode(', ', $missingFields));
        }

        try {
            $this->db->table('user')->insert($insert);
            $insertId = (int) $this->db->insertID();
            $affectedRows = (int) $this->db->affectedRows();

            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::createStudentUser - Insert result: insertID=%d, affectedRows=%d',
                $insertId,
                $affectedRows
            ));

            if ($affectedRows <= 0 || $insertId <= 0) {
                $error = $this->db->error();
                log_message('error', sprintf(
                    'SelfRegistrationPromotionService::createStudentUser - Failed to create user. Error: %s, Insert data: %s',
                    json_encode($error),
                    json_encode($insert)
                ));
                
                // Check for specific database errors
                $errorMessage = 'Unable to create student user';
                if (!empty($error['message'])) {
                    $errorMessage .= ': ' . $error['message'];
                } elseif (!empty($error['code'])) {
                    $errorMessage .= ' (Error code: ' . $error['code'] . ')';
                } else {
                    $errorMessage .= ': Unknown database error';
                }
                
                throw new RuntimeException($errorMessage);
            }
            
            log_message('info', sprintf(
                'SelfRegistrationPromotionService::createStudentUser - Successfully created student user_id=%d, email=%s, school_id=%s',
                $insertId,
                $email,
                $schoolId
            ));

            return $insertId;
        } catch (\Exception $e) {
            log_message('error', sprintf(
                'SelfRegistrationPromotionService::createStudentUser - Exception during insert: %s, Trace: %s',
                $e->getMessage(),
                $e->getTraceAsString()
            ));
            throw new RuntimeException('Failed to create student user: ' . $e->getMessage(), 0, $e);
        }
    }

    private function ensureStudentSchoolMapping(array $student, int $schoolId, string $now): void
    {
        $existing = (string) ($student['school_id'] ?? '');
        $schools = array_filter(array_map('trim', explode(',', $existing)));

        if (!in_array((string) $schoolId, $schools, true)) {
            $schools[] = (string) $schoolId;
            $update = $this->filterColumns('user', [
                'school_id' => implode(',', array_unique($schools)),
                'modified_date' => $now,
            ]);

            $this->db->table('user')
                ->where('user_id', $student['user_id'])
                ->update($update);
        }
    }

    private function syncUserProfile(int $userId, array $registration, int $actorId, string $now): void
    {
        $builder = $this->db->table('user_profile');
        $result = $builder->where('user_id', $userId)->get();
        $profile = ($result !== false && $result->getNumRows() > 0) ? $result->getRowArray() : null;

        [$firstName, $lastName] = $this->splitName(
            trim(($registration['student_first_name'] ?? '') . ' ' . ($registration['student_last_name'] ?? ''))
        );

        $data = $this->filterColumns('user_profile', [
            'user_id' => $userId,
            'first_name' => $firstName ?: $registration['student_first_name'],
            'last_name' => $lastName ?: $registration['student_last_name'],
            'gender' => null,
            'birthday' => $registration['date_of_birth'] ?? null,
            'created_by' => $actorId,
            'created_date' => $now,
            'modified_date' => $now,
        ]);

        if ($profile) {
            unset($data['user_id'], $data['created_by'], $data['created_date']);
            if (!empty($data)) {
                $this->db->table('user_profile')
                    ->where('user_id', $userId)
                    ->update(array_filter($data, static fn($value) => $value !== null && $value !== ''));
            }
        } else {
            $this->db->table('user_profile')->insert($data);
        }
    }

    private function syncUserProfileDetails(int $userId, array $registration, int $actorId, string $now): void
    {
        $result = $this->db->table('user_profile_details')
            ->where('user_id', $userId)
            ->where('school_id', (int) $registration['school_id'])
            ->get();
        
        $details = ($result !== false && $result->getNumRows() > 0) ? $result->getRowArray() : null;

        $gradeId = isset($registration['grade_id']) ? (int) $registration['grade_id'] : 0;
        $data = $this->filterColumns('user_profile_details', [
            'user_id' => $userId,
            'school_id' => (int) $registration['school_id'],
            'status' => 1,
            'grade_id' => $gradeId,
            'doj' => $registration['submitted_at'] ?? $now,
            'created_by' => $actorId,
            'created_date' => $now,
            'modified_date' => $now,
            'phone' => $this->normaliseDigits($registration['mobile'] ?? ''),
        ]);

        if ($details) {
            unset($data['user_id'], $data['school_id'], $data['created_by'], $data['created_date']);
            if (!empty($data)) {
                $builder = $this->db->table('user_profile_details');

                $primaryKey = null;
                foreach (['user_details_id', 'id'] as $candidate) {
                    if (array_key_exists($candidate, $details)) {
                        $primaryKey = [$candidate, $details[$candidate]];
                        break;
                    }
                }

                if ($primaryKey) {
                    $builder->where($primaryKey[0], $primaryKey[1]);
                } else {
                    $builder->where('user_id', $userId)
                        ->where('school_id', (int) $registration['school_id']);
                }

                $builder->update(array_filter($data, static fn($value) => $value !== null && $value !== ''));
                log_message(
                    'debug',
                    sprintf(
                        'SelfRegistrationPromotionService::syncUserProfileDetails update user_id=%d school_id=%d payload=%s',
                        $userId,
                        (int) $registration['school_id'],
                        json_encode(array_filter($data, static fn($value) => $value !== null && $value !== ''))
                    )
                );
            }
        } else {
            log_message(
                'debug',
                sprintf(
                    'SelfRegistrationPromotionService::syncUserProfileDetails insert user_id=%d school_id=%d payload=%s',
                    $userId,
                    (int) $registration['school_id'],
                    json_encode($data)
                )
            );
            $this->db->table('user_profile_details')->insert($data);
        }
    }

    /**
     * Insert or reuse guardian record.
     */
    private function upsertGuardian(array $input): ?array
    {
        $name = trim((string) ($input['name'] ?? ''));
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $phone = $this->normaliseDigits($input['phone'] ?? '');

        if ($name === '' && $email === '' && $phone === '') {
            return null;
        }

        $builder = $this->db->table('guardians');
        $builder->where('school_id', (int) $input['school_id']);

        if ($email !== '') {
            $builder->where('LOWER(email)', $email);
        } elseif ($phone !== '') {
            $builder->where('phone', $phone);
        } else {
            $builder->where('first_name', $name);
        }

        $result = $builder->get();
        $existing = ($result !== false && $result->getNumRows() > 0) ? $result->getRowArray() : null;

        [$firstName, $lastName] = $this->splitName($name);

        if ($existing) {
            $update = $this->filterColumns('guardians', [
                'first_name' => $firstName ?: $existing['first_name'],
                'last_name' => $lastName ?: $existing['last_name'],
                'email' => $email ?: $existing['email'],
                'phone' => $phone ?: $existing['phone'],
                'relationship' => $input['relationship'] ?? $existing['relationship'],
                'status' => 1,
                'updated_at' => $input['timestamp'],
            ]);

            if (!empty($update)) {
                $this->db->table('guardians')
                    ->where('id', $existing['id'])
                    ->update($update);
            }

            $existing['email'] = $email ?: $existing['email'];
            $existing['phone'] = $phone ?: $existing['phone'];
            $existing['first_name'] = $firstName ?: $existing['first_name'];
            $existing['last_name'] = $lastName ?: $existing['last_name'];

            return $existing;
        }

        $insert = $this->filterColumns('guardians', [
            'school_id' => (int) $input['school_id'],
            'first_name' => $firstName ?: 'Guardian',
            'last_name' => $lastName,
            'email' => $email ?: null,
            'phone' => $phone ?: null,
            'relationship' => $input['relationship'],
            'communication_preference' => 'both',
            'status' => 1,
            'created_at' => $input['timestamp'],
            'updated_at' => $input['timestamp'],
        ]);

        $this->db->table('guardians')->insert($insert);

        return [
            'id' => (int) $this->db->insertID(),
            'first_name' => $insert['first_name'] ?? $firstName,
            'last_name' => $insert['last_name'] ?? $lastName,
            'email' => $insert['email'] ?? $email,
            'phone' => $insert['phone'] ?? $phone,
        ];
    }

    private function linkGuardianToStudent(int $studentUserId, int $guardianId, bool $isPrimary, int $actorId, string $now): void
    {
        $builder = $this->db->table('student_guardians');
        $builder->where('student_id', $studentUserId);
        $builder->where('guardian_id', $guardianId);

        $result = $builder->get();
        $existing = ($result !== false && $result->getNumRows() > 0) ? $result->getRowArray() : null;

        $payload = $this->filterColumns('student_guardians', [
            'student_id' => $studentUserId,
            'guardian_id' => $guardianId,
            'is_primary' => $isPrimary ? 1 : 0,
            'relationship_override' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($existing) {
            unset($payload['student_id'], $payload['guardian_id'], $payload['created_at']);
            if (!empty($payload)) {
                $this->db->table('student_guardians')
                    ->where('id', $existing['id'])
                    ->update($payload);
            }
        } else {
            $this->db->table('student_guardians')->insert($payload);
        }
    }

    private function splitName(string $value): array
    {
        $value = trim($value);
        if ($value === '') {
            return ['', ''];
        }

        $parts = preg_split('/\s+/', $value, 2);
        $first = $parts[0] ?? '';
        $last = $parts[1] ?? '';

        return [$first, $last];
    }

    private function generatePassword(int $length = 8): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
        $password = '';
        $max = strlen($alphabet) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= $alphabet[random_int(0, $max)];
        }

        return $password;
    }

    private function normaliseDigits(?string $value): string
    {
        if ($value === null) {
            return '';
        }

        return preg_replace('/\D+/', '', $value) ?? '';
    }

    /**
     * Filter data so only existing columns are used per table.
     */
    private function filterColumns(string $table, array $data): array
    {
        $map = $this->getColumnMap($table);

        if (empty($map)) {
            log_message('warning', sprintf(
                'SelfRegistrationPromotionService::filterColumns - No column map for table %s, returning original data',
                $table
            ));
            return $data;
        }

        $filtered = [];
        $filteredOut = [];
        foreach ($data as $key => $value) {
            $lookup = strtolower($key);
            if (isset($map[$lookup])) {
                $filtered[$map[$lookup]] = $value;
            } else {
                $filteredOut[] = $key;
            }
        }
        
        if (!empty($filteredOut)) {
            log_message('debug', sprintf(
                'SelfRegistrationPromotionService::filterColumns - Filtered out columns for table %s: %s',
                $table,
                implode(', ', $filteredOut)
            ));
        }

        return $filtered;
    }

    private function getColumnMap(string $table): array
    {
        if (isset($this->tableColumnCache[$table])) {
            return $this->tableColumnCache[$table];
        }

        try {
            $fields = $this->db->getFieldNames($table);
        } catch (\Throwable $e) {
            $this->tableColumnCache[$table] = [];
            return [];
        }

        $map = [];
        foreach ($fields as $field) {
            $map[strtolower($field)] = $field;
        }

        $this->tableColumnCache[$table] = $map;

        return $map;
    }
}
