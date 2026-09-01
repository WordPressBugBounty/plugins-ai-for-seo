<?php
/**
 * AJAX handler for showing the import settings preview.
 *
 * @package AI_For_SEO
 * @since 2.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Check user permissions.
if ( ! ai4seo_can_administer_plugin() ) {
	return;
}


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// requires the import settings process file in preview mode.
$ai4seo_import_mode = 'preview';
require_once ai4seo_get_includes_ajax_process_path( 'import-settings.php' );
