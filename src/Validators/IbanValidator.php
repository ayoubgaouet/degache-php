<?php

declare(strict_types=1);

namespace AmineZhioua\DegachePhp\Validators;

use AmineZhioua\DegachePhp\DTO\BankInfo;

/**
 * Tunisian IBAN: "TN" + 2 check digits + the 20-digit domestic RIB,
 * 24 characters total (e.g. TN59 1000 6035 1835 9847 8831).
 *
 * Because a valid RIB is divisible by 97 (see RibValidator), the IBAN
 * check digits of a genuine Tunisian account always come out as 59 —
 * "TN59" is not hard-coded here, it falls out of the arithmetic.
 *
 * @author Ayoub Gaouet <https://github.com/ayoubgaouet>
 */
final class IbanValidator
{
    public const string COUNTRY_CODE = 'TN';

    /** Length of a Tunisian IBAN in its electronic (unspaced) form. */
    public const int LENGTH = 24;

    private const string IBAN_REGEX = '/^TN\d{22}$/';

    /**
     * Validates a Tunisian IBAN: structural shape, the IBAN's own
     * ISO 7064 MOD 97-10 checksum, and the embedded RIB.
     */
    public static function validate(?string $iban): bool
    {
        $normalized = self::normalize($iban);

        if ($normalized === null) {
            return false;
        }

        if (self::mod97(self::toNumeric($normalized)) !== 1) {
            return false;
        }

        return RibValidator::validate(self::ribFrom($normalized));
    }

    /**
     * Returns the 20-digit domestic RIB embedded in a valid IBAN, or
     * null if the IBAN is not valid.
     */
    public static function toRib(?string $iban): ?string
    {
        $normalized = self::normalize($iban);

        return $normalized !== null && self::validate($normalized)
            ? self::ribFrom($normalized)
            : null;
    }

    public static function getBankFromIban(?string $iban): ?BankInfo
    {
        $rib = self::toRib($iban);

        return $rib !== null ? RibValidator::getBankFromRib($rib) : null;
    }

    /**
     * Uppercases and strips whitespace, then checks the result is a
     * structurally plausible Tunisian IBAN.
     */
    private static function normalize(?string $iban): ?string
    {
        if ($iban === null) {
            return null;
        }

        $normalized = strtoupper(preg_replace('/\s+/', '', $iban) ?? $iban);

        return preg_match(self::IBAN_REGEX, $normalized) === 1 ? $normalized : null;
    }

    private static function ribFrom(string $normalizedIban): string
    {
        return substr($normalizedIban, 4);
    }

    /**
     * Moves the first 4 characters to the end and maps letters to
     * numbers (A=10 … Z=35), per ISO 7064 MOD 97-10.
     */
    private static function toNumeric(string $iban): string
    {
        $rearranged = substr($iban, 4).substr($iban, 0, 4);

        $numeric = '';

        foreach (str_split($rearranged) as $char) {
            $numeric .= ctype_alpha($char) ? (string) (ord($char) - 55) : $char;
        }

        return $numeric;
    }

    private static function mod97(string $digits): int
    {
        $remainder = 0;

        foreach (str_split($digits) as $digit) {
            $remainder = ($remainder * 10 + (int) $digit) % 97;
        }

        return $remainder;
    }
}
