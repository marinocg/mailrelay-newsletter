<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PhoneNormalizerTest extends TestCase {
	public function test_normalizes_e164_with_plus_prefix(): void {
		$result = RelayPress_Phone_Normalizer::normalize( '+34 600 111 222' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+34600111222', $result['normalized'] );
		$this->assertSame( RelayPress_Phone_Normalizer::REASON_OK, $result['reason'] );
		$this->assertSame( RelayPress_Phone_Normalizer::CONFIDENCE_HIGH, $result['confidence'] );
	}

	public function test_normalizes_e164_with_00_prefix(): void {
		$result = RelayPress_Phone_Normalizer::normalize( '0034 600 111 222' );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+34600111222', $result['normalized'] );
		$this->assertSame( RelayPress_Phone_Normalizer::CONFIDENCE_HIGH, $result['confidence'] );
	}

	public function test_normalizes_local_number_with_default_country(): void {
		$result = RelayPress_Phone_Normalizer::normalize(
			'600111222',
			array(
				'default_country' => 'ES',
			)
		);

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+34600111222', $result['normalized'] );
		$this->assertSame( 'ES', $result['country_used'] );
		$this->assertSame( RelayPress_Phone_Normalizer::CONFIDENCE_LOW, $result['confidence'] );
	}

	public function test_strips_trunk_prefix_for_france(): void {
		$result = RelayPress_Phone_Normalizer::normalize(
			'06 12 34 56 78',
			array(
				'default_country' => 'FR',
			)
		);

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+33612345678', $result['normalized'] );
	}

	public function test_normalizes_us_number_with_separators(): void {
		$result = RelayPress_Phone_Normalizer::normalize(
			'(555) 123-4567',
			array(
				'default_country' => 'US',
			)
		);

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+15551234567', $result['normalized'] );
	}

	public function test_rejects_extensions_when_disabled(): void {
		$result = RelayPress_Phone_Normalizer::normalize( '+1 (555) 123-4567 ext 89' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( RelayPress_Phone_Normalizer::REASON_EXTENSION_NOT_SUPPORTED, $result['reason'] );
	}

	public function test_requires_country_for_local_numbers(): void {
		$result = RelayPress_Phone_Normalizer::normalize( '0712345678' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( RelayPress_Phone_Normalizer::REASON_MISSING_COUNTRY, $result['reason'] );
	}

	public function test_rejects_too_short_numbers(): void {
		$result = RelayPress_Phone_Normalizer::normalize( '+34600' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( RelayPress_Phone_Normalizer::REASON_TOO_SHORT, $result['reason'] );
	}

	public function test_rejects_invalid_characters(): void {
		$result = RelayPress_Phone_Normalizer::normalize( 'abcdef' );

		$this->assertFalse( $result['is_valid'] );
		$this->assertSame( RelayPress_Phone_Normalizer::REASON_INVALID_CHARS, $result['reason'] );
	}

	public function test_combines_prefix_without_duplication(): void {
		$combined = RelayPress_Phone_Normalizer::combine_phone_with_prefix( '34600111222', '+34' );
		$result   = RelayPress_Phone_Normalizer::normalize( $combined );

		$this->assertTrue( $result['is_valid'] );
		$this->assertSame( '+34600111222', $result['normalized'] );
	}
}
