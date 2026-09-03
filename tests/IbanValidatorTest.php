<?php

declare(strict_types=1);

namespace AmineZhioua\DegachePhp\Tests;

use AmineZhioua\DegachePhp\Constants\Banks;
use AmineZhioua\DegachePhp\Validators\IbanValidator;
use PHPUnit\Framework\TestCase;

#[CoversClass(IbanValidator::class)]
final class IbanValidatorTest extends TestCase
{
    public function testValidatesCorrectIban(): void
    {
        // Same checksum-valid RIBs as RibValidatorTest, wrapped in
        // their IBAN form. 10/STB is the Tunisia example published
        // on public IBAN reference sites.
        self::assertTrue(IbanValidator::validate('TN5901123456789012345618'));
        self::assertTrue(IbanValidator::validate('TN5908003000612073212763'));
        self::assertTrue(IbanValidator::validate('TN5910006035183598478831'));
    }

    public function testAcceptsWhitespaceAndLowercase(): void
    {
        self::assertTrue(IbanValidator::validate('tn59 1000 6035 1835 9847 8831'));
    }

    public function testRejectsNull(): void
    {
        self::assertFalse(IbanValidator::validate(null));
    }

    public function testRejectsInvalidFormat(): void
    {
        self::assertFalse(IbanValidator::validate('TN59100060351835984788')); // too short
        self::assertFalse(IbanValidator::validate('TN591000603518359847883100')); // too long
        self::assertFalse(IbanValidator::validate('FR5910006035183598478831')); // wrong country code
        self::assertFalse(IbanValidator::validate('TN59100060351835984788AA')); // non-digit BBAN
    }

    public function testRejectsWrongCheckDigits(): void
    {
        // Same BBAN as the valid STB sample, but "60" instead of the
        // correct "59" check digits.
        self::assertFalse(IbanValidator::validate('TN6010006035183598478831'));
    }

    public function testRejectsValidIbanChecksumWithInvalidRib(): void
    {
        // The IBAN's own MOD 97-10 checksum can pass while the
        // embedded RIB key is still wrong; both must hold.
        self::assertFalse(IbanValidator::validate('TN5901123456789012345678'));
    }

    public function testToRibReturnsTheEmbeddedRib(): void
    {
        self::assertSame(
            '10006035183598478831',
            IbanValidator::toRib('TN5910006035183598478831')
        );
    }

    public function testToRibReturnsNullForInvalidIban(): void
    {
        self::assertNull(IbanValidator::toRib('TN6010006035183598478831'));
        self::assertNull(IbanValidator::toRib(null));
    }

    public function testGetBankFromIbanReturnsCorrectBank(): void
    {
        $stb = Banks::BANKS['STB'];
        $bank = IbanValidator::getBankFromIban('TN5910006035183598478831');
        self::assertNotNull($bank);
        self::assertSame($stb['code'], $bank->code);
        self::assertSame($stb['name'], $bank->name);
    }

    public function testGetBankFromIbanReturnsNullForInvalidIban(): void
    {
        self::assertNull(IbanValidator::getBankFromIban('TN6010006035183598478831'));
        self::assertNull(IbanValidator::getBankFromIban(null));
    }
}
