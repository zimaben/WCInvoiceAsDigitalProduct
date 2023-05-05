<?php 
namespace rbtgc\admin;
use \rbtGoogleCalendar as Plugin;

class PluginAjax extends Plugin {
    static $required_fields = array(
        'rbtgc_add_calendar' => array('calendar'),
        'rbtgc_remove_calendar' => array('calendar'),
        'rbtgc_set_timezone' => array('timezone'),
    );
    public static function run(){
        \add_action( 'wp_ajax_rbtgc_add_calendar', array(get_class(), 'rbtgc_add_calendar' ));
        \add_action( 'wp_ajax_rbtgc_remove_calendar', array(get_class(), 'rbtgc_remove_calendar' ));
        \add_action( 'wp_ajax_rbtgc_set_timezone', array(get_class(), 'rbtgc_set_timezone' ));
        \add_action( 'wp_ajax_rbtgc_link_calendar', array(get_class(), 'rbtgc_link_calendar'));
        \add_action( 'wp_ajax_rbtgc_test_features', array(get_class(), 'rbtgc_test_features' ));
    }
    private static function return_false( $message = 'There was a problem', $code = 400 ){
        echo json_encode(array('status' => $code, 'payload' => $message ));
        die();
    }
    private static function return_true( $message = 'Success', $code = 200 ){
        echo json_encode(array('status' => $code, 'payload' => $message ));
        die();
    }
    private static function checkrequest( $function ){
        if(!isset($_POST['nonce'])) self::return_false( 'Missing nonce' );
        $required = isset(self::$required_fields[$function]);
        if($required){
            foreach(self::$required_fields[$function] as $required){
                if(!isset($_POST[$required])) self::return_false( 'Missing required field');
            }
        }
        
        if( ! wp_verify_nonce( $_POST['nonce'], 'admin_js' ) )  self::return_false( 'Invalid nonce' );

        return true; 
    }
   # m55qqs0bi4or2iu8anfov3kuko@group.calendar.google.com
    public static function rbtgc_test_features(){
        /* boilerplate */
        $check = self::checkrequest( __FUNCTION__ );
        require_once Plugin::get_plugin_path() . 'sdk/Client.php';
        $Client = new \CalendarClient();
        if($Client->err) self::return_false( $Client->err );
        require_once Plugin::get_plugin_path() . 'sdk/Calendar.php';
        $Cal =\GCalendar::get_instance($Client->client, $Client->calendar);
        /* boilerplate end */
        require_once Plugin::get_plugin_path() . 'admin/model.php';

        #new event using function    public function newEvent($StartTimeString = null, $DurationInMinutes = null, $CalendarId = null, $arguments = array() ){
        $arguments['Name'] = 'Drive to Nusa Dua';
        $arguments['Location'] = 'Pink Coco Hotel';
        $arguments['UserEmail'] = 'test@example.com';

        $startTime = '2023/02/09 12:01:00';
        $endTime = '2023/02/09 16:00:00';
        // $Cal_ID =  'm55qqs0bi4or2iu8anfov3kuko@group.calendar.google.com'; //brio
        // $Cal_ID = 'icv7dskmnnadps7c2ub9chu8k4@group.calendar.google.com'; //avanza

        #CheckCalendars returns the ID of the first free calendar in the order of Google Calendar. It returns false if there are no free calendars
        $Cal_ID = $Cal->checkCalendarsFree($startTime, 240); //checkCalendarsFree takes duration as the second argument like newEvent
        if($Cal_ID) {
            error_log($Cal_ID);
            $Cal->newEvent($startTime, 240, $Cal_ID, $arguments);
           self::return_true("booked new event");
        } else {
            self::return_false("That time is already booked");
        }
        self::return_true("testsstsings");

    }

    public static function rbtgc_add_calendar(){
        /* tested */
        /* boilerplate */
        $check = self::checkrequest( __FUNCTION__ );
        require_once Plugin::get_plugin_path() . 'admin/model.php';
        $nextnum = Model::next_free_cal_number();
        if(!$nextnum) self::return_false('Cannot add more than 4 calendars');

        require_once Plugin::get_plugin_path() . 'sdk/Client.php';
        $Client = new \CalendarClient();
        if($Client->err) self::return_false( $Client->err );
        require_once Plugin::get_plugin_path() . 'sdk/Calendar.php';
        $Cal = \GCalendar::get_instance($Client->client, $Client->calendar);
        /* boilerplate end */
        
        
        $CalendarID = $Cal->createNewCalendar( $_POST['calendar'] );
        if($CalendarID) {
            Model::save_calendar( $CalendarID, $_POST['calendar'] );
            self::return_true( 'Created or retrieved calendar ID:'.$CalendarID .' - '. $_POST['calendar']);
        }
        self::return_false('Something may have went wrong creating the calendar. Check your Google Calendar Application');


    }

    public static function rbtgc_link_calendar(){
        /* tested */
        /* boilerplate */
        $check = self::checkrequest( __FUNCTION__ );
        require_once Plugin::get_plugin_path() . 'admin/model.php';
        $nextnum = Model::next_free_cal_number();
        if(!$nextnum) self::return_false('Cannot add more than 4 calendars');

        require_once Plugin::get_plugin_path() . 'sdk/Client.php';
        $Client = new \CalendarClient();
        if($Client->err) self::return_false( $Client->err );
        require_once Plugin::get_plugin_path() . 'sdk/Calendar.php';
        $Cal = \GCalendar::get_instance($Client->client, $Client->calendar);
        /* boilerplate end */
        
        $CalendarName = $Cal->getCalendarName( $_POST['calendar'] );
        if($CalendarName) {
            error_log("GOT NAME " . $CalendarName);
            Model::save_calendar( $_POST['calendar'], $CalendarName );
            self::return_true( 'Successfully linked calendar '.$CalendarName );
        }
        self::return_false('Something may have went wrong linking the calendar. Check your Google Calendar Application');

    }
    
    public static function  rbtgc_remove_calendar(){
        /* tested */
        /* boilerplate */
        $check = self::checkrequest( __FUNCTION__ );
        require_once Plugin::get_plugin_path() . 'admin/model.php';
        $Row = Model::get_calendar_by_id( $_POST['calendar'] );
        $Match = $Row ? $Row->option_name : false; //Ex: rbtgc_calendar_2_id
        $Calendar = $Match ? str_replace('_id', '', $Match) : false; //Ex: rbtgc_calendar
        $Name = $Calendar ? $Calendar . '_name' : false;
        if( $Match && $Calendar && $Name ){
            \delete_option( $Match );
            \delete_option( $Calendar );
            \delete_option( $Name );
            self::return_true("Successfully removed calendar " .$_POST['calendar'] . " from the application");
        } else {
            self::return_false("Could not remove calendar " .$_POST['calendar'] . " from the application");
        }
    }
    public static function rbtgc_set_timezone(){
        /* tested */
        $check = self::checkrequest( __FUNCTION__ );
        $newTZ = $_POST['timezone'];
        if( \update_option('rbtgc_timezone', $newTZ) ){
            self::return_true( 'Timezone changed' ); 
        } else {
            self::return_false( 'Something went wrong with the timezone request');
        }

    }

}
PluginAjax::run();