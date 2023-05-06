<?php 
namespace rbtinv\admin;
use \rbtInvoiceDP as Plugin;


/**
 * This Class handles all form submissions 
 * 
* */ 
 
class PluginAjax extends Plugin {
    public static $required_fields = array(
        'submit_booking_form' => array('client_name', 'client_email', 'client_phone', 'pickupdate' )
    );

    public static function run(){
       \add_action( 'wp_enqueue_scripts', array(get_class(), 'plugin_ajax_enqueue_scripts' ));
       \add_action( 'wp_ajax_submit_booking_form', array(get_class(), 'submit_booking_form'));
       \add_action( 'wp_ajax_nopriv_submit_booking_form', array(get_class(), 'submit_booking_form'));
    }
    private static function return_false( $message = 'There was a problem', $code = 400 ){
        echo json_encode(array('status' => $code, 'payload' => $message ));
        die();
    }
    private static function return_true( $message = 'Success', $code = 200 ){
        echo json_encode(array('status' => $code, 'payload' => $message ));
        die();
    }
    public static function plugin_ajax_enqueue_scripts(){
        $v = (isset(self::$debug) && self::$debug) ? bin2hex(random_bytes(2)) : self::VERSION;
        $file = (isset(self::$debug) && self::$debug) ? 'rbtinv.js' : 'rbtinv.min.js';
        wp_enqueue_script( 'rbtinv-ajax', self::get_plugin_url() . '/theme/' . $file, array(), $v, true );
        wp_localize_script( 'rbtinv-ajax', self::TEXTDOMAIN . '_variables', array(
            'ajaxurl' => \admin_url( 'admin-ajax.php' ),
            'nonce' => \wp_create_nonce( self::TEXTDOMAIN ),
        ));
    }
    private static function checkrequest( $function ){
        if(!isset($_POST['nonce'])) self::return_false( 'Missing nonce' );
        $required = isset(self::$required_fields[$function]);
        if($required){
            foreach(self::$required_fields[$function] as $required){
                if(!isset($_POST[$required])) self::return_false( 'Missing required field ' . $required);
            }
        }
        
        if( ! wp_verify_nonce( $_POST['nonce'], 'theme_vars' ) )  self::return_false( 'Invalid nonce' );

        return true; 
    }


    private static function map_service( $service ){
        $page = \get_page_by_path( $service, OBJECT, 'product' );
        $product = $page ? \wc_get_product( $page->ID ) : false;
        if(!$product) return false;

        return array(
            'service_name' => $page->post_title,
            'service_slug' => $service,
            'price' => $product->get_price(),
            'product_id'=> $page->ID
        );

    }
    private static function get_base_url(){
        $url = \get_template_directory_uri() . '/library/core/pdf/';
        return $url;
    }
    private static function get_booking_form_args( $post ){

        $args['baseURL'] = self::get_base_url();
        $manual_close = false;
        $args['client_name'] = $post['client_name'];
        $args['client_email'] = $post['client_email'];
        $args['client_phone'] = $post['client_phone'];
        $args['pickupdate'] = $post['pickupdate'];
        $args['communication_preference'] = isset($post['communication_preference']) ? $post['communication_preference'] : 'email';

        if(isset($post['is_airport']) && $post['is_airport']) {
            $product = self::map_service('airport-pickup');
            $args['services'] = array(

                'service_name'=> $product['service_name'], 
                'price' => $product['price'],
                'id'=> $product['product_id']
            );
            if($post['from_to'] ){
                //true is From Airport
                $args['pickup_location'] = 'Ngurah Rai International Airport';
                $args['dropoff_location'] = $post['tofrom_airport_address'];
            } else {
                $args['pickup_location'] = $post['tofrom_airport_address'];
                $args['dropoff_location'] = 'Ngurah Rai International Airport';
            }
        } else {
            $product = self::map_service($post['select_service']);
            if(isset($post['select_service_days'])) {
                if($post['select_service_days']=== "4+") {
                    $manual_close = true;
                    $args['services'] = array(
                        'service_name'=> 'Private Driver :' . $post['select_service'] . ' 4+ Days',
                        'price' => $product['price'],
                        'id'=> $product['product_id']
                    );
                } else {
                    $counter = 1;
                    $service_array = array();
                    $price_array = array();
                    while($counter < (Integer) $post['select_service_days']) {
                       array_push( $service_array, array(
                        'service_name'=> $product['service_name'],
                        'price'=> $product['price'],
                        'id'=> $product['product_id'] )
                    );
                    }
                    $args['services'] = $service_array;
                }
            } else {
                $args['services'] = array(
                    'service_name'=> $product['service_name'], 
                    'price'=> $product['price'],
                    'id'=> $product['product_id']
                );
            }
            $args['pickup_location'] = $post['pickup_address'];
            $args['dropoff_location'] = $post['pickup_address'];
        }
        $args['manual_close'] = $manual_close;
        return $args;

    }
    private static function set_shopping_cart( $args ){
        global $woocommerce;
        if(isset($args['services']['id'])) {
            $product = \wc_get_product( $args['services']['id'] );
            if($product){
                $woocommerce->cart->empty_cart();
                $woocommerce->cart->add_to_cart($args['services']['id'],1);
            }
            
        } else if(isset($args['services']) && is_array($args['services']) ) {
            $woocommerce->cart->empty_cart();
            foreach($args['services'] as $service){
                $product = \wc_get_product( $service['id'] );
                if($product) $woocommerce->cart->add_to_cart($service['id'],1);
            }
          
        } else {
            self::return_false( 'Missing Required Fields' );
        }
        return $woocommerce;
    }
    public static function submit_booking_form(){
        #validates request, permissions, required arguments, and nonce
        $check = self::checkrequest( __FUNCTION__ );
        #sets the argument names and values
        $args = self::get_booking_form_args( $_POST );

        /* Add an incomplete order to the database */
        require_once self::get_plugin_path() . '/admin/setup-invoices.php';
        $record = new Record();
        $record_id = $record->addNewRecord($args);

        if(!$record_id) self::return_false( 'Could not create this record' );

        if(isset($args['manual_close']) && $args['manual_close']) {
            #fire off Manual emails
            return_true( $message = 'Please set this up manually');
        }
        #adds the product to the shopping cart
        $woocommerce = self::set_shopping_cart( $args );

        $url_params = $record_id ? '?invoice_id=' . $record_id : '';
        self::return_true( \get_home_url() . '/checkout/' . $url_params  );
        die();

    }

}
PluginAjax::run();