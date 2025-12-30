<?php
/**
 * Frontend rendering and assets.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Frontend rendering and assets.
 */
final class UVE_MR_Frontend {

	/**
	 * Whether assets were requested on the current request.
	 *
	 * @var bool
	 */
	private static bool $assets_requested = false;

	/**
	 * Whether AJAX assets were requested on the current request.
	 *
	 * @var bool
	 */
	private static bool $ajax_requested = false;

	/**
	 * Fallback error message for AJAX responses.
	 *
	 * @var string
	 */
	private static string $ajax_error_msg = '';

	/**
	 * Register the shortcode.
	 *
	 * @return void
	 */
	public static function register_shortcode(): void {
		add_shortcode( 'uve_mailrelay_newsletter', array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Shortcode handler.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts = array() ): string {
		$opts = UVE_Mailrelay_Newsletter::get_options();
		$atts = shortcode_atts(
			array(
				'title'             => $opts['title'],
				'description'       => $opts['description'],
				'email_placeholder' => $opts['email_placeholder'],
				'submit_label'      => $opts['submit_label'],
				'group_ids'         => $opts['group_ids'],
				'privacy_url'       => $opts['privacy_url'],
				'consent_label'     => $opts['consent_label'],
				'class'             => '',
				'ajax'              => $opts['ajax_mode'] ?? '0',
			),
			is_array( $atts ) ? $atts : array()
		);
		return self::render_form( $atts );
	}

	/**
	 * Enqueue frontend assets if needed.
	 *
	 * @return void
	 */
	private static function ensure_assets(): void {
		if ( self::$assets_requested ) {
			return;
		}
		self::$assets_requested = true;

		add_action(
			'wp_footer',
			function () {
				if ( ! wp_script_is( 'cf-turnstile', 'enqueued' ) && ! wp_script_is( 'cf-turnstile', 'done' ) ) {
					wp_enqueue_script(
						'cf-turnstile',
						'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit',
						array(),
						UVE_Mailrelay_Newsletter::VERSION,
						true
					);
				}
			},
			5
		);

		add_action(
			'wp_footer',
			function () {
				$site_key = UVE_MR_Turnstile::get_site_key();
				if ( ! $site_key ) {
					return;
				}
				?>
			<script>
				(function() {
					function renderAll() {
						if (!window.turnstile) return;
						document.querySelectorAll('.uve-mr-turnstile[data-sitekey]').forEach(function(el) {
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
					window.__uveMrRenderTurnstile = renderAll;
				})();
			</script>
				<?php
			},
			50
		);

		add_action(
			'wp_footer',
			function () {
				if ( ! self::$ajax_requested ) {
					return;
				}
				?>
			<script>
				(function() {
					var uveMrAjaxFallbackMessage = <?php echo wp_json_encode( self::$ajax_error_msg ); ?>;

					function renderMessage(form, status, message) {
						var wrapper = form.closest('.uve-mr-newsletter');
						if (!wrapper) return;
						var target = wrapper.querySelector('.uve-mr-response');
						if (!target) {
							target = document.createElement('div');
							target.className = 'uve-mr-response';
							wrapper.insertBefore(target, form);
						}
						var cls = (status === 'ok') ? 'uve-mr-ok' : 'uve-mr-err';
						var p = document.createElement('p');
						p.className = 'uve-mr-msg ' + cls;
						p.textContent = message || '';
						target.innerHTML = '';
						target.appendChild(p);
					}

					function submitForm(form) {
						var url = form.getAttribute('data-ajax-url');
						if (!url) return;
						var data = new FormData(form);
						data.set('action', 'uve_mr_subscribe_ajax');

						fetch(url, { method: 'POST', body: data, credentials: 'same-origin' })
							.then(function(resp) { return resp.json(); })
							.then(function(payload) {
								if (!payload || !payload.data) return;
								var status = payload.data.status || 'error';
								var message = payload.data.message || '';
								renderMessage(form, status, message);
							})
							.catch(function() {
								renderMessage(form, 'error', uveMrAjaxFallbackMessage || '');
							});
					}

					document.addEventListener('submit', function(ev) {
						var form = ev.target;
						if (!form || !form.classList || !form.classList.contains('uve-mr-form')) return;
						if (form.getAttribute('data-ajax') !== '1') return;
						ev.preventDefault();
						submitForm(form);
					});
				})();
			</script>
				<?php
			},
			60
		);
	}

	/**
	 * Render the subscription form.
	 *
	 * @param array $args Shortcode arguments.
	 * @return string
	 */
	private static function render_form( array $args ): string {
		$email_placeholder = $args['email_placeholder'] ?? __( 'Email...', 'uve-mailrelay-newsletter' );
		$title             = $args['title'] ?? '';
		$desc              = $args['description'] ?? '';
		$submit            = $args['submit_label'] ?? __( 'Subscribe', 'uve-mailrelay-newsletter' );
		$group_ids         = $args['group_ids'] ?? '';
		$privacy_url       = $args['privacy_url'] ?? '';
		$consent_label     = $args['consent_label'] ?? __( 'I accept the privacy policy', 'uve-mailrelay-newsletter' );
		$class             = $args['class'] ?? '';
		$ajax_enabled      = '1' === (string) ( $args['ajax'] ?? '0' );
		$ajax_error_msg    = __( 'We could not complete the request. Please try again.', 'uve-mailrelay-newsletter' );

		$site_key = UVE_MR_Turnstile::get_site_key();
		$action   = admin_url( 'admin-post.php' );
		$ajax_url = admin_url( 'admin-ajax.php' );

		$msg_html = '';
		if ( isset( $_GET['uve_mr_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$st = sanitize_text_field( (string) wp_unslash( $_GET['uve_mr_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'ok' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-ok">%s</p>',
					esc_html__( 'Thanks. If the email is valid, you will receive a confirmation email (or you were already subscribed).', 'uve-mailrelay-newsletter' )
				);
			} elseif ( 'captcha' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html__( 'Please verify you are human.', 'uve-mailrelay-newsletter' )
				);
			} elseif ( 'consent' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html__( 'You must accept the privacy policy.', 'uve-mailrelay-newsletter' )
				);
			} else {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html__( 'We could not complete the request. Please try again.', 'uve-mailrelay-newsletter' )
				);
			}
		}

		$template_path = self::resolve_template_path();
		if ( $ajax_enabled ) {
			self::$ajax_requested = true;
		}
		self::$ajax_error_msg = $ajax_error_msg;
		self::ensure_assets();

		$context = array(
			'email_placeholder' => $email_placeholder,
			'title'             => $title,
			'desc'              => $desc,
			'submit'            => $submit,
			'group_ids'         => $group_ids,
			'privacy_url'       => $privacy_url,
			'consent_label'     => $consent_label,
			'class'             => $class,
			'site_key'          => $site_key,
			'action'            => $action,
			'msg_html'          => $msg_html,
			'ajax_enabled'      => $ajax_enabled,
			'ajax_url'          => $ajax_url,
			'ajax_error_msg'    => $ajax_error_msg,
		);

		ob_start();
		$email_placeholder = $context['email_placeholder'];
		$title             = $context['title'];
		$desc              = $context['desc'];
		$submit            = $context['submit'];
		$group_ids         = $context['group_ids'];
		$privacy_url       = $context['privacy_url'];
		$consent_label     = $context['consent_label'];
		$class             = $context['class'];
		$site_key          = $context['site_key'];
		$action            = $context['action'];
		$msg_html          = $context['msg_html'];
		$ajax_enabled      = $context['ajax_enabled'];
		$ajax_url          = $context['ajax_url'];
		$ajax_error_msg    = $context['ajax_error_msg'];
		require $template_path;
		return (string) ob_get_clean();
	}

	/**
	 * Resolve the template path, allowing theme overrides.
	 *
	 * @return string
	 */
	private static function resolve_template_path(): string {
		$default = __DIR__ . '/../templates/form.php';

		if ( function_exists( 'locate_template' ) ) {
			$templates = array(
				'uve-mr-newsletter/form.php',
				'mr4wp/form.php',
			);
			$found     = locate_template( $templates );
			if ( $found ) {
				return $found;
			}
		}

		return $default;
	}
}
