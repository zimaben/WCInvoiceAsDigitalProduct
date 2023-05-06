<?php 
namespace rbtinv\admin;
use \rbtInvoiceDP as Plugin;


/**
 * 
 * This file is an interstitial file for dealing with requests that involve the invoices
 * database table. This file will require the correct libraries to create the invoice PDF
 * and to create the record in the database, and all adjacent WC functionality.
 * For performance reasons is not loaded by default. It should be loaded at the beginning
 * of any request flow that involves invoices.
 * 
 */
function rbt_add_inventory_table($name){ 
    global $wpdb;
    $table = $wpdb->prefix . $name;
    $charset_collate = $wpdb->get_charset_collate();
    #check if the table exists but the option doesn't
    $check_query = $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $table ) );
    if ( ! $wpdb->get_var( $check_query ) == $table ) {
        $query = "CREATE TABLE $table (
            invoice_id                     INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            order_id                       INTEGER, 
            guest_name                      VARCHAR(128),
            guest_email                     VARCHAR(128),
            guest_phone                     VARCHAR(28),
            file_name                       VARCHAR(128),
            amt_paid                        DECIMAL(10,2), 
            payment_method                  VARCHAR(28),
            pickup_date                     DATETIME,
            payment_date                    DATETIME,
            pickup_address                  VARCHAR(128),
            dropoff_address                 VARCHAR(128),
            invoice_status                  VARCHAR(28)
        ) $charset_collate";
          
    return \dbDelta( $query );

    } else {
        return 1;
    }
}

/* Use this as the base file for all sales clicks */
$check_tables = \get_option( Plugin::TEXTDOMAIN . '-tables' );

if( ! $check_tables ){

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

    $table_db_check = rbt_add_inventory_table('rbt_invoices');

    if( $table_db_check ){
        \update_option( Plugin::TEXTDOMAIN . '-tables', true );
        $check_tables = true;
    } else {
        \update_option( Plugin::TEXTDOMAIN . '-tables', false );

    }
}
if($check_tables){
    require_once Plugin::get_plugin_path() . 'admin/pdf/class-invoice.php';
    require_once Plugin::get_plugin_path() . 'admin/pdf/class-record.php';
    require_once Plugin::get_plugin_path() . 'admin/pdf/dompdf/autoload.inc.php';
    $root = preg_replace( '/wp-content.*$/', '', __DIR__ );
    require_once $root . '/wp-load.php';
}

