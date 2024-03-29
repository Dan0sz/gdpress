<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

use GDPRess\Admin;
use GDPRess\Helper;

defined( 'ABSPATH' ) || exit;

class Settings extends Admin {

	const GDPRESS_ADMIN_PAGE           = 'gdpr-press';
	const GDPRESS_ADMIN_SECTION_MANAGE = 'gdpress-manage';
	const GDPRESS_ADMIN_SECTION_HELP   = 'gdpress-help';
	const GDPRESS_ADMIN_CSS_HANDLE     = 'gdpress-admin-css';
	const GDPRESS_ADMIN_JS_HANDLE      = 'gdpress-admin-js';

	/**
	 * Transients
	 */
	const GDPRESS_TRANSIENT_NEWS_REEL = 'gdpress_news_reel';

	/**
	 * Settings
	 */
	const GDPRESS_MANAGE_SETTING_TEST_MODE = 'gdpress_test_mode';
	const GDPRESS_MANAGE_SETTING_REQUESTS  = 'gdpress_external_requests';
	const GDPRESS_MANAGE_SETTING_LOCAL     = 'gdpress_local_urls';
	const GDPRESS_MANAGE_SETTING_EXCLUDED  = 'gdpress_excluded_urls';

	/** @var string $active_tab */
	private $active_tab = '';

	/** @var string $admin_page */
	private $admin_page = '';

