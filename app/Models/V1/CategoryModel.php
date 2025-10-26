<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class CategoryModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'tbl_course_category';
    protected $allowedFields = [
        'category_id',
        'category_name',
        'status',
        'subject_id',
        'description',
        'path',
        'display_order'
    ];

    public function __construct()
    {
        parent::__construct();
        log_message('debug', 'CategoryModel initialized');
    }

    /**
     * Get list of categories with content count
     * 
     * @param string $condition Additional SQL condition
     * @return array List of categories
     */
    public function categoryList(string $condition = ''): array
    {
        try {
            log_message('debug', 'CategoryModel::categoryList - Starting with condition: ' . $condition);
            
            $builder = $this->db->table('tbl_course_category c');
            log_message('debug', 'CategoryModel::categoryList - Table builder created');
            
            $builder->select('c.category_id, c.category_name, c.status, c.subject_id, COALESCE(c.description, \'\') as description, c.path, c.display_order');
            $builder->select('(SELECT COUNT(course_id) FROM tbl_course WHERE category_id = c.category_id) as content_count');
            log_message('debug', 'CategoryModel::categoryList - Select statements added');
            
            if (!empty($condition)) {
                // Safely handle the condition
                if (strpos($condition, 'status') !== false) {
                    $status = (int) filter_var($condition, FILTER_SANITIZE_NUMBER_INT);
                    $builder->where('c.status', $status);
                    log_message('debug', 'CategoryModel::categoryList - Added status condition: ' . $status);
                }
            }

            $query = $builder->get();
            log_message('debug', 'CategoryModel::categoryList - Query executed');
            
            $result = $query->getResultArray();
            log_message('debug', 'CategoryModel::categoryList - Retrieved ' . count($result) . ' categories');
            
            return $result ?: [];
        } catch (\Exception $e) {
            log_message('error', 'CategoryModel::categoryList - Exception: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get category by ID
     * 
     * @param int $categoryId Category ID
     * @return array|null Category details
     */
    public function getCategoryById(int $categoryId): ?array
    {
        $builder = $this->db->table('tbl_course_category');
        $builder->where('category_id', $categoryId);
        $result = $builder->get()->getRowArray();
        
        return $result ?: null;
    }

    /**
     * Get categories by subject ID
     * 
     * @param int $subjectId Subject ID
     * @return array List of categories
     */
    public function getCategoriesBySubject(int $subjectId): array
    {
        $builder = $this->db->table('tbl_course_category');
        $builder->where('subject_id', $subjectId);
        $builder->where('status', 1);
        $builder->orderBy('display_order', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Add new category
     * 
     * @param array $data Category data
     * @return int|bool Category ID or false on failure
     */
    public function addCategory(array $data)
    {
        try {
            $this->db->transStart();
            
            $builder = $this->db->table('tbl_course_category');
            $builder->insert($data);
            
            $categoryId = $this->db->insertID();
            
            $this->db->transComplete();
            
            return $categoryId;
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error adding category: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update category
     * 
     * @param int $categoryId Category ID
     * @param array $data Category data
     * @return bool Success status
     */
    public function updateCategory(int $categoryId, array $data): bool
    {
        try {
            $this->db->transStart();
            
            $builder = $this->db->table('tbl_course_category');
            $builder->where('category_id', $categoryId);
            $builder->update($data);
            
            $this->db->transComplete();
            
            return true;
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error updating category: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete category
     * 
     * @param int $categoryId Category ID
     * @return bool Success status
     */
    public function deleteCategory(int $categoryId): bool
    {
        try {
            $this->db->transStart();
            
            // Check if category has associated courses
            $courseCount = $this->db->table('tbl_course')
                ->where('category_id', $categoryId)
                ->countAllResults();
            
            if ($courseCount > 0) {
                throw new \Exception('Cannot delete category with associated courses');
            }
            
            $builder = $this->db->table('tbl_course_category');
            $builder->where('category_id', $categoryId);
            $builder->delete();
            
            $this->db->transComplete();
            
            return true;
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error deleting category: ' . $e->getMessage());
            return false;
        }
    }
} 