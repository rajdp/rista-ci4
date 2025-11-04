<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Feedback extends BaseController
{
    /**
     * Get list of feedback/notes
     */
    public function list(): ResponseInterface
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
                    'ResponseObject' => null,
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }
            
            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Role_Id should not be empty'
                ]);
            }
            
            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get feedback list
            $builder = $db->table('student_content_feedback scf');
            $builder->select('scf.*, c.name as content_name, 
                             CONCAT(up.first_name, " ", up.last_name) as created_by_name,
                             DATE_FORMAT(scf.created_date, "%m-%d-%Y %H:%i") as created_date_formatted');
            $builder->join('content c', 'c.content_id = scf.content_id', 'left');
            $builder->join('user_profile up', 'up.user_id = scf.created_by', 'left');
            
            // Filter by student if provided
            if (!empty($params['student_id'])) {
                $builder->where('scf.student_id', $params['student_id']);
            }
            
            // Filter by class if provided
            if (!empty($params['class_id'])) {
                $builder->where('scf.class_id', $params['class_id']);
            }
            
            // Filter by school
            if (!empty($params['school_id'])) {
                $builder->where('scf.school_id', $params['school_id']);
            }
            
            // Filter by content if provided
            if (!empty($params['content_id'])) {
                $builder->where('scf.content_id', $params['content_id']);
            }
            
            $builder->orderBy('scf.created_date', 'DESC');
            
            $feedbackList = $builder->get()->getResultArray();
            
            // Process feedback list
            foreach ($feedbackList as $key => $value) {
                if ($value['content_id'] == 0 || empty($value['content_name'])) {
                    $feedbackList[$key]['content_name'] = "Class Notes";
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $feedbackList ?? [],
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
     * Add new feedback/note
     */
    public function add(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['notes'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Notes should not be empty'
                ]);
            }
            
            if (empty($params['type'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Notes type should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            $feedback = [
                'class_id' => $params['class_id'] ?? 0,
                'content_id' => isset($params['content_id']) && $params['content_id'] > 0 ? $params['content_id'] : 0,
                'student_id' => $params['student_id'] ?? 0,
                'school_id' => $params['school_id'] ?? 0,
                'notes' => $params['notes'],
                'notes_type' => $params['type'],
                'created_by' => $params['user_id'] ?? 0,
                'created_date' => date('Y-m-d H:i:s')
            ];
            
            $builder = $db->table('student_content_feedback');
            $insertFeedback = $builder->insert($feedback);
            
            if ($insertFeedback) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Feedback Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Feedback Add Failed'
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
     * Delete feedback/note
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Use $id parameter if provided, otherwise get from request body
            $feedbackId = $id ?? ($params['id'] ?? null);

            // Validation
            if (empty($feedbackId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Feedback ID should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            $builder = $db->table('student_content_feedback');
            $deleteFeedback = $builder->delete(['id' => $feedbackId]);
            
            if ($deleteFeedback) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Feedback Deleted Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Feedback Not Deleted'
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
}
