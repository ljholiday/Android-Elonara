/** Define class */
var Printful_Product_Customizer;

(function () {
    'use strict';

    /**
     *
     * @type {{modal: null, baseUrl: string, onCustomizeClick: onCustomizeClick, closeModal: closeModal, createModal: (function(string): HTMLDivElement), listenForResponse: listenForResponse, onCustomDesignSave: onCustomDesignSave}}
     */
    Printful_Product_Customizer = {
        modal: null,
        baseUrl: 'https://www.printful.com',

        /**
         * Handle click event
         * @param {string} site_url
         */
        onCustomizeClick: function (site_url) {
            var variation_id = jQuery('input[name="variation_id"]').val(); // get selected variation ID

            if (!variation_id) {
                // if variation does not exist, probably means single variant product
                variation_id = jQuery('button[name="add-to-cart"]').val();
            }

            if (!variation_id) {
                return;
            }

            var url = this.baseUrl + '/product-customizer/?website=' + site_url + '&variant=' + variation_id;

            document.body.appendChild(this.createModal(url));
            //start listening to post messaged
            this.listenForResponse();
        },

        /**
         * Close the modal element
         */
        closeModal: function () {
            if (this.modal) {
                this.modal.parentNode.removeChild(this.modal);
                this.modal = null;
            }
        },

        /**
         * Create modal content
         * @param {string} url
         * @returns {HTMLDivElement}
         */
        createModal: function (url) {
            // clear the old one just to be sure
            this.closeModal();

            // create iframe
            var isMobileSafari = false;
            var userAgent = (window.navigator && window.navigator.userAgent) ? window.navigator.userAgent : false;

            if (userAgent && userAgent.match(/iPhone|iPad|iPod/i)) {
                isMobileSafari = true;
            }

            var modal = document.createElement('div');
            modal.className = 'pf-customize-modal';
            modal.setAttribute('style', 'position: fixed; z-index: 2147483648; padding: 20px; top: 0; width: 100%; height: 100%; left: 0; background: rgba(0, 0 , 0, 0.6); box-sizing: border-box;');


            var modalHeader = document.createElement('div');
            modalHeader.setAttribute('style', 'padding: 15px; border-bottom: 1px solid #e5e5e5; overflow:hidden; position:absolute; top:0; left:0; width:100%; box-sizing: border-box;');

            var closeBtn = document.createElement('button');
            closeBtn.onclick = this.closeModal.bind(this);
            closeBtn.setAttribute('style', 'height:30px; width:30px; cursor:pointer; border:0px; background:0 0; padding:0; -webkit-appearance:none; color:#000; float:right; background:none;');
            var closeImg = document.createElement('img');
            closeImg.src = 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAAAXNSR0IArs4c6QAAAgVJREFUaAXtmU2KwkAQhZ0hQbyDu7mIB3PhwoN5EXfeIYguJo9JQRMydurnNYxTDTH+dNWr71VD2mSzyZEOpAPpQDqQDqQD6UA6kA6kA80dOJ1O++Px+NVKGFrQ9Oh9WoMhPAzD5fF4XFpAQwNa0PRAf1iABXaMle7e+r4/nM/nqyVfLUZgx3nS3etutzuMddxqsfPf1cALsJKTAr0AK3omaPWSfj6f21ERx3zso5f3C1hob6da5nW8/KzuMLJVCgnpNEvDBMyGZsGibjMwC5oJ6waOhmbDhgBHQbeADQP2QreCDQW2QreEDQfWQreGpQCvhcY8bFTGk2wX8ZWMkGu5JCvPrstSmWj+vta9aX5TWGjSgJG8Ao0p80HrrAhRgSGigKbDoh468EroJrCoRf1vCUF/edA7/K+WtAJWFg19adM6XIGVWzPvcVmqweL+F1r6FhuPNbBys08zV9a89xy6pC0AlhgPdBiwp3BPrBY+BDii4Igca+DdwJGFRub6Dd4FzCiQkbOENwMzC2PmNgEzC5JusDTUwKxCBLQ8M7TU/5a6rruPReGYj/B9MDYo065MtqKl5n2qpfyu+l7dYWRceIIYDltWvtBp05ND5DQBI7CA3jKfDUMLo4C+W58N/2RyvAIahThSqEKhBU1VUE5OB9KBdCAdSAfSgXQgHUgH0oEQB74BG1sUIwNoL3cAAAAASUVORK5CYII=';
            closeImg.setAttribute('style', 'height:30px; width:30px;');
            closeBtn.appendChild(closeImg);
            modalHeader.appendChild(closeBtn);

            var modalHeaderTitle = document.createElement('h4');
            modalHeaderTitle.className = 'product-customizer__header-title';
            modalHeaderTitle.setAttribute('style', 'float: left;font-weight:bold;font-size:23px;color:#222;line-height:30px;margin:0px;clear:none;');
            modalHeaderTitle.innerText = window.pfGlobal && window.pfGlobal.modal_title ? window.pfGlobal.modal_title : 'Create a personalized design';
            modalHeader.appendChild(modalHeaderTitle);

            var modalContent = document.createElement('div');
            modalContent.setAttribute('style', 'background-color: #fff; width: 100%; height: 100%;overflow:hidden;position:relative');
            modalContent.appendChild(modalHeader);

            var styles = document.createElement('style');
            styles.innerHTML = '@media screen and (max-width: 768px) { .product-customizer__header-title {font-size: 16px !important;} }';
            modalContent.appendChild(styles);

            modal.appendChild(modalContent);

            var iframe = document.createElement('iframe');
            iframe.src = url;
            iframe.width = '100%';
            iframe.height = '100%';

            if (isMobileSafari) {
                var iframeWrapper = document.createElement('div');
                iframeWrapper.setAttribute('style', '-webkit-overflow-scrolling: touch; overflow: scroll; height: 100%; top: 61px; box-sizing: border-box; position: absolute; width: 100%; padding-bottom: 60px;');
                iframe.setAttribute('style', 'border: 0; box-sizing: border-box;');
                iframeWrapper.appendChild(iframe);
                modalContent.appendChild(iframeWrapper);
            } else {
                iframe.setAttribute('style', 'border: 0; padding-top: 60px; box-sizing: border-box;');
                modalContent.appendChild(iframe);
            }


            this.modal = modal;

            return modal;
        },

        /**
         * Listen for response from PF
         */
        listenForResponse: function () {
            var eventMethod = window.addEventListener ? "addEventListener" : "attachEvent";
            var eventer = window[eventMethod];
            var messageEvent = eventMethod === "attachEvent" ? "onmessage" : "message";
            eventer(messageEvent, function (e) {
                if (e.origin !== this.baseUrl) {
                    return;
                }

                if (typeof e.data['action'] === 'undefined' || e.data['action'] !== 'PFProductCustomized') {
                    return;
                }

                this.onCustomDesignSave(e.data['hash']);
            }.bind(this));
        },

        /**
         * Manage response from PF
         * @param {string} hash
         */
        onCustomDesignSave: function (hash) {
            jQuery('#pfc_hash').val(hash);
            this.closeModal();

            jQuery('.single_add_to_cart_button').click();
        }
    };
})();

