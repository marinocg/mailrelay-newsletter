<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Borra opciones
delete_option('uve_mr_newsletter_options');

// Nota: NO borramos la tabla de logs por defecto.
// Si quieres borrarla, hazlo manualmente o añade un flag explícito de "delete data on uninstall".
