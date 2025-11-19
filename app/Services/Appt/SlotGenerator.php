<?php

namespace App\Services\Appt;

use App\Models\Appt\AvailabilityModel;
use App\Models\Appt\BookingModel;
use App\Models\Appt\ExceptionModel;
use CodeIgniter\I18n\Time;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;

class SlotGenerator
{
    public function __construct(
        private AvailabilityModel $availabilityModel,
        private ExceptionModel $exceptionModel,
        private BookingModel $bookingModel,
    ) {
    }

    /**
     * Generate open slots honoring availability, exceptions, buffers, and lead time.
     *
     * @return array<int, array{start:string,end:string,start_local:string,end_local:string}>
     */
    public function generate(
        int $schoolId,
        int $adminUserId,
        DateTimeInterface $fromUtc,
        DateTimeInterface $toUtc,
        int $durationMin,
        array $policy
    ): array {
        $timezone = new DateTimeZone($policy['timezone'] ?? 'UTC');
        $leadTimeMin = (int) ($policy['lead_time_min'] ?? 120);
        $bufferMin = (int) ($policy['buffer_min'] ?? 10);
        $maxPerDay = max(1, (int) ($policy['max_per_day'] ?? 8));

        $windows = $this->groupWindowsByDow(
            $this->availabilityModel->getActiveWindows($schoolId, $adminUserId)
        );

        if (empty($windows)) {
            return [];
        }

        $exceptions = $this->groupExceptionsByDate(
            $this->exceptionModel->getInRange($schoolId, $adminUserId, $fromUtc->format('Y-m-d'), $toUtc->format('Y-m-d'))
        );

        $bookings = $this->normalizeBookings(
            $this->bookingModel->upcomingForHost($schoolId, $adminUserId, $fromUtc->format('Y-m-d H:i:s'), $toUtc->format('Y-m-d H:i:s')),
            $timezone,
            $bufferMin
        );

        $period = new DatePeriod(
            new DateTimeImmutable($fromUtc->format(DateTimeInterface::ATOM)),
            new DateInterval('P1D'),
            (new DateTimeImmutable($toUtc->format(DateTimeInterface::ATOM)))->modify('+1 day')
        );

        $leadLimit = (new DateTimeImmutable('now', new DateTimeZone('UTC')))->modify("+{$leadTimeMin} minutes");

        $slots = [];

        foreach ($period as $dayUtc) {
            $localDay = $dayUtc->setTimezone($timezone);
            $dow = (int) $localDay->format('w');
            if (!isset($windows[$dow])) {
                continue;
            }

            $dayKey = $localDay->format('Y-m-d');
            $dayExceptions = $exceptions[$dayKey] ?? ['open' => [], 'closed' => []];
            $dailyCount = 0;

            foreach ($windows[$dow] as $window) {
                $currentStart = $this->combineTime($localDay, $window['start_time']);
                $windowEnd = $this->combineTime($localDay, $window['end_time']);

                if ($windowEnd <= $currentStart) {
                    continue; // invalid window
                }

                $effectiveSegments = $this->applyOpenOverrides($currentStart, $windowEnd, $dayExceptions['open']);

                foreach ($effectiveSegments as [$segmentStart, $segmentEnd]) {
                    $cursor = $segmentStart;
                    while ($cursor < $segmentEnd) {
                        $slotEnd = $cursor->modify("+{$durationMin} minutes");
                        if ($slotEnd > $segmentEnd) {
                            break;
                        }

                        $cursorUtc = $cursor->setTimezone(new DateTimeZone('UTC'));
                        $slotEndUtc = $slotEnd->setTimezone(new DateTimeZone('UTC'));

                        if ($cursorUtc < $leadLimit) {
                            $cursor = $cursor->modify("+{$durationMin} minutes");
                            continue;
                        }

                        if ($dailyCount >= $maxPerDay) {
                            break 2;
                        }

                        if ($this->fallsInClosedWindow($cursor, $slotEnd, $dayExceptions['closed'])) {
                            $cursor = $cursor->modify("+{$durationMin} minutes");
                            continue;
                        }

                        if ($this->overlapsBooking($cursor, $slotEnd, $bookings)) {
                            $cursor = $cursor->modify("+{$durationMin} minutes");
                            continue;
                        }

                        $slots[] = [
                            'start' => $cursorUtc->format(DateTimeInterface::ATOM),
                            'end' => $slotEndUtc->format(DateTimeInterface::ATOM),
                            'start_local' => $cursor->format(DateTimeInterface::ATOM),
                            'end_local' => $slotEnd->format(DateTimeInterface::ATOM),
                            'admin_user_id' => $adminUserId,
                        ];

                        $dailyCount++;
                        $cursor = $cursor->modify("+{$durationMin} minutes");
                    }
                }
            }
        }

        return $slots;
    }

