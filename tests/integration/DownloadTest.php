<?php
/**
 * Integration tests for GDPRess\Download
 *
 * @package GDPRess
 * @author  Daan van den Bergh
 */

namespace GDPRess\Tests\Integration;

use GDPRess\Download;
use GDPRess\Tests\TestCase;
use ReflectionMethod;

class DownloadTest extends TestCase {
	/** @var Download */
	private $download;
	
	public function setUp(): void {
		$this->download = new Download();
	}
	
	/**
	 * @covers \GDPRess\Download::get_abs_url
	 */
	public function test_get_abs_url() {
		$method = new ReflectionMethod( Download::class, 'get_abs_url' );
		$method->setAccessible( true );
		
		foreach ( $this->get_abs_url_provider() as $name => $data ) {
			$this->assertEquals( $data[2], $method->invoke( $this->download, $data[0], $data[1] ), "Failed test: $name" );
		}
	}
	
	public function get_abs_url_provider() {
		return [
			'root-relative'                           => [
				'/fonts/font.woff2',
				'https://example.com/css/style.css',
				'https://example.com/fonts/font.woff2',
			],
			'root-relative with port'                 => [
				'/fonts/font.woff2',
				'http://localhost:8080/css/style.css',
				'http://localhost:8080/fonts/font.woff2',
			],
			'directory-relative'                      => [
				'font.woff2',
				'https://example.com/css/style.css',
				'https://example.com/css/font.woff2',
			],
			'directory-relative with ./'              => [
				'./font.woff2',
				'https://example.com/css/style.css',
				'https://example.com/css/font.woff2',
			],
			'parent-relative (1 level)'               => [
				'../fonts/font.woff2',
				'https://example.com/css/style.css',
				'https://example.com/fonts/font.woff2',
			],
			'parent-relative (2 levels)'              => [
				'../../fonts/font.woff2',
				'https://example.com/wp-content/themes/theme/assets/css/style.css',
				'https://example.com/wp-content/themes/theme/fonts/font.woff2',
			],
			'parent-relative (multiple levels)'       => [
				'../../../fonts/font.woff2',
				'https://example.com/a/b/c/d/style.css',
				'https://example.com/a/fonts/font.woff2',
			],
			'parent-relative beyond root'             => [
				'../../../../../../fonts/font.woff2',
				'https://example.com/a/style.css',
				'https://example.com/fonts/font.woff2',
			],
			'directory-relative from root'            => [
				'font.woff2',
				'https://example.com/style.css',
				'https://example.com/font.woff2',
			],
			'directory-relative from root with slash' => [
				'font.woff2',
				'https://example.com/',
				'https://example.com/font.woff2',
			],
			'protocol-relative source'                => [
				'/fonts/font.woff2',
				'//example.com/css/style.css',
				'http://example.org/fonts/font.woff2',
			],
		];
	}
	
	/**
	 * @covers \GDPRess\Download::is_rel_url
	 */
	public function test_is_rel_url() {
		$method = new ReflectionMethod( Download::class, 'is_rel_url' );
		$method->setAccessible( true );
		
		foreach ( $this->is_rel_url_provider() as $name => $data ) {
			$this->assertEquals( $data[1], $method->invoke( $this->download, $data[0] ), "Failed test: $name" );
		}
	}
	
	public function is_rel_url_provider() {
		return [
			'absolute https'          => [ 'https://example.com/font.woff2', false ],
			'absolute http'           => [ 'http://example.com/font.woff2', false ],
			'protocol-relative'       => [ '//example.com/font.woff2', false ],
			'data URI'                => [ 'data:application/font-woff2;base64,AAA', false ],
			'root-relative'           => [ '/fonts/font.woff2', true ],
			'directory-relative'      => [ 'fonts/font.woff2', true ],
			'parent-relative'         => [ '../fonts/font.woff2', true ],
			'current-folder-relative' => [ './font.woff2', true ],
		];
	}
}
