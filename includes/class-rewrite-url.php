<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 * 
 * This classes use an alternative method to rewrite URLs in the page source. It has been
 * tested with the following page cache and JS/CSS optimization plugins:
 * 
 * Tests Passed:
 * * Autoptimize (everything on)
 * * WP Fastest Cache (all free options on)
 * * WP Rocket (all options on)
 * * W3 Total Cache
 *   - Page Cache: Disk (basic)
 *   - Database Cache: None
 *   - Object Cache: None
 *   - Browser Cache: Disabled
 *   - Lazy Load: Enabled
 * 
 * Tests Failed:
 * * WP Optimize
 */
class Gdpress_RewriteUrl
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
     * Filters & Actions
     * 
     * @return void 
     */
    private function init()
    {
        add_action('init', function () {
            ob_start();
        });
        add_action('shutdown', [$this, 'retrieve_html'], 0);
        add_filter('gdpress_output', [$this, 'rewrite_urls']);
    }

    /**
     * Rewrite all external URLs in $html.
     * 
     * @param mixed $html 
     * @return mixed 
     */
    public function rewrite_urls($html)
    {
        $external_urls = Gdpress::requests();

        foreach ($external_urls as $type => $requests) {
            foreach ($requests as $request) {
                if (Gdpress::is_excluded($type, $request['href'])) {
                    continue;
                }

                $local_url = Gdpress::get_local_url($request['href'], $type);
                $local_dir = str_replace(content_url(), WP_CONTENT_DIR, $local_url);

                if (!file_exists($local_dir)) {
                    continue;
                }

                $html = str_replace($request['href'], $local_url, $html);
            }
        }

        return $html;
    }

    /**
     * Fetches the entire buffer triggered at runtime.
     * 
     * @return void 
     */
    public function retrieve_html()
    {
        $output = '';
        $level  = ob_get_level();

        for ($i = 0; $i < $level; $i++) {
            $output .= ob_get_clean();
        }

        echo apply_filters('gdpress_output', $output);
    }
}
