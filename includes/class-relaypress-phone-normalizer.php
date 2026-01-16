<?php
/**
 * Phone normalization helpers.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Phone normalization helpers.
 */
final class RelayPress_Phone_Normalizer {
	public const REASON_OK                      = 'OK';
	public const REASON_EMPTY                   = 'EMPTY';
	public const REASON_MISSING_COUNTRY         = 'MISSING_COUNTRY';
	public const REASON_TOO_SHORT               = 'TOO_SHORT';
	public const REASON_TOO_LONG                = 'TOO_LONG';
	public const REASON_INVALID_CHARS           = 'INVALID_CHARS';
	public const REASON_EXTENSION_NOT_SUPPORTED = 'EXTENSION_NOT_SUPPORTED';

	public const CONFIDENCE_HIGH   = 'HIGH';
	public const CONFIDENCE_MEDIUM = 'MEDIUM';
	public const CONFIDENCE_LOW    = 'LOW';

	/**
	 * Normalize a phone number into E.164 when possible.
	 *
	 * @param string $raw Raw phone input.
	 * @param array  $context Normalization context.
	 * @return array{normalized:?string,raw_sanitized:string,is_valid:bool,reason:string,country_used:?string,confidence:string}
	 */
	public static function normalize( string $raw, array $context = array() ): array {
		$raw_sanitized = self::sanitize_raw( $raw );
		if ( '' === $raw_sanitized ) {
			return self::result( $raw_sanitized, null, false, self::REASON_EMPTY, null, self::CONFIDENCE_LOW );
		}

		$accept_extensions = ! empty( $context['accept_extensions'] );
		$require_e164      = ! empty( $context['require_e164'] );

		$country         = RelayPress_Utils::normalize_country_code( (string) ( $context['country'] ?? '' ) );
		$default_country = RelayPress_Utils::normalize_country_code( (string) ( $context['default_country'] ?? '' ) );
		$country_used    = '' !== $country ? $country : $default_country;

		$extension = self::extract_extension( $raw_sanitized );
		if ( $extension['has_extension'] && ! $accept_extensions ) {
			return self::result( $raw_sanitized, null, false, self::REASON_EXTENSION_NOT_SUPPORTED, $country_used, self::CONFIDENCE_LOW );
		}

		$candidate = $extension['clean'];
		if ( preg_match( '/[A-Za-z]/', $candidate ) ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, self::CONFIDENCE_LOW );
		}

