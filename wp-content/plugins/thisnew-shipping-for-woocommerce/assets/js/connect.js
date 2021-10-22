var ThisNew_Connect;

(function () {
    'use strict';

    ThisNew_Connect = {
        interval: 0,
        ajax_url: '',
        init: function (ajax_url) {
            this.ajax_url = ajax_url;
            this.loader();
            this.listen_status();
            this.listen_auth_return();
        },
        loader: function () {
            jQuery('.thisnew-connect-button').click(function () {
                jQuery(this).hide();
                jQuery(this).siblings('.loader').removeClass('hidden');

                setTimeout(function() {
                    ThisNew_Connect.hide_loader();
                }, 60000); //hide the loader after a minute, assume failure
            });
        },
        hide_loader: function() {
            var button = jQuery('.thisnew-connect-button');
            button.show();
            button.siblings('.loader').addClass('hidden');
        },
        listen_status: function () {
            this.interval = setInterval(this.get_status.bind(this), 10000);    //check status every 10 secs
        },
        get_status: function () {
            var interval = this.interval;
            jQuery.ajax( {
                type: "GET",
                url: this.ajax_url,
                success: function( response ) {
                    if (response === 'OK') {
                        clearInterval(interval);
                        ThisNew_Connect.send_return_message();
                    }
                }
            });
        },
        listen_auth_return: function () {
            var intercom = Intercom.getInstance();
            intercom.on('thisnew-auth', function (data) {
                if (data.success === true) {
                    location.reload();
                }
            });
        },
        send_return_message: function () {
            var intercom = Intercom.getInstance();
            intercom.emit('thisnew-auth', {success: true});
            window.top.close();
        }
    };
})();