    private function groupWindowsByDow(array $windows): array
    {
        $grouped = [];
        foreach ($windows as $window) {
            $dow = (int) $window['dow'];
            $grouped[$dow][] = $window;
        }
        return $grouped;
    }

    private function groupExceptionsByDate(array $exceptions): array
    {
        $grouped = [];
        foreach ($exceptions as $exception) {
            $date = $exception['date'];
            // Initialize both open and closed arrays for this date if not already set
            if (!isset($grouped[$date])) {
                $grouped[$date] = ['open' => [], 'closed' => []];
            }
            $bucket = $exception['type'] === 'open_override' ? 'open' : 'closed';
            $grouped[$date][$bucket][] = $exception;
        }
        return $grouped;
    }

    /**
     * Add buffer time around bookings to simplify overlap detection.
     */
    private function normalizeBookings(array $bookings, DateTimeZone $tz, int $bufferMin): array
    {
        $bufferSpec = sprintf('%+d minutes', $bufferMin);
        $bufferNeg = sprintf('-%d minutes', $bufferMin);

        return array_map(static function (array $booking) use ($tz, $bufferSpec, $bufferNeg) {
            $start = (new DateTimeImmutable($booking['start_at_utc'], new DateTimeZone('UTC')))
                ->setTimezone($tz)
                ->modify($bufferNeg);
            $end = (new DateTimeImmutable($booking['end_at_utc'], new DateTimeZone('UTC')))
                ->setTimezone($tz)
                ->modify($bufferSpec);

            return [
                'start' => $start,
                'end' => $end,
                'status' => $booking['status'],
            ];
        }, $bookings);
    }

    private function combineTime(DateTimeImmutable $day, string $time): DateTimeImmutable
    {
        [$hour, $minute, $second] = array_pad(explode(':', $time), 3, '00');
        return $day
            ->setTime((int) $hour, (int) $minute, (int) $second);
    }

    /**
     * Apply open overrides to base window.
     *
     * @return array<int,array{0:DateTimeImmutable,1:DateTimeImmutable}>
     */
    private function applyOpenOverrides(DateTimeImmutable $start, DateTimeImmutable $end, array $overrides): array
    {
        if (empty($overrides)) {
            return [[$start, $end]];
        }

        $segments = [];
        foreach ($overrides as $override) {
            $overrideStart = $this->combineTime($start, $override['start_time']);
            $overrideEnd = $this->combineTime($start, $override['end_time']);

            if ($overrideEnd <= $overrideStart) {
                continue;
            }

            $segments[] = [
                max($overrideStart, $start),
                min($overrideEnd, $end),
            ];
        }

        return $segments ?: [[$start, $end]];
    }

    private function fallsInClosedWindow(DateTimeImmutable $slotStart, DateTimeImmutable $slotEnd, array $closed): bool
    {
        foreach ($closed as $window) {
            $closeStart = $this->combineTime($slotStart, $window['start_time']);
            $closeEnd = $this->combineTime($slotStart, $window['end_time']);
            if ($slotStart < $closeEnd && $slotEnd > $closeStart) {
                return true;
            }
        }
        return false;
    }

    private function overlapsBooking(DateTimeImmutable $slotStart, DateTimeImmutable $slotEnd, array $bookings): bool
    {
        foreach ($bookings as $booking) {
            if ($slotStart < $booking['end'] && $slotEnd > $booking['start']) {
                return true;
            }
        }
        return false;
    }
}
