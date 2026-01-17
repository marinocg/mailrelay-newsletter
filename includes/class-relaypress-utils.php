<?php
/**
 * Utility helpers.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility helpers.
 */
final class RelayPress_Utils {
	/**
	 * Supported Mailrelay locales.
	 *
	 * @return string[]
	 */
	public static function supported_locales(): array {
		return array( 'en', 'es', 'pt-BR', 'fr', 'gl', 'ca', 'eu', 'it' );
	}

	/**
	 * Map locales to human-friendly labels.
	 *
	 * @return array<string,string>
	 */
	public static function locale_labels(): array {
		return array(
			'en'    => __( 'English', 'relaypress-newsletter' ),
			'es'    => __( 'Spanish', 'relaypress-newsletter' ),
			'pt-BR' => __( 'Portuguese (Brazil)', 'relaypress-newsletter' ),
			'fr'    => __( 'French', 'relaypress-newsletter' ),
			'gl'    => __( 'Galician', 'relaypress-newsletter' ),
			'ca'    => __( 'Catalan', 'relaypress-newsletter' ),
			'eu'    => __( 'Basque', 'relaypress-newsletter' ),
			'it'    => __( 'Italian', 'relaypress-newsletter' ),
		);
	}

	/**
	 * Country labels mapped to ISO 3166-1 alpha-2 codes.
	 *
	 * @return array<string,string>
	 */
	public static function country_options(): array {
		static $countries = null;
		if ( null === $countries ) {
			$countries = array(
				'AF' => __( 'Afghanistan', 'relaypress-newsletter' ),
				'AX' => __( 'Aland Islands', 'relaypress-newsletter' ),
				'AL' => __( 'Albania', 'relaypress-newsletter' ),
				'DZ' => __( 'Algeria', 'relaypress-newsletter' ),
				'AS' => __( 'American Samoa', 'relaypress-newsletter' ),
				'AD' => __( 'Andorra', 'relaypress-newsletter' ),
				'AO' => __( 'Angola', 'relaypress-newsletter' ),
				'AI' => __( 'Anguilla', 'relaypress-newsletter' ),
				'AQ' => __( 'Antarctica', 'relaypress-newsletter' ),
				'AG' => __( 'Antigua and Barbuda', 'relaypress-newsletter' ),
				'AR' => __( 'Argentina', 'relaypress-newsletter' ),
				'AM' => __( 'Armenia', 'relaypress-newsletter' ),
				'AW' => __( 'Aruba', 'relaypress-newsletter' ),
				'AU' => __( 'Australia', 'relaypress-newsletter' ),
				'AT' => __( 'Austria', 'relaypress-newsletter' ),
				'AZ' => __( 'Azerbaijan', 'relaypress-newsletter' ),
				'BS' => __( 'Bahamas', 'relaypress-newsletter' ),
				'BH' => __( 'Bahrain', 'relaypress-newsletter' ),
				'BD' => __( 'Bangladesh', 'relaypress-newsletter' ),
				'BB' => __( 'Barbados', 'relaypress-newsletter' ),
				'BY' => __( 'Belarus', 'relaypress-newsletter' ),
				'BE' => __( 'Belgium', 'relaypress-newsletter' ),
				'BZ' => __( 'Belize', 'relaypress-newsletter' ),
				'BJ' => __( 'Benin', 'relaypress-newsletter' ),
				'BM' => __( 'Bermuda', 'relaypress-newsletter' ),
				'BT' => __( 'Bhutan', 'relaypress-newsletter' ),
				'BO' => __( 'Bolivia', 'relaypress-newsletter' ),
				'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'relaypress-newsletter' ),
				'BA' => __( 'Bosnia and Herzegovina', 'relaypress-newsletter' ),
				'BW' => __( 'Botswana', 'relaypress-newsletter' ),
				'BV' => __( 'Bouvet Island', 'relaypress-newsletter' ),
				'BR' => __( 'Brazil', 'relaypress-newsletter' ),
				'IO' => __( 'British Indian Ocean Territory', 'relaypress-newsletter' ),
				'BN' => __( 'Brunei Darussalam', 'relaypress-newsletter' ),
				'BG' => __( 'Bulgaria', 'relaypress-newsletter' ),
				'BF' => __( 'Burkina Faso', 'relaypress-newsletter' ),
				'BI' => __( 'Burundi', 'relaypress-newsletter' ),
				'KH' => __( 'Cambodia', 'relaypress-newsletter' ),
				'CM' => __( 'Cameroon', 'relaypress-newsletter' ),
				'CA' => __( 'Canada', 'relaypress-newsletter' ),
				'CV' => __( 'Cabo Verde', 'relaypress-newsletter' ),
				'KY' => __( 'Cayman Islands', 'relaypress-newsletter' ),
				'CF' => __( 'Central African Republic', 'relaypress-newsletter' ),
				'TD' => __( 'Chad', 'relaypress-newsletter' ),
				'CL' => __( 'Chile', 'relaypress-newsletter' ),
				'CN' => __( 'China', 'relaypress-newsletter' ),
				'CX' => __( 'Christmas Island', 'relaypress-newsletter' ),
				'CC' => __( 'Cocos (Keeling) Islands', 'relaypress-newsletter' ),
				'CO' => __( 'Colombia', 'relaypress-newsletter' ),
				'KM' => __( 'Comoros', 'relaypress-newsletter' ),
				'CG' => __( 'Congo', 'relaypress-newsletter' ),
				'CD' => __( 'Congo, Democratic Republic of the', 'relaypress-newsletter' ),
				'CK' => __( 'Cook Islands', 'relaypress-newsletter' ),
				'CR' => __( 'Costa Rica', 'relaypress-newsletter' ),
				'CI' => __( 'Cote d\'Ivoire', 'relaypress-newsletter' ),
				'HR' => __( 'Croatia', 'relaypress-newsletter' ),
				'CU' => __( 'Cuba', 'relaypress-newsletter' ),
				'CW' => __( 'Curacao', 'relaypress-newsletter' ),
				'CY' => __( 'Cyprus', 'relaypress-newsletter' ),
				'CZ' => __( 'Czechia', 'relaypress-newsletter' ),
				'DK' => __( 'Denmark', 'relaypress-newsletter' ),
				'DJ' => __( 'Djibouti', 'relaypress-newsletter' ),
				'DM' => __( 'Dominica', 'relaypress-newsletter' ),
				'DO' => __( 'Dominican Republic', 'relaypress-newsletter' ),
				'EC' => __( 'Ecuador', 'relaypress-newsletter' ),
				'EG' => __( 'Egypt', 'relaypress-newsletter' ),
				'SV' => __( 'El Salvador', 'relaypress-newsletter' ),
				'GQ' => __( 'Equatorial Guinea', 'relaypress-newsletter' ),
				'ER' => __( 'Eritrea', 'relaypress-newsletter' ),
				'EE' => __( 'Estonia', 'relaypress-newsletter' ),
				'SZ' => __( 'Eswatini', 'relaypress-newsletter' ),
				'ET' => __( 'Ethiopia', 'relaypress-newsletter' ),
				'FK' => __( 'Falkland Islands (Malvinas)', 'relaypress-newsletter' ),
				'FO' => __( 'Faroe Islands', 'relaypress-newsletter' ),
				'FJ' => __( 'Fiji', 'relaypress-newsletter' ),
				'FI' => __( 'Finland', 'relaypress-newsletter' ),
				'FR' => __( 'France', 'relaypress-newsletter' ),
				'GF' => __( 'French Guiana', 'relaypress-newsletter' ),
				'PF' => __( 'French Polynesia', 'relaypress-newsletter' ),
				'TF' => __( 'French Southern Territories', 'relaypress-newsletter' ),
				'GA' => __( 'Gabon', 'relaypress-newsletter' ),
				'GM' => __( 'Gambia', 'relaypress-newsletter' ),
				'GE' => __( 'Georgia', 'relaypress-newsletter' ),
				'DE' => __( 'Germany', 'relaypress-newsletter' ),
				'GH' => __( 'Ghana', 'relaypress-newsletter' ),
				'GI' => __( 'Gibraltar', 'relaypress-newsletter' ),
				'GR' => __( 'Greece', 'relaypress-newsletter' ),
				'GL' => __( 'Greenland', 'relaypress-newsletter' ),
				'GD' => __( 'Grenada', 'relaypress-newsletter' ),
				'GP' => __( 'Guadeloupe', 'relaypress-newsletter' ),
				'GU' => __( 'Guam', 'relaypress-newsletter' ),
				'GT' => __( 'Guatemala', 'relaypress-newsletter' ),
				'GG' => __( 'Guernsey', 'relaypress-newsletter' ),
				'GN' => __( 'Guinea', 'relaypress-newsletter' ),
				'GW' => __( 'Guinea-Bissau', 'relaypress-newsletter' ),
				'GY' => __( 'Guyana', 'relaypress-newsletter' ),
				'HT' => __( 'Haiti', 'relaypress-newsletter' ),
				'HM' => __( 'Heard Island and McDonald Islands', 'relaypress-newsletter' ),
				'VA' => __( 'Holy See', 'relaypress-newsletter' ),
				'HN' => __( 'Honduras', 'relaypress-newsletter' ),
				'HK' => __( 'Hong Kong', 'relaypress-newsletter' ),
				'HU' => __( 'Hungary', 'relaypress-newsletter' ),
				'IS' => __( 'Iceland', 'relaypress-newsletter' ),
				'IN' => __( 'India', 'relaypress-newsletter' ),
				'ID' => __( 'Indonesia', 'relaypress-newsletter' ),
				'IR' => __( 'Iran, Islamic Republic of', 'relaypress-newsletter' ),
				'IQ' => __( 'Iraq', 'relaypress-newsletter' ),
				'IE' => __( 'Ireland', 'relaypress-newsletter' ),
				'IM' => __( 'Isle of Man', 'relaypress-newsletter' ),
				'IL' => __( 'Israel', 'relaypress-newsletter' ),
				'IT' => __( 'Italy', 'relaypress-newsletter' ),
				'JM' => __( 'Jamaica', 'relaypress-newsletter' ),
				'JP' => __( 'Japan', 'relaypress-newsletter' ),
				'JE' => __( 'Jersey', 'relaypress-newsletter' ),
				'JO' => __( 'Jordan', 'relaypress-newsletter' ),
				'KZ' => __( 'Kazakhstan', 'relaypress-newsletter' ),
				'KE' => __( 'Kenya', 'relaypress-newsletter' ),
				'KI' => __( 'Kiribati', 'relaypress-newsletter' ),
				'KP' => __( 'Korea, Democratic People\'s Republic of', 'relaypress-newsletter' ),
				'KR' => __( 'Korea, Republic of', 'relaypress-newsletter' ),
				'KW' => __( 'Kuwait', 'relaypress-newsletter' ),
				'KG' => __( 'Kyrgyzstan', 'relaypress-newsletter' ),
				'LA' => __( 'Lao People\'s Democratic Republic', 'relaypress-newsletter' ),
				'LV' => __( 'Latvia', 'relaypress-newsletter' ),
				'LB' => __( 'Lebanon', 'relaypress-newsletter' ),
				'LS' => __( 'Lesotho', 'relaypress-newsletter' ),
				'LR' => __( 'Liberia', 'relaypress-newsletter' ),
				'LY' => __( 'Libya', 'relaypress-newsletter' ),
				'LI' => __( 'Liechtenstein', 'relaypress-newsletter' ),
				'LT' => __( 'Lithuania', 'relaypress-newsletter' ),
				'LU' => __( 'Luxembourg', 'relaypress-newsletter' ),
				'MO' => __( 'Macao', 'relaypress-newsletter' ),
				'MG' => __( 'Madagascar', 'relaypress-newsletter' ),
				'MW' => __( 'Malawi', 'relaypress-newsletter' ),
				'MY' => __( 'Malaysia', 'relaypress-newsletter' ),
				'MV' => __( 'Maldives', 'relaypress-newsletter' ),
				'ML' => __( 'Mali', 'relaypress-newsletter' ),
				'MT' => __( 'Malta', 'relaypress-newsletter' ),
				'MH' => __( 'Marshall Islands', 'relaypress-newsletter' ),
				'MQ' => __( 'Martinique', 'relaypress-newsletter' ),
				'MR' => __( 'Mauritania', 'relaypress-newsletter' ),
				'MU' => __( 'Mauritius', 'relaypress-newsletter' ),
				'YT' => __( 'Mayotte', 'relaypress-newsletter' ),
				'MX' => __( 'Mexico', 'relaypress-newsletter' ),
				'FM' => __( 'Micronesia, Federated States of', 'relaypress-newsletter' ),
				'MD' => __( 'Moldova, Republic of', 'relaypress-newsletter' ),
				'MC' => __( 'Monaco', 'relaypress-newsletter' ),
				'MN' => __( 'Mongolia', 'relaypress-newsletter' ),
				'ME' => __( 'Montenegro', 'relaypress-newsletter' ),
				'MS' => __( 'Montserrat', 'relaypress-newsletter' ),
				'MA' => __( 'Morocco', 'relaypress-newsletter' ),
				'MZ' => __( 'Mozambique', 'relaypress-newsletter' ),
				'MM' => __( 'Myanmar', 'relaypress-newsletter' ),
				'NA' => __( 'Namibia', 'relaypress-newsletter' ),
				'NR' => __( 'Nauru', 'relaypress-newsletter' ),
				'NP' => __( 'Nepal', 'relaypress-newsletter' ),
				'NL' => __( 'Netherlands', 'relaypress-newsletter' ),
				'NC' => __( 'New Caledonia', 'relaypress-newsletter' ),
				'NZ' => __( 'New Zealand', 'relaypress-newsletter' ),
				'NI' => __( 'Nicaragua', 'relaypress-newsletter' ),
				'NE' => __( 'Niger', 'relaypress-newsletter' ),
				'NG' => __( 'Nigeria', 'relaypress-newsletter' ),
				'NU' => __( 'Niue', 'relaypress-newsletter' ),
				'NF' => __( 'Norfolk Island', 'relaypress-newsletter' ),
				'MK' => __( 'North Macedonia', 'relaypress-newsletter' ),
				'MP' => __( 'Northern Mariana Islands', 'relaypress-newsletter' ),
				'NO' => __( 'Norway', 'relaypress-newsletter' ),
				'OM' => __( 'Oman', 'relaypress-newsletter' ),
				'PK' => __( 'Pakistan', 'relaypress-newsletter' ),
				'PW' => __( 'Palau', 'relaypress-newsletter' ),
				'PS' => __( 'Palestine, State of', 'relaypress-newsletter' ),
				'PA' => __( 'Panama', 'relaypress-newsletter' ),
				'PG' => __( 'Papua New Guinea', 'relaypress-newsletter' ),
				'PY' => __( 'Paraguay', 'relaypress-newsletter' ),
				'PE' => __( 'Peru', 'relaypress-newsletter' ),
				'PH' => __( 'Philippines', 'relaypress-newsletter' ),
				'PN' => __( 'Pitcairn', 'relaypress-newsletter' ),
				'PL' => __( 'Poland', 'relaypress-newsletter' ),
				'PT' => __( 'Portugal', 'relaypress-newsletter' ),
				'PR' => __( 'Puerto Rico', 'relaypress-newsletter' ),
				'QA' => __( 'Qatar', 'relaypress-newsletter' ),
				'RE' => __( 'Reunion', 'relaypress-newsletter' ),
				'RO' => __( 'Romania', 'relaypress-newsletter' ),
				'RU' => __( 'Russian Federation', 'relaypress-newsletter' ),
				'RW' => __( 'Rwanda', 'relaypress-newsletter' ),
				'BL' => __( 'Saint Barthelemy', 'relaypress-newsletter' ),
				'SH' => __( 'Saint Helena, Ascension and Tristan da Cunha', 'relaypress-newsletter' ),
				'KN' => __( 'Saint Kitts and Nevis', 'relaypress-newsletter' ),
				'LC' => __( 'Saint Lucia', 'relaypress-newsletter' ),
				'MF' => __( 'Saint Martin (French part)', 'relaypress-newsletter' ),
				'PM' => __( 'Saint Pierre and Miquelon', 'relaypress-newsletter' ),
				'VC' => __( 'Saint Vincent and the Grenadines', 'relaypress-newsletter' ),
				'WS' => __( 'Samoa', 'relaypress-newsletter' ),
				'SM' => __( 'San Marino', 'relaypress-newsletter' ),
				'ST' => __( 'Sao Tome and Principe', 'relaypress-newsletter' ),
				'SA' => __( 'Saudi Arabia', 'relaypress-newsletter' ),
				'SN' => __( 'Senegal', 'relaypress-newsletter' ),
				'RS' => __( 'Serbia', 'relaypress-newsletter' ),
				'SC' => __( 'Seychelles', 'relaypress-newsletter' ),
				'SL' => __( 'Sierra Leone', 'relaypress-newsletter' ),
				'SG' => __( 'Singapore', 'relaypress-newsletter' ),
				'SX' => __( 'Sint Maarten (Dutch part)', 'relaypress-newsletter' ),
				'SK' => __( 'Slovakia', 'relaypress-newsletter' ),
				'SI' => __( 'Slovenia', 'relaypress-newsletter' ),
				'SB' => __( 'Solomon Islands', 'relaypress-newsletter' ),
				'SO' => __( 'Somalia', 'relaypress-newsletter' ),
				'ZA' => __( 'South Africa', 'relaypress-newsletter' ),
				'GS' => __( 'South Georgia and the South Sandwich Islands', 'relaypress-newsletter' ),
				'SS' => __( 'South Sudan', 'relaypress-newsletter' ),
				'ES' => __( 'Spain', 'relaypress-newsletter' ),
				'LK' => __( 'Sri Lanka', 'relaypress-newsletter' ),
				'SD' => __( 'Sudan', 'relaypress-newsletter' ),
				'SR' => __( 'Suriname', 'relaypress-newsletter' ),
				'SJ' => __( 'Svalbard and Jan Mayen', 'relaypress-newsletter' ),
				'SE' => __( 'Sweden', 'relaypress-newsletter' ),
				'CH' => __( 'Switzerland', 'relaypress-newsletter' ),
				'SY' => __( 'Syrian Arab Republic', 'relaypress-newsletter' ),
				'TW' => __( 'Taiwan, Province of China', 'relaypress-newsletter' ),
				'TJ' => __( 'Tajikistan', 'relaypress-newsletter' ),
				'TZ' => __( 'Tanzania, United Republic of', 'relaypress-newsletter' ),
				'TH' => __( 'Thailand', 'relaypress-newsletter' ),
				'TL' => __( 'Timor-Leste', 'relaypress-newsletter' ),
				'TG' => __( 'Togo', 'relaypress-newsletter' ),
				'TK' => __( 'Tokelau', 'relaypress-newsletter' ),
				'TO' => __( 'Tonga', 'relaypress-newsletter' ),
				'TT' => __( 'Trinidad and Tobago', 'relaypress-newsletter' ),
				'TN' => __( 'Tunisia', 'relaypress-newsletter' ),
				'TR' => __( 'Turkey', 'relaypress-newsletter' ),
				'TM' => __( 'Turkmenistan', 'relaypress-newsletter' ),
				'TC' => __( 'Turks and Caicos Islands', 'relaypress-newsletter' ),
				'TV' => __( 'Tuvalu', 'relaypress-newsletter' ),
				'UG' => __( 'Uganda', 'relaypress-newsletter' ),
				'UA' => __( 'Ukraine', 'relaypress-newsletter' ),
				'AE' => __( 'United Arab Emirates', 'relaypress-newsletter' ),
				'GB' => __( 'United Kingdom', 'relaypress-newsletter' ),
				'US' => __( 'United States', 'relaypress-newsletter' ),
				'UM' => __( 'United States Minor Outlying Islands', 'relaypress-newsletter' ),
				'UY' => __( 'Uruguay', 'relaypress-newsletter' ),
				'UZ' => __( 'Uzbekistan', 'relaypress-newsletter' ),
				'VU' => __( 'Vanuatu', 'relaypress-newsletter' ),
				'VE' => __( 'Venezuela, Bolivarian Republic of', 'relaypress-newsletter' ),
				'VN' => __( 'Viet Nam', 'relaypress-newsletter' ),
				'VG' => __( 'Virgin Islands, British', 'relaypress-newsletter' ),
				'VI' => __( 'Virgin Islands, U.S.', 'relaypress-newsletter' ),
				'WF' => __( 'Wallis and Futuna', 'relaypress-newsletter' ),
				'EH' => __( 'Western Sahara', 'relaypress-newsletter' ),
				'YE' => __( 'Yemen', 'relaypress-newsletter' ),
				'ZM' => __( 'Zambia', 'relaypress-newsletter' ),
				'ZW' => __( 'Zimbabwe', 'relaypress-newsletter' ),
			);
		}

		return apply_filters( 'relaypress_country_options', $countries );
	}

