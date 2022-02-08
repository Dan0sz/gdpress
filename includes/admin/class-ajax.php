<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Ajax
{
    /** @var string $text_domain */
    private $text_domain = 'gdpr-press';

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
            wp_die(__("Sorry, you're not allowed to do this.", $this->text_domain));
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
        $document->loadHTML($html);

        $stylesheets  = $document->getElementsByTagName('link');
        $external_css = [];
        $i            = 0;

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

            if (strpos($href, '?') !== false) {
                $parsed_url = parse_url($href);
                $href       = $parsed_url['path'];
            }

            $external_css[$i]['name'] = basename($href);
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

        update_option('gdpress_detected_external_requests', $external_requests);

        wp_send_json_success();
    }
}
