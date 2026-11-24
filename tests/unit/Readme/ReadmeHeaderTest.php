<?php
/**
 * WPS-8392: readme plugin name, tags, short description and intro.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Readme;

use Tests\Unit\TestCase;

/**
 * Parses README.txt (WordPress.org format) and the main plugin file header and
 * checks the approved listing metadata.
 */
class ReadmeHeaderTest extends TestCase {

	const PLUGIN_NAME = 'Track Orders for WooCommerce – Order Tracking & Shipment Tracking';

	const SHORT_DESCRIPTION = 'Order & shipment tracking for WooCommerce: tracking numbers, 1000+ carriers, branded tracking page, email/WhatsApp alerts & partial shipments.';

	/**
	 * README.txt contents.
	 *
	 * @var string
	 */
	private $readme;

	/**
	 * Load README.txt.
	 */
	protected function set_up() {
		parent::set_up();
		$this->readme = $this->readPluginFile( 'README.txt' );
	}

	/**
	 * Read a file relative to the plugin root (line endings normalised).
	 *
	 * @param string $relative Relative path.
	 * @return string
	 */
	private function readPluginFile( $relative ) {
		$path = TRACK_ORDERS_FOR_WOOCOMMERCE_DIR_PATH . $relative;
		$this->assertFileExists( $path );
		return str_replace( "\r\n", "\n", (string) file_get_contents( $path ) );
	}

	/**
	 * Lines of the readme header: everything before the first "== Section ==".
	 *
	 * @return string[]
	 */
	private function headerLines() {
		$parts = preg_split( '/^==\s*[^=].*?==\s*$/m', $this->readme, 2 );
		return explode( "\n", trim( $parts[0] ) );
	}

	/**
	 * "Field: value" pairs of the readme header.
	 *
	 * @return array<string,string>
	 */
	private function headerFields() {
		$fields = array();
		foreach ( $this->headerLines() as $line ) {
			if ( preg_match( '/^([A-Za-z][A-Za-z ]+):\s*(.*)$/', trim( $line ), $m ) ) {
				$fields[ trim( $m[1] ) ] = trim( $m[2] );
			}
		}
		return $fields;
	}

	/**
	 * The short description: first non-empty header line that is not the title or a field.
	 *
	 * @return string
	 */
	private function shortDescription() {
		foreach ( array_slice( $this->headerLines(), 1 ) as $line ) {
			$line = trim( $line );
			if ( '' === $line || preg_match( '/^[A-Za-z][A-Za-z ]+:\s/', $line ) ) {
				continue;
			}
			return $line;
		}
		return '';
	}

	/**
	 * Body of the "== Description ==" section up to the first sub heading.
	 *
	 * @return string
	 */
	private function intro() {
		$this->assertSame( 1, preg_match( '/^==\s*Description\s*==\s*$(.*?)^===/ms', $this->readme, $m ), 'Description section not found' );
		return $m[1];
	}

	/**
	 * Header fields of the main plugin file.
	 *
	 * @return array<string,string>
	 */
	private function pluginHeader() {
		$source = $this->readPluginFile( 'track-orders-for-woocommerce.php' );
		preg_match_all( '/^\s*\*\s*([A-Za-z][A-Za-z ]+?):\s*(.+?)\s*$/m', $source, $m, PREG_SET_ORDER );
		$fields = array();
		foreach ( $m as $match ) {
			if ( ! isset( $fields[ $match[1] ] ) ) {
				$fields[ $match[1] ] = $match[2];
			}
		}
		return $fields;
	}

	/**
	 * The title line holds the approved plugin name.
	 */
	public function test_plugin_name_in_title_line() {
		$this->assertSame( '=== ' . self::PLUGIN_NAME . ' ===', trim( strtok( $this->readme, "\n" ) ) );
	}

	/**
	 * README.md (GitHub) uses the same plugin name.
	 */
	public function test_readme_md_uses_same_plugin_name() {
		$this->assertStringContainsString( '# ' . self::PLUGIN_NAME, $this->readPluginFile( 'README.md' ) );
	}

	/**
	 * Exactly the five approved tags, in order, without duplicates.
	 */
	public function test_exactly_five_approved_tags() {
		$fields = $this->headerFields();
		$this->assertArrayHasKey( 'Tags', $fields );

		$tags = array_map( 'trim', explode( ',', $fields['Tags'] ) );

		$this->assertSame(
			array( 'order tracking', 'shipment tracking', 'track order', 'woocommerce order tracking', 'tracking number' ),
			$tags
		);
		$this->assertCount( 5, array_unique( array_map( 'strtolower', $tags ) ), 'WordPress.org only shows the first 5 tags' );
	}