	/**
	 * Normalize a country code to ISO 3166-1 alpha-2.
	 *
	 * @param string $country Raw country code.
	 * @return string
	 */
	public static function normalize_country_code( string $country ): string {
		$country = strtoupper( trim( $country ) );
		if ( '' === $country ) {
			return '';
		}
		$countries = self::country_options();
		return array_key_exists( $country, $countries ) ? $country : '';
	}

	/**
	 * Normalize a locale to Mailrelay supported values.
	 *
	 * @param string $locale Raw locale.
	 * @return string
	 */
	public static function normalize_locale( string $locale ): string {
		$locale = trim( $locale );
		if ( '' === $locale ) {
			return '';
		}
		$locale = str_replace( '_', '-', $locale );
		$needle = strtolower( $locale );
		foreach ( self::supported_locales() as $supported ) {
			$supported_lower = strtolower( $supported );
			if ( $needle === $supported_lower ) {
				return $supported;
			}
			if ( 2 === strlen( $supported_lower ) && str_starts_with( $needle, $supported_lower . '-' ) ) {
				return $supported;
			}
		}
		return '';
	}

	/**
	 * Resolve a locale from an Accept-Language header.
	 *
	 * @param string $header Accept-Language header.
	 * @return string
	 */
	public static function locale_from_accept_language( string $header ): string {
		$header = trim( $header );
		if ( '' === $header ) {
			return '';
		}
		$candidates = array();
		foreach ( explode( ',', $header ) as $part ) {
			$part = trim( $part );
			if ( '' === $part ) {
				continue;
			}
			$quality = 1.0;
			if ( false !== strpos( $part, ';' ) ) {
				$bits = array_map( 'trim', explode( ';', $part ) );
				$part = $bits[0] ?? '';
				foreach ( $bits as $bit ) {
					if ( 0 === strpos( $bit, 'q=' ) ) {
						$quality = (float) substr( $bit, 2 );
					}
				}
			}
			if ( '' === $part ) {
				continue;
			}
			$candidates[] = array(
				'locale'  => $part,
				'quality' => $quality,
			);
		}
		if ( empty( $candidates ) ) {
			return '';
		}
		usort(
			$candidates,
			static function ( array $a, array $b ): int {
				if ( $a['quality'] === $b['quality'] ) {
					return 0;
				}
				return ( $a['quality'] < $b['quality'] ) ? 1 : -1;
			}
		);
		foreach ( $candidates as $candidate ) {
			$normalized = self::normalize_locale( (string) ( $candidate['locale'] ?? '' ) );
			if ( '' !== $normalized ) {
				return $normalized;
			}
		}
		return '';
	}

