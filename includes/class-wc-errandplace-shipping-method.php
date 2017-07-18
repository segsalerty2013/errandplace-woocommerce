<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
 
if ( ! defined( 'WPINC' ) ) {
    die;
}
function WC_Errandplace_Shipping_Method(){
    
    class WC_Errandplace_Shipping_Method extends WC_Shipping_Method {

        private $version;
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = WC_ERRANDPLACE_ID;
            $this->version            = WC_ERRANDPLACE_VERSION;
            $this->method_title       = __( 'Errandplace Shipping', 'errandplace' );  
            $this->method_description = __( 'Custom Shipping Method for Errandplace', 'errandplace' ); 

            // Availability & Countries
            $this->availability = 'including';
            $this->countries = ['NG'];

            $this->init();
            $this->load_dependencies();
            $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
            $this->title = $this->method_title; //isset( $this->settings['title'] ) ? $this->settings['title'] : __( 'Errandplace Shipping', 'errandplace' );
        }

        public function getId(){
            return $this->id;
        }

        public function getVersion(){
            return $this->version;
        }

        public function getSupportedCountries(){
            return $this->countries;
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

        public function getEndpoint(){
            return $this->settings['endpoint'];
        }

        public function getPartnerId(){
            return $this->settings['partner'];
        }

        /**
         * Run the loader to execute all of the hooks with WordPress.
         *
         * @since    1.0.0
         */
        public function run() {
            $this->loader->run();
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

             'auto_recommend' => array(
                  'title' => __( 'Shipping Cost', 'errandplace' ),
                  'type' => 'checkbox',
                  'description' => __( 'Customers Automatically gets the best deal for the shipping.', 'errandplace' ),
                  'default' => 'yes'
                  ),

             'pricing_only' => array(
                  'title' => __( 'Pricing Only', 'errandplace' ),
                  'type' => 'checkbox',
                  'description' => __( 'Tick box for pricing only for customers to checkout, Not interested in auto using ErrandPlace Partners. In'
                          . ' thus case, ErrandPlace will pull estimated shipping cost for such customer order and will not reach out to Partner to fulfill the Errand.',
                          'errandplace' ),
                  'default' => 'yes',
                  'desc_tip' => true
              ),

             'hide_postcode' => array(
                'title' => __( 'Hide Postcode', 'errandplace' ),
                  'type' => 'checkbox',
                  'description' => __( 'Check to disable post code in shipping addressing', 'errandplace' ),
                  'default' => 'no'
                  ),

             'endpoint' => array(
                'title' => __( 'Endpoint', 'errandplace' ),
                  'type' => 'text',
                  'description' => __( 'Server endpoint to errandplace', 'errandplace' ),
                  'default' => __( 'https://xlogistics.herokuapp.com', 'errandplace' )
                  ),

             'partner' => array(
                'title' => __( 'Dedicated Partner (Optional)', 'errandplace' ),
                  'type' => 'text',
                  'description' => __( 'Connect to a single logistics partner only', 'errandplace' ),
                  'default' => __( '', 'errandplace' )
                  ),

             'weight' => array(
                'title' => __( 'Default Weight (kg)', 'errandplace' ),
                  'type' => 'number',
                  'description' => __( 'Use weight if product has no defined weight', 'errandplace' ),
                  'default' => 1
                  ),

             'flat' => array(
                'title' => __( 'Flat Amount (NGN)', 'errandplace' ),
                  'type' => 'number',
                  'description' => __( 'Use this amount if no match is found from ErrandPlace', 'errandplace' ),
                  'default' => 3000
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
            $state_seleted = $package["destination"]["state"];

            foreach ( $package['contents'] as $item_id => $values ) 
            { 
                $_product = $values['data']; 
                $_product_weight = floatval($_product->get_weight());
                if($_product_weight < 0.1){
                    //this product has an invalid or no weight set. so, lets use the default
                    $_product_weight = floatval($this->settings['weight']);
                }
                $weight += ($_product_weight * $values['quantity']); 
            }

            $weight = wc_get_weight( $weight, 'kg' );
            $args = [
                'body' => json_encode([
                    'channel'=> 'woocommerce',
                    'vendor'=> $this->getPartnerId(),
                    'origin'=> [(object)[
                        'o'=> (string) wc_get_base_location()['state'],
                        'w'=> $weight //total weight of items
                    ]],
                    'respond'=> true,
                    'destination'=> $state_seleted
                ]),
                'timeout' => '50',
                'headers' => ['Content-Type'=>'application/json']
            ];

            $response = wp_remote_retrieve_body(wp_remote_post( $this->getEndpoint().'/logistics/query', $args ));
            $queryErrand = json_decode($response, FALSE);
            if($queryErrand->code === '00'){
                if(sizeof($queryErrand->data->match) === 0){
                    $calculated_cost = $this->settings['flat']?$this->settings['flat']:0;
                }
                else{
                    //TODO: this will autopick the first in the array for now
                    //but later version will do more
                    WC()->session->set(WC_ERRANDPLACE_ID.'_ref', $queryErrand->data->ref);
                    foreach ($queryErrand->data->match as $value){
                        if(!empty((array) $value->zone->pricings)){
                            $calculated_cost = $value->zone->pricings->amount;
                            break;
                        }
                    }
                }
            }

            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $calculated_cost
            );

            $this->add_rate( $rate );
            
            //var_dump(WC()->session->get(WC_ERRANDPLACE_ID.'_ref'));die;
        }

        function errandplace_validate_order( $posted )   {

            $packages = WC()->shipping->get_packages();

            $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );

            if( is_array( $chosen_methods ) && in_array( $this->id, $chosen_methods ) ) {

                foreach ( $packages as $i => $package ) {

                    if ( $chosen_methods[ $i ] != $this->id ) {

                        continue;

                    }
                    //var_dump($package);die;
                }       
            } 
        }

        public function wc_ng_counties_fetch_states ( ) {
            $countries = [];
            $response = wp_remote_retrieve_body(wp_remote_get( $this->getEndpoint().'/location/nigeria/states'));
            $queryStates = json_decode($response, FALSE);
            if($queryStates->code === '00'){
                $countries['NG'] = array_combine($queryStates->data, $queryStates->data);
            }
            return $countries;
        }

        function wc_remove_billing_postcode_( $fields ) {
            add_filter('woocommerce_shipping_calculator_enable_postcode', '__return_false');
            unset($fields['billing']['billing_postcode']);
            unset($fields['shipping']['shipping_postcode']);
            return $fields;
        }

    //    function wc_remove_validation_( $address_fields ){
    //        $address_fields['postcode']['required'] = false;
    //        $address_fields['state']['required'] = false;
    //        return $address_fields;
    //    }

        private function load_dependencies() {
            //check if user wantes to displable postcode of a thing
            if($this->settings['hide_postcode'] === 'yes'){
                add_filter('woocommerce_checkout_fields' , array($this, 'wc_remove_billing_postcode_'), 10, 1);
            }
            //require_once plugin_dir_path( __FILE__ ) . 'includes/class-errandplace-country-states.php';
            //require_once plugin_dir_path( __FILE__ ) . 'includes/class-wc-errandplace-shipping-method.php';
            add_filter('woocommerce_states', array($this, 'wc_ng_counties_fetch_states'), 10, 0);
            add_filter('woocommerce_countries_shipping_country_states', array($this, 'wc_ng_counties_fetch_states'), 10, 0);
            add_filter('woocommerce_countries_base_state', array($this, 'wc_ng_counties_fetch_states'), 10, 0);

            //add_filter('woocommerce_countries_allowed_countries', 'wc_country_allowed', 10, 0);
            add_action('woocommerce_review_order_before_cart_contents', array($this, 'errandplace_validate_order'), 10, 1);
            add_action('woocommerce_after_checkout_validation', array($this, 'errandplace_validate_order'), 10, 1);
        }
    }
}