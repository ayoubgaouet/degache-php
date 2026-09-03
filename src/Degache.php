<?php

declare(strict_types=1);

namespace AmineZhioua\DegachePhp;

use AmineZhioua\DegachePhp\DTO\CarrierInfo;
use AmineZhioua\DegachePhp\DTO\BankInfo;
use AmineZhioua\DegachePhp\Formatters\PhoneFormatter;
use AmineZhioua\DegachePhp\Validators\PhoneValidator;
use AmineZhioua\DegachePhp\Validators\CinValidator;
use AmineZhioua\DegachePhp\Validators\CarPlateValidator;
use AmineZhioua\DegachePhp\Validators\RibValidator;
use AmineZhioua\DegachePhp\Validators\IbanValidator;
use AmineZhioua\DegachePhp\Validators\PostalCodeValidator;
use AmineZhioua\DegachePhp\Validators\TaxIdValidator;
use AmineZhioua\DegachePhp\Formatters\CurrencyFormatter;
use AmineZhioua\DegachePhp\Formatters\DateFormatter;
use AmineZhioua\DegachePhp\Enums\CarPlateType;
use AmineZhioua\DegachePhp\DTO\CarPlateInfo;
use DateTimeInterface;
use IntlDateFormatter;

/**
 * Facade providing convenient access to DegachePhp's Tunisian utilities.
 *
 * For advanced usage (e.g. dependency injection, testing with mocks),
 * prefer using the individual Validator/Formatter classes directly.
 */
final class Degache
{
    /*
        Phone Number all Methods
    */
    public static function validatePhoneNumber(?string $phoneNumber, bool $strict = false): bool
    {
        return PhoneValidator::validate($phoneNumber, $strict);
    }

    public static function getPhoneCarrierInfo(?string $phoneNumber, bool $strict = false): ?CarrierInfo
    {
        return PhoneValidator::getCarrierInfo($phoneNumber, $strict);
    }

    public static function formatPhoneNumber(?string $phoneNumber): ?string
    {
        return PhoneFormatter::format($phoneNumber);
    }

    /*
        CIN all methods
    */
    public static function validateCIN(?string $cin): bool
    {
        return CinValidator::validate($cin);
    }

    /*
        Banks all methods
    */
    public static function validateRIB(?string $rib): bool {
        return RibValidator::validate($rib);
    }

    public static function getBankInfoFromRIB(?string $rib): ?BankInfo {
        return RibValidator::getBankFromRib($rib);
    }

    public static function validateIBAN(?string $iban): bool {
        return IbanValidator::validate($iban);
    }

    public static function getBankInfoFromIBAN(?string $iban): ?BankInfo {
        return IbanValidator::getBankFromIban($iban);
    }

    public static function getRIBFromIBAN(?string $iban): ?string {
        return IbanValidator::toRib($iban);
    }

    /*
        Currency all methods
    */
    public static function formatCurrency(float $amount, bool $code = false, bool $symbol = false): string {
        return CurrencyFormatter::format($amount, $code, $symbol);
    }

    /*
        Date all methods
    */
    public static function formatDate(
        DateTimeInterface $date,
        int $dateType = IntlDateFormatter::LONG,
        int $timeType = IntlDateFormatter::NONE
    ): string {
        return DateFormatter::format($date, $dateType, $timeType);
    }

    /*
        Car Plate all methods
    */
    public static function validateCarPlate(
        ?string $carPlate,
        CarPlateType $type = CarPlateType::Any,
        bool $strict = false,
    ): bool {
        return CarPlateValidator::validate($carPlate, $type, $strict);
    }

    public static function getCarPlateInfo(
        ?string $carPlate,
        CarPlateType $type = CarPlateType::Any,
        bool $strict = false,
    ): ?CarPlateInfo {
        return CarPlateValidator::getInfo($carPlate, $type, $strict);
    }

    /*
        Postal Code all methods
    */
    public static function validatePostalCode(?string $postalCode): bool
    {
        return PostalCodeValidator::validate($postalCode);
    }

    /*
        Tax ID all methods
    */
    public static function validateTaxID(?string $taxId): bool
    {
        return TaxIdValidator::validate($taxId);
    }
}