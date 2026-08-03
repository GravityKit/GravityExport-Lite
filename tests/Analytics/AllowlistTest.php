<?php

namespace GFExcel\Tests\Analytics;

use GFExcel\Analytics\Allowlist;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see Allowlist}.
 *
 * @since $ver$
 */
class AllowlistTest extends TestCase {
	/**
	 * The class under test.
	 *
	 * @since $ver$
	 * @var Allowlist
	 */
	private $allowlist;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		$this->allowlist = new Allowlist();
	}

	/**
	 * @since $ver$
	 */
	public function testRegisteredEventIsAllowed(): void {
		self::assertTrue( $this->allowlist->allowsEvent( 'export_completed' ) );
	}

	/**
	 * A typo must be rejected outright rather than sent and ignored downstream.
	 *
	 * @since $ver$
	 */
	public function testUnregisteredEventIsRejected(): void {
		self::assertFalse( $this->allowlist->allowsEvent( 'export_complete' ) );
	}

	/**
	 * @since $ver$
	 */
	public function testUnregisteredPropKeyIsDropped(): void {
		$result = $this->allowlist->filterProps(
			[
				'file_format' => 'csv',
				'entry_email' => 'someone@example.com',
			]
		);

		self::assertSame( [ 'file_format' => 'csv' ], $result );
	}

	/**
	 * An illegal enum value loses its key, not the whole event — one stray
	 * value must not cost the event it rode in on.
	 *
	 * @since $ver$
	 */
	public function testIllegalEnumValueIsDroppedButSiblingsSurvive(): void {
		$result = $this->allowlist->filterProps(
			[
				'file_format'  => 'docx',
				'column_count' => 4,
			]
		);

		self::assertSame( [ 'column_count' => 4 ], $result );
	}

	/**
	 * @since $ver$
	 */
	public function testLegalEnumValueSurvives(): void {
		$result = $this->allowlist->filterProps( [ 'file_format' => 'pdf' ] );

		self::assertSame( [ 'file_format' => 'pdf' ], $result );
	}

	/**
	 * Non-enum properties are not value-checked, only key-checked.
	 *
	 * @since $ver$
	 */
	public function testNonEnumPropertyValueIsNotConstrained(): void {
		$result = $this->allowlist->filterProps( [ 'column_count' => 9999 ] );

		self::assertSame( [ 'column_count' => 9999 ], $result );
	}

	/**
	 * Drops are counted, so a silent guard is still an observable one. Without
	 * this the taxonomy's reject-and-say-nothing behaviour makes a typo'd event
	 * indistinguishable from an event that was never fired.
	 *
	 * @since $ver$
	 */
	public function testDropsAreTalliedAndPersistedOnFlush(): void {
		$this->allowlist->allowsEvent( 'not_a_real_event' );
		$this->allowlist->filterProps( [ 'nope' => 1 ] );

		$written = null;

		\WP_Mock::userFunction( 'get_option' )->andReturn( [] );
		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $option, $counters ) use ( &$written ): bool {
				$written = [ $option, $counters ];

				return true;
			}
		);

		$this->allowlist->flush();

		self::assertNotNull( $written, 'flush() never wrote the counters.' );
		self::assertSame( Allowlist::COUNTER_OPTION, $written[0] );
		self::assertSame( 1, $written[1]['event:not_a_real_event'] ?? 0 );
		self::assertSame( 1, $written[1]['prop:nope'] ?? 0 );
	}

	/**
	 * @since $ver$
	 */
	public function testFlushWritesNothingWhenNothingWasDropped(): void {
		$called = false;

		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function () use ( &$called ): bool {
				$called = true;

				return true;
			}
		);

		$this->allowlist->flush();

		self::assertFalse( $called, 'flush() wrote an option with nothing to record.' );
	}
}
