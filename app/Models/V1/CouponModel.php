<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class CouponModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'tbl_coupon';
    protected $allowedFields = [
        'coupon_id',
        'coupon_code',
        'validity_from',
        'validity_to',
        'discount_type',
        'discount',
        'course_based',
        'course_id',
        'no_of_users',
        'status'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function couponList(string $condition): array
    {
        $builder = $this->getBuilder();
        $builder->select('coupon_id, coupon_code, validity_from, validity_to, discount_type, 
                         discount, course_based, course_id, no_of_users, status')
                ->orderBy('coupon_id', 'DESC');

        if (!empty($condition)) {
            $builder->where($condition);
        }

        return $this->getResult($builder);
    }
} 