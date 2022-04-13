<?php

use ParagonIE\Sodium\Core\Curve25519\Ge\P2;

defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Ajax
{
    /**
     * Set fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Filters & Hooks.
     * 
     * @return void 
     */
    private function init()
    {
        add_action('wp_ajax_gdpress_fetch', [$this, 'fetch']);
        add_action('wp_ajax_gdpress_flush', [$this, 'flush']);
    }

    /**
     * Basic logic for fetching external resources.
     * 
     * @return void 
     */
    public function fetch()
    {
        check_ajax_referer(Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__("Sorry, you're not allowed to do this.", 'gdpr-press'));
        }

        /**
         * Trigger a request to the frontend, so the 'template_redirect' action is initiated.
         * 
         * We're adding the gdpress parameter to ensure proper execution.
         * 
         * @see Gdpress_Rewrite_Url::init()
         */
        $site_url = get_home_url() . '?gdpress';
        $response = wp_remote_get($site_url, ['timeout' => 60]);

        if (is_wp_error($response)) {
            /**
             * Dies eventually, so no need to return.
             * 
             * @var WP_Error $response
             */
            wp_send_json_error($response->get_error_code() . ': ' . $response->get_error_message());
        }

        wp_send_json_success();
    }

    /**
     * Flush cache directory and remove DB settings.
     * 
     * @return void 
     */
    public function flush()
    {
        check_ajax_referer(Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE, 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__("Sorry, you're not allowed to do this.", 'gdpr-press'));
        }

        try {
            $entries = array_filter((array) glob(GDPRESS_CACHE_ABSPATH . '/*'));

            foreach ($entries as $entry) {
                $this->delete($entry);
            }

            $db_cleanup = [
                Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS,
                Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_LOCAL,
                Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_EXCLUDED
            ];

            foreach ($db_cleanup as $option) {
                delete_option($option);
            }

            Gdpress_Admin_Notice::set_notice(__('GDPRess Bot has successfully cleared its cache.', 'gdpr-press'));
        } catch (\Exception $e) {
            Gdpress_Admin_Notice::set_notice(
                __('GDPRess Bot could not empty the cache directory: ', 'gdpr-press') . $e->getMessage(),
                'error',
                'all',
                'gdpress-cache-flush-error'
            );
        }
    }

    /**
     * @param $entry
     */
    private function delete($entry)
    {
        if (is_dir($entry)) {
            $file = new \FilesystemIterator($entry);

            // If dir is empty, valid() returns false.
            while ($file->valid()) {
                $this->delete($file->getPathName());
                $file->next();
            }

            rmdir($entry);
        } else {
            unlink($entry);
        }
    }
}
