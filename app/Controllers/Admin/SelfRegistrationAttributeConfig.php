<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\SchoolRegistrationAttributeConfigModel;
use CodeIgniter\HTTP\ResponseInterface;

class SelfRegistrationAttributeConfig extends BaseController
{
    protected $model;

    public function __construct()
    {
        $this->model = new SchoolRegistrationAttributeConfigModel();
    }

    public function load(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? [];
        $schoolId = (int) ($payload['school_id'] ?? 0);

        if ($schoolId <= 0) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'school_id is required'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $row = $this->model->where('school_id', $schoolId)->first();
        $sections = [];

        if (!empty($row['definition'])) {
            $decoded = json_decode($row['definition'], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $sections = $decoded;
            }
        }

        return $this->respond([
            'IsSuccess' => true,
            'ResponseObject' => ['sections' => $sections],
            'ErrorObject' => null
        ]);
    }

    public function save(): ResponseInterface
    {
        $payload = $this->request->getJSON(true) ?? [];
        $schoolId = (int) ($payload['school_id'] ?? 0);
        $sections = $payload['sections'] ?? [];

        if ($schoolId <= 0) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'school_id is required'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $definition = json_encode($sections, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($definition === false) {
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => 'Unable to encode sections payload'
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }

        $existing = $this->model->where('school_id', $schoolId)->first();
        if ($existing) {
            $this->model->update($existing['id'], ['definition' => $definition]);
        } else {
            $this->model->insert([
                'school_id' => $schoolId,
                'definition' => $definition
            ]);
        }

        return $this->respond([
            'IsSuccess' => true,
            'ResponseObject' => ['sections' => $sections],
            'ErrorObject' => null
        ]);
    }
}

