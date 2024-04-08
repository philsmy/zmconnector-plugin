<?php
// if uninstall.php is not called by WordPress, die
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

// Delete the access_token from the options table
delete_option('zmconnector_access_token');

// For site options in Multisite
// delete_site_option('zmconnector_access_token');

// Additional cleanup operations here
