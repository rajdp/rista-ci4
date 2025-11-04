<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class GuardianModel extends Model
{
    protected $table = 'guardians';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'relationship',
        'communication_preference',
        'notes',
        'status',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Fetch guardians with optional filters for school, search, status.
     */
    public function filterGuardians(array $filters = []): array
    {
        $builder = $this->builder();

        if (!empty($filters['school_id'])) {
            $builder->where('school_id', (int) $filters['school_id']);
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $builder->where('status', (int) $filters['status']);
        }

        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $builder->groupStart()
                ->like('first_name', $search)
                ->orLike('last_name', $search)
                ->orLike('email', $search)
                ->orLike('phone', $search)
                ->groupEnd();
        }

        if (!empty($filters['preference'])) {
            $builder->where('communication_preference', $filters['preference']);
        }

        $builder->orderBy('created_at', 'DESC');

        return $builder->get()->getResultArray();
    }
}
