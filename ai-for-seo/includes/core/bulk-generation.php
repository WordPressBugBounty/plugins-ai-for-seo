<?php
// Keep extracted core modules inaccessible when WordPress has not loaded the plugin environment.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// region BULK GENERATION / SEO AUTOPILOT ======================================================= \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯.

/**
 * Check if the auto generation is enabled for a specific context
 *
 * @param string $post_type The context of the auto generation (post, page, product, attachment, keyphrase etc.).
 * @return bool True if the auto generation is enabled, false if not
 */
function ai4seo_is_bulk_generation_enabled( string $post_type ): bool {
	$enabled_bulk_generation_post_types = ai4seo_get_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ) ?: array();

	return is_array( $enabled_bulk_generation_post_types ) && in_array( $post_type, $enabled_bulk_generation_post_types );
}

/**
 * Check if any auto generation is enabled
 *
 * @return bool True if any auto generation is enabled, false if not
 */
function ai4seo_is_any_bulk_generation_enabled(): bool {
	$enabled_bulk_generations_post_types = ai4seo_get_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ) ?: array();

	return count( $enabled_bulk_generations_post_types ) > 0;
}

/**
 * Check if SEO Autopilot should automatically queue applicable entries.
 *
 * @return bool
 */
function ai4seo_should_auto_queue_bulk_generation_entries(): bool {
	return ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_AUTO_QUEUE_ENTRIES ) === true;
}

/**
 * Check whether the SEO Autopilot new/existing setting activates its date boundary.
 *
 * @param mixed $filter Stored new/existing filter.
 * @return bool True for the new and existing modes.
 */
function ai4seo_is_bulk_generation_date_filter_active( $filter ): bool {
	// Only the complementary new/existing modes require a persisted reference time.
	return in_array( $filter, array( 'new', 'existing' ), true );
}

/**
 * Resolve persisted SEO Autopilot date-filter state without side effects.
 *
 * The inactive "both" mode deliberately ignores its reference time and returns a neutral valid
 * DATETIME binding. Active filters require a positive integer timestamp whose UTC representation
 * fits MySQL's supported DATETIME range.
 *
 * @param mixed $filter Stored new/existing filter.
 * @param mixed $reference_timestamp Stored filter reference timestamp.
 * @return array Canonical state with validity, filter, reference time, SQL date, and error code.
 */
function ai4seo_get_bulk_generation_date_filter_state( $filter, $reference_timestamp ): array {
	// Canonicalize only string input because other persisted types are invalid filter state.
	$filter = is_string( $filter ) ? sanitize_key( $filter ) : '';

	// Start fail-closed so every rejected branch returns the same complete state shape.
	$filter_state = array(
		'is_valid'            => false,
		'filter'              => $filter,
		'reference_timestamp' => 0,
		'post_date_gmt'       => '',
		'error_code'          => 'invalid_filter',
	);

	// Reject unsupported filter values before considering their associated reference time.
	if ( ! in_array( $filter, AI4SEO_AVAILABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_OPTIONS, true ) ) {
		return $filter_state;
	}

	// Supply a valid neutral SQL binding when the query does not apply a date comparison.
	if ( 'both' === $filter ) {
		$filter_state['is_valid']      = true;
		$filter_state['post_date_gmt'] = AI4SEO_MYSQL_DATETIME_MINIMUM;
		$filter_state['error_code']    = '';

		return $filter_state;
	}

	// Require an exact positive integer so malformed persisted values cannot widen queue scope.
	$reference_timestamp = ( is_int( $reference_timestamp ) || is_string( $reference_timestamp ) )
		? filter_var(
			$reference_timestamp,
			FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 1,
				),
			)
		)
		: false;

	if ( false === $reference_timestamp ) {
		$filter_state['error_code'] = 'invalid_reference_timestamp';

		return $filter_state;
	}

	// Format through DateTime so platform range failures remain explicit and recoverable.
	try {
		$reference_datetime = new DateTimeImmutable( '@' . $reference_timestamp );
		$post_date_gmt      = $reference_datetime
			->setTimezone( new DateTimeZone( 'UTC' ) )
			->format( 'Y-m-d H:i:s' );
	} catch ( Throwable $throwable ) {
		$filter_state['error_code'] = 'reference_timestamp_format_failed';

		return $filter_state;
	}

	// Keep invalid or out-of-range SQL dates out of every prepared query consumer.
	if ( ! ai4seo_is_valid_mysql_datetime( $post_date_gmt ) ) {
		$filter_state['error_code'] = 'invalid_post_date_gmt';

		return $filter_state;
	}

	// Publish the canonical active state only after every timestamp and SQL-date check succeeds.
	$filter_state['is_valid']            = true;
	$filter_state['reference_timestamp'] = $reference_timestamp;
	$filter_state['post_date_gmt']       = $post_date_gmt;
	$filter_state['error_code']          = '';

	return $filter_state;
}

/**
 * Resolve the currently persisted SEO Autopilot date-filter setting and reference time.
 *
 * @return array Canonical state from ai4seo_get_bulk_generation_date_filter_state().
 */
function ai4seo_get_current_bulk_generation_date_filter_state(): array {
	// Pair the owning setting with its environmental boundary before validating either consumer.
	return ai4seo_get_bulk_generation_date_filter_state(
		ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_NEW_OR_EXISTING_FILTER ),
		ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_BULK_GENERATION_NEW_OR_EXISTING_FILTER_REFERENCE_TIME )
	);
}

/**
 * Check whether an entry timestamp is eligible under resolved SEO Autopilot date-filter state.
 *
 * @param array $filter_state Canonical state from ai4seo_get_bulk_generation_date_filter_state().
 * @param int   $entry_timestamp Entry publication/upload timestamp in UTC.
 * @return bool True when the entry is inside the configured date scope.
 */
function ai4seo_does_bulk_generation_date_filter_include_timestamp( array $filter_state, int $entry_timestamp ): bool {
	// Invalid state is always fail-closed, matching queue excavation and analysis behavior.
	if ( empty( $filter_state['is_valid'] ) ) {
		return false;
	}

	// The inactive mode includes every entry without consulting its neutral reference time.
	if ( 'both' === ( $filter_state['filter'] ?? '' ) ) {
		return true;
	}

	$reference_timestamp = (int) ( $filter_state['reference_timestamp'] ?? 0 );

	// New entries begin strictly after the shared boundary.
	if ( 'new' === ( $filter_state['filter'] ?? '' ) ) {
		return $entry_timestamp > $reference_timestamp;
	}

	// The boundary itself belongs to the complementary existing-entry scope.
	return 'existing' === ( $filter_state['filter'] ?? '' )
		&& $entry_timestamp <= $reference_timestamp;
}

