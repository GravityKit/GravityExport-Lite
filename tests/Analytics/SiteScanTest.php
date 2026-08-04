<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Schema;
use GFExcel\Analytics\SiteScan;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see SiteScan}.
 *
 * @since $ver$
 */
class SiteScanTest extends TestCase {
	/**
	 * Boundaries are the whole point of a bucket, so they are tested at the edges
	 * rather than in the middle of each range.
	 *
	 * @since $ver$
	 *
	 * @dataProvider boundaries
	 *
	 * @param int    $count    The count.
	 * @param string $expected The expected bucket.
	 */
	public function testBucketBoundaries( int $count, string $expected ): void {
		self::assertSame( $expected, SiteScan::bucket( $count ) );
	}

	/**
	 * @since $ver$
	 *
	 * @return array[] The cases.
	 */
	public function boundaries(): array {
		return [
			'empty'                => [ 0, '0' ],
			'single'               => [ 1, '1' ],
			'low edge'             => [ 2, '2-5' ],
			'low top'              => [ 5, '2-5' ],
			'next opens'           => [ 6, '6-10' ],
			'hundred'              => [ 100, '26-100' ],
			'hundred and one'      => [ 101, '101-1000' ],
			'ten thousand'         => [ 10000, '1001-10000' ],
			'ten thousand and one' => [ 10001, '10001-50000' ],
			'real dev site'        => [ 258718, '250001-1000000' ],
			'million'              => [ 1000000, '250001-1000000' ],
			'over a million'       => [ 1000001, '1000000+' ],
			'absurd'               => [ 99999999, '1000000+' ],
		];
	}

	/**
	 * Every bucket label must be a registered enum value, or the allowlist guard
	 * would drop the property and the scan would silently report nothing.
	 *
	 * @since $ver$
	 */
	public function testEveryBucketIsARegisteredEnumValue(): void {
		$legal = (array) Schema::enum( 'scale_bucket' );

		foreach ( [ 0, 1, 3, 8, 20, 60, 500, 5000, 25000, 100000, 500000, 5000000 ] as $count ) {
			self::assertContains( SiteScan::bucket( $count ), $legal );
		}
	}

	/**
	 * The scale must reach far enough to distinguish a large install from a huge
	 * one. A single "10000+" bucket puts a 10k-entry site and a 1M-entry site in
	 * the same group, which is the distinction the field exists to make.
	 *
	 * @since $ver$
	 */
	public function testTheScaleDistinguishesLargeFromHuge(): void {
		self::assertNotSame( SiteScan::bucket( 10001 ), SiteScan::bucket( 900000 ) );
		self::assertNotSame( SiteScan::bucket( 900000 ), SiteScan::bucket( 5000000 ) );
	}

	/**
	 * The parent theme is what gets reported, and this is the case that matters:
	 * a child named after an agency must resolve to its framework, not leak the
	 * agency's name. Measured on 127,776 legacy installs, 27.6% of theme names
	 * contain the word "child" and 15,556 distinct names appear on exactly one
	 * site, so this is the common case rather than an edge case.
	 *
	 * @since $ver$
	 */
	public function testTheThemeListIsMadeOfParentFrameworks(): void {
		$known = Schema::THEMES;

		self::assertArrayHasKey( 'divi', $known );
		self::assertArrayHasKey( 'astra', $known );
		self::assertArrayHasKey( 'genesis', $known );

		// No entry may be a child theme: a "* Child" slug in this list would
		// defeat the point, since matching it means reporting the child.
		foreach ( $known as $key => $slugs ) {
			foreach ( (array) $slugs as $slug ) {
				self::assertStringNotContainsStringIgnoringCase(
					'child',
					$slug,
					sprintf( 'Theme "%s" lists a child slug; the list must contain parents only.', $key )
				);
			}
		}
	}

	/**
	 * @since $ver$
	 */
	public function testEveryThemeKeyIsARegisteredEnumValue(): void {
		$legal = (array) Schema::enum( 'theme' );

		foreach ( array_keys( Schema::THEMES ) as $key ) {
			self::assertContains( $key, $legal );
		}

		self::assertContains( 'other', $legal, 'An unrecognised parent must have somewhere to land.' );
	}

}
