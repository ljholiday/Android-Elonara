var Printful_Block_Loader;

(function () {
    'use strict';

    Printful_Block_Loader = {
        load: function (ajax_url, block) {

            block = jQuery('#' + block);
            if (block.length > 0) {

                jQuery.ajax({
                    type: "GET",
                    url: ajax_url,
                    success: function (response) {
                        block.html(response);
                    }
                });
            }
        }
    };
})();