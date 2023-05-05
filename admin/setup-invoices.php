<?php 
namespace rbtinv\admin;
use \rbtInvoiceDP as Plugin;

/* Use this as the base file for all sales clicks */
$check_tables = \get_option( Plugin::TEXTDOMAIN . '-tables' );

if(! $check_tables ){
    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    global $wpdb;
    $table = $wpdb->prefix . 'rbt_invoices';
    $charset_collate = $wpdb->get_charset_collate();
    $query = "CREATE TABLE $table (
        invoice                     INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order                       INTEGER, 
        guest_name                  VARCHAR(128),
        guest_email                 VARCHAR(128),
        guest_phone                 VARCHAR(28),
        file_name                   VARCHAR(28),
        amt_paid                    DECIMAL(10,2), 
        payment_method              VARCHAR(28),
        payment_date                DATETIME,
        invoice_status              VARCHAR(28),
      ) $charset_collate";
      
    $exe = \dbDelta( $query );
    if( $exe ){
        \update_option( Plugin::TEXTDOMAIN . '-tables', true );
    } else {
        \update_option( Plugin::TEXTDOMAIN . '-tables', false );
    }
}
$check_tables = \get_option( Plugin::TEXTDOMAIN . '-tables' );
if($check_tables){
    require_once Plugin::get_plugin_path() . 'admin/pdf/class-invoice.php';
    require_once Plugin::get_plugin_path()  . 'admin/pdf/dompdf/autoload.inc.php';
    $root = preg_replace( '/wp-content.*$/', '', __DIR__ );
    error_log("ROOT: " . $root);
    require_once $root . '/wp-load.php';
}

