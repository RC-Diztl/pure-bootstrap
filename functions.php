<?php
    /**
    *   Theme: Pure Bootstrap
    *   Theme functions file
    *   @package Pure Bootstrap
    *   @version Pure Bootstrap 1.1.1
    */

    /** custom folder */
    $theme_dir_uri = get_template_directory_uri();
    $custom                 = 'custom';
    $custom_dir_name        = '@' . $custom;
    $custom_dir             = get_template_directory() . '/' . $custom_dir_name;
    /** file name only */
    $custom_style_file      = $custom.'.css';
    $custom_script_file     = $custom.'.js';
    $custom_functions_file  = $custom.'_functions.php';
    /** full path and file */
    $custom_style           = $custom_dir . '/'. $custom_style_file;
    $custom_script          = $custom_dir . '/'. $custom_script_file;
    $custom_functions       = $custom_dir . '/'. $custom_functions_file;

    /** include the users custom functions */
    if ( file_exists($custom_functions) ) {
        include( $custom_functions );
    }

    /** custom navwalker for bootstrap navbar */
    require_once('inc/wp_bootstrap_navwalker.php');

    /** admin theme options */
    include('inc/admin/theme-options.php');

    /** styles and script */
    include('inc/functions/base-styles-and-scripts.php');
    include('inc/functions/custom-styles-and-scripts.php');


    /** start basic wp overides
    ========================================================================== */

    // string (string)
    function publish_later_on_feed($where)
    {
        /** delay feed update */
        global $wpdb;
        if (is_feed()) {
            // timestamp in WP-format
            $now = gmdate('Y-m-d H:i:s');
            // value for wait; + device
            $wait = '5'; // integer
            // http://dev.mysql.com/doc/refman/5.0/en/date-and-time-functions.html#function_timestampdiff
            $device = 'MINUTE'; // MINUTE, HOUR, DAY, WEEK, MONTH, YEAR
            // add SQL-sytax to default $where
            $where .= " AND TIMESTAMPDIFF($device, $wpdb->posts.post_date_gmt, '$now') > $wait ";
        }
        return $where;
    }

    /* Add feautured image support to theme */
    if (function_exists('add_theme_support')) {
        add_theme_support('post-thumbnails');
    }


    /* Delay xml feed update after new posts */
    if (function_exists('add_filter')) {
        add_filter('posts_where', 'publish_later_on_feed');
    }


    // Add a larger thumbnail
    if (function_exists('add_image_size')) {
        add_image_size( 'larger', 1920, 1080, true );
    }
    /** end overides
    ========================================================================== */



    /** start shortcodes
    ========================================================================== */
    /**
      * Facebook album shortcode examples:
      * [fb-album token=YOUR_ACCESS_TOKEN album=156033841132513 limit=20]
      * [fb-album token=YOUR_ACCESS_TOKEN album=156033841132513 limit=20 reverse=true]
      */
    include('inc/functions/fb-album.php');

    /**
      * Contact Form shortcode examples:
      * [contact] (sent to default wp-admin)
      * [contact email=user@example.com]
      */
    include('inc/functions/contact-form.php');
    /** end shortcodes
    ========================================================================== */


    /** menus and widgets */
    include('inc/functions/menus.php');
    include('inc/functions/widgets.php');


    // void (void)
    function get_thumbnail_or_placeholder()
    {
        /** If no featured image is set, get the theme image placeholder */
        $featured_image = get_template_directory_uri() . '/img/featured-placeholder.jpg';
        if ( has_post_thumbnail()) the_post_thumbnail('large', array('class' => 'img-responsive'));
        else echo '<img src="'.$featured_image.'" alt="no featured image" class="img-responsive">';
    }


    // void (void)
    function fullscreen_nav()
    {
        /** This is the nave for the full-screen-no-nav template */
        include('inc/fullscreen-navbar.php');
    }


    // bool (void)
    function use_cdn()
    {
        /** Use CDNs */
        return get_option( 'pure_bootstrap_option', 'use_cdn' );
    }


    // bool (void)
    function show_header()
    {
        /** Theme option to show header. */
        return get_option( 'pure_bootstrap_option', 'show_header' );
    }

    // int (int)
    function custom_excerpt_length( $length )
    {
        /** Custom excerpt length */
        // return 35; // may ghage this back
        return $length;
    }

    /*
      * Que functions
      * ======================================================================= */
    if (function_exists('add_filter')) {
        /* Shorten the excerpt length */
        add_filter( 'excerpt_length', 'custom_excerpt_length', 999 );
    }

    if (function_exists('add_theme_support')) {
        /* Jetpack infinite-scroll support */
        add_theme_support( 'infinite-scroll', array(
            'container' => 'content',
            'footer' => 'page',
        ));
    }

    function create_ACF_meta_in_REST() {
    $postypes_to_exclude = ['acf-field-group','acf-field'];
    $extra_postypes_to_include = ["page"];
    $post_types = array_diff(get_post_types(["_builtin" => false], 'names'),$postypes_to_exclude);

    array_push($post_types, $extra_postypes_to_include);

    foreach ($post_types as $post_type) {
        register_rest_field( $post_type, 'ACF', [
            'get_callback'    => 'expose_ACF_fields',
            'schema'          => null,
       ]
     );
    }

}

