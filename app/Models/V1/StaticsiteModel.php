<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class StaticsiteModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'admin_settings';
    protected $allowedFields = [
        'name',
        'value',
        'status'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function getReceiver(int $type, array $params): array
    {
        $name = '';
        $website = '';

        if ($type === 1) {
            $name = 'static_site_receiver_mail';
            $website = ' ';
        } elseif ($type === 2) {
            switch ($params['type']) {
                case 1:
                    $name = 'palssouthplainfield.com';
                    $website = 'palssouthplainfield';
                    break;
                case 2:
                    $name = 'palsnorthedison.com';
                    $website = 'palsnorthedison';
                    break;
                case 3:
                    $name = 'palsmarlboro.com';
                    $website = 'palsmarlboro';
                    break;
                case 4:
                    $name = 'palseastbrunswick.com';
                    $website = 'palseastbrunswick';
                    break;
                case 5:
                    $name = 'palsmonroe.com';
                    $website = 'palsmonroe';
                    break;
                case 6:
                    $name = 'palsoldbridge.com';
                    $website = 'palsoldbridge';
                    break;
                case 7:
                    $name = 'palsfreehold.com';
                    $website = 'palsfreehold';
                    break;
                case 8:
                    $name = 'palspiscataway.com';
                    $website = 'palspiscataway';
                    break;
                case 9:
                    $name = 'edquill.com';
                    $website = 'edquill';
                    break;
            }
        }

        $builder = $this->getBuilder();
        $builder->select("'{$website}' as webname, value")
                ->where('name', $name)
                ->where('status', 1);

        return $this->getResult($builder);
    }

    public function checkSubscription(array $params): array
    {
        $builder = $this->getBuilder();
        $builder->select('*')
                ->where('name', 'subscription_status')
                ->where('value', $params['subscription_id'])
                ->where('status', 1);

        return $this->getResult($builder);
    }
} 