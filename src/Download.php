<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess;

use GDPRess\Admin\Notice;

class Download {
	
	const WOFF2_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0';
	
	/**
	 * Download $filename from $url.
	 *
	 * @param string $type     The type of file to download.
	 * @param string $filename The name of the file to download.
	 * @param string $url      The url of the file to download.
	 *
	 * @return string
	 *
	 * @throws \SodiumException
	 */
	public function download_file( $filename, $type, $url ) {
		// Normalize protocol-relative URLs.
		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		}
		
		if ( Helper::is_google_fonts_request( $url ) ) {
			$file_path = Helper::get_local_path_google_font( $filename );
			$file_url  = Helper::get_local_url_google_font( $filename, true );
		} else {
			$file_path = Helper::get_local_path( $url, $type );
			$file_url  = Helper::get_local_url( $url, $type, true );
		}
		
		if ( file_exists( $file_path ) ) {
			return $file_url;
		}
		
		if ( Helper::is_google_fonts_request( $url ) ) {
			$path = str_replace( 'google-fonts.css', '', $file_path );
		} else {
			$path = str_replace( $filename, '', $file_path );
		}
		
		$tmp = $this->download_to_tmp( $path, $url );
		
		if ( ! $tmp ) {
			return '';
		}
		
		if ( $type === 'css' ) {
			$this->parse_font_faces( $tmp, $url );
		}
		
		if ( copy( $tmp, $file_path ) ) {
			@unlink( $tmp );
			
			return $file_url;
		}
		
		@unlink( $tmp );
		
