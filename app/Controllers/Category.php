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
            
            if ($schoolId > 0) {
                $builder->where('school_id', $schoolId);
            }
            
            $builder->where('status', 1);
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
