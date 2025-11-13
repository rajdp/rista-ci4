<?php

namespace App\Services;

use App\Models\SelfRegistrationModel;
use CodeIgniter\Database\BaseConnection;
use Config\Database;
use RuntimeException;

class SelfRegistrationPromotionService
{
    private SelfRegistrationModel $registrations;
    private BaseConnection $db;
    private array $tableColumnCache = [];

    public function __construct(
        ?SelfRegistrationModel $registrations = null,
        ?BaseConnection $db = null
    ) {
        $this->registrations = $registrations ?? new SelfRegistrationModel();
        $this->db = $db ?? Database::connect();
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
            [$studentUserId, $studentSummary] = $this->promoteStudent($registration, $actorId, $now);
            $guardianSummary = $this->promoteGuardians($registration, $studentUserId, $actorId, $now);

            $conversionPayload = [
                'student' => $studentSummary,
                'guardians' => $guardianSummary,
                'courses' => $payload['courses'],
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

            $this->db->transCommit();

            return [
                'status' => 'converted',
                'student_user_id' => $studentUserId,
                'student_created' => $studentSummary['created'],
                'student_generated_password' => $studentSummary['generated_password'] ?? null,
                'primary_guardian_id' => $guardianSummary['primary']['id'] ?? null,
                'secondary_guardian_id' => $guardianSummary['secondary']['id'] ?? null,
            ];
        } catch (\Throwable $e) {
            $this->db->transRollback();
            throw $e instanceof RuntimeException ? $e : new RuntimeException($e->getMessage(), 0, $e);
        }
    }

    /**
     * Locate or create the student user along with profile metadata.
     *
     * @return array<int,mixed> [userId, summary]
     */
    private function promoteStudent(array $registration, int $actorId, string $now): array
    {
        $student = $this->findStudent($registration);

        $studentCreated = false;
        $generatedPassword = null;

        if (! $student) {
            $generatedPassword = $this->generatePassword();
            $userId = $this->createStudentUser($registration, $generatedPassword, $actorId, $now);
            $student = $this->db->table('user')
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();
            $studentCreated = true;
        } else {
            $userId = (int) $student['user_id'];
            $this->ensureStudentSchoolMapping($student, (int) $registration['school_id'], $now);
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

    private function findStudent(array $registration): ?array
    {
        $email = strtolower($registration['email']);
        $builder = $this->db->table('user');
        $builder->select('*');
        $builder->where('LOWER(email_id)', $email);
        $existing = $builder->get()->getRowArray();

        if ($existing) {
            return $existing;
        }

        $mobile = $this->normaliseDigits($registration['mobile'] ?? '');
        if ($mobile === '') {
            return null;
        }

        $builder = $this->db->table('user');
        $builder->select('*');
        $builder->groupStart()
            ->where('REPLACE(REPLACE(REPLACE(mobile, "-", ""), " ", ""), "+", "")', $mobile)
            ->orWhere('mobile', $mobile)
            ->groupEnd();

        return $builder->get()->getRowArray() ?: null;
    }

    private function createStudentUser(array $registration, string $plainPassword, int $actorId, string $now): int
    {
        $passwordHash = md5($plainPassword);
        $schoolId = (int) $registration['school_id'];

        $insert = $this->filterColumns('user', [
            'email_id' => strtolower($registration['email']),
            'password' => $passwordHash,
            'default_password' => $plainPassword,
            'role_id' => 5,
            'school_id' => (string) $schoolId,
            'status' => 1,
            'mobile' => $this->normaliseDigits($registration['mobile'] ?? ''),
            'created_by' => $actorId,
            'created_date' => $now,
            'modified_date' => $now,
            'login_type' => 'WEB',
        ]);

        $this->db->table('user')->insert($insert);

        if ((int) $this->db->affectedRows() <= 0) {
            throw new RuntimeException('Unable to create student user');
        }

        return (int) $this->db->insertID();
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
        $profile = $builder->where('user_id', $userId)->get()->getRowArray();

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
        $details = $this->db->table('user_profile_details')
            ->where('user_id', $userId)
            ->where('school_id', (int) $registration['school_id'])
            ->get()
            ->getRowArray();

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

        $existing = $builder->get()->getRowArray();

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
        $builder->where('student_user_id', $studentUserId);
        $builder->where('guardian_id', $guardianId);

        $existing = $builder->get()->getRowArray();

        $payload = $this->filterColumns('student_guardians', [
            'student_user_id' => $studentUserId,
            'guardian_id' => $guardianId,
            'is_primary' => $isPrimary ? 1 : 0,
            'relationship_override' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        if ($existing) {
            unset($payload['student_user_id'], $payload['guardian_id'], $payload['created_at']);
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
            return $data;
        }

        $filtered = [];
        foreach ($data as $key => $value) {
            $lookup = strtolower($key);
            if (isset($map[$lookup])) {
                $filtered[$map[$lookup]] = $value;
            }
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
