<?php

/*
Plugin Name: WooCommerce DTCO Payment Gateway
Description: DTCO Payment gateway for woocommerce
Version: 1.0.0
Author: DTCO
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))){
	
	function dtco_woocommerce_init(){
	
		  if(!class_exists('WC_Payment_Gateway')) return;
		 
		  class WC_Gateway_DTCO extends WC_Payment_Gateway{
		    var $notify_url;
		    
		    public function __construct(){
		    	$this -> id 				= 'DTCO';
		    	$this -> icon = WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/DTCO_payment.png';
				$this -> order_button_text  = __( 'Proceed to DTCO', 'woocommerce' );
				$this -> has_fields 		= true;
				
				$this -> init_form_fields();
				$this -> init_settings();
				
				$this -> title       		= $this -> get_option('title');
				$this -> description 		= $this -> get_option('description');
										
				// Change setting
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this,'process_admin_options') );
				
				// Payment listener/API hook
				add_action('woocommerce_api_wc_gateway_dtco', array($this, 'check_dtco_callback'));			
		    }
		    
		    public function admin_options(){
		    	echo '<h3>' . __('DTCO', 'woocommerce') . '</h3>';
		    	echo '<p>' . __('DTCO standard works by sending customers to DTCO where they can pay with bitcoin.', 'woocommerce') . '</p>';
		    	
				echo '<table class="form-table">';
				$this -> generate_settings_html();
				echo '</table>';	
		    }
		       
		    function init_form_fields(){
		       $this -> form_fields = array(
			                'enabled' => array(
			                    'title' => __('Enable/Disable', 'woocommerce'),
			                    'type' => 'checkbox',
			                    'label' => __('Enable DTCO Payment Button.', 'woocommerce'),
			                    'default' => 'yes'
			                ),
			                'title' => array(
			                    'title' => __('Title', 'woocommerce'),
			                    'type'=> 'text',
			                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
			                    'default' => __('bitcoin', 'woocommerce')
			                ),
			                'description' => array(
			                    'title' => __('Description', 'woocommerce'),
			                    'type' => 'textarea',
			                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
			                    'default' => __('Pay via payment button of DTCO; You can pay with bitcoin easily.', 'woocommerce')
			                ),
			                
			                'merchant' => array(
			                    'title' => __('Merchant Info', 'woocommerce'),
			                    'type' => 'title',
			                    'description' => ''
			                ),
			                'apikey' => array(
			                    'title' => __('API key', 'woocommerce'),
			                    'type' => 'text',
			                    'description' => __('Please conntect with DTCO.')
			                ),
			            );
		    }
		    
		    function process_payment($order_id) {
		    	require_once(plugin_dir_path(__FILE__) . 'DTCO-payment' . DIRECTORY_SEPARATOR . 'DTCO.php');

		    	$order = new WC_Order($order_id);
				
				//callback api name(wc-api)
				$string = WC_Payment_Gateway::get_return_url( $order );
				$string_replace = str_replace( 'https:', 'http:', add_query_arg( 'wc-api', 'WC_Gateway_DTCO', home_url( '/' ) ) );
				
				$back_url = urlencode($string);
				
				$currency = get_woocommerce_currency();
										
				$api_key = $this  -> get_option('apikey');
				if ($api_key == '') {
					echo("The apikey is empty. Please try the onthers payment gateway.");
					return;
				}
				
				$params = array(
					'api_key'       	=> $api_key,
					'fiat_money'	    => $order -> get_total(),
					'fiat_currency'    	=> $currency,
					'order_id'			=> $order_id,
					'return_url'		=> $string_replace,
				);
			
				try {
					$invoiceID 	= DTCO::createPayment($params);
					
				}catch (Exception $e) {
					exit("Something went wrong.");
					return;
				}
				
				WC()->cart->empty_cart();
				
				return array(
					'result'   => 'success',
					'redirect' => "https://merchant.dtco.co/payment?invoiceID=$invoiceID&weburl=$back_url"
				);			
			}
			
			function check_dtco_callback() {
				$receive_order_id = $_REQUEST['KeyNo'];
				$receive_order_status = $_REQUEST['Status'];
				$receive_order_currency = $_REQUEST['FiatCurrency'];
				$receive_order_bill = $_REQUEST['FiatMoney'];

				if($receive_order_id != null && $receive_order_status != null && $receive_order_currency != null && $receive_order_bill != null){
					$check_order = new WC_Order($receive_order_id);

					$order_status = $check_order -> get_status();
					if(strcasecmp($order_status, 'cancelled') == 0 ){
						exit("DTCO woocommerce :: This order is cancelled.");
					}

					$bill_total = floatval($check_order -> get_total() );
					$receive_money = floatval($receive_order_bill);

					$bill_currency = $check_order -> get_order_currency();
					
						
					if(strcasecmp(strval($bill_total), strval($receive_money) ) == 0 && strcasecmp($receive_order_currency, $bill_currency) == 0 ){
						//currect bill
						if(strstr($receive_order_status, 'Finished') == true ){
							$check_order->payment_complete();
							exit("DTCO woocommerce :: Payment Complete");				
						}else{
							exit("DTCO woocommerce :: Pending Payment");
						}
					}else{
						exit("DTCO woocommerce :: Fake Payment");
					}
				}else{
					exit('DTCO woocommerce :: Receive Nothing');
				}	
			}
		}
			
		function add_dtco_gateway( $methods ) {
		    $methods[] = 'WC_Gateway_DTCO'; 
		    return $methods;
		}
		
		add_filter('woocommerce_payment_gateways', 'add_dtco_gateway' );
     
	}
	
	add_action('plugins_loaded', 'dtco_woocommerce_init', 0);
}
?>