setInterval(function () {
    /**
     * @type {Array<HTMLImageElement>}
     */
    var toCheck = [];

    jQuery('img.pf-image-pending').each(function () {
        jQuery(this).removeClass('pf-image-pending');
        jQuery(this).addClass('pf-image-checking');
        toCheck.push(this);
    });

    var hashes = toCheck.reduce(function(carry, image){
        carry[image.getAttribute('data-hash')] = image;

        return carry;
    }, {});

    // Resolve admin base URL
    var adminURL = window.pfGlobal && window.pfGlobal.admin_url && window.pfGlobal.admin_url.length
        ? window.pfGlobal.admin_url
        : '/wp-admin/';

    // rtrim('/') + '/'
    var hasSlash = adminURL.substring(adminURL.length - 1) === '/';
    if (!hasSlash) {
        adminURL += '/';
    }

    // if pending (loading) images exist, request image urls
    if (Object.keys(hashes).length > 0) {
        jQuery.ajax({
            url: adminURL + 'admin-ajax.php',
            type: 'GET',
            data: {
                action: 'printful_customized_thumb',
                hashes: Object.keys(hashes)
            },
            success: function (response) {
                var result = JSON.parse(response);
                for (var hash in result){
                    var image = hashes[hash];

                    if (result[hash]) {
                        image.src = result[hash];
                        jQuery(image).removeClass('pf-image-checking');
                    } else {
                        jQuery(image).removeClass('pf-image-checking');
                        jQuery(image).addClass('pf-image-pending');
                    }
                }
            }
        });
    }
}, 2000);