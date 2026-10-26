<?php

namespace App\Helpers;

class NepaliHelper
{
    /**
     * Map of English to Devanagari digits.
     *
     * @var array
     */
    protected static $nepaliDigits = [
        '0' => '०',
        '1' => '१',
        '2' => '२',
        '3' => '३',
        '4' => '४',
        '5' => '५',
        '6' => '६',
        '7' => '७',
        '8' => '८',
        '9' => '९',
    ];

    /**
     * Convert English digits to Devanagari digits.
     *
     * @param string|int|float|null $input
     * @return string
     */
    public static function toDevanagari($input)
    {
        if ($input === null || $input === '') {
            return '';
        }
        return strtr((string) $input, self::$nepaliDigits);
    }

    /**
     * Convert Devanagari digits to English digits.
     *
     * @param string|null $input
     * @return string
     */
    public static function toEnglish($input)
    {
        if ($input === null || $input === '') {
            return '';
        }
        $flipped = array_flip(self::$nepaliDigits);
        return strtr((string) $input, $flipped);
    }

    /**
     * Format a number according to the active locale or explicit override.
     *
     * @param string|int|float $number
     * @param bool|null $useDevanagari
     * @return string
     */
    public static function formatNumber($number, $useDevanagari = null)
    {
        if ($useDevanagari === null) {
            $useDevanagari = (app()->getLocale() === 'ne');
        }

        $formatted = (string) $number;
        return $useDevanagari ? self::toDevanagari($formatted) : $formatted;
    }

    /**
     * Format currency in NPR / रु.
     *
     * @param float|int $amount
     * @param int $decimals
     * @param bool|null $useDevanagari
     * @return string
     */
    public static function formatNpr($amount, $decimals = 2, $useDevanagari = null)
    {
        if ($useDevanagari === null) {
            $useDevanagari = (app()->getLocale() === 'ne');
        }

        $formattedAmount = number_format((float) $amount, $decimals, '.', ',');

        if ($useDevanagari) {
            return 'रु ' . self::toDevanagari($formattedAmount);
        }

        return 'NPR ' . $formattedAmount;
    }

    /**
     * Format a stored AD date as Bikram Sambat (BS).
     *
     * @param string|\DateTimeInterface|null $adDate
     * @param string $format 'auto', 'ne', 'en', or 'Y-m-d'
     * @return string
     */
    public static function formatBs($adDate, $format = 'auto')
    {
        if (empty($adDate)) {
            return '';
        }

        return app(\App\Services\BikramSambatService::class)->formatBs($adDate, $format);
    }

    /**
     * Format dual date: BS Date (AD Date) or AD Date (BS Date) based on locale.
     *
     * @param string|\DateTimeInterface|null $adDate
     * @return string
     */
    public static function formatDualDate($adDate)
    {
        if (empty($adDate)) {
            return '';
        }

        return app(\App\Services\BikramSambatService::class)->formatDual($adDate);
    }

    /**
     * Convert user entered BS date (YYYY-MM-DD) to stored AD date (YYYY-MM-DD).
     *
     * @param string|null $bsDate
     * @return string|null
     */
    public static function bsToAd($bsDate)
    {
        if (empty($bsDate)) {
            return null;
        }

        try {
            $conv = app(\App\Services\BikramSambatService::class)->bsToAd($bsDate);
            return $conv['ad_date'];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert stored AD date (YYYY-MM-DD) to BS date string (YYYY-MM-DD).
     *
     * @param string|\DateTimeInterface|null $adDate
     * @return string|null
     */
    public static function adToBs($adDate)
    {
        if (empty($adDate)) {
            return null;
        }

        try {
            $conv = app(\App\Services\BikramSambatService::class)->adToBs($adDate);
            return $conv['bs_date'];
        } catch (\Exception $e) {
            return null;
        }
    }
}

