# Track Orders for WooCommerce - Tests

Two kinds of tests live here:

| Kind | Tooling | Location | Runs in CI |
|------|---------|----------|------------|
| Unit | PHPUnit 9 + Brain Monkey + Mockery (no WordPress needed) | `tests/*.php` (legacy), `tests/unit/<Feature>/*Test.php` | `.github/workflows/phpunit-tests.yml` (PHP 7.4 - 8.3) |
| E2E | Playwright + `@wordpress/env` (Docker) | `tests/e2e/specs/*.spec.ts` | `.github/workflows/e2e-tests.yml` |

```
tests/
├── bootstrap.php            # PHPUnit bootstrap: autoload, Patchwork, constants, WP stubs
├── wp-function-stubs.php    # Fallback WordPress functions (redefinable per test)
├── *Test.php                # Legacy unit tests (namespace Tests)
├── unit/
│   ├── TestCase.php         # Base class: Brain Monkey setUp/tearDown + WpTestHelpers
│   ├── Helpers/WpTestHelpers.php
│   ├── Support/WpTestHelpersTest.php   # Shows/proves every helper
│   └── Activator/ActivatorTest.php     # Example: plugin class loaded on its own
└── e2e/
    ├── global-setup.ts      # Prepares the site once per run, saves admin login
    ├── utils/               # wpCli, createOrder, setOption, loginAsAdmin, ...
    └── specs/               # <task-id>-<name>.spec.ts
```

## Unit tests

### Run

```bash
composer install
vendor/bin/phpunit                    # everything (suite "unit")
vendor/bin/phpunit --testsuite unit
vendor/bin/phpunit tests/unit/Activator/ActivatorTest.php
vendor/bin/phpunit --filter test_activation_creates_tracking_pages
```

### Conventions

- New tests go in `tests/unit/<Feature>/<Something>Test.php`, namespace `Tests\Unit\<Feature>`,
  and extend `Tests\Unit\TestCase`. File names must end in `Test.php`.
- Tests must stay PHP 7.4 compatible (the Quality Gate runs PHPCompatibility on `tests/`).
- Load the class under test with `$this->requirePluginFile( 'includes/class-....php' )`.
  `ABSPATH` and the plugin constants are defined by the bootstrap, so plugin files can be
  `require_once`d individually without WordPress.
- Stub any WordPress/WooCommerce function the code calls with Brain Monkey
  (`Functions\when( 'fn' )->justReturn( ... )`, `Functions\expect( 'fn' )->once()->with( ... )`).
  Everything is reset after each test.
- Never declare WordPress functions in `bootstrap.php`; add generic fallbacks to
  `wp-function-stubs.php` instead (Patchwork can only redefine functions declared after it loaded).
- Use `set_up()` / `tear_down()` (PHPUnit polyfills) and call the parent.

### Helper API (`Tests\Unit\TestCase`, trait `Tests\Unit\Helpers\WpTestHelpers`)

| Method | What it does |
|--------|--------------|
| `setOptions( array $options = [] )` | Backs `get_option` / `update_option` / `add_option` / `delete_option` with an in-memory store seeded with `$options`. Unknown options return the `$default` given to `get_option()`. Call again to add more. |
| `getOption( $name, $default = null )` | Read the in-memory store, e.g. to assert what the code saved. |
| `mockOrder( array $props = [] )` | Mockery `WC_Order` with `get_id`, `get_order_number`, `get_status`, `get_billing_email`, `get_customer_id`, `get_meta`, `update_meta_data`, `add_meta_data`, `delete_meta_data`, `save`. Props: `id` (1), `status` ('processing'), `billing_email`, `customer_id` (0), `meta` (array), plus any `'method_name' => value|callable`. Registers the order for `wc_get_order()`. All expectations are `byDefault()`, so tests can override them with `shouldReceive()`. |
| `getOrderMeta( $order_or_id, $key = null )` | Current meta of a mocked order (live: reflects `update_meta_data()` calls). |
| `stubWcGetOrder()` | (Re)stubs `wc_get_order()` to return mocked orders by ID (or `false`). Called by `mockOrder()`. |
| `stubCommonWpFunctions()` | Pass-through stubs for translation (`__`, `_e`, `_x`, `_n`, `esc_html__`, `esc_attr__`, ...), escaping (`esc_html`, `esc_attr`, `esc_url`, ...), `sanitize_text_field`, `sanitize_key`, `wp_unslash`, `wp_kses_post`. |
| `requirePluginFile( $relative_path )` | `require_once` a file relative to the plugin root. |

