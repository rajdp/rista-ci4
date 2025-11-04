<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class StudentGuardianModel extends Model
{
    protected $table = 'student_guardians';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'student_id',
        'guardian_id',
        'is_primary',
        'relationship_override',
        'created_at',
    ];

    protected $useTimestamps = false;

    /**
     * Return guardian assignments for a given student.
     */
    public function getByStudent(int $studentId): array
    {
        return $this->where('student_id', $studentId)->findAll();
    }

    /**
     * Return all students linked to a guardian.
     */
    public function getByGuardian(int $guardianId): array
    {
        return $this->where('guardian_id', $guardianId)->findAll();
    }
}
