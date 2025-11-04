<?php

namespace App\Models\V1;

use CodeIgniter\Model;
use DateInterval;
use DateTime;
use DateTimeZone;

class TeacherAvailabilityModel extends Model
{
    protected $table = 't_teacher_availability';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useSoftDeletes = true;
    protected $allowedFields = [
        'teacher_id',
        'school_id',
        'date',
        'availability_date',
        'day_of_week',
        'is_recurring',
        'start_time_local',
        'end_time_local',
        'spans_midnight',
        'start_time_utc',
        'end_time_utc',
        'duration_minutes',
        'recurrence_end',
        'timezone',
        'notes',
    ];
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    private ?string $resolvedDateColumn = null;

    public function getForTeacher(int $teacherId, int $schoolId): array
    {
        $dateColumn = $this->resolveDateColumn();
        $builder = $this->builder()->from($this->table . ' ta')
            ->where('ta.teacher_id', $teacherId)
            ->where('ta.school_id', $schoolId);

        $select = 'ta.*';
        if ($dateColumn && $dateColumn !== 'date') {
            $select .= ', ta.' . $dateColumn . ' AS date';
        }
        $builder->select($select);

        if ($dateColumn) {
            $builder->orderBy('ta.is_recurring', 'ASC')
                ->orderBy('ta.' . $dateColumn, 'ASC');
        } else {
            $builder->orderBy('ta.is_recurring', 'ASC')
                ->orderBy('ta.start_time_utc', 'ASC');
        }

        return $builder->get()->getResultArray();
    }

    public function getForAdmin(array $filters = []): array
    {
        $dateColumn = $this->resolveDateColumn();
        $select = 'ta.*, up.first_name, up.last_name';
        if ($dateColumn && $dateColumn !== 'date') {
            $select .= ', ta.' . $dateColumn . ' AS date';
        }

        $builder = $this->builder()
            ->from($this->table . ' ta')
            ->select($select)
            ->join('user_profile up', 'up.user_id = ta.teacher_id', 'left');

        if (!empty($filters['teacher_id'])) {
            $builder->where('ta.teacher_id', (int) $filters['teacher_id']);
        }

        if (!empty($filters['school_id'])) {
            $builder->where('ta.school_id', (int) $filters['school_id']);
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date']) && $dateColumn) {
            $builder->groupStart()
                ->where('ta.is_recurring', 0)
                ->where('ta.' . $dateColumn . ' >=', $filters['start_date'])
                ->where('ta.' . $dateColumn . ' <=', $filters['end_date'])
            ->groupEnd();
        }

        if (!empty($filters['day_of_week'])) {
            $builder->where('ta.day_of_week', $filters['day_of_week']);
        }

        $builder->orderBy('ta.teacher_id', 'ASC')
            ->orderBy('ta.is_recurring', 'ASC');

        if ($dateColumn) {
            $builder->orderBy('ta.' . $dateColumn, 'ASC');
        } else {
            $builder->orderBy('ta.start_time_utc', 'ASC');
        }

        return $builder->get()->getResultArray();
    }

