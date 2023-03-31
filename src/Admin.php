<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Ajax;
use GDPRess\Admin\Download;
use GDPRess\Admin\Settings\Help;
use GDPRess\Admin\Settings\Manage;
use GDPRess\Admin\Notice;

defined( 'ABSPATH' ) || exit;

class Admin {

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
		add_action( 'admin_notices', [ $this, 'print_notices' ] );
		add_action( 'admin_init', [ $this, 'download_files' ] );

		$this->add_ajax_hooks();
		$this->build_manage_section();
		$this->build_help_section();
	}

	/**
	 * Print onscreen notices, if any.
	 * 
	 * @return void 
	 */
	public function print_notices() {
		Notice::print_notices();
	}

	/**
	 * File Downloader
	 * 
	 * @return void 
	 */
	public function download_files() {
		new Download();
	}

	/**
	 * Add AJAX hooks.
	 * 
	 * @return void 
	 */
	private function add_ajax_hooks() {
		new Ajax();
	}

	/**
	 * Build Manage section contents.
	 * 
	 * @return void 
	 */
	private function build_manage_section() {
		new Manage();
	}

	/**
	 * Load Help section contents.
	 * 
	 * @return void 
	 */
	private function build_help_section() {
		new Help();
	}
}
