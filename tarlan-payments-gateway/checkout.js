const settings = window.wc.wcSettings.getSetting( 'tarlan_payments_gateway_data', {} );
const label = window.wp.htmlEntities.decodeEntities( settings.title ) || window.wp.i18n.__( 'Tarlan Payments Gateway', 'tarlan_payments_gateway' );
const Content = () => {
    return window.wp.htmlEntities.decodeEntities( settings.description || 'Tarlan Paymnets is your reliable partner for secure and convenient online payment processing. We guarantee fast transaction processing and high level of protection' );
};
const Block_Gateway = {
    name: 'tarlan_payments_gateway',
    label: label,
    content: Object( window.wp.element.createElement )( Content, null ),
    edit: Object( window.wp.element.createElement )( Content, null ),
    canMakePayment: () => true,
    ariaLabel: label,
    supports: {
        features: settings.supports,
    },
};
window.wc.wcBlocksRegistry.registerPaymentMethod( Block_Gateway );