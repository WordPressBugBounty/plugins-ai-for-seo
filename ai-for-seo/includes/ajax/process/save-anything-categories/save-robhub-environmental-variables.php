<?php
/**
 * Processes RobHub environmental-variable updates from save-anything requests.
 *
 * @since 2.0.0
 *
 * @package AI_For_SEO
 */

// Prevent direct execution because this processor depends on the loaded WordPress and plugin runtime.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Processes RobHub environmental-variable updates from sanitized save-anything data.
 *
 * @param array $upcoming_save_anything_updates Sanitized updates shared by the ordered save-anything processors.
 * @return WP_Error|null Error on failure, null on success or no-op.
 */
function ai4seo_process_save_anything_robhub_environmental_variables( array &$upcoming_save_anything_updates ) {
	// Leave payloads without communicator state fields to their owning save processors.
	if ( ! array_intersect( array_keys( $upcoming_save_anything_updates ), array_keys( ai4seo_robhub_api()::DEFAULT_ENVIRONMENTAL_VARIABLES ) ) ) {
		return null;
	}

	// Protect credentials and account state even when the processor is called outside the AJAX dispatcher.
	if ( ! ai4seo_can_administer_plugin() ) {
		return new WP_Error(
			11420725,
			esc_html__( 'Action blocked due to security reasons. Please refresh this page and try again.', 'ai-for-seo' )
		);
	}

	// Capture active credentials before persistence so a failed verification can restore the prior account state.
	$ai4seo_old_api_username = ai4seo_robhub_api()->get_api_username();
	$ai4seo_old_api_password = ai4seo_robhub_api()->get_api_password();

	// Maintain old/new history, a direct bulk-write map, and the complete submitted credential pair separately.
	$ai4seo_recent_robhub_environmental_variable_changes = array();
	$ai4seo_robhub_environmental_variable_updates        = array();
	$ai4seo_submitted_api_credentials                    = array();

	// Use the communicator's defaults registry as the allowlist for RobHub state accepted by save-anything.
	foreach ( array_keys( ai4seo_robhub_api()::DEFAULT_ENVIRONMENTAL_VARIABLES ) as $ai4seo_this_robhub_environmental_variable_name ) {
		// The shared save endpoint can contain unrelated categories, so process only present RobHub variables.
		if ( ! isset( $upcoming_save_anything_updates[ $ai4seo_this_robhub_environmental_variable_name ] ) ) {
			continue;
		}

		// Retain submitted credential fields even when unchanged because the paired field may still need verification.
		if ( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME === $ai4seo_this_robhub_environmental_variable_name ) {
			$ai4seo_submitted_api_credentials['username'] = $upcoming_save_anything_updates[ $ai4seo_this_robhub_environmental_variable_name ];
		} elseif ( ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD === $ai4seo_this_robhub_environmental_variable_name ) {
			$ai4seo_submitted_api_credentials['password'] = $upcoming_save_anything_updates[ $ai4seo_this_robhub_environmental_variable_name ];
		}

		$ai4seo_this_old_robhub_environmental_variable_value = ai4seo_robhub_api()->read_environmental_variable( $ai4seo_this_robhub_environmental_variable_name );
		$ai4seo_this_new_robhub_environmental_variable_value = $upcoming_save_anything_updates[ $ai4seo_this_robhub_environmental_variable_name ];

		// Retain loose comparison because persisted communicator scalars can differ only by PHP representation.
		// phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- Preserve existing save-anything equality semantics across scalar RobHub environmental-variable values.
		if ( $ai4seo_this_new_robhub_environmental_variable_value == $ai4seo_this_old_robhub_environmental_variable_value ) {
			continue;
		}

		// Reject the entire category before persistence when any submitted value violates the communicator contract.
		if ( ! ai4seo_robhub_api()->validate_environmental_variable_value( $ai4seo_this_robhub_environmental_variable_name, $ai4seo_this_new_robhub_environmental_variable_value ) ) {
			return new WP_Error(
				11419225,
				sprintf(
					/* translators: %s: Environmental variable name. */
					esc_html__( 'Invalid robhub environmental variable value for %s', 'ai-for-seo' ),
					$ai4seo_this_robhub_environmental_variable_name
				)
			);
		}

		// Keep old/new pairs for credential side effects while collecting the same new values for one bulk write.
		$ai4seo_recent_robhub_environmental_variable_changes[ $ai4seo_this_robhub_environmental_variable_name ] = array( $ai4seo_this_old_robhub_environmental_variable_value, $ai4seo_this_new_robhub_environmental_variable_value );
		$ai4seo_robhub_environmental_variable_updates[ $ai4seo_this_robhub_environmental_variable_name ]        = $ai4seo_this_new_robhub_environmental_variable_value;
	}

	// Reuse the communicator's bulk writer so all validated RobHub changes share its cache and persistence path.
	if ( $ai4seo_robhub_environmental_variable_updates ) {
		$ai4seo_robhub_update_result = ai4seo_robhub_api()->bulk_update_environmental_variables( $ai4seo_robhub_environmental_variable_updates );

		// Preserve diagnostics for registry mismatches that should be impossible after the allowlist pass above.
		if ( ! empty( $ai4seo_robhub_update_result['invalid_names'] ) ) {
			ai4seo_debug_message( 3317171025, 'RobHub: Bulk update skipped unknown names:' . implode( ', ', $ai4seo_robhub_update_result['invalid_names'] ) );
		}

		// Log rejected values separately so validation regressions remain distinguishable from unknown names.
		if ( ! empty( $ai4seo_robhub_update_result['invalid_values'] ) ) {
			ai4seo_debug_message( 3417171025, 'RobHub: Bulk update skipped invalid values for:' . implode( ', ', $ai4seo_robhub_update_result['invalid_values'] ) );
		}

		// Return persistence failures to the dispatcher while retaining the existing diagnostic message.
		if ( true !== $ai4seo_robhub_update_result['success'] ) {
			ai4seo_debug_message( 3517171025, 'RobHub: Bulk update failed to persist changes.' );
			return new WP_Error(
				3517171,
				esc_html__( 'Failed to update RobHub environmental variables.', 'ai-for-seo' )
			);
		}
	}

	// Resolve the credential keys once because both change detection and rollback use the same communicator fields.
	$ai4seo_robhub_api_username_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_USERNAME;
	$ai4seo_robhub_api_password_key = ai4seo_robhub_api()::ENVIRONMENTAL_VARIABLE_API_PASSWORD;
	$ai4seo_auth_data_was_locked    = ai4seo_robhub_api()->is_auth_data_locked();

	// Verify credentials only when the persisted username or password actually changed.
	if ( isset( $ai4seo_recent_robhub_environmental_variable_changes[ $ai4seo_robhub_api_username_key ] ) || isset( $ai4seo_recent_robhub_environmental_variable_changes[ $ai4seo_robhub_api_password_key ] ) ) {
		// Combine changed, submitted, and prior values so a one-field credential edit still tests a complete pair.
		$ai4seo_new_api_username = $ai4seo_recent_robhub_environmental_variable_changes[ $ai4seo_robhub_api_username_key ][1] ?? $ai4seo_submitted_api_credentials['username'] ?? $ai4seo_old_api_username;
		$ai4seo_new_api_password = $ai4seo_recent_robhub_environmental_variable_changes[ $ai4seo_robhub_api_password_key ][1] ?? $ai4seo_submitted_api_credentials['password'] ?? $ai4seo_old_api_password;

		// Complete credential pairs must be verified before the request can report a successful save.
		if ( $ai4seo_new_api_username && $ai4seo_new_api_password ) {
			// Temporarily lift the lock so the communicator can authenticate with the newly submitted pair.
			ai4seo_robhub_api()->set_auth_data_locked( false );
			ai4seo_robhub_api()->use_this_credentials( $ai4seo_new_api_username, $ai4seo_new_api_password );

			// Notify RobHub of the account transition using both old and candidate usernames for server-side reconciliation.
			$ai4seo_credentials_response = ai4seo_robhub_api()->call(
				'client/changed-api-user',
				array(
					'old-api-username' => $ai4seo_old_api_username,
					'new-api-username' => $ai4seo_new_api_username,
				)
			);

			// Successful verification invalidates account-derived caches and notices tied to the previous credentials.
			if ( ai4seo_robhub_api()->was_call_successful( $ai4seo_credentials_response ) ) {
				ai4seo_robhub_api()->reset_last_account_sync();
				ai4seo_remove_all_notifications();
			} else {
				// Restore a complete prior credential pair; otherwise reset the communicator to an unauthenticated state.
				if ( $ai4seo_old_api_username && $ai4seo_old_api_password ) {
					// Preserve the prior unlocked state before restoring credential values and account-derived caches.
					if ( ! $ai4seo_auth_data_was_locked ) {
						ai4seo_robhub_api()->set_auth_data_locked( false );
					}

					ai4seo_robhub_api()->update_environmental_variable( $ai4seo_robhub_api_username_key, $ai4seo_old_api_username );
					ai4seo_robhub_api()->update_environmental_variable( $ai4seo_robhub_api_password_key, $ai4seo_old_api_password );
					ai4seo_robhub_api()->use_this_credentials( $ai4seo_old_api_username, $ai4seo_old_api_password );
					ai4seo_robhub_api()->reset_last_account_sync();
					ai4seo_remove_all_notifications();
				} else {
					// A failed first connection has no complete prior account to restore.
					ai4seo_robhub_api()->invalidate_auth_data( false );
					ai4seo_remove_all_notifications();
				}

				// Surface the communicator's verification detail while retaining the established fallback message and code.
				$ai4seo_api_response_error_message = $ai4seo_credentials_response['message'] ?? esc_html__( 'Please try to reconnect account', 'ai-for-seo' );
				$ai4seo_credentials_error_message  = sprintf(
					/* translators: %s: API Error message. */
					esc_html__( 'Could not verify new credentials: %s', 'ai-for-seo' ),
					$ai4seo_api_response_error_message
				);

				return new WP_Error(
					391222324,
					$ai4seo_credentials_error_message
				);
			}
		} elseif ( $ai4seo_old_api_username || $ai4seo_old_api_password ) {
			// Clearing either part of existing credentials intentionally disconnects the current RobHub account.
			ai4seo_robhub_api()->invalidate_auth_data( false );
			ai4seo_remove_all_notifications();
		}
	}

	return null;
}
