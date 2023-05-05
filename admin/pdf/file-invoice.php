<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Daily Drivers Bali Invoice</title>
    <link rel="stylesheet" href="pdf.css">
  </head>
  <body>
    <div class="invoiceBody">
        <table name="header" class="layout-table">
            <tbody>
                <tr>
                    <td></td> <td></td> <td style="text-align:right;"><div class="logo"></div></td>
                </tr>
                <tr>
                    <td style="vertical-align:top"><h1>Invoice</h1></td> <td></td> <td style="vertical-align:top"><h2>Bali Daily Drivers</h2></td>
                </tr>
                <tr>
                    <td><h3>Guest Name: <?php echo $this->client_name ?></h3></td>
                    <td></td>
                    <td><h4>E12 Umah D'Jimbaran</h4></td>
                </tr>
                <tr>
                    <td><h3>Guest Email: <?php echo $this->client_email ?></h3></td> 
                    <td></td> 
                    <td style="vertical-align:top"><h4>Jalan Tm XIV, Jimbaran</h4></td>
                </tr>
                <tr>
                    <td><h3>Guest Phone: <?php echo $this->client_phone ?></h3></td>
                    <td></td>
                    <td><h4>Bali, Indonesia</h4></td>
                </tr>
                <tr>
                    <td><h3>Invoice Date: <?php echo date("F j, Y"); ?></h3></td> <td></td> <td>Due Date: <?php echo "Day of Service" . '<br />' . 'Payment Method: Cash' #$this->payment_method ?></td>
                </tr>
                <tr>
                    <td></td> <td></td> <td>Booking Reference: <?php echo $this->booking_id ?></td>
                </tr>
            </tbody>
        </table>

        <table name="services">
            <thead>
                <tr>
                    <th>Date</th><th>Service</th><th>Price</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $total = 0;
                $counter = 0;
                if($this->services){
                    switch($this->services) {
                        case(isset($this->services['service_name'])):
                            $thisdate = new DateTime($this->pickupdate);
                            $thisdate = date_format($thisdate, 'Y-m-d H:i:s');
                            echo '<tr>';
                            echo '<td>' . ($counter + 1) . '. ' . $thisdate . '</td>';
                            echo '<td>' . $this->services['service_name'] . '</td>';
                            echo '<td>' . $this->services['price'] . '</td>';
                            echo '</tr>';
                            $total += (integer) $this->services['price'];
                            break;
                        case(is_array($this->services)):
                            foreach($this->services as $service){
                                if( isset($service['service_name']) ){
                                    $thisdate = new DateTime($this->pickupdate);
                                    $thisdate->modify("+${counter} day");
                                    $thisdate = date_format($thisdate, 'Y-m-d H:i:s');
                                    echo '<tr>';
                                    echo '<td>' . $this->pickupdate . '</td>';
                                    echo '<td>' . $service['service_name'] . '</td>';
                                    echo '<td>' . $service['price'] . '</td>';
                                    echo '</tr>';
                                    $total += (integer) $service['price'];
                                    $counter++;
                                }
                            }
                            break;
                        default : break;
                    }
                }

                ?>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                <td colspan="2"><h2>Total:</h2></td>
                <td><h2><?php echo $total ?></h2></td>
                </tr>
            </tfoot>
        </table>
    </div>
  </body>
</html>