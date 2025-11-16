<?php

namespace App\Services\Appt;

class IcsService
{
    public function makeEvent(array $appt): string
    {
        $uid = ($appt['appt_id'] ?? $appt['id'] ?? uniqid('appt_', true)) . '@edquill';
        $dtStart = gmdate('Ymd\THis\Z', strtotime($appt['start_at_utc']));
        $dtEnd = gmdate('Ymd\THis\Z', strtotime($appt['end_at_utc']));
        $summary = $this->escape($appt['title'] ?? 'Meeting');
        $loc = $this->escape($appt['location_details'] ?? '');

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//EdQuill//Appointments//EN',
            'METHOD:PUBLISH',
            'BEGIN:VEVENT',
            "UID:{$uid}",
            'DTSTAMP:' . gmdate('Ymd\THis\Z'),
            "DTSTART:{$dtStart}",
            "DTEND:{$dtEnd}",
            "SUMMARY:{$summary}",
            "LOCATION:{$loc}",
            'END:VEVENT',
            'END:VCALENDAR',
        ];

        return implode("\r\n", $lines) . "\r\n";
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', ',', ';', "\n"], ['\\\\', '\,', '\;', '\\n'], $value);
    }
}
