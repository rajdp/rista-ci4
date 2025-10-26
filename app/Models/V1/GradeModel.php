<?php

namespace App\Models\V1;

class GradeModel extends BaseModel
{
    protected $table = 'grade';
    protected $primaryKey = 'grade_id';
    protected $allowedFields = ['school_id', 'grade_name', 'description', 'status', 'sorting_no', 'created_date', 'modified_date'];

    public function getGradeList($data)
    {
        $schoolId = is_object($data) ? ($data->school_id ?? 0) : ($data['school_id'] ?? 0);
        $type = is_object($data) ? ($data->type ?? '') : ($data['type'] ?? '');
        
        $builder = $this->getBuilder('grade');
        $builder->select('grade_id, grade_name, description, status, sorting_no');
        
        // If type is 'active' or type is '2', only show active grades
        if ($type == 'active' || $type == '2') {
            $builder->where('status', 1);
        }
        
        if ($schoolId > 0) {
            $builder->groupStart()
                ->where('school_id', $schoolId)
                ->orWhere('school_id', 0)
                ->groupEnd();
        } else {
            $builder->where('school_id', 0);
        }
        
        $builder->orderBy('sorting_no', 'ASC');
        
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
        $gradeData = [
            'school_id' => $data->school_id ?? 0,
            'grade_name' => $data->grade_name ?? '',
            'description' => $data->description ?? '',
            'status' => $data->status ?? 1,
            'sorting_no' => $data->sorting_no ?? 0,
            'created_date' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->table('grade')->insert($gradeData);
    }

    public function updateGrade($gradeId, $data)
    {
        $updateData = [
            'grade_name' => $data->grade_name ?? '',
            'description' => $data->description ?? '',
            'status' => $data->status ?? 1,
            'modified_date' => date('Y-m-d H:i:s')
        ];
        
        return $this->db->table('grade')->where('grade_id', $gradeId)->update($updateData);
    }

    public function removeGrade($gradeId)
    {
        return $this->db->table('grade')->where('grade_id', $gradeId)->update(['status' => 0]);
    }
}
