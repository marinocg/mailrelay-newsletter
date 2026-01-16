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
	 * Whether Turnstile assets were requested on the current request.
	 *
	 * @var bool
	 */
	private static bool $turnstile_requested = false;

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
		$opts     = UVE_Mailrelay_Newsletter::get_options();
		$defaults = UVE_MR_Form_Config::defaults( $opts );
		$raw_atts = is_array( $atts ) ? $atts : array();
		$atts     = shortcode_atts(
			array(
				'id'                => '',
				'title'             => '',
				'description'       => '',
				'email_placeholder' => '',
				'submit_label'      => '',
				'group_ids'         => '',
				'privacy_url'       => '',
				'consent_label'     => '',
				'class'             => '',
				'ajax'              => '',
			),
			$raw_atts
		);

		$form_id = isset( $atts['id'] ) ? (int) $atts['id'] : 0;
		$form    = UVE_MR_Form_Use_Cases::get_form_for_render( $form_id );
		if ( ! $form ) {
			return self::render_missing_form_notice();
		}
		$config = $form->merge_config( $defaults );
		$config = self::apply_legacy_overrides( $config, $raw_atts );

		$extra_class = sanitize_text_field( (string) ( $atts['class'] ?? '' ) );
		$render_id   = $form->id;

		return self::render_form( $config, $extra_class, $render_id );
	}

	/**
	 * Render admin-only placeholder when no form is available.
	 *
	 * @return string
	 */
	private static function render_missing_form_notice(): string {
		if ( function_exists( 'current_user_can' ) && current_user_can( 'manage_options' ) ) {
			$create_url = add_query_arg(
				array(
					'page'   => 'uve-mr-newsletter-forms',
					'action' => 'new',
				),
				admin_url( 'admin.php' )
			);
			$message    = sprintf(
				'%s <a href="%s">%s</a>.',
				esc_html__( 'RelayPress is active, but no forms are published yet.', 'uve-mailrelay-newsletter' ),
				esc_url( $create_url ),
				esc_html__( 'Create a form', 'uve-mailrelay-newsletter' )
			);

			return '<div class="uve-mr-empty-form-notice">' . wp_kses_post( $message ) . '</div>';
		}
		return '';
	}

	/**
	 * Enqueue frontend assets if needed.
	 *
	 * @param bool $needs_turnstile Whether Turnstile assets are required.
	 * @return void
	 */
	private static function ensure_assets( bool $needs_turnstile ): void {
		self::$turnstile_requested = self::$turnstile_requested || $needs_turnstile;
		if ( self::$assets_requested ) {
			return;
		}
		self::$assets_requested = true;

		add_action(
			'wp_footer',
			function () {
				if ( ! self::$turnstile_requested ) {
					return;
				}
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
				if ( ! self::$turnstile_requested ) {
					return;
				}
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
				?>
			<script>
				(function() {
					document.addEventListener('submit', function(ev) {
						var form = ev.target;
						if (!form || !form.classList || !form.classList.contains('uve-mr-form')) return;
						var btn = form.querySelector('button[type="submit"], input[type="submit"]');
						if (btn) {
							btn.disabled = true;
							btn.setAttribute('aria-busy', 'true');
						}
						form.classList.add('uve-mr-loading');
					});
				})();
			</script>
				<?php
			},
			55
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

					function setLoading(form, isLoading) {
						var btn = form.querySelector('button[type="submit"], input[type="submit"]');
						if (btn) {
							btn.disabled = !!isLoading;
							if (isLoading) {
								btn.setAttribute('aria-busy', 'true');
							} else {
								btn.removeAttribute('aria-busy');
							}
						}
						if (isLoading) {
							form.classList.add('uve-mr-loading');
						} else {
							form.classList.remove('uve-mr-loading');
						}
					}

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
						setLoading(form, true);
						var data = new FormData(form);
						data.set('action', 'uve_mr_subscribe_ajax');

						fetch(url, { method: 'POST', body: data, credentials: 'same-origin' })
							.then(function(resp) { return resp.json(); })
							.then(function(payload) {
								if (!payload || !payload.data) {
									setLoading(form, false);
									return;
								}
								var status = payload.data.status || 'error';
								var message = payload.data.message || '';
								renderMessage(form, status, message);
								setLoading(form, false);
							})
							.catch(function() {
								renderMessage(form, 'error', uveMrAjaxFallbackMessage || '');
								setLoading(form, false);
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
	 * @param array  $config Form config.
	 * @param string $extra_class Extra wrapper class.
	 * @param int    $form_id Form ID.
	 * @return string
	 */
	private static function render_form( array $config, string $extra_class, int $form_id ): string {
		$email_placeholder = (string) ( $config['fields']['email']['placeholder'] ?? $config['basics']['email_placeholder'] ?? __( 'Email...', 'uve-mailrelay-newsletter' ) );
		$title             = (string) ( $config['basics']['title'] ?? '' );
		$desc              = (string) ( $config['basics']['description'] ?? '' );
		$submit            = (string) ( $config['basics']['submit_label'] ?? __( 'Subscribe', 'uve-mailrelay-newsletter' ) );
		$group_ids         = (string) ( $config['destination']['group_ids'] ?? '' );
		$privacy_url       = (string) ( $config['consent']['privacy_url'] ?? '' );
		$consent_label     = (string) ( $config['consent']['label'] ?? __( 'I accept the privacy policy', 'uve-mailrelay-newsletter' ) );
		$ajax_enabled      = '1' === (string) ( $config['ajax'] ?? '0' );
		$messages          = $config['messages'] ?? array();
		$ajax_error_msg    = (string) ( $messages['error'] ?? __( 'We could not complete the request. Please try again.', 'uve-mailrelay-newsletter' ) );
		$turnstile_enabled = self::turnstile_enabled( $config );
		$site_key          = $turnstile_enabled ? UVE_MR_Turnstile::get_site_key() : '';

		$action   = admin_url( 'admin-post.php' );
		$ajax_url = admin_url( 'admin-ajax.php' );

		$msg_html    = '';
		$form_marker = isset( $_GET['uve_mr_form_id'] ) ? absint( wp_unslash( $_GET['uve_mr_form_id'] ) ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( $form_marker && $form_marker !== $form_id ) {
			$msg_html = '';
		} elseif ( isset( $_GET['uve_mr_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$st = sanitize_text_field( (string) wp_unslash( $_GET['uve_mr_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'ok' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-ok">%s</p>',
					esc_html( (string) ( $messages['success'] ?? '' ) )
				);
			} elseif ( 'captcha' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html( (string) ( $messages['captcha'] ?? '' ) )
				);
			} elseif ( 'consent' === $st ) {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html( (string) ( $messages['consent'] ?? '' ) )
				);
			} else {
				$msg_html = sprintf(
					'<p class="uve-mr-msg uve-mr-err">%s</p>',
					esc_html( (string) ( $messages['error'] ?? '' ) )
				);
			}
		}

		$template_path = self::resolve_template_path();
		if ( $ajax_enabled ) {
			self::$ajax_requested = true;
		}
		self::$ajax_error_msg = $ajax_error_msg;
		self::ensure_assets( $turnstile_enabled );

		$context = array(
			'form_id'           => $form_id,
			'email_placeholder' => $email_placeholder,
			'title'             => $title,
			'desc'              => $desc,
			'submit'            => $submit,
			'group_ids'         => $group_ids,
			'privacy_url'       => $privacy_url,
			'consent_label'     => $consent_label,
			'class'             => $extra_class,
			'fields'            => $config['fields'] ?? array(),
			'site_key'          => $site_key,
			'turnstile_enabled' => $turnstile_enabled,
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
		$fields            = $context['fields'];
		$site_key          = $context['site_key'];
		$turnstile_enabled = $context['turnstile_enabled'];
		$action            = $context['action'];
		$msg_html          = $context['msg_html'];
		$ajax_enabled      = $context['ajax_enabled'];
		$ajax_url          = $context['ajax_url'];
		$ajax_error_msg    = $context['ajax_error_msg'];
		$form_id           = $context['form_id'];
		require $template_path;
		return (string) ob_get_clean();
	}

	/**
	 * Apply legacy shortcode overrides to config.
	 *
	 * @param array $config Form config.
	 * @param array $raw_atts Raw shortcode attributes.
	 * @return array
	 */
	private static function apply_legacy_overrides( array $config, array $raw_atts ): array {
		if ( array_key_exists( 'title', $raw_atts ) ) {
			$config['basics']['title'] = (string) $raw_atts['title'];
		}
		if ( array_key_exists( 'description', $raw_atts ) ) {
			$config['basics']['description'] = (string) $raw_atts['description'];
		}
		if ( array_key_exists( 'email_placeholder', $raw_atts ) ) {
			$config['basics']['email_placeholder'] = (string) $raw_atts['email_placeholder'];
		}
		if ( array_key_exists( 'submit_label', $raw_atts ) ) {
			$config['basics']['submit_label'] = (string) $raw_atts['submit_label'];
		}
		if ( array_key_exists( 'group_ids', $raw_atts ) ) {
			$config['destination']['group_ids'] = (string) $raw_atts['group_ids'];
		}
		if ( array_key_exists( 'privacy_url', $raw_atts ) ) {
			$config['consent']['privacy_url'] = (string) $raw_atts['privacy_url'];
			$config['consent']['inherit']     = false;
		}
		if ( array_key_exists( 'consent_label', $raw_atts ) ) {
			$config['consent']['label']   = (string) $raw_atts['consent_label'];
			$config['consent']['inherit'] = false;
		}
		if ( array_key_exists( 'ajax', $raw_atts ) ) {
			$config['ajax'] = (string) $raw_atts['ajax'];
		}

		return $config;
	}

	/**
	 * Determine if Turnstile should be enabled for the form config.
	 *
	 * @param array $config Form config.
	 * @return bool
	 */
	private static function turnstile_enabled( array $config ): bool {
		$turnstile = $config['turnstile'] ?? array();
		$mode      = (string) ( $turnstile['mode'] ?? 'inherit' );
		if ( 'off' === $mode ) {
			return false;
		}
		if ( 'on' === $mode ) {
			return true;
		}
		return UVE_MR_Turnstile::is_enabled();
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
