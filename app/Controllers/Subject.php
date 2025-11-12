<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\API\ResponseTrait;

class Subject extends Controller
{
    use ResponseTrait;

    protected $subjectModel;
    protected $format = 'json';

    public function __construct()
    {
        $this->subjectModel = new \App\Models\V1\SubjectModel();
    }

    /**
     * Get list of subjects
     */
    public function list(): ResponseInterface
    {
        try {
            $data = $this->request->getJSON();

            $subjects = $this->subjectModel->getSubjects($data ?? []);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $subjects,
                'ErrorObject' => ''
            ]);

        } catch (\Exception $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ]);
        }
    }

    /**
     * Create a subject.
     */
    public function add(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON(true) ?? []);
            $fields = $this->subjectModel->getTableFields();
            $supportsSchool = in_array('school_id', $fields, true);

            $errors = [];
            $subjectName = trim((string) ($payload['subject_name'] ?? ''));
            if ($subjectName === '') {
                $errors[] = 'Subject name is required';
            }

            $schoolId = null;
            if ($supportsSchool) {
                $schoolId = isset($payload['school_id']) ? (int) $payload['school_id'] : 0;
                if ($schoolId <= 0) {
                    $errors[] = 'School is required';
                }
            }

            $status = isset($payload['status']) && $payload['status'] !== '' ? (int) $payload['status'] : 1;
            if ($status <= 0 && in_array('status', $fields, true)) {
                $errors[] = 'Status must be a positive value';
            }

            [$feeAmount, $feeError] = $this->parseMonetaryValue($payload['fee_amount'] ?? null, 'Fee amount');
            if ($feeError !== null) {
                $errors[] = $feeError;
            }

            [$depositAmount, $depositError] = $this->parseMonetaryValue($payload['deposit_amount'] ?? null, 'Deposit amount');
            if ($depositError !== null) {
                $errors[] = $depositError;
            }

            if ($errors) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => implode(', ', $errors)
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if ($this->subjectModel->subjectExists($subjectName, $supportsSchool ? $schoolId : null)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'A subject with the same name already exists'
                ], ResponseInterface::HTTP_CONFLICT);
            }

            $subjectData = [
                'subject_name' => $subjectName,
                'description' => $payload['description'] ?? null,
            ];

            if (in_array('status', $fields, true)) {
                $subjectData['status'] = $status;
            }

            if ($supportsSchool && $schoolId !== null) {
                $subjectData['school_id'] = $schoolId;
            }

            if (in_array('fee_amount', $fields, true)) {
                if ($feeAmount !== null) {
                    $subjectData['fee_amount'] = $feeAmount;
                } elseif (array_key_exists('fee_amount', $payload)) {
                    $subjectData['fee_amount'] = null;
                }
            }

            if (in_array('deposit_amount', $fields, true)) {
                if ($depositAmount !== null) {
                    $subjectData['deposit_amount'] = $depositAmount;
                } elseif (array_key_exists('deposit_amount', $payload)) {
                    $subjectData['deposit_amount'] = null;
                }
            }

            $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $timestamp = date('Y-m-d H:i:s');

            if ($userId !== null && in_array('created_by', $fields, true)) {
                $subjectData['created_by'] = $userId;
            }

            if (in_array('created_date', $fields, true)) {
                $subjectData['created_date'] = $timestamp;
            }

            if ($userId !== null && in_array('modified_by', $fields, true)) {
                $subjectData['modified_by'] = $userId;
            }

            if (in_array('modified_date', $fields, true)) {
                $subjectData['modified_date'] = $timestamp;
            }

            $subjectId = $this->subjectModel->createSubject($subjectData);

            if ($subjectId <= 0) {
                throw new \RuntimeException('Unable to create subject');
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => 'Subject created successfully',
                'ErrorObject' => ''
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an existing subject.
     */
    public function edit($id = null): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON(true) ?? []);
            $fields = $this->subjectModel->getTableFields();
            $supportsSchool = in_array('school_id', $fields, true);

            $subjectId = $id !== null ? (int) $id : (isset($payload['subject_id']) ? (int) $payload['subject_id'] : 0);
            if ($subjectId <= 0) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Subject ID is required'
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $existingSubject = $this->subjectModel->find($subjectId);
            if (!$existingSubject) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Subject not found'
                ], ResponseInterface::HTTP_NOT_FOUND);
            }

            $errors = [];
            $subjectName = trim((string) ($payload['subject_name'] ?? $existingSubject['subject_name'] ?? ''));
            if ($subjectName === '') {
                $errors[] = 'Subject name is required';
            }

            $schoolId = null;
            if ($supportsSchool) {
                if (isset($payload['school_id'])) {
                    $schoolId = (int) $payload['school_id'];
                } elseif (isset($existingSubject['school_id'])) {
                    $schoolId = (int) $existingSubject['school_id'];
                }

                if ($schoolId === null || $schoolId <= 0) {
                    $errors[] = 'School is required';
                }
            }

            $status = isset($payload['status']) && $payload['status'] !== '' ? (int) $payload['status'] : ($existingSubject['status'] ?? 1);
            if ($status <= 0 && in_array('status', $fields, true)) {
                $errors[] = 'Status must be a positive value';
            }

            [$feeAmount, $feeError] = $this->parseMonetaryValue($payload['fee_amount'] ?? $existingSubject['fee_amount'] ?? null, 'Fee amount');
            if ($feeError !== null) {
                $errors[] = $feeError;
            }

            [$depositAmount, $depositError] = $this->parseMonetaryValue($payload['deposit_amount'] ?? $existingSubject['deposit_amount'] ?? null, 'Deposit amount');
            if ($depositError !== null) {
                $errors[] = $depositError;
            }

            if ($errors) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => implode(', ', $errors)
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            if ($this->subjectModel->subjectExists($subjectName, $supportsSchool ? $schoolId : null, $subjectId)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'A subject with the same name already exists'
                ], ResponseInterface::HTTP_CONFLICT);
            }

            $subjectData = [
                'subject_name' => $subjectName,
                'description' => $payload['description'] ?? $existingSubject['description'] ?? null,
            ];

            if (in_array('status', $fields, true)) {
                $subjectData['status'] = $status;
            }

            if ($supportsSchool && $schoolId !== null) {
                $subjectData['school_id'] = $schoolId;
            }

            if (in_array('fee_amount', $fields, true)) {
                if ($feeAmount !== null) {
                    $subjectData['fee_amount'] = $feeAmount;
                } elseif (array_key_exists('fee_amount', $payload)) {
                    $subjectData['fee_amount'] = null;
                }
            }

            if (in_array('deposit_amount', $fields, true)) {
                if ($depositAmount !== null) {
                    $subjectData['deposit_amount'] = $depositAmount;
                } elseif (array_key_exists('deposit_amount', $payload)) {
                    $subjectData['deposit_amount'] = null;
                }
            }

            $userId = isset($payload['user_id']) ? (int) $payload['user_id'] : null;
            $timestamp = date('Y-m-d H:i:s');

            if ($userId !== null && in_array('modified_by', $fields, true)) {
                $subjectData['modified_by'] = $userId;
            }

            if (in_array('modified_date', $fields, true)) {
                $subjectData['modified_date'] = $timestamp;
            }

            $updated = $this->subjectModel->updateSubject($subjectId, $subjectData);

            if (!$updated) {
                throw new \RuntimeException('Unable to update subject');
            }

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => 'Subject updated successfully',
                'ErrorObject' => ''
            ]);
        } catch (\Throwable $e) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage()
            ], ResponseInterface::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Normalize monetary values to a standard 2-decimal string.
     *
     * @return array{0: ?string, 1: ?string} [value, error]
     */
    private function parseMonetaryValue($value, string $label): array
    {
        if ($value === null || $value === '') {
            return [null, null];
        }

        if (is_string($value)) {
            $value = trim($value);
        }

        $sanitized = is_numeric($value) ? (string) $value : preg_replace('/[^0-9.\-]/', '', (string) $value);

        if ($sanitized === '' || !is_numeric($sanitized)) {
            return [null, $label . ' must be a valid number'];
        }

        return [number_format((float) $sanitized, 2, '.', ''), null];
    }
}
