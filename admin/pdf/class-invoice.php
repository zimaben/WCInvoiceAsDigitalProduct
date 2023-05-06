<?php
namespace rbtinv\admin;

require_once __DIR__  . '/dompdf/autoload.inc.php';
$path = preg_replace( '/wp-content.*$/', '', __DIR__ );
require_once $path . 'wp-load.php';

use Dompdf\Dompdf as PDF;
use Dompdf\Options as Options;

Class Invoice {
    public $invoice_id;
    public $booking_id;
    public $guest_name;
    public $payment_method;
    public $services;
    public $total;

    public function __construct( array $arguments = array(), $file = 'file-invoice.php' ) {
        $this->file = $this->checkFile($file);
        $this->invoice_id = isset($arguments['invoice_id']) ? $arguments['invoice_id'] : 1;
        $this->booking_id = isset($arguments['booking_id']) ? $arguments['booking_id'] : 0;
        $this->guest_name = isset($arguments['guest_name']) ? $arguments['guest_name'] : false;
        $this->guest_email = isset($arguments['guest_email']) ? $arguments['guest_email'] : false;
        $this->guest_phone = isset($arguments['guest_phone']) ? $arguments['guest_phone'] : false;
        $this->payment_method = isset($arguments['payment_method']) ? $arguments['payment_method'] : 'Cash';
        $this->pickupdate = isset($arguments['pickupdate']) ? $arguments['pickupdate'] : false;
        $this->filename = isset($arguments['filename']) ? $arguments['filename'] : $this->generateFileName();
        $this->services = isset($arguments['services']) ? $arguments['services'] : false;
        $this->total = $this->services ? $this->getTotal($this->services) : false;
        $this->ready = $this->file ? $this->checkArgs() : false;
        $this->additionalArgs( $arguments );
        $this->htmlString =$this->ready ? $this->render() : false;
        //$this->pdf = $this->htmlString ? $this->generatePDF() : false;
    }
    private function additionalArgs( $args = array() ){
        $already = array(
            'invoice_id',
            'booking_id',
            'guest_name',
            'guest_email',
            'guest_phone',
            'payment_method',
            'pickupdate',
            'services',
            'total',
            'ready',
            'htmlString',
            'pdf',
            'filename'
        );
        foreach($args as $key => $value){
            if( !in_array($key, $already) ) $this->$key = $value;
        }
    }
    private function generateFileName(){
        $filename = date( 'Y-m-d') . '_' . $this->guest_email . '.pdf';
        return $filename;
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
            $this->invoice_id && 
            $this->booking_id && 
            $this->guest_name && 
            $this->services ){
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