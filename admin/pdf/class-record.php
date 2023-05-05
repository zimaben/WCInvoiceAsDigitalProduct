<?php
namespace rbtinv\admin;

Class Record {

    /*
        invoice                     INTEGER UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
        order                       INTEGER, 
        guest_name                  VARCHAR(128),
        guest_email                 VARCHAR(128),
        guest_phone                 VARCHAR(28),
        file_name                   VARCHAR(28),
        amt_paid                    DECIMAL(10,2), 
        payment_method              VARCHAR(28),
        payment_date                DATETIME,
        pickup_address              VARCHAR(128),
        dropoff_address             VARCHAR(128),
        invoice_status              VARCHAR(28),
    */

    public $error = false;
    public function __construct( integer $record_id = null ) {
        $this->record = $this->getRecord($record_id);
    }
    private function checkNewRecordArgs( $args = array() ){
        $error = '';
        if(!isset($args['guest_name']) || empty($args['guest_name'])) $error.='Missing guest name. ';
        if(!isset($args['guest_email']) || empty($args['guest_email'])) $error.='Missing guest email. ';
        if(!isset($args['guest_phone']) || empty($args['guest_phone'])) $error.='Missing guest phone. ';
        if(!isset($args['pickup_address']) || empty($args['pickup_address'])) $error.='Missing pickup address. ';
        if(!isset($args['dropoff_address']) || empty($args['dropoff_address'])) $error.='Missing dropoff address. ';
        return strlen($error) > 0 ? $error : $args;
    }
    private function getRecord( $record_id = null ){
        if( ! $record_id ) return false;
        global $wpdb;
        $table_name = $wpdb->prefix . 'rbt_invoice';
        $record = $wpdb->get_row( $wpdb->prepare(
            "
            SELECT * FROM $table_name
            WHERE invoice = %d
            ",
            $record_id
        ));
        return $record;
    }
    public function newRecord( $args = array() ){
        $args = $this->checkNewRecordArgs($args);
        if( ! is_array($args) ){
            $this->error = $args;
            return false;
        }
        global $wpdb;
        $table_name = $wpdb->prefix . 'rbt_invoice';
        $inserted = $wpdb->query( $wpdb->prepare(
            "
            INSERT INTO $table_name
            (order, guest_name, guest_email, guest_phone, file_name, amt_paid, payment_method, payment_date, pickup_address, dropoff_address, invoice_status)
            VALUES (%d, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s)
            ",
            0,
            $args['guest_name'],
            $args['guest_email'],
            $args['guest_phone'],
            '',
            0,
            '',
            '',
            $args['pickup_address'],
            $args['dropoff_address'],
            'incomplete'
        ) );
        if($inserted){
            $this->record = $this->getRecord($wpdb->insert_id);
            return $wpdb->insert_id;
        } else {
            $this->error = 'Error inserting record';
            return false;
        } 
    }
    private function additionalArgs( $args = array() ){
        $already = array(
            'invoice_id',
            'billing_id',
            'client_name',
            'client_email',
            'client_phone',
            'payment_method',
            'pickupdate',
            'services',
            'total',
            'ready',
            'htmlString',
            'pdf',
            'file', 
            'filename',
        );
        foreach($args as $key => $value){
            if( !in_array($key, $already) ) $this->$key = $value;
        }
    }
    #Move to class invoice
    // private function generateFileName(){
    //     $filename = date( 'Y-m-d') . '_' . $this->client_email . '.pdf';
    //     return $filename;
    // }
    
    private function addInvoiceRecord(){

    }
    public function generatePDF(){
        if( $this->htmlString ){ 
            $this->htmlString = preg_replace("/&(?!\S+;)/", "&amp;", $this->htmlString);
        } else {
            return false;
        }
        $options = new Options();
        $options->set('isRemoteEnabled', TRUE);
        $options->set('chroot', __DIR__ );
        $dompdf = new PDF($options);
        $dompdf->set_base_path(__DIR__);
        $dompdf->loadHTML($this->htmlString);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        # $dompdf->stream();
        #$pdf->stream("invoice.pdf", array("Attachment" => true));
        $output = $dompdf->output();
        \file_put_contents( __DIR__ . '/invoices/' . $this->filename, $output);
        return true;
    }
    private function checkArgs(){
        if( 
            $this->booking_id && 
            $this->client_name && 
            $this->client_email &&
            $this->services &&
            $this->filename ){
            return true;
        } else {
            return false;
        }
    }
    private function checkFile($file){
        if( file_exists(__DIR__ . '/' . $file) ){
            return $file;
        } else {
            #throw new Exception('File not found: ' . $file);
            return false;
        }
    }
    private function getTotal( Array $services ){
        $total = 0;
        foreach($services as $service){
            if(isset($service['price'])) $total += $service['price'];
        }
        return $total;
    }
    public function render(){
        ob_start();
        include $this->file;
        $output = ob_get_contents();
        // Close the buffer and clear the contents.
        ob_end_clean();
        return $output;
    }

}

?>