/**
 * Return the shared admin-facing explanation for invalid SEO Autopilot date-filter state.
 *
 * @return string Translated recovery guidance.
 */
function ai4seo_get_invalid_bulk_generation_date_filter_message(): string {
	// Keep recovery guidance identical everywhere invalid state is surfaced to administrators.
	return __( "SEO Autopilot cannot automatically queue entries because the 'New or existing entries' filter has an invalid reference date. Open the SEO Autopilot settings and save this filter again.", 'ai-for-seo' );
}

// =========================================================================================== \\

/**
 * Returns available bulk generation queue actions.
 *
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return array Bulk generation queue actions.
 */
function ai4seo_get_bulk_generation_queue_actions( string $surface = 'all', string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): array {
	$surface              = sanitize_key( $surface );
	$active_status_filter = sanitize_key( $active_status_filter );
	$context              = sanitize_key( $context );
	$list_location        = sanitize_key( $list_location );
	$plugin_name          = ai4seo_get_plugin_name();

	// The generated-data action affects the marker only, regardless of whether the entries are posts, pages, products, or media.
	$remove_generated_data_description = sprintf(
		/* translators: %s: Plugin name. */
		__( 'Deletes the %s generated-data marker for selected entries. Saved metadata, synced third-party SEO plugin values, and saved media library fields stay unchanged, but the entries can be queued again when applicable.', 'ai-for-seo' ),
		$plugin_name
	);

	// Queue actions control pending and force-overwrite state, and share their descriptions with the SOOZ table tooltip.
	$queue_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE => array(
			'label'       => __( 'Add to queue (safe)', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Queues only selected entries that %s currently considers applicable, such as missing, failed, or manually excluded entries. Hidden, already queued, processing, or complete entries are skipped.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE => array(
			'label'       => __( 'Add to queue (force)', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Queues selected valid entries in force mode so %s regenerates data even when saved values already exist. Hidden or currently processing entries are skipped.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE => array(
			'label'       => __( 'Remove from queue', 'ai-for-seo' ),
			'description' => __( 'Removes selected entries from the pending queue and clears force mode. Saved metadata, generated-data markers, and entries already processing are not changed.', 'ai-for-seo' ),
		),
	);

	// Related-image actions start from selected metadata entries but mutate discovered media in the attachment queue.
	$related_attachment_queue_actions = array();

	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES !== $context ) {
		$related_attachment_queue_actions = array(
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE => array(
				'label'       => __( 'Add all related images to queue (soft)', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then queues only applicable related images for media attribute generation. Hidden, already queued, processing, or complete images are skipped.', 'ai-for-seo' ),
					$plugin_name
				),
			),
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE => array(
				'label'       => __( 'Add all related images to queue (force)', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then queues valid related images in force mode so media attributes can be regenerated. Hidden or currently processing images are skipped.', 'ai-for-seo' ),
					$plugin_name
				),
			),
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE => array(
				'label'       => __( 'Remove related images from queue', 'ai-for-seo' ),
				'description' => sprintf(
					/* translators: %s: Plugin name. */
					__( 'Finds images related to the selected entries using the same Related Media scan as %s, then removes pending related images from the media attribute queue and clears force mode for removed images. Processing, failed, missing, hidden, generated data, saved media fields, and related-post fallback metadata are not changed.', 'ai-for-seo' ),
					$plugin_name
				),
			),
		);
	}

	// Visibility actions are available only on SOOZ custom tables and depend on the active status filter.
	$hide_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY => array(
			'label'       => __( 'Hide entry', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Hides selected entries from %s lists and automatic queueing, and removes them from pending and missing queue states.', 'ai-for-seo' ),
				$plugin_name
			),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY => array(
			'label'       => __( 'Show entry', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Shows selected hidden entries again and refreshes their %s status so they can appear in the relevant lists.', 'ai-for-seo' ),
				$plugin_name
			),
		),
	);

	// Auto Queue actions manage manual exclusions without changing saved metadata or media fields.
	$auto_queue_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE => array(
			'label'       => __( 'Exclude from Auto Queue feature', 'ai-for-seo' ),
			'description' => __( 'Prevents the Auto Queue feature from adding selected entries automatically. Pending and missing queue states are cleared, but manual queue actions remain available.', 'ai-for-seo' ),
		),
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE => array(
			'label'       => __( 'Allow for Auto Queue feature', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Removes the manual Auto Queue exclusion so selected entries can be added automatically again when %s detects they are applicable.', 'ai-for-seo' ),
				$plugin_name
			),
		),
	);

	// Data removal actions clarify whether SOOZ generation markers or active SOOZ metadata records are affected.
	$data_removal_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA => array(
			'label'       => __( 'Mark as not generated', 'ai-for-seo' ),
			'description' => $remove_generated_data_description,
		),
	);

	if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES !== $context ) {
		$data_removal_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA ] = array(
			'label'       => __( 'Delete SOOZ metadata', 'ai-for-seo' ),
			'description' => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Deletes active %s metadata saved for selected entries from wp_postmeta. Third-party SEO plugin data and generated-data markers are not deleted.', 'ai-for-seo' ),
				$plugin_name
			),
		);
	}

	// Custom-instruction actions need a modal because the bulk dropdown itself cannot collect the instruction text.
	$custom_instructions_actions = array(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS => array(
			'label'          => __( 'Set custom instructions', 'ai-for-seo' ),
			'description'    => sprintf(
				/* translators: %s: Plugin name. */
				__( 'Opens a modal to set entry-specific custom instructions for future %s generations. Existing entry-specific custom instructions are overwritten, and submitting an empty value clears them.', 'ai-for-seo' ),
				$plugin_name
			),
			'requires_modal' => true,
		),
	);

	if ( 'related_attachments_modal' === $list_location ) {
		// The embedded Related Media table inherits attachment.php, but this action is meant for main list tables only.
		$custom_instructions_actions = array();
	}

	if ( 'native' === $surface ) {
		return array_merge( $queue_actions, $related_attachment_queue_actions, $custom_instructions_actions, $auto_queue_actions, $data_removal_actions );
	}

	if ( 'custom' === $surface ) {
		if ( 'hidden' === $active_status_filter ) {
			return array_merge(
				$queue_actions,
				$related_attachment_queue_actions,
				$custom_instructions_actions,
				$auto_queue_actions,
				$data_removal_actions,
				array(
					AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY => $hide_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY ],
				)
			);
		}

		return array_merge(
			$queue_actions,
			$related_attachment_queue_actions,
			$custom_instructions_actions,
			$auto_queue_actions,
			$data_removal_actions,
			array(
				AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY => $hide_actions[ AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY ],
			)
		);
	}

	return array_merge( $queue_actions, $related_attachment_queue_actions, $custom_instructions_actions, $auto_queue_actions, $data_removal_actions, $hide_actions );
}

