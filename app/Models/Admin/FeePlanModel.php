<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class FeePlanModel extends Model
{
    protected $table = 'fee_plans';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'school_id',
        'name',
        'description',
        'plan_type',
        'amount',
        'billing_cycle_days',
        'auto_payment_enabled',
        'metadata',
        'created_at',
        'updated_at',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * List fee plans for a school
     * 
     * @param int $schoolId School ID
     * @param int|null $studentId Optional student ID to filter by active course enrollments
     * @param bool $includeInactive Whether to include inactive plans (if is_active field exists)
     * @return array
     */
    public function listForSchool(int $schoolId, ?int $studentId = null, bool $includeInactive = false): array
    {
        $builder = $this->where('school_id', $schoolId);
        
        // Filter by is_active if the field exists and includeInactive is false
        // Note: is_active field may not exist in all database schemas
        if (!$includeInactive) {
            // Try to filter by is_active - if column doesn't exist, this will be ignored at query time
            // We use a raw query approach that's safer
            $db = \Config\Database::connect();
            $fields = $db->getFieldData($this->table);
            $hasIsActive = false;
            foreach ($fields as $field) {
                if ($field->name === 'is_active') {
                    $hasIsActive = true;
                    break;
                }
            }
            if ($hasIsActive) {
                $builder->where('is_active', 1);
            }
        }
        
        // If student_id is provided, filter to show:
        // 1. General fee plans (not course-specific, name doesn't start with "Course:")
        // 2. Fee plans linked to student's active course enrollments
        if ($studentId !== null) {
            $db = \Config\Database::connect();
            
            // Get fee_plan_id from student_fee_plans linked to student's active course enrollments
            $activeCourseFeePlans = $db->table('student_courses sc')
                ->select('sfp.fee_plan_id')
                ->join('student_fee_plans sfp', 'sfp.id = sc.student_fee_plan_id', 'inner')
                ->where('sc.student_id', $studentId)
                ->where('sc.school_id', $schoolId)
                ->where('sc.status', 'active')
                ->where('sc.student_fee_plan_id IS NOT NULL')
                ->get()
                ->getResultArray();
            
            $feePlanIds = array_filter(array_unique(array_column($activeCourseFeePlans, 'fee_plan_id')));
            
            // Build query: general plans OR plans linked to active enrollments
            $builder->groupStart();
            // General plans (name doesn't start with "Course:")
            $builder->notLike('name', 'Course:', 'after');
            
            // OR plans linked to active course enrollments
            if (!empty($feePlanIds)) {
                $builder->orWhereIn('id', $feePlanIds);
            }
            $builder->groupEnd();
        }
        
        return $builder->orderBy('created_at', 'DESC')->findAll();
    }
}
