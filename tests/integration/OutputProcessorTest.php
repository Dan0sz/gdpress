<?php
/**
 * Integration tests for GDPRess\OutputProcessor
 *
 * @package GDPRess
 * @author  Daan van den Bergh
 */

namespace GDPRess\Tests\Integration;

use GDPRess\OutputProcessor;
use GDPRess\Tests\TestCase;
use ReflectionClass;

class OutputProcessorTest extends TestCase {
	/**
	 * @covers \GDPRess\OutputProcessor::parse_scripts
	 * @covers \GDPRess\OutputProcessor::parse_stylesheets
	 * @covers \GDPRess\Helper::is_local_request
	 */
	public function test_local_files_are_not_external() {
		$host  = wp_parse_url( get_home_url(), PHP_URL_HOST );
		$local = [
			"https://$host/file",
			"http://$host/file",
			"//$host/file",
			"https://www.$host/file",
			'https://' . strtoupper( $host ) . '/file',
			'/wp-includes/file',
		];
		$ext   = [
			'https://cdn.example.net/file',
			'//cdn.example.net/file',
			"https://cdn.example.net/file?u=https://$host",
			"https://$host.example.net/file",
		];
		
		foreach ( $local as $url ) {
			$this->assertEmpty( $this->parse( $url ), "Should be local: $url" );
		}
		
		foreach ( $ext as $url ) {
			$this->assertSame( [ $url, $url ], $this->parse( $url ), "Should be external: $url" );
		}
	}
	
	/**
	 * @param string $url
	 *
	 * @return array The URLs detected as external.
	 */
	private function parse( $url ) {
		$class     = new ReflectionClass( OutputProcessor::class );
		$processor = $class->newInstanceWithoutConstructor();
		$found     = [];
		
		foreach ( [ 'parse_stylesheets' => "<link rel='stylesheet' href='$url' />", 'parse_scripts' => "<script src='$url'></script>" ] as $name => $tag ) {
			$method = $class->getMethod( $name );
			$method->setAccessible( true );
			$found = array_merge( $found, array_column( $method->invoke( $processor, [ $tag ] ), 'href' ) );
		}
		
		return $found;
	}
}
