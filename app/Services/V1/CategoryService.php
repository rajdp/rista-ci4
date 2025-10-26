<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

class CategoryService
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $CI;

    function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('v1/category_model');
        $this->CI->load->model('v1/common_model');
        $this->category_model = $this->CI->category_model;
        $this->common_model = $this->CI->common_model;

    }

    public function categoryList($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } else {
            $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.category_id DESC";
            if (isset($params['type']) && strtolower($params['type']) == 'active') {
                $condition = "WHERE c.status = 'A' AND c.entity_id = {$params['school_id']} ORDER BY c.category_id DESC";
            }
            $category_list = $this->category_model->categoryList($condition);
            foreach ($category_list as $key => $value) {
                $category_list[$key]['subject_id'] = isset($value['subject_id']) && $value['subject_id'] != '' ? explode(',', $value['subject_id']) : [];
            }
            
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $category_list;
        }
        return $this->jsonarr;
    }
    public function categoryAdd($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } elseif ($params['category_name'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Category Name should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else {
            $formDataArray = [];
            $params['category_name'] = trim($params['category_name']);
            $condition = "WHERE c.category_name = '{$params['category_name']}' AND c.entity_id = {$params['school_id']}";
            $checkCategoryExists = $this->category_model->categoryList($condition);
            if (count($checkCategoryExists) == 0) {
                $formDataArray = array(
                    'category_name' => $params['category_name'],
                    'description' => $params['description'],
                    'subject_id' => isset($params['subject_id']) && count($params['subject_id']) > 0 ? implode(',', $params['subject_id']) : NULL,
                    'entity_id' => $params['school_id'],
                    'status' => $params['status'],
                    'path' => $params['path'],
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s')
                );
                $insertCategoryId = $this->common_model->insert('tbl_course_category', $formDataArray);
                if ($insertCategoryId != 0) {
                    $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.display_order DESC";
                    $get_category = $this->category_model->categoryList($condition);
                    if (isset($params['display_order']) && $params['display_order'] != '') {
                        if (count($get_category) > 0 && $get_category[0]['display_order'] > $params['display_order']) {
                            foreach ($get_category as $key => $value) {
                                if ($value['display_order'] >= $params['display_order']) {
                                    $condition = array('category_id' => $value['category_id']);
                                    $orderNo = array('display_order' => $value['display_order'] + 1);
                                    $this->common_model->update('tbl_course_category', $orderNo, $condition);
                                }
                            }
                        }
                        $condition = array('category_id' => $insertCategoryId);
                        $data = array('display_order' => $params['display_order']);
                        $this->common_model->update('tbl_course_category', $data, $condition);
                    }
                    $log_condition = "WHERE category_id = $insertCategoryId";
                    $this->common_model->insertLog('tbl_course_category', 'tbl_course_category_log', $log_condition);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = 'Category added successfully';
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = 'Failed to add Category';
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = 'Category Name Already Exists';
            }
        }
        return $this->jsonarr;
    }

    public function categoryEdit($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id should not be empty";
        } elseif ($params['category_name'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Category Name should not be empty";
        } elseif ($params['category_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Category Id should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else {
            $formDataArray = [];
            $params['category_name'] = trim($params['category_name']);
            $condition = "WHERE c.category_name = '{$params['category_name']}' AND c.category_id != {$params['category_id']} AND c.entity_id = {$params['school_id']}";
            $checkCategoryExists = $this->category_model->categoryList($condition);
            if (count($checkCategoryExists) == 0) {
                $formDataArray = array(
                    'category_name' => $params['category_name'],
                    'description' => $params['description'],
                    'subject_id' => isset($params['subject_id']) && count($params['subject_id']) > 0 ? implode(',', $params['subject_id']) : NULL,
                    'status' => $params['status'],
                    'path' => $params['path'],
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $updateCondition = array('category_id' => $params['category_id']);
                $condition = "WHERE c.entity_id = {$params['school_id']} ORDER BY c.display_order DESC";
                $get_category = $this->category_model->categoryList($condition);
                $condition = "WHERE c.category_id = '{$params['category_id']}'";
                $check_exists = $this->category_model->categoryList($condition);
                foreach ($get_category as $key => $value) {
                    if ($value['display_order'] > $check_exists[0]['display_order'] && $value['display_order'] <= $params['display_order']) {
                        $order_condition = array('category_id' => $value['category_id']);
                        $orderNo = array('display_order' => $value['display_order'] - 1);
                        $this->common_model->update('tbl_course_category', $orderNo, $order_condition);
                    } elseif ($value['display_order'] >= $params['display_order'] && $value['display_order'] < $check_exists[0]['display_order']) {
                        $order_condition = array('category_id' => $value['category_id']);
                        $orderNo = array('display_order' => $value['display_order'] + 1);
                        $this->common_model->update('tbl_course_category', $orderNo, $order_condition);
                    }
                }
                $formDataArray['display_order'] = $params['display_order'];
                $updateCategory = $this->common_model->update('tbl_course_category', $formDataArray, $updateCondition);
                if ($updateCategory) {
                    $log_condition = "WHERE category_id = {$params['category_id']}";
                    $this->common_model->insertLog('tbl_course_category', 'tbl_course_category_log', $log_condition);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = 'Category updated successfully';
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = 'Failed to update Category';
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = 'Category Name Already Exists';
            }
        }
        return $this->jsonarr;
    }
    
}