function expose_ACF_fields( $object ) {
    $ID = $object['id'];
    return get_fields($ID);
}

add_action( 'rest_api_init', 'create_ACF_meta_in_REST' );



add_filter( 'acf/rest_api/field_settings/show_in_rest', '__return_true' );

add_filter( 'acf/rest_api/field_settings/edit_in_rest', '__return_true' );

remove_filter('the_excerpt', 'wpautop');
remove_filter ('the_content', 'wpautop');

add_filter( 'woocommerce_attribute', 'woocommerce_attribute_filter_callback', 10, 3 );
function woocommerce_attribute_filter_callback( $formatted_values, $attribute, $values ) {
    return wptexturize( implode( ', ', $values ) );
}

if( !is_admin() ) // not admin side
    add_filter( 'the_content', 'so_26068464' );
function so_26068464( $content )
{
    if ( ! is_product() ) {
        return $content;
    }
    return strip_tags( $content, '' );
}

add_action('rest_api_init', function () {
	register_rest_route( 'bz/v3', 'changepassword',array(
				  'methods'  => 'post',
				  'callback' => 'updateUserPassword',
				  'permission_callback' => function() {
                      return current_user_can('edit_posts');
                  }
		));
  });

  function updateUserPassword($request) {		
		$user_id = $request['user_id'];
		$user = get_user_by( 'id', $user_id );
		
		$password = $request['password'];
		$new_password = $request['new_password'];
		
		if(empty($user_id)){
				$json = array('code'=>'400','msg'=>'Please enter user id');
			    return new WP_Error( 'empty_category', 'Please enter user id', array('status' => 404) );
			}
			if(empty($password)){
			$json = array('code'=>'400','msg'=>'Please enter old password');
			return new WP_Error( 'empty_category', 'Please enter old password', array('status' => 404) ); 
		}
		if(empty($new_password)){
			$json = array('code'=>'400','msg'=>'Please enter new password');
			return new WP_Error( 'empty_category', 'Please enter new password', array('status' => 404) );
		}
		$hash = $user->data->user_pass;
		$code = 500; $status = false;
		if (wp_check_password( $password, $hash ) ){
			$msg = 'Password updated successfully';
			$code = 200; $status = true;
			wp_set_password($new_password , $user_id);
		}else{
		    $code = 400; $status = true;
			$msg = 'Current password does not match.';
		}

		$json = array('code'=>$code,'status'=>$status,'msg'=>$msg);
		$response = new WP_REST_Response($json);
        $response->set_status(200);
    
        return $response;
  }

use Razorpay\Api\Api;
use Razorpay\Api\Errors;

add_filter('woocommerce_rest_prepare_shop_order_object', 'razor_pay_create_order', 10, 3);

function razor_pay_create_order($response, $post, $request) {
     $order_data = $response->get_data();
     $id = $order_data["id"];
     $order_data["extraId"] = $id;
     if($order_data['payment_method'] == 'razorpay') {
       // $order = wc_get_order($id);
       // $order->calculate_totals();
	$api = new Api('rzp_test_3wAUWAFe936RfC','Dx5kyk79qfzhjNt500ypq8mP');
	$data = array(
		'receipt'         => $id,
		'amount'          => (int) round($order_data['total'] * 100),
		'currency'        => $order_data['currency'],
		'payment_capture' =>  0,
		'app_offer'       =>  0,
		'notes'           => array(
		    "woocommerce_order_number"  => (string) $id,
		),
	    );
	try
	{
	    $razorpayOrder = $api->order->create($data);
	}
	catch (Exception $e)
	{
	    return $e;
	}  
	$razorpayOrderId = $razorpayOrder['id'];
	$order_data['razorpayOrder'] = $razorpayOrderId;
    }
     //return $razorpayOrderId;
    return $response = $order_data;
}
  

?>
