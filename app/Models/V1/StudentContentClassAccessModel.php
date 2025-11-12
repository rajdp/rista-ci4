<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class StudentContentClassAccessModel extends Model
{
    protected $table = 'student_content_class_access';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_content_id', 'class_id', 'class_content_id', 
        'created_date', 'created_by'
    ];
    
    protected $useTimestamps = false;
    
    /**
     * Add class access for a student content record
     */
    public function addClassAccess($studentContentId, $classId, $classContentId, $userId)
    {
        return $this->insert([
            'student_content_id' => $studentContentId,
            'class_id' => $classId,
            'class_content_id' => $classContentId,
            'created_date' => date('Y-m-d H:i:s'),
            'created_by' => $userId
        ]);
    }
    
    /**
     * Remove class access for a student content record
     */
    public function removeClassAccess($studentContentId, $classId)
    {
        return $this->where([
            'student_content_id' => $studentContentId,
            'class_id' => $classId
        ])->delete();
    }
    
    /**
     * Update class access (move from old class to new class)
     */
    public function updateClassAccess($studentContentId, $oldClassId, $newClassId, $newClassContentId, $userId)
    {
        // Remove old access
        $this->removeClassAccess($studentContentId, $oldClassId);
        // Add new access
        return $this->addClassAccess($studentContentId, $newClassId, $newClassContentId, $userId);
    }
    
    /**
     * Get all classes that can access a student content record
     */
    public function getAccessibleClasses($studentContentId)
    {
        return $this->where('student_content_id', $studentContentId)->findAll();
    }
    
    /**
     * Check if a class has access to a student content record
     */
    public function hasClassAccess($studentContentId, $classId)
    {
        return $this->where([
            'student_content_id' => $studentContentId,
            'class_id' => $classId
        ])->first() !== null;
    }
    
    /**
     * Get student content records accessible by a specific class
     */
    public function getClassAccessibleContent($classId)
    {
        return $this->select('student_content_class_access.*, student_content.*')
            ->join('student_content', 'student_content.id = student_content_class_access.student_content_id')
            ->where('student_content_class_access.class_id', $classId)
            ->findAll();
    }
    
    /**
     * Get student content records for a specific student and class
     */
    public function getStudentClassContent($studentId, $classId)
    {
        return $this->select('student_content_class_access.*, student_content.*')
            ->join('student_content', 'student_content.id = student_content_class_access.student_content_id')
            ->where('student_content_class_access.class_id', $classId)
            ->where('student_content.student_id', $studentId)
            ->findAll();
    }
}











