<?php

declare(strict_types=1);

namespace AmineZhioua\DegachePhp\Tests;

use AmineZhioua\DegachePhp\Constants\Banks;
use AmineZhioua\DegachePhp\Validators\RibValidator;
use PHPUnit\Framework\TestCase;

#[CoversClass(RibValidator::class)]
final class RibValidatorTest extends TestCase
{
    public function testValidatesCorrectRib(): void
    {
        // Real checksum-valid samples: 01/ATB and 08/BIAT are
        // constructed with a valid MOD 97-10 key; 10/STB is the
        // Tunisia example published on public IBAN reference sites.
        self::assertTrue(RibValidator::validate('01123456789012345618'));
        self::assertTrue(RibValidator::validate('08003000612073212763'));
        self::assertTrue(RibValidator::validate('10006035183598478831'));
    }

    public function testRejectsInvalidFormat(): void
    {
        self::assertFalse(RibValidator::validate('0123456789012345')); // too short
        self::assertFalse(RibValidator::validate('012345678901234567890')); // too long
        self::assertFalse(RibValidator::validate('0123456789a123456789')); // non-digit
    }

    public function testRejectsNonExistentBankCode(): void
    {
        self::assertFalse(RibValidator::validate('99123456789012345678'));
    }

    public function testRejectsInvalidBranchCodeFormat(): void
    {
        self::assertFalse(RibValidator::validate('0112345678901234567'));
    }

    public function testRejectsInvalidAccountNumberFormat(): void
    {
        self::assertFalse(RibValidator::validate('01123abc456789012345'));
    }

    public function testRejectsInvalidKeyFormat(): void
    {
        self::assertFalse(RibValidator::validate('0112345678901234567a'));
    }

    public function testRejectsCorrectFormatWithWrongChecksum(): void
    {
        // Well-formed, known bank code, right length — but the key
        // is not the MOD 97-10 checksum of the first 18 digits.
        self::assertFalse(RibValidator::validate('01123456789012345678'));
    }

    public function testComputeKeyReturnsTheChecksumDigits(): void
    {
        self::assertSame('18', RibValidator::computeKey('011234567890123456'));
        self::assertSame('63', RibValidator::computeKey('080030006120732127'));
        self::assertSame('31', RibValidator::computeKey('100060351835984788'));
    }

    public function testRejectsOutOfRangeKeysThatDivisibilityAloneWouldAccept(): void
    {
        // Prefix 100000000000000026 has computed key 02. A plain
        // "divisible by 97" check would also accept 99, which no
        // issuer prints.
        self::assertSame('02', RibValidator::computeKey('100000000000000026'));
        self::assertTrue(RibValidator::validate('10000000000000002602'));
        self::assertFalse(RibValidator::validate('10000000000000002699'));
    }

    public function testAcceptsBothSpellingsOfTheZeroKey(): void
    {
        // Computed key 00; issuers following the 01-97 convention
        // write the same key as 97, so both are accepted.
        self::assertSame('00', RibValidator::computeKey('100000000000000059'));
        self::assertTrue(RibValidator::validate('10000000000000005900'));
        self::assertTrue(RibValidator::validate('10000000000000005997'));
    }

    public function testComputeKeyReturnsNullForInvalidLength(): void
    {
        self::assertNull(RibValidator::computeKey('12345'));
        self::assertNull(RibValidator::computeKey('0112345678901234567'));
    }

    public function testGetBankFromRibReturnsCorrectBank(): void
    {
        $atb = Banks::BANKS['ATB'];
        $bank = RibValidator::getBankFromRib('01123456789012345618');
        self::assertNotNull($bank);
        self::assertSame($atb['code'], $bank->code);
        self::assertSame($atb['name'], $bank->name);

        $biat = Banks::BANKS['BIAT'];
        $bank = RibValidator::getBankFromRib('08003000612073212763');
        self::assertNotNull($bank);
        self::assertSame($biat['code'], $bank->code);
        self::assertSame($biat['name'], $bank->name);
    }

    public function testGetBankFromRibReturnsNullForInvalidFormat(): void
    {
        self::assertNull(RibValidator::getBankFromRib('0123456789'));
        self::assertNull(RibValidator::getBankFromRib('0123456789abcdefghij'));
    }

    public function testGetBankFromRibReturnsNullForNonExistentBankCode(): void
    {
        self::assertNull(RibValidator::getBankFromRib('99123456789012345678'));
    }
}