    public function hasOverlap(array $payload, ?int $excludeId = null): bool
    {
        $dateColumn = $this->resolveDateColumn();
        $payload = $this->ensureDateAlias($payload);

        $builder = $this->builder();

        if ($dateColumn && $dateColumn !== 'date') {
            $builder->select($this->table . '.*, ' . $this->table . '.' . $dateColumn . ' AS date');
        }

        $builder
            ->where('teacher_id', $payload['teacher_id'])
            ->where('school_id', $payload['school_id']);

        if ($excludeId) {
            $builder->where('id !=', $excludeId);
        }

        $existing = array_map(fn ($row) => $this->ensureDateAlias($row), $builder->get()->getResultArray());

        foreach ($existing as $entry) {
            if (!$this->timeRangesOverlap(
                $payload['start_time_utc'],
                $payload['end_time_utc'],
                $entry['start_time_utc'],
                $entry['end_time_utc'],
                $payload['spans_midnight'] ?? 0,
                $entry['spans_midnight'] ?? 0
            )) {
                continue;
            }

            if ($payload['is_recurring'] && $entry['is_recurring']) {
                if ($payload['day_of_week'] === $entry['day_of_week'] &&
                    $this->recurrenceRangesOverlap($payload, $entry)) {
                    return true;
                }
            } elseif (!$payload['is_recurring'] && !$entry['is_recurring']) {
                if (($payload['date'] ?? null) === ($entry['date'] ?? null)) {
                    return true;
                }
            } elseif ($payload['is_recurring'] && !$entry['is_recurring']) {
                if ($this->specificDateFallsInRecurring($entry['date'] ?? null, $payload)) {
                    return true;
                }
            } elseif (!$payload['is_recurring'] && $entry['is_recurring']) {
                if ($this->specificDateFallsInRecurring($payload['date'] ?? null, $entry)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function recurrenceRangesOverlap(array $a, array $b): bool
    {
        $a = $this->ensureDateAlias($a);
        $b = $this->ensureDateAlias($b);

        $startA = $a['date'] ?? null;
        $endA = $a['recurrence_end'] ?? null;
        $startB = $b['date'] ?? null;
        $endB = $b['recurrence_end'] ?? null;

        if (!$startA || !$startB) {
            return true;
        }

        if ($endA && $startB > $endA) {
            return false;
        }

        if ($endB && $startA > $endB) {
            return false;
        }

        return true;
    }

    private function specificDateFallsInRecurring(?string $date, array $recurringEntry): bool
    {
        if (!$date) {
            return false;
        }

        $recurringDay = $recurringEntry['day_of_week'] ?? null;
        if (!$recurringDay) {
            return false;
        }

        $dateObj = DateTime::createFromFormat('Y-m-d', $date);
        if (!$dateObj) {
            return false;
        }

        $dayCode = substr($dateObj->format('D'), 0, 3);
        if ($dayCode !== $recurringDay) {
            return false;
        }

        $startDate = $recurringEntry['date'] ?? null;
        $endDate = $recurringEntry['recurrence_end'] ?? null;

        if ($startDate && $date < $startDate) {
            return false;
        }

        if ($endDate && $date > $endDate) {
            return false;
        }

        return true;
    }

    public function normalizeTimes(
        string $start,
        string $end,
        string $timezone,
        ?string $date = null
    ): array {
        $tz = new DateTimeZone($timezone);
        $baseDate = $date ?: '1970-01-05';

        $startDateTime = new DateTime($baseDate . ' ' . $start, $tz);
        $endDateTime = new DateTime($baseDate . ' ' . $end, $tz);
        $spansMidnight = false;

        if ($endDateTime <= $startDateTime) {
            $endDateTime->add(new DateInterval('P1D'));
            $spansMidnight = true;
        }

        $startUtc = clone $startDateTime;
        $endUtc = clone $endDateTime;
        $startUtc->setTimezone(new DateTimeZone('UTC'));
        $endUtc->setTimezone(new DateTimeZone('UTC'));

        return [
            'start_time_local' => $startDateTime->format('H:i:s'),
            'end_time_local' => $endDateTime->format('H:i:s'),
            'start_time_utc' => $startUtc->format('H:i:s'),
            'end_time_utc' => $endUtc->format('H:i:s'),
            'duration_minutes' => (int) round(($endDateTime->getTimestamp() - $startDateTime->getTimestamp()) / 60),
            'spans_midnight' => $spansMidnight ? 1 : 0,
        ];
    }

    private function buildTimeRange(array $entry): array
    {
        $startMinutes = $this->timeToMinutes($entry['start_time_utc']);
        $endMinutes = $this->timeToMinutes($entry['end_time_utc']);

        if (!empty($entry['spans_midnight']) && (int) $entry['spans_midnight'] === 1) {
            $endMinutes += 24 * 60;
        }

        return [$startMinutes, $endMinutes];
    }

    private function timeToMinutes(string $time): int
    {
        [$h, $m, $s] = array_pad(explode(':', $time), 3, '00');
        return ((int) $h * 60) + (int) $m + (int) floor(((int) $s) / 60);
    }

    private function timeRangesOverlap(string $startA, string $endA, string $startB, string $endB, int $spanA = 0, int $spanB = 0): bool
    {
        [$startMinutesA, $endMinutesA] = $this->buildTimeRange([
            'start_time_utc' => $startA,
            'end_time_utc' => $endA,
            'spans_midnight' => $spanA,
        ]);
        [$startMinutesB, $endMinutesB] = $this->buildTimeRange([
            'start_time_utc' => $startB,
            'end_time_utc' => $endB,
            'spans_midnight' => $spanB,
        ]);

        return ($startMinutesA < $endMinutesB) && ($startMinutesB < $endMinutesA);
    }

    private function resolveDateColumn(): ?string
    {
        if ($this->resolvedDateColumn !== null) {
            return $this->resolvedDateColumn;
        }

        if ($this->db->fieldExists('date', $this->table)) {
            return $this->resolvedDateColumn = 'date';
        }

        if ($this->db->fieldExists('availability_date', $this->table)) {
            return $this->resolvedDateColumn = 'availability_date';
        }

        return $this->resolvedDateColumn = null;
    }

    protected function ensureDateAlias(array $data): array
    {
        if (!array_key_exists('date', $data) && array_key_exists('availability_date', $data)) {
            $data['date'] = $data['availability_date'];
        }
        return $data;
    }

    public function prepareForPersistence(array $data): array
    {
        $dateColumn = $this->resolveDateColumn();

        if ($dateColumn && array_key_exists('date', $data)) {
            $data[$dateColumn] = $data['date'];
            if ($dateColumn !== 'date') {
                unset($data['date']);
            }
        }

        return $data;
    }

    public function prepareRecord(?array $record): ?array
    {
        if ($record === null) {
            return null;
        }

        return $this->ensureDateAlias($record);
    }
}
