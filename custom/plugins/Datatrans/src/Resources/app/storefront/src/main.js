// Import all necessary Storefront plugins
import AllquantoDatatransLightboxPayment from './lightbox-payment/allquanto-datatrans.lightbox-payment';

// Register your plugin via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register(
    'AllquantoDatatransLightboxPayment',
    AllquantoDatatransLightboxPayment,
    '[data-allquanto-datatrans-lightbox-payment]'
);
