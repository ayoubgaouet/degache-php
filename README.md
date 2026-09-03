# degache-php

A framework-agnostic PHP library of Tunisian utilities — validators for phone numbers, CIN, RIB, IBAN, car plates, postal codes, and tax IDs, plus locale-aware currency and date formatting.

Ported from [degachejs](https://github.com/) (the original JavaScript/TypeScript library), with the API redesigned to feel native to PHP rather than a direct translation.

Works with Laravel, Symfony, CodeIgniter, or plain PHP — the core package has no framework dependencies.

## Requirements

- PHP 8.2+
- `ext-intl` (used for currency and date formatting)

## Installation

```bash
composer require amine-zhioua/degache-php
```

## Quick start

Every feature is available two ways:

1. **The `Degache` facade** — one class, static methods, good for quick scripts or simple apps.
2. **Individual Validator/Formatter classes** — better for dependency injection, testing, or when you only need one feature and want a smaller autoload footprint.

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validatePhoneNumber('20123456'); // true
```

```php
use AmineZhioua\DegachePhp\Validators\PhoneValidator;

PhoneValidator::validate('20123456'); // true
```

Both call the same underlying code — the facade just delegates.

---

## Features

### Phone number

Validates Tunisian mobile numbers and identifies the carrier (Ooredoo, Orange, Tunisie Telecom).

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validatePhoneNumber('20123456');           // true
Degache::validatePhoneNumber('+21620123456');       // true
Degache::validatePhoneNumber('10123456');           // false — invalid prefix

// Strict mode rejects spaces, hyphens, parentheses
Degache::validatePhoneNumber('20 123 456', strict: true); // false

$carrier = Degache::getPhoneCarrierInfo('90123456');
// $carrier->name === 'Tunisie Telecom'
// $carrier->prefixes === ['2', '9']

Degache::formatPhoneNumber('+21620123456');
// "+216 20 123 456"
```

### CIN (Carte d'Identité Nationale)

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validateCIN('12345678'); // true
Degache::validateCIN('99999999'); // false — must start with 0 or 1
```

### RIB / Banks

Validates a 20-digit Tunisian bank RIB — structure, known bank code, and a
real ISO 7064 MOD 97-10 checksum on the 2-digit key — and identifies the
issuing bank.

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validateRIB('01123456789012345618'); // true — ATB, valid key
Degache::validateRIB('01123456789012345678'); // false — wrong key
Degache::validateRIB('99123456789012345618'); // false — unknown bank code

$bank = Degache::getBankInfoFromRIB('08003000612073212763');
// $bank->code === '08'
// $bank->name === 'Banque Internationale Arabe de Tunisie'
```

### IBAN

Validates a Tunisian IBAN (`TN` + 2 check digits + the 20-digit RIB) — its own
MOD 97-10 checksum plus the embedded RIB — and can recover the RIB and bank
from it. Every valid Tunisian IBAN starts with `TN59`: since a valid RIB is
divisible by 97, the check digits are forced to that value.

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validateIBAN('TN5910006035183598478831'); // true
Degache::validateIBAN('TN6010006035183598478831'); // false — wrong check digits

Degache::getRIBFromIBAN('TN5910006035183598478831');
// '10006035183598478831'

$bank = Degache::getBankInfoFromIBAN('TN5910006035183598478831');
// $bank->code === '10'
// $bank->name === 'Société Tunisienne de Banque'
```

### Car plate

Validates standard (`123 تونس 4567`) and special/RS (`RS 123 تونس`) Tunisian plates.

```php
use AmineZhioua\DegachePhp\Degache;
use AmineZhioua\DegachePhp\Enums\CarPlateType;

Degache::validateCarPlate('123 تونس 4567');            // true
Degache::validateCarPlate('RS 123 تونس');               // true
Degache::validateCarPlate('rs 123 تونس');               // true — normalized

// Restrict to one type
Degache::validateCarPlate('RS 123 تونس', type: CarPlateType::Standard); // false

// Strict mode: no normalization, exact Arabic text required
Degache::validateCarPlate('rs 123 تونس', strict: true); // false

$info = Degache::getCarPlateInfo('123 تونس 4567');
// $info->type === CarPlateType::Standard
// $info->components === ['prefix' => '123', 'region' => 'تونس', 'suffix' => '4567']
```

### Postal code

Validates against the real map of ~680 Tunisian postal codes across all 24 governorates.

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validatePostalCode('2001'); // true — Ariana
Degache::validatePostalCode('9999'); // false — well-formed but doesn't exist
```

### Tax ID (Matricule Fiscal)

```php
use AmineZhioua\DegachePhp\Degache;

Degache::validateTaxID('1234567A/B/C/000'); // true
Degache::validateTaxID('1234567a/B/C/000'); // false — must be uppercase
```

### Currency

Formats amounts as Tunisian Dinar using the `ar-TN` locale (via `ext-intl`).

```php
use AmineZhioua\DegachePhp\Degache;

Degache::formatCurrency(1234.56);
// "1.234,560 دينار تونسي"

Degache::formatCurrency(1234.56, symbol: true);
// "1.234,560 د.ت"

Degache::formatCurrency(1234.56, code: true);
// "1.234,560 TND"
```

### Date

Formats dates using the `ar-TN` locale, with Maghrebi month names (e.g. `جانفي`, not `يناير`).

```php
use AmineZhioua\DegachePhp\Degache;
use IntlDateFormatter;

$date = new DateTimeImmutable('2024-01-15');

Degache::formatDate($date);
// "15 جانفي 2024"

Degache::formatDate($date, IntlDateFormatter::FULL);
// "الاثنين، 15 جانفي 2024"
```

---

## Design notes

- **No options objects.** Where the original JS library used an options bag (`{ strict: true }`), this port uses named arguments instead (`strict: true`), which is more idiomatic in modern PHP.
- **DTOs over arrays.** Structured results (`CarrierInfo`, `BankInfo`, `CarPlateInfo`) are `readonly` classes, not associative arrays — you get autocomplete and type safety.
- **One intentional behavior difference:** `formatPhoneNumber` diverges from the original JS library, which has a bug where numbers already containing the `+216` prefix format incorrectly. This is fixed here. See [degachejs issue #TBD] if you're curious about the original bug.

## Testing

```bash
composer test
```

## Contributing

Issues and PRs welcome.

## License

MIT
