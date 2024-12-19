<?php
/*
Plugin Name: Tarlan Payments Gateway
Description: Tarlan Payments is your reliable partner for secure and convenient online payment processing. We guarantee fast transaction processing and high level of protection
Version: 1.0.1
Author: Tarlan Payments Dev Team
Author URI: tarlanpayments.kz
Text Domain: tarlan-payments-gateway
Domain Path: /languages
*/

function plugin_load_textdomain() {
    load_plugin_textdomain( 'tarlan-payments-gateway', false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'init', 'plugin_load_textdomain' );

add_action('plugins_loaded', 'woocommerce_myplugin', 0);
function woocommerce_myplugin(){
    if (!class_exists('WC_Payment_Gateway'))
        return; // if the WC payment gateway class 

    include(plugin_dir_path(__FILE__) . 'class-gateway.php');
}


add_filter('woocommerce_payment_gateways', 'add_tarlan_payments_gateway');

function add_tarlan_payments_gateway($gateways) {
    $gateways[] = 'Tarlan_Payments_Gateway';
    return $gateways;
}

/**
 * Custom function to declare compatibility with cart_checkout_blocks feature
 */
function declare_cart_checkout_blocks_compatibility() {
    // Check if the required class exists
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        // Declare compatibility for 'cart_checkout_blocks'
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}
// Hook the custom function to the 'before_woocommerce_init' action
add_action('before_woocommerce_init', 'declare_cart_checkout_blocks_compatibility');

// Hook the custom function to the 'woocommerce_blocks_loaded' action
add_action( 'woocommerce_blocks_loaded', 'oawoo_register_order_approval_payment_method_type' );

/**
 * Custom function to register a payment method type

 */
function oawoo_register_order_approval_payment_method_type() {
    // Check if the required class exists
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    // Include the custom Blocks Checkout class
    require_once plugin_dir_path(__FILE__) . 'class-block.php';

    // Hook the registration function to the 'woocommerce_blocks_payment_method_type_registration' action
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            // Register an instance of Tarlan_Payments_Gateway_Blocks
            $payment_method_registry->register( new Tarlan_Payments_Gateway_Blocks );
        }
    );
}
?>