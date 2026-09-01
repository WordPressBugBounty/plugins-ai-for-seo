<?php
/**
 * Typed database-query construction helpers.
 *
 * @package AI_For_SEO
 */

/**
 * Returns the maximum number of values that one prepared query may bind.
 *
 * The budget covers the largest supported ID chunk plus fixed filters while
 * remaining comfortably below MySQL's prepared-statement marker limit.
 *
 * @return int
 */
function ai4seo_get_database_placeholder_budget(): int {
	return 25000;
}

/**
 * Determines whether a database identifier has the restricted expected shape.
 *
 * @param mixed $identifier Candidate identifier.
 * @return bool
 */
function ai4seo_is_valid_database_identifier( $identifier ): bool {
	return is_string( $identifier ) && 1 === preg_match( '/^[A-Za-z0-9_]+$/', $identifier );
}

/**
 * Adds an available wpdb table property to the identifier registry.
 *
 * @param array  $registry Registry receiving the identifier.
 * @param string $registry_key Stable allowlist key.
 * @param object $database WordPress database object.
 * @param string $property wpdb property holding the table name.
 * @return void
 */
function ai4seo_add_database_identifier_from_property(
	array &$registry,
	string $registry_key,
	$database,
	string $property
): void {
	if ( ! isset( $database->{$property} ) ) {
		return;
	}

	$identifier = $database->{$property};

	if ( ai4seo_is_valid_database_identifier( $identifier ) ) {
		$registry[ $registry_key ] = $identifier;
	}
}

/**
 * Returns the closed registry of database identifiers available to query specs.
 *
 * Callers bind registry keys, never table names supplied at runtime.
 *
 * @return array<string,string>
 */
function ai4seo_get_database_identifier_registry(): array {
	global $wpdb;

	if ( ! is_object( $wpdb ) ) {
		return array();
	}

	$registry = array();

	// Register only core table properties exposed by the active WordPress database connection.
	$wordpress_table_properties = array(
		'table.blogs'              => 'blogs',
		'table.comments'           => 'comments',
		'table.commentmeta'        => 'commentmeta',
		'table.links'              => 'links',
		'table.options'            => 'options',
		'table.posts'              => 'posts',
		'table.postmeta'           => 'postmeta',
		'table.terms'              => 'terms',
		'table.termmeta'           => 'termmeta',
		'table.term_relationships' => 'term_relationships',
		'table.term_taxonomy'      => 'term_taxonomy',
		'table.users'              => 'users',
		'table.usermeta'           => 'usermeta',
	);

	foreach ( $wordpress_table_properties as $registry_key => $property ) {
		ai4seo_add_database_identifier_from_property( $registry, $registry_key, $wpdb, $property );
	}

	// Single-site WordPress does not populate the global blogs-table property, but its base prefix remains authoritative.
	if ( ! isset( $registry['table.blogs'] )
		&& isset( $wpdb->base_prefix )
		&& is_string( $wpdb->base_prefix )
	) {
		$blogs_table = $wpdb->base_prefix . 'blogs';

		if ( ai4seo_is_valid_database_identifier( $blogs_table ) ) {
			$registry['table.blogs'] = $blogs_table;
		}
	}

	// Derive known integration tables from the active site prefix before validating each identifier.
	if ( isset( $wpdb->prefix ) && is_string( $wpdb->prefix ) ) {
		$prefixed_tables = array(
			'table.aioseo_posts'     => 'aioseo_posts',
			'table.nextgen_gallery'  => 'ngg_gallery',
			'table.nextgen_pictures' => 'ngg_pictures',
			'table.squirrly'         => 'qss',
		);

		foreach ( $prefixed_tables as $registry_key => $suffix ) {
			$identifier = $wpdb->prefix . $suffix;

			if ( ai4seo_is_valid_database_identifier( $identifier ) ) {
				$registry[ $registry_key ] = $identifier;
			}
		}
	}

	return $registry;
}

/**
 * Normalizes one database ID without accepting PHP's loose numeric forms.
 *
 * @param mixed $value Candidate database ID.
 * @return int|false Normalized positive ID, or false when the value is invalid.
 */
function ai4seo_normalize_database_id( $value ) {
	if ( is_int( $value ) ) {
		$normalized_id = $value;
	} elseif ( is_string( $value ) && 1 === preg_match( '/^[1-9][0-9]*$/', $value ) ) {
		$normalized_id = (int) $value;

		// Reject canonical-looking strings that overflowed PHP's integer range during conversion.
		if ( (string) $normalized_id !== $value ) {
			return false;
		}
	} else {
		return false;
	}

	return $normalized_id > 0 ? $normalized_id : false;
}

/**
 * Normalizes a list of database IDs without accepting PHP's loose numeric forms.
 *
 * Positive integers and canonical positive-integer strings are accepted. The
 * first occurrence of an ID wins and the original order remains stable.
 *
 * @param array $values Candidate database IDs.
 * @return array<int,int>|false Normalized IDs, or false when any value is invalid.
 */
