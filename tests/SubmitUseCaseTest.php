<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SubmitUseCaseTest extends TestCase {
	public function test_subscribe_use_case_executes_and_logs(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '3',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '1',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$request_context = new Test_Request_Context();

		$use_case = new RelayPress_Subscribe_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$request_context
		);

		$result = $use_case->execute(
			array(
				'email'           => 'demo@example.com',
				'group_ids'       => array( 3 ),
				'accepted'        => true,
				'fields'          => array( 'name' => 'Demo User' ),
				'consent_label'   => 'Consent',
				'consent_context' => 'form',
				'page_url'        => 'https://example.test/',
			)
		);

		$this->assertTrue( (bool) ( $result['ok'] ?? false ) );
		$this->assertSame( array( 3 ), $mailrelay->last_group_ids );
		$this->assertSame( 'Consent', $logs->last_log['consent_label'] ?? '' );
		$this->assertSame( 'form', $logs->last_log['consent_context'] ?? '' );
	}

	public function test_subscribe_use_case_queues_first_when_enabled(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'          => 'https://api.test/api/v1',
				'api_token'             => 'token',
				'group_ids'             => '3',
				'subscriber_status'     => 'active',
				'store_consent_log'     => '1',
				'mailrelay_queue_first' => '1',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client_Tracker();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$request_context = new Test_Request_Context();
		$scheduler       = new Test_Task_Scheduler();

		$use_case = new RelayPress_Subscribe_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$request_context,
			$scheduler
		);

		$result = $use_case->execute(
			array(
				'email'           => 'demo@example.com',
				'group_ids'       => array( 3 ),
				'accepted'        => true,
				'fields'          => array( 'name' => 'Demo User' ),
				'consent_label'   => 'Consent',
				'consent_context' => 'form',
				'page_url'        => 'https://example.test/',
			)
		);

		$this->assertTrue( (bool) ( $result['queued'] ?? false ) );
		$this->assertSame( 1, $scheduler->enqueued );
		$this->assertSame( 0, $mailrelay->calls );
		$this->assertSame( array(), $logs->last_log );
	}

	public function test_subscribe_use_case_schedules_retry_on_retryable_failure(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'          => 'https://api.test/api/v1',
				'api_token'             => 'token',
				'group_ids'             => '3',
				'subscriber_status'     => 'active',
				'store_consent_log'     => '1',
				'mailrelay_queue_first' => '0',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client_Retryable();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$request_context = new Test_Request_Context();
		$scheduler       = new Test_Task_Scheduler();

		$use_case = new RelayPress_Subscribe_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$request_context,
			$scheduler
		);

		$result = $use_case->execute(
			array(
				'email'           => 'demo@example.com',
				'group_ids'       => array( 3 ),
				'accepted'        => true,
				'fields'          => array( 'name' => 'Demo User' ),
				'consent_label'   => 'Consent',
				'consent_context' => 'form',
				'page_url'        => 'https://example.test/',
			)
		);

		$this->assertTrue( (bool) ( $result['retry_scheduled'] ?? false ) );
		$this->assertSame( 1, $scheduler->scheduled );
		$this->assertSame( array(), $logs->last_log );
	}

	public function test_subscribe_use_case_fires_success_hook(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'              => 'https://api.test/api/v1',
				'api_token'                 => 'token',
				'group_ids'                 => '3',
				'subscriber_status'         => 'active',
				'store_consent_log'         => '0',
				'mailrelay_queue_first'     => '0',
			),
		);

		$payload_capture = array();
		add_action(
			'relaypress_mailrelay_subscribe_success',
			function ( $payload, $result, $attempt ) use ( &$payload_capture ): void {
				$payload_capture = array(
					'payload' => $payload,
					'result'  => $result,
					'attempt' => $attempt,
				);
			},
			10,
			3
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$request_context = new Test_Request_Context();
		$scheduler       = new Test_Task_Scheduler();

		$use_case = new RelayPress_Subscribe_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$request_context,
			$scheduler
		);

		$result = $use_case->execute(
			array(
				'email'       => 'demo@example.com',
				'group_ids'   => array( 3 ),
				'accepted'    => true,
				'fields'      => array(),
				'page_url'    => 'https://example.test/',
				'consent_context' => 'form',
			)
		);

		$this->assertTrue( (bool) ( $result['ok'] ?? false ) );
		$this->assertSame( 1, $payload_capture['attempt'] ?? 0 );
		$this->assertSame( 'demo@example.com', $payload_capture['payload']['email'] ?? '' );
	}

	public function test_subscribe_use_case_normalizes_phone_payload(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'          => 'https://api.test/api/v1',
				'api_token'             => 'token',
				'group_ids'             => '3',
				'subscriber_status'     => 'active',
				'default_phone_country' => 'ES',
				'send_raw_phone_on_fail' => '0',
				'store_consent_log'     => '0',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$request_context = new Test_Request_Context();

		$use_case = new RelayPress_Subscribe_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$request_context
		);

		$result = $use_case->execute(
			array(
				'email'       => 'demo@example.com',
				'group_ids'   => array( 3 ),
				'accepted'    => true,
				'fields'      => array(),
				'phone'       => array(
					'raw'                  => '600 111 222',
					'prefix'               => '',
					'country'              => '',
					'default_country'      => 'ES',
					'apply_default_prefix' => true,
				),
				'phone_strict' => true,
			)
		);

		$this->assertTrue( (bool) ( $result['ok'] ?? false ) );
		$this->assertSame( '+34600111222', $mailrelay->last_args['fields']['sms_phone'] ?? '' );
	}

	public function test_submit_use_case_calls_mailrelay_with_configured_groups(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
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

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1,2',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( array( 1, 2 ), $mailrelay->last_group_ids );
	}

	public function test_submit_use_case_passes_country_field(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 12;
		$form->name   = 'Form 12';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'country' => array(
					'enabled'  => true,
					'required' => true,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 12,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'country'                   => 'es',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( 'ES', $mailrelay->last_args['fields']['country'] ?? '' );
	}

	public function test_submit_use_case_rejects_invalid_country_when_required(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 13;
		$form->name   = 'Form 13';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'country' => array(
					'enabled'  => true,
					'required' => true,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 13,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'country'                   => 'XX',
				),
			)
		);

		$this->assertSame( 'error', $result['status'] );
	}

	public function test_submit_use_case_normalizes_phone_with_default_country(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'default_phone_country'         => 'ES',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 21;
		$form->name   = 'Form 21';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'phone' => array(
					'enabled'  => true,
					'required' => false,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 21,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'phone'                     => '600 111 222',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '+34600111222', $mailrelay->last_args['fields']['sms_phone'] ?? '' );
	}

	public function test_submit_use_case_combines_phone_prefix_with_number(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 23;
		$form->name   = 'Form 23';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'phone' => array(
					'enabled'  => true,
					'required' => false,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 23,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'phone_prefix'              => '+34',
					'phone'                     => '34600111222',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '+34600111222', $mailrelay->last_args['fields']['sms_phone'] ?? '' );
	}

	public function test_submit_use_case_uses_default_prefix_when_selector_hidden(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'hide_phone_prefix_selector'    => '1',
				'default_phone_country'         => 'ES',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 24;
		$form->name   = 'Form 24';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'phone' => array(
					'enabled'  => true,
					'required' => false,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 24,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'phone'                     => '600111222',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( '+34600111222', $mailrelay->last_args['fields']['sms_phone'] ?? '' );
	}

	public function test_submit_use_case_returns_phone_error_on_invalid_phone(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 22;
		$form->name   = 'Form 22';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
			),
			'fields'      => array(
				'phone' => array(
					'enabled'  => true,
					'required' => false,
				),
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_form_id'   => 22,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
					'phone'                     => 'abcdef',
				),
			)
		);

		$this->assertSame( 'phone', $result['status'] );
	}

	public function test_submit_use_case_uses_forced_locale(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'locale_fallback'               => 'fr',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 99;
		$form->name   = 'Form 99';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
				'locale_mode'       => 'force',
				'locale'            => 'es',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$turnstile,
			$rate_limiter,
			$request_context,
			$sanitizer,
			$forms
		);

		$use_case->process_submission(
			array(
				'relaypress_form_id'   => 99,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'es', $mailrelay->last_args['locale'] ?? '' );
	}

	public function test_submit_use_case_falls_back_when_locale_unsupported(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'locale_fallback'               => 'it',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context( 'de-DE,de;q=0.9' );
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new Test_Form_Repository();

		$use_case = new RelayPress_Submit_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$turnstile,
			$rate_limiter,
			$request_context,
			$sanitizer,
			$forms
		);

		$use_case->process_submission(
			array(
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'it', $mailrelay->last_args['locale'] ?? '' );
	}

	public function test_submit_use_case_uses_browser_locale_when_supported(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'locale_fallback'               => 'en',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context( 'fr-FR,es;q=0.9' );
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new Test_Form_Repository();

		$use_case = new RelayPress_Submit_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$turnstile,
			$rate_limiter,
			$request_context,
			$sanitizer,
			$forms
		);

		$use_case->process_submission(
			array(
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'fr', $mailrelay->last_args['locale'] ?? '' );
	}

	public function test_submit_use_case_inherits_global_force_locale(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => 'secret_key',
				'locale_mode'                   => 'force',
				'locale_force'                  => 'pt-BR',
				'locale_fallback'               => 'en',
			),
		);

		$form = new RelayPress_Form();
		$form->id     = 11;
		$form->name   = 'Form 11';
		$form->status = 'publish';
		$form->config = array(
			'destination' => array(
				'group_ids'         => '1',
				'subscriber_status' => 'active',
				'locale_mode'       => 'inherit',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context( 'es-ES,es;q=0.9' );
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new SubmitUseCase_Test_Form_Repository( $form );

		$use_case = new RelayPress_Submit_Use_Case(
			$mailrelay,
			$options,
			$logs,
			$turnstile,
			$rate_limiter,
			$request_context,
			$sanitizer,
			$forms
		);

		$use_case->process_submission(
			array(
				'relaypress_form_id'   => 11,
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'pt-BR', $mailrelay->last_args['locale'] ?? '' );
	}

	public function test_submit_use_case_skips_turnstile_when_not_configured(): void {
		$GLOBALS['relaypress_test_options'] = array(
			RelayPress_Newsletter::OPT_KEY => array(
				'api_base_url'                  => 'https://api.test/api/v1',
				'api_token'                     => 'token',
				'group_ids'                     => '1',
				'subscriber_status'             => 'active',
				'confirm_resend_max'            => 0,
				'confirm_resend_window_seconds' => 3600,
				'store_consent_log'             => '0',
				'rate_limit_max'                => 5,
				'rate_limit_window_seconds'     => 3600,
				'turnstile_secret_key'          => '',
				'turnstile_site_key'            => '',
			),
		);

		$mailrelay       = new Test_Mailrelay_Client();
		$options         = new Test_Options_Repository();
		$logs            = new Test_Logs_Repository();
		$turnstile       = new Test_Turnstile_Verifier_Tracker();
		$rate_limiter    = new Test_Rate_Limiter();
		$request_context = new Test_Request_Context();
		$sanitizer       = new Test_Input_Sanitizer();
		$forms           = new Test_Form_Repository();

		$use_case = new RelayPress_Submit_Use_Case(
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
				'relaypress_hp'        => '',
				'relaypress_group_ids' => '1',
				'subscriber'       => array(
					'email'                     => 'test@example.com',
					'subscribed_with_acceptance' => '1',
				),
			)
		);

		$this->assertSame( 'ok', $result['status'] );
		$this->assertSame( 0, $turnstile->calls );
	}
}

