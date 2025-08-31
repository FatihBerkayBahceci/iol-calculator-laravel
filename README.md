# Docratech IOL Calculator

Enterprise IOL (Intraocular Lens) Calculator Package for Laravel applications.

## Features

- Multiple IOL calculation formulas (SRK/T, Holladay, Haigis, Barrett, etc.)
- Advanced biometry calculations
- Toric IOL calculations
- Complete Laravel integration
- API endpoints for calculations
- Database migrations included
- Configurable calculation parameters

## Installation

Install via Composer:

```bash
composer require docratech/iol-calculator
```

Publish migrations and config:

```bash
php artisan vendor:publish --provider="Docratech\IolCalculator\IolCalculatorServiceProvider"
php artisan migrate
```

## Configuration

Publish the config file:

```bash
php artisan vendor:publish --tag=iol-calculator-config
```

Configure your settings in `config/iol-calculator.php`.

## Usage

### Basic IOL Calculation

```php
use Docratech\IolCalculator\Services\IolCalculationService;

$calculator = app(IolCalculationService::class);

$result = $calculator->calculate([
    'axial_length' => 24.5,
    'keratometry_k1' => 43.25,
    'keratometry_k2' => 44.50,
    'anterior_chamber_depth' => 3.2,
    'formula' => 'SRK/T',
    'target_refraction' => -0.25
]);
```

### Advanced Calculations

```php
use Docratech\IolCalculator\Services\AdvancedIolCalculationService;

$advancedCalculator = app(AdvancedIolCalculationService::class);

$result = $advancedCalculator->calculateToric([
    'axial_length' => 24.5,
    'keratometry_k1' => 43.25,
    'keratometry_k2' => 44.50,
    'cylinder' => -1.25,
    'axis' => 90
]);
```

## API Endpoints

The package provides RESTful API endpoints:

- `GET /api/iol-calculator/calculations` - List calculations
- `POST /api/iol-calculator/calculations` - Create new calculation
- `GET /api/iol-calculator/calculations/{id}` - Get specific calculation
- `POST /api/iol-calculator/calculations/{id}/calculate` - Perform calculation
- `GET /api/iol-calculator/calculations/{id}/report` - Get calculation report

## Available Formulas

- SRK/T
- SRK II
- Holladay 1
- Holladay 2
- Hoffer Q
- Haigis
- Barrett Universal II
- Hill-RBF
- Kane
- Ladas
- EVO
- Olsen

## Requirements

- PHP 8.1+
- Laravel 10.0+

## License

MIT License# iol-calculator-laravel
