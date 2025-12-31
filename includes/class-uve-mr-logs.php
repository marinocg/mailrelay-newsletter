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

		$nonce        = wp_create_nonce( 'uve_mr_logs_search' );
		$nonce_ok     = false;
		$search       = '';
		$paged        = 1;
		$per_page     = 30;
		$orderby      = 'created_at';
		$order        = 'desc';
		$per_page_set = array( 20, 30, 50, 100 );

		if ( isset( $_GET['_wpnonce'] ) ) {
			$submitted_nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			$nonce_ok        = (bool) wp_verify_nonce( $submitted_nonce, 'uve_mr_logs_search' );
		}

		if ( $nonce_ok ) {
			$search   = isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '';
			$paged    = isset( $_GET['paged'] ) ? max( 1, (int) $_GET['paged'] ) : 1;
			$per_page = isset( $_GET['per_page'] ) ? (int) $_GET['per_page'] : 30;
			$per_page = in_array( $per_page, $per_page_set, true ) ? $per_page : 30;
			$orderby  = isset( $_GET['orderby'] ) ? sanitize_key( wp_unslash( $_GET['orderby'] ) ) : 'created_at';
			$order    = isset( $_GET['order'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : 'desc';
		}

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

		$where_sql  = '';
		$where_args = array();
		if ( '' !== $search ) {
			$searchable = array( 'email', 'page_url', 'ip_raw', 'ip_hash' );
			$searchable = array_values( array_intersect( $searchable, $cols ) );
			if ( $searchable ) {
				$like       = '%' . $wpdb->esc_like( $search ) . '%';
				$parts      = array();
				$where_args = array();
				foreach ( $searchable as $col ) {
					$parts[]      = '`' . esc_sql( $col ) . '` LIKE %s';
					$where_args[] = $like;
				}
				$where_sql = 'WHERE (' . implode( ' OR ', $parts ) . ')';
			}
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where_sql}";
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$total = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_args ) );
		$pages = max( 1, (int) ceil( $total / $per_page ) );
		if ( $paged > $pages ) {
			$paged = $pages;
		}
		$offset = ( $paged - 1 ) * $per_page;

		$sortable_columns = array(
			'id'                        => __( 'ID', 'uve-mailrelay-newsletter' ),
			'created_at'                => __( 'Date', 'uve-mailrelay-newsletter' ),
			'email'                     => __( 'Email', 'uve-mailrelay-newsletter' ),
			'accepted'                  => __( 'Consent', 'uve-mailrelay-newsletter' ),
			'mailrelay_http_code'       => __( 'Signup', 'uve-mailrelay-newsletter' ),
			'confirmation_requested_at' => __( 'Confirmation email', 'uve-mailrelay-newsletter' ),
		);
		foreach ( array_keys( $sortable_columns ) as $col ) {
			if ( ! in_array( $col, $cols, true ) ) {
				unset( $sortable_columns[ $col ] );
			}
		}

		if ( ! isset( $sortable_columns[ $orderby ] ) ) {
			$orderby = 'created_at';
		}
		if ( ! in_array( $orderby, $cols, true ) ) {
			$orderby = 'created_at';
		}
		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = 'desc';
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
		$orderby_sql = '`' . esc_sql( $orderby ) . '`';
		$order_sql   = ( 'asc' === $order ) ? 'ASC' : 'DESC';
		$sql         = "SELECT {$columns_sql} FROM {$table} {$where_sql} ORDER BY {$orderby_sql} {$order_sql} LIMIT %d OFFSET %d";
		$sql_args    = array_merge( $where_args, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sql_args ), ARRAY_A );

		if ( ! $rows ) {
			if ( '' !== $search ) {
				echo '<p>' . esc_html__( 'No records match your search.', 'uve-mailrelay-newsletter' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'No records yet.', 'uve-mailrelay-newsletter' ) . '</p>';
			}
			return;
		}

		$has_confirm = in_array( 'confirmation_requested_at', $select, true ) || in_array( 'confirmation_http_code', $select, true );

		$current_page = 'uve-mr-newsletter-logs';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $current_page ) . '">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
		echo '<div class="tablenav top">';
		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="per_page">' . esc_html__( 'Items per page', 'uve-mailrelay-newsletter' ) . '</label>';
		echo '<select name="per_page" id="per_page">';
		foreach ( $per_page_set as $size ) {
			echo '<option value="' . esc_attr( (string) $size ) . '"' . selected( $per_page, $size, false ) . '>' . esc_html( (string) $size ) . '</option>';
		}
		echo '</select>';
		submit_button( __( 'Apply', 'uve-mailrelay-newsletter' ), 'secondary', '', false );
		echo '</div>';
		echo '<div class="search-box">';
		echo '<label class="screen-reader-text" for="log-search-input">' . esc_html__( 'Search logs', 'uve-mailrelay-newsletter' ) . '</label>';
		echo '<input type="search" id="log-search-input" name="s" value="' . esc_attr( $search ) . '">';
		submit_button( __( 'Search', 'uve-mailrelay-newsletter' ), 'secondary', '', false, array( 'id' => 'search-submit' ) );
		echo '</div>';
		echo '<div class="tablenav-pages">';
		// translators: %1$s: number of items.
		echo '<span class="displaying-num">' . esc_html( sprintf( __( '%1$s items', 'uve-mailrelay-newsletter' ), (string) $total ) ) . '</span>';
		echo '</div>';
		echo '</div>';
		echo '</form>';

		$header_cols = array(
			'id'                  => __( 'ID', 'uve-mailrelay-newsletter' ),
			'created_at'          => __( 'Date', 'uve-mailrelay-newsletter' ),
			'email'               => __( 'Email', 'uve-mailrelay-newsletter' ),
			'accepted'            => __( 'Consent', 'uve-mailrelay-newsletter' ),
			'ip'                  => __( 'IP', 'uve-mailrelay-newsletter' ),
			'page_url'            => __( 'Source', 'uve-mailrelay-newsletter' ),
			'mailrelay_http_code' => __( 'Signup', 'uve-mailrelay-newsletter' ),
		);
		if ( $has_confirm ) {
			$header_cols['confirmation_requested_at'] = __( 'Confirmation email', 'uve-mailrelay-newsletter' );
		}

		echo '<table class="wp-list-table widefat fixed striped table-view-list"><thead><tr>';
		foreach ( $header_cols as $col => $label ) {
			if ( isset( $sortable_columns[ $col ] ) ) {
				$is_sorted  = ( $orderby === $col );
				$next_order = ( $is_sorted && 'asc' === $order ) ? 'desc' : 'asc';
				$class      = $is_sorted ? 'sorted ' . $order : 'sortable desc';
				$link       = add_query_arg(
					array(
						'page'     => $current_page,
						's'        => $search,
						'per_page' => $per_page,
						'_wpnonce' => $nonce,
						'orderby'  => $col,
						'order'    => $next_order,
					),
					admin_url( 'admin.php' )
				);
				echo '<th scope="col" class="manage-column ' . esc_attr( $class ) . '"><a href="' . esc_url( $link ) . '"><span>' . esc_html( $label ) . '</span><span class="sorting-indicator"></span></a></th>';
			} else {
				echo '<th scope="col" class="manage-column">' . esc_html( $label ) . '</th>';
			}
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

		$pagination = paginate_links(
			array(
				'base'      => esc_url_raw(
					add_query_arg(
						array(
							'page'     => $current_page,
							's'        => $search,
							'per_page' => $per_page,
							'_wpnonce' => $nonce,
							'orderby'  => $orderby,
							'order'    => $order,
							'paged'    => '%#%',
						),
						admin_url( 'admin.php' )
					)
				),
				'format'    => '',
				'total'     => $pages,
				'current'   => $paged,
				'prev_text' => __( '&laquo; Previous', 'uve-mailrelay-newsletter' ),
				'next_text' => __( 'Next &raquo;', 'uve-mailrelay-newsletter' ),
				'type'      => 'array',
			)
		);
		if ( $pagination ) {
			echo '<div class="tablenav bottom"><div class="tablenav-pages">';
			// translators: %1$s: number of items.
			echo '<span class="displaying-num">' . esc_html( sprintf( __( '%1$s items', 'uve-mailrelay-newsletter' ), (string) $total ) ) . '</span>';
			echo '<span class="pagination-links">';
			foreach ( $pagination as $link ) {
				echo wp_kses_post( $link );
			}
			echo '</span></div></div>';
		}

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