final class Test_Mailrelay_Client implements RelayPress_Mailrelay_Client {
	/**
	 * @var array<int>
	 */
	public $last_group_ids = array();
	/**
	 * @var array<string, mixed>
	 */
	public $last_args = array();

	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		$this->last_group_ids = $group_ids;
		$this->last_args      = $args;
		return array(
			'ok'        => true,
			'http_code' => 201,
			'body'      => '',
		);
	}

	public function update_subscriber_fields_by_email( string $email, array $args = array() ): array {
		unset( $email, $args );
		return array(
			'ok'        => true,
			'http_code' => 200,
			'body'      => '',
		);
	}

	public function get_groups( bool $force_refresh = false ): array {
		return array();
	}
}

final class Test_Mailrelay_Client_Tracker implements RelayPress_Mailrelay_Client {
	public int $calls = 0;

	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		$this->calls++;
		return array(
			'ok'        => true,
			'http_code' => 201,
			'body'      => '',
		);
	}

	public function update_subscriber_fields_by_email( string $email, array $args = array() ): array {
		unset( $email, $args );
		return array(
			'ok'        => true,
			'http_code' => 200,
			'body'      => '',
		);
	}

	public function get_groups( bool $force_refresh = false ): array {
		return array();
	}
}

final class Test_Mailrelay_Client_Retryable implements RelayPress_Mailrelay_Client {
	public function subscribe_with_confirmation( string $email, array $group_ids, bool $accepted, string $ip, array $args = array() ): array {
		unset( $email, $group_ids, $accepted, $ip, $args );
		return array(
			'ok'        => false,
			'http_code' => 503,
			'body'      => 'Service Unavailable',
			'retryable' => true,
		);
	}

