<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 * 
 * This class uses template_redirect's output to rewrite URLs in the page source.
 */
namespace GDPRess;

use GDPRess\Helper;
use GDPRess\Admin\Settings;

defined( 'ABSPATH' ) || exit;

class RewriteUrl {

	/**
	 * @var array $page_builders Array of keys set by page builders when they're displaying their previews.
	 */
	private $page_builders = [
		'bt-beaverbuildertheme',
		'ct_builder',
		'elementor-preview',
		'et_fb',
		'fb-edit',
		'fl_builder',
		'siteorigin_panels_live_editor',
		'tve',
		'vc_action',
	];

	/**
	 * Set fields.
	 * 
	 * @return void 
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Filters & Actions
	 * 
	 * @return void 
	 */
	private function init() {
		/**
		 * Halt execution if:
		 * * Test Mode is enabled and current user is not an admin.
		 * * Test Mode is enabled and `gdpress` GET-parameter is not set.
		 */
		if (
			( ( GDPRESS_TEST_MODE == 'on' && ! current_user_can( 'manage_options' ) )
				&& ( GDPRESS_TEST_MODE == 'on' && ! current_user_can( 'manage_options' ) && ! isset( $_GET['gdpress'] ) ) )
		) {
			return;
		}

		/**
		 * Make sure GDPRess runs first, this allows:
		 * - Plugins like Autoptimize to capture the locally hosted stylesheets for compression.
		 * - OMGF Pro to optimize previously externally hosted stylesheets, containing Google Fonts.
		 * - Etc.
		 */
		add_action( 'template_redirect', [ $this, 'maybe_buffer_output' ], 1 );

		add_filter( 'gdpress_buffer_output', [ $this, 'rewrite_urls' ] );
	}

	/**
	 * Start output buffer.
	 * 
	 * @action template_redirect
	 * 
	 * @return void 
	 */
	public function maybe_buffer_output() {
		/**
		 * Make sure Page Builder previews don't get optimized content.
		 */
		foreach ( $this->page_builders as $page_builder ) {
			if ( array_key_exists( $page_builder, $_GET ) ) {
				return false;
			}
		}

		/** 
		 * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
		 * 
		 * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
		 */
		if ( array_key_exists( 'PageSpeed', $_GET ) && 'off' === $_GET['PageSpeed'] ) {
			return false;
		}

		/**
		 * WP Customizer previews shouldn't get optimized content.
		 */
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}

