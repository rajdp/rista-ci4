<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class TestingModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'user';
    protected $allowedFields = [
        'user_id',
        'email_id',
        'role_id'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getUserId(array $params): ?array
    {
        $builder = $this->getBuilder();
        $builder->select('user_id')
                ->where('email_id', $params['email_id'])
                ->where('role_id', $params['role_id']);

        return $this->getRow($builder);
    }

    public function deleteAllUser(array $userId): void
    {
        $this->db->transStart();

        try {
            $this->db->table('user')->where('user_id', $userId['user_id'])->delete();
            $this->db->table('user_address')->where('user_id', $userId['user_id'])->delete();
            $this->db->table('user_profile')->where('user_id', $userId['user_id'])->delete();
            $this->db->table('user_profile_details')->where('user_id', $userId['user_id'])->delete();

            $this->db->transComplete();
        } catch (\Exception $e) {
            $this->db->transRollback();
            throw $e;
        }
    }
} 