	public function update_subscriber_fields_by_email( string $email, array $args = array() ): array {
		unset( $email, $args );
		return array(
			'ok'        => false,
			'http_code' => 503,
			'body'      => 'Service Unavailable',
			'retryable' => true,
		);
	}

	public function get_groups( bool $force_refresh = false ): array {
		return array();
	}
}

final class Test_Options_Repository implements RelayPress_Options_Repository {
	public function get_options(): array {
		return RelayPress_Newsletter::get_options();
	}
}

final class Test_Task_Scheduler implements RelayPress_Task_Scheduler {
	public int $enqueued = 0;
	public int $scheduled = 0;

	public function is_available(): bool {
		return true;
	}

	public function enqueue( string $hook, array $args = array(), string $group = '' ): bool {
		unset( $hook, $args, $group );
		$this->enqueued++;
		return true;
	}

	public function schedule( int $timestamp, string $hook, array $args = array(), string $group = '' ): bool {
		unset( $timestamp, $hook, $args, $group );
		$this->scheduled++;
		return true;
	}
}

final class Test_Logs_Repository implements RelayPress_Logs_Repository {
	public array $last_log = array();

	public function ensure_table(): void {}

	public function store_consent_log( array $data ): void {
		$this->last_log = $data;
	}
}

final class Test_Turnstile_Verifier implements RelayPress_Turnstile_Verifier {
	public function verify( string $token, string $ip ): bool {
		return true;
	}
}

