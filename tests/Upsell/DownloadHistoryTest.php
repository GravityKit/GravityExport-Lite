<?php

namespace GFExcel\Tests\Upsell;

use GFExcel\Tests\TestCase;
use GFExcel\Upsell\DownloadHistory;

/**
 * Unit tests for {@see DownloadHistory}.
 *
 * @since $ver$
 */
class DownloadHistoryTest extends TestCase {
	/**
	 * Stored option values, keyed by option name.
	 *
	 * @since $ver$
	 * @var array
	 */
	private static $options = [];

	/**
	 * The class under test.
	 *
	 * @since $ver$
	 * @var DownloadHistory
	 */
	private $history;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		self::$options = [];

		\WP_Mock::userFunction( 'add_action' )->andReturn( true );
		\WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			static function ( string $name, $default = false ) {
				return self::$options[ $name ] ?? $default;
			}
		);
		\WP_Mock::userFunction( 'update_option' )->andReturnUsing(
			static function ( string $name, $value ) {
				self::$options[ $name ] = $value;

				return true;
			}
		);
		\WP_Mock::userFunction( 'delete_option' )->andReturnUsing(
			static function ( string $name ) {
				unset( self::$options[ $name ] );

				return true;
			}
		);

		$this->history = new DownloadHistory();
	}

	/**
	 * @since $ver$
	 */
	public function testRecordingAccumulatesPerForm(): void {
		$this->history->record( 7 );
		$this->history->record( 7 );
		$this->history->record( 9 );

		self::assertSame( 2, $this->history->total( 7 ) );
		self::assertSame( 1, $this->history->total( 9 ) );
	}

	/**
	 * @since $ver$
	 */
	public function testAnUnknownFormHasNoHistory(): void {
		self::assertTrue( $this->history->isEmpty( 123 ) );
		self::assertSame( 0, $this->history->total( 123 ) );
	}

	/**
	 * @since $ver$
	 */
	public function testAnInvalidFormIdIsIgnored(): void {
		$this->history->record( 0 );
		$this->history->record( -1 );

		self::assertSame( [], self::$options );
	}

	/**
	 * Quiet days must appear as zero rather than be omitted. A chart drawn from
	 * stored days alone compresses gaps and makes usage look steadier than it
	 * was — a flattering error, which is the kind that costs trust when someone
	 * eventually notices.
	 *
	 * @since $ver$
	 */
	public function testTheSeriesIsZeroFilledAndContiguous(): void {
		self::$options[ DownloadHistory::OPTION_PREFIX . 4 ] = [
			gmdate( 'Ymd' )                             => 3,
			gmdate( 'Ymd', strtotime( '-5 days' ) )     => 2,
		];

		$series = $this->history->series( 4, 7 );

		self::assertCount( 7, $series );
		self::assertSame( 5, array_sum( $series ) );
		self::assertSame( 3, $series[ gmdate( 'Y-m-d' ) ] );
		self::assertSame( 2, $series[ gmdate( 'Y-m-d', strtotime( '-5 days' ) ) ] );
		self::assertSame( 0, $series[ gmdate( 'Y-m-d', strtotime( '-1 days' ) ) ] );

		// Oldest first, so the chart reads left to right.
		self::assertSame( gmdate( 'Y-m-d', strtotime( '-6 days' ) ), array_key_first( $series ) );
		self::assertSame( gmdate( 'Y-m-d' ), array_key_last( $series ) );
	}

	/**
	 * @since $ver$
	 */
	public function testHistoryOutsideTheWindowIsPruned(): void {
		self::$options[ DownloadHistory::OPTION_PREFIX . 5 ] = [
			gmdate( 'Ymd', strtotime( '-200 days' ) ) => 99,
			gmdate( 'Ymd', strtotime( '-10 days' ) )  => 4,
		];

		$this->history->record( 5 );

		$stored = self::$options[ DownloadHistory::OPTION_PREFIX . 5 ];

		self::assertArrayNotHasKey( gmdate( 'Ymd', strtotime( '-200 days' ) ), $stored );
		self::assertArrayHasKey( gmdate( 'Ymd', strtotime( '-10 days' ) ), $stored );
		self::assertLessThanOrEqual( DownloadHistory::RETENTION_DAYS + 1, count( $stored ) );
	}

	/**
	 * @since $ver$
	 */
	public function testForgettingRemovesTheHistory(): void {
		$this->history->record( 11 );
		self::assertFalse( $this->history->isEmpty( 11 ) );

		$this->history->forget( 11 );

		self::assertTrue( $this->history->isEmpty( 11 ) );
	}
}
