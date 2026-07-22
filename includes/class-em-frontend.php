<?php
/**
 * Frontend handler class.
 *
 * Registers shortcode, enqueues assets, and renders the enquiry form.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

class EM_Frontend {

	public function register_shortcode(): void {
		add_shortcode( 'enquiry_form', array( $this, 'render_form' ) );
	}

	public function enqueue_assets(): void {
		global $post;

		if ( ! is_a( $post, 'WP_Post' ) ) {
			return;
		}

		if ( ! has_shortcode( $post->post_content, 'enquiry_form' ) ) {
			return;
		}

		wp_enqueue_style(
			'em-frontend',
			EM_PLUGIN_URL . 'public/css/frontend.css',
			array(),
			EM_VERSION
		);

		wp_enqueue_script(
			'em-frontend',
			EM_PLUGIN_URL . 'public/js/frontend.js',
			array(),
			EM_VERSION,
			true
		);

		wp_localize_script(
			'em-frontend',
			'EM_Frontend',
			array(
				'rest_url' => rest_url( EM_REST_NAMESPACE . '/enquiries' ),
				'nonce'    => wp_create_nonce( 'wp_rest' ),
				'strings'  => array(
					'submitting'      => __( 'Submitting...', 'enquiry-manager' ),
					'submit'          => __( 'Submit Enquiry', 'enquiry-manager' ),
					'successMessage'  => __( 'Your enquiry has been submitted successfully.', 'enquiry-manager' ),
					'genericError'    => __( 'An unexpected error occurred. Please try again.', 'enquiry-manager' ),
					'nameRequired'    => __( 'Name is required.', 'enquiry-manager' ),
					'nameTooLong'     => __( 'Name must be 255 characters or fewer.', 'enquiry-manager' ),
					'emailRequired'   => __( 'A valid email address is required.', 'enquiry-manager' ),
					'emailInvalid'    => __( 'A valid email address is required.', 'enquiry-manager' ),
					'emailTooLong'    => __( 'Email must be 255 characters or fewer.', 'enquiry-manager' ),
					'phoneTooLong'    => __( 'Phone number must be an 11-digit Bangladesh mobile number starting with 01 (e.g. 01712345678).', 'enquiry-manager' ),
					'phoneInvalid'    => __( 'Phone number must be an 11-digit Bangladesh mobile number starting with 01 (e.g. 01712345678).', 'enquiry-manager' ),
					'subjectRequired' => __( 'Subject is required.', 'enquiry-manager' ),
					'subjectTooLong'  => __( 'Subject must be 255 characters or fewer.', 'enquiry-manager' ),
					'messageRequired' => __( 'Message is required.', 'enquiry-manager' ),
					'messageTooShort' => __( 'Message must be at least 10 characters.', 'enquiry-manager' ),
					'messageTooLong'  => __( 'Message must be 10000 characters or fewer.', 'enquiry-manager' ),
				),
			)
		);
	}

	public function render_form(): string {
		static $rendered = false;

		if ( $rendered ) {
			return '';
		}

		$rendered = true;

		ob_start();
		?>
		<div class="em-enquiry-form-wrapper">
			<form id="em-enquiry-form" class="em-enquiry-form" novalidate>
				<div class="em-form-fields">
					<div class="em-form-row">
						<label for="em-name"><?php echo esc_html__( 'Name', 'enquiry-manager' ); ?> <span class="em-required">*</span></label>
						<input type="text" id="em-name" name="name" required maxlength="255" autocomplete="name" placeholder="<?php echo esc_attr__( 'Your full name', 'enquiry-manager' ); ?>" />
						<span class="em-error" id="em-error-name"></span>
					</div>

					<div class="em-form-row">
						<label for="em-email"><?php echo esc_html__( 'Email', 'enquiry-manager' ); ?> <span class="em-required">*</span></label>
						<input type="email" id="em-email" name="email" required maxlength="255" autocomplete="email" placeholder="<?php echo esc_attr__( 'your@email.com', 'enquiry-manager' ); ?>" />
						<span class="em-error" id="em-error-email"></span>
					</div>

					<div class="em-form-row">
						<label for="em-phone"><?php echo esc_html__( 'Phone', 'enquiry-manager' ); ?> <span class="em-optional">(<?php echo esc_html__( 'optional', 'enquiry-manager' ); ?>)</span></label>
						<input type="tel" id="em-phone" name="phone" maxlength="11" autocomplete="tel" placeholder="<?php echo esc_attr__( 'e.g. 01712345678', 'enquiry-manager' ); ?>" />
						<span class="em-error" id="em-error-phone"></span>
					</div>

					<div class="em-form-row">
						<label for="em-subject"><?php echo esc_html__( 'Subject', 'enquiry-manager' ); ?> <span class="em-required">*</span></label>
						<input type="text" id="em-subject" name="subject" required maxlength="255" placeholder="<?php echo esc_attr__( 'What is this regarding?', 'enquiry-manager' ); ?>" />
						<span class="em-error" id="em-error-subject"></span>
					</div>

					<div class="em-form-row">
						<label for="em-message"><?php echo esc_html__( 'Message', 'enquiry-manager' ); ?> <span class="em-required">*</span></label>
						<textarea id="em-message" name="message" required maxlength="10000" rows="5" placeholder="<?php echo esc_attr__( 'Please describe your enquiry in detail...', 'enquiry-manager' ); ?>"></textarea>
						<span class="em-error" id="em-error-message"></span>
					</div>
				</div>

				<div class="em-submit-row">
					<button type="submit" id="em-submit-btn" class="em-submit-btn">
						<?php echo esc_html__( 'Submit Enquiry', 'enquiry-manager' ); ?>
					</button>
				</div>

				<div id="em-form-notice" class="em-form-notice" style="display:none;"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}
}
