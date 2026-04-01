<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Settings;

class Helper {
	/**
	 * Generates a local URL from $url.
	 *
	 * @param string $url
	 * @param string $type
	 * @param bool   $bypass We can force returning the URL (even when the file doesn't exist) by setting this to true.
	 *
	 * @return string
	 */
	public static function get_local_url( $url, $type, $bypass = false ) {
		if ( ! file_exists( self::get_local_path( $url, $type ) ) && ! $bypass ) {
			return '';
		}
		
		$path = wp_parse_url( $url, PHP_URL_PATH );
		
		if ( ! $path ) {
			/**
			 * @TODO: Generate a unique path a different way.
			 */
			return '';
		}
		
		return content_url( GDPRESS_CACHE_DIR . "/$type" . $path );
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
		if ( ! isset( $url ) ) {
			return '';
		}
		
		$path = wp_parse_url( $url, PHP_URL_PATH );
		
		if ( ! $path ) {
			/**
			 * @TODO: Generate a unique path a different way.
			 */
			return '';
		}
		
		return GDPRESS_CACHE_ABSPATH . "/$type" . $path;
	}
	
	/**
	 * Generate a local URL from $filename.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $filename
	 * @param bool  $bypass Force returning the URL even when the file doesn't exist.
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
	 * Generates a local path for Google Fonts.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $filename
	 *
	 * @return string
	 */
	public static function get_local_path_google_font( $filename ) {
		return GDPRESS_CACHE_ABSPATH . "/css/$filename/google-fonts.css";
	}
	
	/**
	 * Check if $url is marked as excluded.
	 *
	 * @param mixed $type
	 * @param mixed $url
	 *
	 * @return bool
	 */
	public static function is_excluded( $type, $url ) {
		$is_excluded = isset( self::excluded()[ $type ] ) && in_array( $url, self::excluded()[ $type ] );
		
		if ( $is_excluded ) {
			return true;
		}
		
		foreach ( self::exclusion_list() as $pattern ) {
			if ( str_contains( $url, $pattern ) ) {
				return true;
			}
		}
		
		return false;
	}
	
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
	 * Contains a list of URL patterns that are automatically marked as excluded.
	 *
	 * @since v1.4.0
	 *
	 * @return array
	 */
	public static function exclusion_list() {
		return apply_filters(
			'gdpress_exclusion_list',
			[
				'gtag.js',
				'analytics.js',
				'maps.googleapis.com',
				'js.stripe.com',
				'app.usercentrics.eu',
			]
		);
	}
	
	/**
	 * Check if $url is Google Fonts API request.
	 *
	 * @since v1.1.0
	 *
	 * @param mixed $url
	 *
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
	
	/**
	 * Check if $url is Web Font Loader script.
	 *
	 * @since v1.3.1
	 *
	 * @param mixed $url
	 *
	 * @return bool
	 */
	public static function is_webfont_loader_request( $url ) {
		return strpos( $url, 'webfont.js' ) !== false;
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
	 *
	 * @param string $type
	 * @param string $url
	 * @param bool   $write_to_db
	 *
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
	 * Contains a list of URL patterns that are displayed as an upsell.
	 *
	 * @since v1.4.0
	 *
	 * @return array
	 */
	public static function upsell_list() {
		return apply_filters(
			'gdpress_upsell_list',
			[
				'fonts.googleapis.com/css',
				'fonts.googleapis.com/icon',
				'fonts.gstatic.com',
				'webfont.js',
			]
		);
	}
}
