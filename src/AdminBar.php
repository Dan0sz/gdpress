<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

class AdminBar {
	
	/**
	 * Set fields.
	 *
	 * @return void
	 */
	public function __construct() {
		$this->init();
	}
	
	/**
	 * Hooks and Filters
	 *
	 * @return void
	 */
	private function init() {
		add_action( 'admin_bar_menu', [ $this, 'add_admin_bar_menu' ], 99 );
	}
	
	/**
	 * Add GDPRess item to the WordPress admin bar.
	 *
	 * @param \WP_Admin_Bar $wp_admin_bar
	 *
	 * @return void
	 */
	public function add_admin_bar_menu( $wp_admin_bar ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		
		$wp_admin_bar->add_node(
			[
				'id'    => 'gdpress',
				'title' => 'GDPRess',
				'href'  => admin_url( 'options-general.php?page=' . Settings::GDPRESS_ADMIN_PAGE ),
			]
		);
		
		$wp_admin_bar->add_node(
			[
				'id'     => 'gdpress-scan',
				'parent' => 'gdpress',
				'title'  => __( 'Scan this page for external resources', 'gdpr-press' ),
				'href'   => add_query_arg( [ 'gdpress' => '' ] ),
			]
		);
	}
}
