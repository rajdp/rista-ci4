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
        $builder = $this->db->table($this->table . ' s');
        $fields = $this->getTableFields();

        // Build select fields dynamically based on what exists in the table
        $selectFields = ['s.subject_id', 's.subject_name', 's.description', 's.status', 's.school_id'];
        
        // Add fee_amount and deposit_amount only if they exist in the table
        if (in_array('fee_amount', $fields, true)) {
            $selectFields[] = 's.fee_amount';
        }
        if (in_array('deposit_amount', $fields, true)) {
            $selectFields[] = 's.deposit_amount';
        }
        
        // Add standard fields if they exist
        if (in_array('created_by', $fields, true)) {
            $selectFields[] = 's.created_by';
        }
        if (in_array('created_date', $fields, true)) {
            $selectFields[] = 's.created_date';
        }
        if (in_array('modified_by', $fields, true)) {
            $selectFields[] = 's.modified_by';
        }
        if (in_array('modified_date', $fields, true)) {
            $selectFields[] = 's.modified_date';
        }
        
        // Add school name from join
        $selectFields[] = 'COALESCE(sc.name, "") AS school_name';
        
        $builder->select(implode(', ', $selectFields));
        $builder->join('school sc', 's.school_id = sc.school_id', 'left');

        // Filter by school if the column exists and the caller provided a value
        if (in_array('school_id', $fields, true) && !empty($params['school_id'])) {
            $builder->where('s.school_id', $params['school_id']);
        }

        // Filter by status when explicitly provided, otherwise default to active subjects for backwards compatibility
        if (isset($params['status']) && $params['status'] !== '' && $params['status'] !== null) {
            $builder->where('s.status', $params['status']);
        } elseif (in_array('status', $fields, true)) {
            $builder->where('s.status', 1);
        }

        if (!empty($params['search'])) {
            $searchTerm = trim((string) $params['search']);
            $builder->groupStart()
                ->like('s.subject_name', $searchTerm)
                ->orLike('s.description', $searchTerm)
                ->groupEnd();
        }

        $builder->orderBy('s.subject_name', 'ASC');

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
        // Always refresh to ensure we detect newly added columns
        // Clear cache if it exists to force refresh
        $this->tableFields = [];
        $this->tableFields = $this->db->getFieldNames($this->table);

        return $this->tableFields;
    }
}
