<?php

namespace App\Models;

use App\Models\V1\BaseModel;

class RequestConversationModel extends BaseModel
{
    protected $table = 't_request_conversation';
    protected $primaryKey = 'conversation_id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields = [
        'school_id',
        'request_type',
        'request_id',
        'author_id',
        'author_role_id',
        'message',
        'is_internal',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = null;

    protected $validationRules = [
        'school_id' => 'required|integer',
        'request_type' => 'required|in_list[profile_change,absence,special_request,document]',
        'request_id' => 'required|integer',
        'author_id' => 'required|integer',
        'author_role_id' => 'required|integer',
        'message' => 'required',
    ];

    protected $validationMessages = [
        'school_id' => [
            'required' => 'School ID is required',
        ],
        'message' => [
            'required' => 'Message is required',
        ],
    ];

    /**
     * Get conversation for a request
     */
    public function getConversation($requestType, $requestId, $includeInternal = false)
    {
        $builder = $this->where('request_type', $requestType)
                       ->where('request_id', $requestId);

        if (!$includeInternal) {
            $builder->where('is_internal', 0);
        }

        return $builder->orderBy('created_at', 'ASC')
                      ->findAll();
    }

    /**
     * Add message to conversation
     */
    public function addMessage($schoolId, $requestType, $requestId, $authorId, $authorRoleId, $message, $isInternal = false)
    {
        $data = [
            'school_id' => $schoolId,
            'request_type' => $requestType,
            'request_id' => $requestId,
            'author_id' => $authorId,
            'author_role_id' => $authorRoleId,
            'message' => $message,
            'is_internal' => $isInternal ? 1 : 0,
        ];

        return $this->insert($data);
    }

    /**
     * Count messages for a request
     */
    public function countMessages($requestType, $requestId, $includeInternal = false)
    {
        $builder = $this->where('request_type', $requestType)
                       ->where('request_id', $requestId);

        if (!$includeInternal) {
            $builder->where('is_internal', 0);
        }

        return $builder->countAllResults();
    }
}
