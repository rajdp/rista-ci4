<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class MailboxModel extends BaseModel
{
    protected $table = 'mailbox';
    protected $allowedFields = [
        'message_id',
        'parent_message_id',
        'from_id',
        'to_id',
        'cc',
        'subject',
        'body',
        'status',
        'created_date'
    ];

    public function getMailList($params)
    {
        $builder = $this->getBuilder('mailbox_details md');
        $builder->select('m.message_id, m.parent_message_id, m.from_id, m.to_id, m.subject, m.body, m.status, m.created_date');
        $builder->select('md.user_id, md.is_read, md.is_starred, md.is_trash');
        $builder->select('COALESCE(ma.attachment, "") as attachment');
        $builder->join('mailbox m', 'm.message_id = md.message_id', 'left');
        $builder->join('mailbox_attachment ma', 'm.message_id = ma.message_id', 'left');

        if (isset($params['type']) && $params['type'] != '') {
            switch ($params['type']) {
                case 'all':
                    $builder->where('md.user_id', $params['user_id']);
                    break;
                case 'starred':
                    $builder->where('md.user_id', $params['user_id']);
                    $builder->where('md.is_starred', 1);
                    break;
                case 'sent':
                    $builder->where('m.from_id', $params['user_id']);
                    $builder->where('m.status !=', 1);
                    break;
                case 'draft':
                    $builder->where('m.from_id', $params['user_id']);
                    $builder->where('m.status', 1);
                    break;
                case 'trash':
                    $builder->where('md.user_id', $params['user_id']);
                    $builder->where('md.is_trash', 1);
                    break;
            }
        }

        return $this->getResult($builder);
    }

    public function mailDetails($msgId, $parentMsgId)
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.message_id, m.parent_message_id, m.from_id, m.to_id, m.cc, m.subject, m.body, m.status, m.created_date');

        if (is_null($parentMsgId)) {
            $builder->where('m.message_id', $msgId);
            $builder->orWhere('m.parent_message_id', $msgId);
        } else {
            $builder->where('m.message_id', $parentMsgId);
            $builder->orWhere('m.parent_message_id', $parentMsgId);
        }

        $builder->orderBy('m.created_date');
        return $this->getResult($builder);
    }

    public function getParentMsg($msgId)
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.message_id, m.parent_message_id, m.from_id, m.to_id, m.subject, m.body, m.status, m.created_date');
        $builder->select('ma.attachment');
        $builder->join('mailbox_attachment ma', 'm.message_id = ma.message_id', 'left');
        $builder->where('m.message_id', $msgId);
        return $this->getResult($builder);
    }

    public function mailList($params)
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.message_id, m.parent_message_id, m.from_id, m.to_id, m.subject, m.body, m.status, m.created_date');
        $builder->select('ma.attachment');
        $builder->select('u.email_id as sender_email');
        $builder->select("CONCAT_WS(' ', up.first_name, up.last_name) AS sender_name");

        if ($params['type'] == 'sent' || $params['type'] == 'draft') {
            $builder->join('user u', 'u.user_id = m.from_id', 'left');
            $builder->join('mailbox_attachment ma', 'm.message_id = ma.message_id', 'left');
            $builder->join('user_profile up', 'up.user_id = m.from_id', 'left');
            $builder->where('m.from_id', $params['user_id']);

            if ($params['type'] == 'draft') {
                $builder->where('m.status', 1);
            }
        } else {
            $builder->join('user u', 'u.user_id = m.from_id', 'left');
            $builder->join('user_profile up', 'up.user_id = m.from_id', 'left');
            $builder->join('mailbox_details md', 'm.message_id = md.message_id', 'left');
            $builder->where('md.user_id', $params['user_id']);

            switch ($params['type']) {
                case 'all':
                    $builder->where('md.is_trash !=', 1);
                    break;
                case 'trash':
                    $builder->where('md.is_trash', 1);
                    break;
                case 'starred':
                    $builder->where('md.is_starred', 1);
                    break;
                case 'read':
                    $builder->where('md.is_read', 1);
                    break;
            }

            $builder->select('md.is_read, md.is_trash, md.is_starred');
            $builder->select("(SELECT GROUP_CONCAT(attachment) FROM mailbox_attachment WHERE message_id = m.message_id) as attachment");
        }

        return $this->getResult($builder);
    }

    public function getClassName($params)
    {
        $builder = $this->getBuilder('class');
        $builder->select('class_id, class_name');
        $builder->where('class_id', $params['class_id']);
        return $this->getResult($builder);
    }

    public function getSearchUser($params)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.role_id, u.email_id');
        $builder->select("CONCAT_WS(' ', up.first_name, up.last_name) as user_name");
        $builder->select('upd.grade_id');
        $builder->select("COALESCE((SELECT grade_name FROM grade WHERE grade_id = upd.grade_id LIMIT 1), '') AS grade_name");
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        $builder->join('user_profile_details upd', 'upd.user_id = u.user_id', 'left');
        $builder->where('upd.school_id', $params['school_id']);

        // Role-based conditions
        switch ($params['role_id']) {
            case 5:
                $builder->whereIn('u.role_id', [2, 4]);
                break;
            case 4:
                $builder->whereIn('u.role_id', [2, 5]);
                break;
            case 2:
                $builder->whereIn('u.role_id', [4, 5]);
                break;
        }

        // Search by name if provided
        if (isset($params['user_name']) && $params['user_name'] != "") {
            $builder->groupStart()
                ->like('up.first_name', $params['user_name'])
                ->orLike('up.last_name', $params['user_name'])
                ->groupEnd();
        }

        return $this->getResult($builder);
    }

    public function getMessages($condition)
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.message_id, m.parent_message_id, m.class_id, m.from_id, m.to_id, m.body, m.status, m.created_date');
        $builder->select('md.message_detail_id, md.user_id, md.is_read');
        $builder->select('ma.attachment_id, ma.attachment, ma.type');
        $builder->select("CONCAT_WS(' ', up.first_name, up.last_name) as from_name");
        $builder->select('up.profile_url');
        $builder->join('mailbox_details md', 'm.message_id = md.message_id', 'inner');
        $builder->join('mailbox_attachment ma', 'm.message_id = ma.message_id', 'left');
        $builder->join('user_profile up', 'm.from_id = up.user_id', 'inner');
        $builder->where($condition);
        return $this->getResult($builder);
    }

    public function getMailBox($condition)
    {
        $builder = $this->getBuilder('mailbox m');
        $builder->select('m.message_id, m.parent_message_id, m.class_id, m.from_id, m.to_id, m.body, m.status, m.created_date');
        $builder->select('md.message_detail_id, md.user_id, md.is_read');
        $builder->join('mailbox_details md', 'm.message_id = md.message_id', 'inner');
        $builder->where($condition);
        return $this->getResult($builder);
    }
} 