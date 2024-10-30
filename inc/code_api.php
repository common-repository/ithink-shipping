<?php
if (!function_exists('ithk_addtocartbutton')) { 
function ithk_addtocartbutton($content)
{
    if (is_single())
    {
        $content .= '<input type="text" id="pincode" placeholder="Enter Pincode" name="pincode" style="margin-bottom:20px"><br /> <a href="#" id="checkp" class="button">Check pincode</a><p id="msg"></p>';
        echo $content;
    }
    else
    {
        echo $content;
    }
}
}
add_action('woocommerce_before_add_to_cart_button', 'ithk_addtocartbutton', 10, 2);
add_action('wp_ajax_nopriv_cpincode', 'ithk_cpincode');
add_action('wp_ajax_cpincode', 'ithk_cpincode');

if (!function_exists('ithk_cpincode')) { 

function ithk_cpincode()
{
    global $wpdb; // this is how you get access to the database
    $pincode = intval($_POST['pincode']);
    if ($pincode == 0)
    {
        echo "Invalid pincode";
    }
    else
    {
        ithk_checkpincode($pincode);
    }

    wp_die(); 
	// this is required to terminate immediately and return a proper response
    
}
}
add_action('wp_ajax_nopriv_fulfillment', 'ithk_fulfillment');
add_action('wp_ajax_fulfillment', 'ithk_fulfillment');

