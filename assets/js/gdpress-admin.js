/**
 * @package   GDPRess
 * @author    Daan van den Bergh
 *            https://ffw.press
 */
jQuery(document).ready(function ($) {
    var gdpress_admin = {
        ticker_items: document.querySelectorAll('.ticker-item'),
        ticker_index: 0,
        fetch_nonce: $('#gdpress-fetch').data('nonce'),
        flush_nonce: $('#gdpress-flush').data('nonce'),

        init: function () {
            // Buttons
            $('#gdpress-fetch').on('click', this.fetch);
            $('#gdpress-flush').on('click', this.flush);
            $('.locked').on('click', this.lock);

            // Ticker
            setInterval(this.loop_ticker_items, 4000);
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
        },

        loop_ticker_items: function () {
            gdpress_admin.ticker_items.forEach(function (item, index) {
                if (index == gdpress_admin.ticker_index) {
                    $(item).fadeIn(500);
                } else {
                    $(item).hide(0);
                }
            });

            gdpress_admin.ticker_index++;

            if (gdpress_admin.ticker_index == gdpress_admin.ticker_items.length) {
                gdpress_admin.ticker_index = 0;
            }
        }
    }

    gdpress_admin.init();
});