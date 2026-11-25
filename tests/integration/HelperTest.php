<?php
/**
 * Integration tests for GDPRess\Helper
 *
 * @package GDPRess
 * @author  Daan van den Bergh
 */

namespace GDPRess\Tests\Integration;

use GDPRess\Helper;
use GDPRess\Tests\TestCase;

class HelperTest extends TestCase {
	/**
	 * @covers \GDPRess\Helper::is_bunny_fonts_request
	 * @covers \GDPRess\Helper::is_excluded
	 */
	public function test_bunny_fonts_are_excluded() {
		foreach ( [ 'https://fonts.bunny.net/css?family=roboto:400', 'https://FONTS.BUNNY.NET/css?family=inter:400' ] as $url ) {
			$this->assertTrue( Helper::is_bunny_fonts_request( $url ) );
			$this->assertTrue( Helper::is_excluded( 'css', $url ) );
		}
	}
	
	/**
	 * @covers \GDPRess\Helper::get_local_path
	 * @covers \GDPRess\Helper::get_local_url
	 */
	public function test_local_copies_are_stored_by_host() {
		$paths = [
			'https://cdn.example.net/a/widgets.js?ver=1' => '/js/cdn.example.net/a/widgets.js',
			'//CDN.example.com/a/widgets.js'             => '/js/cdn.example.com/a/widgets.js',
			'https://cdn.example.net:8080/a/widgets.js'  => '/js/cdn.example.net-8080/a/widgets.js',
			'https://cdn.example.net/../../widgets.js'   => '/js/cdn.example.net/widgets.js',
		];
		
		foreach ( $paths as $url => $path ) {
			$this->assertSame( wp_normalize_path( GDPRESS_CACHE_ABSPATH . $path ), Helper::get_local_path( $url, 'js' ) );
			$this->assertSame( content_url( GDPRESS_CACHE_DIR . $path ), Helper::get_local_url( $url, 'js', true ) );
		}
		
		$this->assertSame( '', Helper::get_local_path( 'https://cdn.example.net?file=1', 'js' ) );
	}
	
	/**
	 * @covers \GDPRess\Helper::get_request_id
	 */
	public function test_variants_share_an_id() {
		$variants = [
			'https://cdn.example.net/sdk/js',
			'http://cdn.example.net/sdk/js?ver=1',
			'//CDN.example.net/sdk/js?ver=1&#038;list=a%2Cb',
			'https://cdn.example.net/sdk/js?ver=1&amp;list=a,b#hash',
		];
		
		foreach ( $variants as $url ) {
			$this->assertSame( 'cdn.example.net/sdk/js', Helper::get_request_id( $url ) );
		}
	}
	
	/**
	 * @covers \GDPRess\Helper::get_request_id
	 */
	public function test_different_files_have_different_ids() {
		$urls = [
			'https://cdn.example.net/widgets.js',
			'https://cdn.example.com/widgets.js',
			'https://cdn.example.net:8080/widgets.js',
			'https://cdn.example.net/v2/widgets.js',
			'https://cdn.example.net?file=1',
			'https://cdn.example.net?file=2',
			'https://fonts.googleapis.com/css?family=Poppins',
			'https://fonts.googleapis.com/css?family=Roboto',
		];
		
		$this->assertCount( count( $urls ), array_unique( array_map( [ Helper::class, 'get_request_id' ], $urls ) ) );
	}
}
