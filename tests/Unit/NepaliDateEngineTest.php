<?php

namespace Tests\Unit;

use App\Services\NepaliDateService;
use App\User;
use Carbon\Carbon;
use InvalidArgumentException;
use Tests\TestCase;

class NepaliDateEngineTest extends TestCase
{
    /**
     * @var NepaliDateService
     */
    protected $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new NepaliDateService();
    }

    /**
     * Test verified known AD to BS conversion pairs.
     */
    public function test_verified_ad_to_bs_conversions()
    {
        // 1943-04-14 AD = 2000-01-01 BS (Anchor date)
        $bs2000 = $this->service->adToBs('1943-04-14');
        $this->assertEquals(2000, $bs2000['year']);
        $this->assertEquals(1, $bs2000['month']);
        $this->assertEquals(1, $bs2000['day']);
        $this->assertEquals('2000-01-01', $bs2000['bs_date']);
        $this->assertContains($bs2000['month_name_ne'], ['वैशाख', 'बैशाख']);
        $this->assertEquals('बैशाख', $bs2000['standard_month_ne']);

        // 2026-04-28 AD = 2083-01-15 BS (Prompt reference date)
        $bs2083 = $this->service->adToBs('2026-04-28');
        $this->assertEquals(2083, $bs2083['year']);
        $this->assertEquals(1, $bs2083['month']);
        $this->assertEquals(15, $bs2083['day']);
        $this->assertEquals('2083-01-15', $bs2083['bs_date']);
        $this->assertEquals('Baisakh', $bs2083['standard_month_en']);
        $this->assertEquals('बैशाख', $bs2083['standard_month_ne']);

        // 2023-04-14 AD = 2080-01-01 BS
        $bs2080 = $this->service->adToBs('2023-04-14');
        $this->assertEquals(2080, $bs2080['year']);
        $this->assertEquals(1, $bs2080['month']);
        $this->assertEquals(1, $bs2080['day']);
        $this->assertEquals('2080-01-01', $bs2080['bs_date']);

        // 2024-04-13 AD = 2081-01-01 BS
        $bs2081 = $this->service->adToBs('2024-04-13');
        $this->assertEquals(2081, $bs2081['year']);
        $this->assertEquals(1, $bs2081['month']);
        $this->assertEquals(1, $bs2081['day']);
        $this->assertEquals('2081-01-01', $bs2081['bs_date']);
    }

    /**
     * Test verified known BS to AD conversions.
     */
    public function test_verified_bs_to_ad_conversions()
    {
        // 2083-01-15 BS = 2026-04-28 AD
        $ad1 = $this->service->bsToAd('2083-01-15');
        $this->assertEquals(2026, $ad1['year']);
        $this->assertEquals(4, $ad1['month']);
        $this->assertEquals(28, $ad1['day']);
        $this->assertEquals('2026-04-28', $ad1['ad_date']);

        // 2080-01-01 BS = 2023-04-14 AD
        $ad2 = $this->service->bsToAd('2080-01-01');
        $this->assertEquals('2023-04-14', $ad2['ad_date']);

        // 2081-01-01 BS = 2024-04-13 AD
        $ad3 = $this->service->bsToAd(2081, 1, 1);
        $this->assertEquals('2024-04-13', $ad3['ad_date']);

        // 2000-01-01 BS = 1943-04-14 AD
        $adAnchor = $this->service->bsToAd('2000-01-01');
        $this->assertEquals('1943-04-14', $adAnchor['ad_date']);
    }

    /**
     * Test Devanagari digit input parsing and conversion.
     */
    public function test_devanagari_digit_conversion()
    {
        // २०८३-०१-१५ BS -> 2026-04-28 AD
        $conv = $this->service->bsToAd('२०८३-०१-१५');
        $this->assertEquals('2026-04-28', $conv['ad_date']);

        // २०८०-०१-०१ BS -> 2023-04-14 AD
        $conv2 = $this->service->bsToAd('२०८०-०१-०१');
        $this->assertEquals('2023-04-14', $conv2['ad_date']);

        // Parse BS date with Devanagari numerals
        $parsed = $this->service->parseBsDate('२०८३-०५-२२');
        $this->assertEquals(2083, $parsed['year']);
        $this->assertEquals(5, $parsed['month']);
        $this->assertEquals(22, $parsed['day']);
    }

    /**
     * Test round-trip conversion AD -> BS -> AD.
     */
    public function test_round_trip_conversion()
    {
        $testDates = [
            '2023-04-14',
            '2023-12-31',
            '2024-02-29', // Leap day
            '2024-04-13',
            '2025-01-01',
            '2026-04-28',
            '2026-10-24',
        ];

        foreach ($testDates as $adDate) {
            $bs = $this->service->adToBs($adDate);
            $backToAd = $this->service->bsToAd($bs['bs_date']);
            $this->assertEquals($adDate, $backToAd['ad_date'], "Round trip failed for {$adDate}");
        }
    }

    /**
     * Test rejection of invalid BS dates based on exact month lengths.
     */
    public function test_invalid_bs_date_rejection()
    {
        // 2083 Baisakh has 31 days. Day 32 is impossible and must be rejected.
        $this->expectException(InvalidArgumentException::class);
        $this->service->bsToAd('2083-01-32');
    }

    /**
     * Test rejection of invalid month number.
     */
    public function test_invalid_bs_month_rejection()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->bsToAd('2083-13-01');
    }

    /**
     * Test rejection of out-of-range BS years.
     */
    public function test_out_of_range_bs_year()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->service->bsToAd('2095-01-01');
    }

    /**
     * Test all 12 Nepali calendar billing month boundaries.
     */
    public function test_all_12_nepali_billing_month_boundaries()
    {
        $months = $this->service->getMonths();
        $this->assertCount(12, $months);

        // Check Baisakh to Chaitra names
        $expectedNames = [
            1 => 'Baisakh',
            2 => 'Jestha',
            3 => 'Ashadh',
            4 => 'Shrawan',
            5 => 'Bhadra',
            6 => 'Ashwin',
            7 => 'Kartik',
            8 => 'Mangsir',
            9 => 'Poush',
            10 => 'Magh',
            11 => 'Falgun',
            12 => 'Chaitra',
        ];

        foreach ($expectedNames as $mNum => $name) {
            $this->assertEquals($name, $months[$mNum]['en']);
            $range = $this->service->getBsMonthAdRange(2083, $mNum);
            $this->assertNotEmpty($range['start_ad']);
            $this->assertNotEmpty($range['end_ad']);
            $this->assertGreaterThanOrEqual(29, $range['days']);
            $this->assertLessThanOrEqual(32, $range['days']);

            // Verify start date converts to BS day 1
            $startBs = $this->service->adToBs($range['start_ad']);
            $this->assertEquals(2083, $startBs['year']);
            $this->assertEquals($mNum, $startBs['month']);
            $this->assertEquals(1, $startBs['day']);

            // Verify end date converts to BS last day
            $endBs = $this->service->adToBs($range['end_ad']);
            $this->assertEquals(2083, $endBs['year']);
            $this->assertEquals($mNum, $endBs['month']);
            $this->assertEquals($range['days'], $endBs['day']);
        }
    }

    /**
     * Test calendar display modes: BS, AD, and Both.
     */
    public function test_calendar_display_modes()
    {
        $adDate = '2026-04-28'; // 2083-01-15 BS

        // Mode 'bs'
        $bsDisplay = $this->service->formatDisplayDate($adDate, NepaliDateService::DISPLAY_BS);
        $this->assertEquals('2083-01-15 BS', $bsDisplay);

        // Mode 'ad'
        $adDisplay = $this->service->formatDisplayDate($adDate, NepaliDateService::DISPLAY_AD);
        $this->assertEquals('2026-04-28 AD', $adDisplay);

        // Mode 'both'
        $bothDisplay = $this->service->formatDisplayDate($adDate, NepaliDateService::DISPLAY_BOTH);
        $this->assertEquals('2083-01-15 BS (2026-04-28 AD)', $bothDisplay);
    }

    /**
     * Test display mode resolution hierarchy (session -> user -> setting).
     */
    public function test_display_mode_resolution_hierarchy()
    {
        \App\Models\Setting::updateOrCreate(['type' => 'calendar_display'], ['description' => 'both']);

        // Default when no user or session
        $this->assertEquals('both', $this->service->getDisplayMode());

        // Session override
        session(['calendar_display' => 'bs']);
        $this->assertEquals('bs', $this->service->getDisplayMode());
        session()->forget('calendar_display');

        // User preference
        $user = new User();
        $user->calendar_display = 'ad';
        $this->assertEquals('ad', $this->service->getDisplayMode($user));

        // User with 'default' falls back to system setting
        $user->calendar_display = 'default';
        $this->assertEquals('both', $this->service->getDisplayMode($user));
    }

    /**
     * Test timestamp handling across Asia/Kathmandu boundary.
     */
    public function test_timestamp_handling_in_nepal_timezone()
    {
        // 2026-04-28 12:00:00 UTC = 2026-04-28 17:45:00 in Kathmandu
        $carbon = Carbon::create(2026, 4, 28, 12, 0, 0, 'UTC');
        $bs = $this->service->adToBs($carbon->timestamp);

        $this->assertEquals(2083, $bs['year']);
        $this->assertEquals(1, $bs['month']);
        $this->assertEquals(15, $bs['day']);
    }
}
