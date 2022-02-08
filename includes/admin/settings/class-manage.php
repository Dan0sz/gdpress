<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Manage extends Gdpress_Admin_Settings_Builder
{
    /** @var string $text_domain */
    private $text_domain = 'gdpr-press';

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
        add_filter('gdpress_manage_content', [$this, 'do_before']);

        // Content
        add_filter('gdpress_manage_content', [$this, 'manage_section']);

        // Close
        add_filter('gdpress_manage_content', [$this, 'do_after']);
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
                <p>
                    <?= __('Manage all of your site\'s external requests, blablabla.', $this->text_domain); ?>
                </p>
            </div>
        </div>
<?php
    }
}
