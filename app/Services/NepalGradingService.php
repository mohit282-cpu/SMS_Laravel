<?php

namespace App\Services;

class NepalGradingService
{
    /**
     * Standard Nepal Grading Scale (Government Curriculum Development Centre / SEE / NEB guidelines)
     * Grade | Min % | Max % | Grade Point | Description | Nepali Remark
     */
    public const STANDARD_SCALE = [
        ['grade' => 'A+', 'min' => 90, 'max' => 100, 'gpa' => 4.0, 'remark' => 'Outstanding', 'remark_ne' => 'विशिष्ट (Outstanding)'],
        ['grade' => 'A',  'min' => 80, 'max' => 89.99, 'gpa' => 3.6, 'remark' => 'Excellent',   'remark_ne' => 'उत्कृष्ट (Excellent)'],
        ['grade' => 'B+', 'min' => 70, 'max' => 79.99, 'gpa' => 3.2, 'remark' => 'Very Good',   'remark_ne' => 'धेरै राम्रो (Very Good)'],
        ['grade' => 'B',  'min' => 60, 'max' => 69.99, 'gpa' => 2.8, 'remark' => 'Good',        'remark_ne' => 'राम्रो (Good)'],
        ['grade' => 'C+', 'min' => 50, 'max' => 59.99, 'gpa' => 2.4, 'remark' => 'Satisfactory', 'remark_ne' => 'सन्तोषजनक (Satisfactory)'],
        ['grade' => 'C',  'min' => 40, 'max' => 49.99, 'gpa' => 2.0, 'remark' => 'Acceptable',  'remark_ne' => 'स्वीकार्य (Acceptable)'],
        ['grade' => 'D',  'min' => 35, 'max' => 39.99, 'gpa' => 1.6, 'remark' => 'Basic',       'remark_ne' => 'आधारभूत (Basic)'],
        ['grade' => 'NG', 'min' => 0,  'max' => 34.99, 'gpa' => 0.0, 'remark' => 'Non-Graded',  'remark_ne' => 'अवर्गीकृत (Non-Graded)'],
    ];

    /**
     * Get Grade and GPA Point from percentage or marks obtained out of total.
     *
     * @param float|int $percentage
     * @param bool $isAbsent
     * @param bool $isExempt
     * @return array ['grade' => 'A+', 'gpa' => 4.0, 'remark' => 'Outstanding', 'remark_ne' => 'विशिष्ट']
     */
    public function getGradeAndGpa($percentage, bool $isAbsent = false, bool $isExempt = false): array
    {
        if ($isAbsent) {
            return [
                'grade' => 'ABS',
                'gpa' => 0.0,
                'remark' => 'Absent',
                'remark_ne' => 'अनुपस्थित (Absent)',
                'is_passed' => false,
            ];
        }

        if ($isExempt) {
            return [
                'grade' => 'EXM',
                'gpa' => 0.0,
                'remark' => 'Exempted',
                'remark_ne' => 'छुट (Exempted)',
                'is_passed' => true,
            ];
        }

        $p = max(0, min(100, (float)$percentage));

        foreach (self::STANDARD_SCALE as $item) {
            if ($p >= $item['min'] && $p <= $item['max']) {
                return [
                    'grade' => $item['grade'],
                    'gpa' => $item['gpa'],
                    'remark' => $item['remark'],
                    'remark_ne' => $item['remark_ne'],
                    'is_passed' => ($item['grade'] !== 'NG'),
                ];
            }
        }

        return [
            'grade' => 'NG',
            'gpa' => 0.0,
            'remark' => 'Non-Graded',
            'remark_ne' => 'अवर्गीकृत (Non-Graded)',
            'is_passed' => false,
        ];
    }

    /**
     * Convert GPA point (0.0 to 4.0) into overall letter grade.
     *
     * @param float $gpa
     * @return string
     */
    public function gpaToGrade(float $gpa): string
    {
        if ($gpa >= 3.6) return 'A+';
        if ($gpa >= 3.2) return 'A';
        if ($gpa >= 2.8) return 'B+';
        if ($gpa >= 2.4) return 'B';
        if ($gpa >= 2.0) return 'C+';
        if ($gpa >= 1.6) return 'C';
        if ($gpa >= 1.2) return 'D';
        return 'NG';
    }

    /**
     * Calculate cumulative GPA from an array of subject marks and credit hours.
     * Each item in $subjectRecords: ['gpa' => float, 'credit_hour' => float, 'is_absent' => bool, 'is_exempt' => bool, 'is_passed' => bool]
     *
     * @param array $subjectRecords
     * @return array ['gpa' => 3.45, 'overall_grade' => 'A', 'result_status' => 'PASS'|'FAIL']
     */
    public function calculateOverallResult(array $subjectRecords): array
    {
        $totalWeightedGpa = 0.0;
        $totalCredits = 0.0;
        $hasFailedSubject = false;
        $hasAbsentSubject = false;
        $validSubjectsCount = 0;

        foreach ($subjectRecords as $sub) {
            if (!empty($sub['is_exempt'])) {
                continue; // Exempted subject does not drag down GPA calculation
            }

            if (!empty($sub['is_absent'])) {
                $hasAbsentSubject = true;
                $hasFailedSubject = true;
            }

            if (isset($sub['is_passed']) && !$sub['is_passed']) {
                $hasFailedSubject = true;
            }

            $credit = (float)($sub['credit_hour'] ?? 4.0);
            $gpa = (float)($sub['gpa'] ?? 0.0);

            $totalWeightedGpa += ($gpa * $credit);
            $totalCredits += $credit;
            $validSubjectsCount++;
        }

        $calculatedGpa = ($totalCredits > 0) ? round($totalWeightedGpa / $totalCredits, 2) : 0.0;

        $overallGrade = $this->gpaToGrade($calculatedGpa);

        $resultStatus = 'PASS';
        if ($hasFailedSubject || $overallGrade === 'NG') {
            $resultStatus = 'FAIL';
        }
        if ($validSubjectsCount === 0) {
            $resultStatus = 'PENDING';
        }

        return [
            'gpa' => $calculatedGpa,
            'overall_grade' => $overallGrade,
            'result_status' => $resultStatus,
            'total_credits' => $totalCredits,
        ];
    }
}
