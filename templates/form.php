<?php
/**
 * Newsletter form template.
 *
 * @package RelayPress_Newsletter
 *
 * @var string $email_placeholder
 * @var string $title
 * @var string $desc
 * @var string $submit
 * @var string $group_ids
 * @var string $privacy_url
 * @var string $consent_label
 * @var string $class
 * @var string $site_key
 * @var string $action
 * @var string $msg_html
 * @var bool   $ajax_enabled
 * @var string $ajax_url
 * @var string $ajax_error_msg
 * @var int    $form_id
 * @var array  $fields
 * @var bool   $turnstile_enabled
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="relaypress-newsletter <?php echo esc_attr( $class ); ?>">
	<?php
	if ( $title ) :
		?>
		<h2 class="widgettitle"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
	<div class="relaypress-response">
		<?php
		echo $msg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>

	<form class="simple_form form form-vertical relaypress-form" method="post" action="<?php echo esc_url( $action ); ?>" accept-charset="UTF-8" data-ajax="<?php echo esc_attr( $ajax_enabled ? '1' : '0' ); ?>" data-ajax-url="<?php echo esc_url( $ajax_url ); ?>">
		<input type="hidden" name="action" value="relaypress_subscribe">
		<?php wp_nonce_field( RelayPress_Newsletter::NONCE, '_wpnonce' ); ?>
		<input type="hidden" name="relaypress_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
		<input type="hidden" name="relaypress_group_ids" value="<?php echo esc_attr( $group_ids ); ?>">
		<input type="hidden" name="relaypress_page_url" value="<?php echo esc_attr( RelayPress_Utils::current_url() ); ?>">

		<div class="mc4wp-form-fields relaypress-form-fields">
			<?php
			if ( $desc ) :
				?>
				<p class="mdes"><?php echo esc_html( $desc ); ?></p><?php endif; ?>

			<?php if ( is_array( $fields ) ) : ?>
				<?php $country_options = null; ?>
				<?php foreach ( $fields as $key => $field ) : ?>
					<?php
					if ( ! is_array( $field ) || empty( $field['enabled'] ) ) {
						continue;
					}
					$field_type  = (string) ( $field['type'] ?? 'text' );
					$label       = (string) ( $field['label'] ?? '' );
					$placeholder = (string) ( $field['placeholder'] ?? '' );
					$required    = ! empty( $field['required'] );
					$name_attr   = 'subscriber[' . $key . ']';
					if ( 'email' === $key ) {
						$field_type  = 'email';
						$placeholder = $email_placeholder;
					}
					$label_text       = '' === $label ? ucfirst( (string) $key ) : $label;
					$placeholder_text = '' === $placeholder ? $label : $placeholder;
					$is_country       = ( 'country' === $field_type );
					if ( $is_country && null === $country_options ) {
						$country_options = (array) RelayPress_Utils::country_options();
						uasort(
							$country_options,
							static function ( string $left, string $right ): int {
								return strcasecmp( $left, $right );
							}
						);
					}
					if ( $is_country && '' === $placeholder_text ) {
						$placeholder_text = __( 'Select a country', 'relaypress-newsletter' );
					}
					?>
					<p class="mfield mfield-<?php echo esc_attr( $key ); ?>">
						<label class="relaypress-field-label" for="relaypress-<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $label_text ); ?>
							<?php if ( $required ) : ?>
								<span class="relaypress-required" aria-hidden="true">*</span>
							<?php endif; ?>
						</label>
						<?php if ( $is_country ) : ?>
							<select id="relaypress-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name_attr ); ?>" <?php echo $required ? 'required' : ''; ?>>
								<option value=""><?php echo esc_html( $placeholder_text ); ?></option>
								<?php foreach ( $country_options as $code => $country_label ) : ?>
									<option value="<?php echo esc_attr( $code ); ?>"><?php echo esc_html( $country_label ); ?></option>
								<?php endforeach; ?>
							</select>
						<?php else : ?>
							<input type="<?php echo esc_attr( $field_type ); ?>" id="relaypress-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name_attr ); ?>" placeholder="<?php echo esc_attr( $placeholder_text ); ?>" <?php echo $required ? 'required' : ''; ?>>
						<?php endif; ?>
					</p>
				<?php endforeach; ?>
			<?php endif; ?>

			<div style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
				<label><?php echo esc_html__( 'Leave this field empty', 'relaypress-newsletter' ); ?></label>
				<input type="text" name="relaypress_hp" tabindex="-1" autocomplete="off" value="">
			</div>

			<p class="mconsent">
				<input value="0" type="hidden" name="subscriber[subscribed_with_acceptance]">
				<label class="relaypress-consent-label">
					<input type="checkbox" value="1" name="subscriber[subscribed_with_acceptance]" required>
					<span>
						<?php echo esc_html( $consent_label ); ?>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>" rel="noopener" target="_blank">(<?php echo esc_html__( 'view', 'relaypress-newsletter' ); ?>)</a>
						<?php endif; ?>
						<br><small><?php echo esc_html__( 'You can unsubscribe at any time using the link in each email.', 'relaypress-newsletter' ); ?></small>
					</span>
				</label>
			</p>

			<?php if ( $turnstile_enabled && $site_key ) : ?>
				<div class="relaypress-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<noscript>
					<p><?php echo esc_html__( 'Enable JavaScript to subscribe.', 'relaypress-newsletter' ); ?></p>
				</noscript>
			<?php elseif ( $turnstile_enabled ) : ?>
				<p class="relaypress-msg relaypress-err"><?php echo esc_html__( 'Turnstile is not configured (Site Key).', 'relaypress-newsletter' ); ?></p>
			<?php endif; ?>

			<p class="msubmit">
				<input type="submit" value="<?php echo esc_attr( $submit ); ?>">
			</p>
		</div>
	</form>

	<style>
		.relaypress-newsletter .widgettitle {
			margin-top: 0;
			margin-bottom: 8px;
			font-size: clamp(22px, 2.2vw, 28px);
		}

		.relaypress-form-fields {
			display: grid;
			gap: 20px;
		}

		.relaypress-form-fields .mdes {
			margin: 0;
			color: var(--wp--preset--color--contrast-2, #555);
		}

		.relaypress-field-label {
			display: inline-block;
			margin-bottom: 6px;
			font-weight: 600;
			color: var(--wp--preset--color--contrast, #111);
		}

		.relaypress-required {
			color: #c31919;
			margin-left: 4px;
		}

		.relaypress-form-fields input[type="text"],
		.relaypress-form-fields input[type="email"],
		.relaypress-form-fields input[type="url"],
		.relaypress-form-fields input[type="tel"],
		.relaypress-form-fields input[type="date"],
		.relaypress-form-fields select {
			display: block;
			width: 100%;
			padding: 14px 16px;
			border-radius: 10px;
			border: 1px solid #7a7a7a;
			background: var(--wp--preset--color--base, #fff);
			box-sizing: border-box;
			line-height: 1.2;
		}

		.relaypress-form-fields input[type="text"]:focus,
		.relaypress-form-fields input[type="email"]:focus,
		.relaypress-form-fields input[type="url"]:focus,
		.relaypress-form-fields input[type="tel"]:focus,
		.relaypress-form-fields input[type="date"]:focus,
		.relaypress-form-fields select:focus {
			border-color: #111;
			box-shadow: 0 0 0 2px rgba(17, 17, 17, 0.2);
			outline: none;
		}

		.relaypress-consent-label {
			display: flex;
			gap: 10px;
			align-items: flex-start;
			font-size: 0.95em;
		}

		.relaypress-consent-label small {
			color: var(--wp--preset--color--contrast-2, #666);
		}

		.msubmit input[type="submit"] {
			width: 100%;
			padding: 14px 18px;
			border-radius: 10px;
			border: 1px solid #111;
			background: #111;
			color: #fff;
			font-weight: 600;
			cursor: pointer;
		}

		.msubmit input[type="submit"]:hover {
			opacity: 0.92;
		}

		.relaypress-msg {
			margin: 0 0 10px;
			padding: 10px 12px;
			border-radius: 10px;
			font-size: 0.95em;
		}

		.relaypress-ok {
			background: rgba(0, 128, 0, 0.08);
		}

		.relaypress-err {
			background: rgba(200, 0, 0, 0.08);
		}

		.relaypress-loading .msubmit input,
		.relaypress-loading .msubmit button {
			opacity: .6;
			cursor: not-allowed;
		}
	</style>
</div>
