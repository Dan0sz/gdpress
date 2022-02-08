/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
jQuery(document).ready(function ($) {
    var gdpress_admin = {
        nonce: $('#gdpress-fetch').data('nonce'),

        init: function () {
            // Buttons
            $('#gdpress-fetch').on('click', this.fetch);
            $('.locked').on('click', this.lock);
        },

        fetch: function (e) {
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gdpress_fetch',
                    nonce: gdpress_admin.nonce
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        lock: function () {
            this.checked = true;
        }
    }

    gdpress_admin.init();
});