	/**
	 * Set fields
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : self::GDPRESS_ADMIN_SECTION_MANAGE;
		$this->admin_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

		parent::__construct();

		$this->init();
	}

	/**
	 * Action & Filter hooks.
	 * 
	 * @return void 
	 */
	private function init() {
		// Global
		add_action( 'admin_menu', [ $this, 'create_menu' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( GDPRESS_PLUGIN_FILE ), [ $this, 'add_settings_link' ] );

		if ( $this->admin_page !== self::GDPRESS_ADMIN_PAGE ) {
			return;
		}

		// Scripts
		add_action( 'admin_head', [ $this, 'enqueue_admin_assets' ] );

		// Footer Text
		add_filter( 'admin_footer_text', [ $this, 'set_footer_text_left' ], 99 );
		add_filter( 'update_footer', [ $this, 'set_footer_text_right' ], 11 );

		// Tabs
		add_action( 'gdpress_settings_tab', [ $this, 'add_manage_tab' ], 1 );
		add_action( 'gdpress_settings_tab', [ $this, 'add_help_tab' ], 2 );

		// Settings Screen Content
		add_action( 'gdpress_settings_content', [ $this, 'set_content' ], 1 );
	}

	/**
	 * Create WP menu-item
	 */
	public function create_menu() {
		add_options_page(
			'GDPRess',
			'GDPRess',
			'manage_options',
			self::GDPRESS_ADMIN_PAGE,
			[ $this, 'settings_page' ]
		);

		add_action( 'admin_init', [ $this, 'register_settings' ] );
	}


	/**
	 * Create settings page
	 */
	public function settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( "You're not cool enough to access this page.", 'gdpr-press' ) );
		} ?>

		<div class="wrap">
			<h1><?php _e( 'GDPRess | Eliminate External Requests', 'gdpr-press' ); ?></h1>

			<h2 class="gpress-nav nav-tab-wrapper">
				<?php do_action( 'gdpress_settings_tab' ); ?>
			</h2>

			<form id="<?php echo esc_attr( $this->active_tab ); ?>-form" method="post" action="options.php?tab=<?php echo esc_attr( $this->active_tab ); ?>">
				<?php
				settings_fields( $this->active_tab );
				do_settings_sections( $this->active_tab ); 
				?>

				<?php do_action( 'gdpress_settings_content' ); ?>

				<?php
				$current_section = str_replace( '-', '_', $this->active_tab );
				do_action( "after_$current_section" ); 
				?>

				<?php if ( $this->active_tab == self::GDPRESS_ADMIN_SECTION_MANAGE && Helper::requests() ) : ?>
					<?php submit_button( __( 'Save Changes & Download', 'gdpr-press' ), 'primary', 'submit', false ); ?>
					<input type="button" name="button" id="gdpress-fetch" class="button" value="<?php echo __( 'Scan Again', 'gdpr-press' ); ?>">
					<a href="#" id="gdpress-flush" data-nonce="<?php echo wp_create_nonce( self::GDPRESS_ADMIN_PAGE ); ?>" class="gdpress-flush button-cancel"><?php _e( 'Empty Cache Directory', 'gdpr-press' ); ?></a>
				<?php endif; ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Register all settings.
	 *
	 * @throws ReflectionException
	 */
	public function register_settings() {
		if (
			$this->active_tab !== self::GDPRESS_ADMIN_SECTION_MANAGE
			&& $this->active_tab !== self::GDPRESS_ADMIN_SECTION_HELP
		) {
			$this->active_tab = self::GDPRESS_ADMIN_SECTION_MANAGE;
		}

		foreach ( $this->get_settings() as $constant => $value ) {
			register_setting(
				$this->active_tab,
				$value
			);
		}
	}

	/**
	 * Get all settings for the current section using the constants in this class.
	 *
	 * @return array
	 * @throws ReflectionException
	 */
	private function get_settings() {
		$reflection = new \ReflectionClass( $this );
		$constants  = apply_filters( 'gdpress_register_settings', $reflection->getConstants() );

		switch ( $this->active_tab ) {
			default:
				$needle = apply_filters( 'gdpress_register_settings_needle', 'GDPRESS_MANAGE_SETTING' );
		}

		return array_filter(
			$constants,
			function ( $key ) use ( $needle ) {
				return strpos( $key, $needle ) !== false;
			},
			ARRAY_FILTER_USE_KEY
		);
	}

	/**
	 * Add settings link to plugin overview
	 *
	 * @param $links
	 *
	 * @return mixed
	 */
	public function add_settings_link( $links ) {
		$adminUrl     = admin_url() . 'options-general.php?page=' . self::GDPRESS_ADMIN_PAGE;
		$settingsLink = "<a href='$adminUrl'>" . __( 'Settings', 'gdpr-press' ) . '</a>';
		array_push( $links, $settingsLink );

		return $links;
	}
	/**
	 * We add the assets directly to the head to avoid ad blockers blocking the URLs cause they include 'analytics'.
	 * 
	 * @return void 
	 */
	public function enqueue_admin_assets() {
		wp_enqueue_script( self::GDPRESS_ADMIN_JS_HANDLE, plugin_dir_url( GDPRESS_PLUGIN_FILE ) . 'assets/js/gdpress-admin.js', [ 'jquery' ], GDPRESS_STATIC_VERSION, true );
		wp_enqueue_style( self::GDPRESS_ADMIN_CSS_HANDLE, plugin_dir_url( GDPRESS_PLUGIN_FILE ) . 'assets/css/gdpress-admin.css', [], GDPRESS_STATIC_VERSION );
	}

	/**
	 * Changes footer text.
	 * 
	 * @return string 
	 */
	public function set_footer_text_left() {
		$text = sprintf( __( 'Coded with %s in The Netherlands @ <strong>FFW.Press</strong>.', 'gdpr-press' ), '<span class="dashicons dashicons-heart ffwp-heart"></span>' );

		return '<span id="footer-thankyou">' . $text . '</span>';
	}


	/**
	 * All logic to generate the news reel in the bottom right of the footer on GDPRess' settings pages.
	 * 
	 * Includes multiple checks to make sure the reel is only shown if a recent post is available.
	 * 
	 * @param mixed $text 
	 * @return mixed 
	 */
	public function set_footer_text_right( $text ) {
		if ( ! extension_loaded( 'simplexml' ) ) {
			return $text;
		}

		/**
		 * If a WordPress update is available, show the original text.
		 */
		if ( strpos( $text, 'Get Version' ) !== false ) {
			return $text;
		}

		// Prevents bashing the API.
		$xml = get_transient( self::GDPRESS_TRANSIENT_NEWS_REEL );

		if ( ! $xml ) {
			$response = wp_remote_get( 'https://daan.dev/blog/tag/gdpr/feed' );

			if ( ! is_wp_error( $response ) ) {
				$xml = wp_remote_retrieve_body( $response );

				// Refresh the feed once a day to prevent bashing of the API.
				set_transient( self::GDPRESS_TRANSIENT_NEWS_REEL, $xml, DAY_IN_SECONDS );
			}
		}

		if ( ! $xml ) {
			return $text;
		}

		/**
		 * Make sure the XML is properly encoded.
		 */
		$xml = utf8_encode( html_entity_decode( $xml ) );
		$xml = simplexml_load_string( $xml );

		if ( ! $xml ) {
			return $text;
		}

		$items = $xml->channel->item ?? [];

		if ( empty( $items ) ) {
			return $text;
		}

		$text  = sprintf( __( 'Recently tagged <a target="_blank" href="%s"><strong>#GDPR</strong></a> on my blog:', 'gdpr-press' ), 'https://daan.dev/blog/tag/gdpr' ) . ' ';
		$text .= '<span id="gdpress-ticker-wrap">';
		$i     = 0;

		foreach ( $items as $item ) {
			if ( $i > 4 ) {
				break;
			}

			$hide  = $i > 0 ? 'style="display: none;"' : '';
			$text .= "<span class='ticker-item' $hide>" . sprintf( '<a target="_blank" href="%s"><em>%s</em></a>', $item->link, $item->title ) . '</span>';
			$i++;
		}

		$text .= '</span>';

		return $text;
	}

	/**
	 * Adds the Manage Tab to the settings screen.
	 * 
	 * @return void 
	 */
	public function add_manage_tab() {
		$this->generate_tab( self::GDPRESS_ADMIN_SECTION_MANAGE, 'dashicons-download', __( 'Manage External Requests', 'gdpr-press' ) );
	}

	/**
	 * Adds the Manage Tab to the settings screen.
	 * 
	 * @return void 
	 */
	public function add_help_tab() {
		$this->generate_tab( self::GDPRESS_ADMIN_SECTION_HELP, 'dashicons-editor-help', __( 'Help & Support', 'gdpr-press' ) );
	}

	/**
	 * @param      $id
	 * @param null $icon
	 * @param null $label
	 */
	private function generate_tab( $id, $icon = null, $label = null ) {
		?>
		<a class="nav-tab dashicons-before <?php echo esc_attr( $icon ); ?> <?php echo $this->active_tab == $id ? 'nav-tab-active' : ''; ?>" href="<?php echo $this->generate_tab_link( $id ); ?>">
			<?php echo esc_html( $label ); ?>
		</a>
		<?php
	}

	/**
	 * @param $tab
	 *
	 * @return string
	 */
	private function generate_tab_link( $tab ) {
		$admin_page = self::GDPRESS_ADMIN_PAGE;

		return esc_url( admin_url( "options-general.php?page=$admin_page&tab=$tab" ) );
	}

	/**
	 * Render active content.
	 */
	public function set_content() {
		echo apply_filters( str_replace( '-', '_', esc_attr( $this->active_tab ) ) . '_content', '' );
	}
}
