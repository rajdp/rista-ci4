<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class CronModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'cron_jobs';
    protected $allowedFields = [
        'job_id',
        'job_name',
        'job_type',
        'status',
        'last_run',
        'next_run',
        'created_date',
        'modified_date'
    ];

    private $settings = [];

    public function __construct()
    {
        parent::__construct();
        date_default_timezone_set("Asia/Calcutta");
        
        // Load admin settings
        $adminSettings = $this->adminSettings('');
        foreach ($adminSettings as $key => $details) {
            $this->settings[$details['setting_name']] = $details['setting_value'];
        }
    }

    public function adminSettings($name)
    {
        $builder = $this->getBuilder('admin_settings');
        $builder->select('id as setting_id, value as setting_value, name as setting_name');
        $builder->where('status', 1);
        
        if ($name != '') {
            $builder->where('name', $name);
            return $this->getResult($builder);
        }
        
        return $this->getResult($builder);
    }

    public function autoCorrection()
    {
        $builder = $this->getBuilder('student_content');
        $builder->select('class_id, student_id, content_id');
        $builder->where('status', 4);
        return $this->getResult($builder);
    }

    public function fileConversion()
    {
        $builder = $this->getBuilder('content');
        $builder->select('file_path');
        return $this->getResult($builder);
    }

    public function notifyParents()
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, u.email_id, up.first_name, up.last_name');
        $builder->join('user u', 'sc.student_id = u.user_id', 'left');
        $builder->join('user_profile up', 'sc.student_id = up.user_id', 'left');
        $builder->where('sc.status', 1);
        return $this->getResult($builder);
    }

    public function contentOverDueEmail()
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.content_name, c.due_date');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->where('sc.status', 0);
        $builder->where('c.due_date <', date('Y-m-d H:i:s'));
        return $this->getResult($builder);
    }

    public function emailNotification()
    {
        $builder = $this->getBuilder('email_notifications');
        $builder->select('*');
        $builder->where('status', 0);
        return $this->getResult($builder);
    }

    public function adminEmailInsert($data)
    {
        return $this->insert('admin_emails', $data);
    }

    public function adminEmailNotify()
    {
        $builder = $this->getBuilder('admin_emails');
        $builder->select('*');
        $builder->where('status', 0);
        return $this->getResult($builder);
    }

    public function edquillRegistrationMail()
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.*, up.first_name, up.last_name');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.role_id', 2); // Assuming 2 is teacher role
        $builder->where('u.status', 1);
        return $this->getResult($builder);
    }

    public function studentPlatformWiseAnswerReport()
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.content_name, c.content_type');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->where('sc.status', 1);
        return $this->getResult($builder);
    }

    public function uploadAnswerExcelMail()
    {
        $builder = $this->getBuilder('answer_excel_uploads');
        $builder->select('*');
        $builder->where('status', 0);
        return $this->getResult($builder);
    }

    public function sendAttachment($subject, $emailid, $attachment, $message, $fromEmailId, $fromPassword)
    {
        $email = Services::email();
        $email->setFrom($fromEmailId, 'System');
        $email->setTo($emailid);
        $email->setSubject($subject);
        $email->setMessage($message);
        $email->attach($attachment);
        return $email->send();
    }

    public function belowScoreReport()
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.content_name, c.passing_score');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->where('sc.score < c.passing_score');
        return $this->getResult($builder);
    }

    public function futureClassShift()
    {
        $builder = $this->getBuilder('class c');
        $builder->select('c.*, cs.teacher_id');
        $builder->join('class_schedule cs', 'c.class_id = cs.class_id', 'left');
        $builder->where('c.start_date >', date('Y-m-d H:i:s'));
        return $this->getResult($builder);
    }

    public function studentUpgrade($params)
    {
        $builder = $this->getBuilder('student_grade sg');
        $builder->select('sg.*, g.next_grade_id');
        $builder->join('grade g', 'sg.grade_id = g.grade_id', 'left');
        $builder->where('sg.school_id', $params['school_id']);
        return $this->getResult($builder);
    }

    public function dayWiseReport()
    {
        $builder = $this->getBuilder('student_content sc');
        $builder->select('sc.*, c.content_name, c.content_type');
        $builder->join('content c', 'sc.content_id = c.content_id', 'left');
        $builder->where('sc.created_date >=', date('Y-m-d 00:00:00'));
        return $this->getResult($builder);
    }

    public function weekOfMonth($date)
    {
        $firstOfMonth = strtotime(date('Y-m-01', strtotime($date)));
        return intval(date('W', strtotime($date))) - intval(date('W', $firstOfMonth)) + 1;
    }

    public function searchForId($id1, $id2, $array)
    {
        foreach ($array as $key => $val) {
            if ($val['id1'] === $id1 && $val['id2'] === $id2) {
                return $key;
            }
        }
        return null;
    }

    public function studentAddLimitNotification()
    {
        $builder = $this->getBuilder('school s');
        $builder->select('s.*, COUNT(u.user_id) as student_count');
        $builder->join('user u', 's.school_id = u.school_id AND u.role_id = 5', 'left');
        $builder->groupBy('s.school_id');
        $builder->having('student_count >= s.student_limit');
        return $this->getResult($builder);
    }

    public function contentRemoved_get()
    {
        $builder = $this->getBuilder('content_removed');
        $builder->select('*');
        $builder->where('status', 0);
        return $this->getResult($builder);
    }

    public function getClassContent($fromDate, $toDate)
    {
        $builder = $this->getBuilder('class_content cc');
        $builder->select('cc.*, c.content_name');
        $builder->join('content c', 'cc.content_id = c.content_id', 'left');
        $builder->where('cc.created_date >=', $fromDate);
        $builder->where('cc.created_date <=', $toDate);
        return $this->getResult($builder);
    }

    public function getAllAnnotations()
    {
        $builder = $this->getBuilder('content_annotations');
        $builder->select('*');
        return $this->getResult($builder);
    }

    public function updateContentAnnotation($data)
    {
        return $this->update('content_annotations', $data, ['annotation_id' => $data['annotation_id']]);
    }

    public function studentWorkareaAnnotation()
    {
        $builder = $this->getBuilder('student_workarea_annotations');
        $builder->select('*');
        return $this->getResult($builder);
    }

    public function teacherClassAnnotation()
    {
        $builder = $this->getBuilder('teacher_class_annotations');
        $builder->select('*');
        return $this->getResult($builder);
    }

    public function updateContentLinks($data)
    {
        return $this->update('content_links', $data, ['link_id' => $data['link_id']]);
    }

    public function removeInactiveStudents()
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.*');
        $builder->where('u.role_id', 5); // Assuming 5 is student role
        $builder->where('u.status', 0);
        return $this->getResult($builder);
    }

    public function deleteCompletedClass()
    {
        $builder = $this->getBuilder('class c');
        $builder->select('c.*');
        $builder->where('c.end_date <', date('Y-m-d H:i:s'));
        $builder->where('c.status', 1);
        return $this->getResult($builder);
    }

    public function inboxCron()
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.*');
        $builder->where('m.status', 0);
        return $this->getResult($builder);
    }
} 