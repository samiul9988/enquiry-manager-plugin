<?php
/**
 * Uninstall handler for Enquiry Manager.
 *
 * Removes the custom database table and all plugin options/settings.
 * Only runs when the plugin is fully deleted, not on deactivation.
 *
 * @package EnquiryManager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

global $wpdb;

$table_name = $wpdb->prefix . 'enquiries';

$wpdb->query( "DROP TABLE IF EXISTS `{$table_name}`" );

delete_option( 'em_db_version' );
delete_option( 'em_notification_email' );
