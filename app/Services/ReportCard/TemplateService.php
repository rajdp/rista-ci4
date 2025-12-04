<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardTemplateModel;

class TemplateService
{
    protected $templateModel;

    public function __construct()
    {
        $this->templateModel = new ReportCardTemplateModel();
    }

    /**
     * Validate template schema
     *
     * @param array $schema
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function validateSchema(array $schema): array
    {
        $errors = [];

        // Check required top-level keys
        if (!isset($schema['header'])) {
            $errors[] = 'Schema must have a "header" section';
        }

        if (!isset($schema['sections']) || !is_array($schema['sections'])) {
            $errors[] = 'Schema must have a "sections" array';
        } else {
            // Validate each section
            foreach ($schema['sections'] as $index => $section) {
                if (!isset($section['id'])) {
                    $errors[] = "Section at index $index must have an 'id'";
                }
                if (!isset($section['title'])) {
                    $errors[] = "Section at index $index must have a 'title'";
                }
                if (!isset($section['type'])) {
                    $errors[] = "Section at index $index must have a 'type'";
                }
            }
        }

        if (!isset($schema['footer'])) {
            $errors[] = 'Schema must have a "footer" section';
        }

        return [
            'isValid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    /**
     * Generate preview HTML with sample data
     *
     * @param array $schema
     * @param array $sampleData
     * @return string HTML preview
     */
    public function generatePreview(array $schema, array $sampleData = null): string
    {
        if ($sampleData === null) {
            $sampleData = $this->getSampleData();
        }

        $html = '<div class="report-card-preview">';

        // Header
        $html .= $this->renderHeader($schema['header'] ?? [], $sampleData);

        // Sections
        if (isset($schema['sections'])) {
            foreach ($schema['sections'] as $section) {
                $html .= $this->renderSection($section, $sampleData);
            }
        }

        // Footer
        $html .= $this->renderFooter($schema['footer'] ?? [], $sampleData);

        $html .= '</div>';

        return $html;
    }

    /**
     * Get sample data for preview
     */
    protected function getSampleData(): array
    {
        return [
            'student' => [
                'name' => 'John Doe',
                'id' => '12345',
                'grade' => '5th Grade',
                'homeroom' => 'Room 101',
            ],
            'school' => [
                'name' => 'Sample Elementary School',
                'logo' => '/assets/logo.png',
            ],
            'term' => 'Fall 2025',
            'academic_year' => '2025-26',
            'subjects' => [
                ['name' => 'Math', 'grade' => 'A', 'teacher_comments' => 'Excellent work'],
                ['name' => 'English', 'grade' => 'B+', 'teacher_comments' => 'Good progress'],
            ],
        ];
    }

    protected function renderHeader(array $header, array $data): string
    {
        $html = '<div class="rc-header">';
        if ($header['show_school_logo'] ?? false) {
            $html .= '<img src="' . ($data['school']['logo'] ?? '') . '" alt="School Logo" class="school-logo">';
        }
        $html .= '<h2>' . ($data['school']['name'] ?? 'School Name') . '</h2>';
        $html .= '<h3>Report Card</h3>';
        if ($header['show_term_info'] ?? false) {
            $html .= '<p>' . ($data['term'] ?? '') . ' - ' . ($data['academic_year'] ?? '') . '</p>';
        }
        $html .= '</div>';
        return $html;
    }

    protected function renderSection(array $section, array $data): string
    {
        $html = '<div class="rc-section" data-section-id="' . ($section['id'] ?? '') . '">';
        $html .= '<h4>' . ($section['title'] ?? '') . '</h4>';

        switch ($section['type'] ?? '') {
            case 'subjects_grid':
                $html .= $this->renderSubjectsGrid($section, $data);
                break;
            case 'attendance':
                $html .= $this->renderAttendance($section, $data);
                break;
            case 'rubric':
                $html .= $this->renderRubric($section, $data);
                break;
            case 'long_text':
                $html .= $this->renderLongText($section, $data);
                break;
            case 'summary':
                $html .= $this->renderSummary($section, $data);
                break;
            default:
                $html .= '<p>[Section type: ' . ($section['type'] ?? 'unknown') . ']</p>';
        }

        $html .= '</div>';
        return $html;
    }

    protected function renderSubjectsGrid(array $section, array $data): string
    {
        $html = '<table class="subjects-grid"><thead><tr>';
        $columns = $section['columns'] ?? ['subject', 'grade'];
        foreach ($columns as $col) {
            $html .= '<th>' . ucfirst(str_replace('_', ' ', $col)) . '</th>';
        }
        $html .= '</tr></thead><tbody>';

        $subjects = $data['subjects'] ?? [];
        foreach ($subjects as $subject) {
            $html .= '<tr>';
            foreach ($columns as $col) {
                $html .= '<td>' . ($subject[$col] ?? '-') . '</td>';
            }
            $html .= '</tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    protected function renderAttendance(array $section, array $data): string
    {
        $html = '<div class="attendance">';
        $html .= '<p>Days Present: 90</p>';
        $html .= '<p>Days Absent: 5</p>';
        $html .= '<p>Days Tardy: 2</p>';
        $html .= '</div>';
        return $html;
    }

    protected function renderRubric(array $section, array $data): string
    {
        $html = '<table class="rubric"><thead><tr><th>Criteria</th><th>Rating</th></tr></thead><tbody>';
        $items = $section['items'] ?? [];
        foreach ($items as $item) {
            $html .= '<tr><td>' . $item . '</td><td>Excellent</td></tr>';
        }
        $html .= '</tbody></table>';
        return $html;
    }

    protected function renderLongText(array $section, array $data): string
    {
        return '<div class="long-text"><p>Sample teacher comments would appear here.</p></div>';
    }

    protected function renderSummary(array $section, array $data): string
    {
        $html = '<div class="summary">';
        $html .= '<p>Term GPA: 3.5</p>';
        $html .= '<p>Cumulative GPA: 3.6</p>';
        $html .= '<p>Credits Earned: 15</p>';
        $html .= '</div>';
        return $html;
    }

    protected function renderFooter(array $footer, array $data): string
    {
        $html = '<div class="rc-footer">';
        $html .= '<div class="signatures">';
        $signatures = $footer['signatures'] ?? [];
        foreach ($signatures as $sig) {
            $html .= '<div class="signature"><p>_____________</p><p>' . ucfirst($sig) . ' Signature</p></div>';
        }
        $html .= '</div>';
        if ($footer['show_date'] ?? false) {
            $html .= '<p class="date">Date: ' . date('F j, Y') . '</p>';
        }
        $html .= '</div>';
        return $html;
    }
}
