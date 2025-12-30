<?php
/**
 * Logs and database helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs and database helpers.
 */
final class UVE_MR_Logs {

	/**
	 * Cron callback must return void.
	 *
	 * @return void
	 */
	public static function purge_old_logs_cron(): void {
		self::purge_old_logs( false );
	}

	/**
	 * Purge logs older than the retention window.
	 *
	 * @param bool $return_count Whether to return deleted row count.
	 * @return int|null
	 */
	public static function purge_old_logs( bool $return_count = false ) {
		$opts = UVE_Mailrelay_Newsletter::get_options();
		$days = max( 1, (int) $opts['retention_days'] );

		global $wpdb;
		$table = self::table_name();

		$cutoff_ts    = time() - ( $days * DAY_IN_SECONDS );
		$cutoff_local = gmdate( 'Y-m-d H:i:s', $cutoff_ts );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$deleted = $wpdb->query(
			$wpdb->prepare( 'DELETE FROM %i WHERE created_at < %s', $table, $cutoff_local )
		);

		if ( false === $deleted ) {
			return $return_count ? 0 : null;
		}

		return $return_count ? (int) $deleted : null;
	}

	/**
	 * Get the logs table name.
	 *
	 * @return string
	 */
	private static function table_name(): string {
		global $wpdb;
		return $wpdb->prefix . UVE_Mailrelay_Newsletter::TABLE;
	}

	/**
	 * Fetch existing table columns.
	 *
	 * @return array
	 */
	private static function get_table_columns(): array {
		global $wpdb;
		$table = self::table_name();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			return array();
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$cols = $wpdb->get_results( $wpdb->prepare( 'SHOW COLUMNS FROM %i', $table ), ARRAY_A );
		if ( ! is_array( $cols ) ) {
			return array();
		}
		$names = array();
		foreach ( $cols as $c ) {
			if ( ! empty( $c['Field'] ) ) {
				$names[] = (string) $c['Field'];
			}
		}
		return $names;
	}

	/**
	 * Render the logs table if available.
	 *
	 * @return void
	 */
	public static function render_logs_table_safe(): void {
		global $wpdb;
		$table = self::table_name();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists !== $table ) {
			echo '<p>' . esc_html__( 'The logs table does not exist yet. Deactivate and reactivate the plugin to create it.', 'uve-mailrelay-newsletter' ) . '</p>';
			return;
		}

		$cols = self::get_table_columns();

		// Build SELECT dynamically based on actual columns.
		$want   = array(
			'id',
			'created_at',
			'email',
			'accepted',
			'ip_hash',
			'ip_raw',
			'page_url',
			'mailrelay_http_code',
			'confirmation_requested_at',
			'confirmation_http_code',
		);
		$select = array();
		foreach ( $want as $c ) {
			if ( in_array( $c, $cols, true ) ) {
				$select[] = $c;
			}
		}

		if ( ! $select ) {
			echo '<p>' . esc_html__( 'The table exists but columns could not be read.', 'uve-mailrelay-newsletter' ) . '</p>';
			return;
		}

