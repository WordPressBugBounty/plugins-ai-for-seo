<?php
/**
 * Renders the content of the submenu page for the AI for SEO help page.
 *
 * @since 1.2.1
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! ai4seo_can_use_plugin_content() ) {
	return;
}

$ai4seo_can_administer_plugin = ai4seo_can_administer_plugin();

// Keep completion state available across the page include scope and helper functions because the menu frame includes this file inside its own scope.
$GLOBALS['ai4seo_debug_operation_completion_page'] = array();
$ai4seo_debug_operation_completion_page            = array();

/**
 * Returns the debug operations available under Help > Troubleshooting.
 *
 * @return array<string, array<string, string>>
 */
function ai4seo_get_debug_operations(): array {
	return array(
		'generate_cronjob'                     => array(
			'label' => __( 'Run SEO Autopilot cron job now', 'ai-for-seo' ),
		),
		'analyze_cronjob'                      => array(
			'label' => __( 'Run plugin performance analysis', 'ai-for-seo' ),
		),
		'force_tidyup'                         => array(
			'label' => __( 'Run tidy-up', 'ai-for-seo' ),
		),
		'debug_condensed_post_content'         => array(
			'label' => __( 'Debug condensed post content', 'ai-for-seo' ),
		),
		'debug_combined_post_content'          => array(
			'label' => __( 'Debug combined post content', 'ai-for-seo' ),
		),
		'debug_posts_table_analysis'           => array(
			'label' => __( 'Force posts table analysis refresh', 'ai-for-seo' ),
		),
		'read_generation_status_summary'       => array(
			'label' => __( 'Read generation status summary', 'ai-for-seo' ),
		),
		'debug_active_metadata_migration_v235' => array(
			'label' => __( 'Run active metadata migration v235 once', 'ai-for-seo' ),
		),
		'prohibit-ai-for-seo'                  => array(
			'label' => __( 'Open URL with plugin prohibited', 'ai-for-seo' ),
		),
	);
}

/**
 * Returns the transient name for the current user's debug operation result.
 *
 * @return string Transient name.
 */
function ai4seo_get_debug_operation_result_transient_name(): string {
	return 'ai4seo_debug_operation_result_' . (int) get_current_user_id();
}

/**
 * Returns the canonical Troubleshooting URL for debug operation redirects.
 *
 * @return string Troubleshooting URL.
 */
function ai4seo_get_debug_operation_redirect_url(): string {
	return ai4seo_get_subpage_url( 'help', array( 'ai4seo_help_section' => 'troubleshooting' ) ) . '#ai4seo-troubleshooting-section';
}

/**
 * Renders the local completion view after a debug operation finishes.
 *
 * @param array<string, mixed> $completion_page Completion page state.
 * @return void
 */
function ai4seo_render_debug_operation_completion_page( array $completion_page ): void {
	// Keep the completion view self-contained because the full Help page is rendered hidden behind it.
	$back_url          = ai4seo_get_debug_operation_redirect_url();
	$result            = isset( $completion_page['result'] ) && is_array( $completion_page['result'] ) ? $completion_page['result'] : array();
	$continue_url      = ! empty( $completion_page['continue_url'] ) && is_string( $completion_page['continue_url'] ) ? $completion_page['continue_url'] : '';
	$auto_redirect_url = ! empty( $completion_page['auto_redirect_url'] ) && is_string( $completion_page['auto_redirect_url'] ) ? $completion_page['auto_redirect_url'] : '';
	$debug_output      = ! empty( $completion_page['debug_output'] ) && is_string( $completion_page['debug_output'] ) ? $completion_page['debug_output'] : '';
	$result_message    = ! empty( $result['message'] ) && is_scalar( $result['message'] ) ? sanitize_text_field( (string) $result['message'] ) : '';

	echo "<div id='ai4seo-debug-operation-completion-page' class='ai4seo-debug-operation-completion-page' data-ai4seo-auto-redirect-url='" . esc_url( $auto_redirect_url ) . "'";
	echo '>';

	if ( '' !== $debug_output ) {
		echo "<div class='ai4seo-debug-operation-output'>";
			ai4seo_echo_wp_kses( $debug_output );
		echo '</div>';
	}

	echo '<p><strong>' . esc_html__( 'Finished', 'ai-for-seo' ) . '</strong></p>';

	if ( '' !== $result_message ) {
		echo '<p>' . esc_html( $result_message ) . '</p>';
	}

	if ( '' !== $continue_url ) {
		echo '<p>';
			echo "<a class='ai4seo-button ai4seo-primary-button' href='" . esc_url( $continue_url ) . "'>" . esc_html__( 'Continue', 'ai-for-seo' ) . '</a>';
		echo '</p>';
	}

	echo '<p>';
		echo "<button type='button' class='ai4seo-button ai4seo-primary-button ai4seo-debug-operation-go-back-button' data-ai4seo-target='ai4seo-debug-operation-full-help-page' data-ai4seo-completion='ai4seo-debug-operation-completion-page' data-ai4seo-url='" . esc_url( $back_url ) . "'>";
			echo esc_html__( 'Go back', 'ai-for-seo' );
		echo '</button>';
	echo '</p>';

	echo '</div>';
}

/**
 * Stores completion state for rendering after the full Help page has been prepared.
 *
 * @param array<string, mixed> $result       Debug operation result.
 * @param string               $continue_url Optional continuation URL for operations that normally redirect elsewhere.
 * @param string               $debug_output Captured direct debug output.
 * @param string               $auto_redirect_url Optional URL for completion pages that should leave the fallback automatically.
 * @return void
 */
function ai4seo_prepare_debug_operation_completion_page( array $result, string $continue_url = '', string $debug_output = '', string $auto_redirect_url = '' ): void {
	// The page-level renderer reads this state after normal Help variables and markup are available.
	global $ai4seo_debug_operation_completion_page;

	$ai4seo_debug_operation_completion_page = array(
		'result'            => $result,
		'continue_url'      => $continue_url,
		'debug_output'      => $debug_output,
		'auto_redirect_url' => $auto_redirect_url,
	);
}

/**
 * Creates a one-time token for opening a URL with this plugin stopped.
 *
 * @param string $target_url Target URL.
 * @return string Token, or an empty string when storing failed.
 */
function ai4seo_create_prohibit_plugin_token( string $target_url ): string {
	// Store only a hash-derived transient key so the raw token never becomes an option name.
	$token = wp_generate_password( 32, false, false );

	$stored = set_transient(
		ai4seo_get_prohibit_plugin_token_transient_name( $token ),
		$target_url,
		5 * MINUTE_IN_SECONDS
	);

	return $stored ? $token : '';
}

/**
 * Validates a debug target URL and returns a safe same-site URL.
 *
 * @param string $target_url Target URL.
 * @return string Valid target URL, or empty string.
 */
function ai4seo_get_valid_debug_operation_target_url( string $target_url ): string {
	// Normalize admin input before comparing the URL against this site's trusted hosts.
	$target_url = trim( $target_url );

	if ( '' === $target_url ) {
		return '';
	}

	// Require a full HTTP(S) URL because the early plugin loader cannot infer admin intent from relative paths.
	$target_url    = esc_url_raw( $target_url, array( 'http', 'https' ) );
	$target_scheme = wp_parse_url( $target_url, PHP_URL_SCHEME );
	$target_host   = wp_parse_url( $target_url, PHP_URL_HOST );

	if ( ! is_string( $target_scheme ) || ! in_array( strtolower( $target_scheme ), array( 'http', 'https' ), true ) || ! is_string( $target_host ) ) {
		return '';
	}

	$allowed_hosts = array();

	// Accept both home and site hosts so installations with separate WordPress and frontend URLs still work.
	foreach ( array( home_url(), site_url() ) as $allowed_url ) {
		$allowed_host = wp_parse_url( $allowed_url, PHP_URL_HOST );

		if ( is_string( $allowed_host ) && '' !== $allowed_host ) {
			$allowed_hosts[] = strtolower( $allowed_host );
		}
	}

	if ( ! in_array( strtolower( $target_host ), array_unique( $allowed_hosts ), true ) ) {
		return '';
	}

	return $target_url;
}

/**
 * Reads the post ID field shared by post-content debug operations.
 *
 * @param array $request Unsanitized request values.
 * @return int Post ID, or 0 when the submitted value is not usable.
 */
function ai4seo_get_debug_operation_post_id( array $request ): int {
	// Keep array input from reaching absint() while preserving the old numeric-string behavior.
	$ai4seo_debug_post_id_raw = $request['ai4seo_debug_operation_post_id'] ?? 0;

	return is_scalar( $ai4seo_debug_post_id_raw ) && is_numeric( $ai4seo_debug_post_id_raw ) ? absint( $ai4seo_debug_post_id_raw ) : 0;
}

/**
 * Runs one manual active metadata migration v235 batch with before/after debug output.
 *
 * @return void
 */
function ai4seo_run_active_metadata_migration_v235_debug_operation(): void {
	// Capture migration state before the manual run so support can distinguish locked, completed, and progressed states.
	$ai4seo_active_metadata_migration_v235_state_before              = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, false );
	$ai4seo_active_metadata_migration_v235_started_time_before       = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, false );
	$ai4seo_active_metadata_migration_v235_processed_entries_before  = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, false );
	$ai4seo_active_metadata_migration_v235_processing_lock_is_active = (
		'processing' === $ai4seo_active_metadata_migration_v235_state_before
		&& $ai4seo_active_metadata_migration_v235_started_time_before > time() - ( 15 * MINUTE_IN_SECONDS )
	);

	ai4seo_debug_message(
		984321703,
		'Manual v235 active metadata migration run requested. State before: ' . ai4seo_stringify( $ai4seo_active_metadata_migration_v235_state_before )
		. ', started time before: ' . ai4seo_stringify( ai4seo_format_unix_timestamp( $ai4seo_active_metadata_migration_v235_started_time_before ) )
		. ', processed entries before: ' . ai4seo_stringify( $ai4seo_active_metadata_migration_v235_processed_entries_before )
	);

	// Preserve the migration lock and completion checks used by the cron path.
	if ( $ai4seo_active_metadata_migration_v235_processing_lock_is_active ) {
		ai4seo_debug_message(
			984321704,
			'Manual v235 active metadata migration run skipped because another run is marked as processing. Started time: '
			. ai4seo_stringify( ai4seo_format_unix_timestamp( $ai4seo_active_metadata_migration_v235_started_time_before ) ),
			true
		);
	} elseif ( ai4seo_is_active_metadata_migration_v235_completed() ) {
		ai4seo_debug_message(
			984321705,
			'Manual v235 active metadata migration run skipped because the migration is already completed.'
		);
	} else {
		// Compare post-run counters to the earlier snapshot because the migration helper returns only a boolean.
		$ai4seo_active_metadata_migration_v235_run_result              = ai4seo_active_metadata_migration_v235_cron_job();
		$ai4seo_active_metadata_migration_v235_state_after             = ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STATE, false );
		$ai4seo_active_metadata_migration_v235_started_time_after      = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_STARTED_TIME, false );
		$ai4seo_active_metadata_migration_v235_last_run_time_after     = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_LAST_RUN_TIME, false );
		$ai4seo_active_metadata_migration_v235_processed_entries_after = (int) ai4seo_read_environmental_variable( AI4SEO_ENVIRONMENTAL_VARIABLE_ACTIVE_METADATA_MIGRATION_V235_PROCESSED_ENTRIES, false );

		ai4seo_debug_message(
			984321706,
			'Manual v235 active metadata migration run finished. Result: ' . ( $ai4seo_active_metadata_migration_v235_run_result ? 'true' : 'false' )
			. ', state after: ' . ai4seo_stringify( $ai4seo_active_metadata_migration_v235_state_after )
			. ', started time after: ' . ai4seo_stringify( ai4seo_format_unix_timestamp( $ai4seo_active_metadata_migration_v235_started_time_after ) )
			. ', last run time after: ' . ai4seo_stringify( ai4seo_format_unix_timestamp( $ai4seo_active_metadata_migration_v235_last_run_time_after ) )
			. ', processed entries after: ' . ai4seo_stringify( $ai4seo_active_metadata_migration_v235_processed_entries_after )
			. ', processed entries this run: ' . ai4seo_stringify( max( 0, $ai4seo_active_metadata_migration_v235_processed_entries_after - $ai4seo_active_metadata_migration_v235_processed_entries_before ) ),
			! $ai4seo_active_metadata_migration_v235_run_result
		);
	}
}

/**
 * Executes a selected debug operation.
 *
 * @param string $operation Debug operation key.
 * @param array  $request   Unsanitized request values.
 * @return array<string, mixed> Execution result.
 */
