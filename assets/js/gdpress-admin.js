/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
jQuery(document).ready(function ($) {
    var gdpress_admin = {
        fetch_nonce: $('#gdpress-fetch').data('nonce'),
        flush_nonce: $('#gdpress-flush').data('nonce'),

        init: function () {
            // Buttons
            $('#gdpress-fetch').on('click', this.fetch);
            $('#gdpress-flush').on('click', this.flush);
            $('.locked').on('click', this.lock);
        },

        fetch: function (e) {
            e.preventDefault();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'gdpress_fetch',
                    nonce: gdpress_admin.fetch_nonce
                },
                complete: function () {
                    location.reload();
                }
            });
        },

        flush: function (e) {
            e.preventDefault();

            $.ajax({
                type: 'POST',
                url: ajaxurl,
                data: {
                    action: 'gdpress_flush',
                    nonce: gdpress_admin.flush_nonce
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