		$columns_sql = implode(
			',',
			array_map(
				static function ( $col ) {
					return '`' . esc_sql( $col ) . '`';
				},
				$select
			)
		);
		$sql         = "SELECT {$columns_sql} FROM {$table} ORDER BY id DESC LIMIT 30";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $sql, ARRAY_A );

		if ( ! $rows ) {
			echo '<p>' . esc_html__( 'No records yet.', 'uve-mailrelay-newsletter' ) . '</p>';
			return;
		}

		$has_confirm = in_array( 'confirmation_requested_at', $select, true ) || in_array( 'confirmation_http_code', $select, true );

		echo '<table class="widefat striped"><thead><tr>';
		echo '<th>' . esc_html__( 'ID', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'Date', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'Email', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'Consent', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'IP', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'Source', 'uve-mailrelay-newsletter' ) . '</th>';
		echo '<th>' . esc_html__( 'Signup', 'uve-mailrelay-newsletter' ) . '</th>';
		if ( $has_confirm ) {
			echo '<th>' . esc_html__( 'Confirmation email', 'uve-mailrelay-newsletter' ) . '</th>';
		}
		echo '</tr></thead><tbody>';

		foreach ( $rows as $r ) {
			$ip = $r['ip_raw'] ?? '';
			if ( ! $ip ) {
				$ip = $r['ip_hash'] ?? '';
			}

			$create = (string) ( $r['mailrelay_http_code'] ?? '' );

			$confirm_text = '-';
			if ( $has_confirm ) {
				$c_at   = (string) ( $r['confirmation_requested_at'] ?? '' );
				$c_code = (string) ( $r['confirmation_http_code'] ?? '' );
				if ( $c_at ) {
					$confirm_text = $c_at . ' / ' . $c_code;
				}
			}

			echo '<tr>';
			echo '<td>' . esc_html( (string) ( $r['id'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $r['created_at'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( (string) ( $r['email'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( ( 1 === (int) ( $r['accepted'] ?? 0 ) ) ? __( 'yes', 'uve-mailrelay-newsletter' ) : __( 'no', 'uve-mailrelay-newsletter' ) ) . '</td>';
			echo '<td><code>' . esc_html( (string) $ip ) . '</code></td>';
			echo '<td>' . esc_html( (string) ( $r['page_url'] ?? '' ) ) . '</td>';
			echo '<td>' . esc_html( $create ) . '</td>';
			if ( $has_confirm ) {
				echo '<td>' . esc_html( $confirm_text ) . '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody></table>';

		if ( ! $has_confirm ) {
			echo '<p class="description">' . esc_html__( 'Confirmation columns do not exist yet. The plugin should add them automatically on activation (dbDelta).', 'uve-mailrelay-newsletter' ) . '</p>';
		}
	}

	/**
	 * Create or update the logs table.
	 *
	 * @return void
	 */
	public static function maybe_create_or_update_table(): void {
		global $wpdb;
		$table   = self::table_name();
		$charset = $wpdb->get_charset_collate();

		// dbDelta is idempotent and will add missing columns/indexes when SQL changes.
		$sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            email VARCHAR(255) NOT NULL,
            accepted TINYINT(1) NOT NULL DEFAULT 0,
            accepted_at DATETIME NULL,
            page_url TEXT NULL,
            ip_hash VARCHAR(128) NULL,
            ip_raw VARCHAR(64) NULL,
            user_agent TEXT NULL,
            mailrelay_http_code INT NULL,
            mailrelay_response LONGTEXT NULL,
            confirmation_requested_at DATETIME NULL,
            confirmation_http_code INT NULL,
            confirmation_response LONGTEXT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY created_at (created_at),
            KEY email_idx (email),
            KEY confirmation_requested_at_idx (confirmation_requested_at),
            KEY ip_hash_idx (ip_hash)
        ) {$charset};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	/**
	 * Insert a consent log row using available columns.
	 *
	 * @param array $row Row data.
	 * @return void
	 */
	public static function store_consent_log_compatible( array $row ): void {
		global $wpdb;
		$opts  = UVE_Mailrelay_Newsletter::get_options();
		$table = self::table_name();

		$cols = self::get_table_columns();
		if ( ! $cols ) {
			return;
		}

		$ip      = (string) ( $row['ip'] ?? '' );
		$ip_hash = $ip ? hash_hmac( 'sha256', $ip, wp_salt( 'auth' ) ) : null;
		$ip_raw  = ( '1' === $opts['hash_ip'] ) ? null : $ip;

		$data = array(
			'email'                     => (string) $row['email'],
			'accepted'                  => (int) ( $row['accepted'] ?? 0 ),
			'accepted_at'               => $row['accepted_at'] ?? null,
			'page_url'                  => $row['page_url'] ?? null,
			'ip_hash'                   => $ip_hash,
			'ip_raw'                    => $ip_raw,
			'user_agent'                => (string) ( $row['user_agent'] ?? '' ),
			'mailrelay_http_code'       => isset( $row['mailrelay_http_code'] ) ? (int) $row['mailrelay_http_code'] : null,
			'mailrelay_response'        => isset( $row['mailrelay_response'] ) ? (string) $row['mailrelay_response'] : null,
			'confirmation_requested_at' => $row['confirmation_requested_at'] ?? null,
			'confirmation_http_code'    => isset( $row['confirmation_http_code'] ) ? (int) $row['confirmation_http_code'] : null,
			'confirmation_response'     => isset( $row['confirmation_response'] ) ? (string) $row['confirmation_response'] : null,
			'created_at'                => current_time( 'mysql' ),
		);

		// Keep only existing columns.
		$filtered = array();
		foreach ( $data as $k => $v ) {
			if ( in_array( $k, $cols, true ) ) {
				$filtered[ $k ] = $v;
			}
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( $table, $filtered );
	}
}
