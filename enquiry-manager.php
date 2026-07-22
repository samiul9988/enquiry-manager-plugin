<?php
/**
 * Plugin Name: Enquiry Manager
 * Plugin URI: https://github.com/opencode/enquiry-manager
 * Description: A self-contained WordPress plugin for managing visitor enquiries submitted via a frontend form. Features REST API submission, admin dashboard with search/filter/pagination, status management, and email notifications.
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: OpenCode
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: enquiry-manager
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

define( 'EM_VERSION', '1.0.0' );
define( 'EM_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'EM_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'EM_DB_VERSION', '1.0' );
define( 'EM_REST_NAMESPACE', 'enquiry-manager/v1' );
define( 'EM_TABLE_SUFFIX', 'enquiries' );

require_once EM_PLUGIN_DIR . 'includes/class-em-plugin.php';

require_once EM_PLUGIN_DIR . 'includes/class-em-database.php';
register_activation_hook( __FILE__, array( 'EM_Database', 'activate_on_hook' ) );

function em_init() {
	EM_Plugin::instance();
}
add_action( 'plugins_loaded', 'em_init' );