function ai4seo_execute_debug_operation( string $operation, array $request ): array {
	if ( ! ai4seo_can_administer_plugin() ) {
		return array(
			'success' => false,
			'message' => __( 'You are not allowed to run debug operations.', 'ai-for-seo' ),
		);
	}

	$debug_operations = ai4seo_get_debug_operations();

	// Reject unknown operation keys before any operation-specific input is read.
	if ( ! isset( $debug_operations[ $operation ] ) ) {
		return array(
			'success' => false,
			'message' => __( 'Please select a valid debug operation.', 'ai-for-seo' ),
		);
	}

	// Dispatch only operations from the registry above; form fields are validated in their individual cases.
	switch ( $operation ) {
		case 'generate_cronjob':
			// Emit the current cron state before triggering the manual SEO Autopilot run.
			$ai4seo_cron_job_status             = ai4seo_get_cron_job_status( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			$ai4seo_cron_job_status_update_time = ai4seo_get_cron_job_status_update_time( AI4SEO_BULK_GENERATION_CRON_JOB_NAME );
			ai4seo_debug_message( 478684129, ai4seo_stringify( $ai4seo_cron_job_status ) );
			ai4seo_debug_message( 819232188, ai4seo_stringify( ai4seo_format_unix_timestamp( $ai4seo_cron_job_status_update_time ) ) );
			ai4seo_automated_generation_cron_job( true );
			break;

		case 'analyze_cronjob':
			// Run the analysis as a trusted manual debug action, including the heavy-DB override.
			ai4seo_analyze_plugin_performance(
				true, // Debug output.
				true, // Force analysis throttles.
				true, // Trusted admin mutation.
				true, // Heavy DB operations debug override.
			);
			break;

		case 'force_tidyup':
			// Reuse the existing tidy-up routine so this operation stays aligned with normal maintenance.
			ai4seo_tidy_up();
			break;

		case 'debug_condensed_post_content':
			$ai4seo_debug_post_id = ai4seo_get_debug_operation_post_id( $request );

			if ( ! $ai4seo_debug_post_id ) {
				return array(
					'success' => false,
					'message' => __( 'Please enter a valid post ID.', 'ai-for-seo' ),
				);
			}

			$ai4seo_condensed_post_content_from_database = ai4seo_get_condensed_post_content_from_database( $ai4seo_debug_post_id, true );
			ai4seo_add_post_context( $ai4seo_debug_post_id, $ai4seo_condensed_post_content_from_database );
			ai4seo_debug_message(
				658418123,
				'FINAL WITH CONTEXT >' . ai4seo_stringify(
					htmlspecialchars( $ai4seo_condensed_post_content_from_database, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
				)
			);
			break;

		case 'debug_combined_post_content':
			$ai4seo_debug_post_id = ai4seo_get_debug_operation_post_id( $request );

			if ( ! $ai4seo_debug_post_id ) {
				return array(
					'success' => false,
					'message' => __( 'Please enter a valid post ID.', 'ai-for-seo' ),
				);
			}

			$ai4seo_get_combined_post_content = ai4seo_get_combined_post_content( $ai4seo_debug_post_id, '', true, true );
			ai4seo_debug_message(
				402011426,
				ai4seo_stringify(
					htmlspecialchars( $ai4seo_get_combined_post_content, ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401, 'UTF-8' )
				)
			);
			break;

		case 'debug_posts_table_analysis':
			// Run the posts-table refresh as a trusted manual debug action, including the heavy-DB override.
			ai4seo_force_posts_table_analysis_refresh(
				true, // Debug output.
				true, // Force analysis throttles.
				true, // Trusted admin mutation.
				true, // Heavy DB operations debug override.
			);
			break;

		case 'read_generation_status_summary':
			// Preserve the two old summary flags as nonce-protected form checkboxes.
			$ai4seo_totals_only               = ! empty( $request['ai4seo_debug_generation_status_summary_totals_only'] );
			$ai4seo_direct_database_call      = ! empty( $request['ai4seo_debug_generation_status_summary_direct_database_call'] );
			$ai4seo_generation_status_summary = ai4seo_read_generation_status_summary( $ai4seo_totals_only, $ai4seo_direct_database_call );
			ai4seo_debug_message( 49174526, ai4seo_stringify( $ai4seo_generation_status_summary ) );
			break;

		case 'debug_active_metadata_migration_v235':
			ai4seo_run_active_metadata_migration_v235_debug_operation();
			break;

		case 'prohibit-ai-for-seo':
			// The target URL becomes an early-loader request, so it must be same-site before a token is minted.
			$target_url_raw = $request['ai4seo_debug_operation_target_url'] ?? '';
			$target_url     = is_scalar( $target_url_raw ) ? ai4seo_get_valid_debug_operation_target_url( (string) $target_url_raw ) : '';

			if ( '' === $target_url ) {
				return array(
					'success' => false,
					'message' => __( 'Please enter a valid same-site target URL.', 'ai-for-seo' ),
				);
			}

			$token = ai4seo_create_prohibit_plugin_token( $target_url );

			if ( '' === $token ) {
				return array(
					'success' => false,
					'message' => __( 'Could not create a one-time plugin prohibition token. Please try again.', 'ai-for-seo' ),
				);
			}

			// Return a redirect URL instead of a stored result so the form can open the one-time request in a new tab.
			return array(
				'success'          => true,
				'message'          => __( 'Opening the target URL with the plugin prohibited.', 'ai-for-seo' ),
				'redirect_url'     => ai4seo_add_query_arg( 'ai4seo_prohibit_plugin_token', $token, $target_url ),
				'trusted_redirect' => true,
				'auto_redirect'    => true,
			);
	}

	return array(
		'success' => true,
		'message' => sprintf(
			/* translators: %s: Debug operation label. */
			__( 'Debug operation completed: %s', 'ai-for-seo' ),
			$debug_operations[ $operation ]['label']
		),
	);
}

/**
 * Handles nonce-protected debug operation submissions.
 *
 * @return void
 */
function ai4seo_handle_debug_operation_request(): void {
	// Treat any owned debug-operation field as a submission because browsers omit the clicked button value on some submit paths.
	$ai4seo_is_debug_operation_request = (
		isset( $_POST['ai4seo-debug-operation-submit'] )
		|| isset( $_POST['ai4seo_debug_operation'] )
		|| isset( $_POST['ai4seo-debug-operation-nonce'] )
	);

	if ( ! $ai4seo_is_debug_operation_request ) {
		return;
	}

	// Fail closed before dispatching because all operations can trigger support-only debug paths.
	if ( ! ai4seo_can_administer_plugin() ) {
		wp_die( esc_html__( 'You are not allowed to run debug operations.', 'ai-for-seo' ), esc_html__( 'Debug operation rejected', 'ai-for-seo' ), array( 'response' => 403 ) );
	}

	// The nonce covers the selected operation and any optional operation-specific fields.
	if ( ! isset( $_POST['ai4seo-debug-operation-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai4seo-debug-operation-nonce'] ) ), 'ai4seo-debug-operation' ) ) {
		wp_die( esc_html__( 'Security check failed. Please try again.', 'ai-for-seo' ), esc_html__( 'Debug operation rejected', 'ai-for-seo' ), array( 'response' => 403 ) );
	}

	// Sanitize the operation key separately while preserving raw field values for operation-specific validation.
	$request       = wp_unslash( $_POST );
	$operation_raw = $request['ai4seo_debug_operation'] ?? '';
	$operation     = is_scalar( $operation_raw ) ? sanitize_key( (string) $operation_raw ) : '';

	// Capture print-style debug output so the completion page can hide it together with the status block.
	ob_start();
	$result                          = ai4seo_execute_debug_operation( $operation, $request );
	$ai4seo_debug_operation_output   = (string) ob_get_clean();
	$ai4seo_needs_completion_wrapper = headers_sent() || '' !== trim( $ai4seo_debug_operation_output );

	// Prohibit-plugin requests intentionally leave the admin page and are opened by the form target.
	if ( ! empty( $result['redirect_url'] ) && is_string( $result['redirect_url'] ) ) {
		if ( $ai4seo_needs_completion_wrapper ) {
			// Expose the target link when the surrounding admin frame has already made HTTP redirects unavailable.
			$auto_redirect_url = ! empty( $result['auto_redirect'] ) ? $result['redirect_url'] : '';
			ai4seo_prepare_debug_operation_completion_page( $result, $result['redirect_url'], $ai4seo_debug_operation_output, $auto_redirect_url );
			return;
		}

		if ( ! empty( $result['trusted_redirect'] ) ) {
			// Use the already validated same-site URL so split admin/frontend hosts do not fall back to the Help page.
			// phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- The target URL is validated against home_url()/site_url() before this tokenized redirect is returned.
			wp_redirect( $result['redirect_url'] );
			exit;
		}

		// Keep any future generic debug redirects on WordPress' safe redirect path by default.
		wp_safe_redirect( $result['redirect_url'] );
		exit;
	}

	// Store one short-lived result message so the hidden Help page can still show the outcome after the in-place return.
	set_transient(
		ai4seo_get_debug_operation_result_transient_name(),
		array(
			'success' => ! empty( $result['success'] ),
			'message' => sanitize_text_field( $result['message'] ?? '' ),
		),
		MINUTE_IN_SECONDS
	);

	// Normal operations finish on a local completion view while the full Troubleshooting page remains hidden for the Go back action.
	ai4seo_prepare_debug_operation_completion_page( $result, '', $ai4seo_debug_operation_output );
}

// Handle submitted debug operations before the Help page content markup is rendered.
if ( $ai4seo_can_administer_plugin ) {
	ai4seo_handle_debug_operation_request();
}

// Copy handler-written completion state back into this include scope so the renderer below can show the local completion view.
$ai4seo_debug_operation_completion_page = isset( $GLOBALS['ai4seo_debug_operation_completion_page'] ) && is_array( $GLOBALS['ai4seo_debug_operation_completion_page'] )
	? $GLOBALS['ai4seo_debug_operation_completion_page']
	: array();


// ___________________________________________________________________________________________ \\
// === PREPARE =============================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Variable for the subject-options for the contact form.
$ai4seo_contact_subject_options = array(
	'0'                       => __( 'Please select', 'ai-for-seo' ),
	'Request a Feature'       => __( 'Request a Feature', 'ai-for-seo' ),
	'Report a bug'            => __( 'Report a bug', 'ai-for-seo' ),
	'Get a quote'             => __( 'Get a quote', 'ai-for-seo' ),
	'General question'        => __( 'General question', 'ai-for-seo' ),
	'I need help with my SEO' => __( 'I need help with my SEO', 'ai-for-seo' ),
	'Other'                   => __( 'Other', 'ai-for-seo' ),
);

$ai4seo_free_plan_credits = ai4seo_get_plan_credits( 'free' );
$ai4seo_s_plan_credits    = ai4seo_get_plan_credits( 's' );
$ai4seo_m_plan_credits    = ai4seo_get_plan_credits( 'm' );
$ai4seo_l_plan_credits    = ai4seo_get_plan_credits( 'l' );

// Prepare debug operation labels and consume the last stored result for the current admin user.
$ai4seo_debug_operations       = ai4seo_get_debug_operations();
$ai4seo_debug_operation_result = get_transient( ai4seo_get_debug_operation_result_transient_name() );

// Delete consumed result messages so a page refresh does not repeat an old operation outcome.
if ( false !== $ai4seo_debug_operation_result ) {
	delete_transient( ai4seo_get_debug_operation_result_transient_name() );
}

// Normalize the optional transient payload so rendering can use a single array shape.
if ( ! is_array( $ai4seo_debug_operation_result ) ) {
	$ai4seo_debug_operation_result = array();
}

// Render the full Help page behind the debug-operation completion fallback so the return control can reveal it without another request.
$ai4seo_has_debug_operation_completion_page = ! empty( $ai4seo_debug_operation_completion_page ) && is_array( $ai4seo_debug_operation_completion_page );

$ai4seo_reset_generated_data_tooltip = sprintf(
	/* translators: %1$s plugin name, %2$s plugin name */
	__( '<strong>This will delete AI-generated data for the selected entry types.</strong><br>Use this option to allow *%1$s* to regenerate previously generated data for those entry types.<br><br>- <strong>Note:</strong> This does not remove or undo data that has been synced to third-party SEO plugins or written into the media library. However, this data will be considered "old" and can be overwritten if the "Include Complete Entries When Overwriting" setting is enabled.<br>- No Credits will be refunded.<br>- <strong>Caution:</strong> This action is irreversible and may cause metadata generated by *%2$s* to no longer appear on your website.', 'ai-for-seo' ),
	esc_html( AI4SEO_PLUGIN_NAME ),
	esc_html( AI4SEO_PLUGIN_NAME )
);
$ai4seo_generated_data_reset_post_type_counts = ai4seo_get_generated_data_reset_post_type_counts();
$ai4seo_credits_packs                         = ai4seo_get_credits_packs();

// Keep the selected Help section server-readable so refreshes render the active tile and content without a client-side correction flicker.
$ai4seo_help_sections = array(
	'getting-started' => array(
		'anchor_id' => 'ai4seo-getting-started-section',
		'target_id' => 'ai4seo-help-getting-started',
	),
	'faq'             => array(
		'anchor_id' => 'ai4seo-faq-section',
		'target_id' => 'ai4seo-help-faq',
	),
	'troubleshooting' => array(
		'anchor_id' => 'ai4seo-troubleshooting-section',
		'target_id' => 'ai4seo-help-troubleshooting',
	),
	'links'           => array(
		'anchor_id' => 'ai4seo-links-section',
		'target_id' => 'ai4seo-help-links',
	),
);

if ( ! $ai4seo_can_administer_plugin ) {
	unset( $ai4seo_help_sections['troubleshooting'] );
}

// Accept only known Help section keys from the request; hashes are not sent to PHP during a refresh.
$ai4seo_requested_help_section = 'getting-started';

if ( isset( $_GET['ai4seo_help_section'] ) && ! is_array( $_GET['ai4seo_help_section'] ) ) {
	$ai4seo_requested_help_section = sanitize_key( wp_unslash( $_GET['ai4seo_help_section'] ) );
}
$ai4seo_active_help_section = isset( $ai4seo_help_sections[ $ai4seo_requested_help_section ] ) ? $ai4seo_requested_help_section : 'getting-started';

// Precompute link and class state once so navigation tiles and content containers cannot drift apart.
foreach ( $ai4seo_help_sections as $ai4seo_this_help_section_key => $ai4seo_this_help_section ) {
	// Build the same canonical URL used by JavaScript so the selected tile survives a browser refresh.
	$ai4seo_help_section_url = ai4seo_get_subpage_url( 'help', array( 'ai4seo_help_section' => $ai4seo_this_help_section_key ) ) . '#' . $ai4seo_this_help_section['anchor_id'];

	// Store active-state derivatives with the section data so navigation and content markup use one source.
	$ai4seo_is_this_help_section_active = ( $ai4seo_active_help_section === $ai4seo_this_help_section_key );

	// Attach render-ready state to each section while keeping the original section identifiers intact.
	$ai4seo_help_sections[ $ai4seo_this_help_section_key ]['url']              = $ai4seo_help_section_url;
	$ai4seo_help_sections[ $ai4seo_this_help_section_key ]['is_active']        = $ai4seo_is_this_help_section_active;
	$ai4seo_help_sections[ $ai4seo_this_help_section_key ]['navigation_class'] = 'ai4seo-help-preview-selection ai4seo-help-navigation-link' . ( $ai4seo_is_this_help_section_active ? ' ai4seo-active-help-preview-selection' : '' );
	$ai4seo_help_sections[ $ai4seo_this_help_section_key ]['content_class']    = $ai4seo_is_this_help_section_active ? 'ai4seo-help-content' : 'ai4seo-display-none ai4seo-help-content';
}


// ___________________________________________________________________________________________ \\
// === PROCESS CONTACT FORM ================================================================== \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

do {
	if ( isset( $_POST['ai4seo-contact-form-submit'] ) ) {
		// check if the nonce is valid.
		if ( ! isset( $_POST['ai4seo-contact-form-nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['ai4seo-contact-form-nonce'] ) ), 'ai4seo-contact-form' ) ) {
			// Invalid nonce, stop the script.
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . esc_html__( 'Security check failed. Please try again.', 'ai-for-seo' ) . '</p>';
			echo '</div>';
			break;
		}

		// Stop script if the user tries to submit the form within 60 seconds after the last submit.
		$ai4seo_last_contact_form_submit_time = (int) get_transient( 'ai4seo_last_contact_form_submit_timestamp' );

		if ( $ai4seo_last_contact_form_submit_time && ( time() - $ai4seo_last_contact_form_submit_time ) < 60 ) {
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . sprintf(
					/* translators: %s: Number of seconds. */
					esc_html__( 'You can submit only one request every %s seconds. Please wait a moment and try again.', 'ai-for-seo' ),
					esc_html( ai4seo_format_number_i18n( 60 ) )
				) . '</p>';
			echo '</div>';
			break;
		}

		// Sanitize.
		$ai4seo_contact_form_name    = sanitize_text_field( wp_unslash( $_POST['ai4seo-contact-form-name'] ?? '' ) );
		$ai4seo_contact_form_email   = sanitize_email( wp_unslash( $_POST['ai4seo-contact-form-email'] ?? '' ) );
		$ai4seo_contact_form_subject = sanitize_text_field( wp_unslash( $_POST['ai4seo-contact-form-subject'] ?? '' ) );
		$ai4seo_contact_form_message = sanitize_textarea_field( wp_unslash( $_POST['ai4seo-contact-form-message'] ?? '' ) );

		// shorten name and message.
		$ai4seo_contact_form_name    = substr( $ai4seo_contact_form_name, 0, 64 );
		$ai4seo_contact_form_message = substr( $ai4seo_contact_form_message, 0, 5000 );

		// Make sure that name exists.
		if ( ! $ai4seo_contact_form_name || ! preg_match( "/^[\p{L} '-]+$/u", $ai4seo_contact_form_name ) ) {
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . esc_html__( 'Please enter a valid name!', 'ai-for-seo' ) . '</p>';
			echo '</div>';
			break;
		}

		// Make sure that email exists.
		if ( ! $ai4seo_contact_form_email || ! filter_var( $ai4seo_contact_form_email, FILTER_VALIDATE_EMAIL ) ) {
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . esc_html__( 'Please enter a valid email-address!', 'ai-for-seo' ) . '</p>';
			echo '</div>';
			break;
		}

		// Make sure that subject exists.
		if ( ! $ai4seo_contact_form_subject || '0' === $ai4seo_contact_form_subject || ! isset( $ai4seo_contact_subject_options[ $ai4seo_contact_form_subject ] ) ) {
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . esc_html__( 'Please enter a valid subject!', 'ai-for-seo' ) . '</p>';
			echo '</div>';
			break;
		}

		// Make sure that message exists.
		if ( ! $ai4seo_contact_form_message ) {
			echo "<div class='notice notice-error is-dismissible'>";
				echo '<p>' . esc_html__( 'Please enter a message!', 'ai-for-seo' ) . '</p>';
			echo '</div>';
			break;
		}

		// Prepare send email.
		$ai4seo_email_recipient      = esc_html( sanitize_email( AI4SEO_SUPPORT_EMAIL ) );
		$ai4seo_contact_form_subject = esc_html( $ai4seo_contact_form_subject );
		$ai4seo_contact_form_message = nl2br( esc_textarea( $ai4seo_contact_form_message ) );

		// Prepare headers.
		$ai4seo_email_headers = array(
			'MIME-Version: 1.0',
			'Content-type: text/html; charset=utf-8',
			'From: ' . esc_html( $ai4seo_contact_form_name ) . ' <' . esc_html( $ai4seo_contact_form_email ) . '>',
		);

		// Send email.
		$ai4seo_sent_email_successfully = wp_mail( $ai4seo_email_recipient, $ai4seo_contact_form_subject, $ai4seo_contact_form_message, $ai4seo_email_headers );

		// Display success message.
		if ( $ai4seo_sent_email_successfully ) {
			echo "<div class='notice notice-success is-dismissible'>";
				echo '<p>' . esc_html__( 'Thank you for your message. We have successfully received your email and will be in touch with you shortly.', 'ai-for-seo' ) . '</p>';
			echo '</div>';

			set_transient( 'ai4seo_last_contact_form_submit_timestamp', time(), 60 );
		} else {
			// Display error message.
			echo "<div class='notice notice-error is-dismissible'>";
			echo '<p>' . sprintf(
				/* translators: %s: contact URL */
				esc_html__( "Your system encountered an issue sending the email. Please try again later, or feel free to <a href='%s' target='_blank'>contact us</a> directly if the problem persists.", 'ai-for-seo' ),
				esc_html( AI4SEO_OFFICIAL_CONTACT_URL ),
			) . '</p>';
			echo '</div>';
		}
	}
} while ( 0 );


// ___________________________________________________________________________________________ \\
// === OUTPUT ================================================================================ \\
// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

// Keep debug-operation POST fallbacks on one loaded page, with the normal Help markup hidden until the admin returns.
if ( $ai4seo_has_debug_operation_completion_page ) {
	ai4seo_render_debug_operation_completion_page( $ai4seo_debug_operation_completion_page );
	echo "<div id='ai4seo-debug-operation-full-help-page' class='ai4seo-display-none'>";
}

echo "<div class='ai4seo-help-selection-container'>";

	// === CONTAINERS FOR THE BUTTONS TO THE HELP-AREAS ========================================== \\

	// Getting started is the fallback server-rendered Help section when no valid section key is present.
	echo "<a href='" . esc_url( $ai4seo_help_sections['getting-started']['url'] ) . "'" . ( $ai4seo_help_sections['getting-started']['is_active'] ? " aria-current='page'" : '' ) . "><div class='" . esc_attr( $ai4seo_help_sections['getting-started']['navigation_class'] ) . "' data-ai4seo-help-target='" . esc_attr( $ai4seo_help_sections['getting-started']['target_id'] ) . "'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'rocket', '', '', true ) );
		echo '<span>' . esc_html__( 'Getting started', 'ai-for-seo' ) . '</span>';
	echo '</div></a>';

	// FAQ uses the same server-readable section key as the content container to avoid refresh mismatch.
	echo "<a href='" . esc_url( $ai4seo_help_sections['faq']['url'] ) . "'" . ( $ai4seo_help_sections['faq']['is_active'] ? " aria-current='page'" : '' ) . "><div class='" . esc_attr( $ai4seo_help_sections['faq']['navigation_class'] ) . "' data-ai4seo-help-target='" . esc_attr( $ai4seo_help_sections['faq']['target_id'] ) . "'>";
		ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'editor-help', '', true ) );
		echo '<span>' . esc_html__( 'F.A.Q.', 'ai-for-seo' ) . '</span>';
	echo '</div></a>';

	// Contact
	// echo "<a href='#ai4seo-contact-section'><div class='ai4seo-help-preview-selection'  onclick='jQuery(\".ai4seo-help-content\").hide();jQuery(\"#ai4seo-help-contact\").show();'>";.
	echo "<a href='" . esc_attr( AI4SEO_OFFICIAL_CONTACT_URL ) . "' target='_blank'><div class='ai4seo-help-preview-selection'>";
		ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'email', '', true ) );
		echo '<span>' . esc_html__( 'Contact', 'ai-for-seo' ) . '</span><br>';
		echo "<span class='ai4seo-help-language-note'>" . esc_html__( '(All Languages)', 'ai-for-seo' ) . '</span><br>';
	echo '</div></a>';