		/**
		 * Let's GO!
		 */
		ob_start( [ $this, 'return_buffer' ] );
	}

	/**
	 * Wraps the buffer output into a filter after performing several checks.
	 *  
	 * @since v4.3.1 Tested with:
	 *               - Autoptimize v2.9.5.1:
	 *                 - CSS/JS/Page Optimization: On
	 *               - Cache Enabler v1.8.7:
	 *                 - Default Settings
	 *               - W3 Total Cache v2.2.1:
	 *                 - Page Cache: Disk (basic)
	 *                 - Database/Object Cache: Off
	 *                 - JS/CSS minify/combine: On
	 *               - WP Fastest Cache v0.9.9:
	 *                 - JS/CSS minify/combine: On
	 *                 - Page Cache: On
	 *               - WP Rocket v3.8.8:
	 *                 - Page Cache: Enabled
	 *                 - JS/CSS minify/combine: Enabled
	 *               - WP Super Cache v1.7.7
	 *                 - Page Cache: Enabled
	 *  
	 * @return string $html
	 */
	public function return_buffer( $html ) {
		if ( ! $this->should_process( $html ) ) {
			return $html;
		}

		return apply_filters( 'gdpress_buffer_output', $html );
	}

	/**
	 * Check if given markup can be processed.
	 *
	 * @param string $content Markup.
	 *
	 * @return bool
	 */
	public function should_process( $content ) {
		$process = true;

		if (
			// Has no HTML tag
			stripos( $content, '<html' ) === false
			// Is XSL stylesheet
			|| ( stripos( $content, '<xsl:stylesheet' ) !== false || stripos( $content, '<?xml-stylesheet' ) !== false )
			// Is not a HTML5 Document
			|| preg_match( '/^<!DOCTYPE.+html>/i', ltrim( $content ) ) === 0
		) {
			$process = false;
		}

		return $process;
	}

	/**
	 * Rewrite all external URLs in $html.
	 * 
	 * @filter gdpress_buffer_output
	 * 
	 * @param string $html 
	 * 
	 * @return string 
	 */
	public function rewrite_urls( $html ) {
		$site_url = get_home_url();

		preg_match_all( '/<link.*?stylesheet.*?[\/]?>/', $html, $stylesheets );

		$stylesheets = $this->parse_stylesheets( $stylesheets[0] ?? [], $site_url );

		preg_match_all( '/<script.*?src.*?<\/script>/', $html, $scripts );

		$scripts = $this->parse_scripts( $scripts[0] ?? [], $site_url );

		$external_reqs = [];

		if ( ! empty( $stylesheets ) ) {
			$external_reqs['css'] = $stylesheets;
		}

		if ( ! empty( $scripts ) ) {
			$external_reqs['js'] = $scripts;
		}

		if ( wp_json_encode( Helper::requests() ) !== wp_json_encode( $external_reqs ) ) {
			update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, $external_reqs );
		}

		$html = $this->process_requests( $external_reqs, $html );

		return $html;
	}

	/**
	 * Build processable array from $stylesheets.
	 * 
	 * @since v1.2.0
	 * 
	 * @param array  $stylesheets 
	 * @param string $site_url
	 *  
	 * @return array { int => { 'name' => string, 'href' => string } }
	 */
	private function parse_stylesheets( $stylesheets, $site_url ) {
		$external_css = [];
		$i            = 0;

		foreach ( $stylesheets as $stylesheet ) {
			preg_match( '/href=[\'"](?P<href>.*?)[\'"]/', $stylesheet, $href );

			$href = $href['href'] ?? '';

			// If the resource is already locally loaded or it's an inline style block, move along.
			if ( ! $href || strpos( $href, '/' ) === 0 || strpos( $href, $site_url ) !== false ) {
				continue;
			}

			// If OMGF is active, let's ignore any stylesheets generated by the Google Fonts API.
			if ( function_exists( 'omgf_init' ) && Helper::is_google_fonts_request( $href ) ) {
				continue;
			}

			$external_css[ $i ]['href'] = $href;

			if ( strpos( $href, '?' ) !== false && ! Helper::is_google_fonts_request( $href ) ) {
				$parsed_url = wp_parse_url( $href );
				$href       = $parsed_url['path'];
			}

			$external_css[ $i ]['name'] = $this->generate_file_name( $href );
			$i++;
		}

		return $external_css;
	}

	/**
	 * Build processable array from scripts.
	 * 
	 * @since v1.2.0
	 * 
	 * @param array  $scripts 
	 * @param string $site_url
	 *  
	 * @return array { int => { 'name' => string, 'href' => string } }
	 */
	private function parse_scripts( $scripts, $site_url ) {
		$external_js = [];
		$i           = 0;

		foreach ( $scripts as $script ) {
			preg_match( '/src=[\'"](?P<src>.*?)[\'"]/', $script, $src );

			$src = $src['src'] ?? '';

			// If the resource is already locally loaded or it's an inline style block, move along.
			if ( strpos( $src, $site_url ) !== false || ! $src ) {
				continue;
			}

			// If CAOS is active, let's ignore any files related to Google Analytics.
			if (
				function_exists( 'caos_init' ) &&
				( strpos( $src, 'google-analytics.com' ) !== false || strpos( $src, 'googletagmanager.com' ) !== false )
			) {
				continue;
			}

			$external_js[ $i ]['href'] = $src;

			if ( strpos( $src, '?' ) !== false ) {
				$parsed_url = wp_parse_url( $src );
				$src        = $parsed_url['path'];
			}

			$external_js[ $i ]['name'] = basename( $src );
			$i++;
		}

		return $external_js;
	}

	/**
	 * Processes the found external requests in $html. Download files and update DB when needed.
	 * 
	 * @since v1.2.0
	 * 
	 * @param array  $requests { 'css' => int { 'name' => string, 'href' => string }, 'js' => int { 'name' => string, 'href' => string } }
	 * @param string $html     Valid HTML 
	 * 
	 * @return string Valid HTML 
	 * 
	 * @throws SodiumException 
	 * @throws SodiumException 
	 * @throws SodiumException 
	 * @throws SodiumException 
	 * @throws SodiumException 
	 */
	private function process_requests( $requests, $html ) {
		$download  = new Download();
		$added_new = false;

		foreach ( $requests as $type => $type_requests ) {
			foreach ( $type_requests as $request ) {
				if ( Helper::is_excluded( $type, $request['href'] ) ) {
					continue;
				}

				if ( Helper::is_google_fonts_request( $request['href'] ) ) {
					$local_url = Helper::get_local_url_google_font( $request['name'] );
					$local_dir = Helper::get_local_path_google_font( $request['name'] );
				} else {
					$local_url = Helper::get_local_url( $request['href'], $type );
					$local_dir = Helper::get_local_path( $request['href'], $type );
				}

				/**
				 * If it doesn't exist, download it.
				 */
				if ( ! file_exists( $local_dir ) ) {
					$download->download_file( $request['name'], $type, $request['href'] );

					Helper::set_local_url( $type, $request['href'] );

					$added_new = true;
				}

				$html = str_replace( $request['href'], esc_attr( $local_url ), $html );
			}
		}

		if ( $added_new ) {
			Helper::set_local_url( '', '', true );
		}

		return $html;
	}

	/**
	 * Return the basename, unless it's a Google Fonts URL.
	 * 
	 * @param string $url 
	 * 
	 * @return string 
	 */
	private function generate_file_name( $url ) {
		if ( ! Helper::is_google_fonts_request( $url ) ) {
			return basename( $url );
		}

		$parts = wp_parse_url( $url );

		if ( $parts['path'] === '/css2' ) {
			return $this->google_fonts_css2_filename( $parts['query'] );
		} else {
			return $this->google_fonts_filename( $parts['query'] );
		}
	}

	/**
	 * Generate a readable filename from a Google Fonts API v2 request.
	 * 
	 * @param string $query 
	 * 
	 * @return string limited to 30 chars
	 */
	private function google_fonts_css2_filename( $query ) {
		preg_match_all( '/family=(?P<font_family>.*?)[&:]/', $query, $families );

		if ( ! isset( $families['font_family'] ) ) {
			return 'google-fonts-css';
		}

		$font_families = [];

		foreach ( $families['font_family'] as $font_family ) {
			$font_families[] = str_replace( [ '+', ' ' ], '-', $font_family );
		}

		$filename = strtolower( implode( '-', $font_families ) );

		return substr( $filename, 0, 30 );
	}

	/**
	 * Generate a readable filename from a Google Fonts API request.
	 * 
	 * @param string $query
	 * 
	 * @return string limited to 30 chars long.
	 */
	private function google_fonts_filename( $query ) {
		parse_str( $query, $parts );

		if ( ! isset( $parts['family'] ) ) {
			// Let's just do a default name, assuming this won't be in use at all.
			return 'google-fonts-css';
		}

		$families = explode( '|', $parts['family'] );
		$filename = '';
		$max      = count( $families );

		foreach ( $families as $i => $family ) {
			list($family_name) = explode( ':', $family );

			$filename .= strtolower( $family_name );

			if ( ++$i < $max ) {
				$filename .= '-';
			}
		}

		return substr( $filename, 0, 30 );
	}
}
