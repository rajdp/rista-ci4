<?php

namespace App\Controllers;

use App\Libraries\Authorization;
use App\Models\V1\TeacherAvailabilityModel;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\RESTful\ResourceController;
use DateTime;

class Availability extends ResourceController
{
    protected $modelName = TeacherAvailabilityModel::class;
    protected $format = 'json';

    private const ROLE_ADMIN = 2;
    private const ROLE_TEACHER = 4;

    public function create()
    {
        try {
            [$auth, $roleId] = $this->getAuthContext();
            $data = $this->request->getJSON(true) ?? [];

            $existing = null;
            $this->validateRequest($data, $existing);

            $teacherId = $this->resolveTeacherId($data, $auth, $roleId);
            $schoolId = $this->resolveSchoolId($auth, $data);
            $isRecurring = !empty($data['is_recurring']);

            $payload = [
                'teacher_id' => $teacherId,
                'school_id' => $schoolId,
                'date' => $this->normalizeDate($data['date'] ?? null),
                'day_of_week' => $isRecurring ? $this->normalizeDayOfWeek($data['day_of_week'] ?? null) : null,
                'is_recurring' => $isRecurring ? 1 : 0,
                'recurrence_end' => $isRecurring ? $this->normalizeDate($data['recurrence_end'] ?? null) : null,
                'timezone' => $data['timezone'],
                'notes' => $data['notes'] ?? null,
            ];

            $timeData = $this->model->normalizeTimes(
                $data['start_time'],
                $data['end_time'],
                $data['timezone'],
                $payload['date']
            );

            $payload = array_merge($payload, $timeData);

            if ($this->model->hasOverlap($payload)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Availability block overlaps with an existing entry.',
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $persistPayload = $this->model->prepareForPersistence($payload);
            $this->model->insert($persistPayload);
            $id = $this->model->getInsertID();

            return $this->respondCreated([
                'IsSuccess' => true,
                'ResponseObject' => $this->model->prepareRecord($this->model->find($id)),
                'ErrorObject' => '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ Availability::create error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function index()
    {
        try {
            [$auth, $roleId] = $this->getAuthContext();
            $teacherId = $this->request->getGet('teacher_id');

            if ($roleId !== self::ROLE_ADMIN) {
                $teacherId = $auth->user_id;
            } elseif ($teacherId) {
                $teacherId = (int) $teacherId;
            } else {
                $teacherId = $auth->user_id;
            }

            $availability = $this->model->getForTeacher((int) $teacherId, (int) $auth->school_id);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $availability,
                'ErrorObject' => '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ Availability::index error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function update($id = null)
    {
        try {
            [$auth, $roleId] = $this->getAuthContext();
            $id = (int) ($id ?? 0);

            if ($id <= 0) {
                throw new \InvalidArgumentException('Invalid availability ID.');
            }

            $existing = $this->model->prepareRecord($this->model->find($id));
            if (!$existing) {
                throw new \RuntimeException('Availability block not found.');
            }

            $this->assertPermission($existing, $auth, $roleId);

            $data = $this->request->getJSON(true) ?? [];
            $this->validateRequest($data, $existing);

            $isRecurring = array_key_exists('is_recurring', $data)
                ? (!empty($data['is_recurring']))
                : (!empty($existing['is_recurring']));

            $payload = [
                'date' => $this->normalizeDate($data['date'] ?? $existing['date']),
                'day_of_week' => $isRecurring ? $this->normalizeDayOfWeek($data['day_of_week'] ?? $existing['day_of_week']) : null,
                'is_recurring' => $isRecurring ? 1 : 0,
                'recurrence_end' => $isRecurring ? $this->normalizeDate($data['recurrence_end'] ?? $existing['recurrence_end']) : null,
                'timezone' => $data['timezone'] ?? $existing['timezone'],
                'notes' => $data['notes'] ?? $existing['notes'],
            ];

            $startTime = $data['start_time'] ?? $existing['start_time_local'];
            $endTime = $data['end_time'] ?? $existing['end_time_local'];

            $timeData = $this->model->normalizeTimes(
                $startTime,
                $endTime,
                $payload['timezone'],
                $payload['date']
            );

            $payload = array_merge($existing, $payload, $timeData);

            if ($this->model->hasOverlap($payload, $id)) {
                return $this->respond([
                    'IsSuccess' => false,
                    'ResponseObject' => null,
                    'ErrorObject' => 'Availability block overlaps with an existing entry.',
                ], ResponseInterface::HTTP_BAD_REQUEST);
            }

            $persistPayload = $this->model->prepareForPersistence($payload);
            $this->model->update($id, $persistPayload);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $this->model->prepareRecord($this->model->find($id)),
                'ErrorObject' => '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ Availability::update error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function delete($id = null)
    {
        try {
            [$auth, $roleId] = $this->getAuthContext();
            $id = (int) ($id ?? 0);

            if ($id <= 0) {
                throw new \InvalidArgumentException('Invalid availability ID.');
            }

            $existing = $this->model->find($id);
            if (!$existing) {
                throw new \RuntimeException('Availability block not found.');
            }

            $this->assertPermission($existing, $auth, $roleId);
            $this->model->delete($id);

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => ['id' => $id],
                'ErrorObject' => '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ Availability::delete error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    public function adminView()
    {
        try {
            [$auth, $roleId] = $this->getAuthContext();

            if ($roleId !== self::ROLE_ADMIN) {
                throw new \RuntimeException('Only admins can access this endpoint.');
            }

            $filters = [
                'teacher_id' => $this->request->getGet('teacher_id'),
                'school_id' => $this->request->getGet('school_id') ?? $auth->school_id,
                'start_date' => $this->normalizeDate($this->request->getGet('start_date')),
                'end_date' => $this->normalizeDate($this->request->getGet('end_date')),
                'day_of_week' => $this->normalizeDayOfWeek($this->request->getGet('day_of_week')),
            ];

            $availability = $this->model->getForAdmin(array_filter($filters));

            return $this->respond([
                'IsSuccess' => true,
                'ResponseObject' => $availability,
                'ErrorObject' => '',
            ]);
        } catch (\Throwable $e) {
            log_message('error', '❌ Availability::adminView error: ' . $e->getMessage());
            return $this->respond([
                'IsSuccess' => false,
                'ResponseObject' => null,
                'ErrorObject' => $e->getMessage(),
            ], ResponseInterface::HTTP_BAD_REQUEST);
        }
    }

    private function validateRequest(array $data, ?array $existing = null): void
    {
        $start = $data['start_time'] ?? $existing['start_time_local'] ?? null;
        $end = $data['end_time'] ?? $existing['end_time_local'] ?? null;
        $timezone = $data['timezone'] ?? $existing['timezone'] ?? null;

        if (!$start || !$this->isValidTime($start)) {
            throw new \InvalidArgumentException('start_time is required in HH:MM format.');
        }

        if (!$end || !$this->isValidTime($end)) {
            throw new \InvalidArgumentException('end_time is required in HH:MM format.');
        }

        if (!$timezone) {
            throw new \InvalidArgumentException('timezone is required.');
        }

        $isRecurring = array_key_exists('is_recurring', $data)
            ? (!empty($data['is_recurring']))
            : (!empty($existing['is_recurring']));

        if ($isRecurring) {
            $day = $data['day_of_week'] ?? $existing['day_of_week'] ?? null;
            if (!$day) {
                throw new \InvalidArgumentException('day_of_week is required for recurring availability.');
            }
        } else {
            $date = $data['date'] ?? $existing['date'] ?? null;
            if (!$date) {
                throw new \InvalidArgumentException('date is required for one-off availability.');
            }
        }
    }

    private function isValidTime(string $value): bool
    {
        return (bool) preg_match('/^\d{2}:\d{2}(:\d{2})?$/', trim($value));
    }

    private function assertPermission(array $entry, object $auth, int $roleId): void
    {
        if ($roleId === self::ROLE_ADMIN) {
            return;
        }

        if ($roleId === self::ROLE_TEACHER && (int) $entry['teacher_id'] === (int) $auth->user_id) {
            return;
        }

        throw new \RuntimeException('You do not have permission to modify this availability block.');
    }

    private function getAuthContext(): array
    {
        $token = $this->request->getHeaderLine('Accesstoken');
        if (!$token) {
            throw new \RuntimeException('Access token required.');
        }

        $payload = Authorization::validateToken($token);
        if (!$payload) {
            throw new \RuntimeException('Invalid or expired token.');
        }

        $roleId = property_exists($payload, 'role_id') ? (int) $payload->role_id : 0;
        if (!$roleId) {
            throw new \RuntimeException('Unable to determine user role from token.');
        }

        return [$payload, $roleId];
    }

    private function resolveTeacherId(array $data, object $auth, int $roleId): int
    {
        if ($roleId === self::ROLE_ADMIN) {
            if (empty($data['teacher_id'])) {
                throw new \InvalidArgumentException('teacher_id is required for admin created entries.');
            }
            return (int) $data['teacher_id'];
        }

        if ($roleId === self::ROLE_TEACHER) {
            return (int) $auth->user_id;
        }

        throw new \RuntimeException('Only teachers or admins can manage availability.');
    }

    private function resolveSchoolId(object $auth, array $data): int
    {
        if (!empty($auth->school_id)) {
            return (int) $auth->school_id;
        }

        if (!empty($data['school_id'])) {
            return (int) $data['school_id'];
        }

        throw new \RuntimeException('Unable to resolve school context for availability.');
    }

    private function normalizeDate(?string $value): ?string
    {
        if (!$value) {
            return null;
        }

        $value = trim($value);
        $date = DateTime::createFromFormat('Y-m-d', $value) ?: DateTime::createFromFormat('m/d/Y', $value);
        if (!$date) {
            throw new \InvalidArgumentException('Invalid date format. Use YYYY-MM-DD.');
        }

        return $date->format('Y-m-d');
    }

    private function normalizeDayOfWeek(?string $day): ?string
    {
        if (!$day) {
            return null;
        }

        $day = ucfirst(strtolower(trim($day)));
        $map = [
            'Monday' => 'Mon',
            'Mon' => 'Mon',
            'Tuesday' => 'Tue',
            'Tue' => 'Tue',
            'Wednesday' => 'Wed',
            'Wed' => 'Wed',
            'Thursday' => 'Thu',
            'Thu' => 'Thu',
            'Friday' => 'Fri',
            'Fri' => 'Fri',
            'Saturday' => 'Sat',
            'Sat' => 'Sat',
            'Sunday' => 'Sun',
            'Sun' => 'Sun',
        ];

        if (!isset($map[$day])) {
            throw new \InvalidArgumentException('Invalid day_of_week value.');
        }

        return $map[$day];
    }
}
