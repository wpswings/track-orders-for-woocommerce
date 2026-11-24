<?php
/**
 * Base test case for unit tests that need Brain Monkey / Mockery.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit;

use Brain\Monkey;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\Unit\Helpers\WpTestHelpers;
use Yoast\PHPUnitPolyfills\TestCases\TestCase as PolyfillTestCase;

/**
 * Sets up and tears down Brain Monkey around every test and exposes the
 * WpTestHelpers API (setOptions, mockOrder, stubCommonWpFunctions, ...).
 */
abstract class TestCase extends PolyfillTestCase {

	use MockeryPHPUnitIntegration;
	use WpTestHelpers;

	/**
	 * Set up Brain Monkey before each test.
	 */
	protected function set_up() {
		parent::set_up();
		Monkey\setUp();
		$this->resetWpTestHelpers();
	}

	/**
	 * Tear down Brain Monkey (also closes Mockery) after each test.
	 */
	protected function tear_down() {
		Monkey\tearDown();
		parent::tear_down();
	}
}
