<?php

namespace App\Models\Appt;

use CodeIgniter\Model;

class GuestModel extends Model
{
    protected $table = 't_appt_guest';
    protected $primaryKey = 'guest_id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'appt_id',
        'name',
        'email',
        'role',
    ];
}