// =========================================================================================== \\

/**
 * Returns a bulk generation queue action label.
 *
 * @param string $action Action identifier.
 * @param bool   $include_plugin_name Whether to prefix the plugin name.
 * @return string Bulk generation queue action label.
 */
function ai4seo_get_bulk_generation_queue_action_label( string $action, bool $include_plugin_name = false ): string {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );
	$action_label                  = $bulk_generation_queue_actions[ $action ]['label'] ?? $action;

	if ( ! $include_plugin_name ) {
		return $action_label;
	}

	// Native WordPress bulk action labels use the short brand to keep option text compact.
	return sprintf(
		/* translators: 1: Short plugin name, 2: bulk action label. */
		__( '%1$s: %2$s', 'ai-for-seo' ),
		AI4SEO_SHORT_PLUGIN_NAME,
		$action_label
	);
}

// =========================================================================================== \\

/**
 * Returns a tooltip icon that explains the available bulk generation queue actions.
 *
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return string HTML.
 */
function ai4seo_get_bulk_generation_queue_action_help_icon_html( string $surface = 'all', string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): string {
	// Keep tooltip rendering separate from select rendering while both consume the same action metadata.
	$tooltip_html = ai4seo_get_bulk_generation_queue_action_tooltip_html( $surface, $active_status_filter, $context, $list_location );

	if ( ! $tooltip_html ) {
		return '';
	}

	// Keep this specialized trigger contextual so screen readers distinguish it from generic help icons.
	return "<span class='ai4seo-bulk-generation-queue-action-help'>"
		. ai4seo_get_icon_with_tooltip_tag( $tooltip_html, '', 'circle-question', __( 'Explain bulk actions', 'ai-for-seo' ) )
	. '</span>';
}

// =========================================================================================== \\

/**
 * Returns tooltip content for the available bulk generation queue actions.
 *
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return string Tooltip HTML.
 */
function ai4seo_get_bulk_generation_queue_action_tooltip_html( string $surface = 'all', string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): string {
	$surface              = sanitize_key( $surface );
	$active_status_filter = sanitize_key( $active_status_filter );
	$context              = sanitize_key( $context );
	$list_location        = sanitize_key( $list_location );

	// The surface and filter choose the same action subset that the SOOZ custom table select renders.
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( $surface, $active_status_filter, $context, $list_location );
	$tooltip_parts                 = array();

	foreach ( $bulk_generation_queue_actions as $this_action_identifier => $this_action_details ) {
		// Actions without descriptions remain valid bulk actions but are not useful in the help tooltip.
		$this_action_description = trim( (string) ( $this_action_details['description'] ?? '' ) );

		if ( ! $this_action_description ) {
			continue;
		}

		$this_action_label = (string) ( $this_action_details['label'] ?? $this_action_identifier );

		$tooltip_parts[] = '<strong>'
			. esc_html( $this_action_label )
		. ':</strong> '
		. esc_html( $this_action_description );
	}

	return implode( '<br><br>', $tooltip_parts );
}

// =========================================================================================== \\

/**
 * Checks whether a bulk generation queue action exists.
 *
 * @param string $action Action identifier.
 * @return bool Whether the action exists.
 */
function ai4seo_is_bulk_generation_queue_action( string $action ): bool {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );

	return isset( $bulk_generation_queue_actions[ $action ] );
}

// =========================================================================================== \\

/**
 * Checks whether a bulk generation queue action is available on a specific surface.
 *
 * @param string $action Action identifier.
 * @param string $surface Active surface: all, native, or custom.
 * @param string $active_status_filter Active SOOZ list status filter.
 * @param string $context Active queue context: all, metadata, or attachment_attributes.
 * @param string $list_location Active list location, such as main or related_attachments_modal.
 * @return bool Whether the action is available.
 */
function ai4seo_is_bulk_generation_queue_action_available_for_surface( string $action, string $surface, string $active_status_filter = 'all', string $context = 'all', string $list_location = 'main' ): bool {
	$action                        = sanitize_key( $action );
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( $surface, $active_status_filter, $context, $list_location );

	return isset( $bulk_generation_queue_actions[ $action ] );
}

// =========================================================================================== \\

/**
 * Checks whether a bulk generation queue action needs a modal before it can be processed.
 *
 * @param string $action Action identifier.
 * @return bool Whether this action is modal-only.
 */
function ai4seo_is_bulk_generation_queue_action_modal_required( string $action ): bool {
	$action = sanitize_key( $action );

	// The shared action registry carries modal requirements so native and custom handlers cannot drift.
	$bulk_generation_queue_actions = ai4seo_get_bulk_generation_queue_actions( 'all' );

	return ! empty( $bulk_generation_queue_actions[ $action ]['requires_modal'] );
}

// =========================================================================================== \\

/**
 * Checks whether an action queues related attachment images from selected source posts.
 *
 * @param string $action Action identifier.
 * @return bool Whether this is a related attachment queue action.
 */
function ai4seo_is_related_attachment_bulk_generation_queue_action( string $action ): bool {
	$action = sanitize_key( $action );

	// The remove action uses the same scanner dispatch, but the processor applies different queue side effects.
	if ( ai4seo_is_related_attachment_remove_bulk_generation_queue_action( $action ) ) {
		return true;
	}

	return in_array(
		$action,
		array(
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE,
			AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE,
		),
		true
	);
}

// =========================================================================================== \\

/**
 * Checks whether a related-image bulk action removes discovered attachments from Pending.
 *
 * @param string $action Action identifier.
 * @return bool Whether this is the related attachment remove action.
 */
function ai4seo_is_related_attachment_remove_bulk_generation_queue_action( string $action ): bool {
	$action = sanitize_key( $action );

	// Keep remove-action checks centralized because the action shares scanning but has different queue side effects.
	return ( AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE === $action );
}

