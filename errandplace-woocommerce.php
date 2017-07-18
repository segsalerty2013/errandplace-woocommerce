<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Plugin Name: ErrandPlace Shipping
 * Plugin URI: 
 * Description: Automated Shipping Method and Handling for WooCommerce. One place for reliable errand, shipping and logistic service providers. Currently supports only businesses in <strong>NIGERIA</strong>.
 * Version: 1.0.0
 * Author: ErrandPlace
 * Author URI: https://www.errandplace.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /
 * Text Domain: errandplace
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}

define( 'WC_ERRANDPLACE_VERSION', '1.0.0' );
define( 'WC_ERRANDPLACE_ID', 'errandplace' );
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
    
    function errandplace_init(){
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-errandplace-shipping-method.php';
        add_action( 'woocommerce_shipping_init', 'wc_errandplace_shipping_method' );
        add_filter( 'woocommerce_shipping_methods', 'add_errandplace_shipping_method' );
    }
    
    
    function add_errandplace_shipping_method( $methods ) {
        $methods[] = 'WC_Errandplace_Shipping_Method';
        return $methods;
    }
    
    /**
    * Add Settings link to the plugin entry in the plugins menu
    **/
    function errandplace_plugin_action_links( $links ) {

        $settings_link = array(
            'settings' => '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=shipping&section=errandplace' ) . '" title="View ErrandPlace WooCommerce Settings">Settings</a>'
        );

        return array_merge( $links, $settings_link );

    }
    add_filter('plugin_action_links_' . plugin_basename( __FILE__ ), 'errandplace_plugin_action_links' );
    
    add_action( 'plugins_loaded', 'errandplace_init', 0 );
}