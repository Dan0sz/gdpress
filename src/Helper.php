<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class Helper {
	/**
	 * Contains all URLs marked as excluded.
	 * 
	 * @return array 
	 */
	public static function excluded() {
		static $excluded;

		/**
		 * Get a fresh copy from the database if $excluded is empty|null|false (on 1st run)
		 */
		if ( empty( $excluded ) ) {
			$excluded = get_option( Settings::GDPRESS_MANAGE_SETTING_EXCLUDED, [] ) ?: [];
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * 
		 * @since v0.1
		 */
		if ( is_string( $excluded ) ) {
			$excluded = unserialize( $excluded );
		}

		return $excluded;
	}

	/**
	 * Contains all fetched external requests.
	 * 
	 * @return array 
	 */
	public static function requests( $raw = false ) {
		static $requests;

		/**
		 * Get a fresh copy from the database if $requests is empty|null|false (on 1st run)
		 */
		if ( empty( $requests ) && $raw === false ) {
			$requests = get_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [] ) ?: [];
		} elseif ( empty( $requests ) ) {
			$requests = get_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS );
		}

		/**
		 * get_option() should take care of this, but sometimes it doesn't.
		 * 
		 * @since v0.1
		 */
		if ( is_string( $requests ) ) {
			$requests = unserialize( $requests );
		}

		return $requests;
	}

	/**
	 * Contains all localized file URLs.
	 * 
	 * @return array 
	 */
	public static function local() {
		static $local;

		/**
		 * Get a fresh copy from the database if $excluded is empty|null|false (on 1st run)
		 */
		if ( empty( $local ) ) {
			$local = get_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, [] ) ?: [];
		}

		/**
		 * get_option() should take care of this, but often it doesn't.
		 * 
		 * @since v0.1
		 */
		if ( is_string( $local ) ) {
			$local = unserialize( $local );
		}

		return $local;
	}

	/**
	 * Check if $url is marked as excluded.
	 * 
	 * @param mixed $type 
	 * @param mixed $url 
	 * @return bool 
	 */
	public static function is_excluded( $type, $url ) {
		return isset( self::excluded()[ $type ] ) && in_array( $url, self::excluded()[ $type ] );
	}

	/**
	 * 
	 * @param string $type 
	 * @param string $url 
	 * @param bool $write_to_db 
	 * @return bool 
	 */
	public static function set_local_url( $type = '', $url = '', $write_to_db = false ) {
		static $local_urls;

		if ( $type && $url ) {
			$local_urls[ $type ][] = $url;
		}

		if ( $write_to_db ) {
			return update_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, $local_urls );
		}

		return true;
	}

	/**
	 * Generate a local path from $url.
	 * 
	 * @param string $url 
	 * @param string $type
	 *  
	 * @return string 
	 */
	public static function get_local_path( $url, $type ) {
		if ( ! isset( $url['path'] ) ) {
			return '';
		}
		
		return GDPRESS_CACHE_ABSPATH . "/$type" . wp_parse_url( $url )['path'];
	}

	/**
	 * Generates a local path for Google Fonts.
	 * 
	 * @since v1.1.0
	 * 
	 * @param mixed $filename 
	 * @return string 
	 */
	public static function get_local_path_google_font( $filename ) {
		return GDPRESS_CACHE_ABSPATH . "/css/$filename/google-fonts.css";
	}

	/**
	 * Generates a local URL from $url.
	 * 
	 * @param string $url
	 * @param string $type
	 * @param bool $bypass We can force returning the URL (even when the file doesn't exist) by setting this to true.
	 * 
	 * @return string 
	 */
	public static function get_local_url( $url, $type, $bypass = false ) {
		if ( ! file_exists( self::get_local_path( $url, $type ) ) && ! $bypass ) {
			return '';
		}

		return content_url( GDPRESS_CACHE_DIR . "/$type" . wp_parse_url( $url )['path'] );
	}

	/**
	 * Generate a local URL from $filename.
	 * 
	 * @since v1.1.0
	 * 
	 * @param mixed $filename 
	 * @param bool $bypass Force returning the URL even when the file doesn't exist.
	 * 
	 * @return string 
	 */
	public static function get_local_url_google_font( $filename, $bypass = false ) {
		if ( ! file_exists( self::get_local_path_google_font( $filename ) ) && ! $bypass ) {
			return '';
		}

		return content_url( GDPRESS_CACHE_DIR . "/css/$filename/google-fonts.css" );
	}

	/**
	 * Check if $url is Google Fonts API request.
	 * 
	 * @since v1.1.0
	 * 
	 * @param mixed $url 
	 * @return bool 
	 */
	public static function is_google_fonts_request( $url ) {
		/**
		 * Is OMGF active? If so, bail.
		 */
		return strpos( $url, 'fonts.googleapis.com/css' ) !== false
			|| strpos( $url, 'fonts.googleapis.com/icon' ) !== false
			|| strpos( $url, 'fonts.gstatic.com' ) !== false;
	}
}
