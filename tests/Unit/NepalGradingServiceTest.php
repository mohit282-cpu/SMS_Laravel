<?php

namespace Tests\Unit;

use App\Services\NepalGradingService;
use Tests\TestCase;

class NepalGradingServiceTest extends TestCase
{
    /**
     * @var NepalGradingService
     */
    protected $grading;

    protected function setUp(): void
    {
        parent::setUp();
        $this->grading = new NepalGradingService();
    }

    /**
     * Test verified Nepal letter grade boundaries and GPA points.
     */
    public function test_grade_boundaries_and_grade_points()
    {
        // 90 - 100% -> A+ (4.0)
        $aPlus = $this->grading->getGradeAndGpa(95.5);
        $this->assertEquals('A+', $aPlus['grade']);
        $this->assertEquals(4.0, $aPlus['gpa']);
        $this->assertTrue($aPlus['is_passed']);

        // 80 - 89.9% -> A (3.6)
        $a = $this->grading->getGradeAndGpa(84);
        $this->assertEquals('A', $a['grade']);
        $this->assertEquals(3.6, $a['gpa']);

        // 70 - 79.9% -> B+ (3.2)
        $bPlus = $this->grading->getGradeAndGpa(75);
        $this->assertEquals('B+', $bPlus['grade']);
        $this->assertEquals(3.2, $bPlus['gpa']);

        // 60 - 69.9% -> B (2.8)
        $b = $this->grading->getGradeAndGpa(62.5);
        $this->assertEquals('B', $b['grade']);
        $this->assertEquals(2.8, $b['gpa']);

        // 50 - 59.9% -> C+ (2.4)
        $cPlus = $this->grading->getGradeAndGpa(55);
        $this->assertEquals('C+', $cPlus['grade']);
        $this->assertEquals(2.4, $cPlus['gpa']);

        // 40 - 49.9% -> C (2.0)
        $c = $this->grading->getGradeAndGpa(42);
        $this->assertEquals('C', $c['grade']);
        $this->assertEquals(2.0, $c['gpa']);

        // 35 - 39.9% -> D (1.6)
        $d = $this->grading->getGradeAndGpa(36.5);
        $this->assertEquals('D', $d['grade']);
        $this->assertEquals(1.6, $d['gpa']);

        // Below 35% -> Non-Graded (NG, 0.0)
        $ng = $this->grading->getGradeAndGpa(30);
        $this->assertEquals('NG', $ng['grade']);
        $this->assertEquals(0.0, $ng['gpa']);
        $this->assertFalse($ng['is_passed']);
    }

    /**
     * Test absent status handling.
     */
    public function test_absent_student_handling()
    {
        $absent = $this->grading->getGradeAndGpa(0, true, false);
        $this->assertEquals('ABS', $absent['grade']);
        $this->assertEquals(0.0, $absent['gpa']);
        $this->assertFalse($absent['is_passed']);
    }

    /**
     * Test exempt status handling.
     */
    public function test_exempt_student_handling()
    {
        $exempt = $this->grading->getGradeAndGpa(0, false, true);
        $this->assertEquals('EXM', $exempt['grade']);
        $this->assertEquals(0.0, $exempt['gpa']);
        $this->assertTrue($exempt['is_passed']);
    }

    /**
     * Test cumulative GPA and overall result calculation.
     */
    public function test_cumulative_gpa_and_overall_result()
    {
        $subjects = [
            ['gpa' => 4.0, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => true],
            ['gpa' => 3.6, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => true],
            ['gpa' => 3.2, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => true],
            ['gpa' => 2.8, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => true],
        ];

        // Total credit: 16. Total weighted: 16.0 + 14.4 + 12.8 + 11.2 = 54.4 / 16 = 3.4
        $overall = $this->grading->calculateOverallResult($subjects);
        $this->assertEquals(3.4, $overall['gpa']);
        $this->assertEquals('A', $overall['overall_grade']);
        $this->assertEquals('PASS', $overall['result_status']);
    }

    /**
     * Test failed subject causes FAIL result.
     */
    public function test_failed_subject_causes_fail_status()
    {
        $subjects = [
            ['gpa' => 4.0, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => true],
            ['gpa' => 0.0, 'credit_hour' => 4.0, 'is_absent' => false, 'is_exempt' => false, 'is_passed' => false], // Failed
        ];

        $overall = $this->grading->calculateOverallResult($subjects);
        $this->assertEquals('FAIL', $overall['result_status']);
    }
}