// =========================================================================================== \\

/**
 * Returns the queue contexts supported by all bulk-generation routing helpers.
 *
 * @return array
 */
function ai4seo_get_bulk_generation_queue_contexts(): array {
	// Keep the shared registry ordered consistently for validation, aggregation, and future context-wide operations.
	return array(
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES,
	);
}

// =========================================================================================== \\

/**
 * Checks whether a bulk generation queue context exists.
 *
 * @param string $context Queue context.
 * @return bool Whether the queue context exists.
 */
function ai4seo_is_bulk_generation_queue_context( string $context ): bool {
	$context = sanitize_key( $context );

	// Reuse the routing registry so validation cannot drift from context-wide operations.
	return in_array( $context, ai4seo_get_bulk_generation_queue_contexts(), true );
}

// =========================================================================================== \\

/**
 * Returns the entry-level custom-instruction postmeta key for a bulk generation queue context.
 *
 * @param string $context Queue context.
 * @return string Postmeta key.
 */
function ai4seo_get_custom_instructions_postmeta_key_by_bulk_generation_queue_context( string $context ): string {
	$context = sanitize_key( $context );

	// Context decides which editor-owned postmeta key the bulk action writes to.
	switch ( $context ) {
		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA:
			return AI4SEO_POST_META_METADATA_CUSTOM_INSTRUCTIONS_META_KEY;

		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES:
			return AI4SEO_POST_META_ATTACHMENT_ATTRIBUTES_CUSTOM_INSTRUCTIONS_META_KEY;
	}

	return '';
}

// =========================================================================================== \\

/**
 * Returns the queue option names for a bulk generation queue context.
 *
 * @param string $context Queue context.
 * @return array Queue option names.
 */
function ai4seo_get_bulk_generation_queue_options_by_context( string $context ): array {
	$context = sanitize_key( $context );

	switch ( $context ) {
		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA:
			return array(
				'missing'               => AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME,
				'pending'               => AI4SEO_PENDING_METADATA_POST_IDS_OPTION_NAME,
				'processing'            => AI4SEO_PROCESSING_METADATA_POST_IDS_OPTION_NAME,
				'failed'                => AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME,
				'force_overwrite'       => AI4SEO_FORCE_OVERWRITE_METADATA_POST_IDS_OPTION_NAME,
				'hidden'                => AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME,
				'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME,
			);

		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES:
			return array(
				'missing'               => AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'pending'               => AI4SEO_PENDING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'processing'            => AI4SEO_PROCESSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'failed'                => AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'force_overwrite'       => AI4SEO_FORCE_OVERWRITE_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'hidden'                => AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
				'auto_queue_disallowed' => AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME,
			);
	}

	return array();
}

// =========================================================================================== \\

/**
 * Checks whether a pending bulk generation queue entry should be processed in force-overwrite mode.
 *
 * @param int    $post_id Post ID.
 * @param string $context Queue context.
 * @return bool Whether this queued entry has a force-overwrite marker.
 */
function ai4seo_is_bulk_generation_queue_entry_force_overwrite( int $post_id, string $context ): bool {
	$post_id       = absint( $post_id );
	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if ( ! $post_id || ! $queue_options || ! isset( $queue_options['pending'], $queue_options['force_overwrite'] ) ) {
		return false;
	}

	$pending_post_ids         = ai4seo_get_post_ids_from_option( $queue_options['pending'] );
	$force_overwrite_post_ids = ai4seo_get_post_ids_from_option( $queue_options['force_overwrite'] );

	return in_array( $post_id, $pending_post_ids, true )
		&& in_array( $post_id, $force_overwrite_post_ids, true );
}

// =========================================================================================== \\

/**
 * Returns the default result shape for bulk generation queue actions.
 *
 * @param string $action Queue action.
 * @param int    $selected_entries Number of selected entries.
 * @return array Default result counts.
 */
function ai4seo_get_bulk_generation_queue_action_result_defaults( string $action, int $selected_entries ): array {
	// Keep the result shape stable for AJAX and native table handlers while new action types add their own counters.
	return array(
		'action'                         => sanitize_key( $action ),
		'selected'                       => max( 0, absint( $selected_entries ) ),
		'changed'                        => 0,
		'not_applicable'                 => 0,
		'skipped'                        => max( 0, absint( $selected_entries ) ),
		'generated_data_deleted'         => 0,
		'active_metadata_deleted'        => 0,
		'related_source_entries_scanned' => 0,
		'related_images_found'           => 0,
		'related_images_changed'         => 0,
		'related_images_skipped'         => 0,
		'related_sources_without_images' => 0,
		'related_partial_scans'          => 0,
		'custom_instructions_cleared'    => false,
		'queue_count'                    => 0,
	);
}

// =========================================================================================== \\

/**
 * Applies the bulk custom-instructions action to selected valid entries.
 *
 * @param array  $post_ids Selected post IDs.
 * @param string $context Queue context.
 * @param mixed  $custom_instructions Raw custom-instructions value.
 * @return array Result counts.
 */
function ai4seo_process_bulk_custom_instructions_action( array $post_ids, string $context, $custom_instructions ): array {
	$context          = sanitize_key( $context );
	$post_ids         = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$selected_entries = count( $post_ids );
	$result           = ai4seo_get_bulk_generation_queue_action_result_defaults(
		AI4SEO_BULK_GENERATION_QUEUE_ACTION_SET_CUSTOM_INSTRUCTIONS,
		$selected_entries
	);

	if ( ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		return $result;
	}

	// Resolve the same entry-level postmeta key used by the Metadata and Media Attributes editors.
	$meta_key = ai4seo_get_custom_instructions_postmeta_key_by_bulk_generation_queue_context( $context );

	if ( '' === $meta_key ) {
		return $result;
	}

	// Reuse the same validity rules as queue actions so unsupported/deleted rows are skipped consistently.
	$valid_post_ids = ai4seo_get_valid_bulk_generation_queue_post_ids( $post_ids, $context );

	if ( ! $valid_post_ids ) {
		return $result;
	}

	// Save one normalized value across the valid batch; empty strings clear existing entry-level instructions.
	$save_result     = ai4seo_save_custom_instructions_postmeta_for_post_ids(
		$valid_post_ids,
		$meta_key,
		$custom_instructions
	);
	$changed_entries = count( $save_result['saved_post_ids'] );

	// "Changed" means the valid entry was successfully saved or cleared, even when the stored value was unchanged.
	$result['changed']                     = $changed_entries;
	$result['skipped']                     = max( 0, $selected_entries - $changed_entries );
	$result['custom_instructions_cleared'] = ! empty( $save_result['custom_instructions_cleared'] );

	return $result;
}

