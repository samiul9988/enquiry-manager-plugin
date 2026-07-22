<?php
/**
 * Main plugin bootstrap class.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

final class EM_Plugin {

	private static $instance = null;

	private $database;
	private $rest_api;
	private $admin;
	private $settings;
	private $frontend;

	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		$this->load_dependencies();
		$this->init_components();
		$this->register_hooks();
	}

	private function load_dependencies(): void {
		require_once EM_PLUGIN_DIR . 'includes/class-em-database.php';
		require_once EM_PLUGIN_DIR . 'includes/class-em-rest-api.php';
		require_once EM_PLUGIN_DIR . 'includes/class-em-admin.php';
		require_once EM_PLUGIN_DIR . 'includes/class-em-settings.php';
		require_once EM_PLUGIN_DIR . 'includes/class-em-frontend.php';
	}

	private function init_components(): void {
		$this->database  = new EM_Database();
		$this->rest_api  = new EM_Rest_API();
		$this->admin     = new EM_Admin();
		$this->settings  = new EM_Settings();
		$this->frontend  = new EM_Frontend();
	}

	private function register_hooks(): void {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_action( 'rest_api_init', array( $this->rest_api, 'register_routes' ) );
		add_action( 'init', array( $this->frontend, 'register_shortcode' ) );
		add_action( 'wp_enqueue_scripts', array( $this->frontend, 'enqueue_assets' ) );
		add_action( 'admin_menu', array( $this->admin, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this->admin, 'enqueue_assets' ) );
		add_action( 'admin_init', array( $this->admin, 'process_actions' ) );
		add_action( 'admin_init', array( $this->settings, 'register_settings' ) );
	}

	public function load_textdomain(): void {
		load_plugin_textdomain(
			'enquiry-manager',
			false,
			dirname( plugin_basename( EM_PLUGIN_DIR . 'enquiry-manager.php' ) ) . '/languages'
		);
	}
}
