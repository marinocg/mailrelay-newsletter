<?php
/**
 * Turnstile extension hooks.
 *
 * @package RelayPress_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Turnstile extension hooks.
 */
final class RelayPress_Turnstile_Extension {

	/**
	 * Track whether Turnstile assets are needed.
	 *
	 * @var bool
	 */
	private static bool $assets_requested = false;

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public static function register(): void {
		RelayPress_Turnstile_Extension_Provider::register();
		add_filter( 'relaypress_extension_form_context', array( __CLASS__, 'extend_form_context' ), 10, 3 );
		add_filter( 'relaypress_extension_needs_assets', array( __CLASS__, 'needs_assets' ), 10, 3 );
		add_action( 'relaypress_extension_render_fields', array( __CLASS__, 'render_fields' ), 10, 1 );
		add_action( 'relaypress_extension_enqueue_assets', array( __CLASS__, 'enqueue_assets' ), 10, 1 );
		add_filter( 'relaypress_extension_verify_submission', array( __CLASS__, 'verify_submission' ), 10, 4 );
	}

	/**
	 * Extend form context with Turnstile data.
	 *
	 * @param array $context Form context.
	 * @param array $config Form config.
	 * @param int   $form_id Form ID.
	 * @return array
	 */
	public static function extend_form_context( array $context, array $config, int $form_id ): array {
		unset( $form_id );
		$enabled                       = self::is_enabled_for_config( $config );
		$context['turnstile_enabled']  = $enabled;
		$context['turnstile_site_key'] = $enabled ? RelayPress_Container::turnstile_config()->get_site_key() : '';
		return $context;
	}

	/**
	 * Determine if assets should be enqueued.
	 *
	 * @param bool  $needed Whether assets are needed.
	 * @param array $config Form config.
	 * @param int   $form_id Form ID.
	 * @return bool
	 */
	public static function needs_assets( bool $needed, array $config, int $form_id ): bool {
		unset( $form_id );
		if ( ! self::should_load_script() ) {
			return false;
		}
		if ( $needed ) {
			self::$assets_requested = true;
			return true;
		}
		$enabled = self::is_enabled_for_config( $config );
		$key     = $enabled ? RelayPress_Container::turnstile_config()->get_site_key() : '';
		if ( '' !== $key ) {
			self::$assets_requested = true;
			return true;
		}
		return false;
	}

	/**
	 * Render Turnstile fields inside the form.
	 *
	 * @param array $context Form context.
	 * @return void
	 */
	public static function render_fields( array $context ): void {
		$enabled  = ! empty( $context['turnstile_enabled'] );
		$site_key = (string) ( $context['site_key'] ?? '' );
		if ( ! $enabled ) {
			return;
		}
		if ( '' !== $site_key ) {
			?>
			<div class="relaypress-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
			<noscript>
				<p><?php echo esc_html__( 'Enable JavaScript to subscribe.', 'relaypress-newsletter' ); ?></p>
			</noscript>
			<?php
			return;
		}
		?>
		<p class="relaypress-msg relaypress-err"><?php echo esc_html__( 'Turnstile is not configured (Site Key).', 'relaypress-newsletter' ); ?></p>
		<?php
	}

	/**
	 * Enqueue Turnstile assets when requested.
	 *
	 * @param array $contexts Form contexts.
	 * @return void
	 */
	public static function enqueue_assets( array $contexts = array() ): void {
		unset( $contexts );
		if ( ! self::should_load_script() ) {
			return;
		}
		if ( ! self::$assets_requested ) {
			return;
		}

		if ( ! wp_script_is( 'cf-turnstile', 'enqueued' ) && ! wp_script_is( 'cf-turnstile', 'done' ) ) {
			wp_enqueue_script(
				'cf-turnstile',
				'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
				array(),
				RelayPress_Newsletter::VERSION,
				true
			);
		}
		?>
		<script>
			(function() {
				function renderAll() {
					if (!window.turnstile) return;
					document.querySelectorAll('.relaypress-turnstile[data-sitekey]').forEach(function(el) {
						if (el.getAttribute('data-rendered') === '1') return;
						el.setAttribute('data-rendered', '1');
						try {
							window.turnstile.render(el, {
								sitekey: el.getAttribute('data-sitekey')
							});
						} catch (e) {}
					});
				}
				if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', renderAll);
				else renderAll();
				window.__relaypressRenderTurnstile = renderAll;
			})();
		</script>
		<?php
	}

	/**
	 * Verify Turnstile submission when enabled.
	 *
	 * @param array|null $result Current result.
	 * @param array      $data Submission payload.
	 * @param array      $context Form context.
	 * @param string     $ip Client IP.
	 * @return array|null
	 */
	public static function verify_submission( ?array $result, array $data, array $context, string $ip ): ?array {
		if ( is_array( $result ) && 'ok' !== ( $result['status'] ?? 'ok' ) ) {
			return $result;
		}

		$config = $context['config'] ?? array();
		if ( ! is_array( $config ) || ! self::is_enabled_for_config( $config ) ) {
			return $result;
		}

		$token = $data['cf-turnstile-response'] ?? '';
		$token = is_string( $token ) ? $token : '';
		$token = sanitize_text_field( wp_unslash( $token ) );
		if ( ! RelayPress_Container::turnstile_verifier()->verify( $token, $ip ) ) {
			return array( 'status' => 'captcha' );
		}

		return $result;
	}

	/**
	 * Determine if Turnstile is enabled for a form config.
	 *
	 * @param array $config Form config.
	 * @return bool
	 */
	private static function is_enabled_for_config( array $config ): bool {
		unset( $config );
		$state = new RelayPress_WP_Extension_State_Repository();
		if ( ! $state->is_enabled( RelayPress_Turnstile_Extension_Provider::SLUG, true ) ) {
			return false;
		}

		return RelayPress_Container::turnstile_config()->is_enabled();
	}

	/**
	 * Determine if the Turnstile frontend script should be loaded.
	 *
	 * @return bool
	 */
	private static function should_load_script(): bool {
		$opts = RelayPress_Newsletter::get_options();
		return '1' === (string) ( $opts['turnstile_load_js'] ?? '1' );
	}
}
