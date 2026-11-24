<?php
/**
 * Thrown by the wp_send_json_* stubs of the WPS-8401 tests (WordPress dies there).
 *
 * @package Track_Orders_For_Woocommerce
 */

namespace Tests\Unit\MultiVendor\Support;

/**
 * JSON response sent by an AJAX handler.
 */
class JsonResponse extends \RuntimeException {

	/**
	 * Success flag.
	 *
	 * @var bool
	 */
	public $success;

	/**
	 * Response data.
	 *
	 * @var mixed
	 */
	public $data;

	/**
	 * HTTP status.
	 *
	 * @var int|null
	 */
	public $status;

	/**
	 * Constructor.
	 *
	 * @param bool     $success Success.
	 * @param mixed    $data    Data.
	 * @param int|null $status  Status.
	 */
	public function __construct( $success, $data, $status ) {
		parent::__construct( 'wp_send_json_' . ( $success ? 'success' : 'error' ) );
		$this->success = $success;
		$this->data    = $data;
		$this->status  = $status;
	}
}
