<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin\Settings;


class Help extends Builder {

    public function __construct() {
        $this->init();
    }

    private function init() {
        // Content
        add_filter( 'gdpress_help_content', [ $this, 'content' ], 20 );
    }

    public function content() {
        $tweet_url = add_query_arg(
                [
                        'text'     => sprintf(
                                _x( 'I am using %s for `@WordPress`! Eliminate external requests and increase GDPR compliance. Try it for yourself:', 'Promotional Tweet text on Help tab in Settings screen', 'gdpr-press' ),
                                wp_strip_all_tags( (string) apply_filters( 'gdpress_settings_page_title', 'GDPRess' ) )
                        ),
                        'via'      => 'Dan0sz',
                        'hashtags' => 'GDPR,Privacy,WordPress',
                        'url'      => esc_url_raw( (string) apply_filters( 'gdpress_help_tab_plugin_url', 'https://wordpress.org/plugins/gdpr-press/' ) ),
                ],
                'https://twitter.com/intent/tweet'
        );
        ?>
        <div class="gdpress-help-container">
            <div class="content">
                <h2><?php echo esc_html( sprintf( __( 'Thank you for using %s!', 'gdpr-press' ), wp_strip_all_tags( apply_filters( 'gdpress_settings_page_title', 'GDPRess' ) ) ) ); ?></h2>
                <p class="about">
                    <?php echo esc_html( sprintf( __( 'Need help configuring %s? Please refer to the links below to get you started.', 'gdpr-press' ), wp_strip_all_tags( apply_filters( 'gdpress_settings_page_title', 'GDPRess' ) ) ) ); ?>
                </p>
                <div class="column-container">
                    <div class="column">
                        <h3>
                            <?php _e( 'Need Help?', 'gdpr-press' ); ?>
                        </h3>
                        <ul>
                            <li><a target="_blank" rel="noopener noreferrer"
                                   href="<?php echo esc_url( apply_filters( 'gdpress_settings_help_support_link', 'https://wordpress.org/support/plugin/gdpr-press/' ) ); ?>"><i
                                            class="dashicons dashicons-email"></i><?php echo __( 'Get Support', 'gdpr-press' ); ?></a></li>
                        </ul>
                    </div>
                    <div class="column">
                        <h3><?php echo esc_html( sprintf( __( 'Support %s & Spread the Word!', 'gdpr-press' ), wp_strip_all_tags( apply_filters( 'gdpress_settings_page_title', 'GDPRess' ) ) ) ); ?></h3>
                        <ul>
                            <li>
                                <a target="_blank" rel="noopener noreferrer"
                                   href="<?php echo esc_url( apply_filters( 'gdpress_help_tab_review_link', 'https://wordpress.org/support/plugin/gdpr-press/reviews/?rate=5#new-post' ) ); ?>">
                                    <i class="dashicons dashicons-star-filled"></i>
                                    <?php echo esc_html__( 'Write a 5-star Review or,', 'gdpr-press' ); ?>
                                </a>
                            </li>
                            <li>
                                <a target="_blank" rel="noopener noreferrer" href="<?php echo esc_url( $tweet_url ); ?>">
                                    <i class="dashicons dashicons-twitter"></i>
                                    <?php echo esc_html__( 'Tweet about it!', 'gdpr-press' ); ?>
                                </a>
                            </li>
                        </ul>
                    </div>
                    <div class="column last">
                        <h3 class="signature"><?php echo esc_html__( 'Coded with ❤️ by', 'gdpr-press' ); ?> </h3>
                        <p class="signature">
                            <a target="_blank" title="<?php echo esc_attr__( 'Visit Daan.dev', 'gdpr-press' ); ?>" href="https://daan.dev/wordpress-plugins/">
                                <img class="signature-image" alt="<?php echo esc_attr__( 'Visit Daan.dev', 'gdpr-press' ); ?>"
                                     src="<?php echo esc_url( plugin_dir_url( GDPRESS_PLUGIN_FILE ) . 'assets/images/logo-color@2x.png' ); ?>"/>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}
