<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Traits\RestTrait;
use App\Models\Admin\ExamModel;
use App\Models\Admin\ExamScoreModel;
use App\Models\Admin\ReportCardModel;
use App\Models\Admin\StudentsModel;
use App\Libraries\SimplePdfGenerator;
use CodeIgniter\HTTP\ResponseInterface;

class ReportCards extends BaseController
{
    use RestTrait;

    protected ExamModel $examModel;
    protected ExamScoreModel $scoreModel;
    protected ReportCardModel $reportCardModel;
    protected StudentsModel $studentsModel;
    protected SimplePdfGenerator $pdfGenerator;

    public function __construct()
    {
        $this->examModel = new ExamModel();
        $this->scoreModel = new ExamScoreModel();
        $this->reportCardModel = new ReportCardModel();
        $this->studentsModel = new StudentsModel();
        $this->pdfGenerator = new SimplePdfGenerator();
    }

    public function listExams(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            if (empty($payload['school_id'])) {
                return $this->errorResponse('school_id is required');
            }

            return $this->successResponse(
                $this->examModel->listForSchool((int) $payload['school_id']),
                'Exams retrieved'
            );
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to list exams: ' . $e->getMessage());
        }
    }

    public function saveExam(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['school_id', 'name'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $data = [
                'school_id' => (int) $payload['school_id'],
                'name' => $payload['name'],
                'term' => $payload['term'] ?? null,
                'class_id' => $payload['class_id'] ?? null,
                'exam_date' => $payload['exam_date'] ?? null,
                'created_by' => $payload['created_by'] ?? null,
            ];

            if (!empty($payload['id'])) {
                $this->examModel->update((int) $payload['id'], $data);
                $exam = $this->examModel->find((int) $payload['id']);
                $message = 'Exam updated';
            } else {
                $examId = $this->examModel->insert($data, true);
                $exam = $this->examModel->find($examId);
                $message = 'Exam created';
            }

            return $this->successResponse($exam, $message);
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to save exam: ' . $e->getMessage());
        }
    }

    public function saveScores(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['exam_id', 'student_id', 'scores'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $examId = (int) $payload['exam_id'];
            $studentId = (int) $payload['student_id'];
            $scores = array_map(static function ($row) {
                return [
                    'id' => $row['id'] ?? null,
                    'subject' => $row['subject'] ?? 'Subject',
                    'max_score' => $row['max_score'] ?? 100,
                    'score' => $row['score'] ?? 0,
                    'teacher_comments' => $row['teacher_comments'] ?? null,
                ];
            }, (array) $payload['scores']);

            $this->scoreModel->upsertScores($examId, $studentId, $scores);

            $savedScores = $this->scoreModel->scoresForStudent($examId, $studentId);
            return $this->successResponse($savedScores, 'Scores saved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to save scores: ' . $e->getMessage());
        }
    }

    public function scores(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['exam_id', 'student_id'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $scores = $this->scoreModel->scoresForStudent((int) $payload['exam_id'], (int) $payload['student_id']);
            return $this->successResponse($scores, 'Scores retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to load scores: ' . $e->getMessage());
        }
    }

    public function generate(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['exam_id', 'student_id'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $examId = (int) $payload['exam_id'];
            $studentId = (int) $payload['student_id'];

            $exam = $this->examModel->find($examId);
            if (!$exam) {
                return $this->errorResponse('Exam not found', 404);
            }

            $student = $this->studentsModel->find($studentId);
            if (!$student) {
                return $this->errorResponse('Student not found', 404);
            }

            $scores = $this->scoreModel->scoresForStudent($examId, $studentId);
            if (empty($scores)) {
                return $this->errorResponse('Scores are required before generating report');
            }

            $reportCard = $this->reportCardModel->findByExamAndStudent($examId, $studentId);
            $payloadMeta = [
                'comments' => $payload['comments'] ?? null,
                'generated_by' => $payload['generated_by'] ?? null,
            ];

            if ($reportCard) {
                $this->reportCardModel->update($reportCard['id'], [
                    'status' => 'generated',
                    'generated_at' => date('Y-m-d H:i:s'),
                    'metadata' => json_encode($payloadMeta),
                ]);
                $reportCard = $this->reportCardModel->find($reportCard['id']);
            } else {
                $reportCardId = $this->reportCardModel->insert([
                    'exam_id' => $examId,
                    'student_id' => $studentId,
                    'status' => 'generated',
                    'generated_at' => date('Y-m-d H:i:s'),
                    'metadata' => json_encode($payloadMeta),
                ], true);
                $reportCard = $this->reportCardModel->find($reportCardId);
            }

            $pdfPath = $this->pdfGenerator->generateReportPdf($reportCard, $student, $scores, $exam);
            $this->reportCardModel->update($reportCard['id'], ['pdf_path' => $pdfPath]);
            $reportCard['pdf_path'] = $pdfPath;
            $reportCard['metadata'] = json_decode($reportCard['metadata'] ?? '[]', true);

            return $this->successResponse($reportCard, 'Report card generated');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to generate report: ' . $e->getMessage());
        }
    }

    public function share(): ResponseInterface
    {
        try {
            $payload = (array) ($this->request->getJSON() ?? []);
            $required = ['report_card_id'];

            if (!$this->validateRequiredFields($payload, $required)) {
                return $this->errorResponse('Missing required fields: ' . implode(', ', $this->getMissingFields($payload, $required)));
            }

            $reportCard = $this->reportCardModel->find((int) $payload['report_card_id']);
            if (!$reportCard) {
                return $this->errorResponse('Report card not found', 404);
            }

            $shareToken = bin2hex(random_bytes(8));
            $expiresAt = !empty($payload['expires_at']) ? $payload['expires_at'] : date('Y-m-d H:i:s', strtotime('+14 days'));

            $this->reportCardModel->update($reportCard['id'], [
                'status' => 'shared',
                'share_token' => $shareToken,
                'expires_at' => $expiresAt,
            ]);

            $reportCard = $this->reportCardModel->find($reportCard['id']);
            $reportCard['metadata'] = json_decode($reportCard['metadata'] ?? '[]', true);

            return $this->successResponse($reportCard, 'Share link created');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to share report card: ' . $e->getMessage());
        }
    }

    public function viewByToken(string $token): ResponseInterface
    {
        try {
            $reportCard = $this->reportCardModel
                ->where('share_token', $token)
                ->first();

            if (!$reportCard) {
                return $this->errorResponse('Report card not found', 404);
            }

            if (!empty($reportCard['expires_at']) && strtotime($reportCard['expires_at']) < time()) {
                return $this->errorResponse('Share link has expired', 403);
            }

            $scores = $this->scoreModel->scoresForStudent($reportCard['exam_id'], $reportCard['student_id']);
            $student = $this->studentsModel->find($reportCard['student_id']);
            $exam = $this->examModel->find($reportCard['exam_id']);

            $reportCard['metadata'] = json_decode($reportCard['metadata'] ?? '[]', true);

            return $this->successResponse([
                'report_card' => $reportCard,
                'student' => $student,
                'exam' => $exam,
                'scores' => $scores,
            ], 'Report card retrieved');
        } catch (\Throwable $e) {
            return $this->errorResponse('Unable to view report card: ' . $e->getMessage());
        }
    }
}
