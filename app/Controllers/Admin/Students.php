<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\StudentsModel;
use CodeIgniter\HTTP\ResponseInterface;

class Students extends BaseController
{
    use RestTrait;

    protected $studentsModel;

    public function __construct()
    {
        $this->studentsModel = new StudentsModel();
    }

    /**
     * Get admin student list
     */
    public function adminList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $students = $this->studentsModel->getAdminStudents($data ?? []);
            
            return $this->successResponse($students, 'Students retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve students: ' . $e->getMessage());
        }
    }

    /**
     * Add new student (admin)
     */
    public function adminAdd(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['first_name', 'last_name', 'email', 'school_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $studentId = $this->studentsModel->createStudent($data);
            
            if ($studentId) {
                return $this->successResponse(['student_id' => $studentId], 'Student created successfully');
            } else {
                return $this->errorResponse('Failed to create student');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create student: ' . $e->getMessage());
        }
    }

    /**
     * Edit student (admin)
     */
    public function adminEdit(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['student_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $student = $this->studentsModel->getStudentById($data->student_id);
            
            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            return $this->successResponse($student, 'Student retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve student: ' . $e->getMessage());
        }
    }

    /**
     * Update student (admin)
     */
    public function adminUpdate(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['student_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->studentsModel->updateStudent($data->student_id, $data);
            
            if ($result) {
                return $this->successResponse($result, 'Student updated successfully');
            } else {
                return $this->errorResponse('Failed to update student');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update student: ' . $e->getMessage());
        }
    }

    /**
     * Remove student (admin)
     */
    public function adminRemove(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['student_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->studentsModel->deleteStudent($data->student_id);
            
            if ($result) {
                return $this->successResponse(null, 'Student deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete student');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete student: ' . $e->getMessage());
        }
    }

    /**
     * Get student details (admin)
     */
    public function adminDetail(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['student_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $student = $this->studentsModel->getStudentById($data->student_id);
            
            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            return $this->successResponse($student, 'Student details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve student details: ' . $e->getMessage());
        }
    }

    /**
     * Get student statistics
     */
    public function adminStats(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $stats = $this->studentsModel->getStudentStats($data ?? []);
            
            return $this->successResponse($stats, 'Student statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve student statistics: ' . $e->getMessage());
        }
    }

    /**
     * Bulk update students
     */
    public function adminBulkUpdate(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['student_ids', 'update_data'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->studentsModel->bulkUpdateStudents($data->student_ids, $data->update_data);
            
            if ($result) {
                return $this->successResponse(['updated_count' => $result], 'Students updated successfully');
            } else {
                return $this->errorResponse('Failed to update students');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update students: ' . $e->getMessage());
        }
    }
}
