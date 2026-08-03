<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Schema;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Schema}.
 *
 * @since $ver$
 */
class SchemaTest extends TestCase {
	/**
	 * The canonical taxonomy fixes the registry at thirty events and mandates
	 * this assertion by name. A drift here means the generated file and the
	 * contract have diverged.
	 *
	 * @since $ver$
	 */
	public function testEventCountMatchesTheTaxonomy(): void {
		self::assertCount( 30, Schema::EVENTS );
	}

	/**
	 * @since $ver$
	 */
	public function testEventsAreUnique(): void {
		self::assertSame( Schema::EVENTS, array_values( array_unique( Schema::EVENTS ) ) );
	}

	/**
	 * Every enum-typed property must name an enum that actually exists, or the
	 * guard would silently pass values it believes are unconstrained.
	 *
	 * @since $ver$
	 */
	public function testEveryEnumTypedPropResolvesToADefinedEnum(): void {
		$props = array_merge( Schema::PROPS, Schema::SUPER_PROPS );

		foreach ( $props as $key => $spec ) {
			if ( ! isset( $spec['enum'] ) ) {
				continue;
			}

			self::assertIsArray(
				Schema::enum( $spec['enum'] ),
				sprintf( 'Property "%s" names undefined enum "%s".', $key, $spec['enum'] )
			);
		}
	}

	/**
	 * @since $ver$
	 */
	public function testActivationPredicatesReferenceRegisteredEvents(): void {
		foreach ( Schema::ACTIVATION as $product => $predicate ) {
			self::assertContains(
				$predicate[0],
				Schema::EVENTS,
				sprintf( 'Activation row "%s" references unregistered event "%s".', $product, $predicate[0] )
			);
		}
	}

	/**
	 * The attribution salt must never be the identity salt. Sharing them would
	 * make every stored discount claim re-identify that install's whole history.
	 *
	 * @since $ver$
	 */
	public function testAttributionSaltIsSeparateFromIdentitySalt(): void {
		self::assertNotSame(
			Schema::IDENTITY['salt_option'],
			Schema::ATTRIBUTION['salt_option']
		);
	}

	/**
	 * @since $ver$
	 */
	public function testEnumForPropReturnsNullForNonEnumProps(): void {
		self::assertNull( Schema::enumForProp( 'column_count' ) );
		self::assertSame( 'file_format', Schema::enumForProp( 'file_format' ) );
	}
}
