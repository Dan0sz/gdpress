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
}
