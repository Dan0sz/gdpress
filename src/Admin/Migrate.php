<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Helper;

class Migrate {
	
	/**
	 * Build class.
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', [ $this, 'maybe_migrate' ], 1 );
	}
	
	/**
	 * Run the migrations this install hasn't had yet.
	 *
	 * @action admin_init
	 *
	 * @return void
	 */
	public function maybe_migrate() {
		$db_version = get_option( Settings::GDPRESS_OPTION_DB_VERSION, '0' );
		
		if ( version_compare( $db_version, GDPRESS_DB_VERSION, '>=' ) ) {
			return;
		}
		
		if ( version_compare( $db_version, '1.3.2', '<' ) ) {
			$this->remove_local_requests();
		}
		
		update_option( Settings::GDPRESS_OPTION_DB_VERSION, GDPRESS_DB_VERSION );
	}
	
	/**
	 * Previous versions would store this site's own files if the URL's scheme differed.
	 *
	 * @since v1.3.2
	 *
	 * @return void
	 */
	private function remove_local_requests() {
		$requests = maybe_unserialize( Helper::requests( true ) );
		
		if ( ! is_array( $requests ) ) {
			return;
		}
		
		foreach ( $requests as $type => $type_requests ) {
			$requests[ $type ] = array_values( array_filter( $type_requests, fn( $request ) => ! Helper::is_local_request( $request['href'] ) ) );
		}
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, array_filter( $requests ) );
	}
}
