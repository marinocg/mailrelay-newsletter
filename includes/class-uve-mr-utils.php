<?php
/**
 * Utility helpers.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Utility helpers.
 */
final class UVE_MR_Utils {
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
			'en'    => __( 'English', 'uve-mailrelay-newsletter' ),
			'es'    => __( 'Spanish', 'uve-mailrelay-newsletter' ),
			'pt-BR' => __( 'Portuguese (Brazil)', 'uve-mailrelay-newsletter' ),
			'fr'    => __( 'French', 'uve-mailrelay-newsletter' ),
			'gl'    => __( 'Galician', 'uve-mailrelay-newsletter' ),
			'ca'    => __( 'Catalan', 'uve-mailrelay-newsletter' ),
			'eu'    => __( 'Basque', 'uve-mailrelay-newsletter' ),
			'it'    => __( 'Italian', 'uve-mailrelay-newsletter' ),
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
				'AF' => __( 'Afghanistan', 'uve-mailrelay-newsletter' ),
				'AX' => __( 'Aland Islands', 'uve-mailrelay-newsletter' ),
				'AL' => __( 'Albania', 'uve-mailrelay-newsletter' ),
				'DZ' => __( 'Algeria', 'uve-mailrelay-newsletter' ),
				'AS' => __( 'American Samoa', 'uve-mailrelay-newsletter' ),
				'AD' => __( 'Andorra', 'uve-mailrelay-newsletter' ),
				'AO' => __( 'Angola', 'uve-mailrelay-newsletter' ),
				'AI' => __( 'Anguilla', 'uve-mailrelay-newsletter' ),
				'AQ' => __( 'Antarctica', 'uve-mailrelay-newsletter' ),
				'AG' => __( 'Antigua and Barbuda', 'uve-mailrelay-newsletter' ),
				'AR' => __( 'Argentina', 'uve-mailrelay-newsletter' ),
				'AM' => __( 'Armenia', 'uve-mailrelay-newsletter' ),
				'AW' => __( 'Aruba', 'uve-mailrelay-newsletter' ),
				'AU' => __( 'Australia', 'uve-mailrelay-newsletter' ),
				'AT' => __( 'Austria', 'uve-mailrelay-newsletter' ),
				'AZ' => __( 'Azerbaijan', 'uve-mailrelay-newsletter' ),
				'BS' => __( 'Bahamas', 'uve-mailrelay-newsletter' ),
				'BH' => __( 'Bahrain', 'uve-mailrelay-newsletter' ),
				'BD' => __( 'Bangladesh', 'uve-mailrelay-newsletter' ),
				'BB' => __( 'Barbados', 'uve-mailrelay-newsletter' ),
				'BY' => __( 'Belarus', 'uve-mailrelay-newsletter' ),
				'BE' => __( 'Belgium', 'uve-mailrelay-newsletter' ),
				'BZ' => __( 'Belize', 'uve-mailrelay-newsletter' ),
				'BJ' => __( 'Benin', 'uve-mailrelay-newsletter' ),
				'BM' => __( 'Bermuda', 'uve-mailrelay-newsletter' ),
				'BT' => __( 'Bhutan', 'uve-mailrelay-newsletter' ),
				'BO' => __( 'Bolivia', 'uve-mailrelay-newsletter' ),
				'BQ' => __( 'Bonaire, Sint Eustatius and Saba', 'uve-mailrelay-newsletter' ),
				'BA' => __( 'Bosnia and Herzegovina', 'uve-mailrelay-newsletter' ),
				'BW' => __( 'Botswana', 'uve-mailrelay-newsletter' ),
				'BV' => __( 'Bouvet Island', 'uve-mailrelay-newsletter' ),
				'BR' => __( 'Brazil', 'uve-mailrelay-newsletter' ),
				'IO' => __( 'British Indian Ocean Territory', 'uve-mailrelay-newsletter' ),
				'BN' => __( 'Brunei Darussalam', 'uve-mailrelay-newsletter' ),
				'BG' => __( 'Bulgaria', 'uve-mailrelay-newsletter' ),
				'BF' => __( 'Burkina Faso', 'uve-mailrelay-newsletter' ),
				'BI' => __( 'Burundi', 'uve-mailrelay-newsletter' ),
				'KH' => __( 'Cambodia', 'uve-mailrelay-newsletter' ),
				'CM' => __( 'Cameroon', 'uve-mailrelay-newsletter' ),
				'CA' => __( 'Canada', 'uve-mailrelay-newsletter' ),
				'CV' => __( 'Cabo Verde', 'uve-mailrelay-newsletter' ),
				'KY' => __( 'Cayman Islands', 'uve-mailrelay-newsletter' ),
				'CF' => __( 'Central African Republic', 'uve-mailrelay-newsletter' ),
				'TD' => __( 'Chad', 'uve-mailrelay-newsletter' ),
				'CL' => __( 'Chile', 'uve-mailrelay-newsletter' ),
				'CN' => __( 'China', 'uve-mailrelay-newsletter' ),
				'CX' => __( 'Christmas Island', 'uve-mailrelay-newsletter' ),
				'CC' => __( 'Cocos (Keeling) Islands', 'uve-mailrelay-newsletter' ),
				'CO' => __( 'Colombia', 'uve-mailrelay-newsletter' ),
				'KM' => __( 'Comoros', 'uve-mailrelay-newsletter' ),
				'CG' => __( 'Congo', 'uve-mailrelay-newsletter' ),
				'CD' => __( 'Congo, Democratic Republic of the', 'uve-mailrelay-newsletter' ),
				'CK' => __( 'Cook Islands', 'uve-mailrelay-newsletter' ),
				'CR' => __( 'Costa Rica', 'uve-mailrelay-newsletter' ),
				'CI' => __( 'Cote d\'Ivoire', 'uve-mailrelay-newsletter' ),
				'HR' => __( 'Croatia', 'uve-mailrelay-newsletter' ),
				'CU' => __( 'Cuba', 'uve-mailrelay-newsletter' ),
				'CW' => __( 'Curacao', 'uve-mailrelay-newsletter' ),
				'CY' => __( 'Cyprus', 'uve-mailrelay-newsletter' ),
				'CZ' => __( 'Czechia', 'uve-mailrelay-newsletter' ),
				'DK' => __( 'Denmark', 'uve-mailrelay-newsletter' ),
				'DJ' => __( 'Djibouti', 'uve-mailrelay-newsletter' ),
				'DM' => __( 'Dominica', 'uve-mailrelay-newsletter' ),
				'DO' => __( 'Dominican Republic', 'uve-mailrelay-newsletter' ),
				'EC' => __( 'Ecuador', 'uve-mailrelay-newsletter' ),
				'EG' => __( 'Egypt', 'uve-mailrelay-newsletter' ),
				'SV' => __( 'El Salvador', 'uve-mailrelay-newsletter' ),
				'GQ' => __( 'Equatorial Guinea', 'uve-mailrelay-newsletter' ),
				'ER' => __( 'Eritrea', 'uve-mailrelay-newsletter' ),
				'EE' => __( 'Estonia', 'uve-mailrelay-newsletter' ),
				'SZ' => __( 'Eswatini', 'uve-mailrelay-newsletter' ),
				'ET' => __( 'Ethiopia', 'uve-mailrelay-newsletter' ),
				'FK' => __( 'Falkland Islands (Malvinas)', 'uve-mailrelay-newsletter' ),
				'FO' => __( 'Faroe Islands', 'uve-mailrelay-newsletter' ),
				'FJ' => __( 'Fiji', 'uve-mailrelay-newsletter' ),
				'FI' => __( 'Finland', 'uve-mailrelay-newsletter' ),
				'FR' => __( 'France', 'uve-mailrelay-newsletter' ),
				'GF' => __( 'French Guiana', 'uve-mailrelay-newsletter' ),
				'PF' => __( 'French Polynesia', 'uve-mailrelay-newsletter' ),
				'TF' => __( 'French Southern Territories', 'uve-mailrelay-newsletter' ),
				'GA' => __( 'Gabon', 'uve-mailrelay-newsletter' ),
				'GM' => __( 'Gambia', 'uve-mailrelay-newsletter' ),
				'GE' => __( 'Georgia', 'uve-mailrelay-newsletter' ),
				'DE' => __( 'Germany', 'uve-mailrelay-newsletter' ),
				'GH' => __( 'Ghana', 'uve-mailrelay-newsletter' ),
				'GI' => __( 'Gibraltar', 'uve-mailrelay-newsletter' ),
				'GR' => __( 'Greece', 'uve-mailrelay-newsletter' ),
				'GL' => __( 'Greenland', 'uve-mailrelay-newsletter' ),
				'GD' => __( 'Grenada', 'uve-mailrelay-newsletter' ),
				'GP' => __( 'Guadeloupe', 'uve-mailrelay-newsletter' ),
				'GU' => __( 'Guam', 'uve-mailrelay-newsletter' ),
				'GT' => __( 'Guatemala', 'uve-mailrelay-newsletter' ),
				'GG' => __( 'Guernsey', 'uve-mailrelay-newsletter' ),
				'GN' => __( 'Guinea', 'uve-mailrelay-newsletter' ),
				'GW' => __( 'Guinea-Bissau', 'uve-mailrelay-newsletter' ),
				'GY' => __( 'Guyana', 'uve-mailrelay-newsletter' ),
				'HT' => __( 'Haiti', 'uve-mailrelay-newsletter' ),
				'HM' => __( 'Heard Island and McDonald Islands', 'uve-mailrelay-newsletter' ),
				'VA' => __( 'Holy See', 'uve-mailrelay-newsletter' ),
				'HN' => __( 'Honduras', 'uve-mailrelay-newsletter' ),
				'HK' => __( 'Hong Kong', 'uve-mailrelay-newsletter' ),
				'HU' => __( 'Hungary', 'uve-mailrelay-newsletter' ),
				'IS' => __( 'Iceland', 'uve-mailrelay-newsletter' ),
				'IN' => __( 'India', 'uve-mailrelay-newsletter' ),
				'ID' => __( 'Indonesia', 'uve-mailrelay-newsletter' ),
				'IR' => __( 'Iran, Islamic Republic of', 'uve-mailrelay-newsletter' ),
				'IQ' => __( 'Iraq', 'uve-mailrelay-newsletter' ),
				'IE' => __( 'Ireland', 'uve-mailrelay-newsletter' ),
				'IM' => __( 'Isle of Man', 'uve-mailrelay-newsletter' ),
				'IL' => __( 'Israel', 'uve-mailrelay-newsletter' ),
				'IT' => __( 'Italy', 'uve-mailrelay-newsletter' ),
				'JM' => __( 'Jamaica', 'uve-mailrelay-newsletter' ),
				'JP' => __( 'Japan', 'uve-mailrelay-newsletter' ),
				'JE' => __( 'Jersey', 'uve-mailrelay-newsletter' ),
				'JO' => __( 'Jordan', 'uve-mailrelay-newsletter' ),
				'KZ' => __( 'Kazakhstan', 'uve-mailrelay-newsletter' ),
				'KE' => __( 'Kenya', 'uve-mailrelay-newsletter' ),
				'KI' => __( 'Kiribati', 'uve-mailrelay-newsletter' ),
				'KP' => __( 'Korea, Democratic People\'s Republic of', 'uve-mailrelay-newsletter' ),
				'KR' => __( 'Korea, Republic of', 'uve-mailrelay-newsletter' ),
				'KW' => __( 'Kuwait', 'uve-mailrelay-newsletter' ),
				'KG' => __( 'Kyrgyzstan', 'uve-mailrelay-newsletter' ),
				'LA' => __( 'Lao People\'s Democratic Republic', 'uve-mailrelay-newsletter' ),
				'LV' => __( 'Latvia', 'uve-mailrelay-newsletter' ),
				'LB' => __( 'Lebanon', 'uve-mailrelay-newsletter' ),
				'LS' => __( 'Lesotho', 'uve-mailrelay-newsletter' ),
				'LR' => __( 'Liberia', 'uve-mailrelay-newsletter' ),
				'LY' => __( 'Libya', 'uve-mailrelay-newsletter' ),
				'LI' => __( 'Liechtenstein', 'uve-mailrelay-newsletter' ),
				'LT' => __( 'Lithuania', 'uve-mailrelay-newsletter' ),
				'LU' => __( 'Luxembourg', 'uve-mailrelay-newsletter' ),
				'MO' => __( 'Macao', 'uve-mailrelay-newsletter' ),
				'MG' => __( 'Madagascar', 'uve-mailrelay-newsletter' ),
				'MW' => __( 'Malawi', 'uve-mailrelay-newsletter' ),
				'MY' => __( 'Malaysia', 'uve-mailrelay-newsletter' ),
				'MV' => __( 'Maldives', 'uve-mailrelay-newsletter' ),
				'ML' => __( 'Mali', 'uve-mailrelay-newsletter' ),
				'MT' => __( 'Malta', 'uve-mailrelay-newsletter' ),
				'MH' => __( 'Marshall Islands', 'uve-mailrelay-newsletter' ),
				'MQ' => __( 'Martinique', 'uve-mailrelay-newsletter' ),
				'MR' => __( 'Mauritania', 'uve-mailrelay-newsletter' ),
				'MU' => __( 'Mauritius', 'uve-mailrelay-newsletter' ),
				'YT' => __( 'Mayotte', 'uve-mailrelay-newsletter' ),
				'MX' => __( 'Mexico', 'uve-mailrelay-newsletter' ),
				'FM' => __( 'Micronesia, Federated States of', 'uve-mailrelay-newsletter' ),
				'MD' => __( 'Moldova, Republic of', 'uve-mailrelay-newsletter' ),
				'MC' => __( 'Monaco', 'uve-mailrelay-newsletter' ),
				'MN' => __( 'Mongolia', 'uve-mailrelay-newsletter' ),
				'ME' => __( 'Montenegro', 'uve-mailrelay-newsletter' ),
				'MS' => __( 'Montserrat', 'uve-mailrelay-newsletter' ),
				'MA' => __( 'Morocco', 'uve-mailrelay-newsletter' ),
				'MZ' => __( 'Mozambique', 'uve-mailrelay-newsletter' ),
				'MM' => __( 'Myanmar', 'uve-mailrelay-newsletter' ),
				'NA' => __( 'Namibia', 'uve-mailrelay-newsletter' ),
				'NR' => __( 'Nauru', 'uve-mailrelay-newsletter' ),
				'NP' => __( 'Nepal', 'uve-mailrelay-newsletter' ),
				'NL' => __( 'Netherlands', 'uve-mailrelay-newsletter' ),
				'NC' => __( 'New Caledonia', 'uve-mailrelay-newsletter' ),
				'NZ' => __( 'New Zealand', 'uve-mailrelay-newsletter' ),
				'NI' => __( 'Nicaragua', 'uve-mailrelay-newsletter' ),
				'NE' => __( 'Niger', 'uve-mailrelay-newsletter' ),
				'NG' => __( 'Nigeria', 'uve-mailrelay-newsletter' ),
				'NU' => __( 'Niue', 'uve-mailrelay-newsletter' ),
				'NF' => __( 'Norfolk Island', 'uve-mailrelay-newsletter' ),
				'MK' => __( 'North Macedonia', 'uve-mailrelay-newsletter' ),
				'MP' => __( 'Northern Mariana Islands', 'uve-mailrelay-newsletter' ),
				'NO' => __( 'Norway', 'uve-mailrelay-newsletter' ),
				'OM' => __( 'Oman', 'uve-mailrelay-newsletter' ),
				'PK' => __( 'Pakistan', 'uve-mailrelay-newsletter' ),
				'PW' => __( 'Palau', 'uve-mailrelay-newsletter' ),
				'PS' => __( 'Palestine, State of', 'uve-mailrelay-newsletter' ),
				'PA' => __( 'Panama', 'uve-mailrelay-newsletter' ),
				'PG' => __( 'Papua New Guinea', 'uve-mailrelay-newsletter' ),
				'PY' => __( 'Paraguay', 'uve-mailrelay-newsletter' ),
				'PE' => __( 'Peru', 'uve-mailrelay-newsletter' ),
				'PH' => __( 'Philippines', 'uve-mailrelay-newsletter' ),
				'PN' => __( 'Pitcairn', 'uve-mailrelay-newsletter' ),
				'PL' => __( 'Poland', 'uve-mailrelay-newsletter' ),
				'PT' => __( 'Portugal', 'uve-mailrelay-newsletter' ),
				'PR' => __( 'Puerto Rico', 'uve-mailrelay-newsletter' ),
				'QA' => __( 'Qatar', 'uve-mailrelay-newsletter' ),
				'RE' => __( 'Reunion', 'uve-mailrelay-newsletter' ),
				'RO' => __( 'Romania', 'uve-mailrelay-newsletter' ),
				'RU' => __( 'Russian Federation', 'uve-mailrelay-newsletter' ),
				'RW' => __( 'Rwanda', 'uve-mailrelay-newsletter' ),
				'BL' => __( 'Saint Barthelemy', 'uve-mailrelay-newsletter' ),
				'SH' => __( 'Saint Helena, Ascension and Tristan da Cunha', 'uve-mailrelay-newsletter' ),
				'KN' => __( 'Saint Kitts and Nevis', 'uve-mailrelay-newsletter' ),
				'LC' => __( 'Saint Lucia', 'uve-mailrelay-newsletter' ),
				'MF' => __( 'Saint Martin (French part)', 'uve-mailrelay-newsletter' ),
				'PM' => __( 'Saint Pierre and Miquelon', 'uve-mailrelay-newsletter' ),
				'VC' => __( 'Saint Vincent and the Grenadines', 'uve-mailrelay-newsletter' ),
				'WS' => __( 'Samoa', 'uve-mailrelay-newsletter' ),
				'SM' => __( 'San Marino', 'uve-mailrelay-newsletter' ),
				'ST' => __( 'Sao Tome and Principe', 'uve-mailrelay-newsletter' ),
				'SA' => __( 'Saudi Arabia', 'uve-mailrelay-newsletter' ),
				'SN' => __( 'Senegal', 'uve-mailrelay-newsletter' ),
				'RS' => __( 'Serbia', 'uve-mailrelay-newsletter' ),
				'SC' => __( 'Seychelles', 'uve-mailrelay-newsletter' ),
				'SL' => __( 'Sierra Leone', 'uve-mailrelay-newsletter' ),
				'SG' => __( 'Singapore', 'uve-mailrelay-newsletter' ),
				'SX' => __( 'Sint Maarten (Dutch part)', 'uve-mailrelay-newsletter' ),
				'SK' => __( 'Slovakia', 'uve-mailrelay-newsletter' ),
				'SI' => __( 'Slovenia', 'uve-mailrelay-newsletter' ),
				'SB' => __( 'Solomon Islands', 'uve-mailrelay-newsletter' ),
				'SO' => __( 'Somalia', 'uve-mailrelay-newsletter' ),
				'ZA' => __( 'South Africa', 'uve-mailrelay-newsletter' ),
				'GS' => __( 'South Georgia and the South Sandwich Islands', 'uve-mailrelay-newsletter' ),
				'SS' => __( 'South Sudan', 'uve-mailrelay-newsletter' ),
				'ES' => __( 'Spain', 'uve-mailrelay-newsletter' ),
				'LK' => __( 'Sri Lanka', 'uve-mailrelay-newsletter' ),
				'SD' => __( 'Sudan', 'uve-mailrelay-newsletter' ),
				'SR' => __( 'Suriname', 'uve-mailrelay-newsletter' ),
				'SJ' => __( 'Svalbard and Jan Mayen', 'uve-mailrelay-newsletter' ),
				'SE' => __( 'Sweden', 'uve-mailrelay-newsletter' ),
				'CH' => __( 'Switzerland', 'uve-mailrelay-newsletter' ),
				'SY' => __( 'Syrian Arab Republic', 'uve-mailrelay-newsletter' ),
				'TW' => __( 'Taiwan, Province of China', 'uve-mailrelay-newsletter' ),
				'TJ' => __( 'Tajikistan', 'uve-mailrelay-newsletter' ),
				'TZ' => __( 'Tanzania, United Republic of', 'uve-mailrelay-newsletter' ),
				'TH' => __( 'Thailand', 'uve-mailrelay-newsletter' ),
				'TL' => __( 'Timor-Leste', 'uve-mailrelay-newsletter' ),
				'TG' => __( 'Togo', 'uve-mailrelay-newsletter' ),
				'TK' => __( 'Tokelau', 'uve-mailrelay-newsletter' ),
				'TO' => __( 'Tonga', 'uve-mailrelay-newsletter' ),
				'TT' => __( 'Trinidad and Tobago', 'uve-mailrelay-newsletter' ),
				'TN' => __( 'Tunisia', 'uve-mailrelay-newsletter' ),
				'TR' => __( 'Turkey', 'uve-mailrelay-newsletter' ),
				'TM' => __( 'Turkmenistan', 'uve-mailrelay-newsletter' ),
				'TC' => __( 'Turks and Caicos Islands', 'uve-mailrelay-newsletter' ),
				'TV' => __( 'Tuvalu', 'uve-mailrelay-newsletter' ),
				'UG' => __( 'Uganda', 'uve-mailrelay-newsletter' ),
				'UA' => __( 'Ukraine', 'uve-mailrelay-newsletter' ),
				'AE' => __( 'United Arab Emirates', 'uve-mailrelay-newsletter' ),
				'GB' => __( 'United Kingdom', 'uve-mailrelay-newsletter' ),
				'US' => __( 'United States', 'uve-mailrelay-newsletter' ),
				'UM' => __( 'United States Minor Outlying Islands', 'uve-mailrelay-newsletter' ),
				'UY' => __( 'Uruguay', 'uve-mailrelay-newsletter' ),
				'UZ' => __( 'Uzbekistan', 'uve-mailrelay-newsletter' ),
				'VU' => __( 'Vanuatu', 'uve-mailrelay-newsletter' ),
				'VE' => __( 'Venezuela, Bolivarian Republic of', 'uve-mailrelay-newsletter' ),
				'VN' => __( 'Viet Nam', 'uve-mailrelay-newsletter' ),
				'VG' => __( 'Virgin Islands, British', 'uve-mailrelay-newsletter' ),
				'VI' => __( 'Virgin Islands, U.S.', 'uve-mailrelay-newsletter' ),
				'WF' => __( 'Wallis and Futuna', 'uve-mailrelay-newsletter' ),
				'EH' => __( 'Western Sahara', 'uve-mailrelay-newsletter' ),
				'YE' => __( 'Yemen', 'uve-mailrelay-newsletter' ),
				'ZM' => __( 'Zambia', 'uve-mailrelay-newsletter' ),
				'ZW' => __( 'Zimbabwe', 'uve-mailrelay-newsletter' ),
			);
		}

		return apply_filters( 'uve_mr_country_options', $countries );
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

		$raw_url = $data['uve_mr_page_url'] ?? '';
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
		$class = (string) apply_filters( 'uve_mr_premium_class', 'UVE_MR_Premium' );
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
		return __DIR__ . '/../class-uve-mailrelay-newsletter.php';
	}
}
