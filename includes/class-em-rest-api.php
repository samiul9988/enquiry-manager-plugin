<?php
/**
 * REST API handler class.
 *
 * Registers REST routes and handles enquiry submission.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

class EM_Rest_API {

	public function register_routes(): void {
		register_rest_route(
			EM_REST_NAMESPACE,
			'/enquiries',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'handle_submission' ),
				'permission_callback' => '__return_true',
				'args'                => $this->get_validation_args(),
			)
		);
	}

	private function get_validation_args(): array {
		return array(
			'name'    => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					$value = trim( $value );
					if ( empty( $value ) ) {
						return new WP_Error( 'em_invalid_name', __( 'Name is required.', 'enquiry-manager' ) );
					}
					if ( mb_strlen( $value ) > 255 ) {
						return new WP_Error( 'em_invalid_name', __( 'Name must be 255 characters or fewer.', 'enquiry-manager' ) );
					}
					return true;
				},
			),
			'email'   => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_email',
				'validate_callback' => function ( $value ) {
					$value = trim( $value );
					if ( empty( $value ) || ! is_email( $value ) ) {
						return new WP_Error( 'em_invalid_email', __( 'A valid email address is required.', 'enquiry-manager' ) );
					}
					if ( mb_strlen( $value ) > 255 ) {
						return new WP_Error( 'em_invalid_email', __( 'Email must be 255 characters or fewer.', 'enquiry-manager' ) );
					}
					return true;
				},
			),
			'phone'   => array(
				'required'          => false,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					$value = trim( $value );
					if ( empty( $value ) ) {
						return true;
					}
					if ( ! preg_match( '/^01[0-9]{9}$/', $value ) ) {
						return new WP_Error( 'em_invalid_phone', __( 'Phone number must be an 11-digit Bangladesh mobile number starting with 01 (e.g. 01712345678).', 'enquiry-manager' ) );
					}
					return true;
				},
			),
			'subject' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
				'validate_callback' => function ( $value ) {
					$value = trim( $value );
					if ( empty( $value ) ) {
						return new WP_Error( 'em_invalid_subject', __( 'Subject is required.', 'enquiry-manager' ) );
					}
					if ( mb_strlen( $value ) > 255 ) {
						return new WP_Error( 'em_invalid_subject', __( 'Subject must be 255 characters or fewer.', 'enquiry-manager' ) );
					}
					return true;
				},
			),
			'message' => array(
				'required'          => true,
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_textarea_field',
				'validate_callback' => function ( $value ) {
					$value = trim( $value );
					if ( empty( $value ) ) {
						return new WP_Error( 'em_invalid_message', __( 'Message is required.', 'enquiry-manager' ) );
					}
					if ( mb_strlen( $value ) < 10 ) {
						return new WP_Error( 'em_invalid_message', __( 'Message must be at least 10 characters.', 'enquiry-manager' ) );
					}
					if ( mb_strlen( $value ) > 10000 ) {
						return new WP_Error( 'em_invalid_message', __( 'Message must be 10000 characters or fewer.', 'enquiry-manager' ) );
					}
					return true;
				},
			),
		);
	}

	public function handle_submission( WP_REST_Request $request ): WP_REST_Response {
		$nonce = $request->get_header( 'X-WP-Nonce' );
		if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return $this->error_response( __( 'Security verification failed. Please refresh the page and try again.', 'enquiry-manager' ), 403 );
		}

		$name    = $request->get_param( 'name' );
		$email   = $request->get_param( 'email' );
		$phone   = $request->get_param( 'phone' );
		$subject = $request->get_param( 'subject' );
		$message = $request->get_param( 'message' );

		$name    = sanitize_text_field( wp_unslash( $name ) );
		$email   = sanitize_email( wp_unslash( $email ) );
		$phone   = sanitize_text_field( wp_unslash( $phone ) );
		$subject = sanitize_text_field( wp_unslash( $subject ) );
		$message = sanitize_textarea_field( wp_unslash( $message ) );

		$name    = trim( $name );
		$email   = trim( $email );
		$phone   = trim( $phone );
		$subject = trim( $subject );
		$message = trim( $message );

		if ( empty( $name ) ) {
			return $this->error_response( __( 'Name is required.', 'enquiry-manager' ), 400 );
		}
		if ( mb_strlen( $name ) > 255 ) {
			return $this->error_response( __( 'Name must be 255 characters or fewer.', 'enquiry-manager' ), 400 );
		}
		if ( empty( $email ) || ! is_email( $email ) ) {
			return $this->error_response( __( 'A valid email address is required.', 'enquiry-manager' ), 400 );
		}
		if ( mb_strlen( $email ) > 255 ) {
			return $this->error_response( __( 'Email must be 255 characters or fewer.', 'enquiry-manager' ), 400 );
		}
		if ( ! empty( $phone ) && ! preg_match( '/^01[0-9]{9}$/', $phone ) ) {
			return $this->error_response( __( 'Phone number must be an 11-digit Bangladesh mobile number starting with 01 (e.g. 01712345678).', 'enquiry-manager' ), 400 );
		}
		if ( empty( $subject ) ) {
			return $this->error_response( __( 'Subject is required.', 'enquiry-manager' ), 400 );
		}
		if ( mb_strlen( $subject ) > 255 ) {
			return $this->error_response( __( 'Subject must be 255 characters or fewer.', 'enquiry-manager' ), 400 );
		}
		if ( empty( $message ) ) {
			return $this->error_response( __( 'Message is required.', 'enquiry-manager' ), 400 );
		}
		if ( mb_strlen( $message ) < 10 ) {
			return $this->error_response( __( 'Message must be at least 10 characters.', 'enquiry-manager' ), 400 );
		}
		if ( mb_strlen( $message ) > 10000 ) {
			return $this->error_response( __( 'Message must be 10000 characters or fewer.', 'enquiry-manager' ), 400 );
		}

		$submitted_ip = $this->get_client_ip();

		$database = new EM_Database();
		$insert_id = $database->insert(
			array(
				'name'         => $name,
				'email'        => $email,
				'phone'        => $phone,
				'subject'      => $subject,
				'message'      => $message,
				'submitted_ip' => $submitted_ip,
			)
		);

		if ( 0 === $insert_id ) {
			error_log( '[Enquiry Manager] Database insert failed.' );
			return $this->error_response( __( 'An unexpected error occurred. Please try again later.', 'enquiry-manager' ), 500 );
		}

		$this->send_notification( $insert_id, $name, $email, $phone, $subject, $message );

		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => __( 'Your enquiry has been submitted successfully.', 'enquiry-manager' ),
			),
			201
		);
	}

	private function get_client_ip(): string {
		$ip = '';

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		if ( ! empty( $ip ) && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6 ) ) {
			return $ip;
		}

		return '0.0.0.0';
	}

	private function send_notification( int $id, string $name, string $email, string $phone, string $subject, string $message ): void {
		$notification_email = get_option( 'em_notification_email', get_option( 'admin_email' ) );

		if ( empty( $notification_email ) || ! is_email( $notification_email ) ) {
			return;
		}

		$site_name = wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );

		/* translators: %s: site name */
		$email_subject = sprintf( __( '[%s] New Enquiry Received', 'enquiry-manager' ), $site_name );

		$body  = __( 'A new enquiry has been submitted.', 'enquiry-manager' ) . "\n\n";
		$body .= str_repeat( '-', 50 ) . "\n\n";
		$body .= __( 'Name:', 'enquiry-manager' ) . ' ' . $name . "\n";
		$body .= __( 'Email:', 'enquiry-manager' ) . ' ' . $email . "\n";

		if ( ! empty( $phone ) ) {
			$body .= __( 'Phone:', 'enquiry-manager' ) . ' ' . $phone . "\n";
		}

		$body .= __( 'Subject:', 'enquiry-manager' ) . ' ' . $subject . "\n";
		$body .= __( 'Message:', 'enquiry-manager' ) . "\n" . $message . "\n\n";
		$body .= str_repeat( '-', 50 ) . "\n\n";
		$body .= __( 'Submission Date:', 'enquiry-manager' ) . ' ' . current_time( 'mysql' ) . "\n";
		$body .= __( 'Enquiry ID:', 'enquiry-manager' ) . ' #' . $id . "\n\n";

		$body .= sprintf(
			/* translators: %s: admin URL */
			__( 'View all enquiries: %s', 'enquiry-manager' ),
			admin_url( 'admin.php?page=em_enquiries' )
		) . "\n";

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $notification_email, $email_subject, $body, $headers );

		if ( ! $sent ) {
			error_log( '[Enquiry Manager] Notification email failed to send to: ' . $notification_email );
		}
	}

	private function error_response( string $message, int $status = 400 ): WP_REST_Response {
		return new WP_REST_Response(
			array(
				'success' => false,
				'message' => $message,
			),
			$status
		);
	}
}
