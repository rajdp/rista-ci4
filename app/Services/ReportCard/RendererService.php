<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardTemplateModel;

class RendererService
{
    protected $templateModel;

    public function __construct()
    {
        $this->templateModel = new ReportCardTemplateModel();
    }

    /**
     * Assemble report card payload from template and data
     *
     * @param array $template Template definition
     * @param array $studentData Student and academic data
     * @return array Complete payload JSON
     */
    public function assemblePayload(array $template, array $studentData): array
    {
        $schema = json_decode($template['schema_json'] ?? '{}', true);

        $payload = [
            'template_id' => $template['template_id'],
            'template_version' => $template['version'],
            'generated_at' => date('Y-m-d H:i:s'),
            'student' => $studentData['student'] ?? [],
            'school' => $studentData['school'] ?? [],
            'term' => $studentData['term'] ?? '',
            'academic_year' => $studentData['academic_year'] ?? '',
            'sections' => [],
        ];

        // Process each section from template
        if (isset($schema['sections'])) {
            foreach ($schema['sections'] as $section) {
                $payload['sections'][] = $this->processSection($section, $studentData);
            }
        }

        return $payload;
    }

    /**
     * Process individual section
     */
    protected function processSection(array $section, array $data): array
    {
        $processed = [
            'id' => $section['id'] ?? '',
            'title' => $section['title'] ?? '',
            'type' => $section['type'] ?? '',
            'data' => [],
        ];

        switch ($section['type']) {
            case 'subjects_grid':
                $processed['data'] = $this->processSubjectsGrid($section, $data);
                break;
            case 'attendance':
                $processed['data'] = $this->processAttendance($section, $data);
                break;
            case 'rubric':
                $processed['data'] = $this->processRubric($section, $data);
                break;
            case 'long_text':
                $processed['data'] = $this->processLongText($section, $data);
                break;
            case 'summary':
                $processed['data'] = $this->processSummary($section, $data);
                break;
        }

        return $processed;
    }

    protected function processSubjectsGrid(array $section, array $data): array
    {
        return [
            'columns' => $section['columns'] ?? [],
            'subjects' => $data['subjects'] ?? [],
        ];
    }

    protected function processAttendance(array $section, array $data): array
    {
        return $data['attendance'] ?? [
            'days_present' => 0,
            'days_absent' => 0,
            'days_tardy' => 0,
        ];
    }

    protected function processRubric(array $section, array $data): array
    {
        $items = [];
        foreach ($section['items'] ?? [] as $item) {
            $items[] = [
                'criteria' => $item,
                'rating' => $data['rubric'][$item] ?? 'Not Assessed',
            ];
        }
        return [
            'scale' => $section['scale'] ?? [],
            'items' => $items,
        ];
    }

    protected function processLongText(array $section, array $data): array
    {
        $sectionId = $section['id'] ?? '';
        return [
            'content' => $data['comments'][$sectionId] ?? '',
            'max_length' => $section['max_length'] ?? 500,
        ];
    }

    protected function processSummary(array $section, array $data): array
    {
        return $data['summary'] ?? [
            'term_gpa' => 0.0,
            'cumulative_gpa' => 0.0,
            'credits_earned' => 0,
            'credits_attempted' => 0,
        ];
    }

    /**
     * Calculate summary statistics
     */
    public function calculateSummary(array $payload): array
    {
        $summary = [
            'student_id' => $payload['student']['id'] ?? null,
            'student_name' => $payload['student']['name'] ?? '',
            'term' => $payload['term'] ?? '',
            'academic_year' => $payload['academic_year'] ?? '',
        ];

        // Extract GPA if available
        foreach ($payload['sections'] ?? [] as $section) {
            if ($section['type'] === 'summary') {
                $summary['overall_gpa'] = $section['data']['term_gpa'] ?? null;
                $summary['cumulative_gpa'] = $section['data']['cumulative_gpa'] ?? null;
                break;
            }
        }

        return $summary;
    }
}
