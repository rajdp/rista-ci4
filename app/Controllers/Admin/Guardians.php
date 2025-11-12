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
            
            log_message('debug', '📥 Guardians::list - Payload: ' . json_encode($payload));
            
            // Get access token from header
            $accessToken = $this->request->getHeaderLine('Accesstoken');
            
            if (empty($accessToken)) {
                log_message('warning', '⚠️ Guardians::list - No access token provided');
                return $this->unauthorizedResponse('Access token required');
            }

            // Validate token using the Authorization library with exception handling
            try {
                $tokenData = \App\Libraries\Authorization::validateToken($accessToken);
                
                if (!$tokenData) {
                    log_message('warning', '⚠️ Guardians::list - Token validation returned false');
                    return $this->unauthorizedResponse('Invalid or expired token');
                }
            } catch (\Throwable $e) {
                log_message('error', '❌ Guardians::list - Token validation exception: ' . $e->getMessage());
                return $this->unauthorizedResponse('Invalid token format');
            }

            log_message('debug', '✅ Guardians::list - Token validated successfully. Token data: ' . json_encode($tokenData));

            // Get user info from token
            $userId = \App\Libraries\Authorization::getUserId($tokenData);
            $schoolId = \App\Libraries\Authorization::getSchoolId($tokenData);
            $isAdmin = \App\Libraries\Authorization::isAdmin($tokenData);
            $roleId = isset($tokenData->role_id) ? (int) $tokenData->role_id : 0;
            $canViewOtherStudents = $isAdmin || in_array($roleId, [2, 4], true);
            $canListWithoutStudentId = $isAdmin || $roleId === 2;

            log_message('debug', '📝 Guardians::list - User ID: ' . ($userId ?? 'null') . ', School ID: ' . ($schoolId ?? 'null') . ', Is Admin: ' . ($isAdmin ? 'yes' : 'no'));

            // If a specific student_id is requested, verify permission
            if (!empty($payload['student_id'])) {
                $requestedStudentId = (int) $payload['student_id'];
                
                // Allow if user is admin OR if user is the student requesting their own data
                if (!$canViewOtherStudents && (string) $userId !== (string) $requestedStudentId) {
                    log_message('warning', '⚠️ Guardians::list - Permission denied: user ' . $userId . ' requested student ' . $requestedStudentId);
                    return $this->unauthorizedResponse('You do not have permission to view this student\'s guardian information');
                }
                
                log_message('debug', '✅ Guardians::list - Permission granted for student: ' . $requestedStudentId);
            } else if (!$canListWithoutStudentId) {
                // Users without elevated permissions must provide a specific student_id (their own)
                log_message('warning', '⚠️ Guardians::list - Non-admin user must provide student_id');
                return $this->unauthorizedResponse('Student ID is required');
            }

            if (empty($payload['school_id'])) {
                $payload['school_id'] = $schoolId;
                log_message('debug', '📝 Guardians::list - School ID from token: ' . ($payload['school_id'] ?? 'null'));
            }

            // If student_id is provided, get guardians linked to that student
            if (!empty($payload['student_id'])) {
                $studentId = (int) $payload['student_id'];
                
                // Get guardian IDs linked to this student
                $studentLinks = $this->studentGuardianModel
                    ->where('student_id', $studentId)
                    ->findAll();
                
                $guardianIds = array_column($studentLinks, 'guardian_id');
                
                if (empty($guardianIds)) {
                    log_message('debug', '📝 Guardians::list - No guardians found for student: ' . $studentId);
                    return $this->successResponse([], 'No guardians found for this student');
                }
                
                // Get guardian details
                $guardians = $this->guardianModel
                    ->whereIn('id', $guardianIds)
                    ->where('school_id', $payload['school_id'])
                    ->where('status', 1)
                    ->findAll();
                
                $links = $studentLinks;
            } else {
                // Admin viewing all guardians
                $guardians = $this->guardianModel->filterGuardians($payload);

                // Attach linked students for quick display
                $guardianIds = array_column($guardians, 'id');
                $links = [];

                if (!empty($guardianIds)) {
                    $links = $this->studentGuardianModel
                        ->whereIn('guardian_id', $guardianIds)
                        ->findAll();
                }
            }

            $studentDetails = [];
            if (!empty($links)) {
                $studentIds = array_unique(array_column($links, 'student_id'));
                if (!empty($studentIds)) {
                    try {
                        $studentDetails = $this->studentsModel
                            ->select('id, first_name, last_name, email, grade_id')
                            ->whereIn('id', $studentIds)
                            ->findAll();
                        $studentDetails = array_column($studentDetails, null, 'id');
                    } catch (\Throwable $e) {
                        log_message('warning', '⚠️ Guardians::list - Unable to load student reference data: ' . $e->getMessage());
                        $studentDetails = [];
                    }
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

            log_message('debug', '✅ Guardians::list - Returning ' . count($guardians) . ' guardian(s)');
            return $this->successResponse($guardians, 'Guardians retrieved successfully');
        } catch (\Throwable $e) {
            log_message('error', '❌ Guardians::list - Error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            log_message('error', '❌ Guardians::list - Stack trace: ' . $e->getTraceAsString());
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

            // Get access token and validate permission
            $accessToken = $this->request->getHeaderLine('Accesstoken');
            if (!empty($accessToken)) {
                $tokenData = \App\Libraries\Authorization::validateToken($accessToken);
                if ($tokenData) {
                    $userId = \App\Libraries\Authorization::getUserId($tokenData);
                    $isAdmin = \App\Libraries\Authorization::isAdmin($tokenData);
                    
                    // Check if non-admin user is trying to assign guardians to other students
                    if (!$isAdmin) {
                        $studentIds = array_map('intval', (array) $payload['student_ids']);
                        foreach ($studentIds as $studentId) {
                            if ($userId != $studentId) {
                                log_message('warning', '⚠️ Guardians::assign - Permission denied: user ' . $userId . ' tried to assign to student ' . $studentId);
                                return $this->unauthorizedResponse('You can only manage your own guardian information');
                            }
                        }
                    }
                }
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

            log_message('debug', '✅ Guardians::assign - Guardian ' . $guardianId . ' assigned to ' . count($studentIds) . ' student(s)');
            return $this->successResponse(true, 'Guardian assignments updated successfully');
        } catch (\Throwable $e) {
            log_message('error', '❌ Guardians::assign - Error: ' . $e->getMessage());
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

            // Get access token and validate permission
            $accessToken = $this->request->getHeaderLine('Accesstoken');
            if (!empty($accessToken)) {
                $tokenData = \App\Libraries\Authorization::validateToken($accessToken);
                if ($tokenData) {
                    $userId = \App\Libraries\Authorization::getUserId($tokenData);
                    $isAdmin = \App\Libraries\Authorization::isAdmin($tokenData);
                    
                    // Check if non-admin user is trying to remove guardians from other students
                    if (!$isAdmin && !empty($payload['student_id'])) {
                        $studentId = (int) $payload['student_id'];
                        if ($userId != $studentId) {
                            log_message('warning', '⚠️ Guardians::remove - Permission denied: user ' . $userId . ' tried to remove from student ' . $studentId);
                            return $this->unauthorizedResponse('You can only manage your own guardian information');
                        }
                    }
                }
            }

            if (!empty($payload['student_id'])) {
                $this->studentGuardianModel->where('guardian_id', (int) $payload['guardian_id'])
                    ->where('student_id', (int) $payload['student_id'])
                    ->delete();

                log_message('debug', '✅ Guardians::remove - Guardian ' . $payload['guardian_id'] . ' unlinked from student ' . $payload['student_id']);
                return $this->successResponse(true, 'Guardian unlinked from student');
            }

            $this->studentGuardianModel->where('guardian_id', (int) $payload['guardian_id'])->delete();
            $this->guardianModel->delete((int) $payload['guardian_id']);

            log_message('debug', '✅ Guardians::remove - Guardian ' . $payload['guardian_id'] . ' removed completely');
            return $this->successResponse(true, 'Guardian removed successfully');
        } catch (\Throwable $e) {
            log_message('error', '❌ Guardians::remove - Error: ' . $e->getMessage());
            return $this->errorResponse('Unable to remove guardian: ' . $e->getMessage());
        }
    }
}
