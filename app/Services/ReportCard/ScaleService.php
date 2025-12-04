<?php

namespace App\Services\ReportCard;

use App\Models\ReportCardScaleModel;

class ScaleService
{
    protected $scaleModel;

    public function __construct()
    {
        $this->scaleModel = new ReportCardScaleModel();
    }

    /**
     * Convert numeric grade to letter grade using scale
     *
     * @param float $numericGrade
     * @param array $scale Scale definition array
     * @return array ['letter' => string, 'gpa' => float, 'description' => string]
     */
    public function gradeToLetter(float $numericGrade, array $scale): array
    {
        foreach ($scale as $range) {
            $min = $range['min'] ?? 0;
            $max = $range['max'] ?? 100;

            if ($numericGrade >= $min && $numericGrade <= $max) {
                return [
                    'letter' => $range['letter'] ?? '',
                    'gpa' => $range['gpa'] ?? 0.0,
                    'description' => $range['description'] ?? '',
                ];
            }
        }

        // Default if no match
        return [
            'letter' => 'N/A',
            'gpa' => 0.0,
            'description' => 'No grade',
        ];
    }

    /**
     * Calculate GPA from grades
     *
     * @param array $grades Array of grade objects with 'numeric' and 'credits' keys
     * @param array $scale Scale definition
     * @return float GPA
     */
    public function calculateGPA(array $grades, array $scale): float
    {
        $totalPoints = 0;
        $totalCredits = 0;

        foreach ($grades as $grade) {
            $numericGrade = $grade['numeric'] ?? 0;
            $credits = $grade['credits'] ?? 1;

            $letterData = $this->gradeToLetter($numericGrade, $scale);
            $gpa = $letterData['gpa'];

            $totalPoints += $gpa * $credits;
            $totalCredits += $credits;
        }

        if ($totalCredits == 0) {
            return 0.0;
        }

        return round($totalPoints / $totalCredits, 2);
    }

    /**
     * Validate scale definition
     *
     * @param array $scale
     * @return array ['isValid' => bool, 'errors' => array]
     */
    public function validateScale(array $scale): array
    {
        $errors = [];

        if (empty($scale)) {
            $errors[] = 'Scale cannot be empty';
            return ['isValid' => false, 'errors' => $errors];
        }

        foreach ($scale as $index => $range) {
            if (!isset($range['min'])) {
                $errors[] = "Range at index $index must have 'min' value";
            }
            if (!isset($range['max'])) {
                $errors[] = "Range at index $index must have 'max' value";
            }
            if (!isset($range['letter'])) {
                $errors[] = "Range at index $index must have 'letter' value";
            }
            if (!isset($range['gpa'])) {
                $errors[] = "Range at index $index must have 'gpa' value";
            }

            // Check that min <= max
            if (isset($range['min']) && isset($range['max']) && $range['min'] > $range['max']) {
                $errors[] = "Range at index $index: min cannot be greater than max";
            }
        }

        // Check for overlapping ranges
        for ($i = 0; $i < count($scale); $i++) {
            for ($j = $i + 1; $j < count($scale); $j++) {
                if ($this->rangesOverlap($scale[$i], $scale[$j])) {
                    $errors[] = "Ranges at indices $i and $j overlap";
                }
            }
        }

        return [
            'isValid' => count($errors) === 0,
            'errors' => $errors,
        ];
    }

    /**
     * Check if two ranges overlap
     */
    protected function rangesOverlap(array $range1, array $range2): bool
    {
        $min1 = $range1['min'] ?? 0;
        $max1 = $range1['max'] ?? 100;
        $min2 = $range2['min'] ?? 0;
        $max2 = $range2['max'] ?? 100;

        return ($min1 <= $max2 && $max1 >= $min2);
    }

    /**
     * Get scale by ID
     */
    public function getScale($scaleId, $schoolId): ?array
    {
        $scale = $this->scaleModel->getScaleForSchool($scaleId, $schoolId);
        if ($scale && isset($scale['scale_json'])) {
            $scale['scale_data'] = json_decode($scale['scale_json'], true);
        }
        return $scale;
    }
}
