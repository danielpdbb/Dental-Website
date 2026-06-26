<?php

namespace App\Support;

/**
 * Shared phone-number validation. Accepts digits with common separators (spaces,
 * dashes, parentheses) and an optional leading "+", with at least 7 digits — so
 * "0917-100-0001", "09171000001" and "+63 917 100 0001" all pass, but free text
 * and obviously-too-short values are rejected.
 */
class Phone
{
    /** Allowed shape: optional +, then a digit, then 6+ digits/separators. */
    public const REGEX = '/^\+?[0-9][0-9 ()\-]{6,}$/';

    /**
     * @return array<int, string>
     */
    public static function rules(bool $required = false, int $max = 30): array
    {
        return [$required ? 'required' : 'nullable', 'string', 'max:'.$max, 'regex:'.self::REGEX];
    }

    public static function message(): string
    {
        return 'Enter a valid phone number (digits, spaces, dashes or parentheses, optional leading +).';
    }
}