`apply_filters()` / `do_action()` / `add_action()` / `add_filter()` come from Brain Monkey:
`apply_filters()` returns the value unchanged unless you set an expectation
(`Filters\expectApplied( 'hook' )->andReturn( ... )`); assert registrations with
`has_action()` / `has_filter()` or `Actions\expectAdded()`.

Example:

```php
namespace Tests\Unit\Tracking;

use Brain\Monkey\Functions;
use Tests\Unit\TestCase;

class TrackingNumberTest extends TestCase {

	protected function set_up() {
		parent::set_up();
		$this->requirePluginFile( 'includes/class-track-orders-for-woocommerce-tracking-service.php' );
	}

	public function test_saves_tracking_number() {
		$this->setOptions( array( 'wps_tofw_is_plugin_enable' => 'on' ) );
		$order = $this->mockOrder( array( 'id' => 10, 'status' => 'completed' ) );
		Functions\when( 'current_time' )->justReturn( 1700000000 );

		// ... call the code under test with $order or order ID 10 ...

		$this->assertSame( 'TRK1', $this->getOrderMeta( 10, 'wps_tofw_package_tracking_number' ) );
	}
}
```

## E2E tests

### Requirements

Node 22.13+, npm and Docker (Docker Desktop, Colima, Docker Engine, ...).

### Run locally

```bash
npm ci
npx playwright install chromium
npm run env:start        # WordPress + WooCommerce + this plugin at http://localhost:8888 (admin / password)
npm run test:e2e         # global setup configures the site, then runs tests/e2e/specs
npm run test:e2e -- tests/e2e/specs/WPS-8391-smoke.spec.ts
npm run test:e2e:headed  # watch the browser
npm run test:e2e:report  # open the last HTML report
npm run env:stop         # or env:destroy to delete the containers and database
```

`.wp-env.json` mounts the latest stable WooCommerce at `wp-content/plugins/woocommerce` and this
folder at `wp-content/plugins/track-orders-for-woocommerce` (so the plugin slug is correct whatever
your checkout folder is called). Put local tweaks in `.wp-env.override.json` (git-ignored), e.g. another port.
`WP_BASE_URL` overrides the site URL used by Playwright and `E2E_WP_CLI` replaces the
`wp-env run cli wp` prefix (e.g. `E2E_WP_CLI="wp --path=/path/to/wordpress"`).

### What global setup does (`tests/e2e/global-setup.ts`)

Idempotent, runs before every test run:

1. Activates `woocommerce` and `track-orders-for-woocommerce`.
2. `setupStore()`: skips WooCommerce onboarding/task list, turns off coming-soon mode, sets USD and a US store
   address, creates the WooCommerce pages, a flat-rate shipping zone "E2E Everywhere" ($5), pretty permalinks
   and the simple product `E2E-SIMPLE` ($10).
3. `setupPlugin()`: sets `wps_tofw_is_plugin_enable=on`, `tofw_enable_track_order=on`,
   `wps_msp_onboarding_data_sent=sent` (no onboarding popup) and makes sure the tracking pages exist
   (option `wps_tofw_tracking_page`).
4. Logs in as admin and saves the session to `tests/e2e/.auth/admin.json`; every spec starts logged in.

### Conventions

- One spec per task: `tests/e2e/specs/<task-id>-<name>.spec.ts`, e.g. `WPS-8500-guest-tracking.spec.ts`.
- Import helpers from `../utils`. Arrange state with WP-CLI helpers (fast, no UI), then assert in the browser.
- Specs run serially against one shared site: create your own data (orders, options) per test and do not
  rely on data from other specs. Restore options you change if other specs could be affected.
