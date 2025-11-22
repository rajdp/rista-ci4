<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentCustomItemModel extends Model
{
    protected $table = 'student_custom_items';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $protectFields = true;
    protected $allowedFields = [
        'student_id',
        'school_id',
        'description',
        'amount',
        'start_date',
        'end_date',
        'is_active',
        'created_by',
        'created_at',
        'updated_at'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required|integer',
        'school_id' => 'required|integer',
        'description' => 'required|max_length[255]',
        'amount' => 'required|decimal',
        'start_date' => 'required|valid_date',
        'end_date' => 'permit_empty|valid_date',
        'is_active' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Get active custom items for a student within a date range
     * 
     * @param int $studentId
     * @param int $schoolId
     * @param string|null $date Date to check (Y-m-d format), defaults to today
     * @return array
     */
    public function getActiveItemsForStudent(int $studentId, int $schoolId, ?string $date = null): array
    {
        if ($date === null) {
            $date = date('Y-m-d');
        }

        try {
            $builder = $this->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('is_active', 1)
                ->where('start_date <=', $date)
                ->groupStart()
                    ->where('end_date', null)
                    ->orWhere('end_date >=', $date)
                ->groupEnd()
                ->orderBy('start_date', 'ASC');

            return $builder->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItemModel::getActiveItemsForStudent - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Get all custom items for a student
     * 
     * @param int $studentId
     * @param int $schoolId
     * @return array
     */
    public function getAllItemsForStudent(int $studentId, int $schoolId): array
    {
        try {
            $builder = $this->where('student_id', $studentId)
                ->where('school_id', $schoolId);
            
            // Check if created_at column exists before ordering by it
            if ($this->db->fieldExists('created_at', $this->table)) {
                $builder->orderBy('start_date', 'DESC')
                    ->orderBy('created_at', 'DESC');
            } else {
                $builder->orderBy('start_date', 'DESC');
            }
            
            return $builder->findAll();
        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItemModel::getAllItemsForStudent - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return [];
        }
    }

    /**
     * Calculate total amount from active custom items for a student
     * 
     * @param int $studentId
     * @param int $schoolId
     * @param string|null $date Date to check (Y-m-d format), defaults to today
     * @return float Total amount (can be negative)
     */
    public function getTotalAmountForStudent(int $studentId, int $schoolId, ?string $date = null): float
    {
        $items = $this->getActiveItemsForStudent($studentId, $schoolId, $date);
        $total = 0.0;

        foreach ($items as $item) {
            $total += (float)$item['amount'];
        }

        return $total;
    }
}