		return '';
	}
	
	/**
	 * Downloads file to temporary storage and creates directories recursively where necessary.
	 *
	 * @param string $path The path to create.
	 * @param string $url  The URL of the file to download.
	 *
	 * @return string
	 *
	 * @throws \SodiumException
	 */
	private function download_to_tmp( $path, $url ) {
		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		
		wp_mkdir_p( $path );
		
		// Is relative protocol?
		if ( str_starts_with( $url, '//' ) ) {
			$url = 'https:' . $url;
		}
		
		/**
		 * We set this user agent to retrieve WOFF2 files from the Google Fonts API.
		 *
		 * @since v1.1.0 This doesn't affect other requests at all.
		 */
		add_filter(
			'http_headers_useragent',
			function () {
				return self::WOFF2_USER_AGENT;
			}
		);
		
		$tmp = download_url( $url );
		
		if ( is_wp_error( $tmp ) ) {
			/** @var \WP_Error $tmp */
			Notice::set_notice( sprintf( __( 'Ouch! GDPRess encountered an error while downloading <code>%s</code>', 'gdpr-press' ), basename( $url ) ) . ': ' . $tmp->get_error_message(), 'error', 'all', 'gdpress-download-failed' );
			
			return '';
		}
		
		return $tmp;
	}
	
	/**
	 * Manipulates $file's embedded font faces.
	 *
	 * @param mixed $file
	 *
	 * @return false|int
	 *
	 * @throws \SodiumException
	 */
	private function parse_font_faces( $file, $ext_url ) {
		$contents = file_get_contents( $file );
		
		if ( ! str_contains( $contents, '@font-face' ) ) {
			return false;
		}
		
		preg_match_all( '/@font-face\s*?{.*?}/si', $contents, $font_faces );
		
		/**
		 * Let's assume $font_faces[0] exists. We already checked if the stylesheet contains @font-face statements,
		 * so if the Regex didn't find any, then that's a bug in the regex, and I'd like to know about it.
		 */
		$font_faces = $font_faces[0];
		
		/**
		 * Parse each @font-face statement for src url's.
		 */
		foreach ( $font_faces as $font_face ) {
			preg_match_all( '/url\([\'"]?(?P<urls>.+?)[\'"]?\)/', $font_face, $urls );
			
			$urls = $urls['urls'] ?? [];
			
			/**
			 * Download each file (defined as @font-face src) to the appropriate dir.
			 */
			foreach ( $urls as $url ) {
				$orig_url = $url;
				
				// Normalize protocol-relative URLs.
				if ( str_starts_with( $url, '//' ) ) {
					$url = 'https:' . $url;
				}
				
				// Save a copy of $is_rel_url for later down the road.
				if ( $is_rel_url = $this->is_rel_url( $url ) ) {
					$url = $this->get_abs_url( $url, $ext_url );
				}
				
				[ $cleaned_url ] = explode( '?', $url );
				$filename = basename( $cleaned_url );
				$dir      = str_replace( $filename, '', $cleaned_url );
				$path     = Helper::get_local_path( $dir, 'css' );
				
				if ( ! $path ) {
					Notice::set_notice( sprintf( __( 'Ouch! GDPRess encountered an error while determining the local path for <code>%s</code>.', 'gdpr-press' ), $url ), 'error', 'gdpress-settings-manage', 'gdpress-path-error' );
					
					continue;
				}
				
				$tmp = $this->download_to_tmp( $path, $url );
				
				if ( ! $tmp ) {
					continue;
				}
				
				/**
				 * If absolute URLs are used for this @font-face statement, rewrite
				 * $contents to use local cache dir.
				 */
				if ( ! $is_rel_url ) {
					// Use original URL (and protocol-relative variant) for matching.
					$contents = $this->replace_abs_urls( $contents, str_replace( $filename, '', $orig_url ) );
					
					// Also match protocol-normalized variant if it differs from the original.
					if ( str_starts_with( $orig_url, 'https:' ) || str_starts_with( $orig_url, 'http:' ) ) {
						$protocol_relative = preg_replace( '/^https?:/', '', $orig_url );
						$contents          = $this->replace_abs_urls( $contents, str_replace( $filename, '', $protocol_relative ) );
					} elseif ( str_starts_with( $orig_url, '//' ) ) {
						$protocol_absolute = 'https:' . $orig_url;
						$contents          = $this->replace_abs_urls( $contents, str_replace( $filename, '', $protocol_absolute ) );
					}
				}
				
				/**
				 * Copy font file.
				 */
				if ( ! copy( $tmp, trailingslashit( $path ) . $filename ) ) {
					Notice::set_notice( sprintf( __( 'Ouch! GDPRess failed to copy font file <code>%s</code>.', 'gdpr-press' ), $filename ), 'error', 'gdpress-settings-manage', 'gdpress-copy-error' );
				}
				
				@unlink( $tmp );
			}
		}
		
		return file_put_contents( $file, $contents );
	}
	
	/**
	 * Checks if $source is a relative URL (root-relative or relative to directory).
	 *
	 * @param string $source
	 *
	 * @return bool
	 */
	private function is_rel_url( string $source ) {
		// Does $source have a scheme? e.g. http://, https://, data:, etc.
		if ( preg_match( '/^[a-zA-Z][a-zA-Z0-9+.\-]*:/', $source ) ) {
			return false;
		}
		
		// Does $source start with // (protocol-relative)?
		if ( str_starts_with( $source, '//' ) ) {
			return false;
		}
		
		return true;
	}
	
	/**
	 * @param string $rel_url Relative URL to rewrite, e.g. '/fonts/file.woff2'
	 * @param string $source  URL to be used for rewriting relative URL to an absolute URL.
	 *
	 * @return string Absolute URL
	 */
	private function get_abs_url( $rel_url, $source ) {
		// Root-relative URL? Resolve against origin of $source (or site origin as fallback).
		if ( str_starts_with( $rel_url, '/' ) ) {
			$origin = '';
			$parts  = parse_url( $source );
			
			if ( isset( $parts['scheme'], $parts['host'] ) ) {
				$origin = $parts['scheme'] . '://' . $parts['host'];
				
				if ( isset( $parts['port'] ) ) {
					$origin .= ':' . $parts['port'];
				}
			}
			
			if ( $origin ) {
				return $origin . $rel_url;
			}
			
			return get_site_url( null, $rel_url );
		}
		
		$folder_depth  = substr_count( $rel_url, '../' );
		$url_to_insert = $source;
		$parts         = parse_url( $source );
		$origin        = '';
		
		if ( isset( $parts['scheme'], $parts['host'] ) ) {
			$origin = $parts['scheme'] . '://' . $parts['host'];
			
			if ( isset( $parts['port'] ) ) {
				$origin .= ':' . $parts['port'];
			}
		}
		
		/**
		 * Remove everything after the last occurrence of a forward slash ('/');
		 *
		 * $i = 0: Filename
		 *      1: First level subdirectory, i.e. '../'
		 *      2: 2nd level subdirectory, i.e. '../../'
		 *      3: Etc.
		 */
		for ( $i = 0; $i <= $folder_depth; $i ++ ) {
			$last_slash_pos = strrpos( $url_to_insert, '/' );
			
			if ( $last_slash_pos === false || ( $origin && $url_to_insert === $origin ) || ( $origin && $last_slash_pos < strlen( $origin . '/' ) ) ) {
				break;
			}
			
			$url_to_insert = substr( $url_to_insert, 0, $last_slash_pos );
		}
		
		$path = ltrim( $rel_url, './' );
		
		return $url_to_insert . '/' . $path;
	}
	
	/**
	 * Parse $file contents for occurrences of host.
	 *
	 * @param string $contents
	 * @param string $url
	 *
	 * @return array|string|string[]
	 */
	private function replace_abs_urls( $contents, $url ) {
		$path = parse_url( $url, PHP_URL_PATH );
		
		if ( ! $path ) {
			$path = '/';
		}
		
		$local_url = content_url( GDPRESS_CACHE_DIR . '/css' . $path );
		
		return str_replace( $url, $local_url, $contents );
	}
}
