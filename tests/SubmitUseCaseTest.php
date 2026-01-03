<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SubmitUseCaseTest extends TestCase {
	public function test_submit_use_case_calls_mailrelay_with_configured_groups(): void {
		$GLOBALS['uve_mr_test_options'] = array(
			UVE_Mailrelay_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1,2',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);
		$_SERVER['REMOTE_ADDR'] = '203.0.113.10';

		$mailrelay   = new Test_Mailrelay_Client();
		$options     = new Test_Options_Repository();
		$logs        = new Test_Logs_Repository();
		$turnstile   = new Test_Turnstile_Verifier();
		$rate_limiter = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new Test_Form_Repository();

		$use_case = new UVE_MR_Submit_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$turnstile,
			$rate_limiter,
			$request_context,
			$sanitizer,
			$forms
		);

		$result = $use_case->process_submission(
			array(
				'uve_mr_hp'        => '',
				'uve_mr_group_ids' => '1,2',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( array( 1, 2 ), $mailrelay->last_group_ids );
	}
}

final class Test_Mailrelay_Client implements UVE_MR_Mailrelay_Client {
	/**
	 * @var array<int>
	 */
	public $last_group_ids = array();

	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		$this->last_group_ids = $group_ids;
		return array(
			'ok'        => true,
			'http_code' => 201,
			'body'      => '',
		);
	}

	public function get_groups( bool $force_refresh = false ): array {
		return array();
	}
}

final class Test_Options_Repository implements UVE_MR_Options_Repository {
	public function get_options(): array {
		return UVE_Mailrelay_Newsletter::get_options();
	}
}

final class Test_Logs_Repository implements UVE_MR_Logs_Repository {
	public function ensure_table(): void {}

	public function store_consent_log( array $data ): void {}
}

final class Test_Turnstile_Verifier implements UVE_MR_Turnstile_Verifier {
	public function verify( string $token, string $ip ): bool {
		return true;
	}
}

final class Test_Rate_Limiter implements UVE_MR_Rate_Limiter {
	public function hit( string $key, int $max, int $window_seconds ): bool {
		return true;
	}
}

final class Test_Request_Context implements UVE_MR_Request_Context {
	public function get_client_ip(): string {
		return '203.0.113.10';
	}

	public function get_user_agent(): string {
		return 'test-agent';
	}

	public function get_page_url_from_request( array $data ): string {
		return 'https://example.test/';
	}

	public function current_time_mysql(): string {
		return '2024-01-01 00:00:00';
	}
}

final class Test_Input_Sanitizer implements UVE_MR_Input_Sanitizer {
	public function unslash( $value ): string {
		return is_scalar( $value ) ? (string) $value : '';
	}

	public function sanitize_text( string $value ): string {
		return trim( $value );
	}

	public function sanitize_email( string $value ): string {
		return trim( $value );
	}

	public function sanitize_url( string $value ): string {
		return trim( $value );
	}

	public function is_email( string $value ): bool {
		return false !== filter_var( $value, FILTER_VALIDATE_EMAIL );
	}
}

final class Test_Form_Repository implements UVE_MR_Form_Repository_Interface {
	public function get( int $id ): ?UVE_MR_Form {
		return null;
	}

	public function list( array $args = array() ): array {
		return array();
	}

	public function create( string $name, array $config, string $status ): ?UVE_MR_Form {
		return null;
	}

	public function update( int $id, string $name, array $config, string $status ): ?UVE_MR_Form {
		return null;
	}

	public function trash( int $id ): bool {
		return false;
	}

	public function count_published( ?int $exclude_id = null ): int {
		return 0;
	}
}
