<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class SubjectModel extends Model
{
    protected $table = 'subject';
    protected $primaryKey = 'subject_id';
    protected $returnType = 'array';
    protected $allowedFields = [
        'subject_name',
        'description',
        'status',
        'school_id',
        'fee_amount',
        'deposit_amount',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date'
    ];
    protected $db;
    /**
     * Cached list of table fields to avoid repeated metadata queries.
     *
     * @var array<string>
     */
    protected array $tableFields = [];

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get list of subjects
     */
    public function getSubjects($data)
    {
        $params = is_object($data) ? (array) $data : (array) $data;
        $builder = $this->db->table($this->table);
        $fields = $this->getTableFields();

        // Filter by school if the column exists and the caller provided a value
        if (in_array('school_id', $fields, true) && !empty($params['school_id'])) {
            $builder->where('school_id', $params['school_id']);
        }

        // Filter by status when explicitly provided, otherwise default to active subjects for backwards compatibility
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $builder->where('status', $params['status']);
        } elseif (in_array('status', $fields, true)) {
            $builder->where('status', 1);
        }

        if (!empty($params['search'])) {
            $searchTerm = trim((string) $params['search']);
            $builder->groupStart()
                ->like('subject_name', $searchTerm)
                ->orLike('description', $searchTerm)
                ->groupEnd();
        }

        $builder->orderBy('subject_name', 'ASC');

        return $builder->get()->getResultArray();
    }

    /**
     * Create a subject record.
     */
    public function createSubject(array $data): int
    {
        $this->insert($data, true);
        return (int) $this->getInsertID();
    }

    /**
     * Update a subject by ID.
     */
    public function updateSubject(int $subjectId, array $data): bool
    {
        return $this->update($subjectId, $data);
    }

    /**
     * Determine if a subject exists for the given name (optionally scoped by school).
     */
    public function subjectExists(string $name, ?int $schoolId = null, ?int $excludeId = null): bool
    {
        $builder = $this->builder();
        $builder->where('LOWER(subject_name)', strtolower($name));

        if ($excludeId !== null) {
            $builder->where($this->primaryKey . ' !=', $excludeId);
        }

        if ($schoolId !== null && in_array('school_id', $this->getTableFields(), true)) {
            $builder->where('school_id', $schoolId);
        }

        return $builder->countAllResults() > 0;
    }

    /**
     * Expose table metadata so controllers can tailor payloads safely.
     *
     * @return array<string>
     */
    public function getTableFields(): array
    {
        if (empty($this->tableFields)) {
            $this->tableFields = $this->db->getFieldNames($this->table);
        }

        return $this->tableFields;
    }
}
