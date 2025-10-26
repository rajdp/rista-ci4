<?php
defined('BASEPATH') or exit('No direct script access allowed');
header('Access-Control-Allow-Origin: *');
header("Access-Control-Allow-Headers: AccessToken");
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');

class SiteContentService
{
    protected $jsonarr = array();
    protected $headers;
    protected $urlAuth;
    protected $controller;
    protected $CI;

    function __construct()
    {
        $this->CI = &get_instance();

        $this->CI->load->model('v1/sitecontent_model');
        $this->CI->load->model('v1/common_model');
        $this->sitecontent_model = $this->CI->sitecontent_model;
        $this->common_model = $this->CI->common_model;

    }

    public function addBlogger($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role Id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User Id Should not be empty";
        } elseif ($params['name'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Name should not be empty";
        } elseif ($params['long_description'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Long description should not be empty";
        } elseif ($params['status'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Status should not be empty";
        } else {
            $remove_special_char = preg_replace('/[^a-zA-Z0-9_ -]/s', '', $params['name']);
            $remove_special_char  = preg_replace('/\s+/', ' ', $remove_special_char);
            $params['name_slug'] = str_replace(' ', '-', $remove_special_char);

           // $name = $this->db->escape($params['name']);
            $blogger_condition = "WHERE name_slug = '{$params['name_slug']}'";
            $check_exists = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
            if (count($check_exists) > 0) {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Blog name already exists";
                return $this->jsonarr;
                exit;
            }

            $blogger = array(
                'name' => $params['name'],
                'name_slug' => $params['name_slug'],
                'entity_id' => $params['school_id'],
                'short_description' => isset($params['short_description']) ? $params['short_description'] : '',
                'image' => $params['image'],
                'long_description' => $params['long_description'],
                'status' => $params['status'],
                'display_from' => isset($params['display_from']) ? $params['display_from'] : '',
                'display_until' => isset($params['display_until']) ? $params['display_until'] : '',
                'redirect_url' => isset($params['redirect_url']) ? $params['redirect_url'] : '',
                'category_id' => implode(',', $params['category_id']),
                'subject_id' => isset($params['subject_id']) && count($params['subject_id']) > 0 ? implode(',', $params['subject_id']) : NULL,
                'author' => $params['author'],
                'location' => $params['location'],
                'timing' => $params['timing'],
                'event_date' => $params['eventDate'],
                'created_by' => $params['user_id'],
                'created_date' => date('Y-m-d')
            );
            $insert = $this->common_model->insert('tbl_content', $blogger);
            if ($insert) {
                $category_id = implode(',', $params['category_id']);
                $blogger_condition = "WHERE category_id IN ({$category_id}) ORDER BY b.display_order DESC";
                $get_content = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
                if (isset($params['display_order']) && $params['display_order'] != '') {
                    if (count($get_content) > 0 && $get_content[0]['display_order'] > $params['display_order']) {
                        foreach ($get_content as $key => $value) {
                            if ($value['display_order'] >= $params['display_order']) {
                                $condition = array('content_id' => $value['content_id']);
                                $orderNo = array('display_order' => $value['display_order'] + 1);
                                $this->common_model->update('tbl_content', $orderNo, $condition);
                            }
                        }
                    }
                    $condition = array('content_id' => $insert);
                    $data = array('display_order' => $params['display_order']);
                    $this->common_model->update('tbl_content', $data, $condition);
                }

                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ResponseObject'] = "Blog added successfully";
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Unable to add blog";
            }
        }
        return $this->jsonarr;
    }

    public function updateBlogger($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Blog id should not be empty";
        } else {
            $remove_special_char = preg_replace('/[^a-zA-Z0-9_ -]/s', '', $params['name']);
            $remove_special_char  = preg_replace('/\s+/', ' ', $remove_special_char);
            $params['name_slug'] = str_replace(' ', '-', $remove_special_char);

           // $name = $this->db->escape($params['name']);
            $blogger_condition = "WHERE name_slug = '{$params['name_slug']}' AND content_id != '{$params['content_id']}'";
            $check_exists = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
            if (count($check_exists) > 0) {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Blog name already exists";
                return $this->jsonarr;
                exit;
            }
            $blogger_condition = "WHERE content_id = '{$params['content_id']}'";
            $check_exists = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
            if (count($check_exists) > 0) {
                $blogger = array(
                    'name' => $params['name'],
                    'name_slug' => $params['name_slug'],
                    'short_description' => isset($params['short_description']) ? $params['short_description'] : '',
                    'image' => $params['image'],
                    'long_description' => $params['long_description'],
                    'status' => $params['status'],
                    'display_from' => isset($params['display_from']) ? $params['display_from'] : '',
                    'display_until' => isset($params['display_until']) ? $params['display_until'] : '',
                    'redirect_url' => isset($params['redirect_url']) ? $params['redirect_url'] : '',
                    'category_id' => implode(',', $params['category_id']),
                    'subject_id' => isset($params['subject_id']) && count($params['subject_id']) > 0 ? implode(',', $params['subject_id']) : NULL,
                    'author' => $params['author'],
                    'location' => $params['location'],
                    'timing' => $params['timing'],
                    'event_date' => $params['eventDate'],
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d')
                );
                $category_id = implode(',', $params['category_id']);
                $blogger_condition = "WHERE category_id IN ({$category_id}) ORDER BY b.display_order DESC";
                $get_content = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
                foreach ($get_content as $key => $value) {
                    if ($value['display_order'] > $check_exists[0]['display_order'] && $value['display_order'] <= $params['display_order']) {
                        $order_condition = array('content_id' => $value['content_id']);
                        $orderNo = array('display_order' => $value['display_order'] - 1);
                        $this->common_model->update('tbl_content', $orderNo, $order_condition);
                    } elseif ($value['display_order'] >= $params['display_order'] && $value['display_order'] < $check_exists[0]['display_order']) {
                        $order_condition = array('content_id' => $value['content_id']);
                        $orderNo = array('display_order' => $value['display_order'] + 1);
                        $this->common_model->update('tbl_content', $orderNo, $order_condition);
                    }
                }
                $blogger['display_order'] = $params['display_order'];
                $condition = array('content_id' => $params['content_id']);
                $update = $this->common_model->update('tbl_content', $blogger, $condition);
                if ($update) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Blog updated successfully";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Unable to update your blog";
            }
        }
        return $this->jsonarr;
    }

    public function bloggerList($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } else {
            // $condition = '';
            // $search = '';
            // if (
            //     isset($params['role_id']) && $params['role_id'] == 1 &&
            //     isset($params['search']) && $params['search'] != ''
            // ) {
            //     $search = "WHERE (name like '%{$params['search']}%' OR short_description like '%{$params['search']}%' OR long_description like '%{$params['search']}%')";
            // } elseif (isset($params['search']) && $params['search'] != '') {
            //     $search = "AND (name like '%{$params['search']}%' OR short_description like '%{$params['search']}%' OR long_description like '%{$params['search']}%')";
            // }
            // if (isset($params['role_id']) && $params['role_id'] == 1) {
            //     $condition = "";
            // } else {
            //     $condition = "WHERE status = 'A' AND (display_from <= NOW() OR display_from = '0000-00-00 00:00:00' OR display_from = DATE_FORMAT(NOW(),'%Y-%m-%d') OR DATE_FORMAT(display_from,'%h:%m:%s') = '00:00:00')
            //               AND (display_until >= NOW() OR display_until = DATE_FORMAT(NOW(),'%Y-%m-%d') OR display_until = '0000-00-00 00:00:00' OR DATE_FORMAT(display_until,'%h:%m:%s') = '00:00:00')";
            // }

            if ($params['role_id'] != 3) {
                $condition = "WHERE entity_id = {$params['school_id']}";
                if (isset($params['type']) && strtolower($params['type']) == 'active') {
                    $condition = "WHERE status = 'A' AND entity_id = {$params['school_id']}";
                }
            } else {
                $params['content_creator_id'] = $params['user_id'];
                $user_detail = $this->user_service->contentCreatorList($params);
                $permission = implode(',', $user_detail['ErrorObject'][0]['permission']);
                $condition = "WHERE entity_id = {$params['school_id']} AND category_id IN ($permission)";
            }
            $condition = $condition . " ORDER BY b.content_id DESC";
            $blogger_list = $this->sitecontent_model->bloggerDetails($condition);
            foreach ($blogger_list as $key => $value) {
                $blogger_list[$key]['uri_name'] = str_replace(' ', '-', $value['name']);
            }
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $blogger_list;
        }
        return $this->jsonarr;
    }


