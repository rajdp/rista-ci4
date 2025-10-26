<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class CorporateModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'user';
    protected $allowedFields = [
        'user_id',
        'email_id',
        'mobile',
        'password',
        'role_id',
        'corporate_id',
        'status'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function checkPassword(array $params): array
    {
        $builder = $this->getBuilder();
        $builder->select('mobile')
                ->where('password', md5($params['password']))
                ->where('email_id', $params['username']);

        return $this->getResult($builder);
    }

    public function login(array $params): array
    {
        $builder = $this->getBuilder('u');
        $builder->select('u.user_id, u.email_id, u.mobile, u.password, up.user_id,
                         up.first_name, up.last_name, u.role_id, u.corporate_id,
                         (SELECT corporate_name FROM corporate WHERE corporate_id = u.corporate_id) as corporate_name,
                         (SELECT corporate_code FROM corporate WHERE corporate_id = u.corporate_id) as corporate_code,
                         (SELECT status FROM corporate WHERE corporate_id = u.corporate_id) as status,
                         up.profile_url, up.profile_thumb_url')
                ->join('user_profile as up', 'up.user_id = u.user_id', 'left')
                ->where('u.email_id', $params['username'])
                ->where('u.password', md5($params['password']))
                ->where('u.role_id', 6);

        return $this->getResult($builder);
    }

    public function loginNew(array $params): array
    {
        $builder = $this->getBuilder('u');
        $builder->select('u.user_id, u.email_id, u.mobile, u.password, up.user_id,
                         c.corporate_name, c.corporate_code, c.status,
                         up.first_name, up.last_name, u.role_id, u.corporate_id,
                         up.profile_url, up.profile_thumb_url')
                ->join('user_profile as up', 'up.user_id = u.user_id', 'left')
                ->join('corporate as c', 'u.corporate_id = c.corporate_id', 'left')
                ->where('u.email_id', $params['username'])
                ->where('u.password', md5($params['password']))
                ->where('u.role_id', 6);

        return $this->getResult($builder);
    }

    public function getUserToken(int $userId): array
    {
        $builder = $this->getBuilder('user_token');
        $builder->select('id, user_id, status, access_token')
                ->where('user_id', $userId)
                ->where('status', 1)
                ->orderBy('id', 'DESC');

        return $this->getResult($builder);
    }

    public function corporateList(): array
    {
        $builder = $this->getBuilder('corporate');
        $builder->select('*')
                ->whereIn('status', [1, 2]);

        return $this->getResult($builder);
    }

    public function getCorporateId(array $params): array
    {
        $builder = $this->getBuilder('corporate');
        $builder->select('corporate_id')
                ->where('corporate_code', $params['corporate_code']);

        return $this->getResult($builder);
    }

    public function requestListOld(array $params): array
    {
        $builder = $this->getBuilder('r');
        $builder->select('r.*, c.corporate_name, c.corporate_code')
                ->join('corporate as c', 'c.corporate_id = r.corporate_id')
                ->where('r.corporate_id', $params['corporate_id']);

        return $this->getResult($builder);
    }

    public function requestList(array $params): array
    {
        $builder = $this->getBuilder('r');
        $builder->select('r.*, c.corporate_name, c.corporate_code')
                ->join('corporate as c', 'c.corporate_id = r.corporate_id')
                ->where('r.corporate_id', $params['corporate_id']);

        return $this->getResult($builder);
    }

    public function checkRequestExists(array $params): array
    {
        $builder = $this->getBuilder('corporate_request');
        $builder->select('*')
                ->where('corporate_id', $params['corporate_id'])
                ->where('status', 1);

        return $this->getResult($builder);
    }

    public function getCorporateMail(int $corporateId): array
    {
        $builder = $this->getBuilder('corporate');
        $builder->select('email_id')
                ->where('corporate_id', $corporateId);

        return $this->getResult($builder);
    }
} 