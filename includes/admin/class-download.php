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

    /** @var WP_Filesystem $filesystem */
    private $fs;

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
        $this->fs               = $this->filesystem();

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

        $tmp = $this->download_to_tmp(str_replace($filename, '', $file_path), $url);

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
     */
    private function download_to_tmp($path, $url)
    {
        wp_mkdir_p($path);

        // Is relative protocol?
        if (strpos($url, '//') === 0) {
            $url = 'https:' . $url;
        }

        $tmp = download_url($url);

        if (is_wp_error($tmp)) {
            /** @var WP_Error $tmp */
            Gdpress_Admin_Notice::set_notice(sprintf(__('Ouch! Gdpress encountered an error while downloading <code>%s</code>', $this->plugin_text_domain), $filename) . ': ' . $tmp->get_error_message(), 'gdpress-download-failed', false, 'error', $tmp->get_error_code());

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
        $contents = $this->fs->get_contents($file);

        if (strpos($contents, '@font-face') === false) {
            return false;
        }

        preg_match_all('/@font-face\s*{([\s\S]*?)}/', $contents, $font_faces);

        /**
         * Let's assume $font_faces[0] exists. We already checked if the stylesheet contains font faces, 
         * so if the Regex didn't find any, then that's a bug and I'd like to know about it.
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

        return $this->fs->put_contents($file, $contents);
    }

    /**
     * Checks if $url begins with '../' or doesn't begin with either 'http', '../' or '/'.
     * @param mixed $url 
     * @return bool 
     */
    private function is_rel_url($url)
    {
        return strpos($url, '../') === 0 || (strpos($url, 'http') === false && strpos($url, '../') === false && strpos($url, '/') > 0);
    }

    /**
     * @param mixed $relative_url 
     * @param mixed $url 
     * @return void 
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
        $local_url = content_url(GDPRESS_CACHE_DIR . $parts['path']);

        return str_replace($path, $local_url, $contents);
    }

    /**
     * Gets filesystem instance.
     * 
     * @return WP_Filesystem_Base 
     */
    private function filesystem()
    {
        global $wp_filesystem;

        if (is_null($wp_filesystem)) {
            require_once ABSPATH . '/wp-admin/includes/file.php';
            WP_Filesystem();
        }

        return $wp_filesystem;
    }
}