		$candidate = trim( $candidate );
		if ( '' === $candidate ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, self::CONFIDENCE_LOW );
		}

		if ( ! self::plus_is_valid( $candidate ) ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, self::CONFIDENCE_LOW );
		}

		$digits = preg_replace( '/\D+/', '', $candidate );
		if ( '' === $digits ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, self::CONFIDENCE_LOW );
		}

		if ( 0 === strpos( $candidate, '+' ) ) {
			$normalized   = '+' . $digits;
			$country_used = self::country_from_calling_code( $digits );
			return self::validate( $raw_sanitized, $normalized, $country_used, self::CONFIDENCE_HIGH, $require_e164 );
		}

		if ( 0 === strpos( $candidate, '00' ) ) {
			$normalized_digits = substr( $digits, 2 );
			if ( '' === $normalized_digits ) {
				return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, self::CONFIDENCE_LOW );
			}
			$normalized   = '+' . $normalized_digits;
			$country_used = self::country_from_calling_code( $normalized_digits );
			return self::validate( $raw_sanitized, $normalized, $country_used, self::CONFIDENCE_HIGH, $require_e164 );
		}

		if ( '' === $country_used ) {
			return self::result( $raw_sanitized, null, false, self::REASON_MISSING_COUNTRY, null, self::CONFIDENCE_LOW );
		}

		$calling_code = self::country_calling_code( $country_used );
		if ( '' === $calling_code ) {
			return self::result( $raw_sanitized, null, false, self::REASON_MISSING_COUNTRY, null, self::CONFIDENCE_LOW );
		}

		$national = $digits;
		$trunk    = self::trunk_prefix( $country_used );
		if ( '' !== $trunk && 0 === strpos( $national, $trunk ) && strlen( $national ) > strlen( $trunk ) ) {
			$national = substr( $national, strlen( $trunk ) );
		}

		$normalized = '+' . $calling_code . $national;
		$confidence = '' !== $country ? self::CONFIDENCE_MEDIUM : self::CONFIDENCE_LOW;
		return self::validate( $raw_sanitized, $normalized, $country_used, $confidence, $require_e164 );
	}

	/**
	 * Compact a raw phone number into a simple digits or +digits format.
	 *
	 * @param string $raw Raw phone input.
	 * @return string
	 */
	public static function compact_raw( string $raw ): string {
		$raw = self::sanitize_raw( $raw );
		if ( '' === $raw ) {
			return '';
		}

		$extension = self::extract_extension( $raw );
		$candidate = trim( $extension['clean'] );
		if ( '' === $candidate ) {
			return '';
		}

		$digits = preg_replace( '/\D+/', '', $candidate );
		if ( '' === $digits ) {
			return '';
		}

		if ( 0 === strpos( $candidate, '+' ) ) {
			return '+' . $digits;
		}

		if ( 0 === strpos( $candidate, '00' ) ) {
			$normalized_digits = substr( $digits, 2 );
			return '' !== $normalized_digits ? '+' . $normalized_digits : '';
		}

		return $digits;
	}

	/**
	 * Get phone prefix entries for UI.
	 *
	 * @return array<int,array{prefix:string,label:string,iso:string}>
	 */
	public static function phone_prefix_entries(): array {
		$entries = self::phone_prefix_source();
		$labels  = RelayPress_Utils::country_options();
		$results = array();
		foreach ( $entries as $entry ) {
			$prefix = (string) ( $entry['prefix'] ?? '' );
			if ( '' === $prefix ) {
				continue;
			}
			$label = (string) ( $entry['label'] ?? '' );
			$iso   = (string) ( $entry['iso'] ?? '' );
			if ( '' !== $iso && isset( $labels[ $iso ] ) ) {
				$label = $labels[ $iso ] . ' (+' . $prefix . ')';
			}
			if ( '' === $label ) {
				$label = '+' . $prefix;
			}
			$results[] = array(
				'prefix' => $prefix,
				'label'  => $label,
				'iso'    => $iso,
			);
		}
		return $results;
	}

	/**
	 * Get calling code for a country code.
	 *
	 * @param string $country Country code.
	 * @return string
	 */
	public static function calling_code_for_country( string $country ): string {
		$country = RelayPress_Utils::normalize_country_code( $country );
		if ( '' === $country ) {
			return '';
		}
		return self::country_calling_code( $country );
	}

	/**
	 * Combine a phone prefix with a phone number, avoiding duplicate prefixes.
	 *
	 * @param string $phone Raw phone input.
	 * @param string $prefix Raw prefix input.
	 * @return string
	 */
	public static function combine_phone_with_prefix( string $phone, string $prefix ): string {
		$phone  = trim( $phone );
		$prefix = trim( $prefix );
		if ( '' === $prefix ) {
			return $phone;
		}
		$prefix = preg_replace( '/\s+/', '', $prefix );
		if ( '' === $prefix ) {
			return $phone;
		}
		if ( 0 === strpos( $prefix, '00' ) ) {
			$prefix = '+' . substr( $prefix, 2 );
		} elseif ( 0 !== strpos( $prefix, '+' ) ) {
			$prefix = '+' . $prefix;
		}
		$prefix_digits = (string) preg_replace( '/\D+/', '', $prefix );
		if ( '' === $prefix_digits ) {
			return $phone;
		}
		$prefix = '+' . $prefix_digits;
		if ( '' === $phone ) {
			return $prefix;
		}
		$phone_trimmed = $phone;
		if ( 0 === strpos( $phone_trimmed, '+' ) || 0 === strpos( $phone_trimmed, '00' ) ) {
			return $phone_trimmed;
		}
		$phone_digits = preg_replace( '/\D+/', '', $phone_trimmed );
		if ( '' === $phone_digits ) {
			return $phone_trimmed;
		}
		if ( '' !== $prefix_digits && 0 === strpos( $phone_digits, $prefix_digits ) ) {
			$phone_digits = substr( $phone_digits, strlen( $prefix_digits ) );
		}
		return $prefix . $phone_digits;
	}

	/**
	 * Trim and normalize whitespace in raw input.
	 *
	 * @param string $raw Raw input.
	 * @return string
	 */
	private static function sanitize_raw( string $raw ): string {
		$raw = trim( $raw );
		if ( '' === $raw ) {
			return '';
		}
		return preg_replace( '/\s+/', ' ', $raw );
	}

	/**
	 * Ensure "+" only appears at the start.
	 *
	 * @param string $candidate Candidate input.
	 * @return bool
	 */
	private static function plus_is_valid( string $candidate ): bool {
		if ( false === strpos( $candidate, '+' ) ) {
			return true;
		}
		if ( 0 !== strpos( $candidate, '+' ) ) {
			return false;
		}
		return 1 === substr_count( $candidate, '+' );
	}

	/**
	 * Extract an extension from the raw phone string.
	 *
	 * @param string $raw Raw input.
	 * @return array{clean:string,has_extension:bool}
	 */
	private static function extract_extension( string $raw ): array {
		$pattern = '/\s*(?:ext\.?|extension|x|#)\s*\d+\s*$/i';
		if ( preg_match( $pattern, $raw ) ) {
			$clean = preg_replace( $pattern, '', $raw );
			return array(
				'clean'         => (string) $clean,
				'has_extension' => true,
			);
		}
		return array(
			'clean'         => $raw,
			'has_extension' => false,
		);
	}

	/**
	 * Validate a normalized E.164 phone string.
	 *
	 * @param string      $raw_sanitized Sanitized raw input.
	 * @param string|null $normalized Normalized phone.
	 * @param string|null $country_used Country used for normalization.
	 * @param string      $confidence Confidence level.
	 * @param bool        $require_e164 Require E.164 format.
	 * @return array{normalized:?string,raw_sanitized:string,is_valid:bool,reason:string,country_used:?string,confidence:string}
	 */
	private static function validate( string $raw_sanitized, ?string $normalized, ?string $country_used, string $confidence, bool $require_e164 ): array {
		if ( null === $normalized || '' === $normalized ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, $confidence );
		}

		$digits = preg_replace( '/\D+/', '', $normalized );
		$length = strlen( $digits );
		if ( $length < 7 ) {
			return self::result( $raw_sanitized, null, false, self::REASON_TOO_SHORT, $country_used, $confidence );
		}
		if ( $length > 15 ) {
			return self::result( $raw_sanitized, null, false, self::REASON_TOO_LONG, $country_used, $confidence );
		}

		if ( $require_e164 && ! preg_match( '/^\+\d{7,15}$/', $normalized ) ) {
			return self::result( $raw_sanitized, null, false, self::REASON_INVALID_CHARS, $country_used, $confidence );
		}

		return self::result( $raw_sanitized, $normalized, true, self::REASON_OK, $country_used, $confidence );
	}

	/**
	 * Build a normalization result array.
	 *
	 * @param string      $raw_sanitized Sanitized raw input.
	 * @param string|null $normalized Normalized E.164 phone.
	 * @param bool        $is_valid Valid flag.
	 * @param string      $reason Reason code.
	 * @param string|null $country_used Country used.
	 * @param string      $confidence Confidence level.
	 * @return array{normalized:?string,raw_sanitized:string,is_valid:bool,reason:string,country_used:?string,confidence:string}
	 */
	private static function result( string $raw_sanitized, ?string $normalized, bool $is_valid, string $reason, ?string $country_used, string $confidence ): array {
		return array(
			'normalized'    => $normalized,
			'raw_sanitized' => $raw_sanitized,
			'is_valid'      => $is_valid,
			'reason'        => $reason,
			'country_used'  => $country_used,
			'confidence'    => $confidence,
		);
	}

	/**
	 * Get a phone calling code by country.
	 *
	 * @param string $country Country code.
	 * @return string
	 */
	private static function country_calling_code( string $country ): string {
		$codes = self::country_calling_codes();
		return $codes[ $country ] ?? '';
	}

	/**
	 * Resolve country from calling code prefix.
	 *
	 * @param string $digits Phone digits without +.
	 * @return string
	 */
	private static function country_from_calling_code( string $digits ): string {
		$codes = self::country_calling_codes();
		$match = '';
		$hit   = '';
		foreach ( $codes as $country => $code ) {
			if ( '' === $code ) {
				continue;
			}
			if ( 0 === strpos( $digits, $code ) ) {
				if ( strlen( $code ) > strlen( $match ) ) {
					$match = $code;
					$hit   = $country;
				}
			}
		}
		return $hit;
	}

	/**
	 * Country calling code map (ISO -> prefix).
	 *
	 * @return array<string,string>
	 */
	private static function country_calling_codes(): array {
		$codes = array();
		foreach ( self::phone_prefix_source() as $entry ) {
			$iso    = (string) ( $entry['iso'] ?? '' );
			$prefix = (string) ( $entry['prefix'] ?? '' );
			if ( '' === $iso || '' === $prefix ) {
				continue;
			}
			$codes[ $iso ] = $prefix;
		}
		/**
		 * Filter supported calling codes for phone normalization.
		 *
		 * @param array<string,string> $codes Map of ISO country code => calling code.
		 */
		return apply_filters( 'relaypress_phone_calling_codes', $codes );
	}

	/**
	 * Source list of phone prefixes.
	 *
	 * @return array<int,array{prefix:string,iso?:string,label?:string}>
	 */
	private static function phone_prefix_source(): array {
		return array(
			array(
				'prefix' => '44',
				'iso'    => 'GB',
				'label'  => 'UK (+44)',
			),
			array(
				'prefix' => '1',
				'iso'    => 'US',
				'label'  => 'USA (+1)',
			),
			array(
				'prefix' => '213',
				'iso'    => 'DZ',
				'label'  => 'Algeria (+213)',
			),
			array(
				'prefix' => '376',
				'iso'    => 'AD',
				'label'  => 'Andorra (+376)',
			),
			array(
				'prefix' => '244',
				'iso'    => 'AO',
				'label'  => 'Angola (+244)',
			),
			array(
				'prefix' => '1264',
				'iso'    => 'AI',
				'label'  => 'Anguilla (+1264)',
			),
			array(
				'prefix' => '1268',
				'iso'    => 'AG',
				'label'  => 'Antigua & Barbuda (+1268)',
			),
			array(
				'prefix' => '54',
				'iso'    => 'AR',
				'label'  => 'Argentina (+54)',
			),
			array(
				'prefix' => '374',
				'iso'    => 'AM',
				'label'  => 'Armenia (+374)',
			),
			array(
				'prefix' => '297',
				'iso'    => 'AW',
				'label'  => 'Aruba (+297)',
			),
			array(
				'prefix' => '61',
				'iso'    => 'AU',
				'label'  => 'Australia (+61)',
			),
			array(
				'prefix' => '43',
				'iso'    => 'AT',
				'label'  => 'Austria (+43)',
			),
			array(
				'prefix' => '994',
				'iso'    => 'AZ',
				'label'  => 'Azerbaijan (+994)',
			),
			array(
				'prefix' => '1242',
				'iso'    => 'BS',
				'label'  => 'Bahamas (+1242)',
			),
			array(
				'prefix' => '973',
				'iso'    => 'BH',
				'label'  => 'Bahrain (+973)',
			),
			array(
				'prefix' => '880',
				'iso'    => 'BD',
				'label'  => 'Bangladesh (+880)',
			),
			array(
				'prefix' => '1246',
				'iso'    => 'BB',
				'label'  => 'Barbados (+1246)',
			),
			array(
				'prefix' => '375',
				'iso'    => 'BY',
				'label'  => 'Belarus (+375)',
			),
			array(
				'prefix' => '32',
				'iso'    => 'BE',
				'label'  => 'Belgium (+32)',
			),
			array(
				'prefix' => '501',
				'iso'    => 'BZ',
				'label'  => 'Belize (+501)',
			),
			array(
				'prefix' => '229',
				'iso'    => 'BJ',
				'label'  => 'Benin (+229)',
			),
			array(
				'prefix' => '1441',
				'iso'    => 'BM',
				'label'  => 'Bermuda (+1441)',
			),
			array(
				'prefix' => '975',
				'iso'    => 'BT',
				'label'  => 'Bhutan (+975)',
			),
			array(
				'prefix' => '591',
				'iso'    => 'BO',
				'label'  => 'Bolivia (+591)',
			),
			array(
				'prefix' => '387',
				'iso'    => 'BA',
				'label'  => 'Bosnia Herzegovina (+387)',
			),
			array(
				'prefix' => '267',
				'iso'    => 'BW',
				'label'  => 'Botswana (+267)',
			),
			array(
				'prefix' => '55',
				'iso'    => 'BR',
				'label'  => 'Brazil (+55)',
			),
			array(
				'prefix' => '673',
				'iso'    => 'BN',
				'label'  => 'Brunei (+673)',
			),
			array(
				'prefix' => '359',
				'iso'    => 'BG',
				'label'  => 'Bulgaria (+359)',
			),
			array(
				'prefix' => '226',
				'iso'    => 'BF',
				'label'  => 'Burkina Faso (+226)',
			),
			array(
				'prefix' => '257',
				'iso'    => 'BI',
				'label'  => 'Burundi (+257)',
			),
			array(
				'prefix' => '855',
				'iso'    => 'KH',
				'label'  => 'Cambodia (+855)',
			),
			array(
				'prefix' => '237',
				'iso'    => 'CM',
				'label'  => 'Cameroon (+237)',
			),
			array(
				'prefix' => '1',
				'iso'    => 'CA',
				'label'  => 'Canada (+1)',
			),
			array(
				'prefix' => '238',
				'iso'    => 'CV',
				'label'  => 'Cape Verde Islands (+238)',
			),
			array(
				'prefix' => '1345',
				'iso'    => 'KY',
				'label'  => 'Cayman Islands (+1345)',
			),
			array(
				'prefix' => '236',
				'iso'    => 'CF',
				'label'  => 'Central African Republic (+236)',
			),
			array(
				'prefix' => '56',
				'iso'    => 'CL',
				'label'  => 'Chile (+56)',
			),
			array(
				'prefix' => '86',
				'iso'    => 'CN',
				'label'  => 'China (+86)',
			),
			array(
				'prefix' => '57',
				'iso'    => 'CO',
				'label'  => 'Colombia (+57)',
			),
			array(
				'prefix' => '269',
				'iso'    => 'KM',
				'label'  => 'Comoros (+269)',
			),
			array(
				'prefix' => '242',
				'iso'    => 'CG',
				'label'  => 'Congo (+242)',
			),
			array(
				'prefix' => '682',
				'iso'    => 'CK',
				'label'  => 'Cook Islands (+682)',
			),
			array(
				'prefix' => '506',
				'iso'    => 'CR',
				'label'  => 'Costa Rica (+506)',
			),
			array(
				'prefix' => '385',
				'iso'    => 'HR',
				'label'  => 'Croatia (+385)',
			),
			array(
				'prefix' => '53',
				'iso'    => 'CU',
				'label'  => 'Cuba (+53)',
			),
			array(
				'prefix' => '90392',
				'label'  => 'Cyprus North (+90392)',
			),
			array(
				'prefix' => '357',
				'iso'    => 'CY',
				'label'  => 'Cyprus South (+357)',
			),
			array(
				'prefix' => '42',
				'iso'    => 'CZ',
				'label'  => 'Czech Republic (+42)',
			),
			array(
				'prefix' => '45',
				'iso'    => 'DK',
				'label'  => 'Denmark (+45)',
			),
			array(
				'prefix' => '253',
				'iso'    => 'DJ',
				'label'  => 'Djibouti (+253)',
			),
			array(
				'prefix' => '1809',
				'iso'    => 'DM',
				'label'  => 'Dominica (+1809)',
			),
			array(
				'prefix' => '1809',
				'iso'    => 'DO',
				'label'  => 'Dominican Republic (+1809)',
			),
			array(
				'prefix' => '593',
				'iso'    => 'EC',
				'label'  => 'Ecuador (+593)',
			),
			array(
				'prefix' => '20',
				'iso'    => 'EG',
				'label'  => 'Egypt (+20)',
			),
			array(
				'prefix' => '503',
				'iso'    => 'SV',
				'label'  => 'El Salvador (+503)',
			),
			array(
				'prefix' => '240',
				'iso'    => 'GQ',
				'label'  => 'Equatorial Guinea (+240)',
			),
			array(
				'prefix' => '291',
				'iso'    => 'ER',
				'label'  => 'Eritrea (+291)',
			),
			array(
				'prefix' => '372',
				'iso'    => 'EE',
				'label'  => 'Estonia (+372)',
			),
			array(
				'prefix' => '251',
				'iso'    => 'ET',
				'label'  => 'Ethiopia (+251)',
			),
			array(
				'prefix' => '500',
				'iso'    => 'FK',
				'label'  => 'Falkland Islands (+500)',
			),
			array(
				'prefix' => '298',
				'iso'    => 'FO',
				'label'  => 'Faroe Islands (+298)',
			),
			array(
				'prefix' => '679',
				'iso'    => 'FJ',
				'label'  => 'Fiji (+679)',
			),
			array(
				'prefix' => '358',
				'iso'    => 'FI',
				'label'  => 'Finland (+358)',
			),
			array(
				'prefix' => '33',
				'iso'    => 'FR',
				'label'  => 'France (+33)',
			),
			array(
				'prefix' => '594',
				'iso'    => 'GF',
				'label'  => 'French Guiana (+594)',
			),
			array(
				'prefix' => '689',
				'iso'    => 'PF',
				'label'  => 'French Polynesia (+689)',
			),
			array(
				'prefix' => '241',
				'iso'    => 'GA',
				'label'  => 'Gabon (+241)',
			),
			array(
				'prefix' => '220',
				'iso'    => 'GM',
				'label'  => 'Gambia (+220)',
			),
			array(
				'prefix' => '7880',
				'iso'    => 'GE',
				'label'  => 'Georgia (+7880)',
			),
			array(
				'prefix' => '49',
				'iso'    => 'DE',
				'label'  => 'Germany (+49)',
			),
			array(
				'prefix' => '233',
				'iso'    => 'GH',
				'label'  => 'Ghana (+233)',
			),
			array(
				'prefix' => '350',
				'iso'    => 'GI',
				'label'  => 'Gibraltar (+350)',
			),
			array(
				'prefix' => '30',
				'iso'    => 'GR',
				'label'  => 'Greece (+30)',
			),
			array(
				'prefix' => '299',
				'iso'    => 'GL',
				'label'  => 'Greenland (+299)',
			),
			array(
				'prefix' => '1473',
				'iso'    => 'GD',
				'label'  => 'Grenada (+1473)',
			),
			array(
				'prefix' => '590',
				'iso'    => 'GP',
				'label'  => 'Guadeloupe (+590)',
			),
			array(
				'prefix' => '671',
				'iso'    => 'GU',
				'label'  => 'Guam (+671)',
			),
			array(
				'prefix' => '502',
				'iso'    => 'GT',
				'label'  => 'Guatemala (+502)',
			),
			array(
				'prefix' => '224',
				'iso'    => 'GN',
				'label'  => 'Guinea (+224)',
			),
			array(
				'prefix' => '245',
				'iso'    => 'GW',
				'label'  => 'Guinea - Bissau (+245)',
			),
			array(
				'prefix' => '592',
				'iso'    => 'GY',
				'label'  => 'Guyana (+592)',
			),
			array(
				'prefix' => '509',
				'iso'    => 'HT',
				'label'  => 'Haiti (+509)',
			),
			array(
				'prefix' => '504',
				'iso'    => 'HN',
				'label'  => 'Honduras (+504)',
			),
			array(
				'prefix' => '852',
				'iso'    => 'HK',
				'label'  => 'Hong Kong (+852)',
			),
			array(
				'prefix' => '36',
				'iso'    => 'HU',
				'label'  => 'Hungary (+36)',
			),
			array(
				'prefix' => '354',
				'iso'    => 'IS',
				'label'  => 'Iceland (+354)',
			),
			array(
				'prefix' => '91',
				'iso'    => 'IN',
				'label'  => 'India (+91)',
			),
			array(
				'prefix' => '62',
				'iso'    => 'ID',
				'label'  => 'Indonesia (+62)',
			),
			array(
				'prefix' => '98',
				'iso'    => 'IR',
				'label'  => 'Iran (+98)',
			),
			array(
				'prefix' => '964',
				'iso'    => 'IQ',
				'label'  => 'Iraq (+964)',
			),
			array(
				'prefix' => '353',
				'iso'    => 'IE',
				'label'  => 'Ireland (+353)',
			),
			array(
				'prefix' => '972',
				'iso'    => 'IL',
				'label'  => 'Israel (+972)',
			),
			array(
				'prefix' => '39',
				'iso'    => 'IT',
				'label'  => 'Italy (+39)',
			),
			array(
				'prefix' => '1876',
				'iso'    => 'JM',
				'label'  => 'Jamaica (+1876)',
			),
			array(
				'prefix' => '81',
				'iso'    => 'JP',
				'label'  => 'Japan (+81)',
			),
			array(
				'prefix' => '962',
				'iso'    => 'JO',
				'label'  => 'Jordan (+962)',
			),
			array(
				'prefix' => '7',
				'iso'    => 'KZ',
				'label'  => 'Kazakhstan (+7)',
			),
			array(
				'prefix' => '254',
				'iso'    => 'KE',
				'label'  => 'Kenya (+254)',
			),
			array(
				'prefix' => '686',
				'iso'    => 'KI',
				'label'  => 'Kiribati (+686)',
			),
			array(
				'prefix' => '850',
				'iso'    => 'KP',
				'label'  => 'Korea North (+850)',
			),
			array(
				'prefix' => '82',
				'iso'    => 'KR',
				'label'  => 'Korea South (+82)',
			),
			array(
				'prefix' => '965',
				'iso'    => 'KW',
				'label'  => 'Kuwait (+965)',
			),
			array(
				'prefix' => '996',
				'iso'    => 'KG',
				'label'  => 'Kyrgyzstan (+996)',
			),
			array(
				'prefix' => '856',
				'iso'    => 'LA',
				'label'  => 'Laos (+856)',
			),
			array(
				'prefix' => '371',
				'iso'    => 'LV',
				'label'  => 'Latvia (+371)',
			),
			array(
				'prefix' => '961',
				'iso'    => 'LB',
				'label'  => 'Lebanon (+961)',
			),
			array(
				'prefix' => '266',
				'iso'    => 'LS',
				'label'  => 'Lesotho (+266)',
			),
			array(
				'prefix' => '231',
				'iso'    => 'LR',
				'label'  => 'Liberia (+231)',
			),
			array(
				'prefix' => '218',
				'iso'    => 'LY',
				'label'  => 'Libya (+218)',
			),
			array(
				'prefix' => '417',
				'iso'    => 'LI',
				'label'  => 'Liechtenstein (+417)',
			),
			array(
				'prefix' => '370',
				'iso'    => 'LT',
				'label'  => 'Lithuania (+370)',
			),
			array(
				'prefix' => '352',
				'iso'    => 'LU',
				'label'  => 'Luxembourg (+352)',
			),
			array(
				'prefix' => '853',
				'iso'    => 'MO',
				'label'  => 'Macao (+853)',
			),
			array(
				'prefix' => '389',
				'iso'    => 'MK',
				'label'  => 'Macedonia (+389)',
			),
			array(
				'prefix' => '261',
				'iso'    => 'MG',
				'label'  => 'Madagascar (+261)',
			),
			array(
				'prefix' => '265',
				'iso'    => 'MW',
				'label'  => 'Malawi (+265)',
			),
			array(
				'prefix' => '60',
				'iso'    => 'MY',
				'label'  => 'Malaysia (+60)',
			),
			array(
				'prefix' => '960',
				'iso'    => 'MV',
				'label'  => 'Maldives (+960)',
			),
			array(
				'prefix' => '223',
				'iso'    => 'ML',
				'label'  => 'Mali (+223)',
			),
			array(
				'prefix' => '356',
				'iso'    => 'MT',
				'label'  => 'Malta (+356)',
			),
			array(
				'prefix' => '692',
				'iso'    => 'MH',
				'label'  => 'Marshall Islands (+692)',
			),
			array(
				'prefix' => '596',
				'iso'    => 'MQ',
				'label'  => 'Martinique (+596)',
			),
			array(
				'prefix' => '222',
				'iso'    => 'MR',
				'label'  => 'Mauritania (+222)',
			),
			array(
				'prefix' => '269',
				'iso'    => 'YT',
				'label'  => 'Mayotte (+269)',
			),
			array(
				'prefix' => '52',
				'iso'    => 'MX',
				'label'  => 'Mexico (+52)',
			),
			array(
				'prefix' => '691',
				'iso'    => 'FM',
				'label'  => 'Micronesia (+691)',
			),
			array(
				'prefix' => '373',
				'iso'    => 'MD',
				'label'  => 'Moldova (+373)',
			),
			array(
				'prefix' => '377',
				'iso'    => 'MC',
				'label'  => 'Monaco (+377)',
			),
			array(
				'prefix' => '976',
				'iso'    => 'MN',
				'label'  => 'Mongolia (+976)',
			),
			array(
				'prefix' => '1664',
				'iso'    => 'MS',
				'label'  => 'Montserrat (+1664)',
			),
			array(
				'prefix' => '212',
				'iso'    => 'MA',
				'label'  => 'Morocco (+212)',
			),
			array(
				'prefix' => '258',
				'iso'    => 'MZ',
				'label'  => 'Mozambique (+258)',
			),
			array(
				'prefix' => '95',
				'iso'    => 'MM',
				'label'  => 'Myanmar (+95)',
			),
			array(
				'prefix' => '264',
				'iso'    => 'NA',
				'label'  => 'Namibia (+264)',
			),
			array(
				'prefix' => '674',
				'iso'    => 'NR',
				'label'  => 'Nauru (+674)',
			),
			array(
				'prefix' => '977',
				'iso'    => 'NP',
				'label'  => 'Nepal (+977)',
			),
			array(
				'prefix' => '31',
				'iso'    => 'NL',
				'label'  => 'Netherlands (+31)',
			),
			array(
				'prefix' => '687',
				'iso'    => 'NC',
				'label'  => 'New Caledonia (+687)',
			),
			array(
				'prefix' => '64',
				'iso'    => 'NZ',
				'label'  => 'New Zealand (+64)',
			),
			array(
				'prefix' => '505',
				'iso'    => 'NI',
				'label'  => 'Nicaragua (+505)',
			),
			array(
				'prefix' => '227',
				'iso'    => 'NE',
				'label'  => 'Niger (+227)',
			),
			array(
				'prefix' => '234',
				'iso'    => 'NG',
				'label'  => 'Nigeria (+234)',
			),
			array(
				'prefix' => '683',
				'iso'    => 'NU',
				'label'  => 'Niue (+683)',
			),
			array(
				'prefix' => '672',
				'iso'    => 'NF',
				'label'  => 'Norfolk Islands (+672)',
			),
			array(
				'prefix' => '670',
				'iso'    => 'MP',
				'label'  => 'Northern Marianas (+670)',
			),
			array(
				'prefix' => '47',
				'iso'    => 'NO',
				'label'  => 'Norway (+47)',
			),
			array(
				'prefix' => '968',
				'iso'    => 'OM',
				'label'  => 'Oman (+968)',
			),
			array(
				'prefix' => '680',
				'iso'    => 'PW',
				'label'  => 'Palau (+680)',
			),
			array(
				'prefix' => '507',
				'iso'    => 'PA',
				'label'  => 'Panama (+507)',
			),
			array(
				'prefix' => '675',
				'iso'    => 'PG',
				'label'  => 'Papua New Guinea (+675)',
			),
			array(
				'prefix' => '595',
				'iso'    => 'PY',
				'label'  => 'Paraguay (+595)',
			),
			array(
				'prefix' => '51',
				'iso'    => 'PE',
				'label'  => 'Peru (+51)',
			),
			array(
				'prefix' => '63',
				'iso'    => 'PH',
				'label'  => 'Philippines (+63)',
			),
			array(
				'prefix' => '48',
				'iso'    => 'PL',
				'label'  => 'Poland (+48)',
			),
			array(
				'prefix' => '351',
				'iso'    => 'PT',
				'label'  => 'Portugal (+351)',
			),
			array(
				'prefix' => '1787',
				'iso'    => 'PR',
				'label'  => 'Puerto Rico (+1787)',
			),
			array(
				'prefix' => '974',
				'iso'    => 'QA',
				'label'  => 'Qatar (+974)',
			),
			array(
				'prefix' => '262',
				'iso'    => 'RE',
				'label'  => 'Reunion (+262)',
			),
			array(
				'prefix' => '40',
				'iso'    => 'RO',
				'label'  => 'Romania (+40)',
			),
			array(
				'prefix' => '7',
				'iso'    => 'RU',
				'label'  => 'Russia (+7)',
			),
			array(
				'prefix' => '250',
				'iso'    => 'RW',
				'label'  => 'Rwanda (+250)',
			),
			array(
				'prefix' => '378',
				'iso'    => 'SM',
				'label'  => 'San Marino (+378)',
			),
			array(
				'prefix' => '239',
				'iso'    => 'ST',
				'label'  => 'Sao Tome & Principe (+239)',
			),
			array(
				'prefix' => '966',
				'iso'    => 'SA',
				'label'  => 'Saudi Arabia (+966)',
			),
			array(
				'prefix' => '221',
				'iso'    => 'SN',
				'label'  => 'Senegal (+221)',
			),
			array(
				'prefix' => '381',
				'iso'    => 'RS',
				'label'  => 'Serbia (+381)',
			),
			array(
				'prefix' => '248',
				'iso'    => 'SC',
				'label'  => 'Seychelles (+248)',
			),
			array(
				'prefix' => '232',
				'iso'    => 'SL',
				'label'  => 'Sierra Leone (+232)',
			),
			array(
				'prefix' => '65',
				'iso'    => 'SG',
				'label'  => 'Singapore (+65)',
			),
			array(
				'prefix' => '421',
				'iso'    => 'SK',
				'label'  => 'Slovak Republic (+421)',
			),
			array(
				'prefix' => '386',
				'iso'    => 'SI',
				'label'  => 'Slovenia (+386)',
			),
			array(
				'prefix' => '677',
				'iso'    => 'SB',
				'label'  => 'Solomon Islands (+677)',
			),
			array(
				'prefix' => '252',
				'iso'    => 'SO',
				'label'  => 'Somalia (+252)',
			),
			array(
				'prefix' => '27',
				'iso'    => 'ZA',
				'label'  => 'South Africa (+27)',
			),
			array(
				'prefix' => '34',
				'iso'    => 'ES',
				'label'  => 'Spain (+34)',
			),
			array(
				'prefix' => '94',
				'iso'    => 'LK',
				'label'  => 'Sri Lanka (+94)',
			),
			array(
				'prefix' => '290',
				'iso'    => 'SH',
				'label'  => 'St. Helena (+290)',
			),
			array(
				'prefix' => '1869',
				'iso'    => 'KN',
				'label'  => 'St. Kitts (+1869)',
			),
			array(
				'prefix' => '1758',
				'iso'    => 'LC',
				'label'  => 'St. Lucia (+1758)',
			),
			array(
				'prefix' => '249',
				'iso'    => 'SD',
				'label'  => 'Sudan (+249)',
			),
			array(
				'prefix' => '597',
				'iso'    => 'SR',
				'label'  => 'Suriname (+597)',
			),
			array(
				'prefix' => '268',
				'iso'    => 'SZ',
				'label'  => 'Swaziland (+268)',
			),
			array(
				'prefix' => '46',
				'iso'    => 'SE',
				'label'  => 'Sweden (+46)',
			),
			array(
				'prefix' => '41',
				'iso'    => 'CH',
				'label'  => 'Switzerland (+41)',
			),
			array(
				'prefix' => '963',
				'iso'    => 'SY',
				'label'  => 'Syria (+963)',
			),
			array(
				'prefix' => '886',
				'iso'    => 'TW',
				'label'  => 'Taiwan (+886)',
			),
			array(
				'prefix' => '7',
				'iso'    => 'TJ',
				'label'  => 'Tajikstan (+7)',
			),
			array(
				'prefix' => '66',
				'iso'    => 'TH',
				'label'  => 'Thailand (+66)',
			),
			array(
				'prefix' => '228',
				'iso'    => 'TG',
				'label'  => 'Togo (+228)',
			),
			array(
				'prefix' => '676',
				'iso'    => 'TO',
				'label'  => 'Tonga (+676)',
			),
			array(
				'prefix' => '1868',
				'iso'    => 'TT',
				'label'  => 'Trinidad & Tobago (+1868)',
			),
			array(
				'prefix' => '216',
				'iso'    => 'TN',
				'label'  => 'Tunisia (+216)',
			),
			array(
				'prefix' => '90',
				'iso'    => 'TR',
				'label'  => 'Turkey (+90)',
			),
			array(
				'prefix' => '7',
				'label'  => 'Turkmenistan (+7)',
			),
			array(
				'prefix' => '993',
				'iso'    => 'TM',
				'label'  => 'Turkmenistan (+993)',
			),
			array(
				'prefix' => '1649',
				'iso'    => 'TC',
				'label'  => 'Turks & Caicos Islands (+1649)',
			),
			array(
				'prefix' => '688',
				'iso'    => 'TV',
				'label'  => 'Tuvalu (+688)',
			),
			array(
				'prefix' => '256',
				'iso'    => 'UG',
				'label'  => 'Uganda (+256)',
			),
			array(
				'prefix' => '380',
				'iso'    => 'UA',
				'label'  => 'Ukraine (+380)',
			),
			array(
				'prefix' => '971',
				'iso'    => 'AE',
				'label'  => 'United Arab Emirates (+971)',
			),
			array(
				'prefix' => '598',
				'iso'    => 'UY',
				'label'  => 'Uruguay (+598)',
			),
			array(
				'prefix' => '7',
				'iso'    => 'UZ',
				'label'  => 'Uzbekistan (+7)',
			),
			array(
				'prefix' => '678',
				'iso'    => 'VU',
				'label'  => 'Vanuatu (+678)',
			),
			array(
				'prefix' => '379',
				'iso'    => 'VA',
				'label'  => 'Vatican City (+379)',
			),
			array(
				'prefix' => '58',
				'iso'    => 'VE',
				'label'  => 'Venezuela (+58)',
			),
			array(
				'prefix' => '84',
				'iso'    => 'VN',
				'label'  => 'Vietnam (+84)',
			),
			array(
				'prefix' => '84',
				'iso'    => 'VG',
				'label'  => 'Virgin Islands - British (+1284)',
			),
			array(
				'prefix' => '84',
				'iso'    => 'VI',
				'label'  => 'Virgin Islands - US (+1340)',
			),
			array(
				'prefix' => '681',
				'iso'    => 'WF',
				'label'  => 'Wallis & Futuna (+681)',
			),
			array(
				'prefix' => '969',
				'label'  => 'Yemen (North)(+969)',
			),
			array(
				'prefix' => '967',
				'label'  => 'Yemen (South)(+967)',
			),
			array(
				'prefix' => '260',
				'iso'    => 'ZM',
				'label'  => 'Zambia (+260)',
			),
			array(
				'prefix' => '263',
				'iso'    => 'ZW',
				'label'  => 'Zimbabwe (+263)',
			),
		);
	}

	/**
	 * Trunk prefix to strip for local numbers.
	 *
	 * @param string $country Country code.
	 * @return string
	 */
	private static function trunk_prefix( string $country ): string {
		$prefixes = array(
			'FR' => '0',
			'IT' => '0',
			'GB' => '0',
			'DE' => '0',
			'PT' => '0',
		);
		/**
		 * Filter supported trunk prefixes for phone normalization.
		 *
		 * @param array<string,string> $prefixes Map of ISO country code => trunk prefix.
		 */
		$prefixes = apply_filters( 'relaypress_phone_trunk_prefixes', $prefixes );
		return $prefixes[ $country ] ?? '';
	}
}
