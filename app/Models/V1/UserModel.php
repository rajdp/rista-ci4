<?php

namespace App\Models\V1;

use CodeIgniter\Model;

class UserModel extends BaseModel
{
    protected $table = 'user';
    protected $primaryKey = 'user_id';
    protected $allowedFields = [
        'user_id',
        'email_id',
        'password',
        'role_id',
        'school_id',
        'status',
        'random_token',
        'googleid_token',
        'provider_id',
        'tc_status',
        'default_password',
        'mobile',
        'corporate_id',
        'created_date',
        'modified_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Login user with username and password
     */
    public function login(array $data): ?array
    {
        $db = \Config\Database::connect();
        
        // Build password check (password is already salted in controller)
        $passwordCheck = "";
        if (isset($data['password']) && $data['password'] != '') {
            $passwordCheck = "AND password = md5('{$data['password']}')";
        }
        
        // Use raw query to match CI3 exactly
        $query = "SELECT u.user_id, u.role_id AS role, upd.individual_teacher, upd.school_id AS school, 
                    upd.status, u.school_id, u.email_id, u.mobile, u.default_password, 
                    COALESCE(up.gender, '') AS gender, up.first_name, up.last_name, 
                    COALESCE(up.profile_url, '') AS profile_url, 
                    COALESCE(up.profile_thumb_url, '') AS profile_thumb_url, 
                    COALESCE(up.birthday, '') AS birthday, u.tc_status,
                    COALESCE(up.currency, '') AS currency, upd.doj, 
                    COALESCE(upd.designation,'') AS designation, upd.school_idno, upd.subject, upd.grade_id,
                    ua.address1, COALESCE(ua.address2,'') AS address2, ua.city, ua.state,
                    COALESCE((SELECT name FROM state WHERE id=ua.state),'') AS state_name, ua.country,
                    (SELECT name FROM country WHERE id=ua.country) AS country_name, 
                    upd.edit_status, 'America/New_York' as time_zone,
                    c.corporate_name, c.corporate_code, u.corporate_id
                    FROM user u
                    LEFT JOIN user_profile up ON up.user_id = u.user_id
                    LEFT JOIN user_profile_details upd ON upd.user_id = u.user_id
                    LEFT JOIN user_address ua ON ua.user_id = u.user_id
                    LEFT JOIN corporate c ON u.corporate_id = c.corporate_id
                    WHERE email_id = ? $passwordCheck";
        
        $users = $db->query($query, [$data['username']])->getResultArray();
        
        if (empty($users)) {
            return null;
        }
        
        $user = $users[0];
        
        // Check user status
        if (isset($user['status']) && $user['status'] != 1) {
            if ($user['role'] == 5) { // Student
                if ($user['status'] == 2) {
                    throw new \Exception("Student marked as inactive. Kindly contact your school admin.");
                } elseif ($user['status'] == 3) {
                    throw new \Exception("Student marked as suspended. Kindly contact your school admin.");
                } elseif ($user['status'] == 4) {
                    throw new \Exception("Student marked as deleted. Kindly contact your school admin.");
                }
            } else {
                if ($user['status'] == 2) {
                    throw new \Exception("User marked as inactive.");
                } elseif ($user['status'] == 3) {
                    throw new \Exception("User marked as suspended.");
                } elseif ($user['status'] == 4) {
                    throw new \Exception("User marked as deleted.");
                }
            }
        }
        
        // Generate access token
        $tokenPayload = [
            'user_id' => $user['user_id'],
            'role_id' => $user['role'],
            'school_id' => $user['school'],
            'timestamp' => time()
        ];
        
        $accessToken = \App\Libraries\Authorization::generateToken($tokenPayload);
        
        // Save token to user_token table
        $db->table('user_token')->insert([
            'user_id' => $user['user_id'],
            'access_token' => $accessToken,
            'status' => 1,
            'created_date' => date('Y-m-d H:i:s')
        ]);
        
        // Format response to match CI3 frontend expectations
        $user['Accesstoken'] = $accessToken; // Capital A
        $user['user_role'] = $user['role']; // Add user_role field
        
        // Get school details for this user
        $schoolQuery = "SELECT s.school_id, s.name, s.status
                        FROM school s
                        WHERE s.school_id = ?";
        $schools = $db->query($schoolQuery, [$user['school']])->getResultArray();
        
        // Add allow_dashboard to each school (default to 1)
        foreach ($schools as &$school) {
            $school['allow_dashboard'] = '1';
        }
        
        // If no schools found, create default
        if (empty($schools)) {
            $schools = [[
                'school_id' => $user['school'],
                'name' => 'School',
                'status' => '1',
                'allow_dashboard' => '1'
            ]];
        }
        
        $user['school_details'] = $schools;
        $user['message'] = 'Login Successful';
        
        return $user;
    }

    /**
     * Get my profile
     */
    public function getMyProfile($userId)
    {
        $db = \Config\Database::connect();
        
        $query = "SELECT u.user_id, u.role_id AS role, u.email_id, u.mobile, u.status, u.default_password,
                    COALESCE(up.first_name, '') AS first_name, 
                    COALESCE(up.last_name, '') AS last_name,
                    COALESCE(up.gender, '') AS gender,
                    COALESCE(up.profile_url, '') AS profile_url,
                    COALESCE(up.profile_thumb_url, '') AS profile_thumb_url,
                    COALESCE(up.birthday, '') AS birthday,
                    COALESCE(upd.school_id, 0) AS school_id,
                    COALESCE(upd.designation, '') AS designation,
                    COALESCE(upd.grade_id, '') AS grade_id,
                    COALESCE(ua.address1, '') AS address1,
                    COALESCE(ua.address2, '') AS address2,
                    COALESCE(ua.city, '') AS city,
                    COALESCE(ua.state, '') AS state,
                    COALESCE(ua.country, '') AS country
                    FROM user u
                    LEFT JOIN user_profile up ON up.user_id = u.user_id
                    LEFT JOIN user_profile_details upd ON upd.user_id = u.user_id
                    LEFT JOIN user_address ua ON ua.user_id = u.user_id
                    WHERE u.user_id = ?";
        
        $result = $db->query($query, [$userId])->getResultArray();
        
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Get dashboard data for a school
     */
    public function getDashboardData($schoolId)
    {
        $db = \Config\Database::connect();
        
        // Get student count (role_id = 5 for students)
        $studentCount = $db->table('user u')
            ->join('user_profile_details upd', 'upd.user_id = u.user_id', 'left')
            ->where('upd.school_id', $schoolId)
            ->where('u.role_id', 5)
            ->where('u.status', 1)
            ->countAllResults();
        
        // Get teacher count (role_id = 2 for teachers)
        $teacherCount = $db->table('user')
            ->where('school_id', $schoolId)
            ->where('role_id', 2)
            ->where('status', 1)
            ->countAllResults();
        
        // Get content count
        $contentCount = $db->table('content')
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->countAllResults();
        
        // Get class count
        $classCount = $db->table('class')
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->countAllResults();
        
        // Get monthly class additions
        $addClasses = $this->getMonthWiseClassData($schoolId, 'created_date');
        $editClasses = $this->getMonthWiseClassData($schoolId, 'modified_date');
        
        return [[
            'students' => $studentCount,
            'teachers' => $teacherCount,
            'content' => $contentCount,
            'classes' => [
                ['add_classes' => $addClasses],
                ['edit_classes' => $editClasses]
            ],
            'class_count' => $classCount
        ]];
    }

    /**
     * Get month-wise class data
     */
    private function getMonthWiseClassData($schoolId, $dateField = 'created_date')
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('class');
        $builder->select("DATE_FORMAT($dateField, '%b') as monthname, COUNT(*) as classes");
        $builder->where('school_id', $schoolId);
        $builder->where('status', 1);
        $builder->where("$dateField >=", date('Y-m-d', strtotime('-6 months')));
        $builder->groupBy("DATE_FORMAT($dateField, '%b')");
        
        return $builder->get()->getResultArray();
    }

    /**
     * Get user records for dashboard
     */
    public function getUserRecords($schoolId)
    {
        $db = \Config\Database::connect();
        
        // Get school created date
        $schoolBuilder = $db->table('school');
        $schoolBuilder->select('created_date');
        $schoolBuilder->where('school_id', $schoolId);
        $dashboardCount = $schoolBuilder->get()->getResultArray();
        
        // Get student count (role_id = 5 for students)
        $studentCountResult = $db->table('user u')
            ->join('user_profile_details upd', 'upd.user_id = u.user_id', 'left')
            ->where('upd.school_id', $schoolId)
            ->where('u.role_id', 5)
            ->where('u.status', 1)
            ->countAllResults();
        $schoolCount = [['count' => $studentCountResult]];
        
        // Get monthly student registrations
        $schoolDataBuilder = $db->table('user u');
        $schoolDataBuilder->select("DATE_FORMAT(u.created_date, '%b') as monthname, COUNT(*) as count");
        $schoolDataBuilder->join('user_profile_details upd', 'upd.user_id = u.user_id', 'left');
        $schoolDataBuilder->where('upd.school_id', $schoolId);
        $schoolDataBuilder->where('u.role_id', 5);
        $schoolDataBuilder->where('u.created_date >=', date('Y-m-d', strtotime('-12 months')));
        $schoolDataBuilder->groupBy("DATE_FORMAT(u.created_date, '%b')");
        $allSchools = $schoolDataBuilder->get()->getResultArray();
        
        // Get overall records
        $overallBuilder = $db->table('user u');
        $overallBuilder->select("DATE_FORMAT(u.created_date, '%b') as monthname, COUNT(*) as student_count");
        $overallBuilder->join('user_profile_details upd', 'upd.user_id = u.user_id', 'left');
        $overallBuilder->where('upd.school_id', $schoolId);
        $overallBuilder->where('u.role_id', 5);
        $overallBuilder->where('u.created_date >=', date('Y-m-d', strtotime('-12 months')));
        $overallBuilder->groupBy("DATE_FORMAT(u.created_date, '%b')");
        $overall = $overallBuilder->get()->getResultArray();
        
        return [
            'dashboard_count' => $dashboardCount,
            'school_count' => $schoolCount,
            'all_schools' => $allSchools,
            'overall' => $overall
        ];
    }

    /**
     * Get content statistics by month
     */
    public function getContentStatistics($schoolId)
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('content');
        $builder->select("DATE_FORMAT(created_date, '%b') as monthname, COUNT(*) as content");
        $builder->where('school_id', $schoolId);
        $builder->where('status', 1);
        $builder->where('created_date >=', date('Y-m-d', strtotime('-7 months')));
        $builder->groupBy("DATE_FORMAT(created_date, '%b')");
        
        $results = $builder->get()->getResultArray();
        
        // Calculate percentages
        $totalContent = $db->table('content')
            ->where('school_id', $schoolId)
            ->where('status', 1)
            ->countAllResults();
        
        foreach ($results as &$result) {
            $result['percentage'] = $totalContent > 0 ? round(($result['content'] / $totalContent) * 100, 2) : 0;
        }
        
        return $results;
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.email_id, u.role_id, u.school_id, u.status');
        $builder->select('up.first_name, up.last_name, up.profile_url');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.user_id', $userId);
        return $this->getRow($builder);
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.email_id, u.role_id, u.school_id, u.status, u.password');
        $builder->select('up.first_name, up.last_name, up.profile_url');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        $builder->where('u.email_id', $email);
        return $this->getRow($builder);
    }

