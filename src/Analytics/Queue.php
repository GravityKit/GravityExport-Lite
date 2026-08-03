<?php

namespace GFExcel\Analytics;

/**
 * Store-and-forward transport.
 *
 * Captures accumulate in memory and leave in one batched request on shutdown.
 * This is not an optimization. The activation event fires from the download
 * path, and WordPress floors HTTP timeouts at one second and runs curl_exec()
 * synchronously even when 'blocking' is false — so a request sent inline would
 * add up to a second to every customer's export download.
 *
 * @since $ver$
 */
class Queue {
	/**
	 * Pending payloads.
	 *
	 * @since $ver$
	 * @var array[]
	 */
	private $items = [];

	/**
	 * Adds a payload, silently discarding beyond the bound.
	 *
	 * @since $ver$
	 *
	 * @param array $payload The payload.
	 *
	 * @return void
	 */
	public function push( array $payload ): void {
		if ( count( $this->items ) >= (int) Schema::TRANSPORT['max_queue'] ) {
			return;
		}

		$this->items[] = $payload;
	}

	/**
	 * Sends everything queued, in one request. Registered on shutdown.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function flush(): void {
		if ( ! $this->items ) {
			return;
		}

		$batch       = $this->items;
		$this->items = [];

		wp_remote_post(
			$this->endpoint(),
			[
				'timeout'  => (int) Schema::TRANSPORT['timeout'],
				'blocking' => false,
				'headers'  => [ 'Content-Type' => 'application/json' ],
				'body'     => wp_json_encode( [ 'batch' => $batch ] ),
			]
		);
	}

	/**
	 * Returns the ingest endpoint.
	 *
	 * The host is filterable and constant-overridable so the proxy can be
	 * repointed without a plugin release. The proxy is also the only place
	 * a bad schema can be corrected after ship, which makes it the rollback
	 * path rather than dumb transport.
	 *
	 * @since $ver$
	 *
	 * @return string The endpoint URL.
	 */
	private function endpoint(): string {
		$host = defined( Schema::TRANSPORT['host_constant'] )
			? constant( Schema::TRANSPORT['host_constant'] )
			: Schema::TRANSPORT['default_host'];

		$host = apply_filters( Schema::TRANSPORT['host_filter'], $host );

		return 'https://' . trim( (string) $host, '/' ) . Schema::TRANSPORT['path'];
	}

	/**
	 * Returns the number of queued payloads. Test seam.
	 *
	 * @since $ver$
	 *
	 * @return int The count.
	 */
	public function count(): int {
		return count( $this->items );
	}
}
