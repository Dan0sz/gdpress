<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Rewrite
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

                $html = str_replace($request['href'], Gdpress::get_local_url($type, $request['name'], true), $html);
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
