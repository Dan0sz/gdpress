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

    /**
     * Set fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->title  = __('Manage External Requests', $this->text_domain);
        $this->notice = __('Because many extra measures are needed to comply with GDPR while using %s, GDPRess ignores this file automatically. <a target="_blank" href="%s">How do I fix this?</a>', $this->text_domain);

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
            <span class="option-title"><?php echo __('External Requests Manager', $this->text_domain); ?></span>
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
                <em><?php echo __('Uh-oh! 😱 Something must\'ve gone wrong while scanning the website.', $this->text_domain); ?></em>
            </p>
        <?php else : ?>
            <?php
            $css_count = isset(Gdpress::requests()['css']) ? count(Gdpress::requests()['css']) : 0;
            $js_count  = isset(Gdpress::requests()['js']) ? count(Gdpress::requests()['js']) : 0;
            ?>
            <p>
                <?php if (empty(Gdpress::local())) : ?>
                    <em><?php echo sprintf(__('Beep-boop! 🤖 GDPRess has detected %s stylesheets and %s scripts loaded from 3rd parties. Download them to your server to increase GDPR compliance.', $this->text_domain), (string) $css_count, (string) $js_count); ?></em>
                <?php else : ?>
                    <em><?php echo sprintf(__('Hurray! 🎉 GDPRess has downloaded %s stylesheets and %s scripts. Kickback, relax and enjoy your GDPR compliance.', $this->text_domain), count(Gdpress::local()['css']) ?? 0, count(Gdpress::local()['js'] ?? 0)); ?></em>
                <?php endif; ?>
            </p>
            <table>
                <thead>
                    <th class="downloaded" scope="col"><?php /** Header for Downloaded Status column */; ?></th>
                    <th class="name" scope="col"><?php echo __('Filename', $this->text_domain); ?></th>
                    <th class="href" scope="col"><?php echo __('External URL', $this->text_domain); ?></th>
                    <th class="href" scope="col"><?php echo __('Local URL', $this->text_domain); ?></th>
                    <th class="exclude" scope="col"><?php echo __('Exclude', $this->text_domain); ?></th>
                </thead>
                <?php foreach (Gdpress::requests() as $type => $requests) : ?>
                    <tbody class="<?php echo $type; ?>">
                        <tr>
                            <td class="title" colspan="5">
                                <h3><?php echo strtoupper($type); ?></h3>
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
                            $ga_descr   = sprintf(__($this->notice, $this->text_domain), 'Google Analytics', 'https://ffw.press/blog/gdpr/google-analytics-compliance-gdpr/');
                            $gf_descr   = sprintf(__($this->notice, $this->text_domain), 'Google Fonts', 'https://ffw.press/blog/how-to/google-fonts-gdpr/');
                            ?>
                            <tr <?php echo $is_ga || $is_gf ? "class='$classes'" : ''; ?>>
                                <td class="downloaded"><?php echo $is_ga || $is_gf ? sprintf('<i class="dashicons dashicons-info-outline tooltip"><span class="tooltip-text"><span class="inline-text">%s</span></span></span></i>', $is_ga ? $ga_descr : $gf_descr) : ($downloaded ? '<i class="dashicons dashicons-yes"></i>' : ''); ?></td>
                                <th class="name" scope="row"><?php echo $request['name']; ?></th>
                                <td class="href"><a href="#" title="<?php echo $request['href']; ?>"><?php echo $request['href']; ?></a></td>
                                <td class="href"><a href="#" title="<?php echo $local_url; ?>"><?php echo $local_url; ?></a></td>
                                <td class="exclude"><input type="checkbox" <?php echo Gdpress::is_excluded($type, $request['href']) || $is_ga || $is_gf ? 'checked' : ''; ?> <?php echo $is_ga || $is_gf ? 'class="locked"' : ''; ?> name="<?php echo Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_EXCLUDED; ?>[<?php echo $type; ?>][]" value="<?php echo esc_url($request['href']); ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="<?php echo Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS; ?>" value='<?php echo serialize(Gdpress::requests()); ?>' />
        <?php endif;
    }

    private function start_screen()
    {
        ?>
        <p>
            <em><?php echo __('Wow, such empty! 🐼 Try giving this big button a steady push.', $this->text_domain); ?></em>
        </p>
        <p>
            <button data-nonce="<?php echo wp_create_nonce(Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE); ?>" id="gdpress-fetch" class="button button-primary button-hero"><?php echo __('Scan Website', $this->text_domain); ?></button>
        </p>
<?php
    }
}
