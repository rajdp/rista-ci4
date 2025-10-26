<?php

namespace App\Services;

use App\Models\CategoryModel;

class CategoryService
{
    protected $categoryModel;

    public function __construct()
    {
        $this->categoryModel = new CategoryModel();
    }

    /**
     * Get list of categories
     */
    public function categoryList($params)
    {
        try {
            $categories = $this->categoryModel->getAllCategories();
            
            return [
                'IsSuccess' => true,
                'ResponseObject' => $categories
            ];
        } catch (\Exception $e) {
            return [
                'IsSuccess' => false,
                'ErrorObject' => $e->getMessage()
            ];
        }
    }

    /**
     * Add new category
     */
    public function categoryAdd($params)
    {
        try {
            // Validate required fields
            if (empty($params['platform']) || empty($params['role_id']) || empty($params['user_id']) || 
                empty($params['category_name']) || empty($params['school_id'])) {
                return [
                    'IsSuccess' => false,
                    'ErrorObject' => 'Missing required fields'
                ];
            }

            $categoryData = [
                'category_name' => $params['category_name'],
                'subject_id' => $params['subject_id'] ?? null,
                'description' => $params['description'] ?? '',
                'status' => $params['status'] ?? 1,
                'path' => $params['path'] ?? '',
                'display_order' => $params['display_order'] ?? 0
            ];

            $categoryId = $this->categoryModel->addCategory($categoryData);

            if ($categoryId) {
                return [
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category added successfully',
                    'category_id' => $categoryId
                ];
            } else {
                return [
                    'IsSuccess' => false,
                    'ErrorObject' => 'Failed to add category'
                ];
            }
        } catch (\Exception $e) {
            return [
                'IsSuccess' => false,
                'ErrorObject' => $e->getMessage()
            ];
        }
    }

    /**
     * Edit category
     */
    public function categoryEdit($params)
    {
        try {
            // Validate required fields
            if (empty($params['platform']) || empty($params['role_id']) || empty($params['user_id']) || 
                empty($params['category_id']) || empty($params['category_name'])) {
                return [
                    'IsSuccess' => false,
                    'ErrorObject' => 'Missing required fields'
                ];
            }

            $categoryData = [
                'category_name' => $params['category_name'],
                'subject_id' => $params['subject_id'] ?? null,
                'description' => $params['description'] ?? '',
                'status' => $params['status'] ?? 1,
                'path' => $params['path'] ?? '',
                'display_order' => $params['display_order'] ?? 0
            ];

            $success = $this->categoryModel->updateCategory($params['category_id'], $categoryData);

            if ($success) {
                return [
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category updated successfully'
                ];
            } else {
                return [
                    'IsSuccess' => false,
                    'ErrorObject' => 'Failed to update category'
                ];
            }
        } catch (\Exception $e) {
            return [
                'IsSuccess' => false,
                'ErrorObject' => $e->getMessage()
            ];
        }
    }
} 