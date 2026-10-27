<?php
/**
 * Bounded legacy option reader for resumable post-state imports.
 *
 * @package AI_For_SEO
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stream serialized/JSON collections without materializing their member lists.
 */
class AI4SEO_Post_State_Reader {
	/**
	 * Exact source option.
	 *
	 * @var string
	 */
	private $name;
	/**
	 * Compact, persistable parser position.
	 *
	 * @var array
	 */
	private $cursor;
	/**
	 * One bounded transport buffer.
	 *
	 * @var string
	 */
	private $buffer = '';
	/**
	 * First buffer byte.
	 *
	 * @var int
	 */
	private $buffer_start = 0;

	/**
	 * Open a frozen source or resume its validated parser position.
	 *
	 * @param string $name Source option.
	 * @param int    $length Exact source length.
	 * @param array  $cursor Previous checkpoint.
	 * @throws RuntimeException When the checkpoint or source cannot be read.
	 * @throws UnexpectedValueException When the source is malformed.
	 */
	public function __construct( string $name, int $length, array $cursor = array() ) {
		$this->name = $name;
		if ( $cursor ) {
			if ( ! self::validate_cursor( $cursor ) || (int) $cursor['length'] !== $length ) {
				throw new RuntimeException( 'Invalid import reader checkpoint.' );
			}
			$this->cursor = self::normalize_cursor( $cursor );
			return;
		}
		$this->cursor = array(
			'byte'     => 0,
			'length'   => $length,
			'end'      => $length,
			'wrappers' => array(),
			'format'   => 'php',
			'frames'   => array(),
			'complete' => false,
		);
		$this->whitespace();
		// Public releases may store a serialized string containing PHP or JSON.
		while ( 's' === $this->peek() ) {
			$this->expect( 's:' );
			$bytes = $this->integer( ':' );
			$this->expect( '"' );
			if ( $bytes < 0 || count( $this->cursor['wrappers'] ) >= 2 || $this->cursor['byte'] + $bytes + 2 > $this->cursor['end'] ) {
				throw new UnexpectedValueException( 'Invalid serialized wrapper.' );
			}
			$this->cursor['wrappers'][] = $this->cursor['end'];
			$this->cursor['end']        = $this->cursor['byte'] + $bytes;
			$this->whitespace();
		}
		$this->whitespace();
		$this->cursor['format'] = 'a' === $this->peek() ? 'php' : 'json';
		$role                   = AI4SEO_GENERATION_STATUS_SUMMARY_OPTION_NAME === $name ? 'summary' : ( isset( AI4SEO_POST_STATE_BUCKETS[ $name ] ) || AI4SEO_POSTS_TO_BE_ANALYZED_OPTION_NAME === $name ? 'list' : 'work' );
		$this->push( $role );
	}

