<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentPaymentMethodModel extends Model
{
    protected $table = 'student_payment_methods';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $deletedField = 'deleted_at';
    protected $allowedFields = [
        'student_id',
        'school_id',
        'provider_id',
        'payment_token',
        'token_type',
        'display_info',
        'is_default',
        'is_active',
        'is_verified',
        'billing_address',
        'authorized_at',
        'authorized_by',
        'authorization_ip',
        'authorization_user_agent',
        'gateway_customer_id',
        'gateway_payment_method_id',
        'gateway_metadata',
        'expires_at',
        'expiry_notification_sent',
        'verification_status',
        'verification_attempts',
        'verified_at',
        'last_used_at',
        'total_charges',
        'total_amount',
        'deleted_by'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'student_id' => 'required|integer',
        'school_id' => 'required|integer',
        'provider_id' => 'required|integer',
        'payment_token' => 'required',
        'token_type' => 'required|in_list[card,ach,bank_account,other]'
    ];

    /**
     * Get all active payment methods for a student
     */
    public function getStudentPaymentMethods(int $studentId): array
    {
        return $this->select('student_payment_methods.*, providers.code as provider_code, providers.name as provider_name')
            ->join('providers', 'providers.id = student_payment_methods.provider_id', 'left')
            ->where('student_payment_methods.student_id', $studentId)
            ->where('student_payment_methods.is_active', 1)
            ->orderBy('student_payment_methods.is_default', 'DESC')
            ->orderBy('student_payment_methods.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Get default payment method for a student
     */
    public function getDefaultPaymentMethod(int $studentId): ?array
    {
        return $this->select('student_payment_methods.*, providers.code as provider_code, providers.name as provider_name')
            ->join('providers', 'providers.id = student_payment_methods.provider_id', 'left')
            ->where('student_payment_methods.student_id', $studentId)
            ->where('student_payment_methods.is_active', 1)
            ->where('student_payment_methods.is_default', 1)
            ->first();
    }

    /**
     * Set a payment method as default (and unset others)
     */
    public function setAsDefault(int $paymentMethodId, int $studentId): bool
    {
        // Unset all other defaults for this student
        $this->where('student_id', $studentId)
            ->set('is_default', 0)
            ->update();

        // Set this one as default
        return $this->update($paymentMethodId, ['is_default' => 1]);
    }

    /**
     * Get payment methods expiring soon
     */
    public function getExpiringPaymentMethods(int $daysAhead = 30): array
    {
        $expiryDate = date('Y-m-d', strtotime("+{$daysAhead} days"));

        return $this->select('student_payment_methods.*, providers.name as provider_name')
            ->join('providers', 'providers.id = student_payment_methods.provider_id', 'left')
            ->where('student_payment_methods.is_active', 1)
            ->where('student_payment_methods.expires_at <=', $expiryDate)
            ->where('student_payment_methods.expires_at >=', date('Y-m-d'))
            ->where('student_payment_methods.expiry_notification_sent', 0)
            ->findAll();
    }

    /**
     * Mark expiry notification as sent
     */
    public function markExpiryNotificationSent(int $paymentMethodId): bool
    {
        return $this->update($paymentMethodId, ['expiry_notification_sent' => 1]);
    }

    /**
     * Update usage statistics after a charge
     */
    public function updateUsageStats(int $paymentMethodId, float $amount): bool
    {
        $method = $this->find($paymentMethodId);
        if (!$method) {
            return false;
        }

        return $this->update($paymentMethodId, [
            'last_used_at' => date('Y-m-d H:i:s'),
            'total_charges' => $method['total_charges'] + 1,
            'total_amount' => $method['total_amount'] + $amount
        ]);
    }

    /**
     * Get payment methods by school
     */
    public function getSchoolPaymentMethods(int $schoolId, array $filters = []): array
    {
        $builder = $this->select('student_payment_methods.*, providers.name as provider_name')
            ->join('providers', 'providers.id = student_payment_methods.provider_id', 'left')
            ->where('student_payment_methods.school_id', $schoolId)
            ->where('student_payment_methods.is_active', 1);

        if (!empty($filters['token_type'])) {
            $builder->where('student_payment_methods.token_type', $filters['token_type']);
        }

        if (!empty($filters['provider_id'])) {
            $builder->where('student_payment_methods.provider_id', $filters['provider_id']);
        }

        return $builder->orderBy('student_payment_methods.created_at', 'DESC')
            ->findAll();
    }

    /**
     * Soft delete payment method
     */
    public function softDelete(int $paymentMethodId, ?int $deletedBy = null): bool
    {
        return $this->update($paymentMethodId, [
            'is_active' => 0,
            'deleted_at' => date('Y-m-d H:i:s'),
            'deleted_by' => $deletedBy
        ]);
    }

    /**
     * Get payment method with full details
     */
    public function getWithDetails(int $paymentMethodId): ?array
    {
        return $this->select('student_payment_methods.*, providers.code as provider_code, providers.name as provider_name, provider_types.code as type_code')
            ->join('providers', 'providers.id = student_payment_methods.provider_id', 'left')
            ->join('provider_types', 'provider_types.id = providers.provider_type_id', 'left')
            ->where('student_payment_methods.id', $paymentMethodId)
            ->first();
    }
}
