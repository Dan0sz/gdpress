<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Manage extends Gdpress_Admin_Settings_Builder
{
    /** @var string $notice */
    private $notice;

    /** @var string $tooltip_markup */
    private $tooltip_markup = '<i class="dashicons dashicons-info-outline tooltip"><span class="tooltip-text"><span class="inline-text">%s</span></span></span></i>';

    /**
     * Set fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->title  = __('Manage External Requests', 'gdpr-press');
        $this->notice = __('Because many extra measures are needed to comply with GDPR while using %s, GDPRess ignores this file automatically. <a target="_blank" href="%s">How do I fix this?</a>', 'gdpr-press');

        $this->init();
    }

    /**
     * Filters & Hooks.
     * 
     * @return void 
     */
    private function init()
    {
        // Open
        add_filter('gdpress_manage_content', [$this, 'do_title']);

        // Content
        add_filter('gdpress_manage_content', [$this, 'manage_section']);
    }

    /**
     * Add Manage section contents.
     * 
     * @return void 
     */
    public function manage_section()
    {
?>
        <div class="gdpress manage postbox">
            <span class="option-title"><?php echo __('External Requests Manager', 'gdpr-press'); ?></span>
            <div class="gdpress-container">
                <?php if (Gdpress::requests()) : ?>
                    <?php $this->manage_screen(); ?>
                <?php else : ?>
                    <?php $this->start_screen(); ?>
                <?php endif; ?>
            </div>
        </div>
        <?php
    }

    private function manage_screen()
    {
        if (empty(Gdpress::requests())) : ?>
            <p>
                <em><?php echo __('Uh-oh! 😱 Something must\'ve gone wrong while scanning the website.', 'gdpr-press'); ?></em>
            </p>
        <?php else : ?>
            <?php
            $css_count = isset(Gdpress::requests()['css']) ? count(Gdpress::requests()['css']) : 0;
            $js_count  = isset(Gdpress::requests()['js']) ? count(Gdpress::requests()['js']) : 0;
            ?>
            <p>
                <?php if (empty(Gdpress::local())) : ?>
                    <em><?php echo sprintf(__('Beep-boop! 🤖 GDPRess has detected %s stylesheets and %s scripts loaded from 3rd parties. Download them to your server to increase GDPR compliance.', 'gdpr-press'), (string) $css_count, (string) $js_count); ?></em>
                <?php else : ?>
                    <em><?php echo sprintf(__('Hurray! 🎉 GDPRess has downloaded %s stylesheets and %s scripts. Kickback, relax and enjoy your (increased) GDPR compliance.', 'gdpr-press'), count(Gdpress::local()['css']) ?? 0, count(Gdpress::local()['js'] ?? 0)); ?></em> <?php echo sprintf($this->tooltip_markup, 'GDPRess is a helper bot, not a legal advice bot. Please refer to your country\'s GDPR regulations and make sure you\'ve taken all necessary steps to comply.'); ?>
                <?php endif; ?>
            </p>
            <table>
                <thead>
                    <th class="downloaded" scope="col"><?php /** Header for Downloaded Status column */; ?></th>
                    <th class="name" scope="col"><?php echo __('Filename', 'gdpr-press'); ?></th>
                    <th class="href" scope="col"><?php echo __('External URL', 'gdpr-press'); ?></th>
                    <th class="href" scope="col"><?php echo __('Local URL', 'gdpr-press'); ?></th>
                    <th class="exclude" scope="col"><?php echo __('Exclude', 'gdpr-press'); ?></th>
                </thead>
                <?php foreach (Gdpress::requests() as $type => $requests) : ?>
                    <tbody class="<?php echo esc_attr($type); ?>">
                        <tr>
                            <td class="title" colspan="5">
                                <h3><?php echo esc_html(strtoupper($type)); ?></h3>
                            </td>
                        </tr>
                        <?php foreach ($requests as $i => $request) : ?>
                            <?php
                            $is_ga      = strpos($request['href'], 'google-analytics') !== false || strpos($request['href'], 'googletagmanager') !== false;
                            $is_gf      = strpos($request['href'], 'fonts.googleapis.com/css') !== false || strpos($request['href'], 'fonts.gstatic.com') !== false;
                            $classes    = $i % 2 ? 'even ' : '';
                            $classes    .= $is_ga || $is_gf ? 'suggestion' : '';
                            $local_url  = Gdpress::get_local_url($request['href'], $type);
                            $downloaded = file_exists(Gdpress::get_local_path($request['href'], $type));
                            $ga_descr   = sprintf(__($this->notice, 'gdpr-press'), 'Google Analytics', 'https://ffw.press/blog/gdpr/google-analytics-compliance-gdpr/');
                            $gf_descr   = sprintf(__($this->notice, 'gdpr-press'), 'Google Fonts', 'https://ffw.press/blog/how-to/google-fonts-gdpr/');
                            ?>
                            <tr <?php echo $is_ga || $is_gf ? "class='" . esc_attr($classes) . "'" : ''; ?>>
                                <td class="downloaded"><?php echo $is_ga || $is_gf ? sprintf($this->tooltip_markup, $is_ga ? wp_kses_post($ga_descr) : wp_kses_post($gf_descr)) : ($downloaded ? '<i class="dashicons dashicons-yes"></i>' : ''); ?></td>
                                <th class="name" scope="row"><?php echo esc_attr($request['name']); ?></th>
                                <td class="href"><a href="#" title="<?php echo esc_url($request['href']); ?>"><?php echo esc_url($request['href']); ?></a></td>
                                <td class="href"><a href="#" title="<?php echo esc_url($local_url); ?>"><?php echo esc_url($local_url); ?></a></td>
                                <td class="exclude"><input type="checkbox" <?php echo Gdpress::is_excluded($type, $request['href']) || $is_ga || $is_gf ? 'checked' : ''; ?> <?php echo $is_ga || $is_gf ? 'class="locked"' : ''; ?> name="<?php echo esc_attr(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_EXCLUDED); ?>[<?php echo esc_attr($type); ?>][]" value="<?php echo esc_url($request['href']); ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="<?php echo esc_attr(Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS); ?>" value='<?php echo serialize(Gdpress::requests()); ?>' />
        <?php endif;
    }

    private function start_screen()
    {
        ?>
        <p>
            <em><?php echo __('Wow, such empty! 🐼 Try giving this big button a steady push.', 'gdpr-press'); ?></em>
        </p>
        <p>
            <button data-nonce="<?php echo wp_create_nonce(Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE); ?>" id="gdpress-fetch" class="button button-primary button-hero"><?php echo __('Scan Website', 'gdpr-press'); ?></button>
        </p>
<?php
    }
}