function ai4seo_normalize_database_ids( array $values ) {
	$normalized_ids = array();
	$seen_ids       = array();

	foreach ( $values as $value ) {
		// Reuse the scalar contract so list and keyset callers cannot drift on accepted ID shapes.
		$normalized_id = ai4seo_normalize_database_id( $value );

		if ( false === $normalized_id ) {
			return false;
		}

		$deduplication_key = (string) $normalized_id;

		if ( isset( $seen_ids[ $deduplication_key ] ) ) {
			continue;
		}

		$seen_ids[ $deduplication_key ] = true;
		$normalized_ids[]               = $normalized_id;
	}

	return $normalized_ids;
}

/**
 * Creates a scalar-value binding for a named query token.
 *
 * Validation is deferred until the complete query specification is compiled.
 *
 * @param string $format One of %d, %s, or %f.
 * @param mixed  $value Scalar value.
 * @return array
 */
function ai4seo_database_scalar_binding( string $format, $value ): array {
	return array(
		'ai4seo_database_binding' => true,
		'kind'                    => 'scalar',
		'format'                  => $format,
		'values'                  => array( $value ),
	);
}

/**
 * Creates a non-empty list binding for a named query token.
 *
 * Validation is deferred until the complete query specification is compiled.
 *
 * @param string $format One of %d, %s, or %f.
 * @param array  $values Ordered scalar values.
 * @return array
 */
function ai4seo_database_list_binding( string $format, array $values ): array {
	return array(
		'ai4seo_database_binding' => true,
		'kind'                    => 'list',
		'format'                  => $format,
		'values'                  => $values,
	);
}

/**
 * Creates an identifier binding backed by the closed identifier registry.
 *
 * @param string $registry_key Identifier registry key.
 * @return array
 */
function ai4seo_database_identifier_binding( string $registry_key ): array {
	return array(
		'ai4seo_database_binding' => true,
		'kind'                    => 'identifier',
		'format'                  => '%i',
		'values'                  => array( $registry_key ),
	);
}

/**
 * Creates a non-empty identifier-list binding backed by the closed registry.
 *
 * @param array $registry_keys Ordered identifier registry keys.
 * @return array
 */
function ai4seo_database_identifier_list_binding( array $registry_keys ): array {
	return array(
		'ai4seo_database_binding' => true,
		'kind'                    => 'identifier_list',
		'format'                  => '%i',
		'values'                  => $registry_keys,
	);
}

/**
 * Determines whether an array is a zero-based ordered list on PHP 7.4.
 *
 * @param array $values Candidate list.
 * @return bool
 */
function ai4seo_is_database_value_list( array $values ): bool {
	$expected_key = 0;

	foreach ( $values as $key => $value ) {
		unset( $value );

		if ( $expected_key !== $key ) {
			return false;
		}

		++$expected_key;
	}

	return true;
}

/**
 * Validates and normalizes one prepared-query value.
 *
 * @param string $format Format placeholder.
 * @param mixed  $value Candidate value.
 * @param array  $identifier_registry Closed identifier registry.
 * @param mixed  $normalized_value Receives the normalized value.
 * @return bool
 */
function ai4seo_normalize_database_binding_value(
	string $format,
	$value,
	array $identifier_registry,
	&$normalized_value
): bool {
	if ( '%d' === $format ) {
		if ( ! is_int( $value ) ) {
			return false;
		}

		$normalized_value = $value;
		return true;
	}

	if ( '%s' === $format ) {
		if ( ! is_string( $value ) ) {
			return false;
		}

		$normalized_value = $value;
		return true;
	}

	if ( '%f' === $format ) {
		if ( ! is_float( $value ) || ! is_finite( $value ) ) {
			return false;
		}

		$normalized_value = $value;
		return true;
	}

	if ( '%i' !== $format || ! is_string( $value ) || ! isset( $identifier_registry[ $value ] ) ) {
		return false;
	}

	$normalized_value = $identifier_registry[ $value ];
	return true;
}

/**
 * Validates a binding and appends its normalized values in template order.
 *
 * @param mixed  $binding Candidate binding structure.
 * @param array  $identifier_registry Closed identifier registry.
 * @param array  $prepared_values Prepared values accumulated by the compiler.
 * @param string $placeholder_sql Receives the scalar or comma-separated placeholders.
 * @return bool
 */
