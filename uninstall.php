<?php
/**
 * Plugin uninstall cleanup.
 *
 * @package UVE_Mailrelay_Newsletter
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Borra opciones.
delete_option( 'uve_mr_newsletter_options' );

// Nota: no borramos la tabla de logs por defecto.
// Si quieres borrarla, hazlo manualmente o anade un flag explicito de "delete data on uninstall".
