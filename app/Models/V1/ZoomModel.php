<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use CodeIgniter\API\ResponseTrait;
use Config\Services;

class ZoomModel extends BaseModel
{
    use ResponseTrait;

    protected $table = 'zoom_meetings';
    protected $allowedFields = [
        'meeting_id',
        'schedule_id',
        'slot_id',
        'host_id',
        'topic',
        'start_time',
        'duration',
        'timezone',
        'password',
        'join_url',
        'start_url',
        'status',
        'created_date',
        'modified_date'
    ];

    public function __construct()
    {
        parent::__construct();
    }

    public function createMeeting(array $params, array $value): ?array
    {
        if ($params['allow_zoom_api'] != 1 || $params['class_type'] != 1) {
            return null;
        }

        $zoomParams = $this->getZoomKeys($params['school_id']);
        if (empty($zoomParams)) {
            return null;
        }

        $zoomKey = [];
        $email = '';
        $timeZone = '';

        foreach ($zoomParams as $param) {
            switch ($param['name']) {
                case 'zoom_apikey':
                case 'zoom_secretkey':
                    $zoomKey[] = $param;
                    break;
                case 'zoom_user_email':
                    $explode = explode(',', $param['value']);
                    $email = $value['email_id'] ?: $explode[0];
                    $param['value'] = $email;
                    $zoomKey[] = $param;
                    break;
                case 'timezone':
                    $timeZone = $this->getTimeZone($param['value']);
                    break;
            }
        }

        $className = $this->getClassName($params['class_id']);
        if (empty($className)) {
            return null;
        }

        $startTime = date('h:i', strtotime($value['slotstarttime']));
        $endTime = date('h:i:s', strtotime($value['slotendtime']));
        $slotDayZoom = ($value['slotday'] + 1);
        $slotDay = $slotDayZoom % 7;

        $data = [
            'agenda' => $className[0]['class_name'],
            'default_password' => false,
            'pre_schedule' => false,
            'password' => '',
            'duration' => $params['duration'],
            'schedule_for' => $email,
            'settings' => [
                'auto_recording' => 'cloud',
                'host_video' => false,
                'participant_video' => false,
                'password' => '',
                'join_before_host' => false,
                'audio' => true,
                'approval_type' => '2'
            ],
            'topic' => $className[0]['class_name'] . ' ' . $className[0]['grade_name'] . ' ' . $className[0]['subject_name'],
            'type' => '2'
        ];

        if ($timeZone) {
            $date = $params['start_date'] . ' ' . $startTime;
            $data['start_time'] = date('Y-m-d\TH:i:s', strtotime($date));
        } else {
            $data['start_time'] = $params['start_date'] . 'T' . $startTime . ':Z';
        }

        $url = "https://api.zoom.us/v2/users/{$value['email_id']}/meetings";
        return $this->curlCall($url, $data, 'POST', 'create_meeting', $params['school_id']);
    }

    public function updateMeeting(array $params, array $value): ?array
    {
        if ($params['allow_zoom_api'] != 1 || $params['class_type'] != 1) {
            return null;
        }

        $zoomParams = $this->getZoomKeys($params['school_id']);
        if (empty($zoomParams)) {
            return null;
        }

        $zoomKey = [];
        $email = '';
        $timeZone = '';

        foreach ($zoomParams as $param) {
            switch ($param['name']) {
                case 'zoom_apikey':
                case 'zoom_secretkey':
                    $zoomKey[] = $param;
                    break;
                case 'zoom_user_email':
                    $explode = explode(',', $param['value']);
                    $email = $value['email_id'] ?: $explode[0];
                    $param['value'] = $email;
                    $zoomKey[] = $param;
                    break;
                case 'timezone':
                    $timeZone = $this->getTimeZone($param['value']);
                    break;
            }
        }

        $className = $this->getClassName($params['class_id']);
        if (empty($className)) {
            return null;
        }

        $startTime = date('h:i', strtotime($value['slotstarttime']));
        $endTime = date('h:i:s', strtotime($value['slotendtime']));
        $slotDayZoom = ($value['slotday'] + 1);
        $slotDay = $slotDayZoom % 7;

        $data = [
            'agenda' => $className[0]['class_name'],
            'default_password' => false,
            'pre_schedule' => false,
            'password' => '',
            'duration' => $params['duration'],
            'schedule_for' => $email,
            'settings' => [
                'auto_recording' => 'cloud',
                'host_video' => false,
                'participant_video' => false,
                'password' => '',
                'join_before_host' => false,
                'audio' => true,
                'approval_type' => '2'
            ],
            'topic' => $className[0]['class_name'] . ' ' . $className[0]['grade_name'] . ' ' . $className[0]['subject_name'],
            'type' => '2'
        ];

        if ($timeZone) {
            $date = $params['start_date'] . ' ' . $startTime;
            $data['start_time'] = date('Y-m-d\TH:i:s', strtotime($date));
        } else {
            $data['start_time'] = $params['start_date'] . 'T' . $startTime . ':Z';
        }

        $url = "https://api.zoom.us/v2/meetings/{$params['meeting_id']}";
        return $this->curlCall($url, $data, 'PATCH', 'update_meeting', $params['school_id']);
    }

