<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Download
{
    const WOFF2_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0';

    /** @var string $settings_page */
    private $settings_page = '';

    /** @var string $settings_tab */
    private $settings_tab = '';

    /** @var bool $settings_updated */
    private $settings_updated = false;

    /** @var WP_Filesystem $filesystem */
    private $fs;

    /**
     * Set Fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->settings_page    = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        $this->settings_tab     = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : Gdpress_Admin_Settings::GDPRESS_ADMIN_SECTION_MANAGE;
        $this->settings_updated = isset($_GET['settings-updated']);
        $this->fs               = $this->filesystem();

        $this->maybe_download();
    }

    /**
     * Filters & Hooks.
     * 
     * @return void 
     */
    private function maybe_download()
    {
        if (Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE != $this->settings_page) {
            return;
        }

        if (Gdpress_Admin_Settings::GDPRESS_ADMIN_SECTION_MANAGE != $this->settings_tab) {
            return;
        }

        if (!$this->settings_updated) {
            return;
        }

        $this->download();

        // Clear default 'Settings saved.' message.
        delete_transient('settings_errors');

        // Set our own.
        add_settings_error('general', 'settings_updated', __('Selected files downloaded successfully.', 'gdpr-press'), 'success');
    }

    /**
     * Download all not excluded files. We don't have to check if $requests actually exists, because 
     * the submit button is only made available when it does.
     * 
     * @return void 
     */
    private function download()
    {
        if (!function_exists('download_url')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }

        $downloader = new Gdpress_Download();

        foreach (Gdpress::requests() as $type => $requests) {
            foreach ($requests as $request) {
                if (Gdpress::is_excluded($type, $request['href'])) {
                    continue;
                }

                $url = $downloader->download_file($request['name'], $type, $request['href']);

                Gdpress::set_local_url($type, $url);
            }
        }

        /**
         * Write everything to the database.
         */
        Gdpress::set_local_url('', '', true);
    }
}
