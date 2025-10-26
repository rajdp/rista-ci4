<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class FeedbackModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'student_content_feedback';
    protected $allowedFields = [
        'id',
        'content_id',
        'class_id',
        'student_id',
        'notes',
        'notes_type',
        'created_date',
        'created_by',
        'school_id'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function feedbackList(array $data): array
    {
        $builder = $this->getBuilder();
        $builder->select('s.id, s.content_id, COALESCE(c.name, "") as content_name, cl.class_name, s.class_id, s.student_id, s.notes, 
                         s.notes_type, DATE_FORMAT(s.created_date, "%m-%d-%Y") as created_date, COALESCE(c.content_type, "") as content_type,
                         (SELECT CONCAT_WS(" ", first_name, last_name) FROM user_profile WHERE user_id = s.created_by) AS created_by')
                ->join('content c', 's.content_id = c.content_id', 'left')
                ->join('class cl', 's.class_id = cl.class_id', 'left');

        if ($data['class_id'] > 0 && $data['content_id'] > 0 && $data['student_id'] > 0) {
            $builder->where('s.content_id', $data['content_id'])
                    ->where('s.class_id', $data['class_id'])
                    ->where('s.student_id', $data['student_id'])
                    ->where('s.school_id', $data['school_id']);
        } elseif ($data['class_id'] > 0 && $data['student_id'] > 0) {
            $builder->where('s.class_id', $data['class_id'])
                    ->where('s.student_id', $data['student_id'])
                    ->where('s.school_id', $data['school_id']);
        } elseif ($data['student_id'] > 0 && $data['school_id'] > 0) {
            $builder->where('s.school_id', $data['school_id'])
                    ->where('s.student_id', $data['student_id']);
        }

        $builder->orderBy('s.created_date', 'DESC');

        return $this->getResult($builder);
    }

    public function getStudentParentEmail(array $params): array
    {
        $builder = $this->getBuilder('user');
        $builder->select('u.email_id, COALESCE(ua.email_ids, "") AS email_ids')
                ->join('user_address ua', 'u.user_id = ua.user_id', 'left')
                ->where('u.user_id', $params['student_id']);

        return $this->getResult($builder);
    }

    public function getContentName(array $params): array
    {
        $builder = $this->getBuilder('content');
        $builder->select('name')
                ->where('content_id', $params['content_id']);

        return $this->getRow($builder);
    }

    public function studentFeedbackList(array $data): array
    {
        $builder = $this->getBuilder();
        $builder->select('id, content_id, class_id, student_id, notes, created_date')
                ->where('student_id', $data['student_id'])
                ->orderBy('created_date', 'DESC');

        return $this->getResult($builder);
    }
} 