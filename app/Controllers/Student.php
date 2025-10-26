<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class Student extends ResourceController
{
    protected $studentModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->studentModel = new \App\Models\V1\StudentModel();
    }

    /**
     * Get list of students
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $students = $this->studentModel->getStudents($data ?? (object)[]);
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $students,
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
     * Get students from class list
     */
    public function StudentFromClassList(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();
            
            $classList = $this->studentModel->studentFromClass($data ?? (object)[]);
            
            // Process teacher IDs
            foreach ($classList as $key => $value) {
                if (isset($value['teacher_id'])) {
                    $classList[$key]['teacher_id'] = explode(',', $value['teacher_id']);
                }
            }
            
            // Remove duplicates
            $uniqueList = array_values(array_map("unserialize", array_unique(array_map("serialize", $classList))));
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $uniqueList,
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
     * Get all classes for a student
     */
    public function studentAllClassList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get all classes for the student
            $builder = $db->table('student_class sc');
            $builder->select('sc.class_id, sc.student_id, sc.notify_status, sc.joining_date, sc.drafted_date,
                             c.class_name, c.start_date, c.end_date, c.status as class_status, 
                             c.class_code, 
                             COALESCE(s.subject_name, "") as subject, 
                             COALESCE(gr.grade_name, "") as grade,
                             b.batch_name');
            $builder->join('class c', 'sc.class_id = c.class_id', 'left');
            $builder->join('batch b', 'c.batch_id = b.batch_id', 'left');
            $builder->join('grade gr', 'c.grade = gr.grade_id', 'left');
            $builder->join('subject s', 'c.subject = s.subject_id', 'left');
            $builder->where('sc.student_id', $params['student_id']);
            $builder->where('sc.status', 1);
            $builder->orderBy('sc.created_date', 'DESC');
            
            $classes = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $classes ?? [],
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

    public function attendanceDetail()
    {
        try {
            // Handle JSON POST data
            $params = $this->request->getJSON(true);
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['student_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Student Id should not be empty'
                ]);
            }

            $db = \Config\Database::connect();
            
            // Get student attendance
            $builder = $db->table('class_attendance ca');
            $builder->select('ca.date, ca.attendance, ca.slot_day, ca.start_time, ca.end_time, 
                             ca.student_id, ca.class_id, c.class_name');
            $builder->join('class c', 'c.class_id = ca.class_id', 'left');
            $builder->where('ca.student_id', $params['student_id']);
            
            // Optional class filter
            if (isset($params['class_id']) && !empty($params['class_id'])) {
                $builder->where('ca.class_id', $params['class_id']);
            }
            
            $builder->orderBy('ca.date', 'ASC');
            
            $attendance = $builder->get()->getResultArray();

            // Check for makeup classes for absent students
            foreach ($attendance as $key => &$value) {
                if (isset($value['attendance']) && $value['attendance'] == 0) {
                    // Check if makeup class exists
                    $makeupBuilder = $db->table('student_class_transfer sc');
                    $makeupBuilder->select('sc.class_id as makeup_class_id, c.class_name as makeup_class_name, 
                                           sc.joining_date as makeup_class_date');
                    $makeupBuilder->join('class c', 'sc.class_id = c.class_id', 'left');
                    $makeupBuilder->where('sc.from_class', $value['class_id']);
                    $makeupBuilder->where('sc.student_id', $params['student_id']);
                    $makeupBuilder->where('sc.absent_date', $value['date']);
                    $makeupBuilder->where('sc.type', 'M');
                    $makeupBuilder->where('sc.status', 1);
                    
                    $makeupClass = $makeupBuilder->get()->getRowArray();
                    
                    if ($makeupClass) {
                        $value['makeup_class_id'] = $makeupClass['makeup_class_id'];
                        $value['makeup_class_name'] = $makeupClass['makeup_class_name'];
                        $value['makeup_class_date'] = $makeupClass['makeup_class_date'];
                    } else {
                        $value['makeup_class_id'] = '';
                        $value['makeup_class_name'] = '';
                        $value['makeup_class_date'] = '';
                    }
                } else {
                    $value['makeup_class_id'] = '';
                    $value['makeup_class_name'] = '';
                    $value['makeup_class_date'] = '';
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $attendance ?? [],
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
}

