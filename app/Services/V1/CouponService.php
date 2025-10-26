<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

class CouponService
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $CI;

    function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('v1/coupon_model');
        $this->CI->load->model('v1/common_model');
        $this->coupon_model = $this->CI->coupon_model;
        $this->common_model = $this->CI->common_model;
    }

    public function couponList($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "User Id should not be empty";
        } else {
            $condition = "WHERE entity_id = {$params['school_id']}";
            $coupon_list = $this->coupon_model->couponList($condition);
            foreach ($coupon_list as $key => $value) {
                $coupon_list[$key]['course_id'] = $value['course_id'] != '' ? explode(',', $value['course_id']) : [];
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $coupon_list;
        }
        return $this->jsonarr;
    }

    public function couponAdd($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "User Id should not be empty";
        } elseif ($params['coupon_code'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Coupon Code should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Status should not be empty";
        } else {
            $coupon = [];
            $params['coupon_code'] = trim($params['coupon_code']);
            $condition = "WHERE coupon_code = '{$params['coupon_code']}'";
            $checkCouponExists = $this->coupon_model->couponList($condition);
            if (count($checkCouponExists) == 0) {
                $coupon = array(
                    'coupon_code' => $params['coupon_code'],
                    'entity_id' => $params['school_id'],
                    'validity_from' => $params['validity_from'],
                    'validity_to' => $params['validity_to'],
                    'discount_type' => $params['discount_type'],
                    'discount' => $params['discount'],
                    'course_based' => $params['course_based_on'],
                    'course_id' => isset($params['course_id']) && $params['course_id'] != '' ? implode(',', $params['course_id']) : 0,
                    'no_of_users' => $params['no_of_users'],
                    'status' => $params['status'],
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s')
                );
                $couponId = $this->common_model->insert('tbl_coupon', $coupon);
                if ($couponId != 0) {
                    $log_condition = "WHERE coupon_id = $couponId";
                    $this->common_model->insertLog('tbl_coupon', 'tbl_coupon_log', $log_condition);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = 'Coupon added successfully';
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = 'Failed to add Coupon';
                }
            }
        }
        return $this->jsonarr;
    }

    public function couponEdit($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "User Id should not be empty";
        } elseif ($params['coupon_code'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Coupon Code should not be empty";
        } elseif ($params['coupon_id'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Coupon Id should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr["success"] = false;
            $this->jsonarr["response"] = "Status should not be empty";
        } else {
            $coupon = [];
            $params['coupon_code'] = trim($params['coupon_code']);
            $condition = "WHERE coupon_code = '{$params['coupon_code']}' AND coupon_id != {$params['coupon_id']}";
            $checkCouponExists = $this->coupon_model->couponList($condition);
            if (count($checkCouponExists) == 0) {
                $coupon = array(
                    'coupon_code' => $params['coupon_code'],
                    'entity_id' => $params['school_id'],
                    'validity_from' => $params['validity_from'],
                    'validity_to' => $params['validity_to'],
                    'discount_type' => $params['discount_type'],
                    'discount' => $params['discount'],
                    'course_based' => $params['course_based_on'],
                    'course_id' => isset($params['course_id']) && $params['course_id'] != '' ? implode(',', $params['course_id']) : 0,
                    'no_of_users' => $params['no_of_users'],
                    'status' => $params['status'],
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $updateCondition = array('coupon_id' => $params['coupon_id']);
                $updateCoupon = $this->common_model->update('tbl_coupon', $coupon, $updateCondition);
                if ($updateCoupon) {
                    $log_condition = "WHERE coupon_id = {$params['coupon_id']}";
                    $this->common_model->insertLog('tbl_coupon', 'tbl_coupon_log', $log_condition);
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = 'Coupon updated successfully';
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = 'Failed to update Coupon';
                }
            }
        }
        return $this->jsonarr;
    }
}
