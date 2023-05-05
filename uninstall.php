<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}
 #@Todo - delete tables
$option = self::text_domain . '-version';
\delete_option( $option );