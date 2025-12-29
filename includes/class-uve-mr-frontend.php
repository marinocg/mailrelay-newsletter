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
	}

	/**
	 * Render the subscription form.
	 *
	 * @param array $args Shortcode arguments.
	 * @return string
	 */
	private static function render_form( array $args ): string {
		self::ensure_assets();

		$email_placeholder = $args['email_placeholder'] ?? 'Email...';
		$title             = $args['title'] ?? '';
		$desc              = $args['description'] ?? '';
		$submit            = $args['submit_label'] ?? 'Suscribir';
		$group_ids         = $args['group_ids'] ?? '';
		$privacy_url       = $args['privacy_url'] ?? '';
		$consent_label     = $args['consent_label'] ?? 'Acepto la polA-tica de privacidad';
		$class             = $args['class'] ?? '';

		$site_key = UVE_MR_Turnstile::get_site_key();
		$action   = admin_url( 'admin-post.php' );

		$msg_html = '';
		if ( isset( $_GET['uve_mr_status'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$st = sanitize_text_field( (string) wp_unslash( $_GET['uve_mr_status'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'ok' === $st ) {
				$msg_html = '<p class="uve-mr-msg uve-mr-ok">Gracias. Si el email es valido, recibiras un correo para confirmar (o ya estabas suscrito).</p>';
			} elseif ( 'captcha' === $st ) {
				$msg_html = '<p class="uve-mr-msg uve-mr-err">Por favor, verifica que eres humano.</p>';
			} elseif ( 'consent' === $st ) {
				$msg_html = '<p class="uve-mr-msg uve-mr-err">Debes aceptar la politica de privacidad.</p>';
			} else {
				$msg_html = '<p class="uve-mr-msg uve-mr-err">No se pudo completar la solicitud. IntAcntalo de nuevo.</p>';
			}
		}

		ob_start();
		?>
		<div class="uve-mr-newsletter <?php echo esc_attr( $class ); ?>">
			<?php
			if ( $title ) :
				?>
				<h2 class="widgettitle"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
			<?php
			echo $msg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>

			<form class="simple_form form form-vertical uve-mr-form" method="post" action="<?php echo esc_url( $action ); ?>" accept-charset="UTF-8">
				<input type="hidden" name="action" value="uve_mr_subscribe">
				<?php wp_nonce_field( UVE_Mailrelay_Newsletter::NONCE, '_wpnonce' ); ?>
				<input type="hidden" name="uve_mr_group_ids" value="<?php echo esc_attr( $group_ids ); ?>">
				<input type="hidden" name="uve_mr_page_url" value="<?php echo esc_attr( UVE_MR_Utils::current_url() ); ?>">

				<div class="mc4wp-form-fields">
					<?php
					if ( $desc ) :
						?>
						<p class="mdes"><?php echo esc_html( $desc ); ?></p><?php endif; ?>

					<p class="memail">
						<input type="email" name="subscriber[email]" placeholder="<?php echo esc_attr( $email_placeholder ); ?>" required>
					</p>

					<div style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
						<label>Deja este campo vacA-o</label>
						<input type="text" name="uve_mr_hp" tabindex="-1" autocomplete="off" value="">
					</div>

					<p class="mconsent" style="margin: 8px 0;">
						<input value="0" type="hidden" name="subscriber[subscribed_with_acceptance]">
						<label style="display:flex; gap:8px; align-items:flex-start;">
							<input type="checkbox" value="1" name="subscriber[subscribed_with_acceptance]" required>
							<span>
								<?php echo esc_html( $consent_label ); ?>
								<?php if ( $privacy_url ) : ?>
									<a href="<?php echo esc_url( $privacy_url ); ?>" rel="noopener" target="_blank">(ver)</a>
								<?php endif; ?>
								<br><small>Puedes darte de baja en cualquier momento desde el enlace de cada email.</small>
							</span>
						</label>
					</p>

					<?php if ( $site_key ) : ?>
						<div class="uve-mr-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
						<noscript>
							<p>Activa JavaScript para poder suscribirte.</p>
						</noscript>
					<?php else : ?>
						<p class="uve-mr-msg uve-mr-err">Falta configurar Turnstile (Site Key).</p>
					<?php endif; ?>

					<p class="msubmit" style="margin-top: 10px;">
						<input type="submit" value="<?php echo esc_attr( $submit ); ?>">
					</p>
				</div>
			</form>

			<style>
				.uve-mr-msg {
					margin: 0 0 10px;
					padding: 10px 12px;
					border-radius: 6px;
				}

				.uve-mr-ok {
					background: rgba(0, 128, 0, .08);
				}

				.uve-mr-err {
					background: rgba(200, 0, 0, .08);
				}
			</style>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
