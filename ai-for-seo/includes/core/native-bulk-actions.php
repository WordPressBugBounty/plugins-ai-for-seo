<?php
/**
 * Native WordPress bulk-action integration.
 *
 * @package AI_For_SEO
 */

// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === NATIVE BULK ACTIONS =================================================================== \\

/**
 * Registers opt-in native WordPress bulk actions for SEO Autopilot queue management.
 *
 * @return void
 */
function ai4seo_register_bulk_generation_queue_bulk_actions() {
	if ( wp_doing_ajax() ) {
		return;
	}

	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ai4seo_does_user_need_to_accept_tos_toc_and_pp() ) {
		return;
	}

	// Keep native WordPress list integrations fully opt-in.
	// Disabled sites skip post-type discovery and native hook registration.
	if ( ai4seo_get_setting( AI4SEO_SETTING_ENABLE_NATIVE_BULK_ACTIONS ) !== true ) {
		return;
	}

	// Native result notices are part of the same opt-in integration surface as the native actions.
	add_action( 'admin_notices', 'ai4seo_show_bulk_generation_queue_bulk_action_admin_notice' );

	// Add actions to every supported native content post type list.
	$supported_post_types = ai4seo_get_supported_post_types();

	foreach ( $supported_post_types as $this_supported_post_type ) {
		$this_supported_post_type = sanitize_key( $this_supported_post_type );

		if ( ! $this_supported_post_type ) {
			continue;
		}

		add_filter( "bulk_actions-edit-{$this_supported_post_type}", 'ai4seo_add_native_bulk_generation_queue_bulk_actions' );
		add_filter( "handle_bulk_actions-edit-{$this_supported_post_type}", 'ai4seo_handle_native_metadata_bulk_generation_queue_action', 10, 3 );
	}

	// Add actions to the native Media Library list when media attributes are supported.
	if ( ai4seo_get_supported_attachment_post_types() ) {
		add_filter( 'bulk_actions-upload', 'ai4seo_add_native_bulk_generation_queue_bulk_actions' );
		add_filter( 'handle_bulk_actions-upload', 'ai4seo_handle_native_attachment_attributes_bulk_generation_queue_action', 10, 3 );
	}
}

// =========================================================================================== \\
/**
 * Returns the bulk queue context for the current native WordPress list screen.
 *
 * @return string Queue context.
 */
function ai4seo_get_native_bulk_generation_queue_context_from_current_screen(): string {
	$screen                  = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	$is_media_library_screen = (
		$screen
		&& (
			( isset( $screen->id ) && 'upload' === $screen->id )
			|| ( isset( $screen->base ) && 'upload' === $screen->base )
		)
	);

	if ( $is_media_library_screen ) {
		return AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES;
	}

	return AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA;
}

// =========================================================================================== \\

/**
 * Adds SEO Autopilot queue actions to a native WordPress bulk actions list.
 *
 * @param array $bulk_actions Existing bulk actions.
 * @return array Updated bulk actions.
 */
function ai4seo_add_native_bulk_generation_queue_bulk_actions( array $bulk_actions ): array {
	$context                       = ai4seo_get_native_bulk_generation_queue_context_from_current_screen();
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'native', 'all', $context );

	foreach ( array_keys( $bulk_generation_queue_actions ) as $this_action_identifier ) {
		$bulk_actions[ $this_action_identifier ] = ai4seo_get_bulk_generation_queue_action_label( $this_action_identifier, true );
	}

	return $bulk_actions;
}

// =========================================================================================== \\

/**
 * Handles native WordPress metadata queue bulk actions.
 *
 * @param string $redirect_to Redirect URL.
 * @param string $doaction Selected bulk action.
 * @param array  $post_ids Selected post IDs.
 * @return string Redirect URL.
 */
function ai4seo_handle_native_metadata_bulk_generation_queue_action( $redirect_to, $doaction, $post_ids ): string {
	return ai4seo_handle_native_bulk_generation_queue_action(
		$redirect_to,
		$doaction,
		$post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
	);
}

// =========================================================================================== \\

/**
 * Handles native WordPress attachment attribute queue bulk actions.
 *
 * @param string $redirect_to Redirect URL.
 * @param string $doaction Selected bulk action.
 * @param array  $post_ids Selected post IDs.
 * @return string Redirect URL.
 */
function ai4seo_handle_native_attachment_attributes_bulk_generation_queue_action( $redirect_to, $doaction, $post_ids ): string {
	return ai4seo_handle_native_bulk_generation_queue_action(
		$redirect_to,
		$doaction,
		$post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
	);
}

// =========================================================================================== \\

