<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class BookModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'content';
    protected $allowedFields = [
        'content_id',
        'name',
        'description',
        'publication_code',
        'download',
        'corporate_id',
        'grade',
        'subject',
        'school_id',
        'content_type',
        'access',
        'status',
        'links',
        'answerkey_path',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function bookList(array $params): array
    {
        $builder = $this->getBuilder('c');
        $builder->select('c.content_id, c.name, c.publication_code, c.description, c.download, c.corporate_id,
                         COALESCE(c.grade, "") as grade, COALESCE(c.subject, "") as subject, c.school_id,
                         COALESCE((SELECT GROUP_CONCAT(grade_name) FROM grade WHERE FIND_IN_SET(grade_id, c.grade)), "") as grade_name,
                         COALESCE((SELECT GROUP_CONCAT(subject_name) FROM subject WHERE FIND_IN_SET(subject_id, c.subject)), "") as subject_name,
                         (SELECT name FROM school WHERE school_id = c.school_id) as school_name,
                         c.content_type, c.access, c.status, c.links, c.answerkey_path');

        if ($params['role_id'] == 6) {
            // Corporate user access
            $builder->groupStart()
                    ->where('c.access', 3)
                    ->where('c.publication_code !=', 1)
                    ->orWhere('c.created_by', $params['user_id'])
                    ->where('c.publication_code !=', 1);

            if (isset($params['corporate_id']) && $params['corporate_id'] > 0) {
                $builder->orWhereIn('c.school_id', function($query) use ($params) {
                    $query->select('school_id')
                          ->from('school')
                          ->where('branch_name', $params['corporate_id']);
                });
            }
        } elseif ($params['role_id'] == 2) {
            // School admin access
            $builder->where('c.school_id', $params['school_id'])
                    ->groupStart()
                    ->whereIn('c.access', [1])
                    ->orWhere('c.created_by', $params['user_id'])
                    ->where('c.access', 2)
                    ->groupEnd()
                    ->where('c.publication_code !=', 1);

            $schoolQuery = $this->getBuilder('school')
                               ->select('branch_name')
                               ->where('school_id', $params['school_id'])
                               ->get()
                               ->getResultArray();

            if ($schoolQuery[0]['branch_name'] > 0) {
                $builder->orWhereIn('c.corporate_id', function($query) use ($params) {
                    $query->select('corporate_id')
                          ->from('corporate_request')
                          ->where('school_id', $params['school_id'])
                          ->where('status', 1)
                          ->where('validity >=', 'CURRENT_DATE()');
                })->where('c.publication_code !=', 1);
            }
        } elseif (in_array($params['role_id'], [3, 4])) {
            // Teacher/Student access
            $builder->where('c.school_id', $params['school_id'])
                    ->whereIn('c.access', [1, 3])
                    ->orWhere('c.created_by', $params['user_id'])
                    ->where('c.access', 2)
                    ->where('c.publication_code !=', 1);

            $schoolQuery = $this->getBuilder('school')
                               ->select('branch_name')
                               ->where('school_id', $params['school_id'])
                               ->get()
                               ->getResultArray();

            if ($schoolQuery[0]['branch_name'] > 0) {
                $builder->orWhereIn('c.corporate_id', function($query) use ($params) {
                    $query->select('corporate_id')
                          ->from('corporate_request')
                          ->where('school_id', $params['school_id'])
                          ->where('status', 1)
                          ->where('validity >=', 'CURRENT_DATE()');
                })->where('c.publication_code !=', 1);
            }
        }

        $builder->orderBy('c.content_id', 'DESC');
        return $this->getResult($builder);
    }

    public function checkBookId(int $id, int $schoolId): array
    {
        $builder = $this->getBuilder();
        $builder->select('content_id')
                ->where('content_id', $id)
                ->where('school_id', $schoolId);

        return $this->getResult($builder);
    }

    public function checkBook(array $data): array
    {
        $builder = $this->getBuilder();
        $builder->select('content_id, status')
                ->where('name', $data['name'])
                ->where('school_id', $data['school_id']);

        return $this->getResult($builder);
    }

    public function getContentList(string $condition): array
    {
        $builder = $this->getBuilder();
        $builder->select('content_id, name, download, content_format, content_type')
                ->where($condition);

        return $this->getResult($builder);
    }
} 