<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\V1\BatchModel;
use App\Models\V1\CommonModel;
use CodeIgniter\HTTP\ResponseInterface;

class Batch extends BaseController
{
    use RestTrait;

    protected $batchModel;
    protected $commonModel;

    public function __construct()
    {
        $this->batchModel = new BatchModel();
        $this->commonModel = new CommonModel();
    }

    /**
     * Get batch list
     */
    public function list(): ResponseInterface
    {
        try {
            $params = $this->request->getJSON(true) ?? [];
            
            if (empty($params)) {
                $params = $this->request->getPost() ?? [];
            }

            // Validation
            if (empty($params['platform'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Platform should not be empty'
                ]);
            }

            if (empty($params['role_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'Role Id should not be empty'
                ]);
            }

            if (empty($params['user_id'])) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => [],
                    'ErrorObject' => 'User Id should not be empty'
                ]);
            }

            $batchList = [];
            
            // Check if list_type is 'list'
            if (isset($params['list_type']) && $params['list_type'] == 'list') {
                $batchList = $this->batchModel->getBatchList($params);
            } else {
                // Check if class_id is provided
                if (isset($params['class_id']) && $params['class_id'] != '') {
                    $getClassBatch = $this->batchModel->getClassBatch($params);
                    
                    if (!empty($getClassBatch[0]['batch_id'])) {
                        $batch_id = explode(',', $getClassBatch[0]['batch_id']);
                        $parent_batch_id = [];
                        
                        foreach ($batch_id as $id) {
                            $formAddBatch = $this->formAddBatchList($id, $params);
                            foreach ($formAddBatch as $value) {
                                if ($value['parent_batch_id'] == 0) {
                                    $batch = $value['batch_id'];
                                } else {
                                    $batch = $this->checkChildren($value['children']);
                                }
                            }
                            $parent_batch_id[] = $batch;
                        }
                        
                        $specificIDs = $parent_batch_id;
                        $getCorporateDetail = $this->batchModel->corporateDetail($params['school_id']);
                        $params['corporate_id'] = count($getCorporateDetail) > 0 && $getCorporateDetail[0]['branch_name'] > 0 ? $getCorporateDetail[0]['branch_name'] : 0;
                        $batchList = $this->formBatchList(0, $params);
                        
                        // Sort by specific IDs
                        $sortedArray = [];
                        foreach ($batchList as $value) {
                            $sortedArray[$value['batch_id']][] = $value;
                        }
                        
                        $result = [];
                        foreach ($specificIDs as $id) {
                            if (isset($sortedArray[$id])) {
                                $result = array_merge($result, $sortedArray[$id]);
                                unset($sortedArray[$id]);
                            }
                        }
                        
                        foreach ($sortedArray as $items) {
                            $result = array_merge($result, $items);
                        }
                        
                        $batchList = $result;
                    } else {
                        $getCorporateDetail = $this->batchModel->corporateDetail($params['school_id']);
                        $params['corporate_id'] = count($getCorporateDetail) > 0 && $getCorporateDetail[0]['branch_name'] > 0 ? $getCorporateDetail[0]['branch_name'] : 0;
                        $batchList = $this->formBatchList(0, $params);
                    }
                } else {
                    $getCorporateDetail = $this->batchModel->corporateDetail($params['school_id']);
                    $params['corporate_id'] = count($getCorporateDetail) > 0 && $getCorporateDetail[0]['branch_name'] > 0 ? $getCorporateDetail[0]['branch_name'] : 0;
                    $batchList = $this->formBatchList(0, $params);
                }
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $batchList,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Batch list error: ' . $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => [],
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Helper function to form batch list
     */
    private function formBatchList($parent_key, $params)
    {
        $batchList = $this->batchModel->batchTreeList($parent_key, $params);
        $batchContents = [];

        foreach ($batchList as $key => $value) {
            if ($value['batch_type'] == 1) {
                $batchContents = $this->batchModel->getBatchContent($value['batch_id'], $params);
                foreach ($batchContents as $contentKey => $contentValue) {
                    $batchContents[$contentKey]['batch_type'] = 2;
                    $batchContents[$contentKey]['children'] = [];
                }
            }
            $batchList[$key]['value'] = $value['value'] . '/' . $value['delete_status'];
            $batchList[$key]['children'] = $this->formBatchList($value['batch_id'], $params);
            $batchList[$key]['children'] = array_merge($batchContents, $batchList[$key]['children']);
        }
        
        return $batchList;
    }

    /**
     * Helper function to form add batch list
     */
    private function formAddBatchList($batch_id, $params)
    {
        $batchList = $this->batchModel->addBatchTree($batch_id, $params);
        
        foreach ($batchList as $key => $value) {
            $batchList[$key]['children'] = $this->formAddBatchList($value['parent_batch_id'], $params);
        }
        
        return $batchList;
    }

    /**
     * Helper function to check children
     */
    private function checkChildren($formAddBatch)
    {
        $batch = null;
        
        foreach ($formAddBatch as $value) {
            if ($value['parent_batch_id'] == 0) {
                $batch = $value['batch_id'];
            } else {
                $batch = $this->checkChildren($value['children']);
            }
        }
        
        return $batch;
    }
}
