<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class BatchModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'batch';
    protected $allowedFields = [
        'batch_id',
        'batch_name',
        'school_id',
        'parent_batch_id',
        'status',
        'batch_type',
        'created_by'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get list of batches with school information
     * 
     * @param array $params Query parameters
     * @return array List of batches
     */
    public function batchList(array $params): array
    {
        $builder = $this->db->table('batch b');
        $builder->select('b.batch_id, b.batch_name, b.school_id, s.name as school_name, b.parent_batch_id, b.status, b.batch_type');
        $builder->join('school s', 'b.school_id = s.school_id', 'left');

        if ($params['role_id'] != 6) {
            $builder->where('b.school_id', $params['school_id']);
        } else {
            $builder->where('b.status', 1);
        }

        if ($params['type'] == '2') {
            $builder->where('b.status', 1);
        }

        $builder->orderBy('b.batch_id', 'ASC');
        return $builder->get()->getResultArray();
    }

    /**
     * Get batch list with additional conditions
     * 
     * @param array $params Query parameters
     * @return array List of batches
     */
    public function getBatchList(array $params): array
    {
        $builder = $this->db->table('batch b');
        $builder->select('b.batch_id, b.batch_name, b.school_id, s.name as school_name, b.status, b.batch_type');
        $builder->join('school s', 'b.school_id = s.school_id', 'left');

        if ($params['role_id'] > 2) {
            $schoolType = $this->db->table('school')
                ->select('institution_type')
                ->where('school_id', $params['school_id'])
                ->get()
                ->getRowArray();

            if ($schoolType['institution_type'] != 2) {
                $builder->where('b.status', 1);
            }
        }

        if ($params['type'] == '2') {
            $builder->where('b.status', 1);
        }

        $corporate = $this->db->table('school')
            ->select('branch_name')
            ->where('school_id', $params['school_id'])
            ->get()
            ->getResultArray();

        if (!empty($corporate[0]['branch_name'])) {
            $builder->whereIn('b.school_id', [0, $params['school_id']]);
        } else {
            $builder->where('b.school_id', $params['school_id']);
        }

        $builder->orderBy('b.batch_name', 'ASC');
        return $builder->get()->getResultArray();
    }

    /**
     * Get batch tree list with additional information
     * 
     * @param int $parent_key Parent batch ID
     * @param array $params Query parameters
     * @return array List of batches with tree structure
     */
    public function batchTreeList(int $parent_key, array $params): array
    {
        $builder = $this->db->table('batch b');
        $builder->select("CONCAT_WS('/', b.batch_id, 'folder', b.parent_batch_id) as value");
        $builder->select('b.batch_name as text, b.school_id, s.name as school_name, b.parent_batch_id, b.status, b.batch_type');
        $builder->select('0 as checked, b.batch_id');
        $builder->select("(SELECT COUNT(id) FROM classroom_content WHERE batch_id = b.batch_id) as content_count");
        $builder->select("'folder' as type, b.created_by");
        
        // Add delete status based on role
        if ($params['role_id'] == 2) {
            $builder->select("(CASE WHEN b.school_id = {$params['school_id']} THEN 1 ELSE 0 END) AS delete_status");
        } elseif (in_array($params['role_id'], [4, 6])) {
            $builder->select("(CASE WHEN b.created_by = {$params['user_id']} THEN 1 ELSE 0 END) AS delete_status");
        } else {
            $builder->select('1 as delete_status');
        }

        $builder->join('school s', 'b.school_id = s.school_id', 'left');
        $builder->where('b.parent_batch_id', $parent_key);

        if ($params['role_id'] != 6) {
            $builder->where('b.school_id', $params['school_id']);
        }

        if ($params['type'] == '2') {
            $builder->where('b.status', 1);
        } else {
            $builder->whereIn('b.status', [1, 2]);
        }

        if (isset($params['corporate_id']) && $params['corporate_id'] > 0) {
            $builder->where("(b.corporate_id = {$params['corporate_id']} OR b.school_id = {$params['school_id']})");
        }

        if (isset($params['parent_batch']) && $params['parent_batch'] != '') {
            $builder->where('b.batch_id', $params['parent_batch']);
        }

        // Handle teacher permissions
        if ($params['role_id'] == 4) {
            $permissions = $this->db->table('user_role_permission')
                ->select('user_permission_id')
                ->where('user_id', $params['user_id'])
                ->where('school_id', $params['school_id'])
                ->get()
                ->getResultArray();

            if (!empty($permissions)) {
                $permissionIds = array_column($permissions, 'user_permission_id');
                if (in_array(5, $permissionIds) && !in_array(6, $permissionIds)) {
                    $builder->where('b.created_by', $params['user_id']);
                }
            }
        }

        // Handle sorting
        if (isset($params['sort_type'])) {
            switch ($params['sort_type']) {
                case 1:
                    $builder->orderBy('b.batch_id', 'DESC');
                    break;
                case 2:
                    $builder->orderBy('b.batch_id', 'ASC');
                    break;
                case 3:
                    $builder->orderBy('b.batch_name', 'ASC');
                    break;
                case 4:
                    $builder->orderBy('b.batch_name', 'DESC');
                    break;
            }
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Check if batch name exists
     * 
     * @param array $params Query parameters
     * @param string $type Operation type (add/edit)
     * @return array Result of the check
     */
    public function checkBatch(array $params, string $type): array
    {
        $builder = $this->db->table('batch');
        $builder->select('batch_id');
        $builder->where('school_id', $params['school_id']);
        $builder->where('batch_name', $params['batch_name']);

        if ($type == 'edit') {
            $builder->where('batch_id !=', $params['batch_id']);
        }

        if (isset($params['parent_batch_id'])) {
            $builder->where('parent_batch_id', $params['parent_batch_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Check batch permissions based on user role
     * 
     * @param array $params Query parameters
     * @return array Result of the check
     */
    public function checkBatchPermission(array $params): array
    {
        $builder = $this->db->table('batch');
        $builder->select('batch_id');
        $builder->where('batch_id', $params['batch_id']);

        if ($params['role_id'] == 2) {
            $builder->where('school_id', $params['school_id']);
        } elseif ($params['role_id'] == 4) {
            $builder->where('school_id', $params['school_id']);
            $builder->where('created_by', $params['user_id']);
        } elseif ($params['role_id'] == 6) {
            $builder->where('created_by', $params['user_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Get corporate/branch details for a school
     * 
     * @param int $schoolId School ID
     * @return array Corporate details
     */
    public function corporateDetail($schoolId): array
    {
        $builder = $this->db->table('school');
        $builder->select('branch_name');
        $builder->where('school_id', $schoolId);
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get content items for a batch/folder
     * 
     * @param int $batchId Batch ID
     * @param array $params Query parameters
     * @return array Content items in the batch
     */
    public function getBatchContent(int $batchId, array $params): array
    {
        $builder = $this->db->table('classroom_content cc');
        $builder->select("CONCAT_WS('/', cc.content_id, 'content', {$batchId}) as value");
        $builder->select('c.name as text, 0 as checked, cc.content_id, c.content_type, c.content_format');
        $builder->select("'content' as type");
        $builder->select("(CASE WHEN cc.batch_id = {$batchId} THEN 1 ELSE 0 END) as delete_status");
        $builder->join('content c', 'cc.content_id = c.content_id', 'left');
        $builder->where('cc.batch_id', $batchId);
        $builder->where('cc.status', 1);
        $builder->where('c.status', 1);
        
        if (isset($params['school_id'])) {
            $builder->where('c.school_id', $params['school_id']);
        }
        
        $builder->orderBy('c.name', 'ASC');
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get batch assignments for a specific class
     *
     * @param array $params
     * @return array
     */
    public function getClassBatch(array $params): array
    {
        $builder = $this->db->table('class');
        $builder->select('batch_id');
        $builder->where('class_id', $params['class_id']);

        if (!empty($params['school_id'])) {
            $builder->where('school_id', $params['school_id']);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Build a lightweight tree for a specific batch and its parents
     *
     * @param int $batchId
     * @param array $params
     * @return array
     */
    public function addBatchTree(int $batchId, array $params): array
    {
        if (empty($batchId)) {
            return [];
        }

        $builder = $this->db->table('batch b');
        $builder->select('b.batch_id, b.batch_name as text, b.parent_batch_id, b.batch_type');
        $builder->select("CONCAT_WS('/', b.batch_id, 'folder', b.parent_batch_id) as value");
        $builder->select("'folder' as type");
        $builder->select('0 as checked');

        if ($params['role_id'] == 2) {
            $builder->select("(CASE WHEN b.school_id = {$params['school_id']} THEN 1 ELSE 0 END) AS delete_status");
        } elseif (in_array($params['role_id'], [4, 6])) {
            $builder->select("(CASE WHEN b.created_by = {$params['user_id']} THEN 1 ELSE 0 END) AS delete_status");
        } else {
            $builder->select('1 as delete_status');
        }

        if ($params['role_id'] != 6) {
            $builder->where('b.school_id', $params['school_id']);
        }

        $builder->where('b.batch_id', $batchId);

        $result = $builder->get()->getResultArray();

        foreach ($result as &$item) {
            $item['children'] = [];
        }

        return $result;
    }
}