/**
 * Handles a native WordPress queue bulk action through the shared processor.
 *
 * @param string $redirect_to Redirect URL.
 * @param string $doaction Selected bulk action.
 * @param array  $post_ids Selected post IDs.
 * @param string $context Queue context.
 * @return string Redirect URL.
 */
function ai4seo_handle_native_bulk_generation_queue_action( $redirect_to, $doaction, $post_ids, string $context ): string {
	$redirect_to                  = ai4seo_remove_bulk_generation_queue_bulk_action_query_args( (string) $redirect_to );
	$bulk_generation_queue_action = sanitize_key( $doaction );

	if ( ! ai4seo_can_manage_this_plugin() ) {
		return $redirect_to;
	}

	// Validate the action against the native table surface before either showing fallback notices or mutating queue state.
	if ( ! ai4seo_is_bulk_generation_queue_action_available_for_surface( $bulk_generation_queue_action, 'native', 'all', $context ) ) {
		return $redirect_to;
	}

	if ( ai4seo_is_bulk_generation_queue_action_modal_required( $bulk_generation_queue_action ) ) {
		// Native table submissions without JavaScript must not mutate data because modal-only actions need extra user input.
		return add_query_arg(
			array(
				'ai4seo_bulk_generation_queue_action'   => $bulk_generation_queue_action,
				'ai4seo_bulk_generation_queue_context'  => sanitize_key( $context ),
				'ai4seo_bulk_generation_queue_selected' => count( (array) $post_ids ),
				'ai4seo_bulk_generation_queue_changed'  => 0,
				'ai4seo_bulk_generation_queue_not_applicable' => 0,
				'ai4seo_bulk_generation_queue_skipped'  => count( (array) $post_ids ),
				'ai4seo_bulk_generation_queue_modal_required' => 1,
			),
			$redirect_to
		);
	}

	$result = ai4seo_process_bulk_generation_queue_action(
		$bulk_generation_queue_action,
		(array) $post_ids,
		$context
	);

	return add_query_arg(
		array(
			'ai4seo_bulk_generation_queue_action'         => $bulk_generation_queue_action,
			'ai4seo_bulk_generation_queue_context'        => sanitize_key( $context ),
			'ai4seo_bulk_generation_queue_selected'       => (int) ( $result['selected'] ?? 0 ),
			'ai4seo_bulk_generation_queue_changed'        => (int) ( $result['changed'] ?? 0 ),
			'ai4seo_bulk_generation_queue_not_applicable' => (int) ( $result['not_applicable'] ?? 0 ),
			'ai4seo_bulk_generation_queue_skipped'        => (int) ( $result['skipped'] ?? 0 ),
			'ai4seo_bulk_generation_queue_generated_data_deleted' => (int) ( $result['generated_data_deleted'] ?? 0 ),
			'ai4seo_bulk_generation_queue_active_metadata_deleted' => (int) ( $result['active_metadata_deleted'] ?? 0 ),

			// Related-image notices need scanner counts because selected source posts differ from affected attachments.
			'ai4seo_bulk_generation_queue_related_source_entries_scanned' => (int) ( $result['related_source_entries_scanned'] ?? 0 ),
			'ai4seo_bulk_generation_queue_related_images_found' => (int) ( $result['related_images_found'] ?? 0 ),
			'ai4seo_bulk_generation_queue_related_images_changed' => (int) ( $result['related_images_changed'] ?? 0 ),
			'ai4seo_bulk_generation_queue_related_images_skipped' => (int) ( $result['related_images_skipped'] ?? 0 ),
			'ai4seo_bulk_generation_queue_related_sources_without_images' => (int) ( $result['related_sources_without_images'] ?? 0 ),
			'ai4seo_bulk_generation_queue_related_partial_scans' => (int) ( $result['related_partial_scans'] ?? 0 ),
		),
		$redirect_to
	);
}

// =========================================================================================== \\

/**
 * Removes old bulk queue result arguments from a redirect URL.
 *
 * @param string $url URL.
 * @return string URL without bulk queue result arguments.
 */
function ai4seo_remove_bulk_generation_queue_bulk_action_query_args( string $url ): string {
	return remove_query_arg(
		array(
			'ai4seo_bulk_generation_queue_action',
			'ai4seo_bulk_generation_queue_context',
			'ai4seo_bulk_generation_queue_selected',
			'ai4seo_bulk_generation_queue_changed',
			'ai4seo_bulk_generation_queue_not_applicable',
			'ai4seo_bulk_generation_queue_skipped',
			'ai4seo_bulk_generation_queue_generated_data_deleted',
			'ai4seo_bulk_generation_queue_active_metadata_deleted',
			'ai4seo_bulk_generation_queue_modal_required',

			// Remove related-image result arguments before adding fresh native table notice state.
			'ai4seo_bulk_generation_queue_related_source_entries_scanned',
			'ai4seo_bulk_generation_queue_related_images_found',
			'ai4seo_bulk_generation_queue_related_images_changed',
			'ai4seo_bulk_generation_queue_related_images_skipped',
			'ai4seo_bulk_generation_queue_related_sources_without_images',
			'ai4seo_bulk_generation_queue_related_partial_scans',
		),
		$url
	);
}