- No `waitForTimeout()`/sleeps: use web-first assertions (`await expect( locator ).toBeVisible()`) and
  `page.waitForURL()` / `page.waitForResponse()`.
- Prefer stable selectors (ids, names, plugin classes) over text that translators may change.
- Guest (logged-out) tests: `test.use( { storageState: GUEST_STORAGE_STATE } );`.

### Helper API (`tests/e2e/utils`)

| Helper | Description |
|--------|-------------|
| `wpCli( command: string \| string[] ): string` | Run WP-CLI in the wp-env `cli` container, returns stdout. Strings are split on spaces (quotes respected). |
| `wpEval( php: string ): string` | `wp eval` the code, returns what it echoes. |
| `wpEvalJson<T>( php: string ): T` | Runs the code inside a closure and returns the JSON-decoded value it `return`s. |
| `phpValue( value ): string` | PHP expression that decodes a JS value (safe way to pass data into `wpEval`). |
| `setOption( name, value )` / `setOptions( { name: value } )` | `update_option` (arrays/objects supported). |
| `getOption<T>( name ): T` / `deleteOption( name )` | Read / delete an option. |
| `createOrder( { status?, billing_email?, billing_first_name?, billing_last_name?, customer_id?, meta?, product_sku?, quantity? } ): CreatedOrder` | Creates an order with `wc_create_order()` (HPOS-safe). Defaults: `processing`, `guest@example.com`, guest, one `E2E-SIMPLE`. Returns `{ id, order_key, status, billing_email, view_url }`. |
| `getOrderMeta<T>( orderId, key )` / `updateOrderMeta( orderId, { key: value } )` / `getOrderStatus( orderId )` | Order data through the WC CRUD API. |
| `ensureSimpleProduct( sku?, name?, price? ): number` / `getProductId( sku? )` / `E2E_PRODUCT_SKU` | Products. |
| `trackingPageUrl( 'wps_track_order_page' \| 'wps_guest_track_order_page' \| 'wps_fedex_track_order' ): string` | Permalink of a page the plugin created. |
| `SETTINGS_PAGE`, `settingsTabUrl( tab )` | Plugin settings URLs, e.g. `settingsTabUrl( 'track-orders-for-woocommerce-track-order' )`. |
| `DEFAULT_PLUGIN_OPTIONS`, `setupPlugin( extraOptions? )`, `setupStore()`, `activatePlugins( ...slugs )` | Used by global setup; call `setupPlugin()` to restore defaults. |
| `loginAsAdmin( page, user?, password? )`, `GUEST_STORAGE_STATE`, `ADMIN_USER`, `ADMIN_PASSWORD` | Authentication. |

Example:

```ts
import { expect, test } from '@playwright/test';
import { createOrder, GUEST_STORAGE_STATE, trackingPageUrl } from '../utils';

test.describe( 'WPS-8500 guest tracking', () => {
	test.use( { storageState: GUEST_STORAGE_STATE } );

	test( 'guest can track an order by ID and email', async ( { page } ) => {
		const order = createOrder( { status: 'completed', billing_email: 'buyer@example.com' } );

		await page.goto( trackingPageUrl( 'wps_guest_track_order_page' ) );
		await page.locator( '#order_id' ).fill( String( order.id ) );
		await page.locator( '#order_email' ).fill( order.billing_email );
		await page.locator( 'input[name="wps_tofw_order_id_submit"]' ).click();

		await expect( page.locator( 'body' ) ).toContainText( String( order.id ) );
	} );
} );
```

### CI

`e2e-tests.yml` runs on every pull request and on pushes to `release/**`: `npm ci`, installs Chromium,
`wp-env start`, `npm run test:e2e`. On failure the Playwright report and traces are uploaded as the
`playwright-report` artifact and `wp-content/debug.log` is printed.

Development files (`node_modules`, `tests`, `package*.json`, `playwright.config.ts`, `.wp-env*.json`, ...)
are listed in `.distignore` so they are not shipped in the plugin zip.