	/**
	 * Resolve the default locale fallback.
	 *
	 * @return string
	 */
	public static function default_locale_fallback(): string {
		$wp_locale  = function_exists( 'get_locale' ) ? (string) get_locale() : '';
		$normalized = self::normalize_locale( $wp_locale );
		return '' !== $normalized ? $normalized : 'en';
	}

	/**
	 * Build a safe back URL with extra query args.
	 *
	 * @param array $add_query Query arguments to add.
	 * @return string
	 */
	public static function safe_back_url( array $add_query ): string {
		$ref = wp_get_referer();
		if ( ! $ref ) {
			$ref = home_url( '/' );
		}
		$url = remove_query_arg( array_keys( $add_query ), $ref );
		return add_query_arg( $add_query, $url );
	}

	/**
	 * Parse comma-separated group IDs.
	 *
	 * @param string $raw Raw group IDs string.
	 * @return array
	 */
	public static function parse_group_ids( string $raw ): array {
		$parts = array_filter( array_map( 'trim', explode( ',', $raw ) ) );
		$ids   = array();
		foreach ( $parts as $p ) {
			$ids[] = (int) $p;
		}
		$ids = array_filter( $ids, fn( $v ) => $v > 0 );
		return array_values( array_unique( $ids ) );
	}

	/**
	 * Get the current URL.
	 *
	 * @return string
	 */
	public static function current_url(): string {
		$scheme = is_ssl() ? 'https' : 'http';
		$host   = sanitize_text_field( wp_unslash( $_SERVER['HTTP_HOST'] ?? '' ) );
		$uri    = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) );
		return esc_url_raw( $scheme . '://' . $host . $uri );
	}

	/**
	 * Get a safe page URL for logging.
	 *
	 * @param array $data Request data.
	 * @return string
	 */
	public static function safe_page_url_from_request( array $data ): string {
		$home_url  = home_url( '/' );
		$home_host = self::extract_host( $home_url );

		$raw_url = $data['relaypress_page_url'] ?? '';
		if ( is_array( $raw_url ) ) {
			$raw_url = '';
		}
		$candidate = sanitize_text_field( is_scalar( $raw_url ) ? wp_unslash( (string) $raw_url ) : '' );
		if ( $candidate ) {
			$c_host = self::extract_host( $candidate );
			if ( '' !== $c_host && '' !== $home_host && strtolower( $c_host ) === strtolower( $home_host ) ) {
				return esc_url_raw( $candidate );
			}
		}

		$ref = wp_get_referer();
		if ( $ref ) {
			$ref_host = self::extract_host( $ref );
			if ( '' !== $ref_host && '' !== $home_host && strtolower( $ref_host ) === strtolower( $home_host ) ) {
				return esc_url_raw( $ref );
			}
		}

		return $home_url;
	}

	/**
	 * Extract host from a URL using wp_parse_url.
	 *
	 * @param string $url URL string.
	 * @return string
	 */
	private static function extract_host( string $url ): string {
		$parsed = wp_parse_url( $url );
		if ( is_array( $parsed ) ) {
			return (string) ( $parsed['host'] ?? '' );
		}
		return '';
	}

	/**
	 * Get client IP address.
	 *
	 * @return string
	 */
	public static function get_client_ip(): string {
		$raw_candidates = array();

		if ( ! empty( $_SERVER['HTTP_CF_CONNECTING_IP'] ) && ! empty( $_SERVER['HTTP_CF_RAY'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CF_CONNECTING_IP'] ) );
		}

		if ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$xff = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			foreach ( explode( ',', $xff ) as $part ) {
				$raw_candidates[] = $part;
			}
		}

		if ( ! empty( $_SERVER['HTTP_X_REAL_IP'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_REAL_IP'] ) );
		}

		if ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$raw_candidates[] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$candidates = array();
		foreach ( $raw_candidates as $raw ) {
			$ip = trim( (string) $raw );
			$ip = preg_replace( '/[^0-9a-fA-F\.:]/', '', $ip );
			if ( ! $ip || false === filter_var( $ip, FILTER_VALIDATE_IP ) ) {
				continue;
			}
			$candidates[] = $ip;
		}

		foreach ( $candidates as $candidate ) {
			if ( false !== filter_var( $candidate, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE ) ) {
				return $candidate;
			}
		}

		return $candidates[0] ?? '';
	}

	/**
	 * Normalize common mojibake issues to UTF-8.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	public static function normalize_text( string $text ): string {
		if ( '' === $text ) {
			return $text;
		}

		$needs_fix = false;
		foreach ( array( 'Ã', 'Â', 'â' ) as $needle ) {
			if ( false !== strpos( $text, $needle ) ) {
				$needs_fix = true;
				break;
			}
		}

		if ( ! $needs_fix ) {
			return $text;
		}

		$fixed = $text;
		if ( function_exists( 'iconv' ) ) {
			$converted = iconv( 'ISO-8859-1', 'UTF-8//IGNORE', $text );
			if ( false !== $converted && '' !== $converted ) {
				$fixed = $converted;
			}
		}

		return $fixed;
	}

	/**
	 * Check whether the premium plugin is installed.
	 *
	 * @return bool
	 */
	public static function is_premium_installed(): bool {
		$class = (string) apply_filters( 'relaypress_premium_class', 'RelayPress_Premium' );
		return '' !== $class && class_exists( $class );
	}

	/**
	 * Apply a filter only when the premium plugin is installed.
	 *
	 * @param string $hook Filter name.
	 * @param mixed  $value Default value to filter.
	 * @param mixed  $default_if_missing Default value when premium is not installed.
	 * @return mixed
	 */
	public static function premium_filter( string $hook, $value, $default_if_missing = null ) {
		if ( ! self::is_premium_installed() ) {
			return null === $default_if_missing ? $value : $default_if_missing;
		}

		return apply_filters( $hook, $value );
	}

	/**
	 * Get the main plugin file path.
	 *
	 * @return string
	 */
	public static function plugin_file(): string {
		return __DIR__ . '/../class-relaypress-newsletter.php';
	}
}
