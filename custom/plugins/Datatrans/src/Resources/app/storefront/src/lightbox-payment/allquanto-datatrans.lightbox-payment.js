import Plugin from 'src/plugin-system/plugin.class';

export default class AllquantoDatatransLightboxPayment extends Plugin {
    static options = {
        /**
         * Specifies the text that is prompted to the user
         * @type string
         */
        datatransTrxId: 'Seems like there\'s nothing more to see here.',
    };

    init() {
        window.addEventListener('DOMContentLoaded', this.onLoad.bind(this));
    }

    onLoad() {
        Datatrans.startPayment({
            transactionId:  this.options.datatransTrxId,
            'opened': function() {console.log('payment-form opened');},
            'loaded': function() {console.log('payment-form loaded');},
            'closed': function() {console.log('payment-page closed');},
            'error': function() {console.log('error');}
        });
    }
}
