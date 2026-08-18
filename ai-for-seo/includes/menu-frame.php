<?php
/**
 * Renders the content of the submenu page for the AI for SEO overview page.
 *
 * @since 1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_manage_this_plugin() ) {
	return;
}


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

$ai4seo_dashboard_url     = ai4seo_get_subpage_url( 'dashboard' );
$ai4seo_is_dashboard_open = ai4seo_is_plugin_page_active( 'dashboard' );
$ai4seo_settings_page_url = ai4seo_get_subpage_url( 'settings' );
$ai4seo_media_page_url    = ai4seo_get_subpage_url( 'media' );
$ai4seo_account_page_url  = ai4seo_get_subpage_url( 'account' );
$ai4seo_help_page_url     = ai4seo_get_subpage_url( 'help' );

$ai4seo_active_plugin_page = ai4seo_get_active_subpage();
$ai4seo_current_post_type  = ai4seo_get_active_post_type_subpage();
$ai4seo_menu_registry      = ai4seo_get_plugins_menu_registry();

$ai4seo_page_heading_label = '';

if ( 'post' === $ai4seo_active_plugin_page ) {
	$ai4seo_page_heading_label = $ai4seo_menu_registry['post_types'][ $ai4seo_current_post_type ]['label'] ?? '';
} else {
	$ai4seo_page_heading_label = $ai4seo_menu_registry[ $ai4seo_active_plugin_page ]['label'] ?? '';
}

$ai4seo_page_heading = AI4SEO_PLUGIN_NAME . ( '' !== $ai4seo_page_heading_label ? ': ' . $ai4seo_page_heading_label : '' );

$ai4seo_active_attachment_attributes = ai4seo_get_active_attachment_attributes();
$ai4seo_supported_post_types         = array_keys( $ai4seo_menu_registry['post_types'] ?? array() );

if ( $ai4seo_is_dashboard_open ) {
	$ai4seo_unread_notifications_count = 0;
} else {
	$ai4seo_unread_notifications_count = ai4seo_get_num_unread_notification();
}

// maybe perform a performance analysis, do it here as we rely on a fully loaded WP environment.
if ( $ai4seo_is_dashboard_open ) {
	ai4seo_check_for_performance_analysis();
}

// ___________________________________________________________________________________________ \\
// === OUTPUT: JUST PURCHASED MODAL ========================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// workaround: amp; is added to the url when the user is redirected from stripe.
if ( isset( $_GET['ai4seo-just-purchased'] ) || isset( $_GET['amp;ai4seo-just-purchased'] ) ) {
	ai4seo_update_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_JUST_PURCHASED_SOMETHING_TIME, time() );

	// --- JAVASCRIPT --------------------------------------------------------- \\
	?>
	<script type="text/javascript">
		jQuery(function() {
			// open modal
			ai4seo_open_generic_success_notification_modal(
				"<?php echo esc_js( esc_html__( 'Your Credits will appear on your dashboard shortly.', 'ai-for-seo' ) ); ?>"
				+ "<br><br><?php echo esc_js( esc_html__( 'Please wait a moment and then click the button below to refresh your Credits balance.', 'ai-for-seo' ) ); ?>",
				"<button type='button' class='ai4seo-button ai4seo-inactive-button ai4seo-inactive-countdown-button' data-time-left='5' onclick='ai4seo_refresh_robhub_account(this, { check_for_purchase: true }); return false;'>"
				+ "<?php echo esc_js( esc_html__( 'Refresh Credits Balance', 'ai-for-seo' ) ); ?>"
				+ "</button>",
				{
					headline: "<?php echo esc_js( esc_html__( 'Thank you for your purchase!', 'ai-for-seo' ) ); ?>",
					close_on_outside_click: false,
					add_close_button: false,
				}
			);
		});
	</script>
	<?php
	// ------------------------------------------------------------------------ \\
}


// ___________________________________________________________________________________________ \\
// === OUTPUT: TOP BAR / NAVIGATION (MOBILE) ================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

echo "<div class='ai4seo-mobile-top-bar'>";
	// toggle button.
	$ai4seo_toggle_sidebar_label = __( 'Toggle navigation', 'ai-for-seo' );

	echo "<button type='button'"
		. " class='ai4seo-mobile-top-bar-toggle-button'"
		. " aria-controls='ai4seo-sidebar'"
		. " aria-expanded='false'"
		. " aria-label='" . esc_attr( $ai4seo_toggle_sidebar_label ) . "'"
		. " title='" . esc_attr( $ai4seo_toggle_sidebar_label ) . "'"
		. " onclick='ai4seo_toggle_sidebar();'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'bars-sort' ) );
	echo '</button>';

	// Main logo.
	echo "<div class='ai4seo-top-bar-headline'>";
		echo "<a href='" . esc_url( $ai4seo_dashboard_url ) . "' >";
			ai4seo_echo_wp_kses( ai4seo_get_sooz_logo_image_tag() );
		echo '</a>';

if ( ai4seo_robhub_api()->are_we_using_local_api() ) {
	echo "<div class='ai4seo-local-mode-hint ai4seo-blink-animation'>[LOCAL MODE]</div>";
}
	echo '</div>';
echo '</div>';

echo "<div class='wrap ai4seo-wrap'>";


	// ___________________________________________________________________________________________ \\
	// === OUTPUT: SIDE BAR / NAVIGATION (DESKTOP) ======================================================= \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	echo "<div id='ai4seo-sidebar' class='ai4seo-sidebar'>";

		// Main logo.
		echo "<div class='ai4seo-sidebar-headline'>";
			echo "<a href='" . esc_url( $ai4seo_dashboard_url ) . "'>";
				ai4seo_echo_wp_kses( ai4seo_get_sooz_logo_image_tag( 'sooz-with-ai-for-seo' ) );
			echo '</a>';

if ( ai4seo_robhub_api()->are_we_using_local_api() ) {
	echo "<div class='ai4seo-local-mode-hint ai4seo-blink-animation'>[LOCAL MODE]</div>";
}
		echo '</div>';

		echo "<nav class='nav-tab-wrapper ai4seo-menu-items-wrapper'>";
			// Dashboard page.
			echo "<a href='" . esc_url( $ai4seo_dashboard_url ) . "'"
				. " class='nav-tab ai4seo-menu-item"
				. ( $ai4seo_is_dashboard_open ? ' nav-tab-active ai4seo-active-menu-item' : '' )
				. "'"
				. ( $ai4seo_is_dashboard_open ? " aria-current='page'" : '' )
				. '>';
				ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'dashboard', 'ai4seo-menu-item-icon', true ) );
				echo '<span>';
					echo esc_html__( 'Dashboard', 'ai-for-seo' );

					// unread notifications count.
if ( $ai4seo_unread_notifications_count > 0 ) {
	echo "<span class='ai4seo-menu-counter'>" . esc_html( ai4seo_format_number_i18n( $ai4seo_unread_notifications_count ) ) . '</span>';
}

				echo '</span>';
			echo '</a>';

			// Pages for supported post-types.
foreach ( $ai4seo_supported_post_types as $ai4seo_this_post_type ) {
	$ai4seo_this_menu_item_label = ai4seo_get_post_type_translation( $ai4seo_this_post_type, true );
	$ai4seo_this_menu_item_label = ai4seo_get_nice_label( $ai4seo_this_menu_item_label );
	$ai4seo_this_menu_item_icon  = ai4seo_get_dashicon_tag_for_navigation( $ai4seo_this_post_type );
	$ai4seo_this_page_is_active  = ( $ai4seo_current_post_type === $ai4seo_this_post_type );
	$ai4seo_this_page_url        = ai4seo_get_post_type_page_url( $ai4seo_this_post_type );

	echo "<a href='" . esc_url( $ai4seo_this_page_url ) . "'"
		. " class='nav-tab ai4seo-menu-item"
		. ( $ai4seo_this_page_is_active ? ' nav-tab-active ai4seo-active-menu-item' : '' )
		. "'"
		. ( $ai4seo_this_page_is_active ? " aria-current='page'" : '' )
		. '>';
		ai4seo_echo_wp_kses( $ai4seo_this_menu_item_icon );
		echo '<div>';
			echo esc_html( $ai4seo_this_menu_item_label );
		echo '</div>';
	echo '</a>';
}

if ( $ai4seo_active_attachment_attributes ) {
	// Media page.
	echo "<a href='" . esc_url( $ai4seo_media_page_url ) . "'"
		. " class='nav-tab ai4seo-menu-item"
		. ( 'media' === $ai4seo_active_plugin_page ? ' nav-tab-active ai4seo-active-menu-item' : '' )
		. "'"
		. ( 'media' === $ai4seo_active_plugin_page ? " aria-current='page'" : '' )
		. '>';
		ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag_for_navigation( 'attachment' ) );
		echo '<span>';
			echo esc_html( ai4seo_get_media_menu_label() );
		echo '</span>';
	echo '</a>';
}

			// Account page.
			echo "<a href='" . esc_url( $ai4seo_account_page_url ) . "'"
				. " class='nav-tab ai4seo-menu-item"
				. ( 'account' === $ai4seo_active_plugin_page ? ' nav-tab-active ai4seo-active-menu-item' : '' )
				. "'"
				. ( 'account' === $ai4seo_active_plugin_page ? " aria-current='page'" : '' )
				. '>';
				ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'key', '', 'ai4seo-menu-item-icon', true ) );
				echo '<span>';
					echo esc_html__( 'Account', 'ai-for-seo' );
				echo '</span>';
			echo '</a>';

			// Settings page.
			echo "<a href='" . esc_url( $ai4seo_settings_page_url ) . "'"
				. " class='nav-tab ai4seo-menu-item"
				. ( 'settings' === $ai4seo_active_plugin_page ? ' nav-tab-active ai4seo-active-menu-item' : '' )
				. "'"
				. ( 'settings' === $ai4seo_active_plugin_page ? " aria-current='page'" : '' )
				. '>';
				ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'admin-generic', 'ai4seo-menu-item-icon', true ) );
				echo '<span>';
					echo esc_html__( 'Settings', 'ai-for-seo' );
				echo '</span>';
			echo '</a>';

			// Help page.
			echo "<a href='" . esc_url( $ai4seo_help_page_url ) . "'"
				. " class='nav-tab ai4seo-menu-item"
				. ( 'help' === $ai4seo_active_plugin_page ? ' nav-tab-active ai4seo-active-menu-item' : '' )
				. "'"
				. ( 'help' === $ai4seo_active_plugin_page ? " aria-current='page'" : '' )
				. '>';
				ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'editor-help', 'ai4seo-menu-item-icon', true ) );
				echo '<span>';
					echo esc_html__( 'Help', 'ai-for-seo' );
				echo '</span>';
			echo '</a>';

		echo '</nav>';

		// STATUS BOX.
		/*
		echo "<div class='ai4seo-status-box'>";
			echo "<h5>" . esc_html__("Credits", "ai-for-seo") . "</h5>";
			echo "<div class='ai4seo-status-box-credits'>";
				echo esc_html(123);
			echo "</div>";

			echo "<h5>" . esc_html__("Bulk Generation", "ai-for-seo") . "</h5>";
			echo "<div class='ai4seo-status-box-bulk-generation'>";
				echo "Working hard...";
			echo "</div>";
		echo "</div>";*/

		echo "<div class='ai4seo-sidebar-version-number'>" . esc_html( AI4SEO_PLUGIN_VERSION_NUMBER ) . '</div>';

	echo '</div>';


	// ___________________________________________________________________________________________ \\
	// === OUTPUT: CONTENT AREA ================================================================== \\
	// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

	echo "<div class='ai4seo-content-wrapper'>";

		// Keep one accessible page-specific heading as WordPress' notice-placement anchor.
		echo "<h1 class='screen-reader-text'>" . esc_html( $ai4seo_page_heading ) . '</h1>';

		// === CHECK FOR NEW TOS ===================================================================== \\

		// set parameter to false, so we definitely don't output anything, if tos was not accepted.
