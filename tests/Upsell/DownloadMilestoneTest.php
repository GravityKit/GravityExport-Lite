<?php

namespace GFExcel\Tests\Upsell;

use GFExcel\Tests\TestCase;
use GFExcel\Upsell\DownloadMilestone;

/**
 * Unit tests for {@see DownloadMilestone}.
 *
 * @since $ver$
 */
class DownloadMilestoneTest extends TestCase {
	/**
	 * The class under test.
	 *
	 * @since $ver$
	 * @var DownloadMilestone
	 */
	private $milestone;

	/**
	 * @inheritdoc
	 * @since $ver$
	 */
	public function setUp(): void {
		parent::setUp();

		\WP_Mock::userFunction( 'add_action' )->andReturn( true );

		$this->milestone = new DownloadMilestone();
	}

	/**
	 * @since $ver$
	 */
	public function testTheLadderIsAscendingAndLogScaled(): void {
		$ladder = $this->milestone->milestones();

		self::assertNotEmpty( $ladder );

		$sorted = $ladder;
		sort( $sorted );
		self::assertSame( $sorted, $ladder, 'The ladder must be ascending.' );

		// Each rung is a meaningful multiple of the last. A linear ladder either
		// fires constantly on a busy site or never on a quiet one.
		for ( $i = 1, $count = count( $ladder ); $i < $count; $i++ ) {
			self::assertGreaterThanOrEqual(
				2,
				$ladder[ $i ] / $ladder[ $i - 1 ],
				'Rungs must at least double, or the prompt becomes a nag.'
			);
		}
	}

	/**
	 * @since $ver$
	 *
	 * @dataProvider dueCases
	 *
	 * @param int      $total    Downloads so far.
	 * @param int      $shown    Highest milestone already celebrated.
	 * @param int|null $expected The milestone that should fire.
	 */
	public function testWhichMilestoneIsDue( int $total, int $shown, ?int $expected ): void {
		\WP_Mock::userFunction( 'get_option' )->andReturnUsing(
			static function ( string $option, $default = false ) use ( $total, $shown ) {
				if ( DownloadMilestone::OPTION_TOTAL === $option ) {
					return $total;
				}

				if ( DownloadMilestone::OPTION_SHOWN === $option ) {
					return $shown;
				}

				return $default;
			}
		);

		self::assertSame( $expected, $this->milestone->due() );
	}

	/**
	 * @since $ver$
	 *
	 * @return array[] The cases.
	 */
	public function dueCases(): array {
		return [
			'nothing yet'                 => [ 0, 0, null ],
			'below the first rung'        => [ 24, 0, null ],
			'exactly the first rung'      => [ 25, 0, 25 ],
			'past it, not yet celebrated' => [ 60, 0, 25 ],
			'already celebrated'          => [ 60, 25, null ],
			'next rung reached'           => [ 120, 25, 100 ],
			// A site that arrives already past several rungs gets congratulated
			// once on the biggest, not marched through every one it skipped.
			'arrives far past several'    => [ 7000, 0, 5000 ],
			'nothing left to celebrate'   => [ 200000, 100000, null ],
		];
	}

	/**
	 * The ladder is filterable, so a site can silence it entirely by emptying it.
	 *
	 * @since $ver$
	 */
	public function testAnEmptyLadderNeverFires(): void {
		\WP_Mock::onFilter( 'gfexcel_download_milestones' )
		        ->with( [ 25, 100, 500, 1000, 5000, 10000, 50000, 100000 ] )
		        ->reply( [] );

		\WP_Mock::userFunction( 'get_option' )->andReturn( 999999 );

		self::assertNull( $this->milestone->due() );
	}

	/**
	 * @since $ver$
	 */
	public function testTheCooldownIsLongerThanAReviewPrompt(): void {
		// Popup Maker waits two weeks before re-asking for a review. This asks
		// for money, so it waits longer.
		self::assertGreaterThan( 14 * DAY_IN_SECONDS, DownloadMilestone::COOLDOWN );
	}
}
