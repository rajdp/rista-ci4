<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Jwt extends BaseConfig
{
    /**
     * JWT Secret Key
     */
    public string $key = 'edquillInternationalINCUthkalSoftware';

    /**
     * Token timeout in minutes
     */
    public int $tokenTimeout = 60;

    /**
     * Algorithm for JWT signing
     */
    public string $algorithm = 'HS256';
}