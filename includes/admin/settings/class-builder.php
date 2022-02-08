<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Builder
{
    /** @var string $plugin_text_domain */
    protected $plugin_text_domain = 'gdpr-press';

    /** @var string $utm_tags */
    protected $utm_tags = '?utm_source=gdpress&utm_medium=plugin&utm_campaign=settings';

    /** @var $title */
    protected $title;

    /**
     * Gdpress_Admin_Settings_Builder constructor.
     */
    public function __construct()
    {
    }

    /**
     *
     */
    public function do_before()
    {
?>
        <table class="form-table">
        <?php
    }

    /**
     *
     */
    public function do_after()
    {
        ?>
        </table>
    <?php
    }

    /**
     *
     */
    public function do_title()
    {
    ?>
        <h2><?= $this->title ?></h2>
    <?php
    }

    /**
     * @param $class
     */
    public function do_tbody_open($class)
    {
    ?>
        <tbody class="<?= $class; ?>" <?= empty(CAOS_OPT_COMPATIBILITY_MODE) ? '' : 'style="display: none;"'; ?>>
        <?php
    }


    /**
     *
     */
    public function do_tbody_close()
    {
        ?>
        </tbody>
    <?php
    }

    /**
     * Generate radio setting
     *
     * @param $label
     * @param $inputs
     * @param $name
     * @param $checked
     * @param $description
     */
    public function do_radio($label, $inputs, $name, $checked, $description)
    {
    ?>
        <tr>
            <th scope="row"><?= $label; ?></th>
            <td id="<?= $name . '_right_column'; ?>">
                <?php foreach ($inputs as $option => $option_label) : ?>
                    <label>
                        <input type="radio" class="<?= str_replace('_', '-', $name . '_' . $option); ?>" name="<?= $name; ?>" value="<?= $option; ?>" <?= $option == $checked ? 'checked="checked"' : ''; ?> />
                        <?= $option_label; ?>
                    </label>
                    <br />
                <?php endforeach; ?>
                <p class="description">
                    <?= apply_filters($name . '_setting_description', $description); ?>
                </p>
            </td>
        </tr>
    <?php
    }

    /**
     * Generate select setting
     *
     * @param      $label
     * @param      $select
     * @param      $options
     * @param      $selected
     * @param      $description
     */
    public function do_select($label, $select, $options, $selected, $description)
    {
    ?>
        <tr>
            <th scope="row">
                <?= apply_filters($select . '_setting_label', $label); ?>
            </th>
            <td>
                <select name="<?= $select; ?>" class="<?= str_replace('_', '-', $select); ?>">
                    <?php
                    $options = apply_filters($select . '_setting_options', $options);
                    ?>
                    <?php foreach ($options as $option => $option_label) : ?>
                        <option value="<?= $option; ?>" <?= ($selected == $option) ? 'selected' : ''; ?>><?= $option_label; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?= apply_filters($select . '_setting_description', $description); ?>
                </p>
            </td>
        </tr>
    <?php
    }

    /**
     * Generate number setting.
     *
     * @param $label
     * @param $name
     * @param $value
     * @param $description
     */
    public function do_number($label, $name, $value, $description, $min = 0)
    {
    ?>
        <tr valign="top">
            <th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <input class="<?= str_replace('_', '-', $name); ?>" type="number" name="<?= $name; ?>" min="<?= $min; ?>" value="<?= $value; ?>" />
                <p class="description">
                    <?= apply_filters($name . '_setting_description', $description); ?>
                </p>
            </td>
        </tr>
    <?php
    }

    /**
     * Generate text setting.
     *
     * @param        $label
     * @param        $name
     * @param        $placeholder
     * @param        $value
     * @param string $description
     * @param bool   $update_required
     */
    public function do_text($label, $name, $placeholder, $value, $description = '', $visible = true)
    {
    ?>
        <tr class="<?= str_replace('_', '-', $name); ?>-row" <?= $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <input class="<?= str_replace('_', '-', $name); ?>" type="text" name="<?= $name; ?>" placeholder="<?= $placeholder; ?>" value="<?= $value; ?>" />
                <p class="description">
                    <?= apply_filters($name . 'setting_description', $description); ?>
                </p>
            </td>
        </tr>
    <?php
    }

    /**
     * Generate checkbox setting.
     *
     * @param $label
     * @param $name
     * @param $checked
     * @param $description
     */
    public function do_checkbox($label, $name, $checked, $description, $disabled = false, $visible = true)
    {
    ?>
        <tr class='<?= str_replace('_', '-', $name); ?>-row' <?= $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?= apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <label for="<?= $name; ?>">
                    <input <?= apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?> type="checkbox" class="<?= str_replace('_', '-', $name); ?>" name="<?= $name; ?>" <?= $checked == "on" ? 'checked = "checked"' : ''; ?> />
                    <?= apply_filters($name . '_setting_description', $description); ?>
                </label>
            </td>
        </tr>
<?php
    }
}
