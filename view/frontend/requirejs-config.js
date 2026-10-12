var config = {
    config: {
        mixins: {
            'Magento_Ui/js/form/element/post-code': {
                'Peerapatfc_ThaiAddress/js/form/element/post-code-mixin': true
            },
            'Magento_Checkout/js/view/shipping-address/address-renderer/default': {
                'Peerapatfc_ThaiAddress/js/view/address-renderer-mixin': true
            }
        }
    }
};
