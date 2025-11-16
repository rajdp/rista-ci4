<?php

namespace App\Models\V1;

class GradeModel extends BaseModel
{
    protected $table = 'grade';
    protected $primaryKey = 'grade_id';
    protected $allowedFields = ['school_id', 'grade_name', 'description', 'status', 'sorting_no', 'created_date', 'modified_date'];

    public function __construct()
    {
        parent::__construct();
        $this->db = \Config\Database::connect();
    }

    public function getGradeList($data)
    {
        // Ensure data is an array
        $params = is_object($data) ? (array) $data : (array) $data;
        
        $schoolId = isset($params['school_id']) ? (int) $params['school_id'] : 0;
        $type = isset($params['type']) ? $params['type'] : '';
        
        // Get table fields to check which columns exist
        $fields = $this->db->getFieldNames($this->table);
        
        $builder = $this->getBuilder('grade');
        
        // Build select fields dynamically
        $selectFields = ['grade_id', 'grade_name', 'description', 'status'];
        if (in_array('sorting_no', $fields, true)) {
            $selectFields[] = 'sorting_no';
        }
        $builder->select(implode(', ', $selectFields));
        
        // If type is 'active' or type is '2', only show active grades
        if ($type == 'active' || $type == '2') {
            $builder->where('status', 1);
        }
        
        if ($schoolId > 0) {
            // Only show grades for the specific school, not global grades
            $builder->where('school_id', $schoolId);
        } else {
            // If no school_id provided, show only global grades
            $builder->where('school_id', 0);
        }
        
        // Only order by sorting_no if the column exists
        if (in_array('sorting_no', $fields, true)) {
            $builder->orderBy('sorting_no', 'ASC');
        } else {
            $builder->orderBy('grade_name', 'ASC');
        }
        
        return $this->getResult($builder);
    }

    public function getGradeById($gradeId)
    {
        $builder = $this->getBuilder('grade');
        $builder->where('grade_id', $gradeId);
        return $this->getRow($builder);
    }

    public function addGrade($data)
    {
        // Get table fields to check which columns exist
        $fields = $this->db->getFieldNames($this->table);
        
        $gradeData = [
            'school_id' => $data->school_id ?? 0,
            'grade_name' => $data->grade_name ?? '',
            'description' => $data->description ?? '',
            'status' => $data->status ?? 1,
            'sorting_no' => $data->sorting_no ?? 0
        ];
        
        // Only add created_date if the column exists
        if (in_array('created_date', $fields, true)) {
            $gradeData['created_date'] = date('Y-m-d H:i:s');
        }
        
        $builder = $this->db->table('grade');
        if ($builder->insert($gradeData)) {
            return (int) $this->db->insertID();
        }
        
        return false;
    }

    public function updateGrade($gradeId, $data)
    {
        // Get table fields to check which columns exist
        $fields = $this->db->getFieldNames($this->table);
        
        $updateData = [
            'grade_name' => $data->grade_name ?? '',
            'description' => $data->description ?? '',
            'status' => $data->status ?? 1
        ];
        
        // Add school_id if it exists in table and is provided
        if (in_array('school_id', $fields, true) && isset($data->school_id)) {
            $updateData['school_id'] = $data->school_id ?? 0;
        }
        
        // Add sorting_no if it exists in table and is provided
        if (in_array('sorting_no', $fields, true) && isset($data->sorting_no)) {
            $updateData['sorting_no'] = $data->sorting_no ?? 0;
        }
        
        // Only add modified_date if the column exists
        if (in_array('modified_date', $fields, true)) {
            $updateData['modified_date'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->table('grade')->where('grade_id', $gradeId)->update($updateData);
    }

    public function removeGrade($gradeId)
    {
        return $this->db->table('grade')->where('grade_id', $gradeId)->update(['status' => 0]);
    }

    /**
     * Check if a grade with the same name already exists for a school
     * @param string $gradeName The grade name to check
     * @param int|null $schoolId The school ID (optional, for scoped checking)
     * @param int|null $excludeId The grade ID to exclude from check (for edit operations)
     * @return bool True if grade exists, false otherwise
     */
    public function gradeExists(string $gradeName, ?int $schoolId = null, ?int $excludeId = null): bool
    {
        $builder = $this->getBuilder('grade');
        $builder->where('LOWER(grade_name)', strtolower(trim($gradeName)));

        if ($excludeId !== null) {
            $builder->where('grade_id !=', $excludeId);
        }

        if ($schoolId !== null) {
            $builder->where('school_id', $schoolId);
        }

        return $builder->countAllResults() > 0;
    }
}
