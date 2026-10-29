<?php

namespace App\Services;

use App\Helpers\NepaliHelper;
use App\Models\Setting;
use App\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

/**
 * Centralized Nepali Bikram Sambat (BS) <-> Gregorian (AD) Date Engine.
 *
 * Provides bidirectional offline conversion, verification against verified
 * calendar day matrix (2000-2090 BS), Nepali 12-month billing period boundaries,
 * Devanagari/English digit normalization, and configurable calendar display (BS, AD, Both).
 */
class NepaliDateService extends BikramSambatService
{
    /**
     * Standard 12 Bikram Sambat months with Nepali script and official English transliteration.
     */
    public const NEPALI_MONTHS = [
        1 => ['id' => 1, 'ne' => 'बैशाख', 'en' => 'Baisakh'],
        2 => ['id' => 2, 'ne' => 'जेठ', 'en' => 'Jestha'],
        3 => ['id' => 3, 'ne' => 'असार', 'en' => 'Ashadh'],
        4 => ['id' => 4, 'ne' => 'साउन', 'en' => 'Shrawan'],
        5 => ['id' => 5, 'ne' => 'भदौ', 'en' => 'Bhadra'],
        6 => ['id' => 6, 'ne' => 'असोज', 'en' => 'Ashwin'],
        7 => ['id' => 7, 'ne' => 'कात्तिक', 'en' => 'Kartik'],
        8 => ['id' => 8, 'ne' => 'मंसिर', 'en' => 'Mangsir'],
        9 => ['id' => 9, 'ne' => 'पुस', 'en' => 'Poush'],
        10 => ['id' => 10, 'ne' => 'माघ', 'en' => 'Magh'],
        11 => ['id' => 11, 'ne' => 'फागुन', 'en' => 'Falgun'],
        12 => ['id' => 12, 'ne' => 'चैत', 'en' => 'Chaitra'],
    ];

    public const DISPLAY_BS = 'bs';
    public const DISPLAY_AD = 'ad';
    public const DISPLAY_BOTH = 'both';
    public const DISPLAY_DEFAULT = 'default';

    public const VALID_DISPLAY_MODES = [
        self::DISPLAY_BS,
        self::DISPLAY_AD,
        self::DISPLAY_BOTH,
    ];

    /**
     * Normalize date string by converting Devanagari digits to English digits
     * and trimming extra characters.
     *
     * @param string|int|null $date
     * @return string
     */
    public function normalizeDateString($date): string
    {
        if ($date === null) {
            return '';
        }

        $str = trim((string)$date);
        // Translate Devanagari numerals to ASCII/English digits
        $str = NepaliHelper::toEnglish($str);

        // Standardize separators to hyphens
        $str = str_replace(['/', '.'], '-', $str);

        return $str;
    }