    public function getMeetingRecordings(array $params, string $meetingId, string $scheduleId): ?array
    {
        $zoomParams = $this->getZoomKeys($params['school_id']);
        if (empty($zoomParams)) {
            return null;
        }

        $url = "https://api.zoom.us/v2/meetings/{$meetingId}/recordings";
        return $this->curlCall($url, [], 'GET', 'get_recordings', $params['school_id']);
    }

    public function getMeetingStatus(string $scheduleId, string $meetingId, array $params, string $classId): ?array
    {
        $zoomParams = $this->getZoomKeys($params['school_id']);
        if (empty($zoomParams)) {
            return null;
        }

        $url = "https://api.zoom.us/v2/meetings/{$meetingId}";
        return $this->curlCall($url, [], 'GET', 'get_meeting_status', $params['school_id']);
    }

    public function getTimeZone(string $id): ?array
    {
        $builder = $this->getBuilder('timezone');
        $builder->select('time_zone');
        $builder->where('id', $id);
        return $this->getResult($builder);
    }

    public function getZoomKeys(string $schoolId): array
    {
        $builder = $this->getBuilder('zoom_config');
        $builder->select('name, value');
        $builder->where('school_id', $schoolId);
        return $this->getResult($builder);
    }

    public function getZoomToken(string $schoolId): ?array
    {
        $builder = $this->getBuilder('zoom_token');
        $builder->select('token, expires_at');
        $builder->where('school_id', $schoolId);
        $builder->where('expires_at >', date('Y-m-d H:i:s'));
        return $this->getRow($builder);
    }

    public function zoomTokenGeneration(array $params): ?array
    {
        $zoomParams = $this->getZoomKeys($params['school_id']);
        if (empty($zoomParams)) {
            return null;
        }

        $apiKey = '';
        $secretKey = '';
        foreach ($zoomParams as $param) {
            if ($param['name'] == 'zoom_apikey') {
                $apiKey = $param['value'];
            } elseif ($param['name'] == 'zoom_secretkey') {
                $secretKey = $param['value'];
            }
        }

        $token = base64_encode($apiKey . ':' . $secretKey);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $data = [
            'school_id' => $params['school_id'],
            'token' => $token,
            'expires_at' => $expiresAt
        ];

        $this->insert('zoom_token', $data);
        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    protected function curlCall(string $url, array $params, string $method, string $type, string $schoolId): ?array
    {
        $token = $this->getZoomToken($schoolId);
        if (!$token) {
            $token = $this->zoomTokenGeneration(['school_id' => $schoolId]);
        }

        $headers = [
            'Authorization: Bearer ' . $token['token'],
            'Content-Type: application/json'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method == 'POST' || $method == 'PATCH') {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }

        if ($method == 'PATCH') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseArray = json_decode($response, true);
        $this->createLog($params, $url, $responseArray, $type, $schoolId);

        return $httpCode == 200 ? $responseArray : null;
    }

    protected function createLog(array $data, string $url, array $responseArray, string $usage, string $logType): void
    {
        $logData = [
            'request_data' => json_encode($data),
            'url' => $url,
            'response_data' => json_encode($responseArray),
            'usage' => $usage,
            'log_type' => $logType,
            'created_date' => date('Y-m-d H:i:s')
        ];

        $this->insert('zoom_logs', $logData);
    }

    protected function getClassName(string $classId): array
    {
        $builder = $this->getBuilder('class c');
        $builder->select('c.class_name, g.grade_name, s.subject_name');
        $builder->join('grade g', 'c.grade_id = g.grade_id');
        $builder->join('subject s', 'c.subject_id = s.subject_id');
        $builder->where('c.class_id', $classId);
        return $this->getResult($builder);
    }
} 