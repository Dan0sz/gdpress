<?php
/**
 * Integration tests for GDPRess\Admin\Migrate
 *
 * @package GDPRess
 * @author  Daan van den Bergh
 */

namespace GDPRess\Tests\Integration;

use GDPRess\Admin\Migrate;
use GDPRess\Admin\Settings;
use GDPRess\Helper;
use GDPRess\Tests\TestCase;

class MigrateTest extends TestCase {
	public function tearDown(): void {
		delete_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS );
		delete_option( Settings::GDPRESS_OPTION_DB_VERSION );
		
		parent::tearDown();
	}
	
	/**
	 * @covers \GDPRess\Admin\Migrate::maybe_migrate
	 */
	public function test_stored_local_files_are_removed_once() {
		$host     = wp_parse_url( get_home_url(), PHP_URL_HOST );
		$external = [ 'href' => 'https://cdn.example.net/script.js', 'name' => 'script.js' ];
		$requests = [
			'css' => [ [ 'href' => "http://$host/style.css", 'name' => 'style.css' ] ],
			'js'  => [ [ 'href' => "http://$host/script.js", 'name' => 'script.js' ], $external ],
		];
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, $requests );
		
		$migrate = new Migrate();
		$migrate->maybe_migrate();
		
		$this->assertSame( [ 'js' => [ $external ] ], Helper::requests( true ) );
		$this->assertSame( GDPRESS_DB_VERSION, get_option( Settings::GDPRESS_OPTION_DB_VERSION ) );
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, $requests );
		$migrate->maybe_migrate();
		
		$this->assertSame( $requests, Helper::requests( true ) );
	}
	
	/**
	 * @covers \GDPRess\Admin\Migrate::maybe_migrate
	 */
	public function test_copies_of_local_files_are_deleted() {
		$host  = wp_parse_url( get_home_url(), PHP_URL_HOST );
		$files = [
			'/css/gdpress-own/css/icons.css'     => "@font-face{src:url('../fonts/icons.woff2?v=1')} a{background:url(../../../../../outside.txt)}",
			'/css/gdpress-own/fonts/icons.woff2' => 'font',
			'/js/gdpress-own/script.js'          => 'js',
			'/js/gdpress-ext/script.js'          => 'js',
		];
		
		foreach ( $files as $path => $contents ) {
			wp_mkdir_p( dirname( GDPRESS_CACHE_ABSPATH . $path ) );
			file_put_contents( GDPRESS_CACHE_ABSPATH . $path, $contents );
		}
		
		$outside = dirname( GDPRESS_CACHE_ABSPATH ) . '/outside.txt';
		file_put_contents( $outside, 'keep' );
		
		$own_js    = "http://$host/gdpress-own/script.js?ver=1";
		$ext_js    = 'https://cdn.example.net/gdpress-ext/script.js';
		$ext_local = Helper::get_local_url( $ext_js, 'js' );
		
		update_option(
			Settings::GDPRESS_MANAGE_SETTING_REQUESTS,
			[
				'css' => [ [ 'href' => "http://$host/gdpress-own/css/icons.css?ver=1", 'name' => 'icons.css' ] ],
				'js'  => [ [ 'href' => $own_js, 'name' => 'script.js' ], [ 'href' => $ext_js, 'name' => 'script.js' ] ],
			]
		);
		update_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, [ 'js' => [ $own_js, Helper::get_local_url( $own_js, 'js' ), $ext_local ] ] );
		
		( new Migrate() )->maybe_migrate();
		
		$this->assertDirectoryDoesNotExist( GDPRESS_CACHE_ABSPATH . '/css/gdpress-own' );
		$this->assertDirectoryDoesNotExist( GDPRESS_CACHE_ABSPATH . '/js/gdpress-own' );
		$this->assertFileExists( GDPRESS_CACHE_ABSPATH . '/js/gdpress-ext/script.js' );
		$this->assertFileExists( $outside );
		$this->assertSame( [ 'js' => [ $ext_local ] ], get_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL ) );
		
		unlink( $outside );
		unlink( GDPRESS_CACHE_ABSPATH . '/js/gdpress-ext/script.js' );
		delete_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL );
	}
}
