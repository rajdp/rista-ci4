<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

class CourseService
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $CI;

    function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('v1/course_model');
        $this->CI->load->model('v1/common_model');
        $this->course_model = $this->CI->course_model;
        $this->common_model = $this->CI->common_model;
    }
    public function categoryList($params)
    {
        $condition = "";
        $category_list = $this->course_model->categoryList($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ErrorObject'] = $category_list;
        return $this->jsonarr;
    }
    public function addCourse($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["course_name"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Name should not be empty";
        } else if (count($params["category_id"]) == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Category should not be empty";
        } else if ($params["short_description"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Short Description should not be empty";
        } else if (count($params["subject_id"]) == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Subject should not be empty";
        } else if ($params["status"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else if ($params["is_popular"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Invalid data for popular course";
        } else if ($params["is_exclusive"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Invalid data for exclusive course";
        } else {

            $course = [];
            // $condition = "WHERE user_id = {$params['user_id']}";
            // $get_admin_detail = $this->course_model->getAdminDetails($condition);
            $condition = "WHERE course_name = '{$params['course_name']}'";
            $check_course_exists = $this->course_model->getCourse($condition);
            if (count($check_course_exists) == 0) {
                if ($params['schedule'] == 1) {
                    $course['course_name'] = $params['course_name'];
                    $params['course_name'] = preg_replace("![^a-z0-9]+!i", "-", $params['course_name']);
                    $course['seo_title'] = $params['course_name'];
                    $course['category_id'] = implode(',', $params['category_id']);
                    $course['short_description'] = $params['short_description'];
                    $course['description'] = isset($params['description']) && $params['description'] != '' ? $params['description'] : '';
                    $course['subject_id'] = implode(',', $params['subject_id']);
                    $course['grade_id'] = isset($params['grade_id']) && count($params['grade_id']) > 0 ? implode(',', $params['grade_id']) : NULL;
                    $course['path'] = $params['path'];
                    $course['validity_start_date'] = isset($params['validity_start_date']) && $params['validity_start_date'] != '' ? $params['validity_start_date'] : NULL;
                    $course['validity_end_date'] = isset($params['validity_end_date']) && $params['validity_end_date'] != '' ? $params['validity_end_date'] : '0000-00-00';
                    $course['status'] = $params['status'];
                    $course['lessons'] = isset($params['lessons']) && $params['lessons'] != '' ? $params['lessons'] : '';
                    $course['overview_content'] = isset($params['overview_content']) && $params['overview_content'] != '' ? $params['overview_content'] : '';
                    $course['course_content'] = isset($params['course_content']) && $params['course_content'] != '' ? $params['course_content'] : '';
                    $course['prerequisites'] = isset($params['prerequisites']) && $params['prerequisites'] != '' ? $params['prerequisites'] : '';
                    $course['other_details'] = isset($params['other_details']) && $params['other_details'] != '' ? $params['other_details'] : '';
                    $course['author'] = isset($params['author']) && $params['author'] != '' ? $params['author'] : '';
                    $course['fees'] = $params['fees'];
                    $course['is_popular'] = $params['is_popular'];
                    $course['is_exclusive'] = $params['is_exclusive'];
                    $course['certified_course'] = $params['certified_course'];
                    $course['multiple_schedule'] = $params['multiple_schedule'];
                    $course['schedule'] = isset($params['schedule']) && $params['schedule'] != '' ? $params['schedule'] : 0;
                    $course['entity_id'] = isset($params['school_id']) && $params['school_id'] != '' ? $params['school_id'] : 0;
                    $course['redirect_url'] = isset($params['redirect_url']) && $params['redirect_url'] != '' ? $params['redirect_url'] : '';
                    $course['button_name'] = isset($params['button_name']) && $params['button_name'] != '' ? $params['button_name'] : '';
                    $course['event'] = isset($params['event']) && $params['event'] != '' ? $params['event'] : 0;
                    $course['contact_info'] = isset($params['contact_info']) && $params['contact_info'] != '' ? $params['contact_info'] : NULL;
                    $course['created_by'] = $params['user_id'];
                    $course['created_date'] = date('Y-m-d H:i:s');
                    $course_id = $this->common_model->insert('tbl_course', $course);
                    if ($course_id > 0) {
                        $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.display_order DESC";
                        $get_course = $this->course_model->getCourse($condition);
                        if (isset($params['display_order']) && $params['display_order'] != '') {
                            if (count($get_course) > 0 && $get_course[0]['display_order'] > $params['display_order']) {
                                foreach ($get_course as $key => $value) {
                                    if ($value['display_order'] >= $params['display_order']) {
                                        $condition = array('course_id' => $value['course_id']);
                                        $orderNo = array('display_order' => $value['display_order'] + 1);
                                        $this->common_model->update('tbl_course', $orderNo, $condition);
                                    }
                                }
                            }
                            $condition = array('course_id' => $course_id);
                            $data = array('display_order' => $params['display_order']);
                            $this->common_model->update('tbl_course', $data, $condition);
                        }
                        $log_condition = "WHERE course_id = $course_id";
                        $course_log = $this->common_model->insertLog('tbl_course', 'tbl_course_log', $log_condition);
                    }
                    if ($course_id > 0) {
                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject'] = "Course Added Successfully";
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Failed to Add Course.";
                    }
                } else {
                    $course['course_name'] = $params['course_name'];
                    $params['course_name'] = preg_replace("![^a-z0-9]+!i", "-", $params['course_name']);
                    $course['seo_title'] = $params['course_name'];
                    $course['category_id'] = implode(',', $params['category_id']);
                    $course['short_description'] = $params['short_description'];
                    $course['description'] = isset($params['description']) && $params['description'] != '' ? $params['description'] : '';
                    $course['subject_id'] = implode(',', $params['subject_id']);
                    $course['grade_id'] = isset($params['grade_id']) && count($params['grade_id']) > 0 ? implode(',', $params['grade_id']) : NULL;
                    $course['path'] = $params['path'];
                    $course['validity_start_date'] = isset($params['validity_start_date']) && $params['validity_start_date'] != '' ? $params['validity_start_date'] : NULL;
                    $course['validity_end_date'] = isset($params['validity_end_date']) && $params['validity_end_date'] != '' ? $params['validity_end_date'] : '0000-00-00';
                    $course['status'] = $params['status'];
                    $course['lessons'] = isset($params['lessons']) && $params['lessons'] != '' ? $params['lessons'] : '';
                    $course['overview_content'] = isset($params['overview_content']) && $params['overview_content'] != '' ? $params['overview_content'] : '';
                    $course['course_content'] = isset($params['course_content']) && $params['course_content'] != '' ? $params['course_content'] : '';
                    $course['prerequisites'] = isset($params['prerequisites']) && $params['prerequisites'] != '' ? $params['prerequisites'] : '';
                    $course['other_details'] = isset($params['other_details']) && $params['other_details'] != '' ? $params['other_details'] : '';
                    $course['author'] = isset($params['author']) && $params['author'] != '' ? $params['author'] : '';
                    $course['fees'] = $params['fees'];
                    $course['is_popular'] = $params['is_popular'];
                    $course['is_exclusive'] = $params['is_exclusive'];
                    $course['certified_course'] = $params['certified_course'];
                    $course['multiple_schedule'] = $params['multiple_schedule'];
                    $course['schedule'] = isset($params['schedule']) && $params['schedule'] != '' ? $params['schedule'] : 0;
                    $course['entity_id'] = isset($params['school_id']) && $params['school_id'] != '' ? $params['school_id'] : 0;
                    $course['redirect_url'] = isset($params['redirect_url']) && $params['redirect_url'] != '' ? $params['redirect_url'] : '';
                    $course['button_name'] = isset($params['button_name']) && $params['button_name'] != '' ? $params['button_name'] : '';
                    $course['event'] = isset($params['event']) && $params['event'] != '' ? $params['event'] : 0;
                    $course['contact_info'] = isset($params['contact_info']) && $params['contact_info'] != '' ? $params['contact_info'] : NULL;
                    $course['created_by'] = $params['user_id'];
                    $course['created_date'] = date('Y-m-d H:i:s');
                    $course_id = $this->common_model->insert('tbl_course', $course);
                    if ($course_id > 0) {
                        $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.display_order DESC";
                        $get_course = $this->course_model->getCourse($condition);
                        if (isset($params['display_order']) && $params['display_order'] != '') {
                            if (count($get_course) > 0 && $get_course[0]['display_order'] > $params['display_order']) {
                                foreach ($get_course as $key => $value) {
                                    if ($value['display_order'] >= $params['display_order']) {
                                        $condition = array('course_id' => $value['course_id']);
                                        $orderNo = array('display_order' => $value['display_order'] + 1);
                                        $this->common_model->update('tbl_course', $orderNo, $condition);
                                    }
                                }
                            }
                            $condition = array('course_id' => $course_id);
                            $data = array('display_order' => $params['display_order']);
                            $this->common_model->update('tbl_course', $data, $condition);
                        }
                        $log_condition = "WHERE course_id = $course_id";
                        $course_log = $this->common_model->insertLog('tbl_course', 'tbl_course_log', $log_condition);

                    }
                    if ($course_id > 0) {
                        //add schedule 
                        $params['program_code'] = strtoupper($this->common_model->random_strings(10));
                        $class['course_id'] = $course_id;
                        $class['class_name'] = $course['course_name'];
                        $class['school_id'] = isset($params['school_id']) && $params['school_id'] != '' ? $params['school_id'] : 0;
                        $class['start_date'] = $params['course_start_date'];
                        $class['end_date'] = $params['course_end_date'];
                        $class['registration_start_date'] = $params['registration_start_date'];
                        $class['registration_end_date'] = $params['registration_end_date'];
                        $class['class_code'] = $params['program_code'];
                        $class['payment_type'] = isset($params['payment_type']) && $params['payment_type'] != '' ? $params['payment_type'] : '';
                        $class['payment_sub_type'] = isset($params['payment_sub_type']) && $params['payment_sub_type'] != '' ? $params['payment_sub_type'] : '';
                        $class['class_type'] = strtoupper($params['course_type']) == 'O' ? 1 : 2;
                        $class['cost'] =  $params['cost'];
                        $class['discount_amount'] = isset($params['discount']) && $params['discount'] != '' ? $params['discount'] : 0;
                        $class['actual_cost'] =  $params['cost'] - $class['discount_amount'];
                        $class['total_slots'] = $params['total_slots'];
                        $class['slots_booked'] = 0;
                        $class['class_status'] = 1;
                        $class['status'] = strtoupper($params['status']) == 'A' ? 1 : 2;
                        $class['created_by'] = $params['user_id'];
                        $class['created_date'] = date('Y-m-d H:i:s');
                        $course_schedule_id = $this->common_model->insert('class', $class);

                        $this->jsonarr['IsSuccess'] = true;
                        $this->jsonarr['ResponseObject'] = "Course Added Successfully";
                    } else {
                        $this->jsonarr['IsSuccess'] = false;
                        $this->jsonarr['ErrorObject'] = "Failed to Add Course.";
                    }
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Course Name Already Exists";
            }
        }
        return $this->jsonarr;
    }

    public function updateCourse($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["course_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Id should not be empty";
        } else if ($params["course_name"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Name should not be empty";
        } else if (count($params["category_id"]) == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Category should not be empty";
        } else if ($params["short_description"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Short Description should not be empty";
        } else if (count($params["subject_id"]) == 0) {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Subject should not be empty";
        } else if ($params["status"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else if ($params["is_popular"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Invalid data for popular course";
        } else if ($params["is_exclusive"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Invalid data for exclusive course";
        } else {
            $course = [];
            $condition = "WHERE course_name = '{$params['course_name']}' AND course_id != {$params['course_id']}";
            $check_course_exists = $this->course_model->getCourse($condition);
            if (count($check_course_exists) == 0) {
                $condition = "WHERE course_id = {$params['course_id']}";
                $get_course = $this->course_model->getCourse($condition);
                $schedule_changed = true;
                if ($get_course[0]['schedule'] == $params['schedule']) {
                    $schedule_changed = false;
                }
                $course['course_name'] = $params['course_name'];
                $params['course_name'] = preg_replace("![^a-z0-9]+!i", "-", $params['course_name']);
                $course['seo_title'] = $params['course_name'];
                $course['category_id'] = implode(',', $params['category_id']);
                $course['short_description'] = $params['short_description'];
                $course['description'] = isset($params['description']) && $params['description'] != '' ? $params['description'] : '';
                $course['subject_id'] = implode(',', $params['subject_id']);
                $course['grade_id'] = isset($params['grade_id']) && count($params['grade_id']) > 0 ? implode(',', $params['grade_id']) : NULL;
                $course['path'] = $params['path'];
                $course['validity_start_date'] = isset($params['validity_start_date']) && $params['validity_start_date'] != '' ? $params['validity_start_date'] : NULL;
                $course['validity_end_date'] = isset($params['validity_end_date']) && $params['validity_end_date'] != '' ? $params['validity_end_date'] : '0000-00-00';
                $course['status'] = $params['status'];
                $course['lessons'] = isset($params['lessons']) && $params['lessons'] != '' ? $params['lessons'] : '';
                $course['overview_content'] = isset($params['overview_content']) && $params['overview_content'] != '' ? $params['overview_content'] : '';
                $course['course_content'] = isset($params['course_content']) && $params['course_content'] != '' ? $params['course_content'] : '';
                $course['prerequisites'] = isset($params['prerequisites']) && $params['prerequisites'] != '' ? $params['prerequisites'] : '';
                $course['other_details'] = isset($params['other_details']) && $params['other_details'] != '' ? $params['other_details'] : '';
                $course['author'] = isset($params['author']) && $params['author'] != '' ? $params['author'] : '';
                $course['fees'] = $params['fees'];
                $course['certified_course'] = $params['certified_course'];
                $course['multiple_schedule'] = $params['multiple_schedule'];
                $course['schedule'] = isset($params['schedule']) && $params['schedule'] != '' ? $params['schedule'] : 0;
                $course['redirect_url'] = isset($params['redirect_url']) && $params['redirect_url'] != '' ? $params['redirect_url'] : '';
                $course['is_popular'] = $params['is_popular'];
                $course['is_exclusive'] = $params['is_exclusive'];
                $course['button_name'] = isset($params['button_name']) && $params['button_name'] != '' ? $params['button_name'] : '';
                $course['event'] = isset($params['event']) && $params['event'] != '' ? $params['event'] : 0;
                $course['contact_info'] = isset($params['contact_info']) && $params['contact_info'] != '' ? $params['contact_info'] : NULL;
                $course['modified_by'] = $params['user_id'];
                $course['modified_date'] = date('Y-m-d H:i:s');
                $condition = array('course_id' => $params['course_id']);
                $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.display_order DESC";
                $get_course = $this->course_model->getCourse($condition);
                $condition = "WHERE c.course_id = '{$params['course_id']}'";
                $check_exists = $this->course_model->getCourse($condition);
                foreach ($get_course as $key => $value) {
                    if ($value['display_order'] > $check_exists[0]['display_order'] && $value['display_order'] <= $params['display_order']) {
                        $order_condition = array('course_id' => $value['course_id']);
                        $orderNo = array('display_order' => $value['display_order'] - 1);
                        $this->common_model->update('tbl_course', $orderNo, $order_condition);
                    } elseif ($value['display_order'] >= $params['display_order'] && $value['display_order'] < $check_exists[0]['display_order']) {
                        $order_condition = array('course_id' => $value['course_id']);
                        $orderNo = array('display_order' => $value['display_order'] + 1);
                        $this->common_model->update('tbl_course', $orderNo, $order_condition);
                    }
                }
                $course['display_order'] = $params['display_order'];
                $condition = array('course_id' => $params['course_id']);
                $update_course = $this->common_model->update('tbl_course', $course, $condition);
                if ($update_course) {
                    $log_condition = "WHERE course_id = {$params['course_id']}";
                    $course_log = $this->common_model->insertLog('tbl_course', 'tbl_course_log', $log_condition);
                }
                if (!$schedule_changed) {
                    if ($params['schedule'] == 1) {
                        if ($update_course) {
                            $this->jsonarr['IsSuccess'] = true;
                            $this->jsonarr['ResponseObject'] = "Course Updated Successfully";
                        } else {
                            $this->jsonarr['IsSuccess'] = false;
                            $this->jsonarr['ErrorObject'] = "Failed to Update Course.";
                        }
                    } else {
                        if ($update_course) {
                            $class['course_id'] = $params['course_id'];
                            $class['class_name'] = $course['course_name'];
                            $class['start_date'] = $params['course_start_date'];
                            $class['end_date'] = $params['course_end_date'];
                            $class['registration_start_date'] = $params['registration_start_date'];
                            $class['registration_end_date'] = $params['registration_end_date'];
                            $class['payment_type'] = isset($params['payment_type']) && $params['payment_type'] != '' ? $params['payment_type'] : '';
                            $class['payment_sub_type'] = isset($params['payment_sub_type']) && $params['payment_sub_type'] != '' ? $params['payment_sub_type'] : '';
                            $class['class_type'] = strtoupper($params['course_type']) == 'O' ? 1 :(strtoupper($params['course_type']) == 'I' ? 2 : 0);
                            $class['cost'] =  $params['cost'];
                            $class['discount_amount'] = isset($params['discount']) && $params['discount'] != '' ? $params['discount'] : 0;
                            $class['actual_cost'] =  $params['cost'] - $class['discount_amount'];
                            $class['total_slots'] = isset($params['total_slots']) ? $params['total_slots'] : 0;
                            $class['slots_booked'] = 0;
                            $class['class_status'] = 1;
                            $class['status'] = $params['status'] == 'A' ? 1 : 2;
                            $class['modified_by'] = $params['user_id'];
                            $class['modified_date'] = date('Y-m-d H:i:s');
                            $this->common_model->update('class', $class, $condition);
                            $this->jsonarr['IsSuccess'] = true;
                            $this->jsonarr['ResponseObject'] = "Course Updated Successfully";
                        } else {
                            $this->jsonarr['IsSuccess'] = false;
                            $this->jsonarr['ErrorObject'] = "Failed to Update Course.";
                        }
                    }
                } else {
                    $condition = array('course_id' => $params['course_id']);
                    $this->common_model->delete('class', $condition);
                    if ($params['schedule'] == 0) {
                        $params['program_code'] = strtoupper($this->common_model->random_strings(10));
                        $class['course_id'] = $params['course_id'];
                        $class['school_id'] = $params['school_id'];
                        $class['class_name'] = $course['course_name'];
                        $class['start_date'] = $params['course_start_date'];
                        $class['end_date'] = $params['course_end_date'];
                        $class['subject'] = implode(',', $params['subject_id']);
                        $class['grade'] = isset($params['grade_id']) && count($params['grade_id']) > 0 ? implode(',', $params['grade_id']) : NULL;
                        $class['registration_start_date'] = $params['registration_start_date'];
                        $class['registration_end_date'] = $params['registration_end_date'];
                        $class['class_code'] = $params['program_code'];
                        $class['payment_type'] = isset($params['payment_type']) && $params['payment_type'] != '' ? $params['payment_type'] : '';
                        $class['payment_sub_type'] = isset($params['payment_sub_type']) && $params['payment_sub_type'] != '' ? $params['payment_sub_type'] : '';
                        $class['class_type'] = strtoupper($params['course_type']) == 'O' ? 1 : 2;
                        $class['cost'] =  $params['cost'];
                        $class['discount_amount'] = isset($params['discount']) && $params['discount'] != '' ? $params['discount'] : 0;
                        $class['actual_cost'] =  $params['cost'] - $class['discount_amount'];
                        $class['total_slots'] = 0;
                        $class['slots_booked'] = 0;
                        $class['status'] = strtoupper($params['status']) == 'A' ? 1 : 2;
                        $class['created_by'] = $params['user_id'];
                        $class['created_date'] = date('Y-m-d H:i:s');
                        $this->common_model->insert('class', $class);
                    }
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Course Updated Successfully";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Course Already Exists";
            }
        }
        return $this->jsonarr;
    }

    public function courseList($params)
    {
        $limit = "";
        if (isset($params['page_no']) && $params['page_no'] != '') {
            $lower_limit = ($params['page_no'] - 1) * $params['records_per_page'];
            $upper_limit = $params['records_per_page'];
            $limit = " limit $lower_limit,$upper_limit";
        }
        // $condition = "WHERE c.status = 'A' AND c.entity_id = {$params['school_id']}";
        // if (isset($params['role_id']) && $params['role_id'] != '') {
        //     $condition = "WHERE c.entity_id = {$params['school_id']}";
        // }
        $schedule_condition = "";
        if (isset($params['schedule']) && $params['schedule'] != '') {
            $schedule_condition = "AND schedule = {$params['schedule']}";
        }
        $condition = "WHERE entity_id = {$params['school_id']} $schedule_condition ORDER BY c.course_id DESC ";
        if (isset($params['type']) && strtolower($params['type']) == 'active') {
            $condition = "WHERE status = 'A' AND entity_id = {$params['school_id']} $schedule_condition ORDER BY c.course_id DESC";
        }
        $course_list = $this->course_model->getCourse($condition . $limit);
        foreach ($course_list as $key => $value) {
            if ($value['schedule'] == 0) {
                $condition = "WHERE csc.course_id = {$value['course_id']}";
                $schedule = $this->course_model->CourseWithoutSchedule($condition);
                $course_list[$key]['schedule_id'] = $schedule[0]['schedule_id'];
                $course_list[$key]['schedule_title'] = $schedule[0]['schedule_title'];
                $course_list[$key]['course_start_date'] = $schedule[0]['course_start_date'];
                $course_list[$key]['course_end_date'] = $schedule[0]['course_end_date'];
                $course_list[$key]['registration_start_date'] = $schedule[0]['registration_start_date'];
                $course_list[$key]['registration_end_date'] = $schedule[0]['registration_end_date'];
                $course_list[$key]['course_type'] = $schedule[0]['course_type'] == 1 ? 'O' :'I';
                $course_list[$key]['location_id'] = $schedule[0]['location_id'];
                $course_list[$key]['cost'] = $schedule[0]['cost'];
                $course_list[$key]['discount_amount'] = $schedule[0]['discount_amount'];
                $course_list[$key]['actual_cost'] = $schedule[0]['actual_cost'];
                $course_list[$key]['total_slots'] = $schedule[0]['total_slots'];
                $course_list[$key]['slots_booked'] = $schedule[0]['slots_booked'];
               // $course_list[$key]['status'] = $schedule[0]['status'];
            }
            $course_list[$key]['category_id'] = explode(',', $value['category_id']);
            $course_list[$key]['subject_id'] = explode(',', $value['subject_id']);
            $course_list[$key]['category_name'] = explode(',', $value['category_name']);
            $course_list[$key]['subject_name'] = explode(',', $value['subject_name']);
            $course_list[$key]['grade_id'] = $value['grade_id'] != '' ? explode(',', $value['grade_id']) : [];
            $course_list[$key]['grade_name'] = $value['grade_name'] != '' ? explode(',', $value['grade_name']) : [];
        }
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = $course_list;
        return $this->jsonarr;
    }

    public function allCourses($params)
    {
        $condition = "";
        $course_condition = [];
        //   $course_condition[] = "cd.registration_start_date <= CURRENT_DATE() AND cd.registration_end_date >= CURRENT_DATE()";
        if (isset($params['search_course']) && $params['search_course'] != '') {
            $course_condition[] = "c.course_name like '%{$params['search_course']}%'";
        }
        if (isset($params['category_id']) && count($params['category_id']) > 0) {
            $category_id = implode("','", $params['category_id']);
            $params['category_id'] = "'" . $category_id . "'";
            $course_condition[] = "c.category_id IN ({$params['category_id']})";
        }
        if (count($course_condition) > 0) {
            $condition = " WHERE " . implode(" AND ", $course_condition);
        }
        $course_detail = $this->course_model->getAllCourse($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ErrorObject'] = $course_detail;
        return $this->jsonarr;
    }

    public function homePageList($params)
    {
        $condition = "WHERE cd.display_homepage = 1";
        $home_page_list = $this->course_model->getAllCourse($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ErrorObject'] = $home_page_list;
        return $this->jsonarr;
    }

    public function courseRating($params)
    {
        $condition = "WHERE course_detail_id = {$params['course_detail_id']}";
        $course_rating = $this->course_model->courseRating($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = $course_rating;
        return $this->jsonarr;
    }

    public function locationList($params)
    {
        $entity_condition = "";
        if (isset($params['school_id']) && $params['school_id'] != '') {
            $entity_condition = "AND entity_id = {$params['school_id']} ";
        }
        $condition = "WHERE status = 'A' $entity_condition";
        $location_list = $this->course_model->locationList($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = $location_list;
        return $this->jsonarr;
    }

    public function courseLocation($params)
    {
        $condition = "WHERE cd.course_id = {$params['course_id']}";
        $get_course_location =  $this->course_model->getCourseLocation($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = count($get_course_location) > 0 && $get_course_location[0]['location_id'] != '' ? $get_course_location : [];
        return $this->jsonarr;
    }

    public function addCourseSeo($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["course_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Id should not be empty";
        } else {
            $course_seo = [];
            $condition = "WHERE s.course_id = '{$params['course_id']}'";
            $course_seo_exists = $this->course_model->getCourseSeo($condition);
            if (count($course_seo_exists) == 0) {
                $course_seo['course_id'] = $params['course_id'];
                $course_seo['meta_author'] = $params['meta_author'];
                $course_seo['meta_title'] = $params['meta_title'];
                $course_seo['meta_description'] = isset($params['meta_description']) && $params['meta_description'] != '' ? $params['meta_description'] : '';
                $course_seo['meta_keywords'] = $params['meta_keywords'];
                $course_seo['meta_keyphrase'] = isset($params['meta_keyphrase']) && $params['meta_keyphrase'] != '' ? $params['meta_keyphrase'] : '';
                $course_seo['meta_topic'] = isset($params['meta_topic']) && $params['meta_topic'] != '' ? $params['meta_topic'] : '';
                $course_seo['meta_subject'] = isset($params['meta_subject']) && $params['meta_subject'] != '' ? $params['meta_subject'] : '';
                $course_seo['meta_classification'] = isset($params['meta_classification']) && $params['meta_classification'] != '' ? $params['meta_classification'] : '';
                $course_seo['meta_robots'] = isset($params['meta_robots']) && $params['meta_robots'] != '' ? $params['meta_robots'] : '';
                $course_seo['meta_rating'] = isset($params['meta_rating']) && $params['meta_rating'] != '' ? $params['meta_rating'] : '';
                $course_seo['meta_audience'] = isset($params['meta_audience']) && $params['meta_audience'] != '' ? $params['meta_audience'] : '';
                $course_seo['og_title'] = isset($params['og_title']) && $params['og_title'] != '' ? $params['og_title'] : '';
                $course_seo['og_type'] = isset($params['og_type']) && $params['og_type'] != '' ? $params['og_type'] : '';
                $course_seo['og_site_name'] = isset($params['og_site_name']) && $params['og_site_name'] != '' ? $params['og_site_name'] : '';
                $course_seo['og_description'] = isset($params['og_description']) && $params['og_description'] != '' ? $params['og_description'] : '';
                $course_seo['og_site_url'] = isset($params['og_site_url']) && $params['og_site_url'] != '' ? $params['og_site_url'] : '';
                $course_seo['twitter_title'] = isset($params['twitter_title']) && $params['twitter_title'] != '' ? $params['twitter_title'] : '';
                $course_seo['twitter_site'] = isset($params['twitter_site']) && $params['twitter_site'] != '' ? $params['twitter_site'] : '';
                $course_seo['twitter_card'] = isset($params['twitter_card']) && $params['twitter_card'] != '' ? $params['twitter_card'] : '';
                $course_seo['twitter_description'] = isset($params['twitter_description']) && $params['twitter_description'] != '' ? $params['twitter_description'] : '';
                $course_seo['twitter_creator'] = isset($params['twitter_creator']) && $params['twitter_creator'] != '' ? $params['twitter_creator'] : '';
                $course_seo['status'] = $params['status'];
                $course_seo['created_by'] = $params['user_id'];
                $course_seo['created_date'] = date('Y-m-d H:i:s');
                $seo_id = $this->common_model->insert('tbl_course_seo', $course_seo);
                if ($seo_id > 0) {
                    $log_condition = "WHERE seo_id = $seo_id";
                    $this->common_model->insertLog('tbl_course_seo', 'tbl_course_seo_log', $log_condition);
                }
                if ($seo_id > 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Course Seo Added Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Failed to Add Course Seo.";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Seo Already Exists";
            }
        }
        return $this->jsonarr;
    }

    public function updateCourseSeo($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["course_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Id should not be empty";
        } else {
            $course_seo = [];
            $condition = "WHERE s.seo_id = '{$params['seo_id']}'";
            $course_seo_exists = $this->course_model->getCourseSeo($condition);
            if (count($course_seo_exists) > 0) {
                $course_seo['course_id'] = $params['course_id'];
                $course_seo['meta_author'] = $params['meta_author'];
                $course_seo['meta_title'] = $params['meta_title'];
                $course_seo['meta_description'] = isset($params['meta_description']) && $params['meta_description'] != '' ? $params['meta_description'] : '';
                $course_seo['meta_keywords'] = $params['meta_keywords'];
                $course_seo['meta_keyphrase'] = isset($params['meta_keyphrase']) && $params['meta_keyphrase'] != '' ? $params['meta_keyphrase'] : '';
                $course_seo['meta_topic'] = isset($params['meta_topic']) && $params['meta_topic'] != '' ? $params['meta_topic'] : '';
                $course_seo['meta_subject'] = isset($params['meta_subject']) && $params['meta_subject'] != '' ? $params['meta_subject'] : '';
                $course_seo['meta_classification'] = isset($params['meta_classification']) && $params['meta_classification'] != '' ? $params['meta_classification'] : '';
                $course_seo['meta_robots'] = isset($params['meta_robots']) && $params['meta_robots'] != '' ? $params['meta_robots'] : '';
                $course_seo['meta_rating'] = isset($params['meta_rating']) && $params['meta_rating'] != '' ? $params['meta_rating'] : '';
                $course_seo['meta_audience'] = isset($params['meta_audience']) && $params['meta_audience'] != '' ? $params['meta_audience'] : '';
                $course_seo['og_title'] = isset($params['og_title']) && $params['og_title'] != '' ? $params['og_title'] : '';
                $course_seo['og_type'] = isset($params['og_type']) && $params['og_type'] != '' ? $params['og_type'] : '';
                $course_seo['og_site_name'] = isset($params['og_site_name']) && $params['og_site_name'] != '' ? $params['og_site_name'] : '';
                $course_seo['og_description'] = isset($params['og_description']) && $params['og_description'] != '' ? $params['og_description'] : '';
                $course_seo['og_site_url'] = isset($params['og_site_url']) && $params['og_site_url'] != '' ? $params['og_site_url'] : '';
                $course_seo['twitter_title'] = isset($params['twitter_title']) && $params['twitter_title'] != '' ? $params['twitter_title'] : '';
                $course_seo['twitter_site'] = isset($params['twitter_site']) && $params['twitter_site'] != '' ? $params['twitter_site'] : '';
                $course_seo['twitter_card'] = isset($params['twitter_card']) && $params['twitter_card'] != '' ? $params['twitter_card'] : '';
                $course_seo['twitter_description'] = isset($params['twitter_description']) && $params['twitter_description'] != '' ? $params['twitter_description'] : '';
                $course_seo['twitter_creator'] = isset($params['twitter_creator']) && $params['twitter_creator'] != '' ? $params['twitter_creator'] : '';
                $course_seo['status'] = $params['status'];
                $course_seo['modified_by'] = $params['user_id'];
                $course_seo['modified_date'] = date('Y-m-d H:i:s');
                $condition = array('seo_id' => $params['seo_id']);
                $update_course = $this->common_model->update('tbl_course_seo', $course_seo, $condition);
                if ($update_course) {
                    $log_condition = "WHERE seo_id = {$params['seo_id']}";
                    $this->common_model->insertLog('tbl_course_seo', 'tbl_course_seo_log', $log_condition);
                }
                if ($update_course) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Course Seo Updated Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Failed to Update Course Seo.";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Course Seo Not Exists";
            }
        }
        return $this->jsonarr;
    }
    public function courseSeo($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["course_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Id should not be empty";
        } else {
            $condition = "WHERE s.course_id = {$params['course_id']}";
            $course_seo = $this->course_model->getCourseSeo($condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $course_seo;
        }
        return $this->jsonarr;
    }

    public function addFaq($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["title"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Title should not be empty";
        } else if ($params["answer"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Answer data for exclusive course";
        } else if ($params["course_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Course Id should not be empty";
        } else {
            $course_faq['course_id'] = $params['course_id'];
            $course_faq['title'] = $params['title'];
            $course_faq['description'] = isset($params['description']) && $params['description'] != '' ? $params['description'] : '';
            $course_faq['answer'] = $params['answer'];
            $course_faq['status'] = $params['status'];
            $course_faq['entity_id'] = $params['school_id'];
            $course_faq['created_by'] = $params['user_id'];
            $course_faq['created_date'] = date('Y-m-d H:i:s');
            $course_faq_id = $this->common_model->insert('tbl_course_faq', $course_faq);
            if ($course_faq_id > 0) {
                $log_condition = "WHERE id = $course_faq_id";
                $this->common_model->insertLog('tbl_course_faq', 'tbl_course_faq_log', $log_condition);
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Faq Updated Successfully";
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ErrorObject'] = "Faq Update Failed";
            }
        }
        return $this->jsonarr;
    }

    public function updateFaq($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else if ($params["id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Faq Id should not be empty";
        } else {
            $course_faq['title'] = $params['title'];
            $course_faq['description'] = isset($params['description']) && $params['description'] != '' ? $params['description'] : '';
            $course_faq['answer'] = $params['answer'];
            $course_faq['status'] = $params['status'];
            $course_faq['modified_by'] = $params['user_id'];
            $course_faq['modified_date'] = date('Y-m-d H:i:s');
            $condition = array('id' => $params['id']);
            $update_course = $this->common_model->update('tbl_course_faq', $course_faq, $condition);
            if ($update_course) {
                $log_condition = "WHERE id = {$params['id']}";
                $course_log = $this->common_model->insertLog('tbl_course_faq', 'tbl_course_faq_log', $log_condition);
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Faq Updated Successfully";
            } else {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ErrorObject'] = "Faq Updated Failed";
            }
        }
        return $this->jsonarr;
    }

    public function faqList($params)
    {
        $condition = "";
        if (isset($params['course_id']) && $params['course_id'] != '') {
            $condition = "WHERE course_id = {$params['course_id']}";
        }
        $course_list = $this->course_model->getCourseFaq($condition);
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr['ResponseObject'] = $course_list;
        return $this->jsonarr;
    }

    public function orderList($params)
    {
        $condition = "WHERE p.payment_status = 1 ORDER by p.payment_id DESC";
        $get_orders = $this->course_model->userOrders($condition);
        foreach ($get_orders as $key => $value) {
            $get_orders[$key]['cart_data'] = json_decode($value['cart_data'], true);
        }
        $this->jsonarr['IsSuccess'] = true;
        $this->jsonarr["ResponseObject"] = $get_orders;

        return $this->jsonarr;
    }

    public function subjectGradeList($params)
    {
        if ($params["platform"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else if ($params["role_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } else if ($params["user_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else {
            $grade_list = $this->course_model->getCourseGradeList($params);
            $subject_list = $this->course_model->getCourseSubjectList($params);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject']['subject_list'] = $subject_list;
            $this->jsonarr['ResponseObject']['grade_list'] = $grade_list;
        }
        return $this->jsonarr;
    }
}
