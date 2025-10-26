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
                             c.lessons, c.overview_content, c.course_content, c.prerequisites, c.other_details, c.author, c.fees,
                             c.certified_course, c.multiple_schedule, c.schedule, c.redirect_url, COALESCE(c.button_name, "") as button_name,
                             c.created_by, c.created_date, c.is_popular, c.is_exclusive, c.event, c.display_order, c.contact_info,
                             c.entity_id,
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
            
            // Process course list
            foreach ($course_list as $key => $value) {
                if (isset($value['schedule']) && $value['schedule'] == 0) {
                    $schedule_condition = "csc.course_id = {$value['course_id']}";
                    $schedule = $this->courseModel->courseWithoutSchedule($schedule_condition);
                    if (!empty($schedule[0])) {
                        $course_list[$key] = array_merge($course_list[$key], [
                            'schedule_id' => $schedule[0]['schedule_id'] ?? '',
                            'schedule_title' => $schedule[0]['schedule_title'] ?? '',
                            'course_start_date' => $schedule[0]['course_start_date'] ?? '',
                            'course_end_date' => $schedule[0]['course_end_date'] ?? '',
                            'registration_start_date' => $schedule[0]['registration_start_date'] ?? '',
                            'registration_end_date' => $schedule[0]['registration_end_date'] ?? '',
                            'course_type' => ($schedule[0]['course_type'] ?? 0) == 1 ? 'O' : 'I',
                            'location_id' => $schedule[0]['location_id'] ?? '',
                            'cost' => $schedule[0]['cost'] ?? '',
                            'discount_amount' => $schedule[0]['discount_amount'] ?? '',
                            'actual_cost' => $schedule[0]['actual_cost'] ?? '',
                            'total_slots' => $schedule[0]['total_slots'] ?? '',
                            'slots_booked' => $schedule[0]['slots_booked'] ?? ''
                        ]);
                    }
                }
                $course_list[$key]['category_id'] = !empty($value['category_id']) ? explode(',', $value['category_id']) : [];
                $course_list[$key]['subject_id'] = !empty($value['subject_id']) ? explode(',', $value['subject_id']) : [];
                $course_list[$key]['category_name'] = !empty($value['category_name']) ? explode(',', $value['category_name']) : [];
                $course_list[$key]['subject_name'] = !empty($value['subject_name']) ? explode(',', $value['subject_name']) : [];
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
}
