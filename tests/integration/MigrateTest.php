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
	public function setUp(): void {
		parent::setUp();
		
		// The migration already ran while WordPress was loading.
		delete_option( Settings::GDPRESS_OPTION_DB_VERSION );
		wp_clear_scheduled_hook( Migrate::DELETE_OLD_FILES_HOOK, [ (int) filectime( GDPRESS_PLUGIN_FILE ) ] );
	}
	
	public function tearDown(): void {
		delete_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS );
		delete_option( Settings::GDPRESS_OPTION_DB_VERSION );
		wp_clear_scheduled_hook( Migrate::DELETE_OLD_FILES_HOOK, [ (int) filectime( GDPRESS_PLUGIN_FILE ) ] );
		
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
	 * @covers \GDPRess\Helper::unique_requests
	 */
	public function test_variants_are_merged() {
		$sdk   = [ 'href' => 'https://cdn.example.net/sdk/js?intent=capture&#038;funding=a%2Cb', 'name' => 'js' ];
		$other = [ 'href' => 'https://cdn.example.net/other.js', 'name' => 'other.js' ];
		$fonts = [
			[ 'href' => 'https://fonts.googleapis.com/css?family=Poppins', 'name' => 'poppins' ],
			[ 'href' => 'https://fonts.googleapis.com/css?family=Roboto', 'name' => 'roboto' ],
		];
		
		update_option(
			Settings::GDPRESS_MANAGE_SETTING_REQUESTS,
			[
				'css' => $fonts,
				'js'  => [ $sdk, [ 'href' => '//cdn.example.net/sdk/js?intent=subscription', 'name' => 'js' ], $other ],
			]
		);
		
		( new Migrate() )->maybe_migrate();
		
		$this->assertSame( [ 'css' => $fonts, 'js' => [ $sdk, $other ] ], Helper::requests( true ) );
	}
	
	/**
	 * @covers \GDPRess\Admin\Migrate::schedule_delete_old_files
	 */
	public function test_cleanup_is_scheduled() {
		wp_mkdir_p( GDPRESS_CACHE_ABSPATH . '/js' );
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [ 'js' => [ [ 'href' => 'https://cdn.example.net/script.js', 'name' => 'script.js' ] ] ] );
		update_option( 'gdpress_local_urls', [ 'js' => [ 'https://example.org/script.js' ] ] );
		
		( new Migrate() )->maybe_migrate();
		
		$this->assertFalse( get_option( 'gdpress_local_urls' ) );
		$this->assertNotFalse( wp_next_scheduled( Migrate::DELETE_OLD_FILES_HOOK, [ (int) filectime( GDPRESS_PLUGIN_FILE ) ] ) );
	}
	
	/**
	 * @covers \GDPRess\Admin\Migrate::copy_local_files
	 */
	public function test_local_files_are_copied_to_their_host() {
		$cache = GDPRESS_CACHE_ABSPATH;
		$url   = content_url( GDPRESS_CACHE_DIR . '/css' );
		$old   = [
			'/js/gdpress-one/script.js'          => 'one',
			'/js/gdpress-shared/script.js'       => 'shared',
			'/css/gdpress-one/css/style.css'     => "@font-face{src:url( '../fonts/font.woff2?v=1' )}",
			'/css/gdpress-one/fonts/font.woff2'  => 'font',
			'/css/gdpress-font/google-fonts.css' => "@font-face{src:url(https://old.example.net" . wp_parse_url( $url, PHP_URL_PATH ) . "/s/gdpress/font.woff2)}",
			'/css/s/gdpress/font.woff2'          => 'google font',
		];
		$new   = [
			'/js/cdn.example.net/gdpress-one/script.js'              => 'one',
			'/css/cdn.example.net/gdpress-one/css/style.css'         => "@font-face{src:url( '../fonts/font.woff2?v=1' )}",
			'/css/cdn.example.net/gdpress-one/fonts/font.woff2'      => 'font',
			'/css/fonts.googleapis.com/gdpress-font/google-fonts.css' => "@font-face{src:url($url/fonts.gstatic.com/s/gdpress/font.woff2)}",
			'/css/fonts.gstatic.com/s/gdpress/font.woff2'            => 'google font',
		];
		
		foreach ( $old as $path => $contents ) {
			wp_mkdir_p( dirname( $cache . $path ) );
			file_put_contents( $cache . $path, $contents );
		}
		
		update_option(
			Settings::GDPRESS_MANAGE_SETTING_REQUESTS,
			[
				'css' => [
					[ 'href' => 'https://cdn.example.net/gdpress-one/css/style.css?ver=1', 'name' => 'style.css' ],
					[ 'href' => 'https://fonts.googleapis.com/css?family=GDPRess', 'name' => 'gdpress-font' ],
				],
				'js'  => [
					[ 'href' => 'https://cdn.example.net/gdpress-one/script.js?ver=1', 'name' => 'script.js' ],
					[ 'href' => 'https://cdn.example.net/gdpress-shared/script.js', 'name' => 'script.js' ],
					[ 'href' => 'https://cdn.example.com/gdpress-shared/script.js', 'name' => 'script.js' ],
				],
			]
		);
		
		( new Migrate() )->maybe_migrate();
		
		foreach ( $new as $path => $contents ) {
			$this->assertStringEqualsFile( $cache . $path, $contents );
		}
		
		$this->assertFileDoesNotExist( $cache . '/js/cdn.example.net/gdpress-shared/script.js' );
		$this->assertFileDoesNotExist( $cache . '/js/cdn.example.com/gdpress-shared/script.js' );
		
		foreach ( array_keys( $old + $new ) as $path ) {
			$this->assertFileExists( $cache . $path );
			unlink( $cache . $path );
		}
	}
	
	/**
	 * @covers \GDPRess\Admin\Migrate::delete_old_files
	 */
	public function test_only_old_files_are_deleted() {
		$old     = GDPRESS_CACHE_ABSPATH . '/js/gdpress-old/script.js';
		$new     = GDPRESS_CACHE_ABSPATH . '/js/gdpress.example.net/script.js';
		$outside = GDPRESS_CACHE_ABSPATH . '/gdpress-outside.txt';
		$now     = time();
		
		foreach ( [ $old, $new, $outside ] as $file ) {
			wp_mkdir_p( dirname( $file ) );
			file_put_contents( $file, 'contents' );
		}
		
		touch( $old, $now - 60 );
		touch( $outside, $now - 60 );
		touch( $new, $now - 60 );
		
		( new Migrate() )->delete_old_files( $now );
		
		$this->assertDirectoryDoesNotExist( dirname( $old ) );
		$this->assertFileExists( $new );
		$this->assertFileExists( $outside );
		
		unlink( $new );
		rmdir( dirname( $new ) );
		unlink( $outside );
	}
}
