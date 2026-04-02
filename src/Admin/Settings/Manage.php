<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin\Settings;

use GDPRess\Admin\Settings;
use GDPRess\Helper;

class Manage extends Builder {

    /** @var string $ga_notice */
    private $ga_notice;

    /** @var string $gf_notice */
    private $gf_notice;

    /** @var string $webfont_notice */
    private $webfont_notice;

    /** @var string $exclusion_notice */
    private $exclusion_notice;

    /** @var string $upsell_notice */
    private $upsell_notice;

    /** @var string $tooltip_markup */
    private $tooltip_markup = '<i class="dashicons dashicons-info-outline tooltip"><span class="tooltip-text"><span class="inline-text">%s</span></span></span></i>';

    /**
     * Set fields.
     *
     * @return void
     */
    public function __construct() {
        $this->init();
    }

    /**
     * Filters & Hooks.
     *
     * @return void
     */
    private function init() {
        add_action( 'init', [ $this, 'set_translations']);

        // Content
        add_filter( 'gdpress_manage_content', [ $this, 'manage_section' ], 2 );

        add_filter( 'gdpress_manage_content', [ $this, 'before_settings_section' ], 3);
        add_filter( 'gdpress_manage_content', [ $this, 'do_before' ], 5 );
        add_filter( 'gdpress_manage_content', [ $this, 'test_mode' ], 7 );
        add_filter( 'gdpress_manage_content', [ $this, 'do_after' ], 9 );
        add_filter( 'gdpress_manage_content', [ $this, 'after_settings_section' ]);
    }

    public function after_settings_section() {
        ?>
        </div>
        <?php
    }

    public function before_settings_section() {
        ?>
        <div class="gdpress-settings-container">
        <?php
    }

