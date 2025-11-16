<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\V1\CourseModel;
use CodeIgniter\HTTP\ResponseInterface;

class Course extends BaseController
{
    use RestTrait;

    protected $courseModel;

    public function __construct()
    {
        $this->courseModel = new CourseModel();
    }

    /**
     * Get course list
     */
    public function list(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }
            
            // Default school_id if not provided
            if (!isset($params['school_id'])) {
                $params['school_id'] = 0;
            }
            
            // Use Query Builder directly with alias
            $db = \Config\Database::connect();
            $builder = $db->table('tbl_course c');
            $builder->select('c.course_id, c.course_name, c.seo_title, c.category_id, c.subject_id, c.grade_id, c.description,
                             c.short_description, COALESCE(c.path, "") as path, c.validity_start_date, c.validity_end_date, c.status, 
                             c.lessons, c.overview_content, c.course_content, c.prerequisites, c.other_details, 
                             COALESCE(c.documentation_requirements, "") as documentation_requirements, c.author, c.fees,
                             c.certified_course, c.multiple_schedule, c.schedule, c.redirect_url, COALESCE(c.button_name, "") as button_name,
                             c.created_by, c.created_date, c.is_popular, c.is_exclusive, c.event, c.display_order, c.contact_info,
                             c.entity_id,
                             (
                                 SELECT COUNT(*) 
                                 FROM class cls 
                                 WHERE cls.course_id = c.course_id 
                                   AND cls.school_id = c.entity_id
                             ) as class_count,
                             (SELECT GROUP_CONCAT(category_name) FROM tbl_course_category WHERE FIND_IN_SET(category_id, c.category_id)) as category_name,
                             (SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject_id)) as subject_name,
                             (SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade_id)) as grade_name');
            
            // Build conditions
            $builder->where('c.entity_id', $params['school_id']);
            
            if (isset($params['schedule']) && $params['schedule'] != '') {
                $builder->where('c.schedule', $params['schedule']);
            }
            
            if (isset($params['type']) && strtolower($params['type']) == 'active') {
                $builder->where('c.status', 'A');
            }
            
            // Add ordering
            $builder->orderBy('c.course_id', 'DESC');
            
            // Add pagination
            if (isset($params['page_no']) && $params['page_no'] != '' && isset($params['records_per_page'])) {
                $lower_limit = ($params['page_no'] - 1) * $params['records_per_page'];
                $builder->limit($params['records_per_page'], $lower_limit);
            }
            
            $course_list = $builder->get()->getResultArray();
            
            // Process course list - convert comma-separated values to arrays
            foreach ($course_list as $key => $value) {
                // Convert category_id, subject_id, grade_id to arrays
                $course_list[$key]['category_id'] = !empty($value['category_id']) ? explode(',', $value['category_id']) : [];
                $course_list[$key]['subject_id'] = !empty($value['subject_id']) ? explode(',', $value['subject_id']) : [];
                $course_list[$key]['grade_id'] = !empty($value['grade_id']) ? explode(',', $value['grade_id']) : [];
                
                // Convert category_name, subject_name, grade_name to arrays  
                $course_list[$key]['category_name'] = !empty($value['category_name']) ? explode(',', $value['category_name']) : [];
                $course_list[$key]['subject_name'] = !empty($value['subject_name']) ? explode(',', $value['subject_name']) : [];
                $course_list[$key]['grade_name'] = !empty($value['grade_name']) ? explode(',', $value['grade_name']) : [];
            }
            
            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $course_list,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Course list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get order list for courses
     */
    public function orderList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $db = \Config\Database::connect();
            
            // Get course orders
            $builder = $db->table('tbl_order o');
            $builder->select('o.*, u.first_name, u.last_name, u.email_id, 
                             c.course_name, c.fees');
            $builder->join('user u', 'o.user_id = u.user_id', 'left');
            $builder->join('tbl_course c', 'o.course_id = c.course_id', 'left');
            
            // Filter by school if provided
            if (!empty($params['school_id'])) {
                $builder->where('o.school_id', $params['school_id']);
            }
            
            // Filter by user if provided
            if (!empty($params['user_id']) && !empty($params['role_id']) && $params['role_id'] == '5') {
                $builder->where('o.user_id', $params['user_id']);
            }
            
            $builder->orderBy('o.created_date', 'DESC');
            
            $orders = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $orders,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Order list error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get SEO list for courses
     */
    public function seoList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $courseId = $params['course_id'] ?? 0;
            
            $builder = $this->db->table('course_seo');
            $builder->select('*');
            
            if ($courseId > 0) {
                $builder->where('course_id', $courseId);
            }
            
            $seoList = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $seoList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get FAQ list for courses
     */
    public function faqList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $courseId = $params['course_id'] ?? 0;
            
            $builder = $this->db->table('course_faq');
            $builder->select('*');
            
            if ($courseId > 0) {
                $builder->where('course_id', $courseId);
            }
            
            $builder->orderBy('id', 'ASC');
            $faqList = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $faqList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Add SEO data for course
     */
    public function addSeo(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $data = [
                'course_id' => $params['course_id'] ?? 0,
                'meta_title' => $params['meta_title'] ?? '',
                'meta_description' => $params['meta_description'] ?? '',
                'meta_keywords' => $params['meta_keywords'] ?? '',
                'created_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('course_seo');
            $result = $builder->insert($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'SEO Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add SEO'
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
     * Update SEO data for course
     */
    public function updateSeo(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $id = $params['id'] ?? 0;
            $data = [
                'meta_title' => $params['meta_title'] ?? '',
                'meta_description' => $params['meta_description'] ?? '',
                'meta_keywords' => $params['meta_keywords'] ?? '',
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('course_seo');
            $builder->where('id', $id);
            $result = $builder->update($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'SEO Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update SEO'
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
     * Add FAQ for course
     */
    public function addFaq(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $data = [
                'course_id' => $params['course_id'] ?? 0,
                'question' => $params['question'] ?? '',
                'answer' => $params['answer'] ?? '',
                'created_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('course_faq');
            $result = $builder->insert($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'FAQ Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add FAQ'
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
     * Update FAQ for course
     */
    public function updateFaq(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $id = $params['id'] ?? 0;
            $data = [
                'question' => $params['question'] ?? '',
                'answer' => $params['answer'] ?? '',
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('course_faq');
            $builder->where('id', $id);
            $result = $builder->update($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'FAQ Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update FAQ'
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
     * Add new course
     */
    public function add(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Prepare course data
            $data = [
                'course_name' => $params['course_name'] ?? '',
                'description' => $params['description'] ?? '',
                'short_description' => $params['short_description'] ?? '',
                'overview_content' => $params['overview_content'] ?? '',
                'prerequisites' => $params['prerequisites'] ?? '',
                'course_content' => $params['course_content'] ?? '',
                'other_details' => $params['other_details'] ?? '',
                'documentation_requirements' => $params['documentation_requirements'] ?? '',
                'author' => $params['author'] ?? '',
                'lessons' => $params['lessons'] ?? '',
                'path' => $params['path'] ?? '',
                'category_id' => is_array($params['category_id']) ? implode(',', $params['category_id']) : ($params['category_id'] ?? ''),
                'subject_id' => is_array($params['subject_id']) ? implode(',', $params['subject_id']) : ($params['subject_id'] ?? ''),
                'grade_id' => is_array($params['grade_id']) ? implode(',', $params['grade_id']) : ($params['grade_id'] ?? ''),
                'display_order' => $params['display_order'] ?? 0,
                'status' => $params['status'] ?? 'D',
                'certified_course' => $params['certified_course'] ?? 'N',
                'multiple_schedule' => $params['multiple_schedule'] ?? 'Y',
                'schedule' => $params['schedule'] ?? '1',
                'is_popular' => $params['is_popular'] ?? 'N',
                'is_exclusive' => $params['is_exclusive'] ?? 'N',
                'event' => $params['event'] ?? '0',
                'redirect_url' => $params['redirect_url'] ?? '',
                'button_name' => $params['button_name'] ?? '',
                'validity_start_date' => $params['validity_start_date'] ?? null,
                'validity_end_date' => $params['validity_end_date'] ?? null,
                'fees' => $params['fees'] ?? 0,
                'contact_info' => $params['contact_info'] ?? '',
                'entity_id' => $params['school_id'] ?? 0,
                'created_by' => $params['user_id'] ?? 0,
                'created_date' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $builder = $db->table('tbl_course');
            $result = $builder->insert($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Course added successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add course'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Course add error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Edit existing course
     */
    public function edit($id = null): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $courseId = $id ?? ($params['course_id'] ?? 0);
            
            if (empty($courseId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Course ID is required'
                ]);
            }

            // Prepare course data
            $data = [
                'course_name' => $params['course_name'] ?? '',
                'description' => $params['description'] ?? '',
                'short_description' => $params['short_description'] ?? '',
                'overview_content' => $params['overview_content'] ?? '',
                'prerequisites' => $params['prerequisites'] ?? '',
                'course_content' => $params['course_content'] ?? '',
                'other_details' => $params['other_details'] ?? '',
                'documentation_requirements' => $params['documentation_requirements'] ?? '',
                'author' => $params['author'] ?? '',
                'lessons' => $params['lessons'] ?? '',
                'path' => $params['path'] ?? '',
                'category_id' => is_array($params['category_id']) ? implode(',', $params['category_id']) : ($params['category_id'] ?? ''),
                'subject_id' => is_array($params['subject_id']) ? implode(',', $params['subject_id']) : ($params['subject_id'] ?? ''),
                'grade_id' => is_array($params['grade_id']) ? implode(',', $params['grade_id']) : ($params['grade_id'] ?? ''),
                'display_order' => $params['display_order'] ?? 0,
                'status' => $params['status'] ?? 'D',
                'certified_course' => $params['certified_course'] ?? 'N',
                'multiple_schedule' => $params['multiple_schedule'] ?? 'Y',
                'schedule' => $params['schedule'] ?? '1',
                'is_popular' => $params['is_popular'] ?? 'N',
                'is_exclusive' => $params['is_exclusive'] ?? 'N',
                'event' => $params['event'] ?? '0',
                'redirect_url' => $params['redirect_url'] ?? '',
                'button_name' => $params['button_name'] ?? '',
                'validity_start_date' => $params['validity_start_date'] ?? null,
                'validity_end_date' => $params['validity_end_date'] ?? null,
                'fees' => $params['fees'] ?? 0,
                'contact_info' => $params['contact_info'] ?? '',
                'entity_id' => $params['school_id'] ?? 0,
                'modified_by' => $params['user_id'] ?? 0,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $db = \Config\Database::connect();
            $builder = $db->table('tbl_course');
            $builder->where('course_id', $courseId);
            $result = $builder->update($data);

            if ($result !== false) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Course updated successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update course'
                ]);
            }

        } catch (\Exception $e) {
            log_message('error', 'Course edit error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }
}
