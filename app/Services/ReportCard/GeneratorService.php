<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardModel;
use App\Models\ReportCardVersionModel;
use App\Models\ReportCardTemplateModel;
use App\Services\ReportCard\RendererService;

class GeneratorService
{
    protected $reportCardModel;
    protected $versionModel;
    protected $templateModel;
    protected $rendererService;
    protected $db;

    public function __construct()
    {
        $this->reportCardModel = new ReportCardModel();
        $this->versionModel = new ReportCardVersionModel();
        $this->templateModel = new ReportCardTemplateModel();
        $this->rendererService = new RendererService();
        $this->db = \Config\Database::connect();
    }

    /**
     * Bulk generate report cards
     *
     * @param array $params Generation parameters
     * @return array Result with job_id, status, count
     */
    public function bulkGenerate(array $params): array
    {
        $schoolId = $params['school_id'];
        $templateId = $params['template_id'];
        $templateVersion = $params['template_version'] ?? null;
        $classId = $params['class_id'] ?? null;
        $studentIds = $params['student_ids'] ?? [];
        $term = $params['term'];
        $academicYear = $params['academic_year'];
        $userId = $params['user_id'];
        $options = $params['options'] ?? [];

        // Get template
        $template = $this->templateModel->getTemplateVersion($templateId, $templateVersion);
        if (!$template) {
            return [
                'IsSuccess' => false,
                'Message' => 'Template not found',
            ];
        }

        // Get students
        if (empty($studentIds) && $classId) {
            $studentIds = $this->getClassStudents($classId, $schoolId);
        }

        if (empty($studentIds)) {
            return [
                'IsSuccess' => false,
                'Message' => 'No students found',
            ];
        }

        // Process in batches
        $batchSize = 50;
        $batches = array_chunk($studentIds, $batchSize);
        $results = [
            'total' => count($studentIds),
            'created' => 0,
            'skipped' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($batches as $batch) {
            $this->processBatch($batch, [
                'school_id' => $schoolId,
                'class_id' => $classId,
                'term' => $term,
                'academic_year' => $academicYear,
                'template' => $template,
                'user_id' => $userId,
                'options' => $options,
            ], $results);
        }

        return [
            'IsSuccess' => true,
            'Data' => $results,
        ];
    }

    /**
     * Process a batch of students
     */
    protected function processBatch(array $studentIds, array $params, array &$results): void
    {
        foreach ($studentIds as $studentId) {
            $this->db->transStart();

            try {
                // Check for duplicates
                $existing = $this->reportCardModel->checkDuplicate(
                    $studentId,
                    $params['school_id'],
                    $params['term'],
                    $params['academic_year']
                );

                if ($existing) {
                    $results['skipped']++;
                    $this->db->transComplete();
                    continue;
                }

                // Create report card
                $rcData = [
                    'school_id' => $params['school_id'],
                    'student_id' => $studentId,
                    'class_id' => $params['class_id'],
                    'term' => $params['term'],
                    'academic_year' => $params['academic_year'],
                    'template_id' => $params['template']['template_id'],
                    'template_version' => $params['template']['version'],
                    'status' => 'draft',
                    'created_by' => $params['user_id'],
                ];

                $rcId = $this->reportCardModel->insert($rcData);

                if ($rcId) {
                    // Generate initial payload
                    $studentData = $this->getStudentData($studentId, $params);
                    $payload = $this->rendererService->assemblePayload($params['template'], $studentData);
                    $summary = $this->rendererService->calculateSummary($payload);

                    // Create version
                    $this->versionModel->createVersion(
                        $rcId,
                        json_encode($payload),
                        json_encode($summary),
                        $params['user_id']
                    );

                    $results['created']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Failed to create report card for student $studentId";
                }

                $this->db->transComplete();
            } catch (\Exception $e) {
                $this->db->transRollback();
                $results['failed']++;
                $results['errors'][] = "Error for student $studentId: " . $e->getMessage();
            }
        }
    }

    /**
     * Get class students
     */
    protected function getClassStudents($classId, $schoolId): array
    {
        $builder = $this->db->table('student_courses sc')
            ->select('sc.user_id')
            ->where('sc.class_id', $classId)
            ->where('sc.school_id', $schoolId)
            ->where('sc.status', 1);

        $results = $builder->get()->getResultArray();
        return array_column($results, 'user_id');
    }

    /**
     * Get student data for report card
     */
    protected function getStudentData($studentId, array $params): array
    {
        // Fetch student info
        $student = $this->db->table('user')
            ->select('id, first_name, last_name, email')
            ->where('id', $studentId)
            ->get()
            ->getRowArray();

        $studentName = ($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '');

        // Fetch school info
        $school = $this->db->table('school')
            ->select('school_name, logo')
            ->where('id', $params['school_id'])
            ->get()
            ->getRowArray();

        // Fetch grades (simplified - would need actual grade data)
        $subjects = [];
        // TODO: Fetch actual grades from student_courses or grade tables

        return [
            'student' => [
                'id' => $studentId,
                'name' => $studentName,
                'email' => $student['email'] ?? '',
            ],
            'school' => [
                'name' => $school['school_name'] ?? '',
                'logo' => $school['logo'] ?? '',
            ],
            'term' => $params['term'],
            'academic_year' => $params['academic_year'],
            'subjects' => $subjects,
            'attendance' => [],
            'rubric' => [],
            'comments' => [],
            'summary' => [],
        ];
    }
}
