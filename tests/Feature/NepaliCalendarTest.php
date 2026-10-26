<?php

namespace Tests\Feature;

use App\Services\BikramSambatService;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class NepaliCalendarTest extends TestCase
{
    protected BikramSambatService $bsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->bsService = app(BikramSambatService::class);
    }

    /**
     * Test valid BS to AD conversion.
     */
    public function test_valid_bs_to_ad_conversion()
    {
        // 2000-01-01 BS = 1943-04-14 AD
        $ad1 = $this->bsService->bsToAd('2000-01-01');
        $this->assertEquals('1943-04-14', $ad1['ad_date']);

        // 2083-01-01 BS = 2026-04-14 AD (Nepali New Year 2083)
        $ad2 = $this->bsService->bsToAd('2083-01-01');
        $this->assertEquals('2026-04-14', $ad2['ad_date']);

        // 2083-07-09 BS = 2026-10-26 AD
        $ad3 = $this->bsService->bsToAd('2083-07-09');
        $this->assertEquals('2026-10-26', $ad3['ad_date']);
    }

    /**
     * Test valid AD to BS conversion.
     */
    public function test_valid_ad_to_bs_conversion()
    {
        // 2026-04-14 AD = 2083-01-01 BS
        $bs1 = $this->bsService->adToBs('2026-04-14');
        $this->assertEquals('2083-01-01', $bs1['bs_date']);
        $this->assertEquals('वैशाख', $bs1['month_name_ne']);
        $this->assertEquals('Baishakh', $bs1['month_name_en']);

        // 2026-10-26 AD = 2083-07-09 BS
        $bs2 = $this->bsService->adToBs('2026-10-26');
        $this->assertEquals('2083-07-09', $bs2['bs_date']);
        $this->assertEquals('कार्तिक', $bs2['month_name_ne']);
        $this->assertEquals('सोमबार', $bs2['day_name_ne']);
    }

    /**
     * Test round-trip conversion for supported dates across years.
     */
    public function test_round_trip_conversion_for_supported_dates()
    {
        $testDates = [
            '2000-01-01',
            '2020-05-15',
            '2050-08-20',
            '2075-10-12',
            '2080-01-01',
            '2082-06-30',
            '2083-07-09',
            '2085-11-25',
            '2089-12-28',
        ];

        foreach ($testDates as $bsDate) {
            $convAd = $this->bsService->bsToAd($bsDate);
            $convBs = $this->bsService->adToBs($convAd['ad_date']);
            $this->assertEquals($bsDate, $convBs['bs_date'], "Round trip failed for BS date: {$bsDate}");
        }
    }

    /**
     * Test BS month boundaries and year transitions.
     */
    public function test_bs_month_boundaries_and_year_transitions()
    {
        // Year 2083 Month lengths: 31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30
        $daysInKartik2083 = $this->bsService->getDaysInBsMonth(2083, 7);
        $this->assertEquals(30, $daysInKartik2083);

        // Valid last day of Kartik 2083
        $lastDay = $this->bsService->bsToAd('2083-07-30');
        $this->assertNotNull($lastDay['ad_date']);

        // First day of next month (Mangsir 2083)
        $nextMonthFirst = $this->bsService->bsToAd('2083-08-01');
        $expectedNextAd = Carbon::parse($lastDay['ad_date'])->addDay()->format('Y-m-d');
        $this->assertEquals($expectedNextAd, $nextMonthFirst['ad_date']);

        // End of year transition: 2082 Chaitra last day to 2083 Baishakh 1
        $daysInChaitra2082 = $this->bsService->getDaysInBsMonth(2082, 12);
        $lastDay2082 = $this->bsService->bsToAd("2082-12-{$daysInChaitra2082}");
        $firstDay2083 = $this->bsService->bsToAd('2083-01-01');
        $expectedNewYearAd = Carbon::parse($lastDay2082['ad_date'])->addDay()->format('Y-m-d');
        $this->assertEquals($expectedNewYearAd, $firstDay2083['ad_date']);
    }

    /**
     * Test invalid BS dates are rejected.
     */
    public function test_invalid_bs_dates_are_rejected()
    {
        // Month 13
        $this->expectException(InvalidArgumentException::class);
        $this->bsService->bsToAd('2083-13-01');
    }

    public function test_invalid_bs_day_exceeding_month_length()
    {
        // Kartik 2083 only has 30 days. Day 31 must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->bsService->bsToAd('2083-07-31');
    }

    public function test_bs_year_out_of_bounds()
    {
        // Year 1999 (before 2000 BS)
        $this->expectException(InvalidArgumentException::class);
        $this->bsService->bsToAd('1999-01-01');
    }

    /**
     * Test date display and formatting.
     */
    public function test_date_display_and_formatting()
    {
        $adDate = '2026-10-26';
        $bsYmd = $this->bsService->formatBs($adDate, 'Y-m-d');
        $this->assertEquals('2083-07-09', $bsYmd);

        $bsEn = $this->bsService->formatBs($adDate, 'en');
        $this->assertEquals('9 Kartik 2083', $bsEn);

        $dualEn = $this->bsService->formatDual($adDate, 'en');
        $this->assertStringContainsString('26 Oct 2026', $dualEn);
        $this->assertStringContainsString('2083-07-09 BS', $dualEn);
    }

    /**
     * Test BS date range to AD date range conversion.
     */
    public function test_bs_date_range_to_ad_range()
    {
        [$fromAd, $toAd] = $this->bsService->bsRangeToAdRange('2083-01-01', '2083-01-31');
        $this->assertEquals('2026-04-14', $fromAd);
        $this->assertEquals('2026-05-14', $toAd);
    }
}
