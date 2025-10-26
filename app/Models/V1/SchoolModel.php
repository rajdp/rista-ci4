<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class SchoolModel extends BaseModel
{
    protected $table = 'school';
    protected $allowedFields = [
        'school_id',
        'name',
        'tax_id',
        'address1',
        'address2',
        'city',
        'state',
        'country',
        'postal_code',
        'has_branch',
        'branch_name',
        'status',
        'profile_url',
        'profile_thumb_url',
        'institution_type',
        'school_website',
        'trial',
        'validity',
        'payment_status',
        'display_until',
        'created_by',
        'created_date',
        'modified_by',
        'modified_date'
    ];

    public function schoolList($params)
    {
        $builder = $this->getBuilder('school s');
        $builder->select('s.school_id, s.name, COALESCE(s.tax_id, "") AS tax_id, s.address1, s.address2, 
            s.city, s.state,
            (SELECT COALESCE(name, "") FROM state WHERE id = s.state LIMIT 1) AS state_name,
            (SELECT COALESCE(name, "") FROM country WHERE id = s.country LIMIT 1) AS country_name,
            s.country, s.postal_code, s.has_branch, s.branch_name,
            (SELECT corporate_name FROM corporate WHERE corporate_id = s.branch_name) as corporate_name, 
            s.status, COALESCE(s.profile_url, "") AS profile_url,
            COALESCE(s.profile_thumb_url, "") AS profile_thumb_url,
            COALESCE(up.first_name, "") AS first_name, s.institution_type, 
            COALESCE(s.school_website, "") AS school_website,
            COALESCE(up.last_name, "") AS last_name, s.created_by, s.created_date, s.modified_by,
            s.modified_date, u.email_id, COALESCE(u.mobile, "") AS mobile');
        
        $builder->join('user u', 'FIND_IN_SET(s.school_id, u.school_id)', 'left');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        
        if (isset($params['school_id']) && $params['school_id'] != 0) {
            $builder->where('s.school_id', $params['school_id']);
        }
        
        if (isset($params['corporate_id']) && $params['corporate_id'] != 0) {
            $builder->where('s.branch_name', $params['corporate_id']);
        }
        
        $builder->where('u.role_id', 2);
        $builder->where('s.institution_type !=', 2);
        $builder->orderBy('s.school_id', 'DESC');
        
        return $this->getResult($builder);
    }

    public function schoolListNew($params)
    {
        $builder = $this->getBuilder('school s');
        $builder->select('s.school_id, s.name, COALESCE(s.tax_id, "") AS tax_id, s.address1, s.address2, 
            s.city, s.state, COALESCE(st.name, "") AS state_name,
            COALESCE(c.name, "") AS country_name, s.country, s.postal_code,
            s.has_branch, s.branch_name,
            (SELECT corporate_name FROM corporate WHERE corporate_id = s.branch_name) as corporate_name, 
            s.status, COALESCE(s.profile_url, "") AS profile_url,
            COALESCE(s.profile_thumb_url, "") AS profile_thumb_url,
            COALESCE(up.first_name, "") AS first_name, s.institution_type, 
            COALESCE(s.school_website, "") AS school_website,
            COALESCE(up.last_name, "") AS last_name, s.created_by, s.created_date, s.modified_by,
            s.modified_date, u.email_id, COALESCE(u.mobile, "") AS mobile');
        
        $builder->join('user u', 'FIND_IN_SET(s.school_id, u.school_id)', 'left');
        $builder->join('user_profile up', 'up.user_id = u.user_id', 'left');
        $builder->join('state st', 's.state = st.id', 'left');
        $builder->join('country c', 's.country = c.id', 'left');
        
        if (isset($params['school_id']) && $params['school_id'] != 0) {
            $builder->where('s.school_id', $params['school_id']);
        }
        
        if (isset($params['corporate_id']) && $params['corporate_id'] != 0) {
            $builder->where('s.branch_name', $params['corporate_id']);
        }
        
        $builder->where('u.role_id', 2);
        $builder->where('s.institution_type !=', 2);
        $builder->orderBy('s.school_id', 'DESC');
        
        return $this->getResult($builder);
    }

    public function schoolDetails($params, $data)
    {
        $builder = $this->getBuilder('school s');
        $builder->select('s.school_id, s.name, u.allow_dashboard, u.subject,
            COALESCE(s.tax_id, "") AS tax_id, s.address1, s.address2, s.city, s.state, 
            s.institution_type, COALESCE(st.name, "") AS state_name,
            COALESCE(c.name, "") AS country_name, s.country, s.postal_code, 
            s.has_branch, s.branch_name, s.status, COALESCE(s.profile_url, "") AS profile_url,
            COALESCE(s.profile_thumb_url, "") AS profile_thumb_url, s.trial, s.validity,
            s.created_by, s.created_date, s.modified_by, s.modified_date, 
            s.payment_status, s.display_until');
        
        $builder->join('state st', 's.state = st.id', 'left');
        $builder->join('country c', 's.country = c.id', 'left');
        $builder->join('user_profile_details u', 's.school_id = u.school_id', 'left');
        
        if (isset($params['school_id']) && $params['school_id'] != '') {
            $builder->whereIn('s.school_id', explode(',', $params['school_id']));
            $builder->where('s.status', 1);
        }
        
        $builder->where('u.user_id', $data);
        $builder->orderBy('s.name', 'ASC');
        
        return $this->getResult($builder);
    }

    public function checkUserExists($id)
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id');
        $builder->where('school_id', $id);
        return $this->getResult($builder);
    }

    public function checkUserProfileExists($id)
    {
        $builder = $this->getBuilder('user_profile');
        $builder->select('user_id');
        $builder->where('user_id', $id);
        return $this->getResult($builder);
    }

    public function checkAdmin($data)
    {
        $builder = $this->getBuilder('user');
        $builder->select('user_id');
        $builder->where('email_id', $data['email_id']);
        return $this->getResult($builder);
    }

    public function checkExistingRequest($params)
    {
        $builder = $this->getBuilder('school_registration_request');
        $builder->select('id');
        $builder->where('email_id', $params['email_id']);
        return $this->getResult($builder);
    }

    public function getSchoolRegistrationEmailTemplateUser()
    {
        $builder = $this->getBuilder('sms_templates');
        $builder->select('subject, template');
        $builder->where('id', 23);
        return $this->getResult($builder, [], true);
    }

    public function getSchoolRegistrationEmailTemplateAdmin()
    {
        $builder = $this->getBuilder('sms_templates');
        $builder->select('subject, template');
        $builder->where('id', 24);
        return $this->getResult($builder, [], true);
    }

    public function checkSchool($schoolId)
    {
        $builder = $this->getBuilder();
        $builder->select('school_id');
        $builder->where('school_id', $schoolId);
        return $this->getResult($builder, [], true);
    }

    public function getCalendar($params)
    {
        $builder = $this->getBuilder('holiday_calendar');
        $builder->select('id, school_id, from_date, to_date, festival_name');
        $builder->where('school_id', $params['school_id']);
        
        if (isset($params['from_date']) && isset($params['to_date'])) {
            $builder->where('from_date >=', $params['from_date']);
            $builder->where('to_date <=', $params['to_date']);
        }
        
        $builder->orderBy('from_date');
        return $this->getResult($builder);
    }

    public function checkCalendarExists($params)
    {
        $builder = $this->getBuilder('holiday_calendar');
        $builder->select('id');
        $builder->where('school_id', $params['school_id']);
        $builder->where('from_date', $params['from_date']);
        $builder->where('to_date', $params['to_date']);
        return $this->getResult($builder);
    }

    public function checkCalendar($params)
    {
        $builder = $this->getBuilder('holiday_calendar');
        $builder->select('id');
        $builder->where('school_id', $params['school_id']);
        $builder->where('id', $params['id']);
        return $this->getResult($builder);
    }

    public function checkSchoolName($name, $branchName)
    {
        $builder = $this->getBuilder();
        $builder->select('school_id');
        $builder->where('name', $name);
        $builder->where('branch_name', $branchName);
        return $this->getResult($builder);
    }

    public function checkSchoolId($schoolId, $userId)
    {
        $builder = $this->getBuilder('user_profile_details upd');
        $builder->select('user_details_id');
        $builder->where('upd.school_id', $schoolId);
        $builder->where('upd.user_id', $userId);
        return $this->getResult($builder);
    }
} 