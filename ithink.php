<?php
/**
 * Plugin Name: iThink Logistics Multivendor - Marketplace eCommerce Shipping for WooCommerce
 * Plugin URI: https://wordpress.org/plugins/ithink-shipping
 * Description: iThink Logistics is an AI-based logistics aggregator. We are your one-stop solution for integrating multiple courier platforms over a single dashboard. It 
is one of the most popular shipping software in India providing services to more than 26000 + pin codes in India.
 * Version: 1.0
 * Author: iThink Logistics
 * Author URI: https://ithinklogistics.com/
 * Tested up to: 5.5
 *
 */
 
if (!defined('WPINC'))
{
    die('security by preventing any direct access to your plugin file');
}

$options = get_option('ithink_options');
if (!defined('ithink_secret_key'))
{
    define('ithink_secret_key', $options['ithink_field_secretkey']);
}
if (!defined('ithink_access_token'))
{
    define('ithink_access_token', $options['ithink_field_accesstoken']);
}



if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins'))))
{
    include (plugin_dir_path(__FILE__) . 'admin/setting.php');
    include (plugin_dir_path(__FILE__) . 'inc/code_api.php');
	
	add_action( 'wp_enqueue_scripts', 'ithk_scripts' );
	function ithk_scripts() {
		wp_register_script( 'main-ajax', plugin_dir_url( __FILE__ ) . '/js/custom.js', array(), '', true );
		wp_register_script( 'sweetalert', plugin_dir_url( __FILE__ ) . '/js/sweetalert.min.js', array(), '', true );
		$arr = array(
			'ajaxurl' => admin_url('admin-ajax.php')
		);
		wp_localize_script('main-ajax','obj',$arr );
		wp_enqueue_script('main-ajax');
		wp_enqueue_script('sweetalert');
	}
	
    function ithk_shipping_init()
    {
        if (!class_exists('ithk_WC_Pickup_Shipping_Methods'))
        {

            class ithk_WC_Pickup_Shipping_Method extends WC_Shipping_Method
            {
                /**
                 * Constructor.
                 *
                 * @param int $instance_id
                 */
                public function __construct($instance_id = 0)
                {
                    $this->id = 'ithk_pickup_shipping_method';
                    $this->instance_id = absint($instance_id);
                    $this->method_title = __("iThink Shipping", 'imp');
                    $this->supports = array(
                        'shipping-zones',
                        'instance-settings',
                        'instance-settings-modal',
                    );

                    $this->init();
                }

                /**
                 * Initialize custom shiping method.
                 */
                public function init()
                {

                    // Load the settings.
                    $this->init_form_fields();
                    $this->init_settings();

                    // Define user set variables
                    $this->title = $this->get_option('title');
                    $this->secret_key = $this->get_option('secret_key');

                    // Actions
                    add_action('woocommerce_update_options_shipping_' . $this->id, array(
                        $this,
                        'process_admin_options'
                    ));
                }

                /**
                 * Calculate custom shipping method.
                 *
                 * @param array $package
                 *
                 * @return void
                 */
                public function calculate_shipping($package = array())
                {
					$payment_mode = WC()->session->get('chosen_payment_method'); //Get the selected payment method
					
					if($payment_mode!='cod') {
							$payment_mode = 'prepaid';
						}
                    $shipping_weight = 0;
                    $cost = 0;
                    $country = $package["destination"]["country"];
                    $fd = 0;
                    foreach ($package['contents'] as $item_id => $values)
                    {
                        $_product = $values['data'];
                        $shipping_weight = $shipping_weight + $_product->get_weight() * $values['quantity'];
                        $totaldimension = $_product->get_length() + $_product->get_width() + $_product->get_height();
                        if ($totaldimension > $fd)
                        {
                            $fd = $totaldimension;
                            $shipping_length = $_product->get_length();
                            $shipping_width = $_product->get_width();
                            $shipping_height = $_product->get_height();
                        }

                       // $weight = wc_get_weight($weight, 'kg');

                        $vendor_id = get_post_field('post_author', $_product->get_id());
                        $vendor = get_user_meta($vendor_id);
                        $from_pincode = get_user_meta($vendor_id, '_wcv_store_postcode', true);
                        $to_pincode = $package['destination']['postcode'];
                        $secret_key = ithink_secret_key;
                        $access_token = ithink_access_token;

                        $data = "{\"data\":{\"from_pincode\":\"$from_pincode\",\"to_pincode\":\"$to_pincode\",\"shipping_length_cms\":\"$shipping_length\",\"shipping_width_cms\":\"$shipping_width\",\"shipping_height_cms\":\"$shipping_height\",\"shipping_weight_kg\":\"$shipping_weight\",\"payment_method\":\"$payment_mode\",\"order_type\":\"forward\",\"product_mrp\":\"1200.00\",\"access_token\":\"$access_token\",\"secret_key\":\"$secret_key\"}}\n";
                        $url = 'https://manage.ithinklogistics.com/api_v3/rate/check.json';
                        $response = wp_remote_post($url, array(
                            'headers' => array(
                                'Content-Type' => 'application/json; charset=utf-8'
                            ) ,
                            'body' => ($data) ,
                            'method' => 'POST',
                            'data_format' => 'body',
                        ));
                        $data = json_decode($response['body']);

                        // $shippingdata= $data->data->$to_pincode;
                        $shippingdata = $data->data;
                        //print_r($shippingdata);
                        

                        foreach ($shippingdata as $value)
                        {
                            if ($value->pickup == 'Y')
                            {
                                $this->add_rate(array(
                                    'id' => $value->logistic_name,
                                    'label' => $value->logistic_name,
                                    'cost' => $value->rate
                                ));
                            }
                        }
                    }
                }
                /**
                 * Init form fields.
                 */
                function init_form_fields()
                {

                    $this->form_fields = array(

                        'enabled' => array(
                            'title' => __('Enable', 'imp') ,
                            'type' => 'checkbox',
                            'description' => __('Enable this shipping method. Manage Secret key and Access token from ithink options page', 'imp') ,
                            'default' => 'yes'
                        ) ,

                        'title' => array(
                            'title' => __('Title', 'imp') ,
                            'type' => 'text',
                            'default' => __('iThink Shipping', 'imp')
                        ) ,

                    );

                }

            }
        }
    }
    add_action('woocommerce_shipping_init', 'ithk_shipping_init');

    function ithk_shipping_method($methods)
    {
        $methods['ithk_pickup_shipping_method'] = 'ithk_WC_Pickup_Shipping_Method';

        return $methods;
    }
    add_filter('woocommerce_shipping_methods', 'ithk_shipping_method');
	
	
	function ithk_updateshipping( $posted_data) {

    global $woocommerce;

    // Parsing posted data on checkout
    $post = array();
    $vars = explode('&', $posted_data);
    foreach ($vars as $k => $value){
        $v = explode('=', urldecode($value));
        $post[$v[0]] = $v[1];
    }

    // Here we collect chosen payment method
    $payment_method = $post['payment_method'];
	
  foreach ( WC()->cart->get_shipping_packages() as $package_key => $package ){
       WC()->session->set( 'shipping_for_package_' . $package_key, true );
		//
    } 
	WC()->session->set('chosen_payment_method', $payment_method);
    WC()->cart->calculate_shipping();

}

add_action('woocommerce_checkout_update_order_review', 'ithk_updateshipping');


}

