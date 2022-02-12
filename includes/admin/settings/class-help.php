<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Help extends Gdpress_Admin_Settings_Builder
{
    public function __construct()
    {
        $this->title = __('Help & Support', 'gdpr-press');

        $this->init();
    }

    private function init()
    {
        // Title
        add_filter('gdpress_help_content', [$this, 'do_title'], 10);

        // Content
        add_filter('gdpress_help_content', [$this, 'content'], 20);
    }

    public function content()
    {
        $tweetUrl = sprintf("https://twitter.com/intent/tweet?text=I+am+using+%s+for+@WordPress!+Eliminate+external+requests+and+increase+GDPR+compliance.+Try+it+for+yourself:&via=Dan0sz&hashtags=GDPR,Privacy,WordPress&url=%s", str_replace(' ', '+', apply_filters('gdpress_settings_page_title', 'GDPRess')), apply_filters('gdpress_help_tab_plugin_url', 'https://wordpress.org/plugins/gdpr-press/'));
?>
        <div class="postbox">
            <div class="content">
                <h2><?php echo sprintf(__('Thank you for using %s!', $this->plugin_text_domain), apply_filters('gdpress_settings_page_title', 'GDPRess')); ?></h2>
                <p class="about">
                    <?php echo sprintf(__('Need help configuring %s? Please refer to the links below to get you started.', $this->plugin_text_domain), apply_filters('gdpress_settings_page_title', 'GDPRess')); ?>
                </p>
                <div class="column-container">
                    <div class="column">
                        <h3>
                            <?php _e('Need Help?', $this->plugin_text_domain); ?>
                        </h3>
                        <ul>
                            <li><a target="_blank" href="<?php echo apply_filters('gdpress_settings_help_support_link', 'https://wordpress.org/support/plugin/gdpr-press/'); ?>"><i class="dashicons dashicons-email"></i><?php echo __('Get Support', $this->plugin_text_domain); ?></a></li>
                        </ul>
                    </div>
                    <div class="column">
                        <h3><?php echo sprintf(__('Support %s & Spread the Word!', $this->plugin_text_domain), apply_filters('gdpress_settings_page_title', 'GDPRess')); ?></h3>
                        <ul>
                            <li><a target="_blank" href="<?php echo apply_filters('gdpress_help_tab_review_link', 'https://wordpress.org/support/plugin/gdpr-press/reviews/?rate=5#new-post'); ?>"><i class="dashicons dashicons-star-filled"></i><?php echo __('Write a 5-star Review or,', $this->plugin_text_domain); ?></a></li>
                            <li><a target="_blank" href="<?php echo $tweetUrl; ?>"><i class="dashicons dashicons-twitter"></i><?php echo __('Tweet about it!', $this->plugin_text_domain); ?></a></li>
                        </ul>
                    </div>
                    <div class="column last">
                        <h3 class="signature"><?php echo sprintf(__('Coded with %s by', $this->plugin_text_domain), '<i class="dashicons dashicons-heart"></i>'); ?> </h3>
                        <p class="signature">
                            <a target="_blank" title="<?php echo __('Visit FFW Press', $this->plugin_text_domain); ?>" href="https://ffw.press/wordpress-plugins/"><img class="signature-image" alt="<?php echo __('Visit FFW Press', $this->plugin_text_domain); ?>" src="<?php echo plugin_dir_url(GDPRESS_PLUGIN_FILE) . 'assets/images/logo-color.png'; ?>" /></a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        </div>
<?php
    }
}
