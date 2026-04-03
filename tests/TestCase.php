<?php
/**
 * Tests Root
 *
 * @package GDPRess
 * @author  Daan van den Bergh
 */

namespace GDPRess\Tests;

use Yoast\WPTestUtils\BrainMonkey\TestCase as YoastTestCase;

class TestCase extends YoastTestCase {
	/**
	 * Build class.
	 */
	public function __construct() {
		/**
		 * During local unit testing this constant is required.
		 */
		if ( ! defined( 'ABSPATH' ) ) {
			define( 'ABSPATH', true );
		}
		
		/**
		 * Required for loading assets.
		 */
		if ( ! defined( 'GDPRESS_TESTS_ROOT' ) ) {
			define( 'GDPRESS_TESTS_ROOT', __DIR__ . '/' );
		}
		
		parent::__construct();
	}
}
