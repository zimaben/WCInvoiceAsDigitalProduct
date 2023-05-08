<?php
namespace rbtinv\admin;

Class Record {
    public $error = false;
    public $ready = false;
    public $first_step_ready = false;
    public $record;
    public function __construct( int $record_id = null) {
        global $wpdb;
        $this->tablename = $wpdb->prefix .'rbt_invoices';
        $this->getRecord($record_id);
    }
    private function checkNewRecordArgs( $args = array() ){
        $error = '';
        if(!isset($args['client_name']) || empty($args['client_name'])) $error.='Missing guest name. ';
        if(!isset($args['client_email']) || empty($args['client_email'])) $error.='Missing guest email. ';
        if(!isset($args['client_phone']) || empty($args['client_phone'])) $error.='Missing guest phone. ';
        if(!isset($args['pickup_location']) || empty($args['pickup_location'])) $error.='Missing pickup address. ';
        if(!isset($args['dropoff_location']) || empty($args['dropoff_location'])) $error.='Missing dropoff address. ';
        if(!isset($args['pickupdate']) || empty($args['pickupdate'])) $error.='Missing Pickup Date. ';
        if (strlen($error) > 0){
            $this->first_step_ready = false;
            return $error;
        }
        $this->first_step_ready = true; 
        return $args;
    }
    /* UPDATE 
    order_id,x
    file_name,
    payment_method,
    amt_paid,
    invoice_status,
    */
    private function isReady(){
        if($this->record->invoice_status === 'incomplete') return false;
        if(
            $this->record->invoice_id &&
            $this->record->order_id &&
            $this->record->guest_name &&
            $this->record->guest_email &&
            $this->record->payment_date &&
            $this->record->pickup_address &&
            $this->record->dropoff_address 

        ){
            $this->ready = true;
            return true;
        } else {
            $this->ready = false;
            return false;
        }
    }
    public function returnInvoiceArgs(){
        $order = \wc_get_order( $this->record->order_id );
        $services_array = array();
        foreach ( $order->get_items() as $item_id => $item ) {
            array_push($services_array, array(
                'service_name' => $item->get_name(),
                'price' => $item->get_total(),
            ));
         }
        $args = array(
            'invoice_id' => $this->record->invoice_id,
            'booking_id' => $this->record->order_id,
            'guest_name' => $this->record->guest_name,
            'guest_email' => $this->record->guest_email,
            'guest_phone' => $this->record->guest_phone,
            'payment_method' => $this->record->payment_method,
            'filename' => $this->record->file_name,
            'pickupdate'=> $this->record->pickup_date,
            'services' => $services_array,
        );
        return $args;
    }
    public function updateField( $field, $value ){
        if( ! $this->record ) return false;
        global $wpdb;
        $updated = $wpdb->query( $wpdb->prepare(
            "
            UPDATE $this->tablename
            SET $field = %s
            WHERE invoice_id = %d
            ",
            $value,
            $this->record->invoice_id
        ));
        if($updated){
            $this->getRecord($this->record->invoice_id);
            $this->isReady();
            return true;
        } else {
            $this->error = 'Error updating record';
            $this->isReady();
            return false;
        } 
    }
    private function getRecord( $record_id = null ){
        if( ! $record_id ) return false;
        global $wpdb;
        $record = $wpdb->get_row( $wpdb->prepare(
            "
            SELECT * FROM $this->tablename
            WHERE invoice_id = %d
            ",
            $record_id
        ));
        $this->record = $record;
        $this->isReady();
        return $record;
    }
    public function addNewRecord( $args = array() ){
        $args = $this->checkNewRecordArgs($args);
        if( ! is_array($args) ){
            $this->error = $args;
            return false;
        }
        global $wpdb;
        $inserted = $wpdb->query( $wpdb->prepare(
            "
            INSERT INTO $this->tablename
            (order_id, guest_name, guest_email, guest_phone, file_name, amt_paid, payment_method, pickup_date, payment_date, pickup_address, dropoff_address, invoice_status)
            VALUES (%d, %s, %s, %s, %s, %d, %s, %s, %s, %s, %s, %s)
            ",
            0,
            $args['client_name'],
            $args['client_email'],
            $args['client_phone'],
            date("Y-m-d"). '_' . $args['client_email'] . '.pdf',
            0,
            '',
            $args['pickupdate'],
            $args['pickupdate'],
            $args['pickup_location'],
            $args['dropoff_location'],
            'incomplete'
        ) );
        if($inserted){
            $this->getRecord($wpdb->insert_id);
            return $wpdb->insert_id;
        } else {
            $this->error = 'Error inserting record';
            return false;
        } 
    }

}

?>