<?php
/**
 * Logs and database helpers.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Logs and database helpers.
 */
final class RelayPress_Logs {

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
		$opts = RelayPress_Newsletter::get_options();
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
		return $wpdb->prefix . RelayPress_Newsletter::TABLE;
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

		$nonce        = wp_create_nonce( 'relaypress_logs_search' );
		$nonce_ok     = false;
		$search       = '';
		$paged        = 1;
		$per_page     = 30;
		$orderby      = 'created_at';
		$order        = 'desc';
		$per_page_set = array( 20, 30, 50, 100 );

		if ( isset( $_GET['_wpnonce'] ) ) {
			$submitted_nonce = sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) );
			$nonce_ok        = (bool) wp_verify_nonce( $submitted_nonce, 'relaypress_logs_search' );
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
			echo '<p>' . esc_html__( 'The logs table does not exist yet. Deactivate and reactivate the plugin to create it.', 'relaypress-newsletter' ) . '</p>';
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
			echo '<p>' . esc_html__( 'The table exists but columns could not be read.', 'relaypress-newsletter' ) . '</p>';
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
					$parts[]      = '`' . $col . '` LIKE %s';
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
			'id'                        => __( 'ID', 'relaypress-newsletter' ),
			'created_at'                => __( 'Date', 'relaypress-newsletter' ),
			'email'                     => __( 'Email', 'relaypress-newsletter' ),
			'accepted'                  => __( 'Consent', 'relaypress-newsletter' ),
			'mailrelay_http_code'       => __( 'Signup', 'relaypress-newsletter' ),
			'confirmation_requested_at' => __( 'Confirmation email', 'relaypress-newsletter' ),
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
					return '`' . $col . '`';
				},
				$select
			)
		);
		$orderby_sql = '`' . $orderby . '`';
		$order_sql   = ( 'asc' === $order ) ? 'ASC' : 'DESC';
		$sql         = "SELECT {$columns_sql} FROM {$table} {$where_sql} ORDER BY {$orderby_sql} {$order_sql} LIMIT %d OFFSET %d";
		$sql_args    = array_merge( $where_args, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, $sql_args ), ARRAY_A );

		if ( ! $rows ) {
			if ( '' !== $search ) {
				echo '<p>' . esc_html__( 'No records match your search.', 'relaypress-newsletter' ) . '</p>';
			} else {
				echo '<p>' . esc_html__( 'No records yet.', 'relaypress-newsletter' ) . '</p>';
			}
			return;
		}

		$has_confirm = in_array( 'confirmation_requested_at', $select, true ) || in_array( 'confirmation_http_code', $select, true );

		$current_page     = 'relaypress-newsletter-logs';
		$pagination_links = '';
		if ( $pages > 1 ) {
			$base_args = array(
				'page'     => $current_page,
				's'        => $search,
				'per_page' => $per_page,
				'_wpnonce' => $nonce,
				'orderby'  => $orderby,
				'order'    => $order,
			);

			$first_url = add_query_arg( array_merge( $base_args, array( 'paged' => 1 ) ), admin_url( 'admin.php' ) );
			$prev_url  = add_query_arg( array_merge( $base_args, array( 'paged' => max( 1, $paged - 1 ) ) ), admin_url( 'admin.php' ) );
			$next_url  = add_query_arg( array_merge( $base_args, array( 'paged' => min( $pages, $paged + 1 ) ) ), admin_url( 'admin.php' ) );
			$last_url  = add_query_arg( array_merge( $base_args, array( 'paged' => $pages ) ), admin_url( 'admin.php' ) );

			$pagination_links = '<span class="pagination-links">';
			if ( $paged > 1 ) {
				$pagination_links .= '<a class="first-page button" href="' . esc_url( $first_url ) . '"><span class="screen-reader-text">' . esc_html__( 'First page', 'relaypress-newsletter' ) . '</span><span aria-hidden="true">&laquo;</span></a>';
				$pagination_links .= '<a class="prev-page button" href="' . esc_url( $prev_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Previous page', 'relaypress-newsletter' ) . '</span><span aria-hidden="true">&lsaquo;</span></a>';
			} else {
				$pagination_links .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>';
				$pagination_links .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>';
			}
			// translators: %1$s: current page, %2$s: total pages.
			$pagination_links .= '<span class="paging-input">' . esc_html( sprintf( __( '%1$s of %2$s', 'relaypress-newsletter' ), (string) $paged, (string) $pages ) ) . '</span>';
			if ( $paged < $pages ) {
				$pagination_links .= '<a class="next-page button" href="' . esc_url( $next_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Next page', 'relaypress-newsletter' ) . '</span><span aria-hidden="true">&rsaquo;</span></a>';
				$pagination_links .= '<a class="last-page button" href="' . esc_url( $last_url ) . '"><span class="screen-reader-text">' . esc_html__( 'Last page', 'relaypress-newsletter' ) . '</span><span aria-hidden="true">&raquo;</span></a>';
			} else {
				$pagination_links .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>';
				$pagination_links .= '<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>';
			}
			$pagination_links .= '</span>';
		}

		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="' . esc_attr( $current_page ) . '">';
		echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
		echo '<div class="tablenav top">';
		echo '<div class="alignleft actions">';
		echo '<label class="screen-reader-text" for="per_page">' . esc_html__( 'Items per page', 'relaypress-newsletter' ) . '</label>';
		echo '<select name="per_page" id="per_page">';
		foreach ( $per_page_set as $size ) {
			echo '<option value="' . esc_attr( (string) $size ) . '"' . selected( $per_page, $size, false ) . '>' . esc_html( (string) $size ) . '</option>';
		}
		echo '</select>';
		submit_button( __( 'Apply', 'relaypress-newsletter' ), 'secondary', '', false );
		echo '</div>';
		echo '<div class="search-box">';
		echo '<label class="screen-reader-text" for="log-search-input">' . esc_html__( 'Search logs', 'relaypress-newsletter' ) . '</label>';
		echo '<input type="search" id="log-search-input" name="s" value="' . esc_attr( $search ) . '">';
		submit_button( __( 'Search', 'relaypress-newsletter' ), 'secondary', '', false, array( 'id' => 'search-submit' ) );
		echo '</div>';
		echo '<div class="tablenav-pages">';
		// translators: %1$s: number of items.
		echo '<span class="displaying-num">' . esc_html( sprintf( __( '%1$s items', 'relaypress-newsletter' ), (string) $total ) ) . '</span>';
		if ( $pagination_links ) {
			echo wp_kses_post( $pagination_links );
		}
		echo '</div>';
		echo '</div>';
		echo '</form>';

		$header_cols = array(
			'email'               => __( 'Email', 'relaypress-newsletter' ),
			'created_at'          => __( 'Date', 'relaypress-newsletter' ),
			'id'                  => __( 'ID', 'relaypress-newsletter' ),
			'accepted'            => __( 'Consent', 'relaypress-newsletter' ),
			'ip'                  => __( 'IP', 'relaypress-newsletter' ),
			'page_url'            => __( 'Source', 'relaypress-newsletter' ),
			'mailrelay_http_code' => __( 'Signup', 'relaypress-newsletter' ),
		);
		if ( $has_confirm ) {
			$header_cols['confirmation_requested_at'] = __( 'Confirmation email', 'relaypress-newsletter' );
		}

		$column_classes = array(
			'id'                        => 'column-id',
			'created_at'                => 'column-date',
			'email'                     => 'column-email column-primary',
			'accepted'                  => 'column-consent',
			'ip'                        => 'column-ip',
			'page_url'                  => 'column-source',
			'mailrelay_http_code'       => 'column-signup',
			'confirmation_requested_at' => 'column-confirmation',
		);

		echo '<div class="relaypress-logs-wrap">';
		echo '<table class="wp-list-table widefat fixed striped table-view-list relaypress-logs"><thead><tr>';
		foreach ( $header_cols as $col => $label ) {
			$col_class = $column_classes[ $col ];
			if ( isset( $sortable_columns[ $col ] ) ) {
				$is_sorted  = ( $orderby === $col );
				$next_order = ( $is_sorted && 'asc' === $order ) ? 'desc' : 'asc';
				$class      = $is_sorted ? 'sorted ' . $order . ' sortable' : 'sortable desc';
				$sr_text    = ( 'asc' === $next_order ) ? __( 'Sort ascending.', 'relaypress-newsletter' ) : __( 'Sort descending.', 'relaypress-newsletter' );
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
				$th_class   = trim( 'manage-column ' . $col_class . ' ' . $class );
				echo '<th scope="col" class="' . esc_attr( $th_class ) . '"' . ( $is_sorted ? ' aria-sort="' . esc_attr( ( 'asc' === $order ) ? 'ascending' : 'descending' ) . '"' : '' ) . '><a href="' . esc_url( $link ) . '"><span>' . esc_html( $label ) . '</span><span class="sorting-indicators"><span class="sorting-indicator asc" aria-hidden="true"></span><span class="sorting-indicator desc" aria-hidden="true"></span></span> <span class="screen-reader-text">' . esc_html( $sr_text ) . '</span></a></th>';
			} else {
				echo '<th scope="col" class="manage-column ' . esc_attr( $col_class ) . '">' . esc_html( $label ) . '</th>';
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

			$email = (string) ( $r['email'] ?? '' );
			echo '<tr>';
			echo '<td class="column-email column-primary" data-colname="' . esc_attr( (string) $header_cols['email'] ) . '">' . esc_html( $email ) . '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'relaypress-newsletter' ) . '</span></button></td>';
			echo '<td class="column-date" data-colname="' . esc_attr( (string) $header_cols['created_at'] ) . '">' . esc_html( (string) ( $r['created_at'] ?? '' ) ) . '</td>';
			echo '<td class="column-id" data-colname="' . esc_attr( (string) $header_cols['id'] ) . '">' . esc_html( (string) ( $r['id'] ?? '' ) ) . '</td>';
			echo '<td class="column-consent" data-colname="' . esc_attr( (string) $header_cols['accepted'] ) . '">' . esc_html( ( 1 === (int) ( $r['accepted'] ?? 0 ) ) ? __( 'yes', 'relaypress-newsletter' ) : __( 'no', 'relaypress-newsletter' ) ) . '</td>';
			echo '<td class="column-ip" data-colname="' . esc_attr( (string) $header_cols['ip'] ) . '"><code>' . esc_html( (string) $ip ) . '</code></td>';
			echo '<td class="column-source" data-colname="' . esc_attr( (string) $header_cols['page_url'] ) . '">' . esc_html( (string) ( $r['page_url'] ?? '' ) ) . '</td>';
			echo '<td class="column-signup" data-colname="' . esc_attr( (string) $header_cols['mailrelay_http_code'] ) . '">' . esc_html( $create ) . '</td>';
			if ( $has_confirm ) {
				echo '<td class="column-confirmation" data-colname="' . esc_attr( (string) $header_cols['confirmation_requested_at'] ) . '">' . esc_html( $confirm_text ) . '</td>';
			}
			echo '</tr>';
		}

		echo '</tbody></table>';
		echo '</div>';

		if ( $pagination_links ) {
			echo '<div class="tablenav bottom"><div class="tablenav-pages">';
			// translators: %1$s: number of items.
			echo '<span class="displaying-num">' . esc_html( sprintf( __( '%1$s items', 'relaypress-newsletter' ), (string) $total ) ) . '</span>';
			echo wp_kses_post( $pagination_links );
			echo '</div></div>';
		}

		if ( ! $has_confirm ) {
			echo '<p class="description">' . esc_html__( 'Confirmation columns do not exist yet. The plugin should add them automatically on activation (dbDelta).', 'relaypress-newsletter' ) . '</p>';
		}
	}

	/**
	 * Migrate legacy logs table to the RelayPress name if needed.
	 *
	 * @return void
	 */
	public static function maybe_migrate_legacy_table(): void {
		global $wpdb;
		$new_table = self::table_name();
		$old_table = $wpdb->prefix . 'uve_mr_newsletter_consent';

		if ( $new_table === $old_table ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$new_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $new_table ) );
		if ( $new_exists ) {
			return;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$old_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $old_table ) );
		if ( ! $old_exists ) {
			return;
		}

		$old_table_escaped = esc_sql( $old_table );
		$new_table_escaped = esc_sql( $new_table );
		$rename_sql        = 'RENAME TABLE `' . $old_table_escaped . '` TO `' . $new_table_escaped . '`';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$wpdb->query( $rename_sql );
	}

	/**
	 * Create or update the logs table.
	 *
	 * @return void
	 */
	public static function maybe_create_or_update_table(): void {
		global $wpdb;
		self::maybe_migrate_legacy_table();
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
            phone_raw TEXT NULL,
            phone_normalized VARCHAR(20) NULL,
            phone_valid TINYINT(1) NULL,
            phone_reason VARCHAR(40) NULL,
            phone_country VARCHAR(4) NULL,
            phone_confidence VARCHAR(10) NULL,
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
		$opts  = RelayPress_Newsletter::get_options();
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
			'phone_raw'                 => $row['phone_raw'] ?? null,
			'phone_normalized'          => $row['phone_normalized'] ?? null,
			'phone_valid'               => isset( $row['phone_valid'] ) ? (int) $row['phone_valid'] : null,
			'phone_reason'              => $row['phone_reason'] ?? null,
			'phone_country'             => $row['phone_country'] ?? null,
			'phone_confidence'          => $row['phone_confidence'] ?? null,
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
