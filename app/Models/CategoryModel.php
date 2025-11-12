<?php

namespace App\Models;

use CodeIgniter\Model;

class CategoryModel extends Model
{
    protected $table = 'tbl_course_category';
    protected $primaryKey = 'category_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'category_name',
        'subject_id',
        'description',
        'status',
        'path',
        'display_order',
        'entity_id',
        'created_by',
        'modified_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_date';
    protected $updatedField = 'modified_date';

    protected $validationRules = [
        'category_name' => 'required|min_length[3]|max_length[255]',
        'subject_id' => 'permit_empty',
        'description' => 'permit_empty',
        'status' => 'required|in_list[A,I]',
        'path' => 'permit_empty|max_length[255]',
        'display_order' => 'permit_empty|integer',
        'entity_id' => 'required|integer'
    ];

    protected $validationMessages = [
        'category_name' => [
            'required' => 'Category name is required',
            'min_length' => 'Category name must be at least 3 characters long',
            'max_length' => 'Category name cannot exceed 255 characters'
        ],
        'status' => [
            'required' => 'Status is required',
            'in_list' => 'Status must be either 0 or 1'
        ]
    ];

    /**
     * Add a new category
     */
    public function addCategory($data)
    {
        return $this->insert($data);
    }

    /**
     * Update an existing category
     */
    public function updateCategory($id, $data)
    {
        return $this->update($id, $data);
    }

    /**
     * Get all categories
     */
    public function getAllCategories()
    {
        return $this->findAll();
    }

    /**
     * Get category by ID
     */
    public function getCategoryById($id)
    {
        return $this->find($id);
    }

    /**
     * Delete a category
     */
    public function deleteCategory($id)
    {
        return $this->delete($id);
    }
} 