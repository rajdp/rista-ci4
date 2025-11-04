<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Teacher extends ResourceController
{
    protected $teacherModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->teacherModel = new \App\Models\V1\TeacherModel();
    }

    /**
     * Get list of teachers
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $teachers = $this->teacherModel->getTeachers($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $teachers,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get class list for teacher
     * This method returns the classes assigned to a teacher
     */
    public function classList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::classList called with params: ' . json_encode($params));

            // Use Classes controller/model logic for getting class list
            $classesController = new \App\Controllers\Classes();
            $classList = $classesController->list();
            
            return $classList;

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::classList error: ' . $e->getMessage());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student assessment list for grading
     * This method returns student work submissions for teacher grading
     */
    public function studentAssessment(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::studentAssessment called with params: ' . json_encode($params));

            // Determine content type: 2 = Assignment, 3 = Assessment
            // Default to 2 (assignment) if not specified
            $contentType = isset($params['content_type']) ? (int)$params['content_type'] : 2;
            
            // Get student assessment/assignment submissions
            $studentWork = $this->teacherModel->studentAssessment($params, $contentType);
            
            log_message('debug', '✅ Teacher::studentAssessment returning ' . count($studentWork) . ' records');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $studentWork,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::studentAssessment error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assessment list for teacher
     */
    public function assessmentList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::assessmentList called with params: ' . json_encode($params));

            // Get assessment list from model
            $assessmentList = $this->teacherModel->assessmentList($params);
            
            // Get student names for each assessment
            foreach ($assessmentList as $key => $value) {
                if (!empty($value['student_ids'])) {
                    $totalStudentsName = $this->teacherModel->getStudentsName($value['student_ids']);
                    $assessmentList[$key]['total_students_Name'] = !empty($totalStudentsName[0]['student_names']) 
                        ? explode(',', $totalStudentsName[0]['student_names']) 
                        : [];
                } else {
                    $assessmentList[$key]['total_students_Name'] = [];
                }
            }

            log_message('debug', '✅ Teacher::assessmentList returning ' . count($assessmentList) . ' records');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $assessmentList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::assessmentList error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get assignment list for teacher
     */
    public function assignmentList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::assignmentList called with params: ' . json_encode($params));

            // Get assignment list from model
            $assignmentList = $this->teacherModel->assignmentList($params);
            
            // Get student names for each assignment
            foreach ($assignmentList as $key => $value) {
                if (!empty($value['student_ids'])) {
                    $totalStudentsName = $this->teacherModel->getStudentsName($value['student_ids']);
                    $assignmentList[$key]['total_students_Name'] = !empty($totalStudentsName[0]['student_names']) 
                        ? explode(',', $totalStudentsName[0]['student_names']) 
                        : [];
                } else {
                    $assignmentList[$key]['total_students_Name'] = [];
                }
            }

            log_message('debug', '✅ Teacher::assignmentList returning ' . count($assignmentList) . ' records');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $assignmentList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::assignmentList error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student correction list (submitted work for teacher to grade)
     */
    public function studentCorrectionList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // For now, return empty array - this needs full implementation from CI3
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => [],
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get list of students assigned to a specific content
     * Returns student list with their progress/scores
     */
    public function teacherassignStudent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::teacherassignStudent called with params: ' . json_encode($params));

            // Validate required parameters
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content ID is required'
                ], 400);
            }

            // Get assigned students for this content
            $studentList = $this->teacherModel->teacherAssign($params);
            
            // Calculate percentages
            foreach($studentList as $key => $value) {
                if ($value['total_question'] != 0) {
                    if ($value['attend_questions'] == '-') {
                        $studentList[$key]['attend_questions'] = 0;
                    }
                }
                if($value['attend_questions'] == '-' || $value['attend_questions'] != 0) {
                    $studentList[$key]['percentage'] = $value['total_points'] != 0 
                        ? round($value['earned_points'] ? ($value['earned_points'] / $value['total_points']) * 100 : 0) 
                        : 0;
                    $studentList[$key]['percentage'] = $studentList[$key]['percentage'] . '%';
                } else {
                    $studentList[$key]['percentage'] = 0;
                    $studentList[$key]['percentage'] = $studentList[$key]['percentage'] . '%';
                }
            }
            
            log_message('debug', '✅ Teacher::teacherassignStudent returning ' . count($studentList) . ' students');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $studentList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::teacherassignStudent error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get student answer list for a specific submission
     * Returns detailed answers for grading
     */
    public function studentAnswerList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            log_message('debug', '📥 Teacher::studentAnswerList called with params: ' . json_encode($params));

            // Validate required parameters
            if (empty($params['content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Content ID is required'
                ], 400);
            }

            if (empty($params['student_content_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Content ID is required'
                ], 400);
            }

            // Get student answer details
            $result = $this->teacherModel->studentAnswerList($params);
            
            log_message('debug', '✅ Teacher::studentAnswerList returning data');

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $result,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', '❌ Teacher::studentAnswerList error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], 500);
        }
    }
}

