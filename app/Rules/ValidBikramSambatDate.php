<?php

namespace App\Rules;

use App\Services\BikramSambatService;
use Illuminate\Contracts\Validation\Rule;

class ValidBikramSambatDate implements Rule
{
    protected BikramSambatService $bsService;
    protected string $messageKey = 'The :attribute is not a valid Bikram Sambat (BS) date.';

    public function __construct()
    {
        $this->bsService = app(BikramSambatService::class);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     * @return bool
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true;
        }

        if (!is_string($value) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            $this->messageKey = 'The :attribute must be in YYYY-MM-DD Bikram Sambat format.';
            return false;
        }

        try {
            return $this->bsService->isValidBsDate($value);
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message(): string
    {
        return __($this->messageKey);
    }
}