if(!function_exists('ithk_fulfillment')) { 

function ithk_fulfillment()
{
    global $wpdb; // this is how you get access to the database
    $order_id = intval($_POST['order']);
    $order = wc_get_order($order_id);
	
	$payment_mode = $order->get_payment_method();
	$billing_email  = $order->get_billing_email();

	// Get the Customer billing phone
	$billing_phone  = $order->get_billing_phone();
	// Customer billing information details
	$billing_first_name = $order->get_billing_first_name();
	$billing_last_name  = $order->get_billing_last_name();
	$billing_company    = $order->get_billing_company();
	$billing_address_1  = $order->get_billing_address_1();
	$billing_address_2  = $order->get_billing_address_2();
	$billing_city       = $order->get_billing_city();
	$billing_state      = $order->get_billing_state();
	$billing_postcode   = $order->get_billing_postcode();
	$billing_country    = $order->get_billing_country();


	if($payment_mode!='cod') {
		$payment_mode = 'prepaid';
	}

	
    $cvendor_id = get_current_user_id();

    $items = $order->get_items();

    $shipping_weight = 0;
    $fd = 0;
    $count = 0;
	$totalp=0;
    foreach ($items as $item)
    {

        $product = wc_get_product($item['product_id']);
        $vendor_id = WCV_Vendors::get_vendor_from_product($item['product_id']);
        $vendors[] = $vendor_id;

        if ($cvendor_id == $vendor_id)
        {

             $sproducts[] = array(
			 "product_name"=> $product->get_name(),
			 "product_sku" => $product->get_sku(),
			 "product_quantity" => $item['quantity'],
			 "product_price" => $product->get_price(),
			 "product_tax_rate" => "",
			 "product_hsn_code" => "",
			 "product_discount" => "0");
			 
			 $totalp = $totalp+$product->get_price()*$item['quantity'];
			$product_desc = $product->get_type();
            $product_name = $product->get_name();
            $totaldimension = $product->get_length() + $product->get_width() + $product->get_height();
            $shipping_weight = ($shipping_weight + $product->get_weight() * $item['quantity']);

            if ($totaldimension > $fd)
            {
                $fd = $totaldimension;
                $shipping_first_name = $order->shipping_first_name;
				$shipping_company_name = $order->shipping_company_name;
                $email = $order->get_billing_email();
                $phone = $order->get_billing_phone();
                $shipping_length = $product->get_length();
                $shipping_width = $product->get_width();
                $shipping_height = $product->get_height();
            }

        }
        else
        {
            $product_id[] = $item['product_id'];

        }
        $count++;
        // etc.
        // etc.
        
    }
    //print_r($vendors);
    $vendors = array_unique($vendors);
    $c = 0;
    foreach ($vendors as $vendor)
    {
        if ($cvendor_id == $vendor)
        {
            $flag = $c;
        }

        $c++;
    }

    $count = 0;
    // Iterating through order shipping items
    foreach ($order->get_items('shipping') as $item_id => $shipping_item_obj)
    {
        if ($count == $flag)
        {
            $order_item_name = $shipping_item_obj->get_name();
            $order_item_type = $shipping_item_obj->get_type();
            $shipping_method_title = $shipping_item_obj->get_method_title();
            $shipping_method_id = $shipping_item_obj->get_method_id(); // The method ID
            $shipping_method_instance_id = $shipping_item_obj->get_instance_id(); // The instance ID
            $shipping_method_total = $shipping_item_obj->get_total();
            $shipping_method_total_tax = $shipping_item_obj->get_total_tax();
            $shipping_method_taxes = $shipping_item_obj->get_taxes();
        }

        $count++;
    }
	
    //if(count($product_id)>0) {
    $fullfillorderid = $order_id . '-' . $cvendor_id;
    //}
    $shopname = WCV_Vendors::get_vendor_shop_name(stripslashes($cvendor_id));
    $order_date = $order->get_date_created();
    $order_total = $order->get_total();
	$order_total = round($totalp + $shipping_method_total);
	$codtotal='';
	if($payment_mode=='cod') {
		$codtotal = $order_total;
	}

    $cdetails = ithk_findcompany($shopname);

    $warehouse_id = $cdetails->id;
    $secret_key = ithink_secret_key;
    $access_token = ithink_access_token;
	$allproducts =  (json_encode($sproducts,JSON_UNESCAPED_SLASHES));
	//$data = "{\"data\":{\"shipments\":[{\"waybill\":\"\",\"order\":\"GK000$fullfillorderid\",\"sub_order\":\"\",\"order_date\":\"$order_date\",\"total_amount\":\"$order_total\",\"name\":\"$shipping_first_name\",\"add\":\"$order->shipping_address_1\",\"add2\":\"$order->shipping_address_2\",\"add3\":\"\",\"pin\":\"$order->shipping_postcode\",\"city\":\"$order->shipping_city\",\"state\":\"$order->shipping_state\",\"country\":\"$order->shipping_country\",\"phone\":\"$phone\",\"email\":\"$email\",\"products\":\"$product_name\",\"products_desc\":\"$product_desc\",\"quantity\":\"1\",\"shipment_length\":\"$shipping_length\",\"shipment_width\":\"$shipping_width\",\"shipment_height\":\"$shipping_height\",\"weight\":\"$shipment_weight\",\"cod_amount\":\"$order_total\",\"payment_mode\":\"$payment_mode\",\"seller_tin\":\"\",\"seller_cst\":\"\",\"return_address_id\":\"$warehouse_id\",\"product_sku\":\"S01\",\"extra_parameters\":{\"return_reason\":\"\",\"encryptedShipmentID\":\"\"}}],\"pickup_address_id\":\"$warehouse_id\",\"access_token\":\"$access_token\",\"secret_key\":\"$secret_key\",\"logistics\":\"$shipping_method_title\",\"s_type\":\"ground\",\"order_type\":\"\"}}";
     $data  = "{\"data\":{\"shipments\":[{\"waybill\":\"\",\"order\":\"GK0r99$fullfillorderid\",\"sub_order\":\"\",\"order_date\":\"$order_date\",\"total_amount\":\"$order_total\",\"name\":\"$shipping_first_name\",\"company_name\":\"$shipping_company_name\",\"add\":\"$order->shipping_address_1\",\"add2\":\"$order->shipping_address_1\",\"add3\":\"\",\"pin\":\"$order->shipping_postcode\",\"city\":\"$order->shipping_city\",\"state\":\"$order->shipping_state\",\"country\":\"$order->shipping_country\",\"phone\":\"$phone\",\"alt_phone\":\"$phone\",\"email\":\"$email\",\"is_billing_same_as_shipping\":\"no\",\"billing_name\":\"$billing_first_name\",\"billing_company_name\":\"$billing_company\",\"billing_add\":\"$billing_address_1\",\"billing_add2\":\"$billing_address_2\",\"billing_add3\":\"\",\"billing_pin\":\"$billing_postcode\",\"billing_city\":\"$billing_city\",\"billing_state\":\"$billing_state\",\"billing_country\":\"$billing_country\",\"billing_phone\":\"$billing_phone\",\"billing_alt_phone\":\"$phone\",\"billing_email\":\"$billing_email\",\"products\":$allproducts,\"shipment_length\":\"$shipping_length\",\"shipment_width\":\"$shipping_width\",\"shipment_height\":\"$shipping_height\",\"weight\":\"$shipping_weight\",\"shipping_charges\":\"$shipping_method_total\",\"giftwrap_charges\":\"0\",\"transaction_charges\":\"0\",\"total_discount\":\"0\",\"first_attemp_discount\":\"0\",\"cod_amount\":\"$codtotal\",\"payment_mode\":\"$payment_mode\",\"reseller_name\":\"\",\"eway_bill_number\":\"\",\"gst_number\":\"\",\"return_address_id\":\"$warehouse_id\"}],\"pickup_address_id\":\"$warehouse_id\",\"access_token\":\"$access_token\",\"secret_key\":\"$secret_key\",\"logistics\":\"$shipping_method_title\",\"s_type\":\"ground\",\"order_type\":\"\"}}";
    
   $url = 'https://manage.ithinklogistics.com/api_v3/order/add.json';
     $response = wp_remote_post($url, array(
	  'method' => 'POST',
        'timeout' => 45,
        'headers' => array(
            'Content-Type' => 'application/json'
        ) ,
        'body' => ($data) ,
        //'data_format' => 'body',
    ));
	
	 if (!is_wp_error($response)) {
		 //print_r($response);
	 $result = json_decode($response['body']);
	//print_r($result);
	
      if ($result->status == 'error')
    {
        echo $result->html_message;
    }
    else
    {
       
	   $fulfill = $result->data;
	  
        $status = ltrim($fulfill->{1}
            ->status);
			
        if ($status == 'error')
        {
            $order = wc_get_order($order_id);

            echo $remark = ltrim($fulfill->{1}
                ->remark);
        }
        else
        {
             $waybill = ltrim($fulfill->{1}
                ->waybill);
            $order = wc_get_order($order_id);
            $order->update_meta_data('waybill_' . $fullfillorderid, $waybill);
            $order->save();
            echo 1;

        }
	  }
	}	

    wp_die(); // this is required to terminate immediately and return a proper response
    
}
}
add_action('wp_ajax_nopriv_genrate_shiplabel', 'ithk_genrateshiplabel');
add_action('wp_ajax_genrate_shiplabel', 'ithk_genrateshiplabel');

