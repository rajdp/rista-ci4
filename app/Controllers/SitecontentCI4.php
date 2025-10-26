<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\HTTP\ResponseInterface;

class SitecontentCI4 extends ResourceController
{
    protected $format = 'json';
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Get category list for site content
     */
    public function categoryList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $schoolId = $params['school_id'] ?? 0;
            $roleId = $params['role_id'] ?? 0;
            
            $builder = $this->db->table('tbl_content_category bc');
            $builder->select('bc.category_id, bc.category_name, bc.status, COALESCE(bc.description,"") as description, 
                             bc.path, bc.display_order,
                             (SELECT COUNT(content_id) FROM tbl_content WHERE category_id = bc.category_id) as content_count');
            
            $builder->where('bc.entity_id', $schoolId);
            
            if (isset($params['type']) && strtolower($params['type']) == 'active') {
                $builder->where('bc.status', 'A');
            }
            
            $builder->orderBy('bc.category_id', 'DESC');
            
            $categories = $builder->get()->getResultArray();

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $categories,
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
     * Get content list for site
     */
    public function listContent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $schoolId = $params['school_id'] ?? 0;
            $categoryId = $params['category_id'] ?? 0;
            $roleId = $params['role_id'] ?? 0;
            
            $builder = $this->db->table('tbl_content b');
            $builder->select('b.content_id, b.name, b.name_slug, b.image, b.short_description, b.long_description, 
                             b.status, b.category_id, b.subject_id, b.views, b.author, b.display_order, b.created_date,
                             b.redirect_url, b.location, b.timing, b.event_date as eventDate,
                             (CASE WHEN b.display_from = "0000-00-00 00:00:00" THEN "" ELSE b.display_from END) as display_from,
                             (CASE WHEN b.display_until = "0000-00-00 00:00:00" THEN "" ELSE b.display_until END) as display_until,
                             (SELECT category_name FROM tbl_content_category WHERE category_id = b.category_id) as category_name');
            
            $builder->where('b.entity_id', $schoolId);
            
            if ($categoryId > 0) {
                $builder->where('b.category_id', $categoryId);
            }
            
            if (isset($params['type']) && strtolower($params['type']) == 'active') {
                $builder->where('b.status', 'A');
            }
            
            $builder->orderBy('b.content_id', 'DESC');
            
            $content = $builder->get()->getResultArray();
            
            // Add uri_name field
            foreach ($content as $key => $value) {
                $content[$key]['uri_name'] = str_replace(' ', '-', $value['name']);
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $content,
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
     * Get SEO list for content
     */
    public function seoList(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $contentId = $params['content_id'] ?? 0;
            
            $builder = $this->db->table('tbl_content_seo');
            $builder->select('*');
            
            if ($contentId > 0) {
                $builder->where('content_id', $contentId);
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
     * Add new category
     */
    public function categoryAdd(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $data = [
                'category_name' => $params['category_name'] ?? '',
                'description' => $params['description'] ?? '',
                'entity_id' => $params['school_id'] ?? 0,
                'path' => $params['path'] ?? '',
                'status' => $params['status'] ?? 'A',
                'display_order' => $params['display_order'] ?? 0,
                'created_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('tbl_content_category');
            $result = $builder->insert($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add category'
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
     * Edit category
     */
    public function categoryEdit(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $categoryId = $params['category_id'] ?? 0;
            $data = [
                'category_name' => $params['category_name'] ?? '',
                'description' => $params['description'] ?? '',
                'path' => $params['path'] ?? '',
                'status' => $params['status'] ?? 'A',
                'display_order' => $params['display_order'] ?? 0,
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('tbl_content_category');
            $builder->where('category_id', $categoryId);
            $result = $builder->update($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update category'
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
     * Add new content
     */
    public function addContent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $data = [
                'title' => $params['title'] ?? '',
                'short_description' => $params['short_description'] ?? '',
                'description' => $params['description'] ?? '',
                'category_id' => $params['category_id'] ?? 0,
                'school_id' => $params['school_id'] ?? 0,
                'path' => $params['path'] ?? '',
                'status' => $params['status'] ?? 'A',
                'created_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('tbl_content');
            $result = $builder->insert($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content Added Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to add content'
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
     * Edit content
     */
    public function editContent(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $contentId = $params['content_id'] ?? 0;
            $data = [
                'title' => $params['title'] ?? '',
                'short_description' => $params['short_description'] ?? '',
                'description' => $params['description'] ?? '',
                'category_id' => $params['category_id'] ?? 0,
                'path' => $params['path'] ?? '',
                'status' => $params['status'] ?? 'A',
                'modified_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('tbl_content');
            $builder->where('content_id', $contentId);
            $result = $builder->update($data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Content Updated Successfully',
                    'ErrorObject' => ''
                ]);
            } else {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Failed to update content'
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
     * Add SEO for content
     */
    public function addSeo(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $data = [
                'content_id' => $params['content_id'] ?? 0,
                'meta_title' => $params['meta_title'] ?? '',
                'meta_description' => $params['meta_description'] ?? '',
                'meta_keywords' => $params['meta_keywords'] ?? '',
                'created_date' => date('Y-m-d H:i:s')
            ];

            $builder = $this->db->table('tbl_content_seo');
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
     * Edit SEO for content
     */
    public function editSeo(): ResponseInterface
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

            $builder = $this->db->table('tbl_content_seo');
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
}

