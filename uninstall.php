<?php

// Make sure we don't expose any info if called directly
if (!defined('WP_UNINSTALL_PLUGIN')) {
    die();
}
global $wpdb;

// Options
$wpdb->query("DELETE FROM `$wpdb->options` WHERE `option_name` LIKE 'socb\_%';");


// Clear any cached data that has been removed
//wp_cache_flush();
?>