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
				<label><?php echo esc_html__( 'Leave this field empty', 'uve-mailrelay-newsletter' ); ?></label>
				<input type="text" name="uve_mr_hp" tabindex="-1" autocomplete="off" value="">
			</div>

			<p class="mconsent" style="margin: 8px 0;">
				<input value="0" type="hidden" name="subscriber[subscribed_with_acceptance]">
				<label style="display:flex; gap:8px; align-items:flex-start;">
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

			<?php if ( $site_key ) : ?>
				<div class="uve-mr-turnstile" data-sitekey="<?php echo esc_attr( $site_key ); ?>"></div>
				<noscript>
					<p><?php echo esc_html__( 'Enable JavaScript to subscribe.', 'uve-mailrelay-newsletter' ); ?></p>
				</noscript>
			<?php else : ?>
				<p class="uve-mr-msg uve-mr-err"><?php echo esc_html__( 'Turnstile is not configured (Site Key).', 'uve-mailrelay-newsletter' ); ?></p>
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
