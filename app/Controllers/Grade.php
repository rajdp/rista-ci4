<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\V1\GradeModel;
use CodeIgniter\HTTP\ResponseInterface;

class Grade extends BaseController
{
    use RestTrait;

    protected $gradeModel;

    public function __construct()
    {
        $this->gradeModel = new GradeModel();
    }

    /**
     * Get grade list
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Ensure data is an array
            if ($data === null) {
                $data = [];
            }
            
            $grades = $this->gradeModel->getGradeList($data);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $grades,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Grade list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Add grade
     */
    public function add(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Validate required fields
            $gradeName = trim((string) ($data['grade_name'] ?? ''));
            if ($gradeName === '') {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Grade name is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
            
            $schoolId = isset($data['school_id']) ? (int) $data['school_id'] : 0;
            
            // Check if grade with same name already exists for this school
            if ($this->gradeModel->gradeExists($gradeName, $schoolId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'A grade with the same name already exists'
                ], ResponseInterface::HTTP_CONFLICT);
            }
            
            $gradeId = $this->gradeModel->addGrade((object)$data);
            
            if ($gradeId) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Grade created successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add grade'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Grade add error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get grade for editing
     */
    public function getGrade(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $grade = $this->gradeModel->getGradeById($data->grade_id ?? 0);
            
            if ($grade) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => $grade,
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Grade not found'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit/Update grade (alias for update method)
     */
    public function edit($id = null): ResponseInterface
    {
        try {
            $data = $this->request->getJSON(true);
            
            // Get grade_id from URL parameter or request body
            $gradeId = $id !== null ? (int) $id : (isset($data['grade_id']) ? (int) $data['grade_id'] : 0);
            
            // Validate required fields
            if ($gradeId <= 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Grade ID is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
            
            // Ensure data is an array
            if ($data === null) {
                $data = [];
            }
            
            // Get existing grade to check current values
            $existingGrade = $this->gradeModel->getGradeById($gradeId);
            if (!$existingGrade) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Grade not found'
                ], ResponseInterface::HTTP_NOT_FOUND);
            }
            
            // Get grade name from request or use existing
            $gradeName = trim((string) ($data['grade_name'] ?? $existingGrade['grade_name'] ?? ''));
            if ($gradeName === '') {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Grade name is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }
            
            // Get school_id from request or use existing
            $schoolId = isset($data['school_id']) ? (int) $data['school_id'] : (isset($existingGrade['school_id']) ? (int) $existingGrade['school_id'] : 0);
            
            // Check if grade with same name already exists for this school (excluding current grade)
            if ($this->gradeModel->gradeExists($gradeName, $schoolId, $gradeId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'A grade with the same name already exists'
                ], ResponseInterface::HTTP_CONFLICT);
            }
            
            $result = $this->gradeModel->updateGrade($gradeId, (object)$data);
            
            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Grade updated successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update grade'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Grade edit error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update grade
     */
    public function update($id = null): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $result = $this->gradeModel->updateGrade($data->grade_id ?? 0, $data);
            
            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['message' => 'Grade updated successfully'],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update grade'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Remove grade
     */
    public function remove(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $result = $this->gradeModel->removeGrade($data->grade_id ?? 0);
            
            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => ['message' => 'Grade removed successfully'],
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to remove grade'
                ]);
            }

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get all students list (optionally filtered by grade)
     */
    public function allStudentList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            if (empty($params['school_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'School Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();

            // Build query to get all students
            $builder = $db->table('user u');
            $builder->select('upd.user_id, upd.user_id AS student_id,
                             u.email_id, u.email_id AS email,
                             COALESCE(up.first_name, "") AS first_name,
                             COALESCE(up.last_name, "") AS last_name,
                             COALESCE(CONCAT_WS(" ", up.first_name, up.last_name), "") AS name,
                             upd.grade_id, COALESCE(g.grade_name, "") AS grade_name');
            
            // Add validity/end_date if provided
            if (isset($params['end_date']) && !empty($params['end_date'])) {
                $builder->select("'{$params['end_date']}' as validity", false);
            }

            $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
            $builder->join('user_profile_details upd', 'u.user_id = upd.user_id', 'left');
            $builder->join('grade g', 'upd.grade_id = g.grade_id', 'left');
            
            // Filter conditions
            $builder->where("FIND_IN_SET('{$params['school_id']}', u.school_id) >", 0);
            $builder->where('u.role_id', '5'); // Role 5 = Student
            $builder->where('upd.status', '1');
            $builder->where('upd.school_id', $params['school_id']);

            // Filter by grade_id if provided
            if (isset($params['grade_id']) && !empty($params['grade_id'])) {
                if (is_array($params['grade_id'])) {
                    $gradeIds = implode(',', $params['grade_id']);
                } else {
                    $gradeIds = $params['grade_id'];
                }
                if (!empty($gradeIds)) {
                    $builder->where("upd.grade_id IN ($gradeIds)", null, false);
                }
            }

            $builder->orderBy('up.first_name', 'ASC');

            $studentList = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $studentList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'All student list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}