    public function viewCount($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform Should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Blog id should not be empty";
        } else {
            $blogger_condition = "WHERE content_id = '{$params['content_id']}'";
            $get_view_count = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
            if ($get_view_count) {
                $params['views'] = $get_view_count[0]['views'] + 1;
            } else {
                $params['views'] = 1;
            }
            $blogger = array('views' => $params['views']);
            $condition = array('content_id' => $params['content_id']);
            $update = $this->common_model->update('tbl_content', $blogger, $condition);
            if ($update) {
                $this->jsonarr['IsSuccess'] = true;
                $this->jsonarr['ErrorObject'] = "Views updated successfully";
                $this->jsonarr["views"] = $params['views'];
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Unable to update the count";
            }
        }
        return $this->jsonarr;
    }


    public function deleteBlogger($params)
    {
        if ($params['platform'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Platform should not be empty";
        } elseif ($params['role_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Role id should not be empty";
        } elseif ($params['user_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "User id should not be empty";
        } elseif ($params['content_id'] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "Blog id should not be empty";
        } else {
            $blogger_condition = "WHERE content_id = '{$params['content_id']}'";
            $check_exists = $this->sitecontent_model->bloggerDetails($blogger_condition, '');
            if (count($check_exists) > 0) {
                $blogger = array(
                    'status' => 'I'
                );
                $condition = array('content_id' => $params['content_id']);
                $update = $this->common_model->update('tbl_content', $blogger, $condition);
                if ($update) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ErrorObject'] = "Blog deleted successfully";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Unable to deleted your blog";
            }
        }
        return $this->jsonarr;
    }

    public function addBlogSeo($params)
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
        } else if ($params["content_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "blog Id should not be empty";
        } else {
            $blog_seo = [];
            $condition = "WHERE s.content_id = '{$params['content_id']}'";
            $blog_seo_exists = $this->sitecontent_model->getBlogSeo($condition);
            if (count($blog_seo_exists) == 0) {
                $blog_seo['content_id'] = $params['content_id'];
                $blog_seo['meta_author'] = $params['meta_author'];
                $blog_seo['meta_title'] = $params['meta_title'];
                $blog_seo['meta_description'] = isset($params['meta_description']) && $params['meta_description'] != '' ? $params['meta_description'] : '';
                $blog_seo['meta_keywords'] = $params['meta_keywords'];
                $blog_seo['meta_keyphrase'] = isset($params['meta_keyphrase']) && $params['meta_keyphrase'] != '' ? $params['meta_keyphrase'] : '';
                $blog_seo['meta_topic'] = isset($params['meta_topic']) && $params['meta_topic'] != '' ? $params['meta_topic'] : '';
                $blog_seo['meta_subject'] = isset($params['meta_subject']) && $params['meta_subject'] != '' ? $params['meta_subject'] : '';
                $blog_seo['meta_classification'] = isset($params['meta_classification']) && $params['meta_classification'] != '' ? $params['meta_classification'] : '';
                $blog_seo['meta_robots'] = isset($params['meta_robots']) && $params['meta_robots'] != '' ? $params['meta_robots'] : '';
                $blog_seo['meta_rating'] = isset($params['meta_rating']) && $params['meta_rating'] != '' ? $params['meta_rating'] : '';
                $blog_seo['meta_audience'] = isset($params['meta_audience']) && $params['meta_audience'] != '' ? $params['meta_audience'] : '';
                $blog_seo['og_title'] = isset($params['og_title']) && $params['og_title'] != '' ? $params['og_title'] : '';
                $blog_seo['og_type'] = isset($params['og_type']) && $params['og_type'] != '' ? $params['og_type'] : '';
                $blog_seo['og_site_name'] = isset($params['og_site_name']) && $params['og_site_name'] != '' ? $params['og_site_name'] : '';
                $blog_seo['og_description'] = isset($params['og_description']) && $params['og_description'] != '' ? $params['og_description'] : '';
                $blog_seo['og_site_url'] = isset($params['og_site_url']) && $params['og_site_url'] != '' ? $params['og_site_url'] : '';
                $blog_seo['twitter_title'] = isset($params['twitter_title']) && $params['twitter_title'] != '' ? $params['twitter_title'] : '';
                $blog_seo['twitter_site'] = isset($params['twitter_site']) && $params['twitter_site'] != '' ? $params['twitter_site'] : '';
                $blog_seo['twitter_card'] = isset($params['twitter_card']) && $params['twitter_card'] != '' ? $params['twitter_card'] : '';
                $blog_seo['twitter_description'] = isset($params['twitter_description']) && $params['twitter_description'] != '' ? $params['twitter_description'] : '';
                $blog_seo['twitter_creator'] = isset($params['twitter_creator']) && $params['twitter_creator'] != '' ? $params['twitter_creator'] : '';
                $blog_seo['status'] = $params['status'];
                $blog_seo['created_by'] = $params['user_id'];
                $blog_seo['created_date'] = date('Y-m-d H:i:s');
                $seo_id = $this->common_model->insert('tbl_content_seo', $blog_seo);
                if ($seo_id > 0) {
                    $log_condition = "WHERE seo_id = $seo_id";
                    $this->common_model->insertLog('tbl_content_seo', 'tbl_content_seo_log', $log_condition);
                }
                if ($seo_id > 0) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "blog Seo Added Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Failed to Add blog Seo.";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Seo Already Exists";
            }
        }
        return $this->jsonarr;
    }

    public function updateBlogSeo($params)
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
        } else if ($params["content_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "blog Id should not be empty";
        } else {
            $blog_seo = [];
            $condition = "WHERE s.seo_id = '{$params['seo_id']}'";
            $blog_seo_exists = $this->sitecontent_model->getBlogSeo($condition);
            if (count($blog_seo_exists) > 0) {
                $blog_seo['content_id'] = $params['content_id'];
                $blog_seo['meta_author'] = $params['meta_author'];
                $blog_seo['meta_title'] = $params['meta_title'];
                $blog_seo['meta_description'] = isset($params['meta_description']) && $params['meta_description'] != '' ? $params['meta_description'] : '';
                $blog_seo['meta_keywords'] = $params['meta_keywords'];
                $blog_seo['meta_keyphrase'] = isset($params['meta_keyphrase']) && $params['meta_keyphrase'] != '' ? $params['meta_keyphrase'] : '';
                $blog_seo['meta_topic'] = isset($params['meta_topic']) && $params['meta_topic'] != '' ? $params['meta_topic'] : '';
                $blog_seo['meta_subject'] = isset($params['meta_subject']) && $params['meta_subject'] != '' ? $params['meta_subject'] : '';
                $blog_seo['meta_classification'] = isset($params['meta_classification']) && $params['meta_classification'] != '' ? $params['meta_classification'] : '';
                $blog_seo['meta_robots'] = isset($params['meta_robots']) && $params['meta_robots'] != '' ? $params['meta_robots'] : '';
                $blog_seo['meta_rating'] = isset($params['meta_rating']) && $params['meta_rating'] != '' ? $params['meta_rating'] : '';
                $blog_seo['meta_audience'] = isset($params['meta_audience']) && $params['meta_audience'] != '' ? $params['meta_audience'] : '';
                $blog_seo['og_title'] = isset($params['og_title']) && $params['og_title'] != '' ? $params['og_title'] : '';
                $blog_seo['og_type'] = isset($params['og_type']) && $params['og_type'] != '' ? $params['og_type'] : '';
                $blog_seo['og_site_name'] = isset($params['og_site_name']) && $params['og_site_name'] != '' ? $params['og_site_name'] : '';
                $blog_seo['og_description'] = isset($params['og_description']) && $params['og_description'] != '' ? $params['og_description'] : '';
                $blog_seo['og_site_url'] = isset($params['og_site_url']) && $params['og_site_url'] != '' ? $params['og_site_url'] : '';
                $blog_seo['twitter_title'] = isset($params['twitter_title']) && $params['twitter_title'] != '' ? $params['twitter_title'] : '';
                $blog_seo['twitter_site'] = isset($params['twitter_site']) && $params['twitter_site'] != '' ? $params['twitter_site'] : '';
                $blog_seo['twitter_card'] = isset($params['twitter_card']) && $params['twitter_card'] != '' ? $params['twitter_card'] : '';
                $blog_seo['twitter_description'] = isset($params['twitter_description']) && $params['twitter_description'] != '' ? $params['twitter_description'] : '';
                $blog_seo['twitter_creator'] = isset($params['twitter_creator']) && $params['twitter_creator'] != '' ? $params['twitter_creator'] : '';
                $blog_seo['status'] = $params['status'];
                $blog_seo['modified_by'] = $params['user_id'];
                $blog_seo['modified_date'] = date('Y-m-d H:i:s');
                $condition = array('seo_id' => $params['seo_id']);
                $update_blog = $this->common_model->update('tbl_content_seo', $blog_seo, $condition);
                if ($update_blog) {
                    $log_condition = "WHERE seo_id = {$params['seo_id']}";
                    $this->common_model->insertLog('tbl_content_seo', 'tbl_content_seo_log', $log_condition);
                }
                if ($update_blog) {
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = "Blog Seo Updated Successfully";
                } else {
                    $this->jsonarr['IsSuccess'] = false;
                    $this->jsonarr['ErrorObject'] = "Failed to Update blog Seo.";
                }
            } else {
                $this->jsonarr['IsSuccess'] = false;
                $this->jsonarr['ErrorObject'] = "Blog Seo Not Exists";
            }
        }
        return $this->jsonarr;
    }
    public function blogSeo($params)
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
        } else if ($params["content_id"] == "") {
            $this->jsonarr['IsSuccess'] = false;
            $this->jsonarr['ErrorObject'] = "blog Id should not be empty";
        } else {
            $seo = "";
            if (isset($params['seo_id']) && $params['seo_id'] > 0) {
                $seo = "AND s.seo_id = {$params['seo_id']}";
            }
            $condition = "WHERE s.content_id = {$params['content_id']} AND s.entity_id = {$params['school_id']} $seo";
            $blog_seo = $this->sitecontent_model->getBlogSeo($condition);
            $this->jsonarr['IsSuccess'] = true;
            $this->jsonarr['ResponseObject'] = $blog_seo;
        }
        return $this->jsonarr;
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
            if ($params['role_id'] != 3) {
                $condition = "WHERE bc.entity_id = {$params['school_id']}";
                if (isset($params['type']) && strtolower($params['type']) == 'active') {
                    $condition = "WHERE bc.status = 'A' AND bc.entity_id = {$params['school_id']}";
                }
            } else {
                $params['content_creator_id'] = $params['user_id'];
                $user_detail = $this->user_service->contentCreatorList($params);
                $permission = implode(',', $user_detail['ErrorObject'][0]['permission']);
                $condition = "WHERE bc.entity_id = {$params['school_id']} AND bc.category_id IN ($permission)";
            }
            $condition = $condition . " ORDER BY bc.category_id DESC";
            $category_list = $this->sitecontent_model->categoryList($condition);
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
            $condition = "WHERE bc.category_name = '{$params['category_name']}' AND entity_id = {$params['school_id']}";
            $checkCategoryExists = $this->sitecontent_model->categoryList($condition);
            if (count($checkCategoryExists) == 0) {
                $formDataArray = array(
                    'category_name' => $params['category_name'],
                    'description' => $params['description'],
                    'entity_id' => $params['school_id'],
                    'status' => $params['status'],
                    'path' => isset($params['path']) && $params['path'] != '' ? $params['path'] : '',
                    'created_by' => $params['user_id'],
                    'created_date' => date('Y-m-d H:i:s')
                );
                $insertCategoryId = $this->common_model->insert('tbl_content_category', $formDataArray);
                if ($insertCategoryId != 0) {
                    $condition = "WHERE bc.entity_id = {$params['school_id']} ORDER BY bc.display_order DESC";
                    $get_category = $this->sitecontent_model->categoryList($condition);
                    if (isset($params['display_order']) && $params['display_order'] != '') {
                        if (count($get_category) > 0 && $get_category[0]['display_order'] > $params['display_order']) {
                            foreach ($get_category as $key => $value) {
                                if ($value['display_order'] >= $params['display_order']) {
                                    $condition = array('category_id' => $value['category_id']);
                                    $orderNo = array('display_order' => $value['display_order'] + 1);
                                    $this->common_model->update('tbl_content_category', $orderNo, $condition);
                                }
                            }
                        }
                        $condition = array('category_id' => $insertCategoryId);
                        $data = array('display_order' => $params['display_order']);
                        $this->common_model->update('tbl_content_category', $data, $condition);
                    }
                    $this->jsonarr['IsSuccess'] = true;
                    $this->jsonarr['ResponseObject'] = 'Category Added successfully';
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
            $condition = "WHERE bc.category_name = '{$params['category_name']}' AND bc.category_id != {$params['category_id']} AND bc.entity_id = {$params['school_id']}";
            $checkCategoryExists = $this->sitecontent_model->categoryList($condition);
            if (count($checkCategoryExists) == 0) {
                $formDataArray = array(
                    'category_name' => $params['category_name'],
                    'description' => $params['description'],
                    'status' => $params['status'],
                    'path' => isset($params['path']) && $params['path'] != '' ? $params['path'] : '',
                    'modified_by' => $params['user_id'],
                    'modified_date' => date('Y-m-d H:i:s')
                );
                $updateCondition = array('category_id' => $params['category_id']);
                $condition = "WHERE bc.entity_id = {$params['school_id']} ORDER BY bc.display_order DESC";
                $get_category = $this->sitecontent_model->categoryList($condition);
                $condition = "WHERE bc.category_id = '{$params['category_id']}'";
                $check_exists = $this->sitecontent_model->categoryList($condition);
                foreach ($get_category as $key => $value) {
                    if ($value['display_order'] > $check_exists[0]['display_order'] && $value['display_order'] <= $params['display_order']) {
                        $order_condition = array('category_id' => $value['category_id']);
                        $orderNo = array('display_order' => $value['display_order'] - 1);
                        $this->common_model->update('tbl_content_category', $orderNo, $order_condition);
                    } elseif ($value['display_order'] >= $params['display_order'] && $value['display_order'] < $check_exists[0]['display_order']) {
                        $order_condition = array('category_id' => $value['category_id']);
                        $orderNo = array('display_order' => $value['display_order'] + 1);
                        $this->common_model->update('tbl_content_category', $orderNo, $order_condition);
                    }
                }
                $formDataArray['display_order'] = $params['display_order'];
                $updateCategory = $this->common_model->update('tbl_content_category', $formDataArray, $updateCondition);
                if ($updateCategory) {
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
