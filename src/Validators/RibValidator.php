<?php

declare(strict_types=1);

namespace AmineZhioua\DegachePhp\Validators;

use AmineZhioua\DegachePhp\Constants\Banks;
use AmineZhioua\DegachePhp\DTO\BankInfo;

final class RibValidator
{
    private const string RIB_REGEX = '/^\d{20}$/';
    private const string BRANCH_CODE_REGEX = '/^\d{3}$/';
    private const string ACCOUNT_NUMBER_REGEX = '/^\d{13}$/';
    private const string KEY_REGEX = '/^\d{2}$/';

    /**
     * Validates a Tunisian RIB (Relevé d'Identité Bancaire).
     *
     * The trailing 2-digit key is a real ISO 7064 MOD 97-10 checksum
     * over the first 18 digits, not just two arbitrary digits: a
     * genuine RIB is always divisible by 97.
    */
    public static function validate(?string $rib): bool
    {
        if ($rib === null || preg_match(self::RIB_REGEX, $rib) !== 1) {
            return false;
        }

        $bankCode = substr($rib, 0, 2);
        $branchCode = substr($rib, 2, 3);
        $accountNumber = substr($rib, 5, 13);
        $key = substr($rib, 18, 2);

        if (Banks::findByCode($bankCode) === null) {
            return false;
        }

        if (preg_match(self::BRANCH_CODE_REGEX, $branchCode) !== 1) {
            return false;
        }

        if (preg_match(self::ACCOUNT_NUMBER_REGEX, $accountNumber) !== 1) {
            return false;
        }

        if (preg_match(self::KEY_REGEX, $key) !== 1) {
            return false;
        }

        return self::keyMatches($rib, $key);
    }

    /**
     * Computes the 2-digit key for an 18-digit bank+branch+account
     * prefix, i.e. whatever makes the full 20-digit RIB divisible by
     * 97. Returns it in the 00-96 range.
     */
    public static function computeKey(string $first18): ?string
    {
        if (preg_match('/^\d{18}$/', $first18) !== 1) {
            return null;
        }

        $key = (97 - self::mod97($first18.'00')) % 97;

        return str_pad((string) $key, 2, '0', STR_PAD_LEFT);
    }

    /**
     * Compares the RIB's trailing key against the computed one.
     *
     * Note this is deliberately stricter than "the 20 digits are
     * divisible by 97". Divisibility alone also accepts the key
     * plus 97 whenever that still fits in two digits, so keys such
     * as 98 and 99 — which no issuer ever prints — would pass. The
     * one genuine ambiguity is the 00 case: some issuers write that
     * key as 97, following the 01-97 convention, so both spellings
     * are accepted there.
     */
    private static function keyMatches(string $rib, string $key): bool
    {
        $expected = self::computeKey(substr($rib, 0, 18));

        if ($expected === null) {
            return false;
        }

        return $key === $expected || ($expected === '00' && $key === '97');
    }

    public static function getBankFromRib(?string $rib): ?BankInfo
    {
        if ($rib === null || preg_match(self::RIB_REGEX, $rib) !== 1) {
            return null;
        }

        $bankCode = substr($rib, 0, 2);
        $bank = Banks::findByCode($bankCode);

        return $bank !== null ? new BankInfo($bank['code'], $bank['name']) : null;
    }

    /**
     * ISO 7064 MOD 97-10 remainder of a numeric digit string,
     * computed digit-by-digit to avoid overflow.
     */
    private static function mod97(string $digits): int
    {
        $remainder = 0;

        foreach (str_split($digits) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}
