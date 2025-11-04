<?php

namespace App\Models\Admin;

use CodeIgniter\Model;

class ExamScoreModel extends Model
{
    protected $table = 'exam_scores';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $allowedFields = [
        'exam_id',
        'student_id',
        'subject',
        'max_score',
        'score',
        'teacher_comments',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    public function scoresForStudent(int $examId, int $studentId): array
    {
        return $this->where('exam_id', $examId)
            ->where('student_id', $studentId)
            ->findAll();
    }

    public function upsertScores(int $examId, int $studentId, array $scores): void
    {
        foreach ($scores as $score) {
            $payload = [
                'exam_id' => $examId,
                'student_id' => $studentId,
                'subject' => $score['subject'],
                'max_score' => $score['max_score'] ?? 100,
                'score' => $score['score'] ?? 0,
                'teacher_comments' => $score['teacher_comments'] ?? null,
            ];

            if (!empty($score['id'])) {
                $this->update($score['id'], $payload);
            } else {
                $this->insert($payload);
            }
        }
    }
}