// =========================================================================================== \\

/**
 * Applies a related-image bulk action from selected source posts to the attachment queue.
 *
 * @param string $action Queue action.
 * @param array  $source_post_ids Selected source post IDs.
 * @return array Result counts.
 */
function ai4seo_process_related_attachment_bulk_generation_queue_action( string $action, array $source_post_ids ): array {
	$action          = sanitize_key( $action );
	$source_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $source_post_ids ) ) ) );

	// Remove actions share the scanner but intentionally skip add-only fallback writes and cron injection.
	$is_related_attachment_remove_queue_action = ai4seo_is_related_attachment_remove_bulk_generation_queue_action( $action );

	// Related-image actions count selected source posts first; skipped is recalculated against discovered images later.
	$result            = ai4seo_get_bulk_generation_queue_action_result_defaults( $action, count( $source_post_ids ) );
	$result['skipped'] = 0;

	if ( ! ai4seo_is_related_attachment_bulk_generation_queue_action( $action ) ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	// Source entries use the metadata context; the discovered media IDs are queued in the attachment context later.
	$valid_source_post_ids                    = ai4seo_get_valid_bulk_generation_queue_post_ids(
		$source_post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA
	);
	$result['related_source_entries_scanned'] = count( $valid_source_post_ids );

	if ( ! $valid_source_post_ids ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	$related_attachment_post_id_lookup             = array();
	$related_attachment_post_ids_by_source_post_id = array();

	// Scan source posts with the existing Related Media scanner and keep first-source fallback ownership for duplicates.
	foreach ( $valid_source_post_ids as $this_source_post_id ) {
		$this_related_attachment_scan_result = ai4seo_get_related_attachment_scan_result( $this_source_post_id );
		$this_related_attachment_post_ids    = array_values(
			array_unique(
				array_filter(
					array_map(
						'absint',
						(array) ( $this_related_attachment_scan_result['attachment_post_ids'] ?? array() )
					)
				)
			)
		);

		if ( ! empty( $this_related_attachment_scan_result['is_partial'] ) ) {
			++$result['related_partial_scans'];
		}

		if ( ! $this_related_attachment_post_ids ) {
			++$result['related_sources_without_images'];
			continue;
		}

		// First selected source post wins, matching the plan while avoiding repeated fallback rewrites.
		foreach ( $this_related_attachment_post_ids as $this_related_attachment_post_id ) {
			if ( isset( $related_attachment_post_id_lookup[ $this_related_attachment_post_id ] ) ) {
				continue;
			}

			$related_attachment_post_id_lookup[ $this_related_attachment_post_id ] = $this_related_attachment_post_id;

			if ( ! isset( $related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] ) ) {
				$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] = array();
			}

			$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ][] = $this_related_attachment_post_id;
		}
	}

	$discovered_related_attachment_post_ids = array_values( $related_attachment_post_id_lookup );
	$result['related_images_found']          = count( $discovered_related_attachment_post_ids );

	// Source-post access does not grant access to media owned by another user, so authorize every discovered target.
	$related_attachment_post_ids = ai4seo_filter_editable_post_ids( $discovered_related_attachment_post_ids );
	$editable_attachment_lookup  = array_fill_keys( $related_attachment_post_ids, true );

	// Keep fallback ownership groups aligned with the authorized attachment set before any postmeta write occurs.
	foreach ( $related_attachment_post_ids_by_source_post_id as $this_source_post_id => $this_related_attachment_post_ids ) {
		$this_related_attachment_post_ids = array_values(
			array_filter(
				$this_related_attachment_post_ids,
				function ( int $attachment_post_id ) use ( $editable_attachment_lookup ): bool {
					return isset( $editable_attachment_lookup[ $attachment_post_id ] );
				}
			)
		);

		if ( $this_related_attachment_post_ids ) {
			$related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] = $this_related_attachment_post_ids;
			continue;
		}

		unset( $related_attachment_post_ids_by_source_post_id[ $this_source_post_id ] );
	}

	if ( ! $related_attachment_post_ids ) {
		$result['related_images_skipped'] = $result['related_images_found'];
		$result['skipped']                = $result['related_images_skipped'];
		$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	if ( ! $is_related_attachment_remove_queue_action ) {
		// Add actions persist fallback values in the same grouped shape used by the single-entry Related Media flow.
		foreach ( $related_attachment_post_ids_by_source_post_id as $this_source_post_id => $this_related_attachment_post_ids ) {
			if ( ai4seo_update_attachment_related_post_id_for_attachment_post_ids( $this_related_attachment_post_ids, (int) $this_source_post_id ) ) {
				continue;
			}

			ai4seo_debug_message(
				4527052603,
				'Could not update related post fallback values for related image bulk action source post ID ' . absint( $this_source_post_id ),
				true
			);
		}
	}

	// Attachment queue state is read once so large bulk actions do not repeat option reads for each source post.
	$attachment_queue_options = ai4seo_get_bulk_generation_queue_options_by_context( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES );

	if ( ! $attachment_queue_options ) {
		$result['related_images_skipped'] = $result['related_images_found'];
		$result['skipped']                = $result['related_images_skipped'];
		$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	// Validate discovered IDs as attachment queue entries before applying the previously-read queue state.
	$valid_related_attachment_post_ids = ai4seo_get_valid_bulk_generation_queue_post_ids(
		$related_attachment_post_ids,
		AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES
	);

	// Pending and Force mode are shared by all related-image queue mutations.
	$pending_attachment_post_ids         = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['pending'] ) );
	$force_overwrite_attachment_post_ids = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['force_overwrite'] ) );
	$changed_attachment_post_ids         = array();

	switch ( $action ) {
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE:
			// Soft mode needs applicability queues to match Add to queue (safe) for attachment attributes.
			$missing_attachment_post_ids               = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['missing'] ) );
			$processing_attachment_post_ids            = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['processing'] ) );
			$failed_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['failed'] ) );
			$hidden_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['hidden'] ) );
			$auto_queue_disallowed_attachment_post_ids = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['auto_queue_disallowed'] ) );
			$applicable_attachment_post_ids            = array_values(
				array_unique(
					array_merge(
						$missing_attachment_post_ids,
						$failed_attachment_post_ids,
						$auto_queue_disallowed_attachment_post_ids
					)
				)
			);
			$applicable_attachment_post_ids            = array_values( array_diff( $applicable_attachment_post_ids, $hidden_attachment_post_ids ) );
			$applicable_attachment_post_ids            = array_values( array_intersect( $valid_related_attachment_post_ids, $applicable_attachment_post_ids ) );

			// Soft mode mirrors Add to queue (safe): queue applicable media only and leave Processing entries untouched.
			$changed_attachment_post_ids = array_values(
				array_diff(
					$applicable_attachment_post_ids,
					$pending_attachment_post_ids,
					$processing_attachment_post_ids
				)
			);

			if ( $changed_attachment_post_ids ) {
				$new_failed_attachment_post_ids          = array_values( array_diff( $failed_attachment_post_ids, $changed_attachment_post_ids ) );
				$new_force_overwrite_attachment_post_ids = array_values( array_diff( $force_overwrite_attachment_post_ids, $changed_attachment_post_ids ) );
				$new_pending_attachment_post_ids         = array_values( array_unique( array_merge( $pending_attachment_post_ids, $changed_attachment_post_ids ) ) );

				if ( $new_failed_attachment_post_ids !== $failed_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['failed'], $new_failed_attachment_post_ids );
				}

				if ( $new_force_overwrite_attachment_post_ids !== $force_overwrite_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['force_overwrite'], $new_force_overwrite_attachment_post_ids );
				}

				if ( $new_pending_attachment_post_ids !== $pending_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['pending'], $new_pending_attachment_post_ids );
				}

				ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_RELATED_ATTACHMENTS_TO_QUEUE_FORCE_OVERWRITE:
			// Force mode ignores saved values but still respects active processing and hidden media.
			$processing_attachment_post_ids            = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['processing'] ) );
			$failed_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['failed'] ) );
			$hidden_attachment_post_ids                = array_values( ai4seo_get_post_ids_from_option( $attachment_queue_options['hidden'] ) );
			$candidate_attachment_post_ids             = array_values(
				array_diff(
					$valid_related_attachment_post_ids,
					$processing_attachment_post_ids,
					$hidden_attachment_post_ids
				)
			);
			$pending_attachment_post_id_lookup         = array_flip( $pending_attachment_post_ids );
			$force_overwrite_attachment_post_id_lookup = array_flip( $force_overwrite_attachment_post_ids );

			// Force mode can promote Pending media, but it must not modify Processing or Hidden media.
			$changed_attachment_post_ids = array_values(
				array_filter(
					$candidate_attachment_post_ids,
					function ( $attachment_post_id ) use ( $pending_attachment_post_id_lookup, $force_overwrite_attachment_post_id_lookup ) {
						return ! isset( $pending_attachment_post_id_lookup[ $attachment_post_id ] )
							|| ! isset( $force_overwrite_attachment_post_id_lookup[ $attachment_post_id ] );
					}
				)
			);

			if ( $changed_attachment_post_ids ) {
				$new_failed_attachment_post_ids          = array_values( array_diff( $failed_attachment_post_ids, $changed_attachment_post_ids ) );
				$new_pending_attachment_post_ids         = array_values( array_unique( array_merge( $pending_attachment_post_ids, $changed_attachment_post_ids ) ) );
				$new_force_overwrite_attachment_post_ids = array_values( array_unique( array_merge( $force_overwrite_attachment_post_ids, $changed_attachment_post_ids ) ) );

				if ( $new_failed_attachment_post_ids !== $failed_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['failed'], $new_failed_attachment_post_ids );
				}

				if ( $new_pending_attachment_post_ids !== $pending_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['pending'], $new_pending_attachment_post_ids );
				}

				if ( $new_force_overwrite_attachment_post_ids !== $force_overwrite_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['force_overwrite'], $new_force_overwrite_attachment_post_ids );
				}

				ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_RELATED_ATTACHMENTS_FROM_QUEUE:
			// Remove mode only changes Pending queue membership and clears matching Force mode markers.
			$changed_attachment_post_ids = array_values(
				array_intersect(
					$valid_related_attachment_post_ids,
					$pending_attachment_post_ids
				)
			);

			if ( $changed_attachment_post_ids ) {
				$new_pending_attachment_post_ids         = array_values( array_diff( $pending_attachment_post_ids, $changed_attachment_post_ids ) );
				$new_force_overwrite_attachment_post_ids = array_values( array_diff( $force_overwrite_attachment_post_ids, $changed_attachment_post_ids ) );

				if ( $new_pending_attachment_post_ids !== $pending_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['pending'], $new_pending_attachment_post_ids );
				}

				if ( $new_force_overwrite_attachment_post_ids !== $force_overwrite_attachment_post_ids ) {
					ai4seo_update_option( $attachment_queue_options['force_overwrite'], $new_force_overwrite_attachment_post_ids );
				}
			}
			break;
	}

	// Return both generic queue counts and related-image-specific counts for native notices and AJAX toasts.
	$result['changed']                = count( $changed_attachment_post_ids );
	$result['related_images_changed'] = $result['changed'];
	$result['related_images_skipped'] = max( 0, $result['related_images_found'] - $result['related_images_changed'] );
	$result['skipped']                = $result['related_images_skipped'];
	$result['queue_count']            = ai4seo_get_bulk_generation_queue_count();

	return $result;
}