if ( $ai4seo_can_administer_plugin ) {
		// Troubleshooting contains site-wide reset and diagnostic controls.
		echo "<a href='" . esc_url( $ai4seo_help_sections['troubleshooting']['url'] ) . "'" . ( $ai4seo_help_sections['troubleshooting']['is_active'] ? " aria-current='page'" : '' ) . "><div class='" . esc_attr( $ai4seo_help_sections['troubleshooting']['navigation_class'] ) . "' data-ai4seo-help-target='" . esc_attr( $ai4seo_help_sections['troubleshooting']['target_id'] ) . "'>";
			ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'hammer', '', true ) );
			echo '<span>' . esc_html__( 'Troubleshooting', 'ai-for-seo' ) . '</span>';
		echo '</div></a>';
}

	// Useful links share the same navigation data contract as the other local Help sections.
	echo "<a href='" . esc_url( $ai4seo_help_sections['links']['url'] ) . "'" . ( $ai4seo_help_sections['links']['is_active'] ? " aria-current='page'" : '' ) . "><div class='" . esc_attr( $ai4seo_help_sections['links']['navigation_class'] ) . "' data-ai4seo-help-target='" . esc_attr( $ai4seo_help_sections['links']['target_id'] ) . "'>";
		ai4seo_echo_wp_kses( ai4seo_get_dashicon_tag( 'admin-links', '', true ) );
		echo '<span>' . esc_html__( 'Useful links', 'ai-for-seo' ) . '</span>';
	echo '</div></a>';

echo '</div>';

echo "<div class='ai4seo-clear'></div>";


// === GETTING STARTED ======================================================================= \\

