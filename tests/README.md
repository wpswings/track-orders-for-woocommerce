# Track Orders for WooCommerce - Unit Tests

This directory contains PHPUnit tests for the Track Orders for WooCommerce plugin.

## Setup

### Install Dependencies

```bash
composer install
```

## Running Tests

### Run All Tests

```bash
composer test
```

or

```bash
vendor/bin/phpunit
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/PluginTest.php
```

### Run with Coverage

```bash
composer test-coverage
```

or

```bash
vendor/bin/phpunit --coverage-html coverage
```

Then open `coverage/index.html` in your browser.

## Test Structure

### Test Files

- **PluginTest.php** - Tests basic plugin functionality, constants, and file structure
- **SanitizationTest.php** - Tests data sanitization and input validation
- **SecurityTest.php** - Tests security measures (XSS, SQL injection prevention, ABSPATH checks)
- **HelperFunctionsTest.php** - Tests WordPress helper functions
- **VersionCompatibilityTest.php** - Tests version requirements and compatibility declarations

### Bootstrap

The `bootstrap.php` file initializes the test environment by:
- Loading Composer autoloader
- Loading PHPUnit polyfills
- Defining plugin constants
- Mocking WordPress core functions

## Test Coverage

The tests cover:
- ✅ Plugin constants and configuration
- ✅ File structure validation
- ✅ Input sanitization (XSS, SQL injection prevention)
- ✅ Security checks (ABSPATH, nonce verification)
- ✅ Version compatibility
- ✅ WordPress and WooCommerce requirements
- ✅ HPOS compatibility
- ✅ Helper functions

## Continuous Integration

These tests run automatically on GitHub Actions for:
- PHP 7.4, 8.0, 8.1, 8.2, 8.3
- Pull requests to all branches
- Code coverage reporting

## Writing New Tests

To add new tests:

1. Create a new file in `tests/` directory
2. Extend `Yoast\PHPUnitPolyfills\TestCases\TestCase`
3. Name test methods starting with `test`
4. Use PHPUnit assertions

Example:

```php
<?php
namespace Tests;

use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

class MyNewTest extends PolyfillTestCase {

    public function testSomething() {
        $this->assertTrue(true);
    }
}
```

## Requirements

- PHP >= 7.4
- Composer
- PHPUnit 9.5+

## License

GPL-3.0-or-later
