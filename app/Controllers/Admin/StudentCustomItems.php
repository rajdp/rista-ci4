<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentCustomItemModel;
use App\Traits\RestTrait;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Student Custom Items Controller
 * Manages promo items, discounts, and additional charges at student level
 */
class StudentCustomItems extends BaseController
{
    use RestTrait;

    protected StudentCustomItemModel $customItemModel;

    public function __construct()
    {
        $this->customItemModel = new StudentCustomItemModel();
    }

    /**
     * Get all custom items for a student
     */
    public function list(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Check if table exists before querying
            $db = \Config\Database::connect();
            if (!$db->tableExists('student_custom_items')) {
                log_message('error', 'StudentCustomItems::list - Table student_custom_items does not exist');
                // Return empty array instead of error if table doesn't exist
                return $this->successResponse([
                    'student_id' => $studentId,
                    'items' => [],
                    'total' => 0
                ]);
            }

            $items = $this->customItemModel->getAllItemsForStudent($studentId, $schoolId);

            return $this->successResponse([
                'student_id' => $studentId,
                'items' => $items,
                'total' => count($items)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItems::list - ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            log_message('error', 'Request payload: ' . json_encode($payload));
            log_message('error', 'Student ID: ' . $studentId);
            // Return more detailed error in development, generic in production
            $errorMessage = ENVIRONMENT === 'development' 
                ? 'Unable to load custom items: ' . $e->getMessage() 
                : 'Unable to load custom items';
            return $this->errorResponse($errorMessage);
        }
    }

    /**
     * Get active custom items for a student (for invoice/calculation purposes)
     */
    public function getActive(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);
            $date = $payload['date'] ?? date('Y-m-d');

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $items = $this->customItemModel->getActiveItemsForStudent($studentId, $schoolId, $date);
            $totalAmount = $this->customItemModel->getTotalAmountForStudent($studentId, $schoolId, $date);

            return $this->successResponse([
                'student_id' => $studentId,
                'date' => $date,
                'items' => $items,
                'total_amount' => $totalAmount,
                'count' => count($items)
            ]);

        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItems::getActive - ' . $e->getMessage());
            return $this->errorResponse('Unable to load active custom items');
        }
    }

    /**
     * Add a new custom item
     */
    public function add(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $studentId = (int) ($payload['student_id'] ?? 0);
            $description = trim($payload['description'] ?? '');
            $amount = $payload['amount'] ?? null;
            $startDate = $payload['start_date'] ?? null;
            $endDate = $payload['end_date'] ?? null;

            if ($studentId <= 0) {
                return $this->errorResponse('student_id is required');
            }

            if (empty($description)) {
                return $this->errorResponse('description is required');
            }

            if ($amount === null || $amount === '') {
                return $this->errorResponse('amount is required');
            }

            if (empty($startDate)) {
                return $this->errorResponse('start_date is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            $userId = (int) $this->getUserId($token);

            // Validate end_date is after start_date if provided
            // Also validate that start_date is not in the past (using current date)
            $currentDate = date('Y-m-d');
            if ($startDate < $currentDate) {
                return $this->errorResponse('start_date cannot be in the past');
            }
            if (!empty($endDate) && $endDate < $startDate) {
                return $this->errorResponse('end_date must be after or equal to start_date');
            }

            $data = [
                'student_id' => $studentId,
                'school_id' => $schoolId,
                'description' => $description,
                'amount' => (float)$amount,
                'start_date' => $startDate,
                'end_date' => !empty($endDate) ? $endDate : null,
                'is_active' => 1,
                'created_by' => $userId
            ];

            $id = $this->customItemModel->insert($data);

            if (!$id) {
                $errors = $this->customItemModel->errors();
                return $this->errorResponse('Failed to create custom item: ' . implode(', ', $errors));
            }

            $item = $this->customItemModel->find($id);

            return $this->successResponse([
                'id' => $id,
                'item' => $item
            ], 'Custom item added successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItems::add - ' . $e->getMessage());
            return $this->errorResponse('Unable to add custom item');
        }
    }

    /**
     * Update a custom item
     */
    public function update($id = null): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $itemId = $id ? (int)$id : (int) ($payload['id'] ?? 0);

            if ($itemId <= 0) {
                return $this->errorResponse('id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Verify item exists and belongs to this school
            $item = $this->customItemModel->find($itemId);
            if (!$item) {
                return $this->errorResponse('Custom item not found');
            }

            if ($item['school_id'] != $schoolId) {
                return $this->errorResponse('Unauthorized access to this custom item');
            }

            // Build update data
            $updateData = [];
            if (isset($payload['description'])) {
                $updateData['description'] = trim($payload['description']);
            }
            if (isset($payload['amount'])) {
                $updateData['amount'] = (float)$payload['amount'];
            }
            if (isset($payload['start_date'])) {
                $updateData['start_date'] = $payload['start_date'];
            }
            if (isset($payload['end_date'])) {
                // Handle end_date - accept null, empty string, or valid date
                $endDateValue = $payload['end_date'];
                if ($endDateValue === null || $endDateValue === 'null' || $endDateValue === '') {
                    $updateData['end_date'] = null;
                } else {
                    $updateData['end_date'] = $endDateValue;
                }
            }
            if (isset($payload['is_active'])) {
                $updateData['is_active'] = (int)$payload['is_active'];
            }

            if (empty($updateData)) {
                return $this->errorResponse('No fields to update');
            }

            // Validate end_date is after start_date if both are being updated
            // Also validate that start_date is not in the past (using current date)
            $currentDate = date('Y-m-d');
            $startDate = $updateData['start_date'] ?? $item['start_date'];
            $endDate = $updateData['end_date'] ?? $item['end_date'];
            if (isset($updateData['start_date']) && $startDate < $currentDate) {
                return $this->errorResponse('start_date cannot be in the past');
            }
            if (!empty($endDate) && $endDate < $startDate) {
                return $this->errorResponse('end_date must be after or equal to start_date');
            }

            $updated = $this->customItemModel->update($itemId, $updateData);

            if (!$updated) {
                $errors = $this->customItemModel->errors();
                return $this->errorResponse('Failed to update custom item: ' . implode(', ', $errors));
            }

            $updatedItem = $this->customItemModel->find($itemId);

            return $this->successResponse([
                'id' => $itemId,
                'item' => $updatedItem
            ], 'Custom item updated successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItems::update - ' . $e->getMessage());
            return $this->errorResponse('Unable to update custom item');
        }
    }

    /**
     * Delete a custom item
     */
    public function delete($id = null): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $itemId = $id ? (int)$id : (int) ($payload['id'] ?? 0);

            if ($itemId <= 0) {
                return $this->errorResponse('id is required');
            }

            $token = $this->validateToken();
            if (!$token) {
                return $this->unauthorizedResponse('Access token required');
            }

            $schoolId = (int) $this->getSchoolId($token);
            if (!$schoolId) {
                return $this->errorResponse('School ID not found');
            }

            // Verify item exists and belongs to this school
            $item = $this->customItemModel->find($itemId);
            if (!$item) {
                return $this->errorResponse('Custom item not found');
            }

            if ($item['school_id'] != $schoolId) {
                return $this->errorResponse('Unauthorized access to this custom item');
            }

            $deleted = $this->customItemModel->delete($itemId);

            if (!$deleted) {
                return $this->errorResponse('Failed to delete custom item');
            }

            return $this->successResponse(null, 'Custom item deleted successfully');

        } catch (\Throwable $e) {
            log_message('error', 'StudentCustomItems::delete - ' . $e->getMessage());
            return $this->errorResponse('Unable to delete custom item');
        }
    }
}

