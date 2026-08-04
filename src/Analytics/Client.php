<?php

namespace GFExcel\Analytics;

/**
 * The local analytics implementation, used when Foundation is absent.
 *
 * This is the deletable half of the design. When Foundation reaches
 * wordpress.org, this class and its collaborators are removed and the facade
 * forwards to Foundation instead. No call site changes, because no call site
 * names this class.
 *
 * @since $ver$
 */
class Client {
	/**
	 * @since $ver$
	 * @var Consent
	 */
	private $consent;

	/**
	 * @since $ver$
	 * @var Allowlist
	 */
	private $allowlist;

	/**
	 * @since $ver$
	 * @var Scrub
	 */
	private $scrub;

	/**
	 * @since $ver$
	 * @var SuperProps
	 */
	private $super_props;

	/**
	 * @since $ver$
	 * @var Queue
	 */
	private $queue;

	/**
	 * @since $ver$
	 *
	 * @param Consent    $consent     The consent store.
	 * @param Allowlist  $allowlist   The three-layer guard.
	 * @param Scrub      $scrub       The PII scrub.
	 * @param SuperProps $super_props The install-global properties.
	 * @param Queue      $queue       The store-and-forward transport.
	 */
	public function __construct(
		Consent $consent,
		Allowlist $allowlist,
		Scrub $scrub,
		SuperProps $super_props,
		Queue $queue
	) {
		$this->consent     = $consent;
		$this->allowlist   = $allowlist;
		$this->scrub       = $scrub;
		$this->super_props = $super_props;
		$this->queue       = $queue;
	}

	/**
	 * Queues an event, after consent, allowlist and scrub.
	 *
	 * @since $ver$
	 *
	 * @param string $event The event name.
	 * @param array  $props The event properties.
	 *
	 * @return void
	 */
	public function capture( string $event, array $props = [] ): void {
		if ( ! $this->is_enabled() ) {
			return;
		}

		if ( ! $this->allowlist->allowsEvent( $event ) ) {
			return;
		}

		$super = $this->super_props->build();
		if ( null === $super ) {
			return; // No salt, so no non-reversible identity. Refuse rather than emit.
		}

		// Attached before filtering so they travel with every event, including the
		// ones a caller built by hand. The IP is the one identifier the plugin
		// cannot withhold at the transport layer, so it is countered by an
		// explicit instruction the sink can honour and an auditor can see.
		// Unconditional by design: a privacy control with an off switch is one
		// that will eventually be switched off.
		$props[ Schema::PRIVACY['ip_property'] ]            = null;
		$props[ Schema::PRIVACY['geoip_disable_property'] ] = true;

		$props = $this->allowlist->filterProps( $props );
		$props = $this->scrub->scrub( $props );

		$activated = $this->resolveActivation( $event, (string) $super['gk_product'] );

		$props = array_merge(
			$super,
			$props,
			[
				'activated_product'   => $activated,
				'is_activation_event' => null !== $activated,
				'funnel_stage'        => null !== $activated ? 'activated' : 'setup',
			]
		);

		$this->queue->push(
			[
				'event'      => $event,
				'properties' => $props,
				'groups'     => [ 'site' => $super['site_id'] ],
				// Install-scale facts describe the site, not the action, so they
				// ride as group properties rather than being stamped onto every
				// event. One home, so the two copies cannot disagree.
				'group_props' => $this->super_props->groupProperties(),
				'timestamp'  => gmdate( 'c' ),
			]
		);
	}

	/**
	 * Records consent and emits the opt-in event.
	 *
	 * @since $ver$
	 *
	 * @param string $source     Where consent was collected.
	 * @param string $disclosure The exact text shown.
	 *
	 * @return void
	 */
	public function opt_in( string $source, string $disclosure ): void {
		$this->consent->grant( $source, $disclosure );
		$this->capture( 'analytics_opt_in', [ 'consent_source' => $source ] );
	}

	/**
	 * Withdraws consent. Emits nothing, by design.
	 *
	 * @since $ver$
	 *
	 * @return void
	 */
	public function opt_out(): void {
		$this->consent->revoke();
	}

	/**
	 * Returns true when this install may send analytics.
	 *
	 * @since $ver$
	 *
	 * @return bool Whether analytics is enabled.
	 */
	public function is_enabled(): bool {
		/**
		 * Filters whether analytics may send.
		 *
		 * @since $ver$
		 *
		 * @param bool $enabled Whether sending is allowed.
		 */
		return (bool) apply_filters( 'gk/analytics/enabled', $this->consent->granted() );
	}

	/**
	 * Resolves which product's activation this event satisfies, if any.
	 *
	 * Matches on the event name alone, because that is all the current schema's
	 * activation rows carry. The canonical taxonomy resolves on
	 * `(event, object_type, layout, render_surface)` so that, for example, a
	 * DataTables publish emitted by the GravityView editor attributes to
	 * DataTables rather than to the emitter. Those discriminators must be added
	 * here at the same time the schema grows rows that set them; matching on
	 * fields no row populates would be dead code today.
	 *
	 * `export_completed` currently matches both GravityExport rows identically,
	 * so the emitter breaks the tie. That tie is a gap in the activation table
	 * rather than a rule, and it is confined here so the fix is one place.
	 *
	 * @since $ver$
	 *
	 * @param string $event   The event name.
	 * @param string $emitter The emitting product slug.
	 *
	 * @return string|null The activated product.
	 */
	private function resolveActivation( string $event, string $emitter ): ?string {
		$matches = [];

		foreach ( Schema::ACTIVATION as $product => $predicate ) {
			if ( $event === $predicate[0] ) {
				$matches[] = $product;
			}
		}

		if ( ! $matches ) {
			return null;
		}

		if ( 1 === count( $matches ) ) {
			return $matches[0];
		}

		return in_array( $emitter, $matches, true ) ? $emitter : $matches[0];
	}
}
