<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * CLI utility to backfill missing class_content rows from student_assign_content.
 *
 * Early CI4 migrations stored assignments only in student_assign_content.
 * The teacher curriculum views rely on class_content, so this command
 * creates any missing records to keep the UI in sync.
 */
class BackfillClassContent extends BaseCommand
{
    protected $group = 'Maintenance';
    protected $name = 'backfill:classcontent';
    protected $description = 'Backfill class_content rows using student_assign_content when missing.';

    public function run(array $params)
    {
        $db = Database::connect();

        $builder = $db->table('student_assign_content sac');
        $builder->select('sac.*, cc.id as existing_id, c.school_id, c.created_by as class_created_by');
        $builder->join(
            'class_content cc',
            'cc.class_id = sac.class_id AND cc.content_id = sac.content_id '
            . 'AND cc.start_date = sac.start_date AND cc.end_date = sac.end_date',
            'left'
        );
        $builder->join('class c', 'c.class_id = sac.class_id', 'left');
        $builder->where('cc.id', null);

        $results = $builder->get()->getResultArray();

        if (empty($results)) {
            CLI::write('✅ No missing class_content rows found. Nothing to backfill.', 'green');
            return;
        }

        $inserted = 0;
        foreach ($results as $row) {
            $startDate = $row['start_date'] ?? date('Y-m-d');
            $endDate = $row['end_date'] ?? '0000-00-00';
            $startTime = $this->normaliseTime($row['start_time'] ?? null, '00:00:00');
            $endTime = $this->normaliseTime($row['end_time'] ?? null, '23:59:00');
            $timestamp = $row['created_date'] ?? date('Y-m-d H:i:s');
            $actor = $row['created_by'] ?? $row['class_created_by'] ?? 0;

            $data = [
                'class_id' => $row['class_id'],
                'content_id' => $row['content_id'],
                'school_id' => $row['school_id'] ?? null,
                'status' => 1,
                'all_student' => 1,
                'release_score' => 0,
                'auto_review' => 0,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'notes' => '',
                'downloadable' => 0,
                'topic_id' => 0,
                'is_accessible' => 0,
                'created_by' => $actor,
                'created_date' => $timestamp,
                'modified_by' => $actor,
                'modified_date' => $timestamp,
            ];

            try {
                $db->table('class_content')->insert($data);
                $inserted++;
            } catch (\Throwable $e) {
                CLI::error(
                    sprintf(
                        'Failed to backfill class_id %s / content_id %s: %s',
                        $row['class_id'],
                        $row['content_id'],
                        $e->getMessage()
                    )
                );
            }
        }

        if ($inserted > 0) {
            CLI::write("✅ Backfilled {$inserted} class_content record(s).", 'green');
        } else {
            CLI::write('⚠️ No class_content rows were inserted. Check logs for errors.', 'yellow');
        }
    }

    private function normaliseTime($time, string $default): string
    {
        if (empty($time)) {
            return $default;
        }

        // Already HH:MM:SS
        if (preg_match('/^\d{1,2}:\d{2}:\d{2}$/', $time)) {
            return $time;
        }

        // HH:MM only
        if (preg_match('/^\d{1,2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        return $default;
    }
}
