<?php
/**
 * Newsletter form template.
 *
 * @package UVE_Mailrelay_Newsletter
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
<div class="uve-mr-newsletter <?php echo esc_attr( $class ); ?>">
	<?php
	if ( $title ) :
		?>
		<h2 class="widgettitle"><?php echo esc_html( $title ); ?></h2><?php endif; ?>
	<div class="uve-mr-response">
		<?php
		echo $msg_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>

	<form class="simple_form form form-vertical uve-mr-form" method="post" action="<?php echo esc_url( $action ); ?>" accept-charset="UTF-8" data-ajax="<?php echo esc_attr( $ajax_enabled ? '1' : '0' ); ?>" data-ajax-url="<?php echo esc_url( $ajax_url ); ?>">
		<input type="hidden" name="action" value="uve_mr_subscribe">
		<?php wp_nonce_field( UVE_Mailrelay_Newsletter::NONCE, '_wpnonce' ); ?>
		<input type="hidden" name="uve_mr_form_id" value="<?php echo esc_attr( (string) $form_id ); ?>">
		<input type="hidden" name="uve_mr_group_ids" value="<?php echo esc_attr( $group_ids ); ?>">
		<input type="hidden" name="uve_mr_page_url" value="<?php echo esc_attr( UVE_MR_Utils::current_url() ); ?>">

		<div class="mc4wp-form-fields uve-mr-form-fields">
			<?php
			if ( $desc ) :
				?>
				<p class="mdes"><?php echo esc_html( $desc ); ?></p><?php endif; ?>

			<?php if ( is_array( $fields ) ) : ?>
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
					?>
					<p class="mfield mfield-<?php echo esc_attr( $key ); ?>">
						<label class="uve-mr-field-label" for="uve-mr-<?php echo esc_attr( $key ); ?>">
							<?php echo esc_html( $label_text ); ?>
							<?php if ( $required ) : ?>
								<span class="uve-mr-required" aria-hidden="true">*</span>
							<?php endif; ?>
						</label>
						<input type="<?php echo esc_attr( $field_type ); ?>" id="uve-mr-<?php echo esc_attr( $key ); ?>" name="<?php echo esc_attr( $name_attr ); ?>" placeholder="<?php echo esc_attr( $placeholder_text ); ?>" <?php echo $required ? 'required' : ''; ?>>
					</p>
				<?php endforeach; ?>
			<?php endif; ?>

			<div style="position:absolute;left:-9999px;height:0;overflow:hidden;" aria-hidden="true">
				<label><?php echo esc_html__( 'Leave this field empty', 'uve-mailrelay-newsletter' ); ?></label>
				<input type="text" name="uve_mr_hp" tabindex="-1" autocomplete="off" value="">
			</div>

			<p class="mconsent">
				<input value="0" type="hidden" name="subscriber[subscribed_with_acceptance]">
				<label class="uve-mr-consent-label">
					<input type="checkbox" value="1" name="subscriber[subscribed_with_acceptance]" required>
					<span>
						<?php echo esc_html( $consent_label ); ?>
						<?php if ( $privacy_url ) : ?>
							<a href="<?php echo esc_url( $privacy_url ); ?>" rel="noopener" target="_blank">(<?php echo esc_html__( 'view', 'uve-mailrelay-newsletter' ); ?>)</a>
						<?php endif; ?>
						<br><small><?php echo esc_html__( 'You can unsubscribe at any time using the link in each email.', 'uve-mailrelay-newsletter' ); ?></small>
					</span>
				</label>
			</p>

			<?php if ( $turnstile_enabled && $site_key ) : ?>
				<div class="uve-mr-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<noscript>
					<p><?php echo esc_html__( 'Enable JavaScript to subscribe.', 'uve-mailrelay-newsletter' ); ?></p>
				</noscript>
			<?php elseif ( $turnstile_enabled ) : ?>
				<p class="uve-mr-msg uve-mr-err"><?php echo esc_html__( 'Turnstile is not configured (Site Key).', 'uve-mailrelay-newsletter' ); ?></p>
			<?php endif; ?>

			<p class="msubmit">
				<input type="submit" value="<?php echo esc_attr( $submit ); ?>">
			</p>
		</div>
	</form>

	<style>
		.uve-mr-newsletter .widgettitle {
			margin-top: 0;
			margin-bottom: 8px;
			font-size: clamp(22px, 2.2vw, 28px);
		}

		.uve-mr-form-fields {
			display: grid;
			gap: 20px;
		}

		.uve-mr-form-fields .mdes {
			margin: 0;
			color: var(--wp--preset--color--contrast-2, #555);
		}

		.uve-mr-field-label {
			display: inline-block;
			margin-bottom: 6px;
			font-weight: 600;
			color: var(--wp--preset--color--contrast, #111);
		}

		.uve-mr-required {
			color: #c31919;
			margin-left: 4px;
		}

		.uve-mr-form-fields input[type="text"],
		.uve-mr-form-fields input[type="email"],
		.uve-mr-form-fields input[type="url"],
		.uve-mr-form-fields input[type="tel"],
		.uve-mr-form-fields input[type="date"] {
			width: 100%;
			padding: 14px 16px;
			border-radius: 10px;
			border: 1px solid #7a7a7a;
			background: var(--wp--preset--color--base, #fff);
		}

		.uve-mr-form-fields input[type="text"]:focus,
		.uve-mr-form-fields input[type="email"]:focus,
			.uve-mr-form-fields input[type="url"]:focus,
		.uve-mr-form-fields input[type="tel"]:focus,
		.uve-mr-form-fields input[type="date"]:focus {
			border-color: #111;
			box-shadow: 0 0 0 2px rgba(17, 17, 17, 0.2);
			outline: none;
		}

		.uve-mr-consent-label {
			display: flex;
			gap: 10px;
			align-items: flex-start;
			font-size: 0.95em;
		}

		.uve-mr-consent-label small {
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

		.uve-mr-msg {
			margin: 0 0 10px;
			padding: 10px 12px;
			border-radius: 10px;
			font-size: 0.95em;
		}

		.uve-mr-ok {
			background: rgba(0, 128, 0, 0.08);
		}

		.uve-mr-err {
			background: rgba(200, 0, 0, 0.08);
		}

		.uve-mr-loading .msubmit input,
		.uve-mr-loading .msubmit button {
			opacity: .6;
			cursor: not-allowed;
		}
	</style>
</div>
