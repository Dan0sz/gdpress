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
         */
        $site_url = get_site_url();
        $response = wp_remote_get($site_url);

        if (is_wp_error($response)) {
            return false;
        }

        $html     = wp_remote_retrieve_body($response);
        $document = new DOMDocument();
        @$document->loadHTML($html);

        $stylesheets       = $document->getElementsByTagName('link');
        $external_css      = [];
        $external_requests = [];
        $i                 = 0;

        foreach ($stylesheets as $stylesheet) {
            $rel  = $stylesheet->getAttribute('rel');

            if ($rel != 'stylesheet' && $rel != 'preload') {
                continue;
            }

            $href = $stylesheet->getAttribute('href');

            // If the resource is already locally loaded or it's an inline style block, move along.
            if (strpos($href, $site_url) !== false || !$href) {
                continue;
            }

            $external_css[$i]['href'] = $href;

            if (strpos($href, '?') !== false && !Gdpress::is_google_fonts_request($href)) {
                $parsed_url = parse_url($href);
                $href       = $parsed_url['path'];
            }

            $external_css[$i]['name'] = $this->generate_file_name($href);
            $i++;
        }

        $scripts     = $document->getElementsByTagName('script');
        $external_js = [];
        $i           = 0;

        foreach ($scripts as $script) {
            $href = $script->getAttribute('src');

            // If the resource is already locally loaded or it's an inline script block, move along.
            if (strpos($href, $site_url) !== false || !$href) {
                continue;
            }

            $external_js[$i]['href'] = $href;

            if (strpos($href, '?') !== false) {
                $parsed_url = parse_url($href);
                $href       = $parsed_url['path'];
            }

            $external_js[$i]['name'] = basename($href);
            $i++;
        }

        if (!empty($external_css)) {
            $external_requests['css'] = $external_css;
        }

        if (!empty($external_js)) {
            $external_requests['js'] = $external_js;
        }

        update_option(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS, $external_requests);

        wp_send_json_success();
    }

    /**
     * Return the basename, unless it's a Google Fonts URL.
     * 
     * @param mixed $url 
     * @return string|void 
     */
    private function generate_file_name($url)
    {
        if (!Gdpress::is_google_fonts_request($url)) {
            return basename($url);
        }

        $parts = parse_url($url);

        if ($parts['path'] == '/css2') {
            return $this->google_fonts_css2_filename($parts['query']);
        } else {
            return $this->google_fonts_filename($parts['query']);
        }
    }

    /**
     * Generate a readable filename from a Google Fonts API v2 request.
     * 
     * @param mixed $query 
     * @return string limited to 30 chars
     */
    private function google_fonts_css2_filename($query)
    {
        preg_match_all('/family=(?P<font_family>.*?)[&:]/', $query, $families);

        if (!isset($families['font_family'])) {
            return 'google-fonts-css';
        }

        foreach ($families['font_family'] as $font_family) {
            $font_families[] = str_replace(['+', ' '], '-', $font_family);
        }

        $filename = strtolower(implode('-', $font_families));

        return substr($filename, 0, 30);
    }

    /**
     * Generate a readable filename from a Google Fonts API request.
     * 
     * @param mixed $query
     * @return string limited to 30 chars long.
     */
    private function google_fonts_filename($query)
    {
        parse_str($query, $parts);

        if (!isset($parts['family'])) {
            // Let's just do a default name, assuming this won't be in use at all.
            return 'google-fonts-css';
        }

        $families = explode('|', $parts['family']);
        $filename = '';
        $max      = count($families);

        foreach ($families as $i => $family) {
            list($family_name) = explode(':', $family);

            $filename .= strtolower($family_name);

            if (++$i < $max) {
                $filename .= '-';
            }
        }

        return substr($filename, 0, 30);
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

        $set_path      = GDPRESS_CACHE_ABSPATH;
        $resolved_path = realpath(GDPRESS_CACHE_ABSPATH);

        if ($resolved_path != $set_path) {
            wp_die(__('Attempted path traversal detected. Sorry, no script kiddies allowed!', 'gdpr-press'));
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
