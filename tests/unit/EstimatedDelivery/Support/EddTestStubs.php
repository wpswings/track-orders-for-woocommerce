<?php
/**
 * Shared WordPress stubs for the WPS-8400 estimated delivery unit tests.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\EstimatedDelivery;

use Brain\Monkey\Functions;
use DateTimeImmutable;
use DateTimeZone;

require_once __DIR__ . '/wc-doubles.php';

/**
 * Installs date / helper stubs. The site timezone is read from $test->siteTimezone().
 */
class EddTestStubs {

	/**
	 * Install the stubs.
	 *
	 * @param object $test Test case exposing siteTimezone().
	 */
	public static function install( $test ) {
		Functions\when( 'wp_timezone' )->alias(
			static function () use ( $test ) {
				return new DateTimeZone( $test->siteTimezone() );
			}
		);
		Functions\when( 'wp_date' )->alias(
			static function ( $format, $timestamp = null, $timezone = null ) use ( $test ) {
				$tz = $timezone ? $timezone : new DateTimeZone( $test->siteTimezone() );
				return ( new DateTimeImmutable( '@' . ( null === $timestamp ? time() : $timestamp ) ) )->setTimezone( $tz )->format( $format );
			}
		);
		Functions\when( 'date_i18n' )->alias(
			static function ( $format, $timestamp = false ) {
				return gmdate( $format, false === $timestamp ? time() : $timestamp );
			}
		);
		Functions\when( 'absint' )->alias(
			static function ( $value ) {
				return abs( (int) $value );
			}
		);
		Functions\when( 'wp_parse_args' )->alias(
			static function ( $args, $defaults = array() ) {
				return array_merge( (array) $defaults, (array) $args );
			}
		);
	}
}