// =========================================================================================== \\

/**
 * Shows the result notice after a native WordPress queue bulk action.
 *
 * @return void
 */
function ai4seo_show_bulk_generation_queue_bulk_action_admin_notice() {
	if ( ! ai4seo_can_manage_this_plugin() ) {
		return;
	}

	if ( ! isset( $_GET['ai4seo_bulk_generation_queue_action'] ) ) {
		return;
	}

	$bulk_generation_queue_action = sanitize_key( wp_unslash( $_GET['ai4seo_bulk_generation_queue_action'] ) );

	if ( ! ai4seo_is_bulk_generation_queue_action( $bulk_generation_queue_action ) ) {
		return;
	}

	$bulk_generation_queue_action_label = ai4seo_get_bulk_generation_queue_action_label( $bulk_generation_queue_action, true );
	$selected_entries                   = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_selected'] ?? 0 ) );
	$changed_entries                    = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_changed'] ?? 0 ) );
	$not_applicable_entries             = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_not_applicable'] ?? 0 ) );
	$skipped_entries                    = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_skipped'] ?? 0 ) );
	$generated_data_deleted_entries     = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_generated_data_deleted'] ?? 0 ) );
	$active_metadata_deleted_entries    = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_active_metadata_deleted'] ?? 0 ) );
	// Modal-only native fallbacks are warning notices because no selected entry has been changed server-side.
	$is_modal_required_fallback = ! empty( $_GET['ai4seo_bulk_generation_queue_modal_required'] );

	// Related-image bulk notices read source-scan and discovered-image counts from native redirect arguments.
	$related_source_entries_scanned = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_source_entries_scanned'] ?? 0 ) );
	$related_images_found           = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_images_found'] ?? 0 ) );
	$related_images_changed         = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_images_changed'] ?? 0 ) );
	$related_images_skipped         = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_images_skipped'] ?? 0 ) );
	$related_sources_without_images = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_sources_without_images'] ?? 0 ) );
	$related_partial_scans          = absint( wp_unslash( $_GET['ai4seo_bulk_generation_queue_related_partial_scans'] ?? 0 ) );

	// Notice severity follows the actually affected target rows, which are attachments for related-image actions.
	$is_remove_generated_data_action           = ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA === $bulk_generation_queue_action );
	$is_remove_saved_data_action               = ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA === $bulk_generation_queue_action );
	$is_related_attachment_queue_action        = ai4seo_is_related_attachment_bulk_generation_queue_action( $bulk_generation_queue_action );
	$is_related_attachment_remove_queue_action = ai4seo_is_related_attachment_remove_bulk_generation_queue_action( $bulk_generation_queue_action );
	$is_data_removal_action                    = ( $is_remove_generated_data_action || $is_remove_saved_data_action );
	$affected_entries                          = $is_data_removal_action
		? $generated_data_deleted_entries + $active_metadata_deleted_entries
		: ( $is_related_attachment_queue_action ? $related_images_changed : $changed_entries );
	$notice_class                              = ( ! $is_modal_required_fallback && $affected_entries > 0 ) ? 'notice-success' : 'notice-warning';

	$selected_entries_label = sprintf(
		/* translators: %s: Number of selected entries. */
		_n( '%s entry selected.', '%s entries selected.', $selected_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $selected_entries )
	);

	$changed_entries_label = sprintf(
		/* translators: %s: Number of entries changed by the bulk action. */
		_n( '%s entry changed.', '%s entries changed.', $changed_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $changed_entries )
	);

	$not_applicable_entries_label = sprintf(
		/* translators: %s: Number of entries not applicable for the bulk action. */
		_n( '%s entry not applicable.', '%s entries not applicable.', $not_applicable_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $not_applicable_entries )
	);

	$skipped_entries_label = sprintf(
		/* translators: %s: Number of entries skipped by the bulk action. */
		_n( '%s entry skipped.', '%s entries skipped.', $skipped_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $skipped_entries )
	);

	$generated_data_deleted_entries_label = sprintf(
		/* translators: %s: Number of generated data entries deleted from wp_postmeta. */
		_n( '%s generated data entry deleted from wp_postmeta.', '%s generated data entries deleted from wp_postmeta.', $generated_data_deleted_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $generated_data_deleted_entries )
	);

	$active_metadata_deleted_entries_label = sprintf(
		/* translators: %s: Number of active meta entries deleted from wp_postmeta. */
		_n( '%s active meta entry deleted from wp_postmeta.', '%s active meta entries deleted from wp_postmeta.', $active_metadata_deleted_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $active_metadata_deleted_entries )
	);

	// Related-image notices distinguish selected source entries from images discovered by the scanner.
	$related_source_entries_selected_label = sprintf(
		/* translators: %s: Number of selected source entries. */
		_n( '%s source entry selected.', '%s source entries selected.', $selected_entries, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $selected_entries )
	);

	$related_source_entries_scanned_label = sprintf(
		/* translators: %s: Number of source entries scanned for related images. */
		_n( '%s source entry scanned.', '%s source entries scanned.', $related_source_entries_scanned, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $related_source_entries_scanned )
	);

	$related_images_found_label = sprintf(
		/* translators: %s: Number of related images found. */
		_n( '%s related image found.', '%s related images found.', $related_images_found, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $related_images_found )
	);

	// Related-image remove actions mutate pending queue membership instead of adding or promoting images.
	$related_images_changed_label = $is_related_attachment_remove_queue_action
		? sprintf(
			/* translators: %s: Number of related images removed from the queue. */
			_n( '%s related image removed from queue.', '%s related images removed from queue.', $related_images_changed, 'ai-for-seo' ),
			ai4seo_format_number_i18n( $related_images_changed )
		)
		: sprintf(
			/* translators: %s: Number of related images queued or promoted. */
			_n( '%s related image queued or promoted.', '%s related images queued or promoted.', $related_images_changed, 'ai-for-seo' ),
			ai4seo_format_number_i18n( $related_images_changed )
		);

	$related_images_skipped_label = sprintf(
		/* translators: %s: Number of related images skipped. */
		_n( '%s related image skipped.', '%s related images skipped.', $related_images_skipped, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $related_images_skipped )
	);

	$related_sources_without_images_label = sprintf(
		/* translators: %s: Number of source entries with no related images. */
		_n( '%s source entry had no related images.', '%s source entries had no related images.', $related_sources_without_images, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $related_sources_without_images )
	);

	$related_partial_scans_label = sprintf(
		/* translators: %s: Number of source entries with a partial related-media scan. */
		_n( '%s source entry had a partial related-media scan.', '%s source entries had partial related-media scans.', $related_partial_scans, 'ai-for-seo' ),
		ai4seo_format_number_i18n( $related_partial_scans )
	);

	echo "<div class='notice " . esc_attr( $notice_class ) . " is-dismissible'><p>";
		echo '<strong>' . esc_html( $bulk_generation_queue_action_label ) . '</strong> ';
	if ( $is_modal_required_fallback ) {
		echo esc_html( $selected_entries_label ) . ' ';
		echo esc_html(
			sprintf(
				/* translators: %s: Plugin name. */
				__( 'This bulk action needs the %s modal so custom instructions can be entered before anything is saved. Please enable JavaScript and try again.', 'ai-for-seo' ),
				AI4SEO_PLUGIN_NAME
			)
		);
		echo '</p></div>';
		return;
	}

		echo esc_html( $is_related_attachment_queue_action ? $related_source_entries_selected_label : $selected_entries_label ) . ' ';
	if ( $is_data_removal_action ) {
		if ( $is_remove_generated_data_action ) {
			echo esc_html( $generated_data_deleted_entries_label ) . ' ';
		}

		if ( $is_remove_saved_data_action ) {
			echo esc_html( $active_metadata_deleted_entries_label ) . ' ';
		}

		if ( $skipped_entries > 0 ) {
			echo esc_html( $skipped_entries_label );
		}
	} elseif ( $is_related_attachment_queue_action ) {
		// Related-image action notices include scanner scope, queue mutations, and bounded-scan caveats.
		echo esc_html( $related_source_entries_scanned_label ) . ' ';
		echo esc_html( $related_images_found_label ) . ' ';
		echo esc_html( $related_images_changed_label ) . ' ';
		echo esc_html( $related_images_skipped_label ) . ' ';

		if ( $related_sources_without_images > 0 ) {
			echo esc_html( $related_sources_without_images_label ) . ' ';
		}

		if ( $related_partial_scans > 0 ) {
			echo esc_html( $related_partial_scans_label );
		}
	} else {
		echo esc_html( $changed_entries_label ) . ' ';
		if ( $not_applicable_entries > 0 ) {
			echo esc_html( $not_applicable_entries_label ) . ' ';
		}
		echo esc_html( $skipped_entries_label );
	}
	echo '</p></div>';
}

// =========================================================================================== \\
