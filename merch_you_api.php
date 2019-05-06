<?php
/**
 * @package merch you api
 */
/*
Plugin Name: Merch You Api
Description: This plugin generates dynamic orders based on Woocommerce orders to Merch You Printers.
Text Domain: merch you api
*/


function plugin_assets()
{
  wp_enqueue_style( 'merch-style', plugins_url( '/css/style.css', __FILE__ ), array(),rand(1,9999) );
  wp_enqueue_script( 'merch-js', plugins_url( '/js/merch.js', __FILE__ ), array(),rand(1,9999) );
}
add_action( 'admin_init', 'plugin_assets' );


add_action('woocommerce_payment_complete', 'wc_woocommerce_payment_complete', 10, 3);
function wc_woocommerce_payment_complete($order_id) 
{

  $order        = wc_get_order( $order_id );
  $order_data   = $order->get_data(); // The Order data
  $order_id     = $order_data['id'];
  //$order_status = $order_data['status'];

  ## BILLING INFORMATION:

  $order_billing_email = $order_data['billing']['email'];
  $order_billing_phone = $order_data['billing']['phone'];

  ## SHIPPING INFORMATION:
  
  $order_shipping_first_name  = $order_data['shipping']['first_name'];
  $order_shipping_last_name   = $order_data['shipping']['last_name'];
  $order_shipping_company     = $order_data['shipping']['company'];
  $order_shipping_address_1   = $order_data['shipping']['address_1'];
  $order_shipping_address_2   = $order_data['shipping']['address_2'];
  $order_shipping_city        = $order_data['shipping']['city'];
  $order_shipping_state       = $order_data['shipping']['state'];
  $order_shipping_postcode    = $order_data['shipping']['postcode'];
  $order_shipping_country     = $order_data['shipping']['country'];
  $order_date                 = date("Y-m-d");

  $order_increment_counter = 0;

  // Iterating through each WC_Order_Item_Product objects
  foreach ($order->get_items() as $item_key => $item_values)
  {
    $product_id = $item_values['product_id'];
    $variation_id = $item_values['variation_id'];
   
    $product = new WC_Product_Variable( $product_id );
    $variations = $product->get_available_variations();
       
    foreach ($variations as $variation) 
    { 
      if($variation['variation_id'] == $variation_id)
      {
        $product_image = $variation['image']['thumb_src'];
        $sku_number    = $variation['variation_description'];
      }
    }
           

      // Below Code Is For Getting Printing Details

    if( have_rows('printdetail',$product_id) )
    {       
      while ( have_rows('printdetail',$product_id) )
      {
        the_row();
        $printloc = get_sub_field('printloc');
        $colsize  = get_sub_field('colsize');
        $basecol  = get_sub_field('basecol');
        $pantone  = get_sub_field('pantone');                       
      }
    } 


    $item_quantity = $item_values->get_quantity();     
    $order_increment_counter = $order_increment_counter+1;


    $data = array (
      'refOrderNum'     =>  'wc-'.$order_id.'-'.$order_increment_counter,
      'orderDate'       =>  $order_date,
      'transportCode'   =>  2,
      'deliveryAddress' => 
                          array (
                              'contactName'   =>  $order_shipping_first_name.$order_shipping_last_name,
                              'phone'         =>  $order_billing_phone,
                              'email'         =>  $order_billing_email,
                              'street'        =>  $order_shipping_address_1.$order_shipping_address_2,
                              'block'         =>  NULL,
                              'city'          =>  $order_shipping_city,
                              'zipCode'       =>  $order_shipping_postcode,
                              'country'       =>  $order_shipping_country,
                          ),
      'productInfo' =>
                      array (
                        array (
                          'lines' => 
                                    array (
                                      array (
                                          "itemCode" => strip_tags($sku_number),
                                          "quantity" => $item_quantity,
                                      ),
                                    ),
                          'design' => 
                                    array (
                                    "designCode"  => "New",
                                    "designName"  => "New",
                                    'attachments' => 
                                      array (
                                          array (
                                            'url' => $product_image,
                                          ),
                                      ),
                                    ),
                          'printInfo' => 
                                    array (
                                      array (
                                        'printLoc'   => $printloc,
                                        'colSize'    => $colsize,
                                        'baseCol'    => $basecol,
                                        'pantone'    => $pantone,
                                        'printQty'   => $item_quantity,
                                        'technology' => 100,
                                        'technique'  => 200,
                                      ), 
                                      array (
                                        'printLoc'   => $printloc,
                                        'colSize'    => $colsize,
                                        'baseCol'    => $basecol,
                                        'pantone'    => $pantone,
                                        'printQty'   => $item_quantity,
                                        'technology' => 100,
                                        'technique'  => 200,
                                      ),
                                    ),
                        ),
                      ),
    );

    var_error_log( $data );
    merchyou_api($data);
  }
}


