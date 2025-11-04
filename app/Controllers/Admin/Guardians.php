<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\GuardianModel;
use App\Models\Admin\StudentGuardianModel;
use App\Models\Admin\StudentsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Guardians extends BaseController
{
    use RestTrait;

    protected GuardianModel $guardianModel;
    protected StudentGuardianModel $studentGuardianModel;
    protected StudentsModel $studentsModel;

    public function __construct()
    {
        $this->guardianModel = new GuardianModel();
        $this->studentGuardianModel = new StudentGuardianModel();
        $this->studentsModel = new StudentsModel();
    }

    /**
     * List guardians with optional filters (search, school, status, preference).
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $token = $this->validateToken();

            if (!$token) {
                return $this->unauthorizedResponse();
            }

            if (empty($payload['school_id'])) {
                $payload['school_id'] = $this->getSchoolId($token) ?? null;
            }

            $guardians = $this->guardianModel->filterGuardians($payload);

            // Attach linked students for quick display
            $guardianIds = array_column($guardians, 'id');
            $links = [];

            if (!empty($guardianIds)) {
                $links = $this->studentGuardianModel
                    ->whereIn('guardian_id', $guardianIds)
                    ->findAll();
            }

            $studentDetails = [];
            if (!empty($links)) {
                $studentIds = array_unique(array_column($links, 'student_id'));
                if (!empty($studentIds)) {
                    $studentDetails = $this->studentsModel
                        ->select('id, first_name, last_name, email, grade_id')
                        ->whereIn('id', $studentIds)
                        ->findAll();
                    $studentDetails = array_column($studentDetails, null, 'id');
                }
            }

            $linksByGuardian = [];
            foreach ($links as $link) {
                $studentId = $link['student_id'];
                $studentInfo = $studentDetails[$studentId] ?? [];

                $linksByGuardian[$link['guardian_id']][] = array_merge($link, [
                    'student_name' => trim(($studentInfo['first_name'] ?? '') . ' ' . ($studentInfo['last_name'] ?? '')),
                    'student_email' => $studentInfo['email'] ?? null,
                    'grade_id' => $studentInfo['grade_id'] ?? null,
                ]);
            }

            $guardians = array_map(static function ($guardian) use ($linksByGuardian) {
                $guardian['students'] = $linksByGuardian[$guardian['id']] ?? [];
                return $guardian;
            }, $guardians);

            return $this->successResponse($guardians, 'Guardians retrieved successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to list guardians: ' . $e->getMessage());
        }
    }

    /**
     * Create or update a guardian record.
     */
    public function save(): ResponseInterface
    {
        try {
            $payload = $this->request->getJSON();

            if (!$payload) {
                return $this->errorResponse('Invalid request payload');
            }

            $payload = (array) $payload;

            $required = ['first_name', 'school_id'];
            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $guardianData = [
                'school_id' => (int) $payload['school_id'],
                'first_name' => $payload['first_name'],
                'last_name' => $payload['last_name'] ?? null,
                'email' => $payload['email'] ?? null,
                'phone' => $payload['phone'] ?? null,
                'relationship' => $payload['relationship'] ?? null,
                'communication_preference' => $payload['communication_preference'] ?? 'both',
                'notes' => $payload['notes'] ?? null,
                'status' => $payload['status'] ?? 1,
            ];

            if (!empty($payload['id'])) {
                $this->guardianModel->update((int) $payload['id'], $guardianData);
                $result = $this->guardianModel->find((int) $payload['id']);
                $message = 'Guardian updated successfully';
            } else {
                $guardianId = $this->guardianModel->insert($guardianData, true);
                $result = $this->guardianModel->find($guardianId);
                $message = 'Guardian created successfully';
            }

            return $this->successResponse($result, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to save guardian: ' . $e->getMessage());
        }
    }

    /**
     * Link a guardian to one or more students.
     */
    public function assign(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['guardian_id', 'student_ids'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $guardianId = (int) $payload['guardian_id'];
            $studentIds = array_map('intval', (array) $payload['student_ids']);
            $isPrimary = !empty($payload['is_primary']);

            // Remove previous assignments if requested
            if (!empty($payload['reset_existing'])) {
                $this->studentGuardianModel->where('guardian_id', $guardianId)->delete();
            }

            foreach ($studentIds as $studentId) {
                $existing = $this->studentGuardianModel
                    ->where('guardian_id', $guardianId)
                    ->where('student_id', $studentId)
                    ->first();

                $data = [
                    'student_id' => $studentId,
                    'guardian_id' => $guardianId,
                    'is_primary' => $isPrimary && ($payload['primary_student_id'] ?? null) == $studentId ? 1 : 0,
                    'relationship_override' => $payload['relationship_override'] ?? null,
                ];

                if ($existing) {
                    $this->studentGuardianModel->update($existing['id'], $data);
                } else {
                    $this->studentGuardianModel->insert($data);
                }
            }

            return $this->successResponse(true, 'Guardian assignments updated successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to assign guardian: ' . $e->getMessage());
        }
    }

    /**
     * Remove guardian link or delete guardian entirely.
     */
    public function remove(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);

            if (empty($payload['guardian_id'])) {
                return $this->errorResponse('Guardian id is required');
            }

            if (!empty($payload['student_id'])) {
                $this->studentGuardianModel->where('guardian_id', (int) $payload['guardian_id'])
                    ->where('student_id', (int) $payload['student_id'])
                    ->delete();

                return $this->successResponse(true, 'Guardian unlinked from student');
            }

            $this->studentGuardianModel->where('guardian_id', (int) $payload['guardian_id'])->delete();
            $this->guardianModel->delete((int) $payload['guardian_id']);

            return $this->successResponse(true, 'Guardian removed successfully');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to remove guardian: ' . $e->getMessage());
        }
    }
}
