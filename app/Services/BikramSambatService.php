<?php

namespace App\Services;

use Nilambar\NepaliDate\NepaliCalendar;
use Nilambar\NepaliDate\NepaliDate;
use InvalidArgumentException;
use Carbon\Carbon;

class BikramSambatService
{
    /**
     * @var NepaliDate
     */
    protected $nepaliDate;

    /**
     * @var NepaliCalendar
     */
    protected $nepaliCalendar;

    /**
     * Nepali BS Month names in Nepali and English
     */
    public const MONTHS_NE = [
        1 => 'वैशाख',
        2 => 'जेठ',
        3 => 'असार',
        4 => 'साउन',
        5 => 'भदौ',
        6 => 'असोज',
        7 => 'कार्तिक',
        8 => 'मंसिर',
        9 => 'पुस',
        10 => 'माघ',
        11 => 'फागुन',
        12 => 'चैत',
    ];

    public const MONTHS_EN = [
        1 => 'Baishakh',
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

    public const DAYS_NE = [
        'आइतबार',
        'सोमबार',
        'मंगलबार',
        'बुधबार',
        'बिहीबार',
        'शुक्रबार',
        'शनिबार',
    ];

    public const DAYS_EN = [
        'Sunday',
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
        'Saturday',
    ];

    public const MIN_BS_YEAR = 2000;
    public const MAX_BS_YEAR = 2090;

    /**
     * BS month lengths matrix from 2000 BS to 2090 BS
     * Each entry contains the 12 month day counts for that BS year.
     * Verified official Bikram Sambat calendar dataset.
     */
    public const BS_CALENDAR_DATA = [
        2000 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2001 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2002 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2003 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2004 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2005 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2006 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2007 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2008 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2009 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2010 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2011 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2012 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2013 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2014 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2015 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2016 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2017 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2018 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2019 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2020 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2021 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2022 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2023 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2024 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2025 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2026 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2027 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2028 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2029 => [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2030 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2031 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2032 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2033 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2034 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2035 => [30, 32, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2036 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2037 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2038 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2039 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2040 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2041 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2042 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2043 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2044 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2045 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2046 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2047 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2048 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2049 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2050 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2051 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2052 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2053 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2054 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2055 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2056 => [31, 31, 32, 31, 32, 30, 30, 29, 30, 29, 30, 30],
        2057 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2058 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2059 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2060 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2061 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2062 => [30, 32, 31, 32, 31, 31, 29, 30, 29, 30, 29, 31],
        2063 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2064 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2065 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2066 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 29, 31],
        2067 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2068 => [31, 31, 32, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2069 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2070 => [31, 31, 31, 32, 31, 31, 29, 30, 30, 29, 30, 30],
        2071 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2072 => [31, 32, 31, 32, 31, 30, 30, 29, 30, 29, 30, 30],
        2073 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 31],
        2074 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2075 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2076 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2077 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2078 => [31, 31, 31, 32, 31, 31, 30, 29, 30, 29, 30, 30],
        2079 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2080 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 29, 30, 30],
        2081 => [31, 32, 31, 32, 31, 30, 30, 30, 29, 30, 29, 31],
        2082 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2083 => [31, 31, 32, 31, 31, 31, 30, 29, 30, 29, 30, 30],
        2084 => [31, 31, 32, 31, 31, 30, 30, 30, 29, 30, 30, 30],
        2085 => [31, 32, 31, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2086 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2087 => [31, 31, 32, 31, 31, 31, 30, 30, 29, 30, 30, 30],
        2088 => [30, 31, 32, 32, 30, 31, 30, 30, 29, 30, 30, 30],
        2089 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
        2090 => [30, 32, 31, 32, 31, 30, 30, 30, 29, 30, 30, 30],
    ];

    public function __construct()
    {
        $this->nepaliDate = new NepaliDate();
        $this->nepaliCalendar = new NepaliCalendar();
    }

    /**
     * Convert Gregorian AD date to Bikram Sambat BS date.
     *
     * @param string|\DateTimeInterface|null $adDate 'YYYY-MM-DD' or DateTime
     * @return array ['year' => 2083, 'month' => 7, 'day' => 8, 'bs_date' => '2083-07-08', 'month_name_ne' => 'कार्तिक', 'month_name_en' => 'Kartik', 'day_name_ne' => '...', 'day_name_en' => '...']
     * @throws InvalidArgumentException
     */
    public function adToBs($adDate): array
    {
        if (empty($adDate)) {
            throw new InvalidArgumentException('AD date cannot be empty.');
        }

        if ($adDate instanceof \DateTimeInterface) {
            $year = (int) $adDate->format('Y');
            $month = (int) $adDate->format('m');
            $day = (int) $adDate->format('d');
        } else {
            $cleanDate = substr(trim((string)$adDate), 0, 10);
            $parts = explode('-', str_replace(['/', '.'], '-', $cleanDate));
            if (count($parts) !== 3 || !checkdate((int)$parts[1], (int)$parts[2], (int)$parts[0])) {
                throw new InvalidArgumentException("Invalid AD date format: {$adDate}. Expected YYYY-MM-DD.");
            }
            $year = (int) $parts[0];
            $month = (int) $parts[1];
            $day = (int) $parts[2];
        }

        $converted = $this->nepaliCalendar->convertEnglishToNepali($year, $month, $day);

        if (empty($converted) || !isset($converted['year'])) {
            // High-precision fallback using verified BS calendar data matrix (1943-04-14 AD = 2000-01-01 BS)
            $anchor = Carbon::createFromDate(1943, 4, 14, 'UTC')->startOfDay();
            $target = Carbon::createFromDate($year, $month, $day, 'UTC')->startOfDay();
            $diffDays = $anchor->diffInDays($target, false);

            if ($diffDays < 0) {
                throw new InvalidArgumentException("AD date {$year}-{$month}-{$day} is out of supported conversion range (BS 2000-2090).");
            }

            $y = 2000;
            while ($y <= self::MAX_BS_YEAR && isset(self::BS_CALENDAR_DATA[$y])) {
                $daysInYear = array_sum(self::BS_CALENDAR_DATA[$y]);
                if ($diffDays < $daysInYear) {
                    break;
                }
                $diffDays -= $daysInYear;
                $y++;
            }

            if ($y > self::MAX_BS_YEAR || !isset(self::BS_CALENDAR_DATA[$y])) {
                throw new InvalidArgumentException("AD date {$year}-{$month}-{$day} is out of supported conversion range (BS 2000-2090).");
            }

            $m = 1;
            foreach (self::BS_CALENDAR_DATA[$y] as $idx => $dim) {
                if ($diffDays < $dim) {
                    $m = $idx + 1;
                    break;
                }
                $diffDays -= $dim;
            }
            $d = $diffDays + 1;

            $converted = [
                'year' => $y,
                'month' => $m,
                'day' => $d,
                'weekday' => $target->dayOfWeek + 1, // Carbon 0=Sun..6=Sat => 1=Sun..7=Sat
            ];
        }

        $bsYear = (int) $converted['year'];
        $bsMonth = (int) $converted['month'];
        $bsDay = (int) $converted['day'];

        $formattedMonth = str_pad((string)$bsMonth, 2, '0', STR_PAD_LEFT);
        $formattedDay = str_pad((string)$bsDay, 2, '0', STR_PAD_LEFT);
        $bsDateStr = "{$bsYear}-{$formattedMonth}-{$formattedDay}";

        $weekday = (int) ($converted['weekday'] ?? 1); // 1 = Sunday ... 7 = Saturday
        $dayIndex = ($weekday >= 1 && $weekday <= 7) ? $weekday - 1 : 0;

        return [
            'year' => $bsYear,
            'month' => $bsMonth,
            'day' => $bsDay,
            'bs_date' => $bsDateStr,
            'month_name_ne' => self::MONTHS_NE[$bsMonth] ?? '',
            'month_name_en' => self::MONTHS_EN[$bsMonth] ?? '',
            'day_name_ne' => self::DAYS_NE[$dayIndex] ?? '',
            'day_name_en' => self::DAYS_EN[$dayIndex] ?? '',
        ];
    }

    /**
     * Convert Bikram Sambat BS date to Gregorian AD date.
     *
     * @param string|int $bsYearOrString Either '2083-07-08' or year int (2083)
     * @param int|null $bsMonth (1-12)
     * @param int|null $bsDay (1-32)
     * @return array ['year' => 2026, 'month' => 10, 'day' => 24, 'ad_date' => '2026-10-24']
     * @throws InvalidArgumentException
     */
    public function bsToAd($bsYearOrString, ?int $bsMonth = null, ?int $bsDay = null): array
    {
        if ($bsMonth === null && $bsDay === null) {
            $cleanDate = substr(trim((string)$bsYearOrString), 0, 10);
            $parts = explode('-', str_replace(['/', '.'], '-', $cleanDate));
            if (count($parts) !== 3) {
                throw new InvalidArgumentException("Invalid BS date format: {$bsYearOrString}. Expected YYYY-MM-DD.");
            }
            $bsYear = (int) $parts[0];
            $bsMonth = (int) $parts[1];
            $bsDay = (int) $parts[2];
        } else {
            $bsYear = (int) $bsYearOrString;
            $bsMonth = (int) $bsMonth;
            $bsDay = (int) $bsDay;
        }

        $this->validateBsDate($bsYear, $bsMonth, $bsDay);

        $converted = $this->nepaliCalendar->convertNepaliToEnglish($bsYear, $bsMonth, $bsDay);

        if (empty($converted) || !isset($converted['year'])) {
            throw new InvalidArgumentException("Failed to convert BS date {$bsYear}-{$bsMonth}-{$bsDay}.");
        }

        $adYear = (int) $converted['year'];
        $adMonth = (int) $converted['month'];
        $adDay = (int) $converted['day'];

        $formattedMonth = str_pad((string)$adMonth, 2, '0', STR_PAD_LEFT);
        $formattedDay = str_pad((string)$adDay, 2, '0', STR_PAD_LEFT);
        $adDateStr = "{$adYear}-{$formattedMonth}-{$formattedDay}";

        return [
            'year' => $adYear,
            'month' => $adMonth,
            'day' => $adDay,
            'ad_date' => $adDateStr,
        ];
    }

    /**
     * Validate whether a BS date is within valid bounds and has a valid day count for that month.
     * Throws InvalidArgumentException on invalid date.
     *
     * @param int|string $bsYearOrString 'YYYY-MM-DD' or int year
     * @param int|null $month
     * @param int|null $day
     * @return bool
     * @throws InvalidArgumentException
     */
    public function validateBsDate($bsYearOrString, ?int $month = null, ?int $day = null): bool
    {
        if ($month === null && $day === null) {
            $cleanDate = substr(trim((string)$bsYearOrString), 0, 10);
            $parts = explode('-', str_replace(['/', '.'], '-', $cleanDate));
            if (count($parts) !== 3) {
                throw new InvalidArgumentException("Invalid BS date format: {$bsYearOrString}. Expected YYYY-MM-DD.");
            }
            $year = (int) $parts[0];
            $month = (int) $parts[1];
            $day = (int) $parts[2];
        } else {
            $year = (int) $bsYearOrString;
            $month = (int) $month;
            $day = (int) $day;
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
            $monthName = (self::MONTHS_NE[$month] ?? '') . ' (' . (self::MONTHS_EN[$month] ?? '') . ')';
            throw new InvalidArgumentException("Invalid day {$day} for {$monthName} {$year} BS. This month has {$daysInMonth} days.");
        }

        return true;
    }

    /**
     * Check if a BS date string or parts is valid without throwing exception.
     *
     * @param int|string $bsYearOrString
     * @param int|null $month
     * @param int|null $day
     * @return bool
     */
    public function isValidBsDate($bsYearOrString, ?int $month = null, ?int $day = null): bool
    {
        try {
            if (empty($bsYearOrString)) {
                return false;
            }
            return $this->validateBsDate($bsYearOrString, $month, $day);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get days in a specific BS year and month.
     *
     * @param int $year
     * @param int $month
     * @return int
     */
    public function getDaysInBsMonth(int $year, int $month): int
    {
        if ($year < self::MIN_BS_YEAR || $year > self::MAX_BS_YEAR || $month < 1 || $month > 12) {
            throw new InvalidArgumentException("Invalid BS Year or Month: {$year}-{$month}");
        }

        return (int) (self::BS_CALENDAR_DATA[$year][$month - 1] ?? 30);
    }

    /**
     * Alias for getDaysInBsMonth.
     */
    public function getDaysInMonth(int $year, int $month): int
    {
        return $this->getDaysInBsMonth($year, $month);
    }

    /**
     * Get full calendar month structure for a BS year & month (for date picker rendering)
     *
     * @param int $year
     * @param int $month
     * @return array
     */
    public function getMonthCalendar(int $year, int $month): array
    {
        $daysInMonth = $this->getDaysInBsMonth($year, $month);
        $firstDayAd = $this->bsToAd($year, $month, 1);
        $carbon = Carbon::parse($firstDayAd['ad_date']);
        $startDayOfWeek = $carbon->dayOfWeek; // 0 = Sunday, 1 = Monday ... 6 = Saturday

        return [
            'year' => $year,
            'month' => $month,
            'month_name_ne' => self::MONTHS_NE[$month],
            'month_name_en' => self::MONTHS_EN[$month],
            'days_in_month' => $daysInMonth,
            'start_day_of_week' => $startDayOfWeek,
        ];
    }

    /**
     * Format a stored AD date as BS.
     *
     * @param string|\DateTimeInterface|null $adDate
     * @param string $format 'ne' (Devanagari text), 'en' (English text), or 'Y-m-d'
     * @param string|null $locale
     * @return string
     */
    public function formatBs($adDate, string $format = 'auto', ?string $locale = null): string
    {
        if (empty($adDate)) {
            return '';
        }

        try {
            $bs = $this->adToBs($adDate);
        } catch (\Exception $e) {
            return (string)$adDate;
        }

        $locale = $locale ?? app()->getLocale();

        if ($format === 'Y-m-d') {
            return $bs['bs_date'];
        }

        $useDevanagari = ($format === 'ne') || ($format === 'auto' && $locale === 'ne');

        if ($useDevanagari) {
            $dayStr = \App\Helpers\NepaliHelper::toDevanagari($bs['day']);
            $yearStr = \App\Helpers\NepaliHelper::toDevanagari($bs['year']);
            return "{$dayStr} {$bs['month_name_ne']} {$yearStr}";
        }

        return "{$bs['day']} {$bs['month_name_en']} {$bs['year']}";
    }

    /**
     * Format dual date: BS Date (AD Date)
     * e.g. "२०८३ कार्तिक ०८ (2026-10-24 AD)" or "24 Oct 2026 (2083-07-08 BS)"
     *
     * @param string|\DateTimeInterface|null $adDate
     * @param string|null $locale
     * @return string
     */
    public function formatDual($adDate, ?string $locale = null): string
    {
        if (empty($adDate)) {
            return '';
        }

        $locale = $locale ?? app()->getLocale();

        try {
            $bs = $this->adToBs($adDate);
            $cleanAd = ($adDate instanceof \DateTimeInterface) ? $adDate->format('Y-m-d') : substr((string)$adDate, 0, 10);

            if ($locale === 'ne') {
                $bsFormatted = $this->formatBs($adDate, 'ne');
                return "{$bsFormatted} ({$cleanAd} AD)";
            } else {
                $carbon = Carbon::parse($cleanAd);
                $adFormatted = $carbon->format('d M Y');
                return "{$adFormatted} ({$bs['bs_date']} BS)";
            }
        } catch (\Exception $e) {
            return (string)$adDate;
        }
    }

    /**
     * Convert user BS date range (from_bs, to_bs) into AD date range for SQL queries.
     *
     * @param string|null $fromBs
     * @param string|null $toBs
     * @return array [string|null $fromAd, string|null $toAd]
     */
    public function bsRangeToAdRange(?string $fromBs, ?string $toBs): array
    {
        $fromAd = null;
        $toAd = null;

        if (!empty($fromBs)) {
            $conv = $this->bsToAd($fromBs);
            $fromAd = $conv['ad_date'];
        }

        if (!empty($toBs)) {
            $conv = $this->bsToAd($toBs);
            $toAd = $conv['ad_date'];
        }

        return [$fromAd, $toAd];
    }

    /**
     * Get current BS date details.
     *
     * @return array
     */
    public function today(): array
    {
        return $this->adToBs(Carbon::today());
    }
}