    /**
     * Parse a BS date input (string or parts) into normalized integer array [year, month, day].
     *
     * @param string|int $bsYearOrString
     * @param int|null $bsMonth
     * @param int|null $bsDay
     * @return array ['year' => int, 'month' => int, 'day' => int]
     * @throws InvalidArgumentException
     */
    public function parseBsDate($bsYearOrString, ?int $bsMonth = null, ?int $bsDay = null): array
    {
        if ($bsMonth === null && $bsDay === null) {
            $normalized = $this->normalizeDateString($bsYearOrString);
            $parts = explode('-', substr($normalized, 0, 10));

            if (count($parts) !== 3) {
                throw new InvalidArgumentException("Invalid BS date format: '{$bsYearOrString}'. Expected YYYY-MM-DD.");
            }

            $year = (int)$parts[0];
            $month = (int)$parts[1];
            $day = (int)$parts[2];
        } else {
            $year = (int)$this->normalizeDateString($bsYearOrString);
            $month = (int)$bsMonth;
            $day = (int)$bsDay;
        }

        $this->validateBsDate($year, $month, $day);

        return [
            'year' => $year,
            'month' => $month,
            'day' => $day,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function validateBsDate($bsYearOrString, ?int $month = null, ?int $day = null): bool
    {
        if ($month === null && $day === null) {
            $normalized = $this->normalizeDateString($bsYearOrString);
            $parts = explode('-', substr($normalized, 0, 10));
            if (count($parts) !== 3) {
                throw new InvalidArgumentException("Invalid BS date format: '{$bsYearOrString}'. Expected YYYY-MM-DD.");
            }
            $year = (int)$parts[0];
            $month = (int)$parts[1];
            $day = (int)$parts[2];
        } else {
            $year = (int)$this->normalizeDateString($bsYearOrString);
            $month = (int)$month;
            $day = (int)$day;
        }

        if ($year < self::MIN_BS_YEAR || $year > self::MAX_BS_YEAR) {
            throw new InvalidArgumentException("BS Year {$year} is out of supported range (" . self::MIN_BS_YEAR . "-" . self::MAX_BS_YEAR . " BS).");
        }

        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException("BS Month {$month} is invalid. Must be between 1 and 12.");
        }

        if (!isset(self::BS_CALENDAR_DATA[$year])) {
            throw new InvalidArgumentException("Calendar data for BS Year {$year} is unavailable.");
        }

        $daysInMonth = self::BS_CALENDAR_DATA[$year][$month - 1] ?? null;
        if ($daysInMonth === null) {
            throw new InvalidArgumentException("Unable to determine days for BS {$year}-{$month}.");
        }

        if ($day < 1 || $day > $daysInMonth) {
            $mName = self::NEPALI_MONTHS[$month]['ne'] . ' (' . self::NEPALI_MONTHS[$month]['en'] . ')';
            throw new InvalidArgumentException("Invalid day {$day} for {$mName} {$year} BS. This month has {$daysInMonth} days.");
        }

        return true;
    }

    /**
     * Convert Bikram Sambat BS date to Gregorian AD date.
     * Supports English and Devanagari digit inputs.
     *
     * @param string|int $bsYearOrString
     * @param int|null $bsMonth
     * @param int|null $bsDay
     * @return array ['year' => int, 'month' => int, 'day' => int, 'ad_date' => 'YYYY-MM-DD']
     * @throws InvalidArgumentException
     */
    public function bsToAd($bsYearOrString, ?int $bsMonth = null, ?int $bsDay = null): array
    {
        $parsed = $this->parseBsDate($bsYearOrString, $bsMonth, $bsDay);

        return parent::bsToAd($parsed['year'], $parsed['month'], $parsed['day']);
    }

    /**
     * Convert Gregorian AD date to Bikram Sambat BS date.
     * Supports \DateTimeInterface, Carbon, integer timestamps, or YYYY-MM-DD strings.
     *
     * @param string|\DateTimeInterface|int|null $adDate
     * @return array ['year' => int, 'month' => int, 'day' => int, 'bs_date' => string, 'bs_date_ne' => string, ...]
     * @throws InvalidArgumentException
     */
    public function adToBs($adDate): array
    {
        if (empty($adDate)) {
            throw new InvalidArgumentException('AD date cannot be empty.');
        }

        if (is_numeric($adDate) && (int)$adDate > 10000) {
            // Integer timestamp: interpret in Asia/Kathmandu
            $carbon = Carbon::createFromTimestamp((int)$adDate, 'Asia/Kathmandu');
            $cleanDate = $carbon->format('Y-m-d');
        } elseif ($adDate instanceof \DateTimeInterface) {
            $cleanDate = $adDate->format('Y-m-d');
        } else {
            $normalized = $this->normalizeDateString($adDate);
            $cleanDate = substr($normalized, 0, 10);
        }

        $res = parent::adToBs($cleanDate);

        // Standardized Nepali and English month names
        $bsMonth = (int)$res['month'];
        $res['standard_month_ne'] = self::NEPALI_MONTHS[$bsMonth]['ne'] ?? '';
        $res['standard_month_en'] = self::NEPALI_MONTHS[$bsMonth]['en'] ?? '';

        $dNe = NepaliHelper::toDevanagari(str_pad((string)$res['day'], 2, '0', STR_PAD_LEFT));
        $mNe = NepaliHelper::toDevanagari(str_pad((string)$res['month'], 2, '0', STR_PAD_LEFT));
        $yNe = NepaliHelper::toDevanagari((string)$res['year']);
        $res['bs_date_ne'] = "{$yNe}-{$mNe}-{$dNe}";
        $res['days_in_month'] = $this->getDaysInBsMonth($res['year'], $res['month']);

        return $res;
    }

    /**
     * Convert BS date input to canonical AD YYYY-MM-DD string.
     * Returns null if input is empty or invalid.
     *
     * @param string|null $bsDate
     * @return string|null
     */
    public function toAdDateString(?string $bsDate): ?string
    {
        if (empty($bsDate)) {
            return null;
        }

        try {
            $conv = $this->bsToAd($bsDate);
            return $conv['ad_date'];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Convert AD date input to BS YYYY-MM-DD string.
     * Returns null if input is empty or conversion fails.
     *
     * @param string|\DateTimeInterface|null $adDate
     * @return string|null
     */
    public function toBsDateString($adDate): ?string
    {
        if (empty($adDate)) {
            return null;
        }

        try {
            $conv = $this->adToBs($adDate);
            return $conv['bs_date'];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Get all 12 Bikram Sambat months (1 to 12) with Nepali and English names.
     *
     * @return array
     */
    public function getMonths(): array
    {
        return self::NEPALI_MONTHS;
    }

    /**
     * Get month name for a specific BS month number.
     *
     * @param int $month (1-12)
     * @param string $lang 'en' or 'ne'
     * @return string
     */
    public function getBsMonthName(int $month, string $lang = 'en'): string
    {
        if (!isset(self::NEPALI_MONTHS[$month])) {
            return '';
        }

        return ($lang === 'ne') ? self::NEPALI_MONTHS[$month]['ne'] : self::NEPALI_MONTHS[$month]['en'];
    }

    /**
     * Get the exact Gregorian AD start and end date boundaries for a BS billing month.
     *
     * @param int $bsYear (2000-2090)
     * @param int $bsMonth (1-12)
     * @return array
     * @throws InvalidArgumentException
     */
    public function getBsMonthAdRange(int $bsYear, int $bsMonth): array
    {
        $daysInMonth = $this->getDaysInBsMonth($bsYear, $bsMonth);

        $startConv = $this->bsToAd($bsYear, $bsMonth, 1);
        $endConv = $this->bsToAd($bsYear, $bsMonth, $daysInMonth);

        $monthInfo = self::NEPALI_MONTHS[$bsMonth] ?? ['ne' => '', 'en' => ''];

        return [
            'bs_year' => $bsYear,
            'bs_month' => $bsMonth,
            'month_name_ne' => $monthInfo['ne'],
            'month_name_en' => $monthInfo['en'],
            'days' => $daysInMonth,
            'start_ad' => $startConv['ad_date'],
            'end_ad' => $endConv['ad_date'],
            'start_ad_carbon' => Carbon::parse($startConv['ad_date'], 'Asia/Kathmandu')->startOfDay(),
            'end_ad_carbon' => Carbon::parse($endConv['ad_date'], 'Asia/Kathmandu')->endOfDay(),
        ];
    }

    /**
     * Resolve the active calendar display preference.
     *
     * Priority:
     * 1. Active session override: session('calendar_display')
     * 2. Authenticated user preference: User->calendar_display (if not 'default')
     * 3. System default setting: Setting::where('type', 'calendar_display')->value('description')
     * 4. Fallback: 'both'
     *
     * @param User|null $user
     * @return string 'bs', 'ad', or 'both'
     */
    public function getDisplayMode(?User $user = null): string
    {
        // 1. Session override
        if (session()->has('calendar_display')) {
            $sessMode = session('calendar_display');
            if (in_array($sessMode, self::VALID_DISPLAY_MODES, true)) {
                return $sessMode;
            }
        }

        // 2. User preference
        $u = $user ?? Auth::user();
        if ($u && !empty($u->calendar_display) && $u->calendar_display !== self::DISPLAY_DEFAULT) {
            if (in_array($u->calendar_display, self::VALID_DISPLAY_MODES, true)) {
                return $u->calendar_display;
            }
        }

        // 3. System setting
        try {
            $sysMode = Setting::where('type', 'calendar_display')->value('description');
            if (!empty($sysMode) && in_array($sysMode, self::VALID_DISPLAY_MODES, true)) {
                return $sysMode;
            }
        } catch (\Throwable $e) {
            // In testing or pre-migration states
        }

        // 4. Fallback default
        return self::DISPLAY_BOTH;
    }

    /**
     * Format a stored Gregorian AD date according to the active or specified display mode.
     *
     * Modes:
     * - 'bs'   => "2083-01-15 BS"
     * - 'ad'   => "2026-04-28 AD"
     * - 'both' => "2083-01-15 BS (2026-04-28 AD)"
     *
     * @param string|\DateTimeInterface|null $adDate
     * @param string|null $mode 'bs', 'ad', 'both', or null (uses getDisplayMode)
     * @param string|null $locale 'en', 'ne', or null
     * @return string
     */
    public function formatDisplayDate($adDate, ?string $mode = null, ?string $locale = null): string
    {
        if (empty($adDate)) {
            return '';
        }

        $cleanAd = ($adDate instanceof \DateTimeInterface)
            ? $adDate->format('Y-m-d')
            : substr($this->normalizeDateString($adDate), 0, 10);

        $mode = $mode ?? $this->getDisplayMode();
        if (!in_array($mode, self::VALID_DISPLAY_MODES, true)) {
            $mode = self::DISPLAY_BOTH;
        }

        $locale = $locale ?? (function_exists('app') && app()->bound('translator') ? app()->getLocale() : 'en');

        try {
            $bs = $this->adToBs($cleanAd);
        } catch (\Throwable $e) {
            // If out of conversion range, safely return the raw AD date
            return "{$cleanAd} AD";
        }

        $bsString = ($locale === 'ne') ? $bs['bs_date_ne'] : $bs['bs_date'];

        switch ($mode) {
            case self::DISPLAY_BS:
                return "{$bsString} BS";

            case self::DISPLAY_AD:
                return "{$cleanAd} AD";

            case self::DISPLAY_BOTH:
            default:
                return "{$bsString} BS ({$cleanAd} AD)";
        }
    }

    /**
     * Format a stored Gregorian AD date into textual Nepali BS representation.
     * e.g., "15 Baisakh 2083" or "१५ बैशाख २०८३".
     *
     * @param string|\DateTimeInterface|null $adDate
     * @param string|null $locale
     * @return string
     */
    public function formatTextualBs($adDate, ?string $locale = null): string
    {
        if (empty($adDate)) {
            return '';
        }

        $locale = $locale ?? (function_exists('app') && app()->bound('translator') ? app()->getLocale() : 'en');

        try {
            $bs = $this->adToBs($adDate);
            if ($locale === 'ne') {
                $dayStr = NepaliHelper::toDevanagari($bs['day']);
                $yearStr = NepaliHelper::toDevanagari($bs['year']);
                return "{$dayStr} {$bs['month_name_ne']} {$yearStr}";
            }

            return "{$bs['day']} {$bs['month_name_en']} {$bs['year']}";
        } catch (\Throwable $e) {
            return (string)$adDate;
        }
    }
}
