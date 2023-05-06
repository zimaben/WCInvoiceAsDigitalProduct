<?php 
namespace rbtinv\admin;
use rbtInvoiceDP as Plugin;
/**
 * This Class handles all form submissions 
 * 
 *  
* */ 
 
class WooFunctions extends Plugin {
    public static function run(){
        \add_action( 'woocommerce_after_order_notes', array(get_class(), 'add_invoice_id_to_checkout' ));
        \add_action( 'woocommerce_checkout_update_order_meta', array(get_class(), 'save_invoice_id_to_order' ));
        \add_action('woocommerce_thankyou', array(get_class(), 'after_order'), 10, 1);
    }

    public static function add_invoice_id_to_checkout( $checkout ) {

        $invoice_id = isset($_GET['invoice_id']) ? $_GET['invoice_id'] : '';
        ?>
        <div data-role="rbt_billing_info">
            <input type="hidden" class="input-hidden" name="rbt_billing_id" id="rbt_billing_id" value="<?php echo $invoice_id ?>">
        </div>';
        <?php
    }
    public static function save_invoice_id_to_order( $order_id ) {
        if ( ! empty( $_POST['rbt_billing_id'] ) ) {
            \update_post_meta( $order_id, 'rbt_billing_id', \sanitize_text_field( $_POST['rbt_billing_id'] ) );
        }
    }
    public static function after_order( $order_id ){
        require_once self::get_plugin_path() . '/admin/setup-invoices.php';
        // Allow code execution only once 
        if( ! get_post_meta( $order_id, '_invoice_done', true ) ) {
            // Get an instance of the WC_Order object
            $order = wc_get_order( $order_id );
            $invoice_id = get_post_meta( $order_id, 'rbt_billing_id', true );
            if($invoice_id){
                $order->add_order_note(
                    sprintf(
                        __( 'Invoice ID: %s', 'woocommerce' ),
                        $invoice_id
                    )
                );
            }
            error_log("ORDER");
            error_log(print_r($order, true));
            #Update Record with Order Info
            $record = new Record($invoice_id);
            $updateOrderID = $record->updateField( 'order_id', $order_id );
            $paymentMethod = $order->get_payment_method();

            if($paymentMethod) {
                $updatePaymentMethod = $record->updateField( 'payment_method', $paymentMethod );
                switch($paymentMethod){
                    case 'cod' : 
                        $updateStatus = $record->updateField( 'invoice_status', 'issued' );
                        break;
                    default : 
                        $updateStatus = $record->updateField( 'invoice_status', 'paid' );
                        $updateAmt = $record->updateField( 'amt_paid', $order->get_total() );
                        $updatePayDate = $record->updateField( 'payment_date', date('Y-m-d H:i:s') );
                        break;
                }
            }
            
            $invoice_args = $record->ready ? $record->returnInvoiceArgs() : false;
            if(!$invoice_args){
                #email notify the problem
            }
            error_log("RECORD");
            error_log(print_r($record, true));
            error_log("INVOICE ARGS");
            error_log(print_r($invoice_args, true));
            $invoice = new Invoice($invoice_args);
            error_log("INVOICE");
            error_log(print_r($invoice, true));
            $pdf = $invoice->generatePDF();

            // Mark thank you action as done (to avoid repetitions on reload for example)
            $order->update_meta_data( '_invoice_done', true );
            $order->save();
        }
    }

}
WooFunctions::run();