function merchyou_api($data)
{     
  $ch = curl_init();
  $post=json_encode($data);
  $headers = array();
  $headers[] = "Api-Key: b1563974-5756-4d91-a7ca-58c2bf734a1c";
  $headers[] = "Content-Type: application/json;charset=UTF-8";

  curl_setopt($ch, CURLOPT_URL, "https://test.merchyou.com/api/v1/multidesign-order");
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post );
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  $result = curl_exec($ch);

  if(curl_errno($ch)) 
  {
    error_log('API not worrking');
    echo 'Error:' . curl_error($ch);
  }
  else 
  {    
    error_log('API worrking');
    $results = json_decode($result);

    error_log('results');
    var_error_log( $results );

    if($results->error != '')
    { 
        //echo '<b> Something Is Wrong!.Please Try Again </b>';
        error_log('Error while using API');
        var_error_log( $results->error );

        global $wpdb;
        $wpdb->insert( 
        $wpdb->prefix .'api_orders', 
                array(  
                        'order_no'     => $data['refOrderNum'],
                        'date'         => $data['orderDate'],
                        'order_status' => 'Order is failed for printing',
                    ), 
                    array
                    ( 
                        '%s',
                        '%s',
                        '%s',
                    ) 
        );

    }
    else
    {
        //echo '<b>You Order is succesfully submitted for printing. </b>';
        error_log('Working');

        global $wpdb;
        $wpdb->insert( 
        $wpdb->prefix .'api_orders', 
                array(  
                        'order_no'     => $data['refOrderNum'],
                        'date'         => $data['orderDate'],
                        'order_status' => 'Order is sent for printing',
                    ), 
                    array
                    ( 
                        '%s',
                        '%s',
                        '%s',
                    ) 
        );

    }

  }

  curl_close($ch);

}


/**
 * Register a custom menu page.
 */
function wpdocs_register_my_custom_menu_page()
{
    add_menu_page( 
        __( 'Merchant You Api', 'textdomain' ),
        'Merchant You Api',
        'manage_options',
        'merchantyou',
        'my_custom_menu_page',
        'dashicons-welcome-widgets-menus',
        6
    ); 
}
add_action( 'admin_menu', 'wpdocs_register_my_custom_menu_page' );
 
/**
 * Display a custom menu page
 */
function my_custom_menu_page()
{
   echo '<div class="about-merch">';
   echo '<h2>About Merch You Api</h2>';
   echo '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>
   <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>
   <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit, sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat. Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur. Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum</p>';
   echo '</div>';
}

/** Api Order Page Start **/
add_action('admin_menu', 'wpdocs_register_my_custom_submenu_page');
function wpdocs_register_my_custom_submenu_page() 
{

    add_submenu_page(
        'merchantyou',
        'api-orders',
        'Api Orders',
        'manage_options',
        'api-orders',
        'wpdocs_my_custom_submenu_page_callback' 
    );

}
 

function wpdocs_my_custom_submenu_page_callback() 
{

    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Api Orders</h2>';
    echo '</div>';
    
    global $wpdb;

    $api_orders = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}api_orders ");

     echo '<table class="wp-list-table widefat fixed striped posts">
           <thead>
            <tr>
                <th scope="col" id="order_number" class="manage-column column-order_number column-primary sortable desc"><span>Order</span></th>
                <th scope="col" id="order_date" class="manage-column column-order_date sortable desc"><span>Date</span></th>
                <th scope="col" id="order_status" class="manage-column column-order_status"><span>Status</span></th>
            </tr>
            </thead>';
    
    if ( $api_orders )
    {
        foreach ( $api_orders as $api_order )
        {   

            if($api_order->order_status !== 'Order is sent for printing')
            {
               $class = "order-failed";
            }

            echo  '<tr>';
            echo  '<td scope="col"><span>' .$api_order->order_no.'</span></td>';
            echo  '<td scope="col"><span>' . $api_order->date.'</span></td>';
            echo  '<td scope="col" class="order_status column-order_status '.$class.'"><mark class="order-status status-erhalten-rechnung tips"><span>' .$api_order->order_status.'</span></mark></td>';
            echo  '</tr>'; 
            
        }
    }
    echo '</table">';
    
}

/** Api Order Page End **/

/** Check Stock Page Start **/

add_action('admin_menu', 'wpdocs_register_my_custom_submenu_page_two');
function wpdocs_register_my_custom_submenu_page_two() 
{
    add_submenu_page(
        'merchantyou',
        'check-stock',
        'Check Stock',
        'manage_options',
        'check-stock',
        'wpdocs_my_custom_submenu_page_callback_two' 
    );
}
 
function wpdocs_my_custom_submenu_page_callback_two() 
{
    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Check Product Stock</h2>';
    echo '</div>';

    include_once('check-stock.php');
}

/** Check Stock Page End **/


/** Order Status Page Start **/

add_action('admin_menu', 'wpdocs_register_my_custom_submenu_page_three');
function wpdocs_register_my_custom_submenu_page_three() 
{
    add_submenu_page(
        'merchantyou',
        'order-status',
        'Order Status',
        'manage_options',
        'order-status',
        'wpdocs_my_custom_submenu_page_callback_three' 
    );
}
 
function wpdocs_my_custom_submenu_page_callback_three() 
{

    echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
        echo '<h2>Check Order Status</h2>';
    echo '</div>';
    include_once('check-order-status.php');

}

/** Order Status Page End **/

