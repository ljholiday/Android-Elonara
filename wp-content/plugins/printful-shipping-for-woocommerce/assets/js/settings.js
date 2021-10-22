var Printful_Settings;

(function () {
    'use strict';

    Printful_Settings = {
        init_submit: function () {

            var form = jQuery('form[name=printful_settings]');
            var submit_button = form.find('.woocommerce-save-button');
            var loader = form.find('.loader');
            var pass = form.find('.loader-wrap .pass');
            var fail = form.find('.loader-wrap .fail');

            submit_button.click(function (e) {

                e.preventDefault();
                submit_button.attr('disabled', 'disabled');
                loader.show();

                jQuery.ajax({
                    type: "POST",
                    url: form.attr('action'),
                    data: form.serialize(),
                    success: function (response) {
                        submit_button.removeAttr('disabled');
                        loader.hide();

                        if (response === 'OK') {
                            pass.show(0).delay(3000).hide(0);
                        } else {
                            fail.empty();
                            fail.append('<span class="dashicons dashicons-no"></span>' + response);
                            fail.show(0).delay(3000).hide(0);
                        }
                    }
                });
            });
        },
        enable_submit_btn: function () {
            jQuery('.printful-submit input[type=submit]').removeClass('disabled').prop('disabled', false);
        }
    };
})();