    /**
     * Create new user
     */
    public function createUser(array $data)
    {
        $userId = $this->db->table('user')->insert($data);
        return $userId ? $this->db->insertID() : false;
    }

    /**
     * Update user
     */
    public function updateUser($userId, array $userData, array $profileData = [])
    {
        $db = \Config\Database::connect();
        $db->transStart();
        
        // Update user table
        $db->table('user')->where('user_id', $userId)->update($userData);
        
        // Update user_profile if provided
        if (!empty($profileData)) {
            $db->table('user_profile')->where('user_id', $userId)->update($profileData);
        }
        
        $db->transComplete();
        return $db->transStatus();
    }

    /**
     * Delete user
     */
    public function deleteUser($userId, $schoolId)
    {
        return $this->db->table('user')
            ->where('user_id', $userId)
            ->update(['status' => 0, 'modified_date' => date('Y-m-d H:i:s')]);
    }

    /**
     * Get user list
     */
    public function userList(array $data): array
    {
        $builder = $this->getBuilder('user u');
        $builder->select('u.user_id, u.role_id AS role, u.status, u.email_id, u.default_password, u.mobile, 
            COALESCE(up.gender, "") AS gender, up.first_name, up.last_name, 
            COALESCE(up.profile_url, "") AS profile_url, 
            COALESCE(up.profile_thumb_url, "") AS profile_thumb_url, up.birthday');
        $builder->join('user_profile up', 'u.user_id = up.user_id', 'left');
        
        if (isset($data['role_id'])) {
            $builder->where('u.role_id', $data['role_id']);
        }
        
        if (isset($data['status'])) {
            $builder->where('u.status', $data['status']);
        }
        
        if (isset($data['school_id'])) {
        $builder->where('u.school_id', $data['school_id']);
        }
        
        return $this->getResult($builder);
    }
}