// =========================================================================================== \\

/**
 * Applies a bulk generation queue action to selected post IDs.
 *
 * @param string $action Queue action.
 * @param array  $post_ids Selected post IDs.
 * @param string $context Queue context.
 * @return array Result counts.
 */
function ai4seo_process_bulk_generation_queue_action( string $action, array $post_ids, string $context ): array {
	$action                              = sanitize_key( $action );
	$context                             = sanitize_key( $context );
	$post_ids                            = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );
	$selected_entries                    = count( $post_ids );
	$result                              = ai4seo_get_bulk_generation_queue_action_result_defaults( $action, $selected_entries );
	$changed_post_ids                    = array();
	$not_applicable_entries              = 0;
	$generated_data_deleted_entries      = 0;
	$active_metadata_deleted_entries     = 0;
	$should_refresh_posts_table_analysis = false;

	if ( ! ai4seo_is_bulk_generation_queue_action( $action ) || ! ai4seo_is_bulk_generation_queue_context( $context ) ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	// Related-image actions originate from metadata tables but mutate attachment queue state in a dedicated processor.
	if ( ai4seo_is_related_attachment_bulk_generation_queue_action( $action ) ) {
		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA !== $context ) {
			$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
			return $result;
		}

		return ai4seo_process_related_attachment_bulk_generation_queue_action( $action, $post_ids );
	}

	$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

	if ( ! $queue_options ) {
		$result['queue_count'] = ai4seo_get_bulk_generation_queue_count();
		return $result;
	}

	$valid_post_ids                 = ai4seo_get_valid_bulk_generation_queue_post_ids( $post_ids, $context );
	$pending_post_ids               = ai4seo_get_post_ids_from_option( $queue_options['pending'] );
	$processing_post_ids            = ai4seo_get_post_ids_from_option( $queue_options['processing'] );
	$force_overwrite_post_ids       = ai4seo_get_post_ids_from_option( $queue_options['force_overwrite'] );
	$hidden_post_ids                = ai4seo_get_post_ids_from_option( $queue_options['hidden'] );
	$auto_queue_disallowed_post_ids = ai4seo_get_post_ids_from_option( $queue_options['auto_queue_disallowed'] );

	switch ( $action ) {
		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE:
			$applicable_post_ids    = ai4seo_get_applicable_bulk_generation_queue_post_ids( $valid_post_ids, $context );
			$not_applicable_entries = count( array_diff( $valid_post_ids, $applicable_post_ids ) );

			// Do not pass Processing IDs to ai4seo_add_post_ids_to_option(), because that helper removes contradictory Processing state.
			$changed_post_ids = array_values( array_diff( $applicable_post_ids, $pending_post_ids, $processing_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_remove_post_ids_from_option( $queue_options['force_overwrite'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['failed'], $changed_post_ids );
				ai4seo_add_post_ids_to_option( $queue_options['pending'], $changed_post_ids );
				ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ADD_TO_QUEUE_FORCE_OVERWRITE:
			// Force-overwrite can promote already Pending entries, but must not touch Processing entries.
			$candidate_post_ids = array_values( array_diff( $valid_post_ids, $processing_post_ids, $hidden_post_ids ) );
			$changed_post_ids   = array_values(
				array_filter(
					$candidate_post_ids,
					function ( $post_id ) use ( $pending_post_ids, $force_overwrite_post_ids ) {
						return ! in_array( $post_id, $pending_post_ids, true )
						|| ! in_array( $post_id, $force_overwrite_post_ids, true );
					}
				)
			);

			if ( $changed_post_ids ) {
				ai4seo_remove_post_ids_from_option( $queue_options['failed'], $changed_post_ids );
				ai4seo_add_post_ids_to_option( $queue_options['pending'], $changed_post_ids );
				ai4seo_add_post_ids_to_option( $queue_options['force_overwrite'], $changed_post_ids );
				ai4seo_inject_additional_cronjob_call( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_FROM_QUEUE:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $pending_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_remove_post_ids_from_option( $queue_options['pending'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['force_overwrite'], $changed_post_ids );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_HIDE_ENTRY:
			$changed_post_ids = array_values( array_diff( $valid_post_ids, $hidden_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_add_post_ids_to_option( $queue_options['hidden'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['pending'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['force_overwrite'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['missing'], $changed_post_ids );
				ai4seo_remove_post_ids_from_generation_status_summary_option( $changed_post_ids, $queue_options['missing'] );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_SHOW_ENTRY:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $hidden_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_remove_post_ids_from_option( $queue_options['hidden'], $changed_post_ids );
				$should_refresh_posts_table_analysis = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_DISALLOW_AUTO_QUEUE:
			$changed_post_ids = array_values( array_diff( $valid_post_ids, $auto_queue_disallowed_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_add_post_ids_to_option( $queue_options['auto_queue_disallowed'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['pending'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['force_overwrite'], $changed_post_ids );
				ai4seo_remove_post_ids_from_option( $queue_options['missing'], $changed_post_ids );
				ai4seo_remove_post_ids_from_generation_status_summary_option( $changed_post_ids, $queue_options['missing'] );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_ALLOW_AUTO_QUEUE:
			$changed_post_ids = array_values( array_intersect( $valid_post_ids, $auto_queue_disallowed_post_ids ) );

			if ( $changed_post_ids ) {
				ai4seo_remove_post_ids_from_option( $queue_options['auto_queue_disallowed'], $changed_post_ids );
				$should_refresh_posts_table_analysis = true;
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_GENERATED_DATA:
			$generated_data_post_ids    = ai4seo_read_post_ids_with_postmeta_key( $valid_post_ids, AI4SEO_POST_META_GENERATED_DATA_META_KEY );
			$generated_data_option_name = ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context )
				? AI4SEO_GENERATED_METADATA_POST_IDS_OPTION_NAME
				: AI4SEO_GENERATED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME;

			if ( $generated_data_post_ids && ai4seo_delete_postmeta_for_post_ids_and_meta_key( $generated_data_post_ids, AI4SEO_POST_META_GENERATED_DATA_META_KEY ) ) {
				$changed_post_ids                    = $generated_data_post_ids;
				$generated_data_deleted_entries      = count( $generated_data_post_ids );
				$should_refresh_posts_table_analysis = true;

				ai4seo_remove_post_ids_from_option( $generated_data_option_name, $generated_data_post_ids );
				ai4seo_remove_post_ids_from_generation_status_summary_option( $generated_data_post_ids, $generated_data_option_name );
			}
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_ACTION_REMOVE_SAVED_DATA:
			if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA !== $context ) {
				break;
			}

			$active_metadata_post_ids = ai4seo_read_post_ids_with_postmeta_key( $valid_post_ids, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY );

			if ( $active_metadata_post_ids && ai4seo_delete_postmeta_for_post_ids_and_meta_key( $active_metadata_post_ids, AI4SEO_POST_META_ACTIVE_METADATA_META_KEY ) ) {
				$changed_post_ids                    = $active_metadata_post_ids;
				$active_metadata_deleted_entries     = count( $active_metadata_post_ids );
				$should_refresh_posts_table_analysis = true;
			}
			break;
	}

	if ( $should_refresh_posts_table_analysis ) {
		// Refresh the generation status summary after the already-authorized bulk queue mutation.
		ai4seo_force_posts_table_analysis_refresh_after_admin_mutation();
	}

	$changed_entries = count( $changed_post_ids );

	$result['changed']                 = $changed_entries;
	$result['not_applicable']          = $not_applicable_entries;
	$result['skipped']                 = max( 0, $selected_entries - $changed_entries - $not_applicable_entries );
	$result['generated_data_deleted']  = $generated_data_deleted_entries;
	$result['active_metadata_deleted'] = $active_metadata_deleted_entries;
	$result['queue_count']             = ai4seo_get_bulk_generation_queue_count();

	return $result;
}

// =========================================================================================== \\

/**
 * Filters selected post IDs to entries that can be handled by the queue context.
 *
 * @param array  $post_ids Post IDs.
 * @param string $context Queue context.
 * @return array Valid post IDs.
 */
function ai4seo_get_valid_bulk_generation_queue_post_ids( array $post_ids, string $context ): array {
	$context        = sanitize_key( $context );
	$valid_post_ids = array();

	foreach ( $post_ids as $this_post_id ) {
		$this_post_id = absint( $this_post_id );

		if ( ! $this_post_id ) {
			continue;
		}

		$this_post = get_post( $this_post_id );

		if ( ! $this_post || is_wp_error( $this_post ) ) {
			continue;
		}

		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA === $context && ai4seo_is_post_a_valid_content_post( $this_post_id, $this_post ) ) {
			$valid_post_ids[] = $this_post_id;
			continue;
		}

		if ( AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES === $context && ai4seo_is_post_a_valid_attachment( $this_post_id, $this_post ) ) {
			$valid_post_ids[] = $this_post_id;
		}
	}

	return array_values( array_unique( $valid_post_ids ) );
}

// =========================================================================================== \\

/**
 * Filters selected queue IDs to entries that are applicable for normal queueing.
 *
 * @param array  $post_ids Post IDs.
 * @param string $context Queue context.
 * @return array Applicable post IDs.
 */
function ai4seo_get_applicable_bulk_generation_queue_post_ids( array $post_ids, string $context ): array {
	$context  = sanitize_key( $context );
	$post_ids = array_values( array_unique( array_filter( array_map( 'absint', $post_ids ) ) ) );

	switch ( $context ) {
		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_METADATA:
			$applicable_post_ids = array_merge(
				ai4seo_get_post_ids_from_option( AI4SEO_MISSING_METADATA_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_FAILED_METADATA_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_METADATA_POST_IDS_OPTION_NAME )
			);
			$hidden_post_ids     = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_METADATA_POST_IDS_OPTION_NAME );
			break;

		case AI4SEO_BULK_GENERATION_QUEUE_CONTEXT_ATTACHMENT_ATTRIBUTES:
			$applicable_post_ids = array_merge(
				ai4seo_get_post_ids_from_option( AI4SEO_MISSING_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_FAILED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME ),
				ai4seo_get_post_ids_from_option( AI4SEO_AUTO_QUEUE_DISALLOWED_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME )
			);
			$hidden_post_ids     = ai4seo_get_post_ids_from_option( AI4SEO_HIDDEN_ATTACHMENT_ATTRIBUTES_POST_IDS_OPTION_NAME );
			break;

		default:
			return array();
	}

	$applicable_post_ids = array_values( array_unique( array_filter( array_map( 'absint', $applicable_post_ids ) ) ) );
	$hidden_post_ids     = array_values( array_unique( array_filter( array_map( 'absint', $hidden_post_ids ?? array() ) ) ) );
	$applicable_post_ids = array_values( array_diff( $applicable_post_ids, $hidden_post_ids ) );

	return array_values( array_intersect( $post_ids, $applicable_post_ids ) );
}

// =========================================================================================== \\

/**
 * Counts bulk generation entries for one queue status across metadata and attachment contexts.
 *
 * @param string $status Queue status key from ai4seo_get_bulk_generation_queue_options_by_context().
 * @return int
 */
function ai4seo_get_bulk_generation_entry_count_by_status( string $status ): int {
	// Reuse the context-to-option map so queue counters stay aligned with bulk action routing.
	$entry_count = 0;

	foreach ( ai4seo_get_bulk_generation_queue_contexts() as $context ) {
		$queue_options = ai4seo_get_bulk_generation_queue_options_by_context( $context );

		// Unknown status keys do not map to a persisted queue option and therefore contribute no entries.
		if ( ! isset( $queue_options[ $status ] ) ) {
			continue;
		}

		$entry_count += count( ai4seo_get_post_ids_from_option( $queue_options[ $status ] ) );
	}

	return $entry_count;
}

// =========================================================================================== \\

/**
 * Count all entries currently queued for SEO Autopilot.
 *
 * @return int
 */
function ai4seo_get_bulk_generation_queue_count(): int {
	// The pending status is the persisted source of all work currently waiting in either queue context.
	return ai4seo_get_bulk_generation_entry_count_by_status( 'pending' );
}

// =========================================================================================== \\

/**
 * Count all SEO Autopilot entries currently being processed.
 *
 * Processing entries are no longer in the pending queue, but they still represent active
 * Autopilot work and must be considered before the dashboard reports an empty queue.
 *
 * @return int
 */
function ai4seo_get_bulk_generation_processing_count(): int {
	// Use the shared status counter so active work follows the same context mapping as pending work.
	return ai4seo_get_bulk_generation_entry_count_by_status( 'processing' );
}

// =========================================================================================== \\

/**
 * Excavates queue entries for the currently enabled SEO Autopilot post types.
 *
 * @param bool $debug
 * @return bool
 */
function ai4seo_try_excavate_bulk_generation_entries_for_enabled_post_types( bool $debug = false ): bool {
	if ( ! ai4seo_should_auto_queue_bulk_generation_entries() ) {
		return false;
	}

	$enabled_bulk_generation_post_types = ai4seo_get_setting( AI4SEO_SETTING_ENABLED_BULK_GENERATION_POST_TYPES ) ?: array();

	if ( ! is_array( $enabled_bulk_generation_post_types ) || ! $enabled_bulk_generation_post_types ) {
		return false;
	}

	$did_excavate = false;

	if ( in_array( 'attachment', $enabled_bulk_generation_post_types, true ) ) {
		$did_excavate = ai4seo_excavate_attachments_with_missing_attributes( $debug ) || $did_excavate;
	}

	foreach ( $enabled_bulk_generation_post_types as $enabled_bulk_generation_post_type ) {
		if ( 'attachment' !== $enabled_bulk_generation_post_type ) {
			$did_excavate = ai4seo_excavate_post_entries_with_missing_metadata( $debug ) || $did_excavate;
			break;
		}
	}

	return $did_excavate;
}


// endregion
// ___________________________________________________________________________________________.
