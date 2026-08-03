<?php

namespace GFExcel\Analytics;

/**
 * The three-layer guard from taxonomy §10.3 step 2.
 *
 * Reject an unregistered event outright; drop an unregistered property key;
 * drop a closed-enum value that is not legal for its key. Dropping keys rather
 * than rejecting the event means one stray value cannot lose the whole event.
 *
 * Adds a diagnostic the taxonomy does not currently mandate. As specified, the
 * guard drops silently, which means a typo'd event name is indistinguishable
 * from an event that was never fired — the failure is invisible in exactly the
 * case you most need to see it. Every drop is counted, and under WP_DEBUG the
 * first occurrence of each distinct name is logged once per request.
 *
 * @since $ver$
 */
class Allowlist {
	/**
	 * Option holding the bounded per-reason drop counters.
	 *
	 * @since $ver$
	 */
	public const COUNTER_OPTION = 'gk_analytics_dropped';

	/**
	 * Names already logged this request, so one bad call cannot flood the log.
	 *
	 * @since $ver$
	 * @var array<string,bool>
	 */
	private $logged = [];

	/**
	 * Drop tallies accumulated this request, flushed once on shutdown.
	 *
	 * @since $ver$
	 * @var array<string,int>
	 */
	private $pending = [];

	/**
	 * Returns true when the event name is registered.
	 *
	 * @since $ver$
	 *
	 * @param string $event The event name.
	 *
	 * @return bool Whether the event may be sent.
	 */
	public function allowsEvent( string $event ): bool {
		$allowed = in_array( $event, Schema::EVENTS, true );

		if ( ! $allowed ) {
			$this->record( 'event:' . $event );
		}

		return $allowed;
	}

	/**
	 * Removes unregistered keys and illegal closed-enum values.
	 *
	 * @since $ver$
	 *
	 * @param array $props The candidate properties.
	 *
	 * @return array The properties that survived.
	 */
	public function filterProps( array $props ): array {
		$clean = [];

		foreach ( $props as $key => $value ) {
			$known = isset( Schema::PROPS[ $key ] ) || isset( Schema::SUPER_PROPS[ $key ] );
			if ( ! $known ) {
				$this->record( 'prop:' . $key );
				continue;
			}

			$enum = Schema::enumForProp( (string) $key );
			if ( null !== $enum && null !== $value ) {
				$legal = Schema::enum( $enum );
				if ( is_array( $legal ) && ! in_array( $value, $legal, true ) ) {
					$this->record( 'enum:' . $key . '=' . ( is_scalar( $value ) ? (string) $value : gettype( $value ) ) );
					continue;
				}
			}

			$clean[ $key ] = $value;
		}

		return $clean;
	}

	/**
	 * Persists this request's drop tallies. Registered on shutdown.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function flush(): void {
		if ( ! $this->pending ) {
			return;
		}

		$counters = get_option( self::COUNTER_OPTION );
		$counters = is_array( $counters ) ? $counters : [];

		foreach ( $this->pending as $reason => $count ) {
			$counters[ $reason ] = ( $counters[ $reason ] ?? 0 ) + $count;
		}

		// Bound the option so a runaway caller cannot grow a row without limit.
		if ( count( $counters ) > 100 ) {
			arsort( $counters );
			$counters = array_slice( $counters, 0, 100, true );
		}

		update_option( self::COUNTER_OPTION, $counters, false );

		$this->pending = [];
	}

	/**
	 * Tallies a drop and logs its first occurrence under WP_DEBUG.
	 *
	 * @since $ver$
	 *
	 * @param string $reason The drop reason.
	 *
	 * @return void
	 */
	private function record( string $reason ): void {
		$this->pending[ $reason ] = ( $this->pending[ $reason ] ?? 0 ) + 1;

		$should_log = defined( 'WP_DEBUG' ) && WP_DEBUG && ! isset( $this->logged[ $reason ] );
		if ( $should_log ) {
			$this->logged[ $reason ] = true;
			error_log( 'GravityExport analytics dropped an unregistered name: ' . $reason );
		}
	}
}
