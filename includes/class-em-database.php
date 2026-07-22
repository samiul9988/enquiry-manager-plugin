<?php
/**
 * Database management class.
 *
 * Handles table creation on activation, schema versioning,
 * and provides CRUD helpers for the enquiries table.
 *
 * @package EnquiryManager
 */

defined( 'ABSPATH' ) || exit;

class EM_Database {

	public static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . EM_TABLE_SUFFIX;
	}

	public static function activate_on_hook(): void {
		$instance = new self();
		$instance->create_table();
		update_option( 'em_db_version', EM_DB_VERSION );
	}

	public function activate(): void {
		$this->create_table();
		update_option( 'em_db_version', EM_DB_VERSION );
	}

	private function create_table(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table      = self::table_name();
		$charset    = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE `{$table}` (
			`id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			`name` VARCHAR(255) NOT NULL DEFAULT '',
			`email` VARCHAR(255) NOT NULL DEFAULT '',
			`phone` VARCHAR(50) NOT NULL DEFAULT '',
			`subject` VARCHAR(255) NOT NULL DEFAULT '',
			`message` LONGTEXT NOT NULL,
			`status` VARCHAR(20) NOT NULL DEFAULT 'new',
			`submitted_ip` VARCHAR(45) NOT NULL DEFAULT '',
			`created_at` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			PRIMARY KEY  (`id`),
			KEY `idx_status` (`status`),
			KEY `idx_email` (`email`),
			KEY `idx_created_at` (`created_at`)
		) {$charset};";

		dbDelta( $sql );
	}

	public function insert( array $data ): int {
		global $wpdb;

		$result = $wpdb->insert(
			self::table_name(),
			array(
				'name'         => $data['name'],
				'email'        => $data['email'],
				'phone'        => $data['phone'] ?? '',
				'subject'      => $data['subject'],
				'message'      => $data['message'],
				'status'       => 'new',
				'submitted_ip' => $data['submitted_ip'] ?? '',
				'created_at'   => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		if ( false === $result ) {
			return 0;
		}

		return (int) $wpdb->insert_id;
	}

	public function get_by_id( int $id ): ?object {
		global $wpdb;

		$table = self::table_name();

		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM `{$table}` WHERE `id` = %d", $id )
		);

		return $row ?: null;
	}

	public function update_status( int $id, string $status ): bool {
		global $wpdb;

		$allowed = array( 'new', 'read', 'archived' );
		if ( ! in_array( $status, $allowed, true ) ) {
			return false;
		}

		$result = $wpdb->update(
			self::table_name(),
			array( 'status' => $status ),
			array( 'id' => $id ),
			array( '%s' ),
			array( '%d' )
		);

		return false !== $result;
	}

	public function delete( int $id ): bool {
		global $wpdb;

		$result = $wpdb->delete(
			self::table_name(),
			array( 'id' => $id ),
			array( '%d' )
		);

		return false !== $result && $result > 0;
	}

	public function get_all( array $args = array() ): array {
		global $wpdb;

		$defaults = array(
			'search'   => '',
			'status'   => '',
			'orderby'  => 'created_at',
			'order'    => 'DESC',
			'per_page' => 10,
			'page'     => 1,
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = self::table_name();

		$where    = array();
		$values = array();

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(`name` LIKE %s OR `email` LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$allowed_statuses = array( 'new', 'read', 'archived' );
			if ( in_array( $args['status'], $allowed_statuses, true ) ) {
				$where[]  = '`status` = %s';
				$values[] = $args['status'];
			}
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		$allowed_orderby = array( 'id', 'name', 'email', 'phone', 'subject', 'status', 'created_at' );
		$orderby = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order   = 'ASC' === strtoupper( $args['order'] ) ? 'ASC' : 'DESC';

		$offset = ( max( 1, (int) $args['page'] ) - 1 ) * max( 1, (int) $args['per_page'] );

		$sql = "SELECT * FROM `{$table}` {$where_clause} ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";

		$values[] = max( 1, (int) $args['per_page'] );
		$values[] = $offset;

		$prepared = $wpdb->prepare( $sql, $values );

		return $wpdb->get_results( $prepared );
	}

	public function count_all( array $args = array() ): int {
		global $wpdb;

		$defaults = array(
			'search' => '',
			'status' => '',
		);

		$args  = wp_parse_args( $args, $defaults );
		$table = self::table_name();

		$where    = array();
		$values = array();

		if ( ! empty( $args['search'] ) ) {
			$like = '%' . $wpdb->esc_like( $args['search'] ) . '%';
			$where[]  = '(`name` LIKE %s OR `email` LIKE %s)';
			$values[] = $like;
			$values[] = $like;
		}

		if ( ! empty( $args['status'] ) ) {
			$allowed_statuses = array( 'new', 'read', 'archived' );
			if ( in_array( $args['status'], $allowed_statuses, true ) ) {
				$where[]  = '`status` = %s';
				$values[] = $args['status'];
			}
		}

		$where_clause = '';
		if ( ! empty( $where ) ) {
			$where_clause = 'WHERE ' . implode( ' AND ', $where );
		}

		$sql = "SELECT COUNT(*) FROM `{$table}` {$where_clause}";

		if ( ! empty( $values ) ) {
			$sql = $wpdb->prepare( $sql, $values );
		}

		return (int) $wpdb->get_var( $sql );
	}
}