	/**
	 * Validate a bounded checkpoint before using any offsets or parser state.
	 *
	 * @param mixed $cursor Stored checkpoint.
	 * @return bool
	 */
	public static function validate_cursor( $cursor ): bool {
		if ( ! is_array( $cursor ) || array_keys( $cursor ) !== array( 'byte', 'length', 'end', 'wrappers', 'format', 'frames', 'complete' )
			|| ! is_bool( $cursor['complete'] ) || ! in_array( $cursor['format'], array( 'php', 'json' ), true )
			|| ! is_array( $cursor['wrappers'] ) || count( $cursor['wrappers'] ) > 2 || ! is_array( $cursor['frames'] ) || count( $cursor['frames'] ) > 4 ) {
			return false;
		}
		foreach ( array( $cursor['byte'], $cursor['end'], $cursor['length'], ...$cursor['wrappers'] ) as $number ) {
			if ( false === ai4seo_normalize_option_post_id( $number, true ) ) {
				return false;
			}
		}
		if ( $cursor['byte'] > $cursor['end'] || $cursor['end'] > $cursor['length'] ) {
			return false;
		}
		$roles = array_column( $cursor['frames'], 'role' );
		if ( $cursor['complete'] ) {
			if ( $roles || $cursor['wrappers'] || (int) $cursor['byte'] !== (int) $cursor['length'] ) {
				return false;
			}
		} elseif ( ! in_array( $roles, array( array( 'list' ), array( 'work' ), array( 'summary' ), array( 'summary', 'types' ), array( 'summary', 'types', 'entry' ), array( 'summary', 'types', 'entry', 'ids' ) ), true ) ) {
			return false;
		}
		$end = $cursor['length'] + 1;
		foreach ( $cursor['wrappers'] as $wrapper ) {
			if ( $wrapper >= $end || $wrapper <= $cursor['end'] ) {
				return false;
			}
			$end = $wrapper;
		}
		foreach ( $cursor['frames'] as $frame ) {
			if ( ! is_array( $frame ) || array_keys( $frame ) !== array( 'role', 'kind', 'left', 'index', 'option', 'type', 'fields', 'total', 'count' )
				|| ! in_array( $frame['role'], array( 'list', 'work', 'summary', 'types', 'entry', 'ids' ), true )
				|| ! in_array( $frame['kind'], array( 'php', '[', '{' ), true )
				|| ! is_string( $frame['option'] ) || strlen( $frame['option'] ) > 191 || ! is_string( $frame['type'] ) || strlen( $frame['type'] ) > 20 ) {
				return false;
			}
			foreach ( array( 'left', 'index', 'fields', 'total', 'count' ) as $key ) {
				if ( false === ai4seo_normalize_option_post_id( $frame[ $key ], true ) ) {
					return false;
				}
			}
			if ( $frame['fields'] > 3 ) {
				return false;
			}
			if ( ( 'php' === $cursor['format'] ) !== ( 'php' === $frame['kind'] ) ) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Restore numeric fields after the environmental system's text sanitizer.
	 *
	 * @param array $cursor Already validated checkpoint.
	 * @return array
	 */
	public static function normalize_cursor( array $cursor ): array {
		foreach ( array( 'byte', 'length', 'end' ) as $key ) {
			$cursor[ $key ] = (int) $cursor[ $key ];
		}
		$cursor['wrappers'] = array_map( 'intval', $cursor['wrappers'] );
		foreach ( $cursor['frames'] as &$frame ) {
			foreach ( array( 'left', 'index', 'fields', 'total', 'count' ) as $key ) {
				$frame[ $key ] = (int) $frame[ $key ];
			}
		}
		unset( $frame );
		return $cursor;
	}

	/**
	 * Get the state after the last consumed record.
	 *
	 * @return array
	 */
	public function checkpoint(): array {
		return $this->cursor;
	}

	/**
	 * Read at most one transport packet into memory.
	 *
	 * @param int $length Requested bytes.
	 * @return string
	 * @throws RuntimeException When the source query fails.
	 * @throws UnexpectedValueException When the source is truncated or oversized.
	 */
	private function read( int $length ): string {
		if ( $length < 0 || $length > 65536 || $length > $this->cursor['end'] - $this->cursor['byte'] ) {
			throw new UnexpectedValueException( 'Truncated or oversized legacy value.' );
		}
		$value     = '';
		$remaining = $length;
		while ( $remaining > 0 ) {
			$position = $this->cursor['byte'] - $this->buffer_start;
			if ( $position < 0 || $position >= strlen( $this->buffer ) ) {
				$rows = ai4seo_post_state_query(
					'SELECT SUBSTRING(BINARY option_value,{{offset}},65536) AS chunk FROM {{options}} WHERE option_name={{name}}',
					array(
						'offset' => $this->cursor['byte'] + 1,
						'name'   => $this->name,
					),
					true
				);
				if ( null === $rows || ! isset( $rows[0]['chunk'] ) ) {
					throw new RuntimeException( 'Legacy source read failed.' );
				}
				$this->buffer       = $rows[0]['chunk'];
				$this->buffer_start = $this->cursor['byte'];
				$position           = 0;
				if ( '' === $this->buffer ) {
					throw new UnexpectedValueException( 'Truncated legacy source.' );
				}
			}
			$take                  = min( $remaining, strlen( $this->buffer ) - $position );
			$value                .= substr( $this->buffer, $position, $take );
			$this->cursor['byte'] += $take;
			$remaining            -= $take;
		}
		return $value;
	}

	/**
	 * Inspect the next byte without consuming it.
	 *
	 * @return string
	 */
	private function peek(): string {
		if ( $this->cursor['byte'] >= $this->cursor['end'] ) {
			return '';
		}
		$char = $this->read( 1 );
		--$this->cursor['byte'];
		return $char;
	}

	/**
	 * Require exact wire bytes.
	 *
	 * @param string $expected Bytes.
	 * @throws UnexpectedValueException When bytes do not match.
	 */
	private function expect( string $expected ): void {
		if ( $expected !== $this->read( strlen( $expected ) ) ) {
			throw new UnexpectedValueException( 'Invalid legacy delimiter.' );
		}
	}

	/** Skip JSON whitespace. */
	private function whitespace(): void {
		while ( in_array( $this->peek(), array( ' ', "\n", "\r", "\t" ), true ) ) {
			$this->read( 1 );
		}
	}

	/**
	 * Read a canonical integer without silently accepting overflow.
	 *
	 * @param string $delimiter Terminator.
	 * @return int
	 * @throws UnexpectedValueException When an integer is malformed or overflows.
	 */
	private function integer( string $delimiter ): int {
		$value = '';
		$char  = $this->read( 1 );
		while ( $char !== $delimiter ) {
			$value .= $char;
			if ( strlen( $value ) > 20 ) {
				throw new UnexpectedValueException( 'Oversized legacy integer.' );
			}
			$char = $this->read( 1 );
		}
		if ( ! preg_match( '/^-?(?:0|[1-9][0-9]*)$/D', $value ) || (string) (int) $value !== $value ) {
			throw new UnexpectedValueException( 'Invalid legacy integer.' );
		}
		return (int) $value;
	}

	/**
	 * Read a scalar or one bounded work payload.
	 *
	 * @param int $depth Nesting depth.
	 * @param int $start Start of this small value.
	 * @return mixed
	 * @throws UnexpectedValueException When a small record is invalid or too large.
	 */
	private function value( int $depth = 0, int $start = -1 ) {
		$start = $start < 0 ? $this->cursor['byte'] : $start;
		if ( $depth > 6 || $this->cursor['byte'] - $start > 65536 ) {
			throw new UnexpectedValueException( 'Oversized legacy record.' );
		}
		$this->whitespace();
		$char = $this->peek();
		if ( ( 'php' === $this->cursor['format'] && 'a' === $char ) || ( 'json' === $this->cursor['format'] && in_array( $char, array( '[', '{' ), true ) ) ) {
			$frame  = $this->container( 'work' );
			$result = array();
			$key    = $this->key( $frame );
			while ( null !== $key ) {
				if ( count( $result ) >= 128 || array_key_exists( $key, $result ) ) {
					throw new UnexpectedValueException( 'Invalid work record keys.' );
				}
				$result[ $key ] = $this->value( $depth + 1, $start );
				$key            = $this->key( $frame );
			}
			return $result;
		}
		if ( 'php' === $this->cursor['format'] ) {
			$type = $this->read( 1 );
			if ( 'N' === $type ) {
				$this->expect( ';' );
				return null;
			}
			$this->expect( ':' );
			if ( 'i' === $type || 'b' === $type ) {
				$value = $this->integer( ';' );
				if ( 'b' === $type && ! in_array( $value, array( 0, 1 ), true ) ) {
					throw new UnexpectedValueException( 'Invalid legacy boolean.' );
				}
				return 'b' === $type ? (bool) $value : $value;
			}
			if ( 's' !== $type ) {
				throw new UnexpectedValueException( 'Unsupported legacy scalar.' );
			}
			$length = $this->integer( ':' );
			$this->expect( '"' );
			$value = $this->read( $length );
			$this->expect( '";' );
			return $value;
		}
		$wire = $this->read( 1 );
		if ( '"' === $wire ) {
			do {
				$char  = $this->read( 1 );
				$wire .= $char;
				if ( '\\' === $char ) {
					$wire .= $this->read( 1 );
				}
				if ( strlen( $wire ) > 65536 ) {
					throw new UnexpectedValueException( 'Oversized JSON scalar.' );
				}
			} while ( '"' !== $char );
		} else {
			while ( '' !== $this->peek() && ! in_array( $this->peek(), array( ',', ']', '}', ' ', "\n", "\t", "\r" ), true ) ) {
				$wire .= $this->read( 1 );
				if ( strlen( $wire ) > 32 ) {
					throw new UnexpectedValueException( 'Oversized JSON scalar.' );
				}
			}
		}
		$value = json_decode( $wire, true );
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			throw new UnexpectedValueException( 'Invalid JSON scalar.' );
		}
		return $value;
	}

	/**
	 * Open an array/object; its members remain on the server.
	 *
	 * @param string $role Logical container.
	 * @param string $option Summary bucket.
	 * @param string $type Summary post type.
	 * @return array
	 * @throws UnexpectedValueException When a container is malformed.
	 */
	private function container( string $role, string $option = '', string $type = '' ): array {
		$this->whitespace();
		$kind = $this->read( 1 );
		$left = 0;
		if ( 'php' === $this->cursor['format'] ) {
			if ( 'a' !== $kind ) {
				throw new UnexpectedValueException( 'Expected legacy collection.' );
			}
			$this->expect( ':' );
			$left = $this->integer( ':' );
			$this->expect( '{' );
			$kind = 'php';
			if ( $left < 0 ) {
				throw new UnexpectedValueException( 'Negative collection length.' );
			}
		} elseif ( ! in_array( $kind, array( '[', '{' ), true ) ) {
			throw new UnexpectedValueException( 'Expected JSON collection.' );
		}
		return array(
			'role'   => $role,
			'kind'   => $kind,
			'left'   => $left,
			'index'  => 0,
			'option' => $option,
			'type'   => $type,
			'fields' => 0,
			'total'  => 0,
			'count'  => 0,
		);
	}

	/**
	 * Consume the next key, or close an exhausted container.
	 *
	 * @param array $frame Current container.
	 * @return int|string|null
	 * @throws UnexpectedValueException When a key is not a supported scalar.
	 */
	private function key( array &$frame ) {
		if ( 'php' === $frame['kind'] ) {
			if ( 0 === $frame['left'] ) {
				$this->expect( '}' );
				return null;
			}
			--$frame['left'];
			$key = $this->value();
		} else {
			$this->whitespace();
			$end = '[' === $frame['kind'] ? ']' : '}';
			if ( $end === $this->peek() ) {
				$this->read( 1 );
				return null;
			}
			if ( $frame['index'] > 0 ) {
				$this->expect( ',' );
				$this->whitespace();
			}
			$key = '[' === $frame['kind'] ? $frame['index'] : $this->value();
			if ( '{' === $frame['kind'] ) {
				if ( ! is_string( $key ) ) {
					throw new UnexpectedValueException( 'Invalid JSON object key.' );
				}
				$this->whitespace();
				$this->expect( ':' );
			}
		}
		if ( ! is_int( $key ) && ! is_string( $key ) ) {
			throw new UnexpectedValueException( 'Invalid legacy key.' );
		}
		++$frame['index'];
		return $key;
	}

	/**
	 * Add a structural frame, never a list of IDs.
	 *
	 * @param string $role Container role.
	 * @param string $option Summary bucket.
	 * @param string $type Post type.
	 */
	private function push( string $role, string $option = '', string $type = '' ): void {
		$this->cursor['frames'][] = $this->container( $role, $option, $type );
	}

	/**
	 * Verify suffixes before declaring a source completely consumed.
	 *
	 * @throws UnexpectedValueException When unread or malformed bytes remain.
	 */
	private function finish(): void {
		$this->whitespace();
		if ( $this->cursor['byte'] !== $this->cursor['end'] ) {
			throw new UnexpectedValueException( 'Unexpected legacy suffix.' );
		}
		while ( $this->cursor['wrappers'] ) {
			$this->cursor['end'] = array_pop( $this->cursor['wrappers'] );
			$this->expect( '";' );
			$this->whitespace();
			if ( $this->cursor['byte'] !== $this->cursor['end'] ) {
				throw new UnexpectedValueException( 'Invalid legacy wrapper suffix.' );
			}
		}
		$this->cursor['complete'] = true;
	}

	/**
	 * Return the next validated logical record and retain its bounded checkpoint.
	 *
	 * @return array|null Null only after every delimiter/count has been verified.
	 * @throws UnexpectedValueException When a source record fails validation.
	 */
	public function next_record(): ?array {
		while ( $this->cursor['frames'] ) {
			$index = count( $this->cursor['frames'] ) - 1;
			$key   = $this->key( $this->cursor['frames'][ $index ] );
			$frame = $this->cursor['frames'][ $index ];
			if ( null === $key ) {
				if ( 'entry' === $frame['role'] && ( 3 !== $frame['fields'] || $frame['count'] !== $frame['total'] ) ) {
					throw new UnexpectedValueException( 'Summary total does not describe its IDs.' );
				}
				array_pop( $this->cursor['frames'] );
				continue;
			}
			if ( 'summary' === $frame['role'] ) {
				if ( ! in_array( $key, ai4seo_get_generation_status_summary_source_option_names(), true ) ) {
					throw new UnexpectedValueException( 'Unknown summary bucket.' );
				}
				$this->push( 'types', $key );
			} elseif ( 'types' === $frame['role'] ) {
				if ( ! is_string( $key ) || '' === $key || strlen( $key ) > 20 ) {
					throw new UnexpectedValueException( 'Invalid summary post type.' );
				}
				$this->push( 'entry', $frame['option'], $key );
			} elseif ( 'entry' === $frame['role'] ) {
				$bit = 'total' === $key ? 1 : ( 'post_ids' === $key ? 2 : 0 );
				if ( $bit && ( $frame['fields'] & $bit ) ) {
					throw new UnexpectedValueException( 'Duplicate summary field.' );
				}
				$this->cursor['frames'][ $index ]['fields'] |= $bit;
				if ( 2 === $bit ) {
					$this->push( 'ids', $frame['option'], $frame['type'] );
				} else {
					$value = $this->value();
					if ( 1 === $bit ) {
						$value = ai4seo_normalize_option_post_id( $value, true );
						if ( false === $value ) {
							throw new UnexpectedValueException( 'Invalid summary total.' );
						}
						$this->cursor['frames'][ $index ]['total'] = $value;
					}
				}
			} elseif ( 'work' === $frame['role'] ) {
				$id        = ai4seo_normalize_option_post_id( $key );
				$value     = $this->value();
				$repair    = false;
				$validated = false === $id ? null : ai4seo_decode_post_state_migration_source(
					$this->name,
					array(
						'exists' => true,
						'value'  => array( $id => $value ),
					),
					$repair
				);
				if ( null === $validated ) {
					throw new UnexpectedValueException( 'Invalid work record.' );
				}
				foreach ( ai4seo_post_state_source_records( $this->name, $validated ) as $record ) {
					return $record;
				}
			} else {
				$id = ai4seo_normalize_option_post_id( $this->value() );
				if ( false === $id ) {
					throw new UnexpectedValueException( 'Invalid legacy post ID.' );
				}
				if ( 'ids' === $frame['role'] ) {
					++$this->cursor['frames'][ $index - 1 ]['count'];
					return array(
						'option'    => $frame['option'],
						'post_id'   => $id,
						'post_type' => $frame['type'],
						'area'      => 2,
					);
				}
				foreach ( ai4seo_post_state_source_records( $this->name, array( $id ) ) as $record ) {
					return $record;
				}
			}
		}
		if ( ! $this->cursor['complete'] ) {
			$this->finish();
		}
		return null;
	}
}