if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
	// show message to accept tos and offer a reload button.
	echo '<center>';
		echo "<div class='ai4seo-tos-notice'>";
			echo '<p>' . esc_html__( 'Please accept our Terms of Service to proceed with using this plugin.', 'ai-for-seo' ) . '</p>';
			ai4seo_echo_wp_kses( ai4seo_get_a_tag_icon_button_tag( ai4seo_get_subpage_url(), '', '', '', esc_html__( 'Show terms of service', 'ai-for-seo' ), 'ai4seo-primary-button' ) );
		echo '</div>';
	echo '</div>';
	return;
}

		// === CONTENT PAGES =========================================================================== \\

switch ( $ai4seo_active_plugin_page ) {
	case '':
	case 'dashboard':
		require_once ai4seo_get_includes_pages_path( 'dashboard.php' );
		break;
	case 'settings':
		require_once ai4seo_get_includes_pages_path( 'settings.php' );
		break;
	case 'post':
		require_once ai4seo_get_includes_pages_content_types_path( 'post.php' );
		break;
	case 'media':
		require_once ai4seo_get_includes_pages_content_types_path( 'attachment.php' );
		break;
	case 'help':
		require_once ai4seo_get_includes_pages_path( 'help.php' );
		break;
	case 'account':
		require_once ai4seo_get_includes_pages_path( 'account.php' );
		break;
	default:
		echo 'Unknown SOOZ - AI for SEO page. Please contact the plugin developer. #2406232005';
}

		// gap.
		echo '<div>&nbsp;</div>';

	echo '</div>';
echo '</div>';


// ___________________________________________________________________________________________ \\
// === OUTPUT: MODAL SCHEMAS ================================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// if wp_footer is not called, we need to include the modal schemas file here.
ai4seo_include_modal_schemas_file();
