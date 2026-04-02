<?php
/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://daan.dev
 */

namespace GDPRess\Admin\Settings;

class Builder {
    /**
     * Builder constructor.
     */
    public function __construct() {
    }

    /**
     *
     */
    public function do_after() {
        ?>
        </table>
        <?php
    }

    /**
     *
     */
    public function do_before() {
        ?>
        <table class="form-table">
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
    public function do_checkbox( $label, $name, $checked, $description, $disabled = false, $visible = true ) {
        ?>
        <tr class='<?php echo str_replace( '_', '-', esc_attr( $name ) ); ?>-row' <?php echo $visible ? '' : 'style="display: none;"'; ?>>
            <th scope="row"><?php echo apply_filters( $name . '_setting_label', esc_attr( $label ) ); ?></th>
            <td>
                <label for="<?php echo esc_attr( $name ); ?>">
                    <input <?php echo apply_filters( $name . '_setting_disabled', $disabled ) ? 'disabled' : ''; ?>
                        type="checkbox"
                        class="<?php echo str_replace( '_', '-', esc_attr( $name ) ); ?>"
                        name="<?php echo esc_attr( $name ); ?>" <?php echo $checked === 'on' ? 'checked = "checked"' : ''; ?>
                    />
                    <?php echo apply_filters( $name . '_setting_description', wp_kses( $description, 'code' ) ); ?>
                </label>
            </td>
        </tr>
        <?php
    }

    /**
     *
     */
    public function do_tbody_close() {
        ?>
        </tbody>
        <?php
    }

    /**
     * @param $class
     */
    public function do_tbody_open( $class ) {
        ?>
        <tbody class="<?php echo esc_attr( $class ); ?>">
        <?php
    }
}