final class Test_Turnstile_Verifier_Tracker implements RelayPress_Turnstile_Verifier {
	public int $calls = 0;

	public function verify( string $token, string $ip ): bool {
		$this->calls++;
		return false;
	}
}

final class Test_Rate_Limiter implements RelayPress_Rate_Limiter {
	public function hit( string $key, int $max, int $window_seconds ): bool {
		return true;
	}
}

final class Test_Request_Context implements RelayPress_Request_Context {
	private string $accept_language;

	public function __construct( string $accept_language = '' ) {
		$this->accept_language = $accept_language;
	}

	public function get_client_ip(): string {
		return '203.0.113.10';
	}

	public function get_user_agent(): string {
		return 'test-agent';
	}

	public function get_page_url_from_request( array $data ): string {
		return 'https://example.test/';
	}

	public function get_accept_language(): string {
		return $this->accept_language;
	}

	public function current_time_mysql(): string {
		return '2024-01-01 00:00:00';
	}
}

final class Test_Input_Sanitizer implements RelayPress_Input_Sanitizer {
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

final class Test_Form_Repository implements RelayPress_Form_Repository_Interface {
	public function get( int $id ): ?RelayPress_Form {
		return null;
	}

	public function list( array $args = array() ): array {
		return array();
	}

	public function create( string $name, array $config, string $status ): ?RelayPress_Form {
		return null;
	}

	public function update( int $id, string $name, array $config, string $status ): ?RelayPress_Form {
		return null;
	}

	public function trash( int $id ): bool {
		return false;
	}

	public function count_published( ?int $exclude_id = null ): int {
		return 0;
	}
}

final class SubmitUseCase_Test_Form_Repository implements RelayPress_Form_Repository_Interface {
	private ?RelayPress_Form $form;

	public function __construct( ?RelayPress_Form $form = null ) {
		$this->form = $form;
	}

	public function get( int $id ): ?RelayPress_Form {
		if ( $this->form && $this->form->id === $id ) {
			return $this->form;
		}
		return null;
	}

	public function list( array $args = array() ): array {
		return $this->form ? array( $this->form ) : array();
	}

	public function create( string $name, array $config, string $status ): ?RelayPress_Form {
		return null;
	}

	public function update( int $id, string $name, array $config, string $status ): ?RelayPress_Form {
		return null;
	}

	public function trash( int $id ): bool {
		return false;
	}

	public function count_published( ?int $exclude_id = null ): int {
		return 0;
	}
}
