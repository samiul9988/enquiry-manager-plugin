<?php
/**
 * Admin dashboard handler class.
 *
 * Registers admin menu pages, renders enquiry listing, detail view,
 * handles status updates, deletions, search, and filtering.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

class EM_Admin {

	public function add_menu_pages(): void {
		add_menu_page(
			__( 'Enquiries', 'enquiry-manager' ),
			__( 'Enquiries', 'enquiry-manager' ),
			'manage_options',
			'em_enquiries',
			array( $this, 'render_list_page' ),
			'dashicons-email-alt',
			30
		);

		add_submenu_page(
			'em_enquiries',
			__( 'All Enquiries', 'enquiry-manager' ),
			__( 'All Enquiries', 'enquiry-manager' ),
			'manage_options',
			'em_enquiries',
			array( $this, 'render_list_page' )
		);

		add_submenu_page(
			null,
			__( 'Enquiry Detail', 'enquiry-manager' ),
			__( 'Detail', 'enquiry-manager' ),
			'manage_options',
			'em_enquiry_detail',
			array( $this, 'render_detail_page' )
		);

		add_submenu_page(
			'em_enquiries',
			__( 'Settings', 'enquiry-manager' ),
			__( 'Settings', 'enquiry-manager' ),
			'manage_options',
			'em_settings',
			array( $this, 'render_settings_page' )
		);
	}

	public function enqueue_assets( string $hook_suffix ): void {
		$plugin_pages = array( 'em_enquiries', 'em_enquiry_detail', 'em_settings' );
		$is_plugin_page = false;
		foreach ( $plugin_pages as $page ) {
			if ( false !== strpos( $hook_suffix, $page ) ) {
				$is_plugin_page = true;
				break;
			}
		}
		if ( ! $is_plugin_page ) {
			return;
		}

		wp_enqueue_style(
			'em-admin',
			EM_PLUGIN_URL . 'admin/css/admin.css',
			array(),
			EM_VERSION
		);

		wp_enqueue_script(
			'em-admin',
			EM_PLUGIN_URL . 'admin/js/admin.js',
			array(),
			EM_VERSION,
			true
		);

		wp_localize_script(
			'em-admin',
			'EM_Admin',
			array(
				'strings' => array(
					'confirmDelete' => __( 'Are you sure you want to delete this enquiry? This action cannot be undone.', 'enquiry-manager' ),
				),
			)
		);
	}

	public function render_list_page(): void {
		$database     = new EM_Database();
		$status       = isset( $_GET['em_status'] ) ? sanitize_text_field( wp_unslash( $_GET['em_status'] ) ) : '';
		$search       = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
		$current_page = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$per_page     = 10;

		$args = array(
			'search'   => $search,
			'status'   => $status,
			'per_page' => $per_page,
			'page'     => $current_page,
		);

		$enquiries   = $database->get_all( $args );
		$total_items = $database->count_all( $args );
		$total_pages = max( 1, (int) ceil( $total_items / $per_page ) );

		$allowed_statuses = array(
			''         => __( 'All Statuses', 'enquiry-manager' ),
			'new'      => __( 'New', 'enquiry-manager' ),
			'read'     => __( 'Read', 'enquiry-manager' ),
			'archived' => __( 'Archived', 'enquiry-manager' ),
		);
		?>
		<div class="wrap">
			<h1 class="wp-heading-inline"><?php echo esc_html__( 'Enquiries', 'enquiry-manager' ); ?></h1>
			<hr class="wp-header-end" />

			<?php $this->render_admin_notices(); ?>

			<div class="em-admin-filters">
				<form method="get" action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>" class="em-filter-form">
					<input type="hidden" name="page" value="em_enquiries" />

					<select name="em_status" class="em-status-filter">
						<?php foreach ( $allowed_statuses as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>>
								<?php echo esc_html( $label ); ?>
							</option>
						<?php endforeach; ?>
					</select>

					<input
						type="search"
						name="s"
						value="<?php echo esc_attr( $search ); ?>"
						placeholder="<?php echo esc_attr__( 'Search by name or email...', 'enquiry-manager' ); ?>"
						class="em-search-input"
					/>

					<button type="submit" class="button">
						<?php echo esc_html__( 'Filter', 'enquiry-manager' ); ?>
					</button>

					<?php if ( ! empty( $search ) || ! empty( $status ) ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=em_enquiries' ) ); ?>" class="button">
							<?php echo esc_html__( 'Clear', 'enquiry-manager' ); ?>
						</a>
					<?php endif; ?>
				</form>
			</div>

			<table class="wp-list-table widefat fixed striped em-enquiries-table">
				<thead>
					<tr>
						<th scope="col" class="em-col-id">#</th>
						<th scope="col"><?php echo esc_html__( 'Name', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Email', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Phone', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Subject', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Status', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Date', 'enquiry-manager' ); ?></th>
						<th scope="col"><?php echo esc_html__( 'Actions', 'enquiry-manager' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( empty( $enquiries ) ) : ?>
						<tr>
							<td colspan="8">
								<?php echo esc_html__( 'No enquiries found.', 'enquiry-manager' ); ?>
							</td>
						</tr>
					<?php else : ?>
						<?php $row_num = ( $current_page - 1 ) * $per_page; ?>
						<?php foreach ( $enquiries as $enquiry ) : ?>
							<?php $row_num++; ?>
							<tr>
								<td><?php echo absint( $row_num ); ?></td>
								<td>
									<strong>
										<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'em_enquiry_detail', 'id' => $enquiry->id ), admin_url( 'admin.php' ) ) ); ?>">
											<?php echo esc_html( $enquiry->name ); ?>
										</a>
									</strong>
								</td>
								<td><?php echo esc_html( $enquiry->email ); ?></td>
								<td><?php echo esc_html( $enquiry->phone ); ?></td>
								<td><?php echo esc_html( $enquiry->subject ); ?></td>
								<td>
									<span class="em-status em-status-<?php echo esc_attr( $enquiry->status ); ?>">
										<?php echo esc_html( ucfirst( $enquiry->status ) ); ?>
									</span>
								</td>
								<td>
									<?php echo esc_html( mysql2date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $enquiry->created_at ) ); ?>
								</td>
								<td class="em-actions-cell">
									<?php
									$detail_url = add_query_arg(
										array(
											'page' => 'em_enquiry_detail',
											'id'   => $enquiry->id,
										),
										admin_url( 'admin.php' )
									);
									?>
									<a href="<?php echo esc_url( $detail_url ); ?>" class="button button-small">
										<?php echo esc_html__( 'View', 'enquiry-manager' ); ?>
									</a>

									<?php if ( 'new' === $enquiry->status ) : ?>
										<a href="<?php echo esc_url( $this->action_url( 'mark_read', $enquiry->id ) ); ?>" class="button button-small">
											<?php echo esc_html__( 'Mark Read', 'enquiry-manager' ); ?>
										</a>
									<?php endif; ?>

									<?php if ( 'read' === $enquiry->status ) : ?>
										<a href="<?php echo esc_url( $this->action_url( 'archive', $enquiry->id ) ); ?>" class="button button-small">
											<?php echo esc_html__( 'Archive', 'enquiry-manager' ); ?>
										</a>
									<?php endif; ?>

									<?php if ( 'archived' === $enquiry->status ) : ?>
										<a href="<?php echo esc_url( $this->action_url( 'mark_new', $enquiry->id ) ); ?>" class="button button-small">
											<?php echo esc_html__( 'Reopen', 'enquiry-manager' ); ?>
										</a>
									<?php endif; ?>

									<a href="<?php echo esc_url( $this->action_url( 'delete', $enquiry->id ) ); ?>" class="button button-small em-delete-btn">
										<?php echo esc_html__( 'Delete', 'enquiry-manager' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
				</tbody>
			</table>

			<?php if ( $total_pages > 1 ) : ?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						$base = add_query_arg(
							array(
								'page'      => 'em_enquiries',
								's'         => $search,
								'em_status' => $status,
							),
							admin_url( 'admin.php' )
						);

						$page_links = paginate_links(
							array(
								'base'      => $base . '&paged=%#%',
								'format'    => '',
								'prev_text' => '&laquo;',
								'next_text' => '&raquo;',
								'total'     => $total_pages,
								'current'   => $current_page,
								'type'      => 'array',
							)
						);

						if ( $page_links ) {
							$safe_links = array_map( 'em_wp_kses_swp', $page_links );
							echo wp_kses_post( implode( "\n", $safe_links ) );
						}
						?>
						<span class="displaying-num">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: total number of items */
									__( '%s items', 'enquiry-manager' ),
									number_format_i18n( $total_items )
								)
							);
							?>
						</span>
					</div>
				</div>
			<?php endif; ?>
		</div>
		<?php
	}

	public function render_detail_page(): void {
		$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;

		if ( 0 === $id ) {
			wp_die( esc_html__( 'Invalid enquiry ID.', 'enquiry-manager' ) );
		}

		$database = new EM_Database();
		$enquiry  = $database->get_by_id( $id );

		if ( null === $enquiry ) {
			wp_die( esc_html__( 'Enquiry not found.', 'enquiry-manager' ) );
		}

		$status_labels = array(
			'new'      => __( 'New', 'enquiry-manager' ),
			'read'     => __( 'Read', 'enquiry-manager' ),
			'archived' => __( 'Archived', 'enquiry-manager' ),
		);
		?>
		<div class="wrap">
			<h1>
				<?php
				echo esc_html(
					sprintf(
						/* translators: %d: enquiry ID */
						__( 'Enquiry #%d', 'enquiry-manager' ),
						$id
					)
				);
				?>
			</h1>

			<p>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=em_enquiries' ) ); ?>">
					&larr; <?php echo esc_html__( 'Back to Enquiries', 'enquiry-manager' ); ?>
				</a>
			</p>

			<?php $this->render_admin_notices(); ?>

			<div class="em-detail-card">
				<table class="form-table em-detail-table" role="presentation">
					<tr>
						<th scope="row"><?php echo esc_html__( 'Name', 'enquiry-manager' ); ?></th>
						<td><?php echo esc_html( $enquiry->name ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Email', 'enquiry-manager' ); ?></th>
						<td>
							<a href="mailto:<?php echo esc_attr( $enquiry->email ); ?>">
								<?php echo esc_html( $enquiry->email ); ?>
							</a>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Phone', 'enquiry-manager' ); ?></th>
						<td><?php echo esc_html( $enquiry->phone ?: '—' ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Subject', 'enquiry-manager' ); ?></th>
						<td><?php echo esc_html( $enquiry->subject ); ?></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Status', 'enquiry-manager' ); ?></th>
						<td>
							<span class="em-status em-status-<?php echo esc_attr( $enquiry->status ); ?>">
								<?php
								echo esc_html(
									$status_labels[ $enquiry->status ] ?? ucfirst( $enquiry->status )
								);
								?>
							</span>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Submitted IP', 'enquiry-manager' ); ?></th>
						<td><code><?php echo esc_html( $enquiry->submitted_ip ); ?></code></td>
					</tr>
					<tr>
						<th scope="row"><?php echo esc_html__( 'Submitted At', 'enquiry-manager' ); ?></th>
						<td>
							<?php
							echo esc_html(
								mysql2date(
									get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
									$enquiry->created_at
								)
							);
							?>
						</td>
					</tr>
				</table>

				<h2><?php echo esc_html__( 'Message', 'enquiry-manager' ); ?></h2>
				<div class="em-message-content"><?php echo nl2br( esc_html( trim( $enquiry->message ) ) ); ?></div>

				<div class="em-detail-actions">
					<?php if ( 'new' === $enquiry->status ) : ?>
						<a href="<?php echo esc_url( $this->action_url( 'mark_read', $id ) ); ?>" class="button button-primary">
							<?php echo esc_html__( 'Mark as Read', 'enquiry-manager' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( 'read' === $enquiry->status ) : ?>
						<a href="<?php echo esc_url( $this->action_url( 'archive', $id ) ); ?>" class="button">
							<?php echo esc_html__( 'Archive', 'enquiry-manager' ); ?>
						</a>
					<?php endif; ?>

					<?php if ( 'archived' === $enquiry->status ) : ?>
						<a href="<?php echo esc_url( $this->action_url( 'mark_new', $id ) ); ?>" class="button">
							<?php echo esc_html__( 'Reopen', 'enquiry-manager' ); ?>
						</a>
					<?php endif; ?>

					<a href="<?php echo esc_url( $this->action_url( 'delete', $id ) ); ?>" class="button button-link-delete em-delete-btn">
						<?php echo esc_html__( 'Delete Enquiry', 'enquiry-manager' ); ?>
					</a>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_settings_page(): void {
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'Enquiry Manager Settings', 'enquiry-manager' ); ?></h1>
			<?php settings_errors(); ?>

			<div class="em-settings-card">
				<h2><?php echo esc_html__( 'Shortcode', 'enquiry-manager' ); ?></h2>
				<p><?php echo esc_html__( 'Use this shortcode on any page or post to display the enquiry submission form:', 'enquiry-manager' ); ?></p>
				<div class="em-shortcode-box">
					<code class="em-shortcode-text">[enquiry_form]</code>
				</div>
				<p class="description">
					<?php echo esc_html__( 'Create a WordPress page, add', 'enquiry-manager' ); ?>
					<code>[enquiry_form]</code>
					<?php echo esc_html__( 'to the content, and publish the page. The form will appear with fields for Name, Email, Phone, Subject, and Message.', 'enquiry-manager' ); ?>
				</p>
			</div>

			<form method="post" action="options.php">
				<?php
				settings_fields( 'em_settings_group' );
				do_settings_sections( 'em_settings' );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	public function process_actions(): void {
		if ( ! isset( $_GET['em_action'] ) || ! isset( $_GET['em_id'] ) || ! isset( $_GET['_wpnonce'] ) ) {
			return;
		}

		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

		if ( ! in_array( $page, array( 'em_enquiries', 'em_enquiry_detail' ), true ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to perform this action.', 'enquiry-manager' ) );
		}

		$action = sanitize_text_field( wp_unslash( $_GET['em_action'] ) );
		$id     = absint( wp_unslash( $_GET['em_id'] ) );
		$nonce  = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'em_admin_action_' . $action . '_' . $id ) ) {
			wp_die( esc_html__( 'Security check failed. Please try again.', 'enquiry-manager' ) );
		}

		$database = new EM_Database();
		$enquiry  = $database->get_by_id( $id );

		if ( null === $enquiry ) {
			$redirect = add_query_arg(
				array(
					'page'      => 'em_enquiries',
					'em_notice' => 'not_found',
				),
				admin_url( 'admin.php' )
			);
			wp_safe_redirect( $redirect );
			exit;
		}

		$notice = '';

		switch ( $action ) {
			case 'mark_read':
				$database->update_status( $id, 'read' );
				$notice = 'status_updated';
				break;

			case 'archive':
				$database->update_status( $id, 'archived' );
				$notice = 'status_updated';
				break;

			case 'mark_new':
				$database->update_status( $id, 'new' );
				$notice = 'status_updated';
				break;

			case 'delete':
				$database->delete( $id );
				$notice = 'deleted';
				break;

			default:
				break;
		}

		$redirect = add_query_arg(
			array(
				'page'      => 'em_enquiries',
				'em_notice' => $notice,
			),
			admin_url( 'admin.php' )
		);
		wp_safe_redirect( $redirect );
		exit;
	}

	private function render_admin_notices(): void {
		if ( ! isset( $_GET['em_notice'] ) ) {
			return;
		}

		$notice = sanitize_text_field( wp_unslash( $_GET['em_notice'] ) );

		$messages = array(
			'status_updated' => array( 'success', __( 'Enquiry status updated successfully.', 'enquiry-manager' ) ),
			'deleted'        => array( 'success', __( 'Enquiry deleted successfully.', 'enquiry-manager' ) ),
			'not_found'      => array( 'error', __( 'Enquiry not found.', 'enquiry-manager' ) ),
			'settings_saved' => array( 'success', __( 'Settings saved successfully.', 'enquiry-manager' ) ),
		);

		if ( ! isset( $messages[ $notice ] ) ) {
			return;
		}

		list( $type, $message ) = $messages[ $notice ];

		printf(
			'<div class="notice notice-%s is-dismissible"><p>%s</p></div>',
			esc_attr( $type ),
			esc_html( $message )
		);
	}

	private function action_url( string $action, int $id ): string {
		$base = admin_url( 'admin.php' );

		return add_query_arg(
			array(
				'page'      => 'em_enquiries',
				'em_action' => $action,
				'em_id'     => $id,
				'_wpnonce'  => wp_create_nonce( 'em_admin_action_' . $action . '_' . $id ),
			),
			$base
		);
	}
}

/**
 * Helper: allow WordPress-safe HTML in pagination links for wp_kses_post.
 */
function em_wp_kses_swp( string $html ): string {
	$allowed = array(
		'a'    => array(
			'href'  => true,
			'class' => true,
			'title' => true,
		),
		'span' => array(
			'class'          => true,
			'aria-current'   => true,
			'aria-hidden'    => true,
			'aria-label'     => true,
		),
	);
	return wp_kses( $html, $allowed );
}
