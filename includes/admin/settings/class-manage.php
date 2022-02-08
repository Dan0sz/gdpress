<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Manage extends Gdpress_Admin_Settings_Builder
{
    /**
     * Set fields.
     * 
     * @return void 
     */
    public function __construct()
    {
        $this->title = __('Manage External Requests', $this->text_domain);

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
            <span class="option-title"><?= __('External Requests Manager', $this->text_domain); ?></span>
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
                <em><?= __('Uh-oh! Something must\'ve gone wrong while scanning the website.', $this->text_domain); ?></em>
            </p>
        <?php else : ?>
            <?php
            $css_count = isset(Gdpress::requests()['css']) ? count(Gdpress::requests()['css']) : 0;
            $js_count  = isset(Gdpress::requests()['js']) ? count(Gdpress::requests()['js']) : 0;
            ?>
            <p>
                <em><?= sprintf(__('Beep-boop! GDPRess has detected %s stylesheets and %s scripts loaded from 3rd parties.', $this->text_domain), (string) $css_count, (string) $js_count); ?></em>
            </p>
            <table>
                <thead>
                    <th class="downloaded" scope="col"><?php /** Header for Downloaded Status column */; ?></th>
                    <th class="name" scope="col"><?= __('Filename', $this->text_domain); ?></th>
                    <th class="href" scope="col"><?= __('External URL', $this->text_domain); ?></th>
                    <th class="href" scope="col"><?= __('Local URL', $this->text_domain); ?></th>
                    <th class="exclude" scope="col"><?= __('Exclude', $this->text_domain); ?></th>
                </thead>
                <?php foreach (Gdpress::requests() as $type => $requests) : ?>
                    <tbody class="<?= $type; ?>">
                        <tr>
                            <td class="title" colspan="5">
                                <h3><?= strtoupper($type); ?></h3>
                            </td>
                        </tr>
                        <?php foreach ($requests as $i => $request) : ?>
                            <?php
                            $is_ga     = strpos($request['href'], 'google-analytics') !== false || strpos($request['href'], 'googletagmanager') !== false;
                            $is_gf     = strpos($request['href'], 'fonts.googleapis.com') !== false || strpos($request['href'], 'fonts.gstatic.com') !== false;
                            $classes   = $i % 2 ? 'even ' : '';
                            $classes   .= $is_ga || $is_gf ? 'suggestion' : '';
                            $local_url = Gdpress::get_local_url($request['href'], $type);
                            ?>
                            <tr <?= $is_ga || $is_gf ? "class='$classes'" : ''; ?>>
                                <td class="downloaded"><?= $is_ga || $is_gf ? '<i class="dashicons dashicons-warning"></i>' : ''; ?></td>
                                <th class="name" scope="row"><?= $request['name']; ?></th>
                                <td class="href"><a href="#" title="<?= $request['href']; ?>"><?= $request['href']; ?></a></td>
                                <td class="href"><a href="#" title="<?= $local_url; ?>"><?= $local_url; ?></a></td>
                                <td class=" exclude"><input type="checkbox" <?= Gdpress::is_excluded($type, $request['href']) || $is_ga || $is_gf ? 'checked' : ''; ?> <?= $is_ga || $is_gf ? 'class="locked"' : ''; ?> name="<?= Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_EXCLUDED; ?>[<?= $type; ?>][]" value="<?= $request['href']; ?>" /></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                <?php endforeach; ?>
            </table>
            <input type="hidden" name="<?= Gdpress_Admin_Settings::GDPRESS_MANAGE_SETTING_REQUESTS; ?>" value='<?= serialize(Gdpress::requests()); ?>' />
        <?php endif;
    }

    private function start_screen()
    {
        ?>
        <p>
            <em><?= __('Wow, such empty! Try giving this big button a steady push.', $this->text_domain); ?></em>
        </p>
        <p>
            <button data-nonce="<?= wp_create_nonce(Gdpress_Admin_Settings::GDPRESS_ADMIN_PAGE); ?>" id="gdpress-fetch" class="button button-primary button-hero"><?= __('Scan Website', $this->text_domain); ?></button>
        </p>
<?php
    }
}
