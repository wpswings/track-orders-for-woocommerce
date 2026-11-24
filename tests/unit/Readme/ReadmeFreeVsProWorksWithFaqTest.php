<?php
/**
 * WPS-8393: readme "Free vs Pro", "Works with" and FAQ sections.
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\Readme;

use Tests\Unit\TestCase;

/**
 * Parses README.txt and checks the Free vs Pro lists, the Works with section and the FAQ,
 * and that the claims they make are backed by the plugin code.
 */
class ReadmeFreeVsProWorksWithFaqTest extends TestCase {

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
	 * Body of a "=== Heading ===" sub section (until the next heading of level 2 or 3).
	 *
	 * @param string $heading Heading text.
	 * @return string
	 */
	private function subSection( $heading ) {
		$found = preg_match( '/^===\s*' . preg_quote( $heading, '/' ) . '\s*===\s*$(.*?)(?=^===?\s|\z)/ms', $this->readme, $m );
		$this->assertSame( 1, $found, "Section '=== $heading ===' not found in README.txt" );
		return $m[1];
	}

	/**
	 * Body of the "== Frequently Asked Questions ==" section.
	 *
	 * @return string
	 */
	private function faqSection() {
		$found = preg_match( '/^==\s*Frequently Asked Questions\s*==\s*$(.*?)(?=^==\s*[^=]|\z)/ms', $this->readme, $m );
		$this->assertSame( 1, $found, 'FAQ section not found in README.txt' );
		return $m[1];
	}

	/**
	 * FAQ entries as question => answer.
	 *
	 * @return array<string,string>
	 */
	private function faqEntries() {
		$parts   = preg_split( '/^=\s*(.+?)\s*=\s*$/m', $this->faqSection(), -1, PREG_SPLIT_DELIM_CAPTURE );
		$entries = array();
		$count   = count( $parts );
		for ( $i = 1; $i < $count; $i += 2 ) {
			$entries[ $parts[ $i ] ] = trim( $parts[ $i + 1 ] );
		}
		return $entries;
	}

	/**
	 * Bullet items that follow a bold "**Label:**" line in the Free vs Pro section.
	 *
	 * @param string $label List label, e.g. "Free includes".
	 * @return string[]
	 */
	private function freeVsProList( $label ) {
		$section = $this->subSection( 'Free vs Pro' );
		$found   = preg_match( '/^\*\*' . preg_quote( $label, '/' ) . ':\*\*\s*$\n(.*?)(?=^\*\*|^>|\z)/ms', $section, $m );
		$this->assertSame( 1, $found, "List '$label' not found in Free vs Pro" );

		preg_match_all( '/^\*\s+(.+?)\s*$/m', $m[1], $items );
		return $items[1];
	}

	/**
	 * Lower-case, strip markup/punctuation for comparison.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private function normalise( $text ) {
		$text = strtolower( preg_replace( '/[*`"“”]/u', '', $text ) );
		return trim( preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Find the first FAQ entry whose question matches the pattern.
	 *
	 * @param string $pattern Regex applied to the question.
	 * @return array{0:string,1:string} Question and answer.
	 */
	private function faqMatching( $pattern ) {
		foreach ( $this->faqEntries() as $question => $answer ) {
			if ( preg_match( $pattern, $question ) ) {
				return array( $question, $answer );
			}
		}
		$this->fail( "No FAQ question matches $pattern. Questions: " . implode( ' | ', array_keys( $this->faqEntries() ) ) );
		return array( '', '' );
	}

	/* ---------------------------------------------------------------- Free vs Pro */

	/**
	 * Both lists exist and are not empty.
	 */
	public function test_free_vs_pro_has_both_lists() {
		$this->assertGreaterThanOrEqual( 5, count( $this->freeVsProList( 'Free includes' ) ) );
		$this->assertGreaterThanOrEqual( 5, count( $this->freeVsProList( 'Pro adds' ) ) );
	}

	/**
	 * Pro is described as an add-on that includes everything in Free.
	 */
	public function test_pro_described_as_superset_of_free() {
		$this->assertMatchesRegularExpression( '/Pro .*includes everything in Free/i', $this->subSection( 'Free vs Pro' ) );
	}

	/**
	 * No feature is listed in both Free and Pro.
	 */
	public function test_no_feature_listed_in_both_free_and_pro() {
		$free = array_map( array( $this, 'normalise' ), $this->freeVsProList( 'Free includes' ) );
		$pro  = array_map( array( $this, 'normalise' ), $this->freeVsProList( 'Pro adds' ) );

		$this->assertSame( array(), array_values( array_intersect( $free, $pro ) ), 'Features listed in both Free and Pro' );
	}

	/**
	 * No duplicates inside each list.
	 */
	public function test_lists_have_no_duplicates() {
		foreach ( array( 'Free includes', 'Pro adds' ) as $label ) {
			$items = array_map( array( $this, 'normalise' ), $this->freeVsProList( $label ) );
			$this->assertSame( count( $items ), count( array_unique( $items ) ), "Duplicate items in '$label'" );
		}
	}

	/**
	 * Pro-only carriers / integrations are never claimed by the Free list and vice versa.
	 */
	public function test_pro_only_integrations_not_in_free_list() {
		$free = $this->normalise( implode( "\n", $this->freeVsProList( 'Free includes' ) ) );
		$pro  = $this->normalise( implode( "\n", $this->freeVsProList( 'Pro adds' ) ) );

		foreach ( array( 'dhl', 'usps', 'canada post', '17track', 'twilio', 'elementor', 'custom css' ) as $pro_only ) {
			$this->assertStringNotContainsString( $pro_only, $free, "Pro-only '$pro_only' listed under Free" );
			$this->assertStringContainsString( $pro_only, $pro, "Pro-only '$pro_only' missing from Pro adds" );
		}
		foreach ( array( 'fedex', 'trackingmore', 'partial shipment', 'order api' ) as $free_feature ) {
			$this->assertStringContainsString( $free_feature, $free, "Free '$free_feature' missing from Free includes" );
			$this->assertStringNotContainsString( $free_feature, $pro, "Free '$free_feature' listed under Pro adds" );
		}
	}

