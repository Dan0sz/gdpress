<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Download
{
    const WOFF2_USER_AGENT = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:97.0) Gecko/20100101 Firefox/97.0';

    /**
     * Download $filename from $url.
     * 
     * @param string $type The type of file to download.
     * @param string $filename The name of the file to download.
     * @param string $url The url of the file to download.
     * 
     * @return string 
     * 
     * @throws SodiumException 
     */
    public function download_file($filename, $type, $url)
    {
        if (Gdpress::is_google_fonts_request($url)) {
            $file_path = Gdpress::get_local_path_google_font($filename);
            $file_url  = Gdpress::get_local_url_google_font($filename, true);
        } else {
            $file_path = Gdpress::get_local_path($url, $type);
            $file_url  = Gdpress::get_local_url($url, $type, true);
        }

        if (file_exists($file_path)) {
            return $file_url;
        }

        if (Gdpress::is_google_fonts_request($url)) {
            $path = str_replace('google-fonts.css', '', $file_path);
        } else {
            $path = str_replace($filename, '', $file_path);
        }

        $tmp = $this->download_to_tmp($path, $url);

        if (!$tmp) {
            return $file_url;
        }

        if ($type == 'css') {
            $this->parse_font_faces($tmp, $url);
        }

        /** @var string $tmp */
        copy($tmp, $file_path);
        @unlink($tmp);

        return $file_url;
    }

    /**
     * Downloads file to temporary storage and creates directories recursively where necessary.
     *
     * @param string $path The path to create.
     * @param string $url The URL of the file to download.
     * 
     * @return string|WP_Error 
     * 
     * @throws SodiumException 
     * @throws SodiumException 
     */
    private function download_to_tmp($path, $url)
    {
        wp_mkdir_p($path);

        // Is relative protocol?
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        /**
         * We set this user agent to retrieve WOFF2 files from the Google Fonts API.
         * 
         * @since v1.1.0 This doesn't affect other requests at all.
         */
        add_filter('http_headers_useragent', function () {
            return self::WOFF2_USER_AGENT;
        });

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            /** @var WP_Error $tmp */
            Gdpress_Admin_Notice::set_notice(sprintf(__('Ouch! Gdpress encountered an error while downloading <code>%s</code>', 'gdpr-press'), basename($url)) . ': ' . $tmp->get_error_message(), 'error', 'all', 'gdpress-download-failed');

            return '';
        }

        return $tmp;
    }

    /**
     * Manipulates $file's embedded font faces.
     * 
     * @param mixed $file 
     * @return void 
     */
    private function parse_font_faces($file, $ext_url)
    {
        $contents = file_get_contents($file);

        if (strpos($contents, '@font-face') === false) {
            return false;
        }

        preg_match_all('/@font-face\s*{([\s\S]*?)}/', $contents, $font_faces);

        /**
         * Let's assume $font_faces[0] exists. We already checked if the stylesheet contains font faces, 
         * so if the Regex didn't find any, then that's a bug in the regex and I'd like to know about it.
         */
        $font_faces = $font_faces[0];

        /**
         * Parse each @font-face statement for src url's.
         */
        foreach ($font_faces as $font_face) {
            preg_match_all('/url\([\'"]?(?P<urls>.+?)[\'"]?\)/', $font_face, $urls);

            $urls = $urls['urls'] ?? [];

            /**
             * Download each file (defined as @font-face src) to the appropriate dir.
             */
            foreach ($urls as $url) {
                // Save a copy of $is_rel_url for later down the road.
                if ($is_rel_url = $this->is_rel_url($url)) {
                    $url = $this->get_abs_url($url, $ext_url);
                }

                list($filename) = explode('?', basename($url));
                $dir            = str_replace($filename, '', $url);
                $path           = Gdpress::get_local_path($dir, 'css');

                $tmp = $this->download_to_tmp($path, $url);

                if (!$tmp) {
                    continue;
                }

                /**
                 * If absolute URLs are used for this @font-face statement, rewrite
                 * $contents to use local cache dir.
                 */
                if (!$is_rel_url) {
                    $contents = $this->replace_abs_urls($contents, $dir);
                }

                /**
                 * Copy font file.
                 */
                copy($tmp, $path . $filename);
                @unlink($tmp);
            }
        }

        return file_put_contents($file, $contents);
    }

    /**
     * Checks if $source contain mentions of '../' or doesn't begin with either 'http', '../' or '/'.
     * 
     * @param string $source 
     * @return bool  false || true for e.g. "../fonts/file.woff2" or "/fonts/file.woff2" or "fonts/file.woff2"
     */
    private function is_rel_url(string $source)
    {
        // true: ../fonts/file.woff2
        return strpos($source, '../') === 0
            // true: /fonts/file.woff2 (checks for relative protocols, i.e. '//')
            || (strpos($source, 'http') === false && strpos($source, '../') === false && strpos($source, '//') === false && strpos($source, '/') === 0)
            // true: fonts/file.woff2
            || (strpos($source, 'http') === false && strpos($source, '../') === false && strpos($source, '/') > 0)
            // true: file.woff2
            || (strpos($source, 'http') === false && strpos($source, '../') === false && strpos($source, '/') === false && preg_match('/^[a-zA-Z]/', $source) === 1);
    }

    /**
     * @param string $rel_url Relative URL to rewrite, e.g. '/fonts/file.woff2'
     * @param string $url     URL to be used for rewriting relative URL to an absolute URL.
     *  
     * @return string Absolute URL
     */
    private function get_abs_url($rel_url, $source)
    {
        $folder_depth  = substr_count($rel_url, '../');
        $url_to_insert = $source;

        /**
         * Remove everything after the last occurence of a forward slash ('/');
         * 
         * $i = 0: Filename
         *      1: First level subdirectory, i.e. '../'
         *      2: 2nd level subdirectory, i.e. '../../'
         *      3: Etc.
         */
        for ($i = 0; $i <= $folder_depth; $i++) {
            $url_to_insert = substr($source, 0, strrpos($url_to_insert, '/'));
        }

        $path = ltrim($rel_url, './');
        $abs  = $url_to_insert . '/' . $path;

        return $abs;
    }

    /**
     * Parse $file contents for occurrences of host. 
     * 
     * @param string $contents 
     * @param string $path 
     * @return void 
     */
    private function replace_abs_urls($contents, $path)
    {
        $parts     = parse_url($path);
        $local_url = content_url(GDPRESS_CACHE_DIR . '/css/' . $parts['path']);

        return str_replace($path, $local_url, $contents);
    }
}