if (!function_exists('ithk_genrateshiplabel')) { 

function ithk_genrateshiplabel()
{
    global $wpdb; // this is how you get access to the database
    $order_id = intval($_POST['order']);
    $cvendor_id = get_current_user_id();

    $fullfillorderid = $order_id . '-' . $cvendor_id;

    //$order_data = $order->get_meta('waybill_meta_key');
    $order_meta = get_post_meta($order_id);
    $waybill = $order_meta['waybill_' . $fullfillorderid][0];
    // same thing than $order->get_items('shipping')
    $secret_key = ithink_secret_key;
    $access_token = ithink_access_token;
	$data = "{\"data\":{\"access_token\":\"$access_token\",\"secret_key\":\"$secret_key\",\"awb_numbers\":\"$waybill\",\"per_page\":\"2\",\"page_size\":\"A4\",\"display_cod_prepaid\":\"\",\"display_shipper_mobile\":\"\",\"display_shipper_address\":\"\"}}\n";
    $url = 'https://manage.ithinklogistics.com/api_v3/shipping/label.json';
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
        ) ,
        'body' => ($data) ,
        'method' => 'POST',
        'data_format' => 'body',
    ));
    $result = json_decode($response['body']);
	print_r($result->file_name);

        wp_die(); // this is required to terminate immediately and return a proper response
        
   
}
}
add_action('wp_ajax_nopriv_tracking_order', 'ithk_trackingorder');
add_action('wp_ajax_tracking_order', 'ithk_trackingorder');

if (!function_exists('ithk_trackingorder')) { 

function ithk_trackingorder()
{
    global $wpdb; // this is how you get access to the database
    $order_id = intval($_POST['order']);
    $order_meta = get_post_meta($order_id);
    $cvendor_id = get_current_user_id();
    $fullfillorderid = $order_id . '-' . $cvendor_id;
    $order_meta = get_post_meta($order_id);
    $waybill = $order_meta['waybill_' . $fullfillorderid][0];
    if (!empty($waybill))
    {
        echo 'https://www.ithinklogistics.com/track-order-status.php?tracking_number=' . $waybill;
    }
    else
    {
        echo 0;
    }

    wp_die();

}
}
/***** Checking pincode is valid or not ******/
if (!function_exists('ithk_checkpincode')) { 

function ithk_checkpincode($pincode)
{

    $data = array(
        'data' => array(
            'pincode' => $pincode,
            'access_token' => ithink_access_token,
            'secret_key' => ithink_secret_key

        )
    );

    $url = 'https://manage.ithinklogistics.com/api_v2/pincode/check.json';
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
        ) ,
        'body' => json_encode($data) ,
        'method' => 'POST',
        'data_format' => 'body',
    ));
    $data = json_decode($response['body']);
    // print_r($data);
    $flag = "Not available";
    foreach ($data
        ->data->$pincode as $shipping => $value)
    {
        if ($shipping != 'state_name' && $shipping != 'city_name')
        {
            if ($value->pickup == 'Y')
            {
                $flag = 'Available';
            }
        }
    }
    echo $flag;

}
}


if (!function_exists('ithk_findcompany')) { 

function ithk_findcompany($company)
{
    $data = array(
        'data' => array(
            'access_token' => ithink_access_token,
            'secret_key' => ithink_secret_key

        )
    );

    $url = 'https://manage.ithinklogistics.com/api_v2/warehouse/get.json';
    $response = wp_remote_post($url, array(
        'headers' => array(
            'Content-Type' => 'application/json; charset=utf-8'
        ) ,
        'body' => json_encode($data) ,
        'method' => 'POST',
        'data_format' => 'body',
    ));

   
    $result = json_decode($response['body']);
    foreach ($result->data as $row)
    {
        if ($row->company_name == $company)
        {
            return $row;
        }
    }
        
}
}
add_action('init', 'ithk_init');

if (!function_exists('ithk_init')) { 

function ithk_init() {
  add_action('wp_footer', 'fg_footer', 9999);
}

function fg_footer() {
  ?>
  <script type="text/javascript">
    jQuery(function($) {
      setInterval(function() {

        $(".input-radio[name='payment_method']").change(function() {
        console.log('triggered');
          jQuery('body').trigger('update_checkout');
        });

      }, 500);
    });
  </script>
  <?php
}

}
?>