	/**
	 * Free features claimed by the readme exist in the free plugin code.
	 */
	public function test_free_claims_backed_by_code() {
		$public = $this->readPluginFile( 'public/class-track-orders-for-woocommerce-public.php' );
		$admin  = $this->readPluginFile( 'admin/class-track-orders-for-woocommerce-admin.php' );

		$this->assertStringContainsString( "add_shortcode( 'WPS_MUTIPLE_CARRIER_TRACKING_FORM'", $public );
		$this->assertStringContainsString( "'Enable Multi-Carrier Tracking'", $admin );
		$this->assertStringContainsString( "'Enable Order Tracking Using Order Id Only'", $admin );
	}

	/* ---------------------------------------------------------------- Works with */

	/**
	 * Works with lists HPOS, block checkout, RMA, Upsell Funnel Builder and shipping/label plugins.
	 *
	 * @dataProvider works_with_provider
	 *
	 * @param string $pattern Regex that one bullet must match.
	 */
	public function test_works_with_lists_integration( $pattern ) {
		preg_match_all( '/^\*\s+(.+)$/m', $this->subSection( 'Works with' ), $items );

		$matching = preg_grep( $pattern, $items[1] );
		$this->assertNotEmpty( $matching, "No 'Works with' item matches $pattern" );
	}

	/**
	 * Required "Works with" entries.
	 *
	 * @return array
	 */
	public function works_with_provider() {
		return array(
			'HPOS'                  => array( '/HPOS|High-Performance Order Storage/' ),
			'Block checkout'        => array( '/Cart and Checkout Blocks|Checkout Block/i' ),
			'RMA'                   => array( '/RMA.*Return, Refund and Exchange/' ),
			'Upsell Funnel Builder' => array( '/Upsell Funnel Builder/' ),
			'Shipping/label'        => array( '/Shipping and label plugins/i' ),
			'Shipping carriers'     => array( '/Shipping carriers.*FedEx/i' ),
		);
	}

	/**
	 * Links in Works with are absolute https links.
	 */
	public function test_works_with_links_are_https() {
		preg_match_all( '/\]\(([^)]+)\)/', $this->subSection( 'Works with' ), $links );

		$this->assertNotEmpty( $links[1] );
		foreach ( $links[1] as $url ) {
			$this->assertMatchesRegularExpression( '#^https://[a-z0-9.-]+\.[a-z]{2,}/#i', $url );
		}
	}

	/**
	 * The HPOS / block checkout compatibility claims match the declarations in the main plugin file.
	 */
	public function test_works_with_compatibility_is_declared_in_code() {
		$main = $this->readPluginFile( 'track-orders-for-woocommerce.php' );

		$this->assertMatchesRegularExpression( "/declare_compatibility\(\s*'custom_order_tables',\s*__FILE__,\s*true\s*\)/", $main );
		$this->assertMatchesRegularExpression( "/declare_compatibility\(\s*'cart_checkout_blocks',\s*__FILE__,\s*true\s*\)/", $main );
	}

	/* ---------------------------------------------------------------- FAQ */

	/**
	 * FAQ explains tracking by tracking number and by email.
	 */
	public function test_faq_tracking_by_number_or_email() {
		list( , $answer ) = $this->faqMatching( '/tracking number.*email|email.*tracking number/i' );

		$this->assertStringContainsString( 'tracking number', $answer );
		$this->assertMatchesRegularExpression( '/billing email|email/i', $answer );
		$this->assertStringContainsString( '[WPS_MUTIPLE_CARRIER_TRACKING_FORM]', $answer );
	}

	/**
	 * FAQ has an HPOS question answered with "Yes".
	 */
	public function test_faq_hpos() {
		list( , $answer ) = $this->faqMatching( '/^Is it HPOS compatible\?$/i' );

		$this->assertStringStartsWith( 'Yes', $answer );
		$this->assertMatchesRegularExpression( '/High-Performance Order Storage/', $answer );
	}

	/**
	 * FAQ explains which carriers are supported.
	 */
	public function test_faq_carriers() {
		list( , $answer ) = $this->faqMatching( '/carriers? (are )?supported/i' );

		foreach ( array( 'TrackingMore', 'FedEx', 'DHL', 'USPS', 'Canada Post', '17TRACK' ) as $carrier ) {
			$this->assertStringContainsString( $carrier, $answer );
		}
		$this->assertMatchesRegularExpression( '/Pro adds/', $answer, 'Carrier answer must say which carriers are Pro-only' );
	}

	/**
	 * Every FAQ question is unique and has an answer.
	 */
	public function test_faq_entries_unique_and_answered() {
		$entries = $this->faqEntries();
		$this->assertGreaterThanOrEqual( 3, count( $entries ) );

		$questions = array_map( 'strtolower', array_keys( $entries ) );
		$this->assertSame( count( $questions ), count( array_unique( $questions ) ), 'Duplicate FAQ question' );

		foreach ( $entries as $question => $answer ) {
			$this->assertNotSame( '', $answer, "FAQ '$question' has no answer" );
			$this->assertStringEndsWith( '?', $question, "FAQ '$question' is not a question" );
		}
	}
}
