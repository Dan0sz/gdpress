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
