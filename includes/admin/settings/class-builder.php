<?php
defined('ABSPATH') || exit;

/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
class Gdpress_Admin_Settings_Builder
{
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
        <h2><?php echo esc_html($this->title); ?></h2>
    <?php
    }

    /**
     * @param $class
     */
    public function do_tbody_open($class)
    {
    ?>
        <tbody class="<?php echo esc_attr($class); ?>">
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
            <th scope="row"><?php echo $label; ?></th>
            <td id="<?php echo $name . '_right_column'; ?>">
                <?php foreach ($inputs as $option => $option_label) : ?>
                    <label>
                        <input type="radio" class="<?php echo str_replace('_', '-', $name . '_' . $option); ?>" name="<?php echo $name; ?>" value="<?php echo esc_attr($option); ?>" <?php echo $option == $checked ? 'checked="checked"' : ''; ?> />
                        <?php echo $option_label; ?>
                    </label>
                    <br />
                <?php endforeach; ?>
                <p class="description">
                    <?php echo apply_filters($name . '_setting_description', $description); ?>
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
                <?php echo apply_filters($select . '_setting_label', $label); ?>
            </th>
            <td>
                <select name="<?php echo $select; ?>" class="<?php echo str_replace('_', '-', $select); ?>">
                    <?php
                    $options = apply_filters($select . '_setting_options', $options);
                    ?>
                    <?php foreach ($options as $option => $option_label) : ?>
                        <option value="<?php echo esc_attr($option); ?>" <?php echo ($selected == $option) ? 'selected' : ''; ?>><?php echo $option_label; ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">
                    <?php echo apply_filters($select . '_setting_description', $description); ?>
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
            <th scope="row"><?php echo apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <input class="<?php echo str_replace('_', '-', $name); ?>" type="number" name="<?php echo $name; ?>" min="<?php echo $min; ?>" value="<?php echo esc_attr($value); ?>" />
                <p class="description">
                    <?php echo apply_filters($name . '_setting_description', $description); ?>
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
        <tr class="<?php echo str_replace('_', '-', $name); ?>-row" <?php echo $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?php echo apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <input class="<?php echo str_replace('_', '-', $name); ?>" type="text" name="<?php echo $name; ?>" placeholder="<?php echo $placeholder; ?>" value="<?php echo esc_textarea($value); ?>" />
                <p class="description">
                    <?php echo apply_filters($name . 'setting_description', $description); ?>
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
        <tr class='<?php echo str_replace('_', '-', $name); ?>-row' <?php echo $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?php echo apply_filters($name . '_setting_label', $label); ?></th>
            <td>
                <label for="<?php echo $name; ?>">
                    <input <?php echo apply_filters($name . '_setting_disabled', $disabled) ? 'disabled' : ''; ?> type="checkbox" class="<?php echo str_replace('_', '-', $name); ?>" name="<?php echo $name; ?>" <?php echo $checked == "on" ? 'checked = "checked"' : ''; ?> />
                    <?php echo apply_filters($name . '_setting_description', $description); ?>
                </label>
            </td>
        </tr>
<?php
    }
}
