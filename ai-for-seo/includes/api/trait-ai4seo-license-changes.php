<?php
/**
 * Durable manual license changes on the requesting installation.
 *
 * @package AI_For_SEO
 */

/** Implements manual license transition storage and transport. */
trait Ai4Seo_License_Changes {

	/**
	 * Prevent nested recovery while checking the server result.
	 *
	 * @var bool
	 */
	private bool $is_reconciling_license_change = false;

	/**
	 * Validate a selected name without changing the legacy login contract.
	 *
	 * @param mixed $name Proposed name.
	 * @return bool Whether selectable.
	 */
	public static function is_selectable_license_owner( $name ): bool {
		return is_string( $name ) && strlen( $name ) >= 5 && strlen( $name ) <= 48
			&& 1 === preg_match( '/\A[a-z0-9]+(?:[-_][a-z0-9]+)*\z/D', $name )
			&& 1 === preg_match( '/[a-z]/', $name )
			&& 1 !== preg_match( '/\A(?:admin|administrator|root|system|support|billing|api|localhost|robhub|sooz|ai4seo)(?:[-_]|\z)/D', $name );
	}

	/**
	 * Return a validated snapshot; unreadable recovery must never be overwritten.
	 *
	 * @return array Snapshot.
	 * @throws RuntimeException On unreadable recovery state.
	 */
	private function license_change_snapshot(): array {
		$snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_LICENSE_CHANGE_OPTION );
		if ( ! is_array( $snapshot ) ) {
			throw new RuntimeException( 'storage_error' );
		}
		if ( ! $snapshot['exists'] ) {
			return $snapshot;
		}
		$value = $snapshot['value'];
		if ( ! is_array( $value ) || 1 !== ( $value['version'] ?? null )
			|| ! $this->is_non_autoload_option_value( $snapshot['autoload'] )
			|| ! in_array( $value['kind'] ?? '', array( 'username', 'password' ), true )
			|| ! in_array( $value['phase'] ?? '', array( 'prepared', 'confirming' ), true )
			|| ! is_int( $value['recover_until'] ?? null )
			|| ( ! self::is_selectable_license_owner( $value['new_username'] ?? null ) && 'username' === ( $value['kind'] ?? '' ) ) ) {
			throw new RuntimeException( 'storage_error' );
		}
		foreach ( array( 'request_id', 'recovery_token', 'original_fingerprint', 'target_fingerprint' ) as $field ) {
			if ( ! is_string( $value[ $field ] ?? null ) || 1 !== preg_match( '/\A[a-f0-9]{64}\z/D', $value[ $field ] ) ) {
				throw new RuntimeException( 'storage_error' );
			}
		}
		if ( ! $this->is_non_empty_api_username_valid( $value['new_username'] ?? null )
			|| ! is_string( $value['new_password'] ?? null )
			|| ( 'password' === $value['kind'] && ! $this->is_api_password_rotation_candidate_valid( $value['new_password'] ) ) ) {
			throw new RuntimeException( 'storage_error' );
		}
		return $snapshot;
	}

	/** Existence alone is enough to keep new changes from hiding unresolved recovery. */
	public function has_pending_license_change(): bool {
		$snapshot = ai4seo_get_raw_option_snapshot( self::PENDING_LICENSE_CHANGE_OPTION );
		return ! is_array( $snapshot ) || $snapshot['exists'];
	}

	/**
	 * Bind a change to the persisted pair without copying the old key.
	 *
	 * @param array $values Persisted environmental values.
	 * @return string Fingerprint.
	 */
	private function license_credential_fingerprint( array $values ): string {
		return hash(
			'sha256',
			wp_json_encode(
				array(
					(string) $values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ],
					(string) $values[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ],
				)
			)
		);
	}

	/**
	 * Save and read back the candidate before transport.
	 *
	 * @param array $snapshot Expected state.
	 * @param array $value Replacement state.
	 * @throws RuntimeException On persistence failure.
	 */
	private function save_license_change( array $snapshot, array $value ): void {
		if ( true !== $this->license_change_storage_write( fn() => ai4seo_compare_and_swap_option_snapshot( self::PENDING_LICENSE_CHANGE_OPTION, $snapshot, $value, false ) ) ) {
			throw new RuntimeException( 'storage_error' );
		}
		$verified = $this->license_change_snapshot();
		if ( ! $verified['exists'] || $verified['value'] !== $value ) {
			throw new RuntimeException( 'storage_error' );
		}
	}

	/**
	 * Prepare locally before the first remote side effect.
	 *
	 * @param string $kind Username or password.
	 * @param string $new_username Proposed username.
	 * @return array Result.
	 * @throws RuntimeException Caught and converted to a closed API error.
	 */
	public function request_license_change( string $kind, string $new_username = '' ): array {
		if ( ! $this->acquire_api_password_rotation_lock() ) {
			return $this->respond_error( 'credential_change_conflict', 52101201 );
		}
		try {
			if ( ! in_array( $kind, array( 'username', 'password' ), true )
				|| ! $this->is_account_synced() || $this->is_auth_data_locked()
				|| ! $this->read_environmental_variable( self::ENVIRONMENTAL_VARIABLE_CREDENTIAL_CHANGES_SUPPORTED )
				|| $this->has_pending_api_password_rotation() || $this->has_api_password_rotation_recovery_intent() ) {
				throw new RuntimeException( 'unavailable' );
			}
			$values = $this->read_fresh_environmental_variables();
			if ( 'username' === $kind && $new_username === $values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ] ) {
				return array(
					'success' => true,
					'data'    => array( 'state' => 'unchanged' ),
				);
			}
			if ( 'username' === $kind && ! self::is_selectable_license_owner( $new_username ) ) {
				throw new RuntimeException( 'invalid_username_or_request' );
			}
			$snapshot = $this->license_change_snapshot();
			if ( $snapshot['exists'] && 'confirming' === $snapshot['value']['phase'] ) {
				throw new RuntimeException( 'conflict' );
			}
			$pending = array(
				'version'              => 1,
				'request_id'           => bin2hex( random_bytes( 32 ) ),
				'recovery_token'       => bin2hex( random_bytes( 32 ) ),
				'kind'                 => $kind,
				'phase'                => 'prepared',
				'original_fingerprint' => $this->license_credential_fingerprint( $values ),
				'new_username'         => 'username' === $kind ? $new_username : $values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ],
				'new_password'         => 'password' === $kind ? bin2hex( random_bytes( 24 ) ) : '',
				'recover_until'        => time() + DAY_IN_SECONDS,
			);
			$target  = $values;
			$target[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ] = $pending['new_username'];
			if ( 'password' === $kind ) {
				$target[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ] = $pending['new_password'];
			}
			$pending['target_fingerprint'] = $this->license_credential_fingerprint( $target );
			$this->save_license_change( $snapshot, $pending );
			$response = $this->call_with_isolated_client_credentials(
				'client/prepare-credential-change',
				array(
					'request_id'         => $pending['request_id'],
					'recovery_token'     => $pending['recovery_token'],
					'kind'               => $kind,
					'new_username'       => $pending['new_username'],
					'replacement_digest' => 'password' === $kind ? hash( 'sha256', $pending['new_password'] ) : '',
				),
				$values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ],
				$values[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
			);
			// A rejected resend did not supersede the previous code. Unknown outcomes retain the new candidate.
			if ( $snapshot['exists'] && ! $this->was_call_successful( $response )
				&& in_array( $response['message'] ?? '', array( 'credential_change_rate_limited', 'credential_change_name_unavailable', 'credential_change_unavailable', 'credential_change_checkout_unavailable', 'credential_change_invalid_request', 'credential_change_invalid_username_or_request' ), true ) ) {
				$current = $this->license_change_snapshot();
				if ( $current['exists'] && $current['value'] === $pending ) {
					$this->save_license_change( $current, $snapshot['value'] );
				}
			}
			return $this->license_change_response( $response, $pending );
		} catch ( Throwable $error ) {
			return $this->license_change_exception( $error );
		} finally {
			$this->release_api_password_rotation_lock();
		}
	}

	/**
	 * Confirm once; lost responses leave the candidate intact for a status check.
	 *
	 * @param string $code Confirmation code.
	 * @return array Result.
	 * @throws RuntimeException Caught and converted to a closed API error.
	 */
	public function confirm_license_change( string $code ): array {
		if ( 1 !== preg_match( '/\A[0-9]{8}\z/D', $code ) || ! $this->acquire_api_password_rotation_lock() ) {
			return $this->respond_error( 'credential_change_invalid_code', 52101201 );
		}
		try {
			$snapshot = $this->license_change_snapshot();
			if ( ! $snapshot['exists'] || $snapshot['value']['recover_until'] < time() ) {
				throw new RuntimeException( 'expired_or_used' );
			}
			$pending = $snapshot['value'];
			if ( 'confirming' === $pending['phase'] ) {
				return $this->get_license_change_status();
			}
			if ( $this->is_auth_data_locked() || $this->has_pending_api_password_rotation() || $this->has_api_password_rotation_recovery_intent() ) {
				throw new RuntimeException( 'unavailable' );
			}
			$values = $this->read_fresh_environmental_variables();
			if ( ! hash_equals( $pending['original_fingerprint'], $this->license_credential_fingerprint( $values ) ) ) {
				throw new RuntimeException( 'conflict' );
			}
			$pending['phase'] = 'confirming';
			$this->save_license_change( $snapshot, $pending );
			$response = $this->call_with_isolated_client_credentials(
				'client/confirm-credential-change',
				array(
					'request_id'        => $pending['request_id'],
					'recovery_token'    => $pending['recovery_token'],
					'confirmation_code' => $code,
					'new_api_password'  => $pending['new_password'],
				),
				$values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ],
				$values[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]
			);
			// Persist a definitive rejection before a status failure can hide it. Unknown outcomes
			// stay fenced because pending status cannot exclude a delayed confirmation request.
			if ( ! $this->was_call_successful( $response )
				&& in_array( $response['message'] ?? '', array( 'credential_change_invalid_code', 'credential_change_checkout_changed', 'credential_change_name_unavailable', 'credential_change_unavailable' ), true ) ) {
				$current = $this->license_change_snapshot();
				if ( $current['exists'] && $current['value'] === $pending ) {
					$pending['phase'] = 'prepared';
					$this->save_license_change( $current, $pending );
				}
			}
			$status = $this->get_license_change_status();
			if ( ! $this->was_call_successful( $status ) ) {
				return $status;
			}
			return $this->was_call_successful( $status ) && 'pending' !== ( $status['data']['state'] ?? '' ) ? $status : $this->license_change_response( $response, $pending );
		} catch ( Throwable $error ) {
			return $this->license_change_exception( $error );
		} finally {
			$this->release_api_password_rotation_lock();
		}
	}

	/**
	 * Status authority cannot return a key; the candidate exists only on this site.
	 *
	 * @return array Result.
	 * @throws RuntimeException Caught and converted to a closed API error.
	 */
	public function get_license_change_status(): array {
		if ( ! $this->acquire_api_password_rotation_lock() ) {
			return $this->respond_error( 'credential_change_conflict', 52101201 );
		}
		try {
			$snapshot = $this->license_change_snapshot();
			if ( ! $snapshot['exists'] ) {
				return array(
					'success' => true,
					'data'    => array( 'state' => 'missing' ),
				);
			}
			$pending = $snapshot['value'];
			if ( $pending['recover_until'] < time() ) {
				if ( true !== $this->license_change_storage_write( fn() => ai4seo_compare_and_delete_option_snapshot( self::PENDING_LICENSE_CHANGE_OPTION, $snapshot ) ) ) {
					throw new RuntimeException( 'storage_error' );
				}
				throw new RuntimeException( 'expired_or_used' );
			}
			$response = $this->call_with_isolated_client_credentials(
				'client/credential-change-status',
				array(
					'request_id'     => $pending['request_id'],
					'recovery_token' => $pending['recovery_token'],
				),
				'license-status',
				str_repeat( '0', 48 )
			);
			$response = $this->license_change_response( $response, $pending );
			if ( ! $this->was_call_successful( $response ) ) {
				return $response;
			}
			$state = $response['data']['state'];
			if ( 'missing' === $state && 'confirming' === $pending['phase'] ) {
				throw new RuntimeException( 'conflict' );
			}
			if ( 'completed' === $state ) {
				$this->promote_license_change( $pending );
			}
			if ( in_array( $state, array( 'completed', 'missing', 'expired', 'locked', 'superseded' ), true ) ) {
				if ( true !== $this->license_change_storage_write( fn() => ai4seo_compare_and_delete_option_snapshot( self::PENDING_LICENSE_CHANGE_OPTION, $snapshot ) ) ) {
					throw new RuntimeException( 'storage_error' );
				}
			}
			$response['data']['can_confirm'] = 'pending' === $state && 'prepared' === $pending['phase'];
			return $response;
		} catch ( Throwable $error ) {
			return $this->license_change_exception( $error );
		} finally {
			$this->release_api_password_rotation_lock();
		}
	}

	/**
	 * Merge only credential changes, preserving simultaneous unrelated updates.
	 *
	 * @param array $pending Confirmed operation.
	 * @throws RuntimeException On changed identity or storage failure.
	 */
	private function promote_license_change( array $pending ): void {
		for ( $attempt = 0; $attempt < 3; ++$attempt ) {
			$snapshot = ai4seo_get_raw_option_snapshot( $this->environmental_variables_option_name );
			if ( ! is_array( $snapshot ) || ! $snapshot['exists'] || ! is_array( $snapshot['value'] ) ) {
				throw new RuntimeException( 'storage_error' );
			}
			$values          = $this->normalize_environmental_variables( $snapshot['value'] );
			$password        = 'password' === $pending['kind'] ? $pending['new_password'] : $values[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ];
			$already_applied = hash_equals( $pending['target_fingerprint'], $this->license_credential_fingerprint( $values ) );
			if ( ! $already_applied && ! hash_equals( $pending['original_fingerprint'], $this->license_credential_fingerprint( $values ) ) ) {
				throw new RuntimeException( 'conflict' );
			}
			$values[ self::ENVIRONMENTAL_VARIABLE_API_USERNAME ]      = $pending['new_username'];
			$values[ self::ENVIRONMENTAL_VARIABLE_API_PASSWORD ]      = $password;
			$values[ self::ENVIRONMENTAL_VARIABLE_IS_AUTH_LOCKED ]    = false;
			$values[ self::ENVIRONMENTAL_VARIABLE_IS_ACCOUNT_SYNCED ] = false;
			$values[ self::ENVIRONMENTAL_VARIABLE_LAST_ACCOUNT_SYNC ] = 0;
			if ( true === $this->license_change_storage_write( fn() => $this->compare_and_swap_environmental_variables_snapshot( $snapshot, $values ) ) ) {
				$this->environmental_variables = $this->read_fresh_environmental_variables();
				$this->synchronize_runtime_credentials_from_environmental_variables();
				if ( $this->api_username === $pending['new_username'] && $this->api_password === $password ) {
					return;
				}
			}
		}
		throw new RuntimeException( 'storage_error' );
	}

	/**
	 * Strictly bound public data prevents malformed responses promoting candidates.
	 *
	 * @param mixed $response API response.
	 * @param array $pending Expected operation.
	 * @return array Closed public result.
	 */
	private function license_change_response( $response, array $pending ): array {
		if ( ! is_array( $response ) || ! $this->was_call_successful( $response ) ) {
			return is_array( $response ) ? $response : $this->respond_error( 'credential_change_storage_error', 52101201 );
		}
		$data = $response['data'] ?? null;
		if ( ! is_array( $data ) || ( $data['request_id'] ?? '' ) !== $pending['request_id']
			|| ! in_array( $data['state'] ?? '', array( 'pending', 'completed', 'missing', 'expired', 'locked', 'superseded' ), true ) ) {
			return $this->respond_error( 'credential_change_conflict', 52101201 );
		}
		return array(
			'success' => true,
			'data'    => array_merge(
				array_intersect_key( $data, array_flip( array( 'state', 'masked_email', 'email_status', 'expires_at' ) ) ),
				array(
					'kind'         => $pending['kind'],
					'new_username' => $pending['new_username'],
					'can_confirm'  => 'prepared' === $pending['phase'],
					'resend_after' => max( 0, min( 60, (int) ( $data['resend_after'] ?? 60 ) ) ),
				)
			),
		);
	}

	/**
	 * Convert only expected local failures; exception text may contain credentials.
	 *
	 * @param Throwable $error Internal failure.
	 * @return array Closed public error.
	 */
	private function license_change_exception( Throwable $error ): array {
		$reason = in_array( $error->getMessage(), array( 'storage_error', 'conflict', 'expired_or_used', 'invalid_username_or_request', 'unavailable' ), true )
			? $error->getMessage() : 'storage_error';
		return $this->respond_error( 'credential_change_' . $reason, 52101201 );
	}

	/**
	 * Protect SQL diagnostics for writes containing the locally retained candidate.
	 *
	 * @param callable $write Existing checked storage operation.
	 * @return mixed Storage result.
	 */
	private function license_change_storage_write( callable $write ) {
		global $wpdb;
		$suppressed = $wpdb->suppress_errors( true );
		$errors     = count( $GLOBALS['EZSQL_ERROR'] ?? array() );
		$queries    = count( $wpdb->queries ?? array() );
		try {
			return $write();
		} finally {
			$wpdb->suppress_errors( $suppressed );
			$error_count = count( $GLOBALS['EZSQL_ERROR'] ?? array() );
			$query_count = count( $wpdb->queries ?? array() );
			for ( $index = $errors; $index < $error_count; ++$index ) {
				// phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- Redact only diagnostics appended by this credential write.
				$GLOBALS['EZSQL_ERROR'][ $index ] = array(
					'query'     => '[license write redacted]',
					'error_str' => 'License storage failure',
				);
			}
			for ( $index = $queries; $index < $query_count; ++$index ) {
				$wpdb->queries[ $index ][0] = '[license write redacted]';
			}
			$wpdb->last_query = '[license write redacted]';
			$wpdb->last_error = '';
		}
	}

	/** Fence ordinary API work while a confirmation has an unknown outcome. */
	private function reconcile_pending_license_change(): bool {
		try {
			$snapshot = $this->license_change_snapshot();
			if ( ! $snapshot['exists'] || 'prepared' === $snapshot['value']['phase'] ) {
				return true;
			}
			$this->is_reconciling_license_change = true;
			$result                              = $this->get_license_change_status();
			return $this->was_call_successful( $result ) && 'pending' !== ( $result['data']['state'] ?? '' );
		} catch ( Throwable $error ) {
			return false;
		} finally {
			$this->is_reconciling_license_change = false;
		}
	}
}
