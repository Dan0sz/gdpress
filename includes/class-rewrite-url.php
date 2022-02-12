<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 * 
 * This class uses template_redirect's output to rewrite URLs in the page source.
 */
class Gdpress_RewriteUrl
{
    /**
     * @var array $page_builders Array of keys set by page builders when they're displaying their previews.
     */
    private $page_builders = [
        'bt-beaverbuildertheme',
        'ct_builder',
        'elementor-preview',
        'et_fb',
        'fb-edit',
        'fl_builder',
        'siteorigin_panels_live_editor',
        'tve',
        'vc_action'
    ];

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
        add_filter('gdpress_buffer_output', [$this, 'rewrite_urls']);
        // Autoptimize at 2. OMGF and CAOS Compatibility Mode run at 3.
        add_action('template_redirect', [$this, 'maybe_buffer_output'], 4);
    }

    /**
     * Start output buffer.
     * 
     * @return void 
     */
    public function maybe_buffer_output()
    {
        $start = true;

        /**
         * Make sure Page Builder previews don't get optimized content.
         */
        foreach ($this->page_builders as $page_builder) {
            if (array_key_exists($page_builder, $_GET)) {
                $start = false;
                break;
            }
        }

        /**
         * Customizer previews shouldn't get optimized content.
         */
        if (function_exists('is_customize_preview')) {
            $start = !is_customize_preview();
        }

        /**
         * Let's GO!
         */
        if ($start) {
            ob_start([$this, 'return_buffer']);
        }
    }

    /**
     * @action template_redirect
     *  
     * @since v4.3.1 Tested with:
     *               - Autoptimize v2.9.5.1:
     *                 - CSS/JS/Page Optimization: On
     *               - Cache Enabler v1.8.7:
     *                 - Default Settings
     *               - W3 Total Cache v2.2.1:
     *                 - Page Cache: Disk (basic)
     *                 - Database/Object Cache: Off
     *                 - JS/CSS minify/combine: On
     *               - WP Fastest Cache v0.9.9:
     *                 - JS/CSS minify/combine: On
     *                 - Page Cache: On
     *               - WP Rocket v3.8.8:
     *                 - Page Cache: Enabled
     *                 - JS/CSS minify/combine: Enabled
     *               - WP Super Cache v1.7.4
     *                 - Page Cache: Enabled
     *  
     * @return string $html
     */
    public function return_buffer($html)
    {
        if (!$html) {
            return $html;
        }

        return apply_filters('gdpress_buffer_output', $html);
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

                $html = str_replace($request['href'], esc_attr($local_url), $html);
            }
        }

        return $html;
    }
}