function ai4seo_compile_database_binding(
	$binding,
	array $identifier_registry,
	array &$prepared_values,
	string &$placeholder_sql
): bool {
	if ( ! is_array( $binding ) ) {
		return false;
	}

	$expected_keys = array(
		'ai4seo_database_binding',
		'kind',
		'format',
		'values',
	);

	if ( array_keys( $binding ) !== $expected_keys || true !== $binding['ai4seo_database_binding'] ) {
		return false;
	}

	$kind   = $binding['kind'];
	$format = $binding['format'];
	$values = $binding['values'];

	if ( ! is_string( $kind ) || ! is_string( $format ) || ! is_array( $values ) ) {
		return false;
	}

	$scalar_formats = array( '%d', '%s', '%f' );

	if ( 'scalar' === $kind ) {
		if ( ! in_array( $format, $scalar_formats, true ) || 1 !== count( $values ) || ! ai4seo_is_database_value_list( $values ) ) {
			return false;
		}
	} elseif ( 'list' === $kind ) {
		if ( ! in_array( $format, $scalar_formats, true ) || empty( $values ) || ! ai4seo_is_database_value_list( $values ) ) {
			return false;
		}
	} elseif ( 'identifier' === $kind ) {
		if ( '%i' !== $format || 1 !== count( $values ) || ! ai4seo_is_database_value_list( $values ) ) {
			return false;
		}
	} elseif ( 'identifier_list' === $kind ) {
		if ( '%i' !== $format || empty( $values ) || ! ai4seo_is_database_value_list( $values ) ) {
			return false;
		}
	} else {
		return false;
	}

	if ( count( $prepared_values ) + count( $values ) > ai4seo_get_database_placeholder_budget() ) {
		return false;
	}

	$placeholders = array();

	foreach ( $values as $value ) {
		$normalized_value = null;

		if ( ! ai4seo_normalize_database_binding_value( $format, $value, $identifier_registry, $normalized_value ) ) {
			return false;
		}

		$prepared_values[] = $normalized_value;
		$placeholders[]    = $format;
	}

	$placeholder_sql = implode( ', ', $placeholders );
	return true;
}

/**
 * Determines whether a query template contains a percent not escaped as %%.
 *
 * @param string $query_template Named-token query template.
 * @return bool
 */
function ai4seo_database_template_has_unescaped_percent( string $query_template ): bool {
	$template_length = strlen( $query_template );

	for ( $offset = 0; $offset < $template_length; ++$offset ) {
		if ( '%' !== $query_template[ $offset ] ) {
			continue;
		}

		$run_length = 1;

		while ( $offset + $run_length < $template_length && '%' === $query_template[ $offset + $run_length ] ) {
			++$run_length;
		}

		if ( 0 !== $run_length % 2 ) {
			return true;
		}

		$offset += $run_length - 1;
	}

	return false;
}

/**
 * Compiles and prepares a query from static named tokens and typed bindings.
 *
 * Every {{lowercase_token}} must occur exactly once and have exactly one
 * binding. Raw placeholders and SQL-fragment bindings are deliberately not
 * supported.
 *
 * @param string $query_template Query template containing named tokens.
 * @param array  $bindings Bindings keyed by token name.
 * @return string|false Prepared query, or false for any invalid specification.
 */
function ai4seo_prepare_database_query( string $query_template, array $bindings ) {
	global $wpdb;

	if (
		'' === trim( $query_template )
		|| empty( $bindings )
		|| ai4seo_database_template_has_unescaped_percent( $query_template )
		|| ! is_object( $wpdb )
		|| ! is_callable( array( $wpdb, 'prepare' ) )
	) {
		return false;
	}

	// Reject incomplete or ambiguous brace syntax before compiling any token into placeholders.
	$token_match_count = preg_match_all( '/\{\{([^{}]*)}}/', $query_template, $token_matches, PREG_SET_ORDER );

	if (
		false === $token_match_count
		|| 0 === $token_match_count
		|| substr_count( $query_template, '{{' ) !== $token_match_count
		|| substr_count( $query_template, '}}' ) !== $token_match_count
		|| count( $bindings ) !== $token_match_count
	) {
		return false;
	}

	// Enforce a one-to-one lowercase token map so replacement order cannot hide missing bindings.
	$token_counts = array();

	foreach ( $token_matches as $token_match ) {
		$token_name = $token_match[1];

		if ( ! is_string( $token_name ) || 1 !== preg_match( '/^[a-z][a-z0-9_]*$/', $token_name ) ) {
			return false;
		}

		if ( isset( $token_counts[ $token_name ] ) ) {
			return false;
		}

		$token_counts[ $token_name ] = 1;
	}

	foreach ( $bindings as $token_name => $binding ) {
		unset( $binding );

		if ( ! is_string( $token_name ) || ! isset( $token_counts[ $token_name ] ) ) {
			return false;
		}
	}

	// Compile in template order so emitted placeholders and prepared values remain positionally aligned.
	$identifier_registry = ai4seo_get_database_identifier_registry();
	$prepared_values     = array();
	$has_invalid_binding = false;
	$compiled_template   = preg_replace_callback(
		'/\{\{([a-z][a-z0-9_]*)}}/',
		static function ( array $token_parts ) use ( $bindings, $identifier_registry, &$prepared_values, &$has_invalid_binding ): string {
			$placeholder_sql = '';

			if ( ! ai4seo_compile_database_binding( $bindings[ $token_parts[1] ], $identifier_registry, $prepared_values, $placeholder_sql ) ) {
				$has_invalid_binding = true;
				return '';
			}

			return $placeholder_sql;
		},
		$query_template
	);

	if ( $has_invalid_binding || ! is_string( $compiled_template ) || empty( $prepared_values ) ) {
		return false;
	}

	// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare,WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- The compiler admits only validated typed placeholders and accumulates exactly one prepared value per emitted placeholder.
	$prepared_query = $wpdb->prepare( $compiled_template, $prepared_values );

	return is_string( $prepared_query ) && '' !== $prepared_query ? $prepared_query : false;
}
