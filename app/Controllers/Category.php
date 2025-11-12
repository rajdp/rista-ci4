<?php
namespace App\Controllers;

use App\Models\CategoryModel;
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Services\CategoryService;

class Category extends ResourceController
{
    use ResponseTrait;

    protected $modelName = 'App\Models\CategoryModel';
    protected $format = 'json';
    protected $categoryService;

    public function __construct()
    {
        $this->model = new CategoryModel();
        $this->categoryService = new CategoryService();
    }

    /**
     * Get list of categories
     */
    public function index()
    {
        try {
            $data = $this->model->findAll();
            return $this->respond($data);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Get list of categories (POST method for frontend compatibility)
     */
    public function list()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $schoolId = $params['school_id'] ?? 0;
            
            $builder = $this->model->builder();
            $builder->select('category_id, category_name, description, subject_id, entity_id, status, path, display_order, created_date, modified_date');
            
            // Use entity_id (not school_id) - this is the actual column name in the database
            if ($schoolId > 0) {
                $builder->where('entity_id', $schoolId);
            }
            
            $builder->where('status', 'A');
            $builder->orderBy('display_order', 'ASC');
            $categories = $builder->get()->getResultArray();

            // Decode subject_id if it's JSON and convert status to match frontend
            foreach ($categories as &$category) {
                // Decode JSON subject_id
                if (!empty($category['subject_id'])) {
                    $decoded = json_decode($category['subject_id'], true);
                    if (is_array($decoded)) {
                        $category['subject_id'] = $decoded;
                    }
                }
                // Add school_id for frontend compatibility
                $category['school_id'] = $category['entity_id'];
            }

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
     * Add new category
     */
    public function create()
    {
        try {
            $data = $this->request->getJSON();
            $this->model->insert($data);
            return $this->respondCreated(['message' => 'Category created successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    /**
     * Add new category (frontend compatibility)
     */
    public function addCategory()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validate required fields
            if (empty($params['category_name'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Category name is required'
                ]);
            }

            // Handle subject_id - can be array or null
            $subjectId = null;
            if (!empty($params['subject_id'])) {
                if (is_array($params['subject_id'])) {
                    $subjectId = !empty($params['subject_id']) ? json_encode($params['subject_id']) : null;
                } else {
                    $subjectId = $params['subject_id'];
                }
            }

            // Get entity_id (school_id) from params
            $entityId = $params['school_id'] ?? 0;
            $userId = $params['user_id'] ?? 0;

            $data = [
                'category_name' => $params['category_name'],
                'subject_id' => $subjectId,
                'description' => $params['description'] ?? '',
                'status' => $params['status'] ?? 'A',
                'path' => $params['path'] ?? '',
                'display_order' => $params['display_order'] ?? 0,
                'entity_id' => $entityId,
                'created_by' => $userId
            ];

            $categoryId = $this->model->insert($data);

            if ($categoryId) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category added successfully',
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
     * Edit category (frontend compatibility)
     */
    public function editCategory()
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            $categoryId = $params['category_id'] ?? 0;

            if (empty($categoryId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Category ID is required'
                ]);
            }

            if (empty($params['category_name'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Category name is required'
                ]);
            }

            // Handle subject_id - can be array or null
            $subjectId = null;
            if (!empty($params['subject_id'])) {
                if (is_array($params['subject_id'])) {
                    $subjectId = !empty($params['subject_id']) ? json_encode($params['subject_id']) : null;
                } else {
                    $subjectId = $params['subject_id'];
                }
            }

            $userId = $params['user_id'] ?? 0;

            $data = [
                'category_name' => $params['category_name'],
                'subject_id' => $subjectId,
                'description' => $params['description'] ?? '',
                'status' => $params['status'] ?? 'A',
                'path' => $params['path'] ?? '',
                'display_order' => $params['display_order'] ?? 0,
                'modified_by' => $userId
            ];

            $result = $this->model->update($categoryId, $data);

            if ($result) {
                return $this->respond([
                    'IsSuccess' => true,
                    'ResponseObject' => 'Category updated successfully',
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
     * Edit category
     */
    public function update($id = null)
    {
        try {
            $data = $this->request->getJSON();
            $this->model->update($id, $data);
            return $this->respond(['message' => 'Category updated successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }

    public function delete($id = null)
    {
        try {
            $this->model->delete($id);
            return $this->respondDeleted(['message' => 'Category deleted successfully']);
        } catch (\Exception $e) {
            return $this->fail($e->getMessage());
        }
    }
}
