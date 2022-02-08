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

        foreach (Gdpress::requests() as $type => $requests) {
            foreach ($requests as $request) {
                if (Gdpress::is_excluded($type, $request['href'])) {
                    continue;
                }

                $url = $this->download_file($request['name'], $type, $request['href']);

                Gdpress::set_local_url($type, $url);
            }
        }

        /**
         * Write everything to the database.
         */
        Gdpress::set_local_url('', '', true);
    }

    /**
     * Downloade $filename from $url.
     * 
     * @param mixed $type 
     * @param mixed $filename 
     * @param mixed $url 
     * @return string 
     * @throws SodiumException 
     */
    private function download_file($filename, $type, $url)
    {
        $file_path = Gdpress::get_local_path($url, $type);
        $file_url  = Gdpress::get_local_url($url, $type, true);

        if (file_exists($file_path)) {
            return $file_url;
        }

        /**
         * Create dir for file recursively.
         */
        wp_mkdir_p(str_replace($filename, '', $file_path));

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
