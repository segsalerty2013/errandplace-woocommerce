<?php


defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

function wc_ng_counties_add_counties ( $states ) {
    $states['NG'] = [];
    $Errandplace_Shipping_Method = new Errandplace_Shipping_Method();
    $endpoint = $Errandplace_Shipping_Method->settings['endpoint'];

    $response = wp_remote_retrieve_body(wp_remote_get( $endpoint.'/location/nigeria/states'));
    $queryStates = json_decode($response, FALSE);
    if($queryStates->code === '00'){
        $states['NG'] = array_combine($queryStates->data, $queryStates->data);
    }
    return $states;
}

add_filter( 'woocommerce_states' , 'wc_ng_counties_add_counties'  );