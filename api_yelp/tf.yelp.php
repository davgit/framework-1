<?php

// Grab Yelp API Data

require_once( dirname( __FILE__ ) . '/tf.yelp.admin-options.php' );

function tf_yelp_api() {

    $api_key = get_option('tf_yelp_api_key');
    $api_phone = get_option('tf_yelp_phone');
    $api_cc = get_option('tf_yelp_country_code');

    $api_response = wp_remote_get("http://api.yelp.com/phone_search?phone={$api_phone}&cc={$api_cc}&ywsid={$api_key}");
    $yelpfile = wp_remote_retrieve_body($api_response);
    $yelp = json_decode($yelpfile);
	
	//error checking
	if( !isset( $yelp->message->code ) || $yelp->message->code != 0 )
		return null;
	
    return $yelp;
}

function tf_yelp_transient() {

    // - get transient -
    $json = get_transient('themeforce_yelp_json');

    // - refresh transient -
    if ( !$json ) {
        $json = tf_yelp_api();
        set_transient('themeforce_yelp_json', $json, 180);
	}

    // - data -
    return $json;
}


/**
 * Delete & Update the Transient upon settings update.
 * 
 */
function tf_delete_yelp_transient_on_update_option() {
	
	delete_transient( 'themeforce_yelp_json' );
}
add_action( 'update_option_tf_yelp_api_key', 'tf_delete_yelp_transient_on_update_option' );
add_action( 'update_option_tf_yelp_phone', 'tf_delete_yelp_transient_on_update_option' );
add_action( 'update_option_tf_yelp_country_code', 'tf_delete_yelp_transient_on_update_option' );

// - YELP BAR -
//---------------------------------------------

function tf_yelp_bar() {

    $yelp = tf_yelp_transient();
    
    if( !$yelp )
    	return;

    ob_start();
        // Shows Response Code for Debugging (as HTML Comment)
        echo '<!-- Yelp Response Code: ' . $yelp->message->text . ' - ' . $yelp->message->code . ' - ' . $yelp->message->version . ' -->';
        echo '<div id="yelpbar">';
        echo '<div id="yelpcontent">';
        // Display Requirement: No-follow Link back to Yelp.com
        echo '<div class="yelpimg"><a href="http://www.yelp.com">';
        echo '<img src ="' . TF_URL . '/assets/images/yelp_logo_50x25.png">';
        echo '</a></div>';
        // Show Venue specific details
        echo '<div class="yelptext">' . __('users have rated our establishment', 'themeforce') . '</div>';
        echo '<a href="' . $yelp->businesses[0]->url . '">';
        echo '<div class="yelpimg"><img src="' . $yelp->businesses[0]->rating_img_url . '" alt=" " style="padding-top:7px;" /></div>';
        echo '</a>';
        echo '<div class="yelptext">' . __('through', 'themeforce') . '</div>';
        echo '<div class="yelptext"><a href="' . $yelp->businesses[0]->url . '" target="_blank">';
        echo $yelp->businesses[0]->review_count . '&nbsp;' . __( 'Reviews', 'themeforce' );
        echo '</a></div></div></div>';
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
};
