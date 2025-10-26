<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;

class MigrationModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'batch';
    protected $allowedFields = [
        'batch_id',
        'school_id',
        'batch_name',
        'edquill_batch_id'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function checkBatchExist(array $params, string $batchName): array
    {
        $builder = $this->getBuilder();
        $builder->select('batch_id')
                ->where('school_id', $params['school_id'])
                ->where('batch_name', $batchName);

        return $this->getResult($builder);
    }

    public function getTeacherName(int $edquillTeacherId): array
    {
        $builder = $this->getBuilder('u');
        $builder->select('u.user_id, CONCAT_WS("", first_name, last_name) as name')
                ->join('user_profile as up', 'up.user_id = u.user_id', 'left')
                ->where('edquill_teacher_id', $edquillTeacherId);

        return $this->getResult($builder);
    }

    public function checkTeacherExists(int $teacherEdquillId): array
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id')
                ->where('edquill_teacher_id', $teacherEdquillId);

        return $this->getResult($builder);
    }

    public function checkBatchExists(int $batchId, int $schoolId): array
    {
        $builder = $this->getBuilder();
        $builder->select('batch_name, batch_id')
                ->where('edquill_batch_id', $batchId)
                ->where('school_id', $schoolId);

        return $this->getResult($builder);
    }

    public function getUserDetail(string $emailId): array
    {
        $builder = $this->getBuilder('u');
        $builder->select('u.user_id, up.first_name, up.last_name')
                ->join('user_profile as up', 'up.user_id = u.user_id', 'left')
                ->where('u.email_id', $emailId)
                ->where('u.role_id', 5);

        return $this->getResult($builder);
    }

    public function getUser(string $data): array
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id')
                ->where('student_id', $data);

        return $this->getResult($builder);
    }

    public function getClass(string $data): array
    {
        $builder = $this->getBuilder('class');
        $builder->select('class_id')
                ->where('edquill_schedule_id', $data);

        return $this->getResult($builder);
    }

    public function checkBook(int $bookId, int $schoolId): array
    {
        $builder = $this->getBuilder('book');
        $builder->select('book_id')
                ->where('edquill_book_id', $bookId)
                ->where('school_id', $schoolId);

        return $this->getResult($builder);
    }

    public function getClassId(string $data): array
    {
        $builder = $this->getBuilder('class');
        $builder->select('class_id')
                ->where('edquill_schedule_id', $data);

        return $this->getResult($builder);
    }

    public function studentExists(int $studentId, int $classId): array
    {
        $builder = $this->getBuilder('class_student');
        $builder->select('*')
                ->where('student_id', $studentId)
                ->where('class_id', $classId);

        return $this->getResult($builder);
    }

    public function checkClassContent(int $classId, int $contentId, string $startDate, string $endDate): array
    {
        $builder = $this->getBuilder('class_content');
        $builder->select('*')
                ->where('class_id', $classId)
                ->where('content_id', $contentId)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate);

        return $this->getResult($builder);
    }

    public function getClassStudents(int $classId): array
    {
        $builder = $this->getBuilder('class_student');
        $builder->select('student_id')
                ->where('class_id', $classId);

        return $this->getResult($builder);
    }

    public function checkStudentContent(int $classId, int $studentId, int $contentId, string $startDate, string $endDate): array
    {
        $builder = $this->getBuilder('student_content');
        $builder->select('*')
                ->where('class_id', $classId)
                ->where('student_id', $studentId)
                ->where('content_id', $contentId)
                ->where('start_date', $startDate)
                ->where('end_date', $endDate);

        return $this->getResult($builder);
    }

    public function checkStudentEmail(string $emailId): array
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id')
                ->where('email_id', $emailId);

        return $this->getResult($builder);
    }

    public function checkSubjectExists(string $subjectName, int $schoolId): array
    {
        $builder = $this->getBuilder('subject');
        $builder->select('subject_id')
                ->where('subject_name', $subjectName)
                ->where('school_id', $schoolId);

        return $this->getResult($builder);
    }

    public function getClassIdBatch(string $data): array
    {
        $builder = $this->getBuilder('class');
        $builder->select('class_id')
                ->where('edquill_schedule_id', $data);

        return $this->getResult($builder);
    }

    public function getQuestions(int $class_id, int $content_id): array
    {
        $builder = $this->getBuilder('questions');
        $builder->select('*')
                ->where('class_id', $class_id)
                ->where('content_id', $content_id);

        return $this->getResult($builder);
    }

    public function getContentQuestions(int $contentId): array
    {
        $builder = $this->getBuilder('questions');
        $builder->select('*')
                ->where('content_id', $contentId);

        return $this->getResult($builder);
    }

    public function getStudentNames(int $class_id): array
    {
        $builder = $this->getBuilder('cs');
        $builder->select('u.user_id, CONCAT_WS(" ", up.first_name, up.last_name) as name')
                ->join('user as u', 'u.user_id = cs.student_id')
                ->join('user_profile as up', 'up.user_id = u.user_id')
                ->where('cs.class_id', $class_id);

        return $this->getResult($builder);
    }

    public function getQuestionAnswerStatus(int $question_no, int $student_id, int $content_id, int $class_id): array
    {
        $builder = $this->getBuilder('student_question_answer');
        $builder->select('*')
                ->where('question_no', $question_no)
                ->where('student_id', $student_id)
                ->where('content_id', $content_id)
                ->where('class_id', $class_id);

        return $this->getResult($builder);
    }
} 