<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin;

defined( 'ABSPATH' ) || exit;

class Notice {

	const GDPRESS_ADMIN_NOTICE_TRANSIENT  = 'gdpress_admin_notice';
	const GDPRESS_ADMIN_NOTICE_EXPIRATION = 60;

	/** @var array $notices */
	public static $notices = [];

	/**
	 * @param        $message
	 * @param bool   $die
	 * @param string $type
	 * @param int    $code
	 * @param string $screen_id
	 * @param string $id
	 */
	public static function set_notice( $message, $type = 'success', $screen_id = 'all', $id = '' ) {
		self::$notices                               = get_transient( self::GDPRESS_ADMIN_NOTICE_TRANSIENT );
		self::$notices[ $screen_id ][ $type ][ $id ] = $message;

		set_transient( self::GDPRESS_ADMIN_NOTICE_TRANSIENT, self::$notices, self::GDPRESS_ADMIN_NOTICE_EXPIRATION );
	}

	/**
	 * Prints notice (if any)
	 */
	public static function print_notices() {
		$admin_notices = get_transient( self::GDPRESS_ADMIN_NOTICE_TRANSIENT );

		if ( is_array( $admin_notices ) ) {
			$current_screen = get_current_screen();

			foreach ( $admin_notices as $screen => $notice ) {
				if ( $current_screen->id != $screen && $screen != 'all' ) {
					continue;
				}

				foreach ( $notice as $type => $message ) {
					?>
					<div id="message" class="notice notice-<?php echo esc_attr( $type ); ?> is-dismissible">
						<?php foreach ( $message as $line ) : ?>
							<p><strong><?php echo wp_kses_post( $line ); ?></strong></p>
						<?php endforeach; ?>
					</div>
					<?php
				}
			}
		}

		delete_transient( self::GDPRESS_ADMIN_NOTICE_TRANSIENT );
	}
}
