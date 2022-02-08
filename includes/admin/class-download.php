<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Download
{
    /** @var string $settings_page */
    private $settings_page = '';

    /** @var string $settings_tab */
    private $settings_tab = '';

    /** @var bool $settings_updated */
    private $settings_updated = false;

    /** @var string $cache_dir */
    private $cache_dir = '';

    /**
     * Set Fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->settings_page    = $_GET['page'] ?? '';
        $this->settings_tab     = $_GET['tab'] ?? Gdpress_Admin_Settings::GDPRESS_ADMIN_SECTION_MANAGE;
        $this->settings_updated = isset($_GET['settings-updated']);
        $this->cache_dir        = WP_CONTENT_DIR . GDPRESS_CACHE_DIR;

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

        if (!file_exists($this->cache_dir)) {
            wp_mkdir_p($this->cache_dir);
        }

        foreach (Gdpress::requests() as $type => $requests) {
            foreach ($requests as $request) {
                if (Gdpress::is_excluded($type, $request['href'])) {
                    continue;
                }

                $url = $this->download_file($type, $request['name'], $request['href']);

                Gdpress::set_local_url($type, $url);
            }
        }

        Gdpress::set_local_url('', '', true);
    }

    /**
     * 
     */
    private function download_file($type, $filename, $url)
    {
        $subfolder = str_replace('.', '-', parse_url($url)['host']);
        $file_path = $this->cache_dir . "/$type/$subfolder/$filename";
        $file_url  = urlencode(content_url(GDPRESS_CACHE_DIR . "/$type/$subfolder/$filename"));

        if (file_exists($file_path)) {
            return $file_url;
        }

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            /** @var WP_Error $tmp */
            Gdpress_Admin_Notice::set_notice(sprintf(__('Ouch! Gdpress encountered an error while downloading <code>%s</code>', $this->plugin_text_domain), $filename) . ': ' . $tmp->get_error_message(), 'gdpress-download-failed', false, 'error', $tmp->get_error_code());

            return '';
        }

        /** @var string $tmp */
        copy($tmp, $file_path);
        @unlink($tmp);

        return $file_url;
    }
}
