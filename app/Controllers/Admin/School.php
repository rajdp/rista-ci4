<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\SchoolModel;
use CodeIgniter\HTTP\ResponseInterface;

class School extends BaseController
{
    use RestTrait;

    protected $schoolModel;

    public function __construct()
    {
        $this->schoolModel = new SchoolModel();
    }

    /**
     * Get admin school list
     */
    public function adminList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $schools = $this->schoolModel->getAdminSchools($data ?? []);
            
            return $this->successResponse($schools, 'Schools retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve schools: ' . $e->getMessage());
        }
    }

    /**
     * Add new school (admin)
     */
    public function adminAdd(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_name', 'email', 'phone', 'address'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $schoolId = $this->schoolModel->createSchool($data);
            
            if ($schoolId) {
                return $this->successResponse(['school_id' => $schoolId], 'School created successfully');
            } else {
                return $this->errorResponse('Failed to create school');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to create school: ' . $e->getMessage());
        }
    }

    /**
     * Edit school (admin)
     */
    public function adminEdit(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $school = $this->schoolModel->getSchoolById($data->school_id);
            
            if (!$school) {
                return $this->errorResponse('School not found', 404);
            }

            return $this->successResponse($school, 'School retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve school: ' . $e->getMessage());
        }
    }

    /**
     * Update school (admin)
     */
    public function adminUpdate(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->schoolModel->updateSchool($data->school_id, $data);
            
            if ($result) {
                return $this->successResponse($result, 'School updated successfully');
            } else {
                return $this->errorResponse('Failed to update school');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to update school: ' . $e->getMessage());
        }
    }

    /**
     * Remove school (admin)
     */
    public function adminRemove(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->schoolModel->deleteSchool($data->school_id);
            
            if ($result) {
                return $this->successResponse(null, 'School deleted successfully');
            } else {
                return $this->errorResponse('Failed to delete school');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to delete school: ' . $e->getMessage());
        }
    }

    /**
     * Get school details (admin)
     */
    public function adminDetail(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $school = $this->schoolModel->getSchoolById($data->school_id);
            
            if (!$school) {
                return $this->errorResponse('School not found', 404);
            }

            return $this->successResponse($school, 'School details retrieved successfully');

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to retrieve school details: ' . $e->getMessage());
        }
    }

    /**
     * Add admin to school
     */
    public function addAdmin(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            if (!$data) {
                return $this->errorResponse('Invalid request data');
            }

            // Validate required fields
            $requiredFields = ['school_id', 'user_id', 'role_id'];
            if (!$this->validateRequiredFields((array)$data, $requiredFields)) {
                $missing = $this->getMissingFields((array)$data, $requiredFields);
                return $this->errorResponse('Missing required fields: ' . implode(', ', $missing));
            }

            $result = $this->schoolModel->addAdminToSchool($data->school_id, $data->user_id, $data->role_id);
            
            if ($result) {
                return $this->successResponse($result, 'Admin added to school successfully');
            } else {
                return $this->errorResponse('Failed to add admin to school');
            }

        } catch (\Exception $e) {
            return $this->errorResponse('Failed to add admin to school: ' . $e->getMessage());
        }
    }
}