	/**
	 * Short description equals the approved text and fits WordPress.org's 150-character limit.
	 */
	public function test_short_description_is_approved_text_and_within_limit() {
		$short = $this->shortDescription();

		$this->assertSame( self::SHORT_DESCRIPTION, $short );
		$this->assertLessThanOrEqual( 150, $this->characterCount( $short ) );
	}

	/**
	 * The short description is the only free text in the header (WordPress.org truncates otherwise).
	 */
	public function test_header_has_single_short_description_line() {
		$free_text = array();
		foreach ( array_slice( $this->headerLines(), 1 ) as $line ) {
			$line = trim( $line );
			if ( '' !== $line && ! preg_match( '/^[A-Za-z][A-Za-z ]+:\s/', $line ) ) {
				$free_text[] = $line;
			}
		}
		$this->assertSame( array( self::SHORT_DESCRIPTION ), $free_text );
	}

	/**
	 * The intro uses the "WooCommerce order tracking" and "shipment tracking" keywords.
	 */
	public function test_intro_uses_target_keywords() {
		$intro = $this->intro();

		$this->assertStringContainsString( 'WooCommerce order tracking', $intro );
		$this->assertStringContainsString( 'shipment tracking', $intro );
	}

	/**
	 * The keywords appear in the first paragraph, not only further down.
	 */
	public function test_first_intro_paragraph_mentions_both_keywords() {
		$paragraphs = preg_split( '/\n\s*\n/', trim( $this->intro() ) );
		$first      = $paragraphs[0];

		$this->assertStringContainsString( 'WooCommerce order tracking', $first );
		$this->assertStringContainsString( 'shipment tracking', $first );
	}

	/**
	 * Tested up to / WC tested up to / Stable tag / requirements match the plugin header.
	 */
	public function test_versions_consistent_with_plugin_header() {
		$readme = $this->headerFields();
		$plugin = $this->pluginHeader();

		foreach ( array( 'Tested up to', 'WC tested up to', 'Requires at least', 'WC requires at least', 'Requires PHP', 'Stable tag' ) as $field ) {
			$this->assertArrayHasKey( $field, $readme, "README.txt is missing '$field'" );
			$this->assertArrayHasKey( $field, $plugin, "Plugin header is missing '$field'" );
			$this->assertSame( $plugin[ $field ], $readme[ $field ], "'$field' differs between README.txt and the plugin header" );
		}
	}

	/**
	 * Stable tag is the released plugin version.
	 */
	public function test_stable_tag_equals_plugin_version() {
		$readme = $this->headerFields();
		$plugin = $this->pluginHeader();

		$this->assertSame( $plugin['Version'], $readme['Stable tag'] );
		$this->assertSame( TRACK_ORDERS_FOR_WOOCOMMERCE_VERSION, $readme['Stable tag'] );
	}

	/**
	 * The stable version has a changelog entry.
	 */
	public function test_stable_tag_has_changelog_entry() {
		$stable = $this->headerFields()['Stable tag'];

		$this->assertMatchesRegularExpression(
			'/^==\s*Changelog\s*==.*?^=\s*' . preg_quote( $stable, '/' ) . '\b/ms',
			$this->readme
		);
	}

	/**
	 * Version fields are well-formed version numbers.
	 */
	public function test_version_fields_are_well_formed() {
		$readme = $this->headerFields();

		foreach ( array( 'Tested up to', 'WC tested up to', 'Stable tag', 'Requires PHP' ) as $field ) {
			$this->assertMatchesRegularExpression( '/^\d+(\.\d+){1,2}$/', $readme[ $field ], "'$field' is not a version number" );
		}
	}

	/**
	 * README.md badges advertise the same tested versions.
	 */
	public function test_readme_md_badges_match_tested_versions() {
		$readme = $this->headerFields();
		$md     = $this->readPluginFile( 'README.md' );

		$this->assertStringContainsString( 'WP%20tested-' . $readme['Tested up to'] . '-', $md );
		$this->assertStringContainsString( 'WC%20tested-' . $readme['WC tested up to'] . '-', $md );
		$this->assertStringContainsString( 'Tags: ' . $readme['Tags'], $md );
	}

	/**
	 * Multibyte-safe length (the name/description use an en dash).
	 *
	 * @param string $text Text.
	 * @return int
	 */
	private function characterCount( $text ) {
		return (int) preg_match_all( '/./us', $text );
	}
}