// Render the selected Help section on the server so refreshes do not flash another section before JavaScript initializes.
echo "<div class='" . esc_attr( $ai4seo_help_sections['getting-started']['content_class'] ) . "' id='" . esc_attr( $ai4seo_help_sections['getting-started']['target_id'] ) . "'>";
	// Headline.
	echo "<h1 id='ai4seo-getting-started-section'>";
		echo esc_html__( 'Getting started', 'ai-for-seo' );
	echo '</h1>';

	// === FIRST STEPS =========================================================================== \\

	// Dashboard.
	$ai4seo_this_accordion_content  = '<p><b>1.</b> ' . __( '<b>Dashboard</b>: Take a look at the statistics at the top of the page. These show which parts of your website have been optimized and which parts have not.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . __( '<b>Dashboard -> Credits</b>: In this section you will see how many Credits are currently available to use. Underneath the total number of Credits you will see an exact cost breakdown followed by the button <b>Get more Credits</b>. If you click on this button, you will see a modal with different purchase options to obtain more Credits.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>2.1.</b> ' . __( '<b>Dashboard -> Credits -> Get more Credits</b>: After clicking on this button a modal with different purchase options will open. Here you can purchase Credits Packs, start or manage a monthly or yearly subscription and customize the Pay-As-You-Go feature to automatically refill your Credits balance with a pre-set amount. Additionally you will see information about the next free daily Credits you will receive.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . __( '<b>Dashboard → SEO Autopilot (Bulk Generation)</b>: In this section, you can view the current status of the SEO Autopilot. At the bottom of this section, you’ll find buttons to set up or stop the SEO Autopilot.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>3.1.</b> ' . __( '<b>Dashboard → SEO Autopilot (Bulk Generation) → Set up SEO Autopilot</b>: Clicking this button opens a modal with the settings for the SEO Autopilot. In this modal, you can choose the content types for which the SEO Autopilot should generate SEO data. You can also specify whether it should manage only new content entries, only existing ones, or both. Additionally, you can define the order in which the SEO Autopilot processes the content entries.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-2.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	$ai4seo_this_accordion_content .= '<p><b>4.</b> ' . __( '<b>Dashboard -> Recent activity</b>: This section will display the recent plugin activity. Next to each item in this list you will see a button to display each content and a button to edit the SEO settings for the entry.', 'ai-for-seo' ) . '</p>';

	// Account.
	$ai4seo_this_accordion_content .= '<p><b>5.</b> ';
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= sprintf( __( '<b>Account</b>: Check out the account settings of the *%s* plugin by clicking on the "Account" menu item in the plugin navigation. On this page you will find your license-details, the settings for SEO and Web Agencies, as well as a section for Privacy & Agreements.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-3.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	$ai4seo_this_accordion_content .= '<p><b>5.1.</b> ' . __( '<b>Account -> License</b>: In this section you will find input fields for the license-owner and the license-key. Additionally you will have access to different buttons to manage and set up payment options.', 'ai-for-seo' ) . '</p>';
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>5.2.</b> ' . sprintf( __( '<b>Account -> For SEO and Web Agencies</b>: Here you can set up different plugin-features that are mainly designed for agencies. These settings allow you to enable the incognito mode to hide the plugin from other users. You will also find the white-label settings that you can use to put your own spin on the *%s* plugin to make it your own.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	// Settings.
	$ai4seo_this_accordion_content .= '<p><b>6.</b> ' . __( '<b>Settings</b>: Checkout the plugins default-settings listed in the "Settings" menu item. Make sure that those settings meet your needs and adjust them accordingly if necessary.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-4.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>6.1.</b> ' . sprintf( __( '<b>Settings -> Metadata</b>: In this section you can change the settings for the metadata generated by the *%s* plugin. This will affect content types such as pages, posts and products.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>6.2.</b> ' . sprintf( __( '<b>Settings -> Media attributes</b>: In this section you can change the settings for the media attributes generated by the *%s* plugin. This will affect media files.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>6.3.</b> ' . sprintf( __( '<b>Settings -> User Management</b>: Here you can select the user groups that should be able to manage the *%s* plugin.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>6.4.</b> ' . __( "<b>Settings -> Troubleshooting -> SEO Autopilot (Bulk Generation) Duration</b>: In this section, you can define the duration of a single SEO Autopilot run. Adjusting this setting may help you process your site's content more efficiently in certain cases.", 'ai-for-seo' ) . '</p>';

	// Content tabes.
	$ai4seo_this_accordion_content .= '<p><b>7.</b> ' . __( '<b>Content pages</b>: Inside the plugin you will find menu items for each supported content type (i.e. Page, Post, etc.). Click on any menu item and open the metadata editor by clicking on the button on the right-hand side of an entry.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>8.</b> ' . __( '<b>Metadata editor</b>: Use Preview mode to review approximate Google, Facebook, X, WhatsApp, and keyword appearances together with field-specific guidance. Use Editor mode to change the existing inputs or generate individual fields. Focus Keyphrase and Custom Instructions remain prominent generation context, and Generate All stays available in the compact editor header. Choose the normal opening mode under Settings -> General -> Default editor mode.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-5.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	$ai4seo_this_accordion_content .= '<p><b>9.</b> ' . __( '<b>Media page</b>: After clicking on the media menu item you can open the media attribute editor by clicking on the button on the right-hand side of each media-entry.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>10.</b> ' . __( '<b>Media attribute editor</b>: Use Preview mode to inspect WordPress-style Title and Description presentations, Alt Text accessibility output, and a theme-neutral Caption example with guidance. Use Editor mode to change or generate the existing media attribute fields. Custom Instructions and Generate All remain available in either workflow.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-6.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	// Help & contact.
	$ai4seo_this_accordion_content .= '<p><b>11.</b> ' . __( '<b>Help</b>: You should also check out the "Help" page of the plugin. On this page we put together several helpful tips and tutorials for you. In addition you will find some troubleshooting functions as well as ways to get in touch with us.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= '<p><b>11.1.</b> ' . __( '<b>Help -> Troubleshooting</b>: In this section you have the option to reset some or all of the plugin date. Please make sure to use these functions with CAUTION. Feel free to reach prior to using these functions if you have any questions about them.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'help-screenshots/first-steps-7.jpg' ) ) . "' class='ai4seo-help-screenshot' />";
	$ai4seo_this_accordion_content .= '<p><b>12.</b> ' . __( '<b>Contact us</b>: If you have questions, suggestions, need further support or require a specific amount of credits, please contact us via Help > Contact. We typically respond within 8 hours.', 'ai-for-seo' ) . '</p>';

	ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'First steps', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

	// === How to edit specific page or post ===================================================== \\

	// First step: Navigate to the page you want to edit.
	$ai4seo_this_accordion_content = '<p><b>1.</b> ' . esc_html__( 'Navigate to the page or post you want to edit. You can do this through the normal editor, using a page builder like Elementor, or by opening the page directly in the frontend.', 'ai-for-seo' ) . '</p>';

	// Second step: Open the "AI for SEO" tool.
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . sprintf( esc_html__( 'Click on the *%s* button located in the top admin-bar. This will open up the Metadata Editor.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-page-post-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	// Third step: Modify or generate SEO content.
	$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . esc_html__( 'Edit existing SEO content or generate new content using our "Generate with SOOZ" buttons. The additional buttons have to be activated in the settings first.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-page-post-2.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	// Additional information: Alternate way to access the "SEO Metadata Editor".
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p>' . sprintf( __( '<b>Alternatively,</b> you can go to the "Pages" or "Posts" page within the *%s* plugin. From there, you can browse through your pages and posts, and choose the ones you want to edit.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-page-post-3.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How to generate or edit SEO-relevant metadata for a specific page or post', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );


	// === Hot to edit specific media-entry ====================================================== \\

	// First step: Select the Relevant Section.
	$ai4seo_this_accordion_content = '<p><b>1.</b> ' . esc_html__( 'Click on the "Media"-link in the main admin-menu of your WordPress-backend', 'ai-for-seo' ) . '</p>';

	// Second step: Activate the Automation Feature.
	$ai4seo_this_accordion_content .= '<p><b>2.1</b> ' . esc_html__( 'If your media-page is using the grid view, click on the specific image file for which you would like to add/edit the attributes for.', 'ai-for-seo' ) . '<br />';
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= sprintf( esc_html__( 'Once the media-modal is opened you will see the *%s*-generate-buttons on the right side above and within the form. These additional media buttons have to be activated in the settings first. Then click on the button to generate the content for each attribute.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	$ai4seo_this_accordion_content .= '<p><b>2.2</b> ' . esc_html__( 'If your media-page is using the table view, click on the edit-button which will appear once you hover over the entry of the specific image file for which you would like to add/edit the attributes for.', 'ai-for-seo' ) . '<br />';
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= sprintf( esc_html__( 'Once the edit-media-page is opened you will see the *%s*-generate-buttons within the form of the page. These additional media buttons have to be activated in the settings first. Then click on the button to generate the content for each attribute.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p>' . sprintf( __( '<b>Alternatively,</b> you can go to the "Media" page within the *%s* plugin. From there, you can browse through your media-entries, and choose the ones you want to edit.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How to add alt-text, captions, titles and descriptions for media files', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );


	// === Bulk generate metadata ======================================================== \\

	// First step: Click the "Set up SEO Autopilot"-button on the plugin dashboard.
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content  = '<p><b>1.</b> ' . sprintf( esc_html__( 'Click on the "Set up SEO Autopilot"-button at the bottom of the "SEO Autopilot (Bulk Generation)"-section on the dashboard of the *%s* plugin page. This will open the "Set up SEO Autopilot" modal.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-seo-autopilot-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	// Second step: Settings of the SEO Autopilot.
	$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . esc_html__( 'Within the “Set up SEO Autopilot” modal, you can adjust various settings to fit your needs. Once everything is set up to your liking, click “Update SEO Autopilot” at the bottom of the modal to save your settings.', 'ai-for-seo' ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-seo-autopilot-2.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	// Third step: Settings of the SEO Autopilot.
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . sprintf( esc_html__( 'If you wish to disable the SEO Autopilot, simply click on the "Stop SEO Autopilot"-button at the bottom of the "SEO Autopilot (Bulk Generation)"-section on the dashboard of the *%s* plugin page.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
	$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-seo-autopilot-3.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

	ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How to activate bulk generation', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );


	// === Credits ======================================================================== \\

	// Prepare the details to the available credits-packs.
	$ai4seo_available_credits_packs = array();
foreach ( $ai4seo_credits_packs as $ai4seo_this_payg_stripe_price_id => $ai4seo_credits_pack_entry ) {
	if ( isset( $ai4seo_credits_pack_entry['credits_amount'] ) ) {
		$ai4seo_available_credits_packs[] = ai4seo_format_number_i18n( $ai4seo_credits_pack_entry['credits_amount'] );
	}
}
	$ai4seo_available_credits_packs_string = implode( ', ', $ai4seo_available_credits_packs );

	// Explanation of how credits are consumed.
	/* translators: %s: plugin name */
	$ai4seo_this_accordion_content = '<p>' . sprintf( esc_html__( 'Credits are used when *%s* generates metadata for your content (posts, pages, products, media files etc.). The amount of Credits required depends on the selected active meta tags and media attributes (see Settings).', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';

	// Details on credits available with different plans.
	$ai4seo_this_accordion_content .= '<ul>';
	$ai4seo_this_accordion_content .= '<li>' . sprintf(
		/* translators: %1$s: number of credits, %2$s: number of daily free credits, %3$s: number of credits */
		__( 'The <b>free plan</b> provides you with %1$s Credits, allowing you to experiment with AI-generated SEO content without any cost. In addition, we provide you with %2$s free Credits every day if your balance falls below %3$s Credits.', 'ai-for-seo' ),
		esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
		esc_html( ai4seo_format_number_i18n( AI4SEO_DAILY_FREE_CREDITS_AMOUNT ) ),
		esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
	) . '</li>';
	$ai4seo_this_accordion_content .= '<li>' . sprintf(
		/* translators: %s: number of credits */
		__( 'With the <b>Basic subscription</b> you receive %s Credits per month: Ideal for smaller websites or blogs.', 'ai-for-seo' ),
		esc_html( ai4seo_format_number_i18n( $ai4seo_s_plan_credits ) )
	) . '</li>';
	$ai4seo_this_accordion_content .= '<li>' . sprintf(
		/* translators: %s: number of credits */
		__( 'With the <b>Pro subscription</b> you receive %s Credits per month: Ideal for professionals who need more extensive SEO support.', 'ai-for-seo' ),
		esc_html( ai4seo_format_number_i18n( $ai4seo_m_plan_credits ) )
	) . '</li>';
	$ai4seo_this_accordion_content .= '<li>' . sprintf(
		/* translators: %s: number of credits */
		__( 'With the <b>Premium subscription</b> you receive %s Credits per month: Ideal for businesses that require substantial SEO support and features.', 'ai-for-seo' ),
		esc_html( ai4seo_format_number_i18n( $ai4seo_l_plan_credits ) )
	) . '</li>';
	$ai4seo_this_accordion_content .= '<li>' . sprintf(
		/* translators: %s: available credits packs */
		__( 'In addition to the subscriptions you can purchase individual <b>Credits Packs</b> to fit your needs perfectly. Credits packs are available in the following sizes: <b>%s</b>', 'ai-for-seo' ),
		esc_html( $ai4seo_available_credits_packs_string )
	) . '</li>';
	$ai4seo_this_accordion_content .= '</ul>';
	$ai4seo_this_accordion_content .= '<p>' . __( "We offer a <strong>Pay-As-You-Go (PAYG)</strong> option which enables you to automatically refill your Credits balance with a custom number of Credits. This way you don't have to worry about purchasing new Credits or ever running out of them. The plugin will automatically purchase Credits for you as soon as you're about to run out.", 'ai-for-seo' ) . '</p>';

	ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do Credits work?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );


	// === "Yoast SEO" elements ======================================================================== \\

	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_YOAST_SEO ) ) {
		// First step: Open the Page or Post.
		$ai4seo_this_accordion_content = '<p><b>1.</b> ' . esc_html__( 'Begin by navigating to the page or blog post you want to edit.', 'ai-for-seo' ) . '</p>';

		// Second step: Initiate AI Generation.
		$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . esc_html__( "Click on the \"Generate with SOOZ\" button, which you'll find within the SEO form. This additional SEO-plugin button has to be activated in the settings first.", 'ai-for-seo' ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-yoast-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		// Third step: Review AI-Generated Description.
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . sprintf( esc_html__( '*%s* will generate a SEO-relevant description based on the content of the selected page or blog post. Review this description to ensure it aligns with your content and objectives.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-yoast-2.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		// Fourth step: Apply AI-Generated SEO to All Fields.
		$ai4seo_this_accordion_content .= '<p><b>4.</b> ' . esc_html__( 'To streamline the process, click the "Generate & Overwrite" button. This will apply the AI-generated descriptions to all corresponding input fields, enhancing the SEO across the entire page or post.', 'ai-for-seo' ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-yoast-3.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Yoast integration', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );
	}

	// === Elementor-elements ==================================================================== \\

	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_ELEMENTOR ) ) {
		// First step: Open the Elementor Editor.
		$ai4seo_this_accordion_content = '<p><b>1.</b> ' . esc_html__( 'Open the page or post you want to edit in Elementor.', 'ai-for-seo' ) . '</p>';

		// Second step: Access the Elementor Sidebar.
		$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . esc_html__( 'Click on the cog-icon located in the Elementor top-bar to open the settings of the page or post.', 'ai-for-seo' ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-elementor-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		// Third step: Open "AI for SEO" layer.
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . sprintf( esc_html__( 'In the settings section, click on the "Show all SEO settings" button to open the *%s* metadata editor. Here, you can adjust the metadata using our AI-driven algorithms.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-elementor-2.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Elementor integration', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );
	}

	// === Be-Builder-elements =================================================================== \\

	if ( ai4seo_is_plugin_or_theme_active( AI4SEO_THIRD_PARTY_PLUGIN_BETHEME ) ) {
		// First step: Open the BeBuilder Editor.
		$ai4seo_this_accordion_content = '<p><b>1.</b> ' . esc_html__( 'Open the page or post you want to edit in BeBuilder, the page-building tool within the BeTheme framework.', 'ai-for-seo' ) . '</p>';

		// Second step: Access SEO Section in BeBuilder.
		$ai4seo_this_accordion_content .= '<p><b>2.</b> ' . esc_html__( 'Click on the page-options-button on the left side of the BeBuilder navigation, then scroll down to the SEO section. This will display all SEO-related settings for the current page.', 'ai-for-seo' ) . '</p>';

		// Third step: Open "AI for SEO" metadata editor.
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= '<p><b>3.</b> ' . sprintf( esc_html__( 'Click on the "Show all SEO settings" button within the SEO section to open the *%s* metadata editor. Here, you can access and manipulate metadata using our AI-driven algorithms.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</p>';
		$ai4seo_this_accordion_content .= "<img src='" . esc_url( ai4seo_get_assets_images_url( 'faq-screenshots/screenshot-be-builder-1.jpg' ) ) . "' class='ai4seo-help-screenshot' />";

		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Be-Builder integration', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );
	}
	echo '</div>';


	// === FAQ =================================================================================== \\

	// FAQ visibility follows the same server-side section state as the active navigation tile.
	echo "<div class='" . esc_attr( $ai4seo_help_sections['faq']['content_class'] ) . "' id='" . esc_attr( $ai4seo_help_sections['faq']['target_id'] ) . "'>";
	// Headline.
	echo "<h1 id='ai4seo-faq-section'>";
		echo esc_html__( 'F.A.Q.', 'ai-for-seo' );
	echo '</h1>';


	// === SEARCH ================================================================================ \\

	// Input for the search.
	echo "<div class='ai4seo-help-search-wrapper'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'magnifying-glass' ) );
		echo "<input type='text' class='ai4seo-help-search' id='ai4seo-help-search' placeholder='" . esc_attr__( 'Search F.A.Q. (enter min.3 characters)', 'ai-for-seo' ) . "' />";
	echo '</div>';

	// Container with the message that no entries could be found based on the search-input.
	echo "<div class='ai4seo-help-search-notice ai4seo-help-faq-search-notice ai4seo-display-none' id='ai4seo-help-faq-search-notice'>";
		echo '<p>' . esc_html__( 'No results could be found based on your search. Please try a different search term.', 'ai-for-seo' ) . '</p>';
	echo '</div>';

	echo "<div class='ai4seo-gap'></div>";

	// === FIND, EDIT & GENERATE SEO DATA ========================================================= \\

	echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Find, Edit & Generate SEO Data', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content = __( 'SEO metadata, including SEO titles and meta descriptions, helps search engines understand your pages and shape search snippets. Better snippets can improve visibility, clicks, visitors, leads, and sales.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why should I add SEO titles, meta descriptions, and metadata to my website?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = esc_html__( 'Use the Preview and Editor buttons at the top of either editor. Preview shows estimated appearances and field-specific guidance, while Editor shows the existing inputs and generation controls. Choose the normal opening mode under Settings > General > Default editor mode. Existing installations upgraded from 2.4.3 or earlier begin in Editor mode unless a mode was already stored.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I switch between Preview and Editor mode in the Metadata or Media Attributes editor?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: link to Google Developers blog post */
			__( "Currently, Google's stance on the use of AI or automation in content creation is generally permissive, as indicated in a Google Developers blog post from February 2023. They state that appropriate use of AI or automation is not against their guidelines. More information can be found at %s.", 'ai-for-seo' ),
			"<a target='_blank' href='https://developers.google.com/search/blog/2023/02/google-search-and-ai-content'>https://developers.google.com/search/blog/2023/02/google-search-and-ai-content</a>"
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Is AI-generated SEO content safe for Google rankings?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes. *%s* analyzes the selected page, post, product, or media file and uses that content as context when generating SEO titles, meta descriptions, focus keyphrases, alt text, and other SEO data.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Does *%s* analyze my page, post, product, or media content before generating SEO data?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Open the page, post, product, or other content through the normal WordPress editor, a supported page builder such as Elementor or BeBuilder, the *%s* content lists, or the frontend admin bar.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Where do I open a page, post, or product to edit its SEO metadata?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'You can find the *%s* button in the top WordPress admin bar. Click it to open the metadata editor for the current page, post, product, or other supported content, then generate or edit metadata.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Where do I find the *%s* metadata editor in WordPress?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "After opening the *%s* metadata editor, you can manually edit existing values or use the 'Generate with SOOZ' buttons to create SEO titles, meta descriptions, focus keyphrases, social metadata, and other active fields.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I generate or manually edit SEO titles, descriptions, and other metadata?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "Yes. You can also open the 'Pages', 'Posts', 'Products', or other content lists inside *%s*. From there, search or filter entries and open the metadata editor for the item you want to generate or edit.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I edit SEO metadata from the *%s* Pages, Posts, or Products lists?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( '*%s* supports the standard WordPress editor, Elementor, and BeTheme (Muffin-Builder / BeBuilder).', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Which WordPress editors and page builders are supported?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( '1. Open the page or post you want to edit in Elementor.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Click the settings button (cog icon) at the top of the Elementor header to reveal the page or post settings.', 'ai-for-seo' ) . '<br />';
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= sprintf( esc_html__( "3. In the settings section, click on the 'Show all SEO settings' button to open the *%s* metadata editor. Here, you can adjust the metadata manually or generate new metadata using AI-driven algorithms.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I edit or generate SEO metadata in Elementor with *%s*?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( '1. Open the page or post you want to edit in BeBuilder, the page-building tool within the BeTheme framework.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Click on the page-options-button on the left side of the BeBuilder navigation, then scroll down to the SEO section.', 'ai-for-seo' ) . '<br />';
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= sprintf( esc_html__( "3. Click on the 'Show all SEO settings' button within the SEO section to open the *%s* metadata editor. Here, you can access and manipulate the metadata using AI-driven algorithms.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I edit or generate SEO metadata in BeBuilder or BeTheme with *%s*?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: link to contact page */
			__( "We are always eager to hear feature requests, editor integration ideas, page builder compatibility requests, and SEO workflow feedback. Feel free to <a href='%s' target='_blank'>contact us</a> with your ideas. We welcome messages in any language.", 'ai-for-seo' ),
			esc_url( AI4SEO_OFFICIAL_CONTACT_URL )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can you add support for another editor, page builder, or SEO feature?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === METADATA & SEARCH SNIPPETS ============================================================= \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Metadata & Search Snippets', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content  = __( 'Add placeholders directly to the prefix or suffix fields under Settings > Metadata or Settings > Media Attributes. Supported placeholders are case-insensitive.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '<strong>{WEBSITE_URL}</strong> - Website URL: Inserts the website URL without a trailing slash.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{WEBSITE_NAME}</strong> - Website name: Inserts the WordPress site name.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{POST_ID}</strong> - Entry ID: Inserts the current post, page, product, or attachment ID.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{PRODUCT_NAME}</strong> - Product name: Inserts the WooCommerce product title for product metadata.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{PRODUCT_PRICE}</strong> - Product price: Inserts the WooCommerce product price for product metadata when a price is available.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{FILE_NAME}</strong> - File name: Inserts the attachment file name without the extension.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{FILE_TYPE}</strong> - File type: Inserts the attachment file extension, such as jpg, png, webp, or avif.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{FILE_SIZE}</strong> - File size: Inserts the attachment file size in kilobytes.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '<strong>{IMAGE_DIMENSIONS}</strong> - Image dimensions: Inserts the image width and height in pixels, formatted like 1200x800.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'The plugin replaces placeholders automatically when meta tags are injected or when attachment attributes are saved.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Which placeholders can I use in metadata, media prefixes, or suffixes?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( "Go to Settings > Metadata > Include product price in metadata (available when WooCommerce is active) and choose between 'Never', 'Fixed', or 'Dynamic'. 'Never' omits pricing, 'Fixed' stores the current product price directly in generated SEO titles and descriptions, and 'Dynamic' inserts a placeholder that is replaced with the live price during frontend rendering.", 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I include WooCommerce product prices in SEO titles and descriptions?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Open Settings > Metadata and adjust the metadata prompt sliders. Use "Metadata Tone Variant" to make SEO titles and descriptions more factual or more expressive, "CTA / Commercial Tone" to control how promotional the copy feels, "SEO Keyword Intensity" and "Focus Keyphrase Influence" to control keyword usage, "Social Metadata Variation" to make social titles and descriptions less repetitive, "Website / Brand Context Influence" to include more brand context, and "Existing Values Reference Strength" to decide how closely new generations should follow existing values.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I make generated SEO titles and meta descriptions match my brand voice?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		// Build FAQ limits from enforced constants so help copy cannot drift from validation and tooltip values.
		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s: Free-plan character limit. %2$s: Subscription character limit. */
			__( 'Use broad instructions only for rules that should apply everywhere, and use specific instructions for exceptions.<br><br>Global Custom Instructions affect future generations in general. Metadata Custom Instructions affect metadata only. Media Attribute Custom Instructions affect image attributes only. Post-type or entry-specific custom instructions are best for rules that apply only to selected posts, pages, products, or media files.<br><br>Custom instructions can guide wording, tone, terminology, emphasis, calls to action, and additional owner-supplied details. They cannot change generated fields, JSON or technical formats, a fixed generation language, configured prefix/suffix behavior, official character, word, sentence, or item limits, storage caps, or safety rules. Requests for extra, repeated, padded, or unlimited output are ignored while compatible parts still apply. Instruction text is limited to %1$s characters on Free or %2$s characters with an active subscription.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_CUSTOM_INSTRUCTIONS_FREE_LENGTH_LIMIT ) ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_CUSTOM_INSTRUCTIONS_SUBSCRIPTION_LENGTH_LIMIT ) )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Where should I add Custom Instructions: global, metadata, media, post type, or entry?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'The focus keyphrase helps connect the SEO title, meta description, keywords, and social metadata around the same search intent. If an entry already has a title or description but no focus keyphrase, the editor shows a heads-up because future generations may be less consistent. Use "Generate & Overwrite" if you want the keyphrase and related metadata fields to be generated together, or add a focus keyphrase manually before generating single fields.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Why does *%s* warn me about a missing focus keyphrase?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Open the Metadata Editor or Media Attributes Editor and look under the field label. Source hints show whether a value was generated by *%s*, imported from a supported SEO plugin, or changed after it was generated or imported. This helps you decide whether a title, meta description, alt text, or other field is safe to overwrite or should be reviewed manually first.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How can I tell whether a title, meta description, or alt text was generated, imported, or edited?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Some fields are hidden when the related meta tag or media attribute is inactive in the plugin settings. The editor shows a notice when inactive fields exist and links you directly to the settings where you can activate them again. Activating a field makes it available for editing and generation, but it does not automatically overwrite existing values until you generate, save, or queue that field.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why are some meta tags or media attribute fields missing from the editor?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			esc_html__( 'Open Settings > Metadata and use "Active Meta Tags" to choose which SEO fields *%1$s* may output in the frontend header. Then use "Meta Tag Output Mode" to decide how *%2$s* handles existing tags from themes or SEO plugins. This lets you control SEO title, meta description, Open Graph, Twitter Card, and other tags, and helps avoid duplicate frontend meta tags when another SEO plugin is active.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I control which meta tags *%s* outputs on the frontend?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === IMAGES & MEDIA ATTRIBUTES ============================================================== \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Images & Media Attributes', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content = __( 'Image attributes such as alt text, image titles, captions, and descriptions help search engines understand your media and can improve image search visibility. Alt text also supports accessibility when images are shown to visitors who use assistive technologies.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why should I add alt text, image titles, captions, and descriptions?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			esc_html__( 'Generated alt text and image attributes are strongest when *%1$s* can understand where and how an image is used. *%2$s* checks common sources such as featured images, attached media, WooCommerce variation images, galleries, local image URLs, and media stored by page builders or custom fields. If an image still receives generic text, check whether it is actually used on a page or product, open the Related Media view for the related entry, and regenerate the image attributes after the context is available.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why is alt text or image attribute generation more accurate for some images?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Open Settings > Media Attributes and adjust the media attribute prompt sliders. Reduce "SEO Keyword Intensity" if alt text feels keyword-heavy or keyword-stuffed, reduce "File Name Influence" if filenames add unwanted product or brand terms, adjust "Recognizable Entity Inclusion" if names are included too often, and use "Surrounding / Page Context Influence" when the page or product context should matter more than the isolated image.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I fix image attributes that sound generic, repetitive, or keyword-stuffed?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( '*%s* first tries to use the image URL. If the URL cannot be accessed, for example because of a CDN, firewall, signed URL, hotlink protection, or private media path, the plugin can retry with direct image data. If generation still fails, go to Settings > Show Advanced Settings > Troubleshooting and set "Image Upload Method" to "Data", then retry the generation.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'What happens if *%s* cannot access an image URL from my CDN, firewall, or host?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		// === FAQ: Image Upload Method = Data ====================================== \\

		$ai4seo_this_accordion_content  = __( 'If alt text, image title, caption, or media attribute generation fails because the image URL cannot be fetched, switch the upload method:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Settings > Show Advanced Settings > Troubleshooting. Set "Image Upload Method" to "Data".', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Save settings and retry generation (Alt Text, Title, Caption).', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. Why this helps: Some CDNs, firewalls, hosts, Cloudflare rules, signed URLs, hotlink protection, or private media paths block direct URL fetching. "Data" sends the image bytes instead of a public URL and is often more reliable.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '4. If issues persist, clear all caches and confirm the image is a real &lt;img&gt; tag, not a CSS background.', 'ai-for-seo' );

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'Alt text or image generation fails. Should I switch Image Upload Method to Data?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "If alt text is saved in the Media Library but missing on frontend images, your theme, page builder, lazy-load plugin, or gallery plugin may not output the stored alt attribute. Enable 'Alt Text Injection' in the plugin settings so *%s* can inject alt text during rendering.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why is alt text missing on my frontend images even though it is saved?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		// === FAQ: Alt Text Injection not visible ================================== \\

		$ai4seo_this_accordion_content  = __( 'If generated alt text is saved but does not appear on the frontend, front page, product page, or page-builder output, try:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Settings > Show Advanced Settings > Troubleshooting. Enable "Alt Text Injection", save, then check again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Optionally enable "Image Title Injection" to add a tooltip on hover.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. Clear caches (plugin/theme cache, page cache, CDN) so updated attributes render on cached pages.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '4. Ensure images are real &lt;img&gt; tags. Background images set via CSS cannot have alt text.', 'ai-for-seo' );

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'I do not see generated alt text on the frontend. How do I make it appear?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "The 'Alt Text Injection' setting helps when themes, page builders, lazy-load plugins, or galleries do not output alt text stored in the database. When enabled, *%s* injects alt text directly while the page is rendered, improving frontend accessibility and image SEO. It is disabled by default and can be found in the advanced plugin settings.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'What is Alt Text Injection and when should I use it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "Yes, *%s* lets you decide which media attributes, such as alt text, image title, caption, and description, should be generated. Configure them in the 'Active Media Attributes' section of the settings to match your image SEO and accessibility needs.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I choose which image attributes *%s* generates, such as alt text, title, caption, or description?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Open the Metadata Editor for a post, page, or product and click "Related Media". The modal shows images *%s* can connect to that entry, including featured images, attached media, WooCommerce variation images, galleries, local image URLs, and media found in supported builder or custom-field data. If a scan is partial, review the page manually as well because some custom layouts may hide image references in a way that cannot be scanned safely.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I find related images *%s* detected for a page, post, or product?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Yes. Use the Related Media workflow or the related-image bulk actions. From a post, page, or product list you can add all related images to the media attribute queue, add them in force mode, or remove pending related images from the queue. This is useful for product galleries, featured images, WooCommerce variation images, and page-builder images connected to one source entry.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can I bulk-generate alt text and image attributes for all related images?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'To import all images from the NextGen Gallery into *%s*, click the Import button in the Media section. Once imported, you can generate alt text, titles, captions, and descriptions for these images using the plugin. All changes will automatically sync with the NextGen Gallery plugin.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I generate alt text and media attributes for NextGen Gallery images?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === SEO AUTOPILOT & QUEUE MANAGEMENT ======================================================= \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'SEO Autopilot & Queue Management', 'ai-for-seo' ) . '</h3>';

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "Use SEO Autopilot to bulk-generate metadata by selecting the desired content type within the 'Set up SEO Autopilot' modal. Open it from the 'SEO Autopilot (Bulk Generation)' section on the *%s* dashboard.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I use SEO Autopilot to bulk-generate metadata?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "Use SEO Autopilot to bulk-generate image alt text, titles, captions, and descriptions by selecting 'Media files' within the 'Set up SEO Autopilot' modal. Open it from the 'SEO Autopilot (Bulk Generation)' section on the *%s* dashboard.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I use SEO Autopilot to bulk-generate alt text and media attributes?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			esc_html__( 'Yes, simply deactivate the checkbox next to the desired content type within the "Set up SEO Autopilot" modal. This modal can be accessed by clicking the "Set up SEO Autopilot"-button within the "SEO Autopilot (Bulk Generation)"-section on the *%1$s* dashboard of your WordPress website. If you would like to disable the automation for every content type entirely you can click the "Stop SEO Autopilot" button instead.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I turn off or stop the SEO Autopilot for specific content types?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes. Open the "Set up SEO Autopilot" modal and change "Auto Queue Entries" to "Only process manually queued entries". SEO Autopilot can stay active, but it will only process entries that you manually add to the queue from *%s* lists, supported WordPress lists, or available queue buttons.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I stop SEO Autopilot from automatically adding entries to the queue?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( __( 'Yes! You can decide exactly how the SEO Autopilot behaves. By default, the plugin automatically generates metadata and media attributes for both new and existing content. However, you can customize this behavior using three options:<br>• New and existing entries<br>• New entries only<br>• Existing entries only<br><br>You can change these settings anytime in the <strong>Set up SEO Autopilot</strong> modal. Just click the <strong>Set up SEO Autopilot</strong> button in the <strong>SEO Autopilot (Bulk Generation)</strong> section of your *%s* dashboard.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can SEO Autopilot generate metadata for new content only, existing content only, or both?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Not by default. SEO Autopilot keeps existing SEO titles, meta descriptions, focus keyphrases, meta tags, and media attributes unless you enable the related overwrite options in the settings.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Will SEO Autopilot overwrite existing SEO titles, descriptions, or media attributes?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			__( '"Add to queue (safe)" queues only entries that *%1$s* currently considers applicable, such as missing, failed, or manually excluded entries. It skips hidden, already queued, processing, and complete entries.<br><br>"Add to queue (force)" queues selected valid entries in force mode so saved values can be regenerated even when data already exists. Hidden or currently processing entries are still skipped. Use the force option only when you intentionally want *%2$s* to replace existing generated or saved values.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'What is the difference between Add to queue (safe) and Add to queue (force)?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( '"Hidden" means the entry is removed from *%s* lists and automatic queueing until you show it again. "Waiting" means the entry is applicable but has not been queued yet, often because Auto Queue is disabled or the queue is waiting for the next run. "Queued" means it is pending generation. "Processing" means SEO Autopilot is working on it now. "Failed" means the last generation attempt did not complete and can usually be retried.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'What do Hidden, Waiting, Queued, Processing, and Failed queue statuses mean?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( __( 'Yes. Enable "Native WordPress bulk actions" under Settings > General to show *%s* bulk actions in native WordPress post, page, product, and Media Library tables. When this setting is off, the same queue controls remain available inside the plugin lists. Actions that require extra input, such as setting custom instructions, may open a modal before changes are applied.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I use *%s* bulk actions in normal WordPress Posts, Pages, Products, and Media Library screens?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( __( 'Use the "Hide entry" bulk action in *%s* lists. Hidden entries are removed from plugin lists and automatic queueing, but their saved metadata, synced SEO plugin data, media attributes, and generated-data records are not deleted. Use the hidden filter and "Show entry" when you want the entries to appear again.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I hide entries from *%s* lists without deleting SEO data?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Use "Exclude from Auto Queue feature" when the entry should stay visible in *%s* lists but should not be added automatically by Auto Queue. This is useful for pages, products, or images that need manual review. Use "Hide entry" when the entry should disappear from the plugin list view as well. Both options leave saved SEO data and media attributes untouched.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'When should I exclude an entry from Auto Queue instead of hiding it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: plugin name */
			esc_html__( 'Use "Mark as not generated" when you want *%s* to forget that selected entries were already generated, while keeping saved metadata, synced third-party SEO plugin values, and saved media fields unchanged. Use "Delete SOOZ metadata" only when you want to remove active plugin metadata saved for selected posts, pages, or products. This does not delete third-party SEO plugin data or media library fields, so review synced data separately if needed.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'If metadata was generated with the wrong settings, should I mark the affected entries as not generated, or delete the plugin metadata using the bulk actions?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "You can find the 'Retry all failed' button in content lists such as Pages, Posts, Products, or Media and on your *%s* dashboard. It retries failed metadata and media attribute generations in one action. The button only appears after at least one generation error has occurred.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I retry failed metadata or media attribute generations?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( "We use WordPress's internal task scheduler to manage automatic generation efficiently. This helps prevent overloading your server with too many simultaneous tasks.", 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( "If you see a 'Pending' status in the 'SEO coverage' column, it means one of two things:", 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. The plugin is waiting for the next scheduled task to run (typically within 1-5 minutes).', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. The plugin is currently generating data for other entries.', 'ai-for-seo' ) . '<br /><br />';
		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= sprintf( esc_html__( 'You can check the *%s* dashboard to see if any generation is in progress for other items. If many items are pending, it may take longer to process them all.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'Rest assured, the plugin will automatically generate data for all pending items over time. If you need immediate results, you can use the manual generation option for specific items.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why is automatic metadata or media attribute generation pending?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'By default, the SEO Autopilot skips entries with a complete set of metadata or media attributes, assuming they are optimized.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'If you want to regenerate metadata or media attributes even for entries that are marked as complete:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'Enable "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Metadata section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( 'Enable "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Media Attributes section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( 'You can find these options in the plugin settings. This will force the SEO Autopilot to overwrite all existing metadata and media attributes, even if they are already set.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why did SEO Autopilot skip complete entries or mark them already completed?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === INTEGRATIONS, LANGUAGES & SITE SCOPE =================================================== \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Integrations, Languages & Site Scope', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content = sprintf(
		/* translators: %1$s plugin name, %2$s plugin name */
			esc_html__( '*%1$s* is compatible with major SEO plugins. Metadata generated by *%2$s* can be synchronized with Yoast SEO, Rank Math, All in One SEO, SEOPress, Slim SEO, SEO Simple Pack, Squirrly SEO, and The SEO Framework, so your SEO title, meta description, and other fields can stay aligned with your preferred SEO tool.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I use *%s* with Yoast SEO, Rank Math, or another SEO plugin?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( __( "To enable synchronization with another SEO plugin:<br><br>1. Navigate to the plugin settings and go to the 'Sync *%s* Changes' section.<br>2. Check the box next to Yoast SEO, Rank Math, All in One SEO, or your desired SEO plugin to activate syncing.<br><br>If you're using SEO Autopilot (Bulk Generation), make sure the relevant meta tags are enabled in the 'Overwrite Existing Metadata (SEO Autopilot Only)' section if you want to replace outdated data within your existing SEO plugin.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );

		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I sync *%s* metadata with Yoast SEO, Rank Math, or another SEO plugin?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			__( 'Changes made later in Yoast SEO, Rank Math, or another third-party SEO plugin may not be visible because *%1$s* handles the frontend output of your meta tags.<br><br>You have two options:<br>1. Apply your updates in the *%2$s* metadata editor and let them sync to your third-party SEO plugin.<br>2. Go to Settings > Show Advanced Settings > Meta Tag Output Mode and set it to "Complementary".', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why are changes in Yoast, Rank Math, or another SEO plugin not showing on the frontend?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Open *%s* > Settings and locate the "Active Post Types" checkboxes in the Metadata section. Uncheck any post types you want to hide. The plugin keeps their existing metadata untouched but removes them from the dashboard, menu and SEO Autopilot queues until you re-enable them.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I hide post types from the *%s* dashboard and SEO Autopilot?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes. Open *%s* > Settings and use the checkboxes "Active Authors" in the Metadata section or "Active Media Authors" in the Media Attributes section. Uncheck any authors you want to exclude. Their existing metadata or media attributes stay untouched, but their posts or media files are removed from the dashboard and the SEO Autopilot until you enable them again.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can I include or exclude pages, posts, products, or media by author?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes. Open *%s* > Settings and use the "Active Categories (taxonomy terms)" checkboxes in the Metadata section. Uncheck any categories you want to exclude. Their existing metadata stays untouched. By default, entries remain included as long as they still have at least one active category assigned, and you can enable the stricter option (advanced setting) to exclude an entry as soon as one disabled category is assigned.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can I include or exclude posts, products, or other content by category or taxonomy term?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Yes, the plugin supports multi-language websites and WPML. For media attributes, the Automatic language setting analyzes the image usage context first. If the context is unavailable or unclear, it falls back to the WPML language when available, then to the system language.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Does *%s* support WPML and multilingual websites?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes. If WPML is active, use the "Active Languages" checkboxes in Settings > Metadata and Settings > Media Attributes. Uncheck a language if entries in that language should be excluded from *%s* dashboards and SEO Autopilot for metadata or media attributes. Existing saved values are not deleted, and newly detected WPML languages stay active by default.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I exclude one WPML language from SEO Autopilot or *%s* lists?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === CREDITS, PLANS & BILLING =============================================================== \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Credits, Plans & Billing', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s: daily free credits, %2$s: free plan credits */
			__( 'The free plan renews on a daily basis. You will receive %1$s free Credits every day if your balance falls below %2$s Credits, allowing you to continue using the basic features of the plugin at no cost.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_DAILY_FREE_CREDITS_AMOUNT ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Do free plan Credits renew every day?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: free plan credits threshold */
			__( "You don't need to do anything—they'll be automatically added to your account every day if your balance falls below %s Credits. Just make sure the plugin remains active on your website.", 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How do I receive the free daily Credits?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: free plan credits threshold */
			__( "Yes, you'll continue to receive the free daily Credits even if you're on a paid subscription if your balance falls below %s Credits. These Credits are in addition to those included in your subscription.", 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Do paid plans still receive free daily Credits?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Credits are used when *%s* generates SEO data for posts, pages, products, media files, or other supported content. The amount of Credits required depends on the selected active meta tags, SEO fields, and media attributes, such as alt text, title, caption, or description.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '<br /><br />';
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How are Credits used for metadata, meta tags, alt text, and media attributes in *%s*?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s: free plan credits, %2$s: daily free credits, %3$s: free plan credits */
			__( 'Yes, the free plan provides you with %1$s Credits, allowing you to experiment with AI-generated SEO content without any cost. In addition, we provide you with %2$s free Credits every day if your balance falls below %3$s Credits. However it does not include advanced features available in paid subscriptions.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_DAILY_FREE_CREDITS_AMOUNT ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
		);
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Can I try *%s* without buying a subscription?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s: s plan credits, %2$s: s plan credits */
			'- ' . __( 'The Basic subscription grants you %1$s Credits per month, suitable for smaller websites or blogs. It covers approximately %2$s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings. Existing data can be used as reference for new data generations.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_s_plan_credits ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_s_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %1$s: m plan credits, %2$s: m plan credits */
			'- ' . __( 'The Pro subscription grants you %1$s Credits per month, designed for professionals who need more extensive SEO capabilities. It covers approximately %2$s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_m_plan_credits ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_m_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %1$s: l plan credits, %2$s: l plan credits */
			'- ' . __( 'The Premium subscription grants you %1$s Credits per month, ideal for businesses that require substantial SEO support and features. It covers approximately %2$s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings. It also features improved entity and celebrity face recognition in images.', 'ai-for-seo' ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_l_plan_credits ) ),
			esc_html( ai4seo_format_number_i18n( $ai4seo_l_plan_credits ) ),
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'How many Credits do Basic, Pro, and Premium subscriptions include?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'You should choose a subscription based on the number of Credits and additional features you need for generating SEO content:', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %s: free plan credits */
			'- ' . __( 'The free plan is great for experimentation. It covers approximately %s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings. However it does not include advanced features available in paid subscriptions.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_free_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %s: s plan credits */
			'- ' . __( 'The Basic subscription is suitable for smaller websites or blogs. It covers approximately %s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings. Existing data can be used as reference for new data generations.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_s_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %s: m plan credits */
			'- ' . __( 'The Pro subscription is designed for professionals who need more extensive SEO capabilities. It covers approximately %s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_m_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %s: l plan credits */
			'- ' . __( 'The Premium subscription is ideal for businesses that require substantial SEO support and features. It covers approximately %s focus keyphrases, meta tags, or image attributes per month, depending on your selected settings. It also features improved entity and celebrity face recognition in images.', 'ai-for-seo' ) . '<br />',
			esc_html( ai4seo_format_number_i18n( $ai4seo_l_plan_credits ) ),
		);
		$ai4seo_this_accordion_content .= sprintf(
			/* translators: %1$s: contact link, %2$s: discount percentage */
			'- ' . __( 'If you have a shop with many products or a large blog, we’d love to help! <a href="%1$s" target="_blank">Contact us now</a> to discuss a custom plan tailored specifically to your needs. Messages in any language are welcome! We are currently offering a %2$s discount on all custom plans.', 'ai-for-seo' ),
			esc_html( AI4SEO_OFFICIAL_CONTACT_URL ),
			esc_html( ai4seo_format_number_i18n( AI4SEO_CUSTOM_PLAN_DISCOUNT ) ) . '%',
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Which subscription plan should I choose for my website?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		// lost key content.
		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s lost license data text in bold, %3$s contact link. */
			__( 'First check the inbox and spam folder for the exact email used during Stripe checkout. Credentials are sent there automatically after a purchase or secure password rotation. Then go to *%1$s* > Account and click %2$s. If the message is unavailable, <a href="%3$s" target="_blank" rel="noopener">contact support</a> with the Stripe invoice or checkout details.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			'<strong>' . esc_html__( 'Lost your license data?', 'ai-for-seo' ) . '</strong>',
			esc_url( AI4SEO_OFFICIAL_CONTACT_URL )
		);
		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'What should I do if I lost my license key or license owner email?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		// login / customer portal.
		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: plugin name */
			__( 'We currently do not offer a separate customer portal. All account-related actions, including subscription and payment management, can be accessed through the WordPress plugin. You can find the relevant links in your WordPress admin area under "*%s*" > "Account".', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
		);

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				sprintf(
					/* translators: %s: plugin name */
					'> ' . esc_html__( 'Where can I manage my *%s* account, customer portal, subscription, or billing?', 'ai-for-seo' ),
					esc_html( AI4SEO_PLUGIN_NAME ),
				),
				$ai4seo_this_accordion_content
			)
		);

		// invoices.
		$ai4seo_this_accordion_content = sprintf(
			/* translators: %s: plugin name */
			__( 'A download link for your invoice is included in the confirmation email sent after each purchase. If you currently have an active subscription, you can also access and download your invoices through the WordPress plugin under "*%s*" > "Account" > "Manage Subscription / Invoices".', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
		);

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'Where can I download invoices or receipts?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		$ai4seo_this_accordion_content = __( 'No. Deactivating or uninstalling the plugin stops plugin functionality on your website, but it does not cancel an active subscription and it does not disable Pay-As-You-Go refills. Before deactivating, cancel or change subscriptions through the "Manage Subscription / Invoices" link and review Pay-As-You-Go under Account > Customize Pay-As-You-Go if automatic refills are enabled.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Does deactivating or uninstalling *%s* cancel my subscription or Pay-As-You-Go refills?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s cancel plan link */
			__( 'You can change or cancel your subscription at any time by going to the *%1$s* dashboard and clicking the <strong>Get more Credits</strong> button in the <strong>Credits</strong> section. In the <strong>How to get more Credits</strong> modal, click the <strong>Manage subscription</strong> button in the <strong>Subscription</strong> section. Alternatively, you can manage your subscription directly at this link: %2$s.', 'ai-for-seo' ) . '<br />',
			esc_html( AI4SEO_PLUGIN_NAME ),
			"<a target='_blank' href='" . esc_html( AI4SEO_OFFICIAL_WEBSITE ) . "/cancel-plan'>" . esc_html( AI4SEO_OFFICIAL_WEBSITE ) . '/cancel-plan</a>',
		);
		$ai4seo_this_accordion_content .= __( "You'll be redirected to Stripe, our invoice partner. Please follow the instructions on the Stripe website to change or cancel your subscription.", 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I change or cancel my *%s* subscription?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s stripe billing url */
			__( 'You can change or cancel your subscription at any time by going to the *%1$s* dashboard and clicking the <strong>Get more Credits</strong> button in the <strong>Credits</strong> section. In the <strong>How to get more Credits</strong> modal, click the <strong>Manage subscription</strong> button in the <strong>Subscription</strong> section. Alternatively, you can manage your subscription directly at this link: %2$s.', 'ai-for-seo' ) . '<br />',
			esc_html( AI4SEO_PLUGIN_NAME ),
			"<a target='_blank' href='" . esc_html( AI4SEO_STRIPE_BILLING_URL ) . "'>" . esc_html( AI4SEO_STRIPE_BILLING_URL ) . '</a>',
		);
		$ai4seo_this_accordion_content .= __( "You'll be redirected to Stripe, our invoice partner. Please follow the instructions on the Stripe website to change your subscription.", 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can I upgrade or downgrade my subscription plan?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'No, any unused Credits will roll over to the next month, allowing you to fully utilize your Credits without losing them at the end of each billing cycle.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Do unused Credits expire or roll over at the end of the month?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( "Yes, you can use your existing subscription, Credits Packs, and license key on another website. Enter the same license holder and license key on the new website under *%s* > Account. Linked websites share the same pool of Credits and various settings. You can also export and import settings between websites using the 'Export/Import Settings' button in the plugin settings.", 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Can I use my subscription, Credits Packs, or license key on another website?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";


		// === SETTINGS, TRANSFER & MAINTENANCE ======================================================= \\

		echo "<div class='ai4seo-faq-section-holder'>";
		// Headline.
		echo '<h3>' . esc_html__( 'Settings, Transfer & Maintenance', 'ai-for-seo' ) . '</h3>';

		$ai4seo_this_accordion_content = __( 'Use Export/Import Settings on the Settings page. Export downloads your current setup as a JSON file for backup or transfer. When importing, choose only the categories you want to import, such as Settings, Account Settings without credentials, SEO Autopilot Settings, or Get More Credits Settings, then click "Show Preview" before applying changes. Before importing on another website, check that the target site has the same relevant post types, languages, SEO plugins, and media settings so you do not accidentally apply settings meant for a different setup.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I export, import, or reuse *%s* settings on another website?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content = sprintf( esc_html__( 'Yes, you can use *%s* on staging, local, and development websites.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				/* translators: %s: plugin name */
				'> ' . sprintf( esc_html__( 'Can I use *%s* on a staging, local, or development site?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ),
				$ai4seo_this_accordion_content
			)
		);

		$ai4seo_this_accordion_content = __( 'If you want to revert the plugin settings to their default state: Use the Reset Settings option under Help > Troubleshooting > Reset Plugin. This will restore all settings to their original values but will not delete generated metadata or media attributes.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I reset *%s* settings to default values?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'If you want to fully remove all generated metadata and plugin data before uninstalling:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Help > Troubleshooting > Reset Plugin and select every checkbox.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Deactivate and uninstall the plugin.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'Note: Media Attributes and synced metadata (to third-party SEO plugins) cannot be removed or undone by the reset. You will need to manually update or remove them in their respective editors.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I uninstall *%s* and remove generated metadata or plugin data?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
			/* translators: %1$s plugin name, %2$s plugin name */
			esc_html__( 'It depends on how the generated data is stored and used. If *%1$s* synchronized generated metadata with a third-party SEO plugin like Yoast SEO, Rank Math, All in One SEO, SEOPress, or similar, then that metadata remains even after uninstalling *%2$s*. Saved image attributes, such as alt text, image titles, captions, and descriptions, are WordPress media fields and may also remain in the Media Library.', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		) . '<br /><br />';

		/* translators: %s: plugin name */
		$ai4seo_this_accordion_content .= sprintf( esc_html__( 'However, if synchronization was not enabled and *%s* was solely responsible for frontend meta tag output, those tags will no longer be generated once the plugin is deactivated or uninstalled.', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'Will generated metadata or image attributes disappear if I uninstall *%s*?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'If you generated metadata or media attributes with incorrect settings, follow these steps:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Use "Reset Generated Data" under Help > Troubleshooting > Reset Plugin to remove all generated metadata. Media files are marked as "not generated" and can be reprocessed again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. If the generated metadata was already synced with a third-party SEO plugin, consider enabling: "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Metadata section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. If you generated media attributes, consider enabling: "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Media Attributes section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( 'These settings will allow the plugin to regenerate and overwrite metadata and media attributes, even for entries that were previously marked as complete.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'I generated metadata or image attributes with the wrong settings. How do I fix it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( "The best setup is to use a cronjob outside the WordPress cron system (external cron job, either on your own server or through a third-party service) that runs every minute. This ensures that the SEO Autopilot and scheduled tasks run smoothly without being dependent on WordPress's internal cron system, which may not execute reliably on low-traffic sites.", 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'What cron job setup is recommended for SEO Autopilot and scheduled tasks?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'If possible, switch to a cron job system outside the WordPress cron system (external cron job, either on your own server or through a third-party service) that runs every minute. If this is not an option and you experience slow SEO Autopilot, consider increasing the SEO Autopilot Duration setting (Settings > Troubleshooting > SEO Autopilot Duration) to allow more processing time per batch.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'I use WordPress WP-Cron. What should I consider for SEO Autopilot?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'If your external cron job runs less frequently than once per minute, we recommend increasing the <strong>SEO Autopilot Duration</strong> setting (Settings > Troubleshooting > SEO Autopilot Duration). This allows each cycle to handle more tasks at once, compensating for the lower execution frequency.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'My external cron job runs every 2 minutes or less often. What should I change?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'Enable "Pause pages and media files analysis" under Help > Troubleshooting.', 'ai-for-seo' );
		$ai4seo_this_accordion_content .= '<br /><br />' . __( 'Switch the setting off after debugging to resume normal data analysis.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'The analysis of pages or media files is slowing down my site. How can I pause it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		// === FAQ: Generated metadata is not visible on the frontend =============================================== \\

		$ai4seo_this_accordion_content  = __( 'If generated SEO title, meta description, or other metadata does not appear on the frontend, in page source, or in a Google snippet preview, try the following steps:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. If you use a caching plugin (e.g., WP Rocket, W3 Total Cache, etc.), enable "Purge caches after saving metadata" under Settings > Show Advanced Settings > Frontend Cache Purge, then save settings and test again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Check whether another SEO plugin is controlling frontend meta tag output, or switch Meta Tag Output Mode if needed.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. If you do not use a caching plugin or the issue persists, please contact our support team for further assistance.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Generated metadata is not visible on the frontend or Google snippet. What can I do?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';
		echo '</div>';


		// === CONTACT FORM ========================================================================== \\

		echo "<div class='ai4seo-display-none ai4seo-help-content' id='ai4seo-help-contact'>";
		// Headline.
		echo "<h1 id='ai4seo-contact-section'>";
		echo esc_html__( 'Contact the makers of this plugin', 'ai-for-seo' );
		echo '</h1>';

		// Description.
		echo '<p>';
		echo esc_html__( 'You can contact us (Space Codes) directly through this page, and we will respond to the email address you provide.', 'ai-for-seo' );
		echo '</p>';

		// Form.
		echo "<form method='post' class='ai4seo-form ai4seo-contact-form' id='ai4seo-contact-form' name='ai4seo-contact-form'>";

		// Nonce.
		wp_nonce_field( 'ai4seo-contact-form', 'ai4seo-contact-form-nonce' );

		// Name.
		echo "<div class='ai4seo-form-item'>";
			echo "<label for='ai4seo-contact-form-name'>" . esc_html__( 'Your name', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				echo "<input type='text' class='ai4seo-editor-textfield' id='ai4seo-contact-form-name' name='ai4seo-contact-form-name' placeholder='" . esc_attr__( 'Your name', 'ai-for-seo' ) . "' value='' required />";
			echo '</div>';
		echo '</div>';

		// get default email address.
		$ai4seo_default_email = sanitize_email( ai4seo_get_option( 'admin_email' ) );

		// Email.
		echo "<div class='ai4seo-form-item'>";
			echo "<label for='ai4seo-contact-form-email'>" . esc_html__( 'Email', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				echo "<input type='email' class='ai4seo-editor-textfield' id='ai4seo-contact-form-email' name='ai4seo-contact-form-email' placeholder='" . esc_attr( 'example@page.com' ) . "' value='" . esc_attr( $ai4seo_default_email ) . "' required />";
			echo '</div>';
		echo '</div>';

		// Subject.
		echo "<div class='ai4seo-form-item'>";
			echo "<label for='ai4seo-contact-form-subject'>" . esc_html__( 'Subject', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				echo "<select class='ai4seo-editor-select' name='ai4seo-contact-form-subject' id='ai4seo-contact-form-subject' required>";
		foreach ( $ai4seo_contact_subject_options as $ai4seo_this_option_key => $ai4seo_this_option_value ) {
			echo "<option value='" . esc_attr( $ai4seo_this_option_key ) . "'>" . esc_attr( $ai4seo_this_option_value ) . '</option>';
		}
				echo '</select>';
			echo '</div>';
		echo '</div>';

		// Message.
		echo "<div class='ai4seo-form-item'>";
			echo "<label for='ai4seo-contact-form-message'>" . esc_html__( 'Message', 'ai-for-seo' ) . ':</label>';
			echo "<div class='ai4seo-form-item-input-wrapper'>";
				echo "<textarea class='ai4seo-editor-textarea' id='ai4seo-contact-form-message' name='ai4seo-contact-form-message' required></textarea>";
			echo '</div>';
		echo '</div>';

		// Submit button.
		submit_button( esc_attr__( 'Send us an email', 'ai-for-seo' ), 'primary', 'ai4seo-contact-form-submit' );
		echo '</form>';
		echo '</div>';


		if ( $ai4seo_can_administer_plugin ) {
		// ___________________________________________________________________________________________ \\
		// === TROUBLESHOOTING ====================================================================== \\
		// ¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯¯ \\

		$ai4seo_bulk_generation_duration               = (int) ai4seo_get_setting( AI4SEO_SETTING_BULK_GENERATION_DURATION );
		$ai4seo_disable_heavy_db_operations_input_name = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS );
		$ai4seo_disable_heavy_db_operations_value      = (bool) ai4seo_get_setting( AI4SEO_SETTING_DISABLE_HEAVY_DB_OPERATIONS );
		$ai4seo_debug_output_mode_input_name           = ai4seo_get_prefixed_input_name( AI4SEO_SETTING_DEBUG_OUTPUT_MODE );
		$ai4seo_debug_output_mode_value                = ai4seo_get_setting( AI4SEO_SETTING_DEBUG_OUTPUT_MODE );
		$ai4seo_debug_output_mode_options              = ai4seo_get_debug_output_mode_options();
		$ai4seo_debug_message_entries                  = get_option( AI4SEO_DEBUG_MESSAGES_OPTION_NAME, array() );

		// Normalize stored debug entries because older or corrupted option values should render as an empty log.
		if ( ! is_array( $ai4seo_debug_message_entries ) ) {
			$ai4seo_debug_message_entries = array();
		}

		// Troubleshooting visibility is decided before the browser paints to avoid refresh flicker after selecting this tile.
		echo "<div class='" . esc_attr( $ai4seo_help_sections['troubleshooting']['content_class'] ) . "' id='" . esc_attr( $ai4seo_help_sections['troubleshooting']['target_id'] ) . "'>";
		// Headline.
		echo "<h1 id='ai4seo-troubleshooting-section'>";
		echo esc_html__( 'Troubleshooting', 'ai-for-seo' );
		echo '</h1>';

		// WARNING.
		echo esc_html__( 'ATTENTION: The following tools are for advanced users only or if you are advised to use them by our support team.', 'ai-for-seo' );
		echo "<div class='ai4seo-gap'></div>";

		// Show the result of nonce-protected operations after redirect without replaying the submitted POST.
		if ( ! empty( $ai4seo_debug_operation_result['message'] ) ) {
			echo '<p><strong>' . esc_html( $ai4seo_debug_operation_result['message'] ) . '</strong></p>';
		}

		// === RESET PLUGIN DATA ===================================================================== \\

		echo "<div class='ai4seo-form ai4seo-unsaved-changes-warnings'>";
		echo "<div class='card ai4seo-form-section ai4seo-troubleshooting-settings-card'>";

		// Headline.
		echo '<h2>';
		echo '<i class="dashicons dashicons-image-rotate"></i>';
		echo esc_html__( 'Reset plugin data', 'ai-for-seo' );
		echo '</h2>';

		echo "<div class='ai4seo-form-item'>";
		echo "<label for='ai4seo-troubleshooting-reset-cache'>";
		echo esc_html__( 'Choose the data you want to reset:', 'ai-for-seo' );
		echo '</label>';

		echo "<div class='ai4seo-form-item-input-wrapper'>";

		// checkboxes for reset cache, resent environmental variables (you have to re-enter license data), reset plugin settings, reset generated data.

		// select all button.
		ai4seo_echo_wp_kses( ai4seo_get_select_all_checkbox( 'ai4seo-troubleshooting-reset-checkbox' ) );
		echo "<div class='ai4seo-medium-gap'></div>";

		// Reset cache.
		echo "<div class='ai4seo-form-multiple-inputs'>";
			echo "<input type='checkbox' id='ai4seo-troubleshooting-reset-cache' name='ai4seo-troubleshooting-reset-checkbox[]' />";
			echo "<label for='ai4seo-troubleshooting-reset-cache'>" . esc_html__( 'Reset cache', 'ai-for-seo' ) . '</label>';
		echo '</div>';

		// Reset notifications.
		echo "<div class='ai4seo-form-multiple-inputs'>";
			echo "<input type='checkbox' id='ai4seo-troubleshooting-reset-notifications' name='ai4seo-troubleshooting-reset-checkbox[]' />";
			echo "<label for='ai4seo-troubleshooting-reset-notifications'>" . esc_html__( 'Reset notifications', 'ai-for-seo' ) . '</label>';
			$ai4seo_notifications_tooltip = __( 'Dismissed notifications will be reset and eventually reappear in the notifications section of the plugin.', 'ai-for-seo' );
			ai4seo_echo_wp_kses( ai4seo_get_icon_with_tooltip_tag( $ai4seo_notifications_tooltip ) );
		echo '</div>';

		// Reset environmental variables.
		echo "<div class='ai4seo-form-multiple-inputs'>";
			echo "<input type='checkbox' id='ai4seo-troubleshooting-reset-env' name='ai4seo-troubleshooting-reset-checkbox[]' />";
			echo "<label for='ai4seo-troubleshooting-reset-env'>" . esc_html__( 'Reset environmental variables', 'ai-for-seo' ) . '</label>';
			$ai4seo_environmental_variables_tooltip = __( '<strong>This will reset all environmental variables.</strong><br>Use this option if you are advised to do so by our support team.<br><br><strong>Note:</strong> You will need to re-enter your license data after using this option.', 'ai-for-seo' );
			ai4seo_echo_wp_kses( ai4seo_get_icon_with_tooltip_tag( $ai4seo_environmental_variables_tooltip ) );
		echo '</div>';

		// Reset plugin settings.
		echo "<div class='ai4seo-form-multiple-inputs'>";
			echo "<input type='checkbox' id='ai4seo-troubleshooting-reset-settings' name='ai4seo-troubleshooting-reset-checkbox[]' />";
			echo "<label for='ai4seo-troubleshooting-reset-settings'>" . esc_html__( 'Reset plugin settings', 'ai-for-seo' ) . '</label>';
			$ai4seo_settings_tooltip = __( '<strong>This will reset all settings across the following pages:</strong><br>Settings Page, Account Page (license data will be kept), Pay-As-You-Go Settings, and Autopilot Settings.<br><br>To reset only the Settings Page, please use the "Restore Default" button on that page.', 'ai-for-seo' );
			ai4seo_echo_wp_kses( ai4seo_get_icon_with_tooltip_tag( $ai4seo_settings_tooltip ) );
		echo '</div>';

		// Reset generated data.
		if ( $ai4seo_generated_data_reset_post_type_counts ) {
			echo "<div class='ai4seo-form-multiple-inputs'>";
				echo '<span>' . esc_html__( 'Remove AI-generated data for:', 'ai-for-seo' ) . '</span>';
				ai4seo_echo_wp_kses( ai4seo_get_icon_with_tooltip_tag( $ai4seo_reset_generated_data_tooltip ) );
			echo '</div>';

			ai4seo_echo_wp_kses(
				ai4seo_get_generated_data_reset_post_type_checkboxes_html(
					'ai4seo-troubleshooting-reset-checkbox',
					'ai4seo-troubleshooting-reset-generated-data-post-type-checkbox',
					'ai4seo-troubleshooting-reset-generated-data',
					$ai4seo_generated_data_reset_post_type_counts
				)
			);
		}

		echo "<div class='ai4seo-medium-gap'></div>";

		// needed for extra information in the upcoming notification modal.
		echo "<div id='ai4seo-reset-generated-data-tooltip-text' class='ai4seo-display-none'>";
			ai4seo_echo_wp_kses( $ai4seo_reset_generated_data_tooltip );
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</div>';

		// Reset button.
		ai4seo_echo_wp_kses( ai4seo_get_submit_button_tag( esc_html__( 'Reset selected data', 'ai-for-seo' ), 'ai4seo-start-inactive ai4seo-lockable ai4seo-troubleshooting-reset-button', 'ai4seo_confirm_reset_plugin_data();' ) );
		echo '</div>';


		// === TROUBLESHOOTING FAQ ================================================================== \\

		echo '<h1>';
		echo esc_html__( 'Troubleshooting FAQ', 'ai-for-seo' );
		echo '</h1>';

		// Input for the search.
		echo "<div class='ai4seo-help-search-wrapper'>";
		ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'magnifying-glass' ) );
		echo "<input type='text' class='ai4seo-help-search' id='ai4seo-help-search-troubleshooting' placeholder='" . esc_attr__( 'Search F.A.Q. (enter min.3 characters)', 'ai-for-seo' ) . "' />";
		echo '</div>';

		// Container with the message that no entries could be found based on the search-input.
		echo "<div class='ai4seo-help-search-notice ai4seo-help-faq-search-notice ai4seo-display-none' id='ai4seo-help-troubleshooting-search-notice'>";
		echo '<p>' . esc_html__( 'No results could be found based on your search. Please try a different search term.', 'ai-for-seo' ) . '</p>';
		echo '</div>';

		echo "<div class='ai4seo-gap'></div>";

		echo "<div class='ai4seo-faq-section-holder'>";
		$ai4seo_this_accordion_content = __( 'If you want to revert the plugin settings to their default state: Use the Reset Settings option under Help > Troubleshooting > Reset Plugin. This will restore all settings to their original values but will not delete generated metadata or media attributes.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I reset *%s* settings to default values?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'If you want to fully remove all generated metadata and plugin data before uninstalling:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Help > Troubleshooting > Reset Plugin and select every checkbox.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Deactivate and uninstall the plugin.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'Note: Media Attributes and synced metadata (to third-party SEO plugins) cannot be removed or undone by the reset. You will need to manually update or remove them in their respective editors.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I uninstall *%s* and remove generated metadata or plugin data?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'If you generated metadata or media attributes with incorrect settings, follow these steps:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Use "Reset Generated Data" under Help > Troubleshooting > Reset Plugin to remove all generated metadata. Media files are marked as "not generated" and can be reprocessed again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. If the generated metadata was already synced with a third-party SEO plugin, consider enabling: "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Metadata section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. If you generated media attributes, consider enabling: "Include Complete Entries When Overwriting (SEO Autopilot Only)" in the Media Attributes section.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( 'These settings will allow the plugin to regenerate and overwrite metadata and media attributes, even for entries that were previously marked as complete.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'I generated metadata or image attributes with the wrong settings. How do I fix it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( 'Enable "Pause pages and media files analysis" under Help > Troubleshooting.', 'ai-for-seo' );
		$ai4seo_this_accordion_content .= '<br /><br />' . __( 'Switch the setting off after debugging to resume normal data analysis.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'The analysis of pages or media files is slowing down my site. How can I pause it?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = __( 'Use Help > Troubleshooting > Preferred Debug Output to send ai4seo_debug_message() diagnostics to the PHP/WP debug log, an uploads log file, the Debug Message Log below, an admin notice, or inline printouts. Turn output back to None when finished. You can also open the Debug Message Log at the bottom of this section to review stored entries (max. 1000), and enable WP_DEBUG_LOG if you prefer writing to wp-content/debug.log.', 'ai-for-seo' );
		/* translators: %s: plugin name */
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . sprintf( esc_html__( 'How do I debug unexpected plugin behavior or find the *%s* debug log?', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content  = __( "WordPress's internal cron system (WP-Cron) may not run reliably in every hosting setup, which can delay tasks such as SEO Autopilot jobs.", 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( 'Recommended setup:', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '1. Disable WP-Cron in your wp-config.php file (set DISABLE_WP_CRON to true).', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Create an external cron job on your server or hosting panel that calls wp-cron.php every minute.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. Save and test the cron job to confirm it runs regularly.', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= sprintf(
		/* translators: %s: YouTube tutorial link for setting up an external cron job */
			__( "For a detailed step-by-step walkthrough, watch this tutorial: <a href='%s' target='_blank' rel='noopener noreferrer'>How to set up an external WordPress cron job (YouTube)</a>.", 'ai-for-seo' ),
			esc_url( 'https://youtu.be/YzPup-6NgQQ?si=yzcAve9lpA3Aepap' )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'WP-Cron is not running reliably. How do I switch to an external WordPress cron job?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		// === FAQ: Alt Text Injection not visible ================================== \\

		$ai4seo_this_accordion_content  = __( 'If generated alt text is saved but does not appear on the frontend, front page, product page, or page-builder output, try:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Settings > Show Advanced Settings > Troubleshooting. Enable "Alt Text Injection", save, then check again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Optionally enable "Image Title Injection" to add a tooltip on hover.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. Clear caches (plugin/theme cache, page cache, CDN) so updated attributes render on cached pages.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '4. Ensure images are real &lt;img&gt; tags. Background images set via CSS cannot have alt text.', 'ai-for-seo' );

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'I do not see generated alt text on the frontend. How do I make it appear?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		// === FAQ: Image Upload Method = Data ====================================== \\

		$ai4seo_this_accordion_content  = __( 'If alt text, image title, caption, or media attribute generation fails because the image URL cannot be fetched, switch the upload method:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. Go to Settings > Show Advanced Settings > Troubleshooting. Set "Image Upload Method" to "Data".', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Save settings and retry generation (Alt Text, Title, Caption).', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. Why this helps: Some CDNs, firewalls, hosts, Cloudflare rules, signed URLs, hotlink protection, or private media paths block direct URL fetching. "Data" sends the image bytes instead of a public URL and is often more reliable.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '4. If issues persist, clear all caches and confirm the image is a real &lt;img&gt; tag, not a CSS background.', 'ai-for-seo' );

		ai4seo_echo_wp_kses(
			ai4seo_get_accordion_element(
				'> ' . esc_html__( 'Alt text or image generation fails. Should I switch Image Upload Method to Data?', 'ai-for-seo' ),
				$ai4seo_this_accordion_content
			)
		);

		// === FAQ: Generated metadata is not visible on the frontend =============================================== \\

		$ai4seo_this_accordion_content  = __( 'If generated SEO title, meta description, or other metadata does not appear on the frontend, in page source, or in a Google snippet preview, try the following steps:', 'ai-for-seo' ) . '<br /><br />';
		$ai4seo_this_accordion_content .= __( '1. If you use a caching plugin (e.g., WP Rocket, W3 Total Cache, etc.), enable "Purge caches after saving metadata" under Settings > Show Advanced Settings > Frontend Cache Purge, then save settings and test again.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '2. Check whether another SEO plugin is controlling frontend meta tag output, or switch Meta Tag Output Mode if needed.', 'ai-for-seo' ) . '<br />';
		$ai4seo_this_accordion_content .= __( '3. If you do not use a caching plugin or the issue persists, please contact our support team for further assistance.', 'ai-for-seo' );
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Generated metadata is not visible on the frontend or Google snippet. What can I do?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		$ai4seo_this_accordion_content = sprintf(
		/* translators: %1$s plugin name, %2$s plugin name */
			__( 'Changes made later in Yoast SEO, Rank Math, or another third-party SEO plugin may not be visible because *%1$s* handles the frontend output of your meta tags.<br><br>You have two options:<br>1. Apply your updates in the *%2$s* metadata editor and let them sync to your third-party SEO plugin.<br>2. Go to Settings > Show Advanced Settings > Meta Tag Output Mode and set it to "Complementary".', 'ai-for-seo' ),
			esc_html( AI4SEO_PLUGIN_NAME ),
			esc_html( AI4SEO_PLUGIN_NAME )
		);
		ai4seo_echo_wp_kses( ai4seo_get_accordion_element( '> ' . esc_html__( 'Why are changes in Yoast, Rank Math, or another SEO plugin not showing on the frontend?', 'ai-for-seo' ), $ai4seo_this_accordion_content ) );

		echo '</div>';

		echo "<div class='ai4seo-troubleshooting-section-gap'></div>";


		// === DEBUG SETTINGS ================================================================================= \\

		echo "<div class='ai4seo-form ai4seo-unsaved-changes-warnings'>";
		echo "<div class='card ai4seo-form-section ai4seo-troubleshooting-settings-card'>";
			// Headline.
			echo '<h2>';
				echo '<i class="dashicons dashicons-sos"></i>';
				echo esc_html__( 'Debug Settings', 'ai-for-seo' );
			echo '</h2>';

			echo "<div class='ai4seo-form-item'>";
				echo "<label for='" . esc_attr( $ai4seo_disable_heavy_db_operations_input_name ) . "'>";
					echo esc_html__( 'Deactivate heavy database operations:', 'ai-for-seo' );
				echo '</label>';

				echo "<div class='ai4seo-form-item-input-wrapper'>";
					echo "<label for='" . esc_attr( $ai4seo_disable_heavy_db_operations_input_name ) . "'>";
						echo "<input type='checkbox' id='" . esc_attr( $ai4seo_disable_heavy_db_operations_input_name ) . "' name='" . esc_attr( $ai4seo_disable_heavy_db_operations_input_name ) . "' value='1' class='ai4seo-single-checkbox ai4seo-lockable'" . ( $ai4seo_disable_heavy_db_operations_value ? " checked='checked'" : '' ) . '/> ';
						echo esc_html__( 'Pause pages and media files analysis', 'ai-for-seo' );
					echo '</label>';

					$ai4seo_disable_heavy_db_operations_description  = __( 'Temporarily stop heavy database refresh operations to reduce load. Coverage statistics and summaries will stop updating, and the plugin shows a warning until you turn this off again.', 'ai-for-seo' );
					$ai4seo_disable_heavy_db_operations_description .= '<br /><br />' . __( 'Only enable this when investigating issues and disable it afterwards so data stays up to date.', 'ai-for-seo' );

					echo "<p class='ai4seo-form-item-description'>";
						ai4seo_echo_wp_kses( $ai4seo_disable_heavy_db_operations_description );
					echo '</p>';
				echo '</div>';
			echo '</div>';

			// Divider.
			echo "<hr class='ai4seo-form-item-divider'>";

			echo "<div class='ai4seo-form-item'>";
				echo "<label for='" . esc_attr( $ai4seo_debug_output_mode_input_name ) . "'>";
					echo esc_html__( 'Preferred debug output:', 'ai-for-seo' );
				echo '</label>';

				echo "<div class='ai4seo-form-item-input-wrapper'>";
					echo "<select class='ai4seo-editor-select ai4seo-lockable' id='" . esc_attr( $ai4seo_debug_output_mode_input_name ) . "' name='" . esc_attr( $ai4seo_debug_output_mode_input_name ) . "'>";
		foreach ( $ai4seo_debug_output_mode_options as $ai4seo_this_output_mode_value => $ai4seo_this_output_mode_label ) {
			echo "<option value='" . esc_attr( $ai4seo_this_output_mode_value ) . "'" . selected( $ai4seo_debug_output_mode_value, $ai4seo_this_output_mode_value, false ) . '>' . esc_html( $ai4seo_this_output_mode_label ) . '</option>';
		}
					echo '</select>';

					$ai4seo_debug_output_mode_description = sprintf(
						/* translators: %1$s: plugin name, %2$s: maximum stored debug log entries */
						__( 'Choose how %1$s delivers diagnostics: disable output, send to the PHP/WP debug log (PHP default error_log or wp-content/debug.log when WP_DEBUG_LOG is enabled), append to <code>wp-content/uploads/ai-for-seo-debug.log</code>, store up to %2$s entries in the database, display an admin notice, or print a formatted block on the page. Notice and print outputs only appear for users who can manage this plugin.', 'ai-for-seo' ),
						esc_html( AI4SEO_PLUGIN_NAME ),
						esc_html( ai4seo_format_number_i18n( 1000 ) )
					);

					echo "<p class='ai4seo-form-item-description'>";
						ai4seo_echo_wp_kses( $ai4seo_debug_output_mode_description );
					echo '</p>';
					echo '</div>';
					echo '</div>';
					echo '</div>';

					ai4seo_echo_wp_kses( ai4seo_get_submit_button_tag( esc_html__( 'Save Debug Settings', 'ai-for-seo' ), 'ai4seo-start-inactive ai4seo-lockable', 'ai4seo_save_anything(jQuery(this), ai4seo_validate_troubleshooting_settings);' ) );
					echo '</div>';

					echo "<div class='ai4seo-troubleshooting-section-gap ai4seo-troubleshooting-section-gap-after-debug-settings'></div>";


					// === DEBUG OPERATIONS ================================================================================= \\

					// The debug operation form routes URL-triggered support actions through a nonce-backed dispatcher.
					echo "<form method='post' class='ai4seo-form ai4seo-debug-operation-form' id='ai4seo-debug-operation-form' action='" . esc_url( ai4seo_get_debug_operation_redirect_url() ) . "' data-ai4seo-prohibit-operation='prohibit-ai-for-seo'>";
					wp_nonce_field( 'ai4seo-debug-operation', 'ai4seo-debug-operation-nonce' );
					echo "<div class='card ai4seo-form-section ai4seo-troubleshooting-settings-card'>";
						echo '<h2>';
							echo '<i class="dashicons dashicons-admin-tools"></i>';
							echo esc_html__( 'Debug Operations', 'ai-for-seo' );
						echo '</h2>';

						echo "<div class='ai4seo-form-item'>";
							echo "<label for='ai4seo_debug_operation'>";
								echo esc_html__( 'Debug operation:', 'ai-for-seo' );
							echo '</label>';

							echo "<div class='ai4seo-form-item-input-wrapper'>";
								echo "<select class='ai4seo-editor-select ai4seo-lockable ai4seo-debug-operation-select' id='ai4seo_debug_operation' name='ai4seo_debug_operation'>";
									// Start with a server-rejected placeholder so loading the page never preselects an executable operation.
									echo "<option value=''>" . esc_html__( 'Select a debug operation', 'ai-for-seo' ) . '</option>';
					foreach ( $ai4seo_debug_operations as $ai4seo_this_debug_operation_key => $ai4seo_this_debug_operation ) {
						// Operation keys are server-owned; labels are the only registry field needed in the UI.
						echo "<option value='" . esc_attr( $ai4seo_this_debug_operation_key ) . "'>" . esc_html( $ai4seo_this_debug_operation['label'] ) . '</option>';
					}
								echo '</select>';
								echo "<p class='ai4seo-form-item-description'>";
									echo esc_html__( 'Choose a support-only debug operation. Required fields appear after selecting an operation.', 'ai-for-seo' );
								echo '</p>';
							echo '</div>';
						echo '</div>';

						echo "<hr class='ai4seo-form-item-divider'>";

						// Post-content operations share one post ID field and server-side validation helper.
						echo "<div class='ai4seo-form-item ai4seo-display-none ai4seo-debug-operation-field' data-ai4seo-operations='debug_condensed_post_content debug_combined_post_content' data-ai4seo-required='1'>";
							echo "<label for='ai4seo_debug_operation_post_id'>";
								echo esc_html__( 'Post ID:', 'ai-for-seo' );
							echo '</label>';
							echo "<div class='ai4seo-form-item-input-wrapper'>";
								echo "<input type='number' min='1' step='1' class='ai4seo-editor-textfield ai4seo-lockable' id='ai4seo_debug_operation_post_id' name='ai4seo_debug_operation_post_id' value='' />";
							echo '</div>';
						echo '</div>';

						// Generation summary options keep the two support-only modes while moving execution behind nonce-protected POST.
						echo "<div class='ai4seo-form-item ai4seo-display-none ai4seo-debug-operation-field' data-ai4seo-operations='read_generation_status_summary'>";
							echo "<label for='ai4seo_debug_generation_status_summary_totals_only'>";
								echo esc_html__( 'Generation status options:', 'ai-for-seo' );
							echo '</label>';
							echo "<div class='ai4seo-form-item-input-wrapper'>";
								echo "<label for='ai4seo_debug_generation_status_summary_totals_only'>";
									echo "<input type='checkbox' id='ai4seo_debug_generation_status_summary_totals_only' name='ai4seo_debug_generation_status_summary_totals_only' value='1' class='ai4seo-lockable' /> ";
									echo esc_html__( 'Totals only', 'ai-for-seo' );
								echo '</label>';
								echo '<br>';
								echo "<label for='ai4seo_debug_generation_status_summary_direct_database_call'>";
									echo "<input type='checkbox' id='ai4seo_debug_generation_status_summary_direct_database_call' name='ai4seo_debug_generation_status_summary_direct_database_call' value='1' class='ai4seo-lockable' /> ";
									echo esc_html__( 'Use direct database call', 'ai-for-seo' );
								echo '</label>';
							echo '</div>';
						echo '</div>';

						// The prohibit operation needs a URL because its tokenized redirect runs before the plugin finishes loading.
						echo "<div class='ai4seo-form-item ai4seo-display-none ai4seo-debug-operation-field' data-ai4seo-operations='prohibit-ai-for-seo' data-ai4seo-required='1'>";
							echo "<label for='ai4seo_debug_operation_target_url'>";
								echo esc_html__( 'Target URL:', 'ai-for-seo' );
							echo '</label>';
							echo "<div class='ai4seo-form-item-input-wrapper'>";
								echo "<input type='url' class='ai4seo-editor-textfield ai4seo-lockable' id='ai4seo_debug_operation_target_url' name='ai4seo_debug_operation_target_url' value='" . esc_attr( home_url( '/' ) ) . "' placeholder='" . esc_attr( home_url( '/' ) ) . "' />";
								echo "<p class='ai4seo-form-item-description'>";
									echo esc_html__( 'Only same-site URLs are accepted. This operation opens in a separate window so you can repeat tests from this page.', 'ai-for-seo' );
								echo '</p>';
							echo '</div>';
						echo '</div>';
					echo '</div>';

					echo "<button type='submit' name='ai4seo-debug-operation-submit' value='1' class='ai4seo-button ai4seo-lockable ai4seo-submit-button ai4seo-debug-operation-submit-button ai4seo-inactive-button' disabled='disabled'>";
						echo '<span>' . esc_html__( 'Run debug operation', 'ai-for-seo' ) . '</span>';
					echo '</button>';
					echo '</form>';

					echo "<div class='ai4seo-troubleshooting-section-gap ai4seo-troubleshooting-section-gap-after-action'></div>";


					// === DEBUG MESSAGE LOG ================================================================================= \\

					$ai4seo_debug_messages_container_for_clipboard = array();

					echo "<div class='card ai4seo-form-section ai4seo-troubleshooting-settings-card'>";
					echo '<h2>';
					echo '<i class="dashicons dashicons-media-text"></i>';
					echo esc_html__( 'Debug Message Log', 'ai-for-seo' );
					echo '</h2>';

					echo "<div id='ai4seo-debug-message-log-entries' class='ai4seo-debug-message-log-entries'>";
					if ( empty( $ai4seo_debug_message_entries ) ) {
						// Render the same empty state used after clearing the log through JavaScript.
						echo "<p class='ai4seo-debug-message-log-empty-message'>" . esc_html__( 'No debug messages recorded yet. Entries stored with "Store in the database" will appear here.', 'ai-for-seo' ) . '</p>';
					} else {
						// Keep the visual log and clipboard export in sync from the same stored entries.
						foreach ( $ai4seo_debug_message_entries as $ai4seo_this_debug_entry ) {
							$ai4seo_this_time       = $ai4seo_this_debug_entry['time'] && is_int( $ai4seo_this_debug_entry['time'] ) ? date_i18n( 'Y-m-d H:i:s', $ai4seo_this_debug_entry['time'] ) : '???';
							$ai4seo_this_code       = (int) ( $ai4seo_this_debug_entry['code'] ?? 2123126 );
							$ai4seo_this_message    = $ai4seo_this_debug_entry['message'] ?? esc_html__( 'No message', 'ai-for-seo' );
							$ai4seo_this_backtrace  = $ai4seo_this_debug_entry['backtrace'] ?? '';
							$ai4seo_no_br_backtrace = '';

							ai4seo_echo_wp_kses( '> <strong>[' . $ai4seo_this_time . ']</strong> ' . $ai4seo_this_message . ' Code: #' . $ai4seo_this_code );

							if ( $ai4seo_this_backtrace ) {
								ai4seo_echo_wp_kses( '<br />' . $ai4seo_this_backtrace );
								$ai4seo_no_br_backtrace = str_replace( array( "\n", "\r", '<br>', '<br />' ), ' > ', $ai4seo_this_backtrace );
							}

							$ai4seo_debug_messages_container_for_clipboard[] = '[' . $ai4seo_this_time . '] ' . $ai4seo_this_message . ' Code: #' . $ai4seo_this_code . ( $ai4seo_no_br_backtrace ? '. Backtrace: ' . esc_html( $ai4seo_no_br_backtrace ) : '' );

							echo "<div class='ai4seo-medium-gap'></div>";
						}
					}
					echo '</div>';
					echo '</div>';

					echo "<div class='ai4seo-buttons-wrapper ai4seo-help-debug-log-buttons'>";
					if ( ! empty( $ai4seo_debug_message_entries ) ) {

						// Offer clipboard and clear actions only when there is stored database output to act on.
						echo "<button type='button' class='ai4seo-button ai4seo-copy-to-clipboard ai4seo-debug-log-copy-button' data-clipboard-text='" . esc_attr( implode( "\n", $ai4seo_debug_messages_container_for_clipboard ) ) . "' title='" . esc_attr__( 'Copy debug log to clipboard', 'ai-for-seo' ) . "'>";
							ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'copy' ) . ' ' . esc_html__( 'Copy debug log to clipboard', 'ai-for-seo' ) );
							echo " <span class='ai4seo-debug-log-copy-to-clipboard-tooltip ai4seo-copied-to-clipboard'>👍 " . esc_html__( 'Copied!', 'ai-for-seo' ) . '</span>';
						echo '</button>';

						// Use the existing modal-confirmation flow before deleting database-backed debug entries.
						echo "<button type='button' class='ai4seo-button ai4seo-abort-button ai4seo-clear-debug-log-button ai4seo-lockable' title='" . esc_attr__( 'Clear debug log', 'ai-for-seo' ) . "'>";
							ai4seo_echo_wp_kses( ai4seo_get_svg_tag( 'circle-xmark' ) . ' ' . esc_html__( 'Clear log', 'ai-for-seo' ) );
						echo '</button>';

					}
					echo '</div>';

					echo "<div class='ai4seo-large-gap'></div>";


					echo '</div>';
		}


					// === USEFUL LINKS ========================================================================== \\

					// Useful links visibility is server-rendered from the same whitelisted Help section parameter.
					echo "<div class='" . esc_attr( $ai4seo_help_sections['links']['content_class'] ) . "' id='" . esc_attr( $ai4seo_help_sections['links']['target_id'] ) . "'>";
					// Headline.
					echo "<h1 id='ai4seo-links-section'>";
					echo esc_html__( 'Useful links', 'ai-for-seo' );
					echo '</h1>';

					// Plugin website.
					echo '<p>';
					echo "<i class='dashicons dashicons-admin-links'></i> ";
					echo '<b>' . esc_html__( 'Official plugin website', 'ai-for-seo' ) . '</b><br />';
					echo "<a href='" . esc_attr( AI4SEO_OFFICIAL_WEBSITE ) . "' target='_blank'>" . esc_html__( 'Check out the official plugin website to learn more about this plugin.', 'ai-for-seo' ) . '</a>';
					echo '</p>';

					// WordPress plugin-page.
					echo '<p>';
					echo "<i class='dashicons dashicons-admin-links'></i> ";
					echo '<b>' . esc_html__( 'WordPress plugin-page', 'ai-for-seo' ) . '</b><br />';
					echo "<a href='" . esc_attr( AI4SEO_PLUGINS_OFFICIAL_WORDPRESS_ORG_PAGE ) . "' target='_blank'>" . esc_html__( 'Check out this plugin directly on WordPress.org!', 'ai-for-seo' ) . '</a>';
					echo '</p>';

					// WordPress.org Support forum.
					echo '<p>';
					echo "<i class='dashicons dashicons-admin-links'></i> ";
					echo '<b>' . esc_html__( 'Support-Forum', 'ai-for-seo' ) . '</b><br />';
					echo "<a href='https://wordpress.org/support/plugin/ai-for-seo/' target='_blank'>" . esc_html__( 'Do you need assistance? Check out the official support-forum on WordPress.org!', 'ai-for-seo' ) . '</a>';
					echo '</p>';

					// Space Codes website.
					echo '<p>';
					echo "<i class='dashicons dashicons-admin-links'></i> ";
					/* translators: %s: plugin name */
					echo '<b>' . sprintf( esc_html__( 'The makers of *%s*', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</b><br />';
					/* translators: %s: plugin name */
					echo "<a href='https://spa.ce.codes' target='_blank'>" . sprintf( esc_html__( 'Do you want to learn more about the makers of *%s*? Then this is the right place for you!', 'ai-for-seo' ), esc_html( AI4SEO_PLUGIN_NAME ) ) . '</a>';
					echo '</p>';
					echo '</div>';

					// Close the hidden full Help page wrapper after every section has been rendered for debug-operation fallbacks.
					if ( $ai4seo_has_debug_operation_completion_page ) {
						echo '</div>';
					}
