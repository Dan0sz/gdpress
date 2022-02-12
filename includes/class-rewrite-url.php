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
     * @action template_redirect
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
         * Honor PageSpeed=off parameter as used by mod_pagespeed, in use by some pagebuilders,
         * 
         * @see https://www.modpagespeed.com/doc/experiment#ModPagespeed
         */
        if (array_key_exists('PageSpeed', $_GET) && 'off' === $_GET['PageSpeed']) {
            $start = false;
        }

        /**
         * WP Customizer previews shouldn't get optimized content.
         */
        if (function_exists('is_customize_preview') && is_customize_preview()) {
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
     * Wraps the buffer output into a filter after performing several checks.
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
        if (!$this->should_process($html)) {
            return $html;
        }

        return apply_filters('gdpress_buffer_output', $html);
    }

    /**
     * Check if given markup can be processed.
     *
     * @param string $content Markup.
     *
     * @return bool
     */
    public function should_process($content)
    {
        $process = true;

        if (
            // Has no HTML tag
            stripos($content, '<html') === false
            // Is XSL stylesheet
            || (stripos($content, '<xsl:stylesheet') !== false || stripos($content, '<?xml-stylesheet') !== false)
            // Is not a HTML5 Document
            || preg_match('/^<!DOCTYPE.+html>/i', ltrim($content)) === 0
        ) {
            $process = false;
        }

        return $process;
    }

    /**
     * Rewrite all external URLs in $html.
     * 
     * @filter gdpress_buffer_output
     * 
     * @param string $html 
     * 
     * @return string 
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
