<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

/**
 * Plugin Name: Errandplace Shipping
 * Plugin URI: 
 * Description: Custom Shipping Method for WooCommerce
 * Version: 1.0.0
 * Author: Mustafa Segun
 * Author URI: https://www.errnaplace.com
 * License: GPL-3.0+
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 * Domain Path: /lang
 * Text Domain: errandplace
 */
 
if ( ! defined( 'WPINC' ) ) {
 
    die;
 
}
 
/*
 * Check if WooCommerce is active
 */
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
 
    function Errandplace_shipping_method() {
        if ( ! class_exists( 'Errandplace_Shipping_Method' ) ) {
            class Errandplace_Shipping_Method extends WC_Shipping_Method {
                /**
                 * Constructor for your shipping class
                 *
                 * @access public
                 * @return void
                 */
                public function __construct() {
                    $this->id                 = 'errandplace'; 
                    $this->method_title       = __( 'Errandplace Shipping', 'errandplace' );  
                    $this->method_description = __( 'Custom Shipping Method for Errandplace', 'errandplace' ); 
 
                    // Availability & Countries
                    $this->availability = 'including';
                    $this->countries = array(
                        'NG', // Nigeria for now
                        );
 
                    $this->init();
 
                    $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
                    $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Errandplace Shipping', 'errandplace' );
                }
 
                /**
                 * Init your settings
                 *
                 * @access public
                 * @return void
                 */
                function init() {
                    // Load the settings API
                    $this->init_form_fields(); 
                    $this->init_settings(); 
 
                    // Save settings in admin if you have any defined
                    add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
                }
 
                /**
                 * Define settings field for this shipping
                 * @return void 
                 */
                function init_form_fields() { 
 
                    $this->form_fields = array(
 
                     'enabled' => array(
                          'title' => __( 'Enable', 'errandplace' ),
                          'type' => 'checkbox',
                          'description' => __( 'Enable this shipping.', 'errandplace' ),
                          'default' => 'yes'
                          ),
 
                     'title' => array(
                        'title' => __( 'Title', 'errandplace' ),
                          'type' => 'text',
                          'description' => __( 'Title to be display on site', 'errandplace' ),
                          'default' => __( 'Errandplace Shipping', 'errandplace' )
                          ),

                     'endpoint' => array(
                        'title' => __( 'Endpoint', 'errandplace' ),
                          'type' => 'text',
                          'description' => __( 'Server endpoint to errandplace', 'errandplace' ),
                          'default' => __( 'https://xlogistics.herokuapp.com', 'errandplace' )
                          ),

                     'partner_id' => array(
                        'title' => __( 'Partner Id (Optional)', 'errandplace' ),
                          'type' => 'text',
                          'description' => __( 'Connect to a single logistics partner', 'errandplace' ),
                          'default' => __( '', 'errandplace' )
                          ),
 
                     'weight' => array(
                        'title' => __( 'Weight (kg)', 'errandplace' ),
                          'type' => 'number',
                          'description' => __( 'Maximum allowed weight', 'errandplace' ),
                          'default' => 100
                          ),
 
                     );
 
                }
 
                /**
                 * This function is used to calculate the shipping cost. Within this function we can check for weights, dimensions and other parameters.
                 *
                 * @access public
                 * @param mixed $package
                 * @return void
                 */
                public function calculate_shipping( $package = array() ) {
                    
                    $weight = 0;
                    $cost = 0;
                    $state = $package["destination"]["state"];
                    $arr_states = [
                        'AB' => 'Abia State' ,
                        'FC' => 'FCT' ,
                        'AD' => 'Adamawa State' ,
                        'AK' => 'Akwa Ibom State' ,
                        'AN' => 'Anambra State' ,
                        'BA' => 'Bauchi State' ,
                        'BY' => 'Bayelsa State' ,
                        'BE' => 'Benue State' ,
                        'BO' => 'Borno State' ,
                        'CR' => 'Cross River State' ,
                        'DE' => 'Delta State' ,
                        'EB' => 'Ebonyi State' ,
                        'ED' => 'Edo State' ,
                        'EK' => 'Ekiti State' ,
                        'EN' => 'Enugu State' ,
                        'GO' => 'Gombe State' ,
                        'IM' => 'Imo State' ,
                        'JI' => 'Jigawa State' ,
                        'KD' => 'Kaduna State' ,
                        'KN' => 'Kano State' ,
                        'KT' => 'Katsina State' ,
                        'KE' => 'Kebbi State' ,
                        'KO' => 'Kogi State' ,
                        'KW' => 'Kwara State' ,
                        'LA' => 'Lagos State' ,
                        'NA' => 'Nasarawa State' ,
                        'NI' => 'Niger State' ,
                        'OG' => 'Ogun State' ,
                        'ON' => 'Ondo State' ,
                        'OS' => 'Osun State' ,
                        'OY' => 'Oyo State' ,
                        'PL' => 'Plateau State' ,
                        'RI' => 'Rivers State' ,
                        'SO' => 'Sokoto State' ,
                        'TA' => 'Taraba State' ,
                        'YO' => 'Yobe State' ,
                        'ZA' => 'Zamfara State'
                    ];
 
                    foreach ( $package['contents'] as $item_id => $values ) 
                    { 
                        $_product = $values['data']; 
                        $weight = $weight + $_product->get_weight() * $values['quantity']; 
                    }
 
                    $weight = wc_get_weight( $weight, 'kg' );
                    $Errandplace_Shipping_Method = new Errandplace_Shipping_Method();
                    $endpoint = $Errandplace_Shipping_Method->settings['endpoint'];
                    $partner_id = $Errandplace_Shipping_Method->settings['partner_id'];

                    $args = [
                        'body' => json_encode([
                            'channel'=> 'woocommerce',
                            'vendor'=> $partner_id,
                            'origin'=> [(object)[
                                'o'=> 'Lagos State',
                                'w'=> $weight //total weight of items
                            ]],
                            'respond'=> true,
                            'destination'=> $arr_states[$state]
                        ]),
                        // 'timeout' => '5',
                        // 'redirection' => '5',
                        // 'httpversion' => '1.0',
                        // 'blocking' => true,
                        // 'headers' => array(),
                        // 'cookies' => array()
                        'timeout' => '50',
                        'headers' => ['Content-Type'=>'application/json']
                    ];

                    $response = wp_remote_retrieve_body(wp_remote_post( $endpoint.'/logistics/query', $args ));
                    $queryErrand = json_decode($response, FALSE);
                    if($queryErrand->code === '00'){
                        foreach ($queryErrand->data->match as $value){
                            if(!empty((array) $value->zone->pricings)){
                                $calculated_cost = $value->zone->pricings->amount;
                            }
                        }
                    }
 
                    $rate = array(
                        'id' => $this->id,
                        'label' => $this->title,
                        'cost' => $calculated_cost?$calculated_cost:'Unavailable'
                    );
 
                    $this->add_rate( $rate );
                    
                }
            }
        }
    }
 
    add_action( 'woocommerce_shipping_init', 'errandplace_shipping_method' );
 
    function add_errandplace_shipping_method( $methods ) {
        $methods[] = 'Errandplace_Shipping_Method';
        return $methods;
    }
 
    add_filter( 'woocommerce_shipping_methods', 'add_errandplace_shipping_method' );
 
    function errandplace_validate_order( $posted )   {
 
        $packages = WC()->shipping->get_packages();
 
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
         
        if( is_array( $chosen_methods ) && in_array( 'errandplace', $chosen_methods ) ) {
             
            foreach ( $packages as $i => $package ) {
 
                if ( $chosen_methods[ $i ] != "errandplace" ) {
                             
                    continue;
                             
                }
 
                $Errandplace_Shipping_Method = new Errandplace_Shipping_Method();
                $weightLimit = (int) $Errandplace_Shipping_Method->settings['weight'];
                $weight = 0;
 
                foreach ( $package['contents'] as $item_id => $values ) 
                { 
                    $_product = $values['data']; 
                    $weight = $weight + $_product->get_weight() * $values['quantity']; 
                }
 
                $weight = wc_get_weight( $weight, 'kg' );
                
                if( $weight > $weightLimit ) {
 
                        $message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'errandplace' ), $weight, $weightLimit, $Errandplace_Shipping_Method->title );
                             
                        $messageType = "error";
 
                        if( ! wc_has_notice( $message, $messageType ) ) {
                         
                            wc_add_notice( $message, $messageType );
                      
                        }
                }
            }       
        } 
    }
 
    add_action( 'woocommerce_review_order_before_cart_contents', 'errandplace_validate_order' , 10 );
    add_action( 'woocommerce_after_checkout_validation', 'errandplace_validate_order' , 10 );
}