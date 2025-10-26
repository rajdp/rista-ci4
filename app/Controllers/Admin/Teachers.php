<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\TeachersModel;
use CodeIgniter\HTTP\ResponseInterface;

class Teachers extends BaseController
{
    use RestTrait;

    protected $teachersModel;

    public function __construct()
    {
        $this->teachersModel = new TeachersModel();
    }

    /**
     * Get admin teacher list
     */
    public function adminList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $teachers = $this->teachersModel->getAdminTeachers($data ?? []);
            
            return $this->successResponse($teachers, 'Teachers retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve teachers: ' . $e->getMessage());
        }
    }

    /**
     * Add new teacher (admin)
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

            $teacherId = $this->teachersModel->createTeacher($data);
            
            if ($teacherId) {
                return $this->successResponse(['teacher_id' => $teacherId], 'Teacher created successfully');
            } else {
                return $this->errorResponse('Failed to create teacher');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create teacher: ' . $e->getMessage());
        }
    }

    /**
     * Edit teacher (admin)
     */
    public function adminEdit(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $teacher = $this->teachersModel->getTeacherById($data->teacher_id);
            
            if (!$teacher) {
                return $this->errorResponse('Teacher not found', 404);
            }

            return $this->successResponse($teacher, 'Teacher retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve teacher: ' . $e->getMessage());
        }
    }

    /**
     * Update teacher (admin)
     */
    public function adminUpdate(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->teachersModel->updateTeacher($data->teacher_id, $data);
            
            if ($result) {
                return $this->successResponse($result, 'Teacher updated successfully');
            } else {
                return $this->errorResponse('Failed to update teacher');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update teacher: ' . $e->getMessage());
        }
    }

    /**
     * Remove teacher (admin)
     */
    public function adminRemove(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->teachersModel->deleteTeacher($data->teacher_id);
            
            if ($result) {
                return $this->successResponse(null, 'Teacher deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete teacher');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete teacher: ' . $e->getMessage());
        }
    }

    /**
     * Get teacher details (admin)
     */
    public function adminDetail(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $teacher = $this->teachersModel->getTeacherById($data->teacher_id);
            
            if (!$teacher) {
                return $this->errorResponse('Teacher not found', 404);
            }

            return $this->successResponse($teacher, 'Teacher details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve teacher details: ' . $e->getMessage());
        }
    }

    /**
     * Get teacher statistics
     */
    public function adminStats(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $stats = $this->teachersModel->getTeacherStats($data ?? []);
            
            return $this->successResponse($stats, 'Teacher statistics retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve teacher statistics: ' . $e->getMessage());
        }
    }

    /**
     * Assign teacher to class
     */
    public function assignToClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id', 'class_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->teachersModel->assignToClass($data->teacher_id, $data->class_id);
            
            if ($result) {
                return $this->successResponse($result, 'Teacher assigned to class successfully');
            } else {
                return $this->errorResponse('Failed to assign teacher to class');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to assign teacher to class: ' . $e->getMessage());
        }
    }

    /**
     * Remove teacher from class
     */
    public function removeFromClass(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['teacher_id', 'class_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->teachersModel->removeFromClass($data->teacher_id, $data->class_id);
            
            if ($result) {
                return $this->successResponse(null, 'Teacher removed from class successfully');
            } else {
                return $this->errorResponse('Failed to remove teacher from class');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to remove teacher from class: ' . $e->getMessage());
        }
    }
}
