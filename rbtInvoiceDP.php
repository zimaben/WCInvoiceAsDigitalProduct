<?php

/* 
 * Sell Invoices as Digital Products in WooCommerce
 *
 * @package         rbtInvoiceDP
 * @author          friendly Robot
 * @license         GNU GPL
 * @link            https://www.gnu.org/licenses/gpl-3.0.en.html
 *
 * @wordpress-plugin
 * Plugin Name:     Invoice as WC Digital Product
 * Plugin URI:      https://github.com/zimaben/WCInvoiceAsDigitalProduct
 * Description:     Generate a Sales Invoice as a Digital Product in WooCommerce
 * Version:         1.0.1
 * Author:          friendlyRobot
 * Author URI:      https://ben-toth.com/
 * License:         GNU GPL
 * Copyright:       friendlyRobot/Ben Toth
 * Class:           rbtInvoiceDP
 * Text Domain:     rbtgc
 * GitHub Plugin URI: https://github.com/zimaben/WCInvoiceAsDigitalProduct
*/

defined( 'ABSPATH' ) OR exit;

if ( ! class_exists( 'rbtInvoiceDP' ) ) {

    register_activation_hook( __FILE__, array ( 'rbtInvoiceDP', 'register_activation_hook' ) );    
    add_action( 'plugins_loaded', array ( 'rbtInvoiceDP', 'get_instance' ), 5 );
    
    class rbtInvoiceDP {
 
        private static $instance = null;

        // Plugin Settings
        const VERSION = '1.0.1';
        static $debug = true; //turns PHP and javascript logging on/off
        const TEXTDOMAIN = 'rbtinv'; // for translation & namespacing ##
        const NICENAME = 'Invoice as Digital Product'; 

        //Plugin Options

        /**
         * Returns a singleton instance
         */
        public static function get_instance() 
        {

            if ( 
                null == self::$instance 
            ) {

                self::$instance = new self;

            }

            return self::$instance;

        }
        
        private function __construct() {

            // activation ##
            \register_activation_hook( __FILE__, array ( get_class(), 'register_activation_hook' ) );

            // deactivation ##
            \register_deactivation_hook( __FILE__, array ( get_class(), 'register_deactivation_hook' ) );

            #execute deactivation options
            \add_action( 'wp_ajax_deactivate', array( get_class(), 'deactivate_callback') );

            // load libraries ##
            self::load_libraries();

            // enqueue scripts & styles


        }
        
        private static function load_libraries() {

            if( \is_admin()){
                require_once self::get_plugin_path() . 'admin/admin.php';
                require_once self::get_plugin_path() . 'admin/ajax.php';
            } else {
                require_once self::get_plugin_path() . 'theme/theme.php';
                require_once self::get_plugin_path() . 'theme/wc-functions.php';
            }

        }

        /* UTILITY FUNCTIONS */

        public static function register_activation_hook() {

            $option = self::TEXTDOMAIN . '-version';
            \update_option( $option, self::VERSION ); 
                
        }

        public static function register_deactivation_hook() {
            
            $option = self::TEXTDOMAIN . '-version';
            \delete_option( $option, self::VERSION ); 
        }

        public static function get_plugin_url( $path = '' ) 
        {

            return \plugins_url( $path, __FILE__ );

        }
        
        public static function get_plugin_path( $path = '' ) 
        {

            return \plugin_dir_path( __FILE__ ).$path;

        }

    }

}