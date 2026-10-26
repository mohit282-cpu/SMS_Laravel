<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BikramSambatService;
use InvalidArgumentException;

class BikramSambatServiceTest extends TestCase
{
    /**
     * @var BikramSambatService
     */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BikramSambatService();
    }

    /**
     * Test verified known AD to BS conversion pairs.
     */
    public function test_verified_ad_to_bs_conversions()
    {
        // 1944-01-01 AD = 2000-09-17 BS
        $bsStart = $this->service->adToBs('1944-01-01');
        $this->assertEquals(2000, $bsStart['year']);
        $this->assertEquals(9, $bsStart['month']);
        $this->assertEquals(17, $bsStart['day']);
        $this->assertEquals('2000-09-17', $bsStart['bs_date']);
        $this->assertEquals('पुस', $bsStart['month_name_ne']);

        // 2023-04-14 AD = 2080-01-01 BS
        $bs2080 = $this->service->adToBs('2023-04-14');
        $this->assertEquals(2080, $bs2080['year']);
        $this->assertEquals(1, $bs2080['month']);
        $this->assertEquals(1, $bs2080['day']);
        $this->assertEquals('2080-01-01', $bs2080['bs_date']);
        $this->assertEquals('वैशाख', $bs2080['month_name_ne']);

        // 2024-04-13 AD = 2081-01-01 BS
        $bs2081 = $this->service->adToBs('2024-04-13');
        $this->assertEquals(2081, $bs2081['year']);
        $this->assertEquals(1, $bs2081['month']);
        $this->assertEquals(1, $bs2081['day']);

        // 2026-10-24 AD = 2083-07-07 BS
        $bsToday = $this->service->adToBs('2026-10-24');
        $this->assertEquals(2083, $bsToday['year']);
        $this->assertEquals(7, $bsToday['month']);
        $this->assertEquals(7, $bsToday['day']);
        $this->assertEquals('2083-07-07', $bsToday['bs_date']);
        $this->assertEquals('कार्तिक', $bsToday['month_name_ne']);
    }

    /**
     * Test verified known BS to AD conversions.
     */
    public function test_verified_bs_to_ad_conversions()
    {
        // 2000-01-01 BS = 1943-04-14 AD
        $adStart = $this->service->bsToAd('2000-01-01');
        $this->assertEquals('1943-04-14', $adStart['ad_date']);

        // 2080-01-01 BS = 2023-04-14 AD
        $ad2080 = $this->service->bsToAd('2080-01-01');
        $this->assertEquals('2023-04-14', $ad2080['ad_date']);

        // 2081-01-01 BS = 2024-04-13 AD
        $ad2081 = $this->service->bsToAd('2081-01-01');
        $this->assertEquals('2024-04-13', $ad2081['ad_date']);

        // 2083-07-07 BS = 2026-10-24 AD
        $adToday = $this->service->bsToAd('2083-07-07');
        $this->assertEquals('2026-10-24', $adToday['ad_date']);
    }

    /**
     * Test month length boundary validation.
     */
    public function test_bs_month_boundaries_and_validations()
    {
        // Baishakh 2081 has 31 days. Day 31 is valid, Day 32 must throw exception.
        $this->assertTrue($this->service->validateBsDate(2081, 1, 31));

        $this->expectException(InvalidArgumentException::class);
        $this->service->validateBsDate(2081, 1, 32);
    }

    /**
     * Test month 13 throws exception.
     */
    public function test_invalid_bs_month_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->validateBsDate(2081, 13, 1);
    }

    /**
     * Test unsupported BS year throws exception.
     */
    public function test_out_of_range_bs_year_throws_exception()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->validateBsDate(2095, 1, 1);
    }

    /**
     * Test formatBs and formatDual methods.
     */
    public function test_bs_formatting_and_dual_display()
    {
        // AD 2026-10-24 is 2083-07-07 (7 Kartik 2083)
        $formattedNe = $this->service->formatBs('2026-10-24', 'ne');
        $this->assertStringContainsString('कार्तिक', $formattedNe);
        $this->assertStringContainsString('२०८३', $formattedNe);

        $formattedEn = $this->service->formatBs('2026-10-24', 'en');
        $this->assertEquals('7 Kartik 2083', $formattedEn);

        $dualNe = $this->service->formatDual('2026-10-24', 'ne');
        $this->assertStringContainsString('कार्तिक', $dualNe);
        $this->assertStringContainsString('(2026-10-24 AD)', $dualNe);

        $dualEn = $this->service->formatDual('2026-10-24', 'en');
        $this->assertStringContainsString('24 Oct 2026', $dualEn);
        $this->assertStringContainsString('(2083-07-07 BS)', $dualEn);
    }

    /**
     * Test BS date range conversion for SQL queries.
     */
    public function test_bs_range_to_ad_range()
    {
        [$fromAd, $toAd] = $this->service->bsRangeToAdRange('2083-01-01', '2083-07-07');
        $this->assertNotNull($fromAd);
        $this->assertEquals('2026-10-24', $toAd);
    }
}