    /**
     * Add Manage section contents.
     *
     * @return void
     */
    public function manage_section() {
        ?>
        <div class="gdpress-manage-container">
            <span class="option-title"><?php echo __( 'External Requests Manager', 'gdpr-press' ); ?></span>
            <div class="gdpress-container">
                <?php if ( Helper::requests() ) : ?>
                    <?php $this->manage_screen(); ?>
                <?php else : ?>
                    <?php $this->start_screen(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function manage_screen() {
        $css_count = isset( Helper::requests()['css'] ) ? count( Helper::requests()['css'] ) : 0;
        $js_count  = isset( Helper::requests()['js'] ) ? count( Helper::requests()['js'] ) : 0;
        ?>
        <?php if ( empty( Helper::local() ) ) : ?>
            <p>
                <em><?php echo sprintf( __( 'Beep-boop! 🤖 GDPRess Bot has detected %1$s stylesheets and %2$s scripts loaded from 3rd parties. Download them to your server to increase GDPR compliance.', 'gdpr-press' ), (string) $css_count, (string) $js_count ); ?></em>
            </p>
        <?php else : ?>
            <p>
                <em><?php echo sprintf( __( 'Hurray! 🎉 GDPRess Bot has downloaded %1$s stylesheets and %2$s scripts. Kickback, relax, enjoy your (increased) GDPR compliance and <a href="%3$s" target="_blank">maybe oil my circuits with a 5 ⭐ rating</a>?', 'gdpr-press' ), count( Helper::local()['css'] ?? [] ), count( Helper::local()['js'] ?? [] ), 'https://wordpress.org/support/plugin/gdpr-press/reviews/?rate=5#new-post' ); ?></em> <?php echo sprintf( $this->tooltip_markup, 'GDPRess is a helper bot, not a legal advice bot. Please refer to your country\'s GDPR regulations and make sure you\'ve taken all necessary steps to comply.' ); ?>
            </p>
        <?php endif; ?>
        <table>
            <thead>
            <tr>
                <th class="downloaded" scope="col"><?php /** Header for Downloaded Status column */ ?></th>
                <th class="name" scope="col"><?php echo __( 'Filename', 'gdpr-press' ); ?></th>
                <th class="href" scope="col"><?php echo __( 'External URL', 'gdpr-press' ); ?></th>
                <th class="href" scope="col"><?php echo __( 'Local URL', 'gdpr-press' ); ?></th>
                <th class="exclude" scope="col"><?php echo __( 'Exclude', 'gdpr-press' ); ?></th>
            </tr>
            </thead>
            <?php foreach ( Helper::requests() as $type => $requests ) : ?>
                <tbody class="<?php echo esc_attr( $type ); ?>">
                <tr>
                    <td class="title" colspan="5">
                        <h3><?php echo esc_html( strtoupper( $type ) ); ?></h3>
                    </td>
                </tr>
                <?php foreach ( $requests as $i => $request ) : ?>
                    <?php
                    $request_type = $this->get_request_type( $request );
                    $classes      = $i % 2 ? 'even ' : '';

                    if ( $request_type === 'google_analytics' || $request_type === 'excluded' ) {
                        $classes .= 'warning';
                    } elseif ( $request_type === 'google_fonts' || $request_type === 'webfont_js' || $request_type === 'upsell' ) {
                        $classes .= 'info';
                    }

                    $local_url    = $request_type === 'google_fonts' ? Helper::get_local_url_google_font( $request['name'] ) : Helper::get_local_url( $request['href'], $type );
                    $downloaded   = $request_type === 'google_fonts' ? file_exists( Helper::get_local_path_google_font( $request['href'] ) ) : file_exists( Helper::get_local_path( $request['href'], $type ) );
                    $tooltip_text = $this->get_tooltip_text( $request_type );
                    ?>
                    <tr <?php echo "class='" . esc_attr( $classes ) . "'"; ?>>
                        <td class="downloaded"><?php echo $request_type ? sprintf( $this->tooltip_markup, wp_kses_post( $tooltip_text ) ) : ( $downloaded ? '<i class="dashicons dashicons-yes"></i>' : '' ); ?></td>
                        <th class="name" scope="row"><?php echo esc_attr( $request['name'] ); ?></th>
                        <td class="href"><span title="<?php echo esc_url( $request['href'] ); ?>"><?php echo esc_url( $request['href'] ); ?></span></td>
                        <td class="href"><span title="<?php echo esc_url( $local_url ); ?>"><?php echo esc_url( $local_url ); ?></span></td>
                        <td class="exclude">
                            <input type="checkbox" <?php echo Helper::is_excluded( $type, $request['href'] ) || $request_type === 'google_analytics' || $request_type === 'excluded' ? 'checked' : ''; ?> <?php echo $request_type === 'google_analytics' || $request_type === 'excluded' ? 'class="locked"' : ''; ?>
                                    name="<?php echo esc_attr( Settings::GDPRESS_MANAGE_SETTING_EXCLUDED ); ?>[<?php echo esc_attr( $type ); ?>][]" value="<?php echo esc_url( $request['href'] );
                                    ?>" />
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            <?php endforeach; ?>
            <tbody class="gdpress-manage-cache">
                <tr>
                    <th>
                        <?php echo esc_html__('Manage cache', 'gdpr-press') ;?>
                    </th>
                    <td>
                        <input type="button" name="button" id="gdpress-fetch" class="button" value="<?php echo __( 'Re-scan', 'gdpr-press' ); ?>" data-nonce="<?php echo wp_create_nonce( Settings::GDPRESS_ADMIN_PAGE ); ?>">
                        <a href="#" id="gdpress-flush" data-nonce="<?php echo wp_create_nonce( Settings::GDPRESS_ADMIN_PAGE ); ?>" class="gdpress-flush button button-cancel"><?php _e( 'Empty cache directory', 'gdpr-press' ); ?></a>
                    </td>
                </tr>
            </tbody>
        </table>
        <input type="hidden" name="<?php echo esc_attr( Settings::GDPRESS_MANAGE_SETTING_REQUESTS ); ?>" value="<?php echo base64_encode( serialize( Helper::requests() ) ); ?>"/>
        <?php
    }

    /**
     * @param array $request
     *
     * @return string
     */
    private function get_request_type( $request ) {
        $is_ga      = str_contains( $request['href'], 'google-analytics' ) || str_contains( $request['href'], 'googletagmanager' );
        $is_gf      = Helper::is_google_fonts_request( $request['href'] );
        $is_webfont = Helper::is_webfont_loader_request( $request['href'] );
        $is_excl    = false;
        $is_upsell  = false;

        foreach ( Helper::exclusion_list() as $pattern ) {
            if ( str_contains( $request['href'], $pattern ) ) {
                $is_excl = true;
                break;
            }
        }

        foreach ( Helper::upsell_list() as $pattern ) {
            if ( str_contains( $request['href'], $pattern ) ) {
                $is_upsell = true;
                break;
            }
        }

        // Only suggest OMGF if there's a serious number of fonts in use.
        if ( $is_gf && ! $is_upsell ) {
            $is_gf = $this->count_gf( $request['href'] ) > 2 || $this->count_gf_variations( $request['href'] ) > 5;
        }

        if ( $is_ga ) {
            return 'google_analytics';
        }

        if ( $is_excl ) {
            return 'excluded';
        }

        if ( $is_upsell ) {
            return 'upsell';
        }

        if ( $is_gf ) {
            return 'google_fonts';
        }

        if ( $is_webfont ) {
            return 'webfont_js';
        }

        return '';
    }

    /**
     * Count Google Fonts families in an URL.
     *
     * @param string $url
     *
     * @return int
     */
    private function count_gf( $url ) {
        $parts = parse_url( $url );

        if ( $parts['path'] == '/css2' ) {
            // CSS2
            $count = substr_count( $parts['query'] ?? '', 'family' );
        } else {
            // Regular ("legacy") API
            parse_str( $parts['query'], $params );

            $count = substr_count( $params['family'] ?? '', '|' ) + 1;
        }

        return $count;
    }

    /**
     * Count Google Fonts variations in an URL
     *
     * @param string $url
     *
     * @return int
     */
    private function count_gf_variations( $url ) {
        // Count fonts.
        $google_fonts = parse_url( $url );

        if ( $google_fonts['path'] == '/css2' ) {
            // CSS2
            $google_fonts_var_count = substr_count( $google_fonts['query'] ?? '', ';' );
        } else {
            // Regular ("legacy") API
            parse_str( $google_fonts['query'], $params );

            $google_fonts_var_count = substr_count( $params['family'] ?? '', ',' );
        }

        // This means all variations are loaded, which is never good. So manually bump up the value to display the suggestion.
        if ( $google_fonts_var_count == 0 ) {
            $google_fonts_var_count = 6;
        }

        return $google_fonts_var_count;
    }

    /**
     * @param string $request_type
     *
     * @return string
     */
    private function get_tooltip_text( $request_type ) {
        switch ( $request_type ) {
            case 'google_analytics':
                return sprintf( $this->ga_notice, admin_url( 'plugin-install.php?s=CAOS&tab=search&type=term' ) );
            case 'excluded':
                return $this->exclusion_notice;
            case 'upsell':
                return sprintf($this->upsell_notice, 'https://daan.dev/wordpress/omgf-pro/');
            case 'google_fonts':
                return sprintf( $this->gf_notice, admin_url( 'plugin-install.php?s=OMGF&tab=search&type=term' ) );
            case 'webfont_js':
                return sprintf( $this->webfont_notice, 'https://daan.dev/wordpress/omgf-pro/' );
            default:
                return '';
        }
    }

    private function start_screen() {
        if ( is_array( Helper::requests( true ) ) ) : ?>
            <p>
                <em><?php echo sprintf( __( 'Does not compute! 😱 GDPRess Bot experienced issues while scanning the website. If you\'re certain your site contains 3rd party resources, try <a href="%s" target="_blank">contacting my support human</a>?', 'gdpr-press' ), 'https://wordpress.org/support/plugin/gdpr-press/' ); ?></em>
            </p>
        <?php else : ?>
            <p>
                <em><?php echo __( 'Wow, such empty! 🐼 GDPRess Bot suggests you try giving this big button a steady push.', 'gdpr-press' ); ?></em>
            </p>
        <?php endif; ?>
        <p>
            <button data-nonce="<?php echo wp_create_nonce( Settings::GDPRESS_ADMIN_PAGE ); ?>" id="gdpress-fetch" class="button button-primary button-hero">
                <?php echo __( 'Scan website', 'gdpr-press' ); ?>
            </button>
        </p>
        <?php
    }

    /**
     * @return void
     */
    public function set_translations() {
        $this->ga_notice        = __( '<strong>Warning!</strong> 🤖 Due to the sensitive nature of using Google Analytics in compliance with GDPR, GDPRess Bot will ignore this file automatically. I suggest optimizing this request using <a href="%s" target="_blank" rel="noopener noreferrer">CAOS</a> (free).', 'gdpr-press' );
        $this->gf_notice        = __( '<strong>Uh-oh!</strong> 😵 GDPRess Bot has detected <strong>a lot</strong> of Google Fonts! I can download all of them, but I doubt you need (all of) them. I suggest optimizing this request using <a href="%s" target="_blank" rel="noopener noreferrer">OMGF</a> (free).', 'gdpr-press' );
        $this->webfont_notice   = __( '<strong>Heads up!</strong> 🔍 GDPRess Bot has detected a Web Font Loader script. I can download it for you, but the Google Fonts it loads will still be requested externally. Want to optimize those too? <a href="%s" target="_blank" rel="noopener noreferrer">OMGF Pro</a> can automatically preload, swap or unload individual font families — without any manual configuration.', 'gdpr-press' );
        $this->exclusion_notice = __( '<strong>Beep-boop!</strong> 🤖 GDPRess Bot has automatically marked this resource as excluded, because it\'s known to break when locally hosted. You\'re welcome!', 'gdpr-press' );
        $this->upsell_notice    = __( '<strong>Psst!</strong> 💡 GDPRess Bot detected a Google Fonts request. I can download it — but humans shouldn\'t be trusted with font optimization. <a href="%s" target="_blank" rel="noopener noreferrer">OMGF Pro</a> automatically preloads, unloads and subsets your fonts. No human error.', 'gdpr-press' );
    }

    /**
     * Add Test Mode setting.
     */
    public function test_mode() {
        $this->do_checkbox(
                __( 'Test mode', 'gdpr-press' ),
                Settings::GDPRESS_MANAGE_SETTING_TEST_MODE,
                GDPRESS_TEST_MODE == 'on',
                __( '<strong>Warning!</strong> Test thoroughly, before disabling this option. While this setting is enabled, any changes made by GDPRess will only be visible to logged in administrators or when <code>?gdpress</code> is added to an URL in the frontend.', 'gdpr-press' )
        );
    }
}
