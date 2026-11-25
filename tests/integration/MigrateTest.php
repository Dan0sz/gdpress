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
		delete_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL );
		delete_option( Settings::GDPRESS_OPTION_DB_VERSION );
		wp_clear_scheduled_hook( Migrate::DELETE_OLD_FILES_HOOK, [ (int) filemtime( GDPRESS_PLUGIN_FILE ) ] );
		
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
	 * @covers \GDPRess\Admin\Migrate::update_local_urls
	 * @covers \GDPRess\Admin\Migrate::schedule_delete_old_files
	 */
	public function test_local_urls_are_updated_and_cleanup_is_scheduled() {
		$url = 'https://cdn.example.net/script.js';
		
		update_option( Settings::GDPRESS_MANAGE_SETTING_REQUESTS, [ 'js' => [ [ 'href' => $url, 'name' => 'script.js' ] ] ] );
		update_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL, [ 'js' => [ content_url( GDPRESS_CACHE_DIR . '/js/script.js' ) ] ] );
		
		( new Migrate() )->maybe_migrate();
		
		$this->assertSame( [ 'js' => [ content_url( GDPRESS_CACHE_DIR . '/js/cdn.example.net/script.js' ) ] ], get_option( Settings::GDPRESS_MANAGE_SETTING_LOCAL ) );
		$this->assertNotFalse( wp_next_scheduled( Migrate::DELETE_OLD_FILES_HOOK, [ (int) filemtime( GDPRESS_PLUGIN_FILE ) ] ) );
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
		touch( $new, $now );
		
		( new Migrate() )->delete_old_files( $now );
		
		$this->assertDirectoryDoesNotExist( dirname( $old ) );
		$this->assertFileExists( $new );
		$this->assertFileExists( $outside );
		
		unlink( $new );
		rmdir( dirname( $new ) );
		unlink( $outside );
	}
}
