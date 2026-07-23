<?php
/**
 * Settings handler class.
 *
 * Registers plugin settings using the WordPress Settings API,
 * including the notification email address.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

class EM_Settings {

	public function register_settings(): void {
		register_setting(
			'em_settings_group',
			'em_notification_email',
			array(
				'type'              => 'string',
				'sanitize_callback' => array( $this, 'sanitize_notification_email' ),
				'default'           => get_option( 'admin_email' ),
			)
		);

		add_settings_section(
			'em_notification_section',
			__( 'Notification Settings', 'enquiry-manager' ),
			array( $this, 'render_notification_section_description' ),
			'em_settings'
		);

		add_settings_field(
			'em_notification_email',
			__( 'Notification Email Address', 'enquiry-manager' ),
			array( $this, 'render_notification_email_field' ),
			'em_settings',
			'em_notification_section'
		);
	}

	public function sanitize_notification_email( $value ): string {
		$value = sanitize_email( trim( $value ) );

		if ( empty( $value ) ) {
			add_settings_error(
				'em_notification_email',
				'em_invalid_email',
				__( 'Please enter a valid email address.', 'enquiry-manager' )
			);
			return get_option( 'em_notification_email', get_option( 'admin_email' ) );
		}

		return $value;
	}

	public function render_notification_section_description(): void {
		echo '<p>' . esc_html__( 'Configure where notification emails are sent when a new enquiry is submitted.', 'enquiry-manager' ) . '</p>';
	}

	public function render_notification_email_field(): void {
		$value = get_option( 'em_notification_email', get_option( 'admin_email' ) );
		?>
		<input
			type="email"
			name="em_notification_email"
			id="em_notification_email"
			value="<?php echo esc_attr( $value ); ?>"
			class="regular-text"
			maxlength="255"
		/>
		<p class="description">
			<?php esc_html_e( 'Enter the email address that will receive notifications for new enquiries. Defaults to the site admin email.', 'enquiry-manager' ); ?>
		</p>
		<p class="description">
			<?php esc_html_e( 'Note: WordPress email delivery relies on your hosting environment. You may need an SMTP plugin for reliable delivery.', 'enquiry-manager' ); ?>
		</p>
		<?php
	}
}
