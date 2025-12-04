<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class ReportCardTemplateModel extends BaseModel
{
    protected $table = 't_rc_template';
    protected $primaryKey = 'template_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'name',
        'version',
        'is_active',
        'schema_json',
        'created_by',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    protected $validationRules = [
        'school_id' => 'required|integer',
        'name' => 'required|max_length[120]',
        'version' => 'required|integer',
        'schema_json' => 'required',
        'created_by' => 'required|integer',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'name' => [
            'required' => 'Template name is required',
            'max_length' => 'Template name cannot exceed 120 characters',
        ],
    ];

    /**
     * Get active templates for a school
     */
    public function getActiveTemplates($schoolId, $limit = null, $offset = 0)
    {
        $builder = $this->where('school_id', $schoolId)
                       ->where('is_active', 1)
                       ->orderBy('name', 'ASC');

        if ($limit) {
            $builder->limit($limit, $offset);
        }

        return $builder->findAll();
    }

    /**
     * Get template with specific version
     */
    public function getTemplateVersion($templateId, $version = null)
    {
        $builder = $this->where('template_id', $templateId);

        if ($version !== null) {
            $builder->where('version', $version);
        } else {
            $builder->orderBy('version', 'DESC')->limit(1);
        }

        return $builder->first();
    }

    /**
     * Get all versions of a template
     */
    public function getTemplateVersions($templateId, $schoolId)
    {
        return $this->where('template_id', $templateId)
                    ->where('school_id', $schoolId)
                    ->orderBy('version', 'DESC')
                    ->findAll();
    }

    /**
     * Create new version of template
     */
    public function createNewVersion($templateId, $schoolId, $schemaJson, $userId)
    {
        // Get current max version
        $currentTemplate = $this->where('template_id', $templateId)
                                ->where('school_id', $schoolId)
                                ->orderBy('version', 'DESC')
                                ->first();

        if (!$currentTemplate) {
            return false;
        }

        $newVersion = $currentTemplate['version'] + 1;

        $data = [
            'school_id' => $schoolId,
            'name' => $currentTemplate['name'],
            'version' => $newVersion,
            'is_active' => 1,
            'schema_json' => $schemaJson,
            'created_by' => $userId,
        ];

        return $this->insert($data);
    }
}
