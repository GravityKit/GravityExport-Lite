<?php

namespace GFExcel\Tests\Upsell;

use GFExcel\Tests\TestCase;
use GFExcel\Upsell\DownloadChart;
use GFExcel\Upsell\DownloadHistory;

/**
 * Accessibility contract for {@see DownloadChart}.
 *
 * A canvas is opaque to assistive technology. Without a text equivalent the
 * chart is not merely hard to read, it does not exist, so these assertions
 * guard a WCAG 1.1.1 Level A obligation rather than a preference.
 *
 * @since $ver$
 */
class DownloadChartTest extends TestCase {
	/**
	 * Renders the markup with a stubbed history.
	 *
	 * @since $ver$
	 *
	 * @return string The markup.
	 */
	private function markup(): string {
		\WP_Mock::userFunction( 'add_filter' )->andReturn( true );
		\WP_Mock::userFunction( 'add_action' )->andReturn( true );
		\WP_Mock::userFunction( 'get_option' )->andReturn( 'F j, Y' );
		\WP_Mock::userFunction( 'date_i18n' )->andReturnUsing(
			static function ( string $format, int $stamp ): string {
				return gmdate( $format, $stamp );
			}
		);
		\WP_Mock::userFunction( 'number_format_i18n' )->andReturnUsing(
			static function ( $n ): string {
				return (string) $n;
			}
		);
		\WP_Mock::userFunction( 'wp_json_encode' )->andReturnUsing( 'json_encode' );
		\WP_Mock::userFunction( 'wp_strip_all_tags' )->andReturnUsing( 'strip_tags' );
		\WP_Mock::userFunction( '_n' )->andReturnUsing(
			static function ( string $single, string $plural, int $n ): string {
				return 1 === $n ? $single : $plural;
			}
		);

		$history = $this->createMock( DownloadHistory::class );
		$history->method( 'series' )->willReturn( [ '2026-08-02' => 0, '2026-08-03' => 3, '2026-08-04' => 1 ] );

		$chart  = new DownloadChart( $history );
		$method = new \ReflectionMethod( DownloadChart::class, 'markup' );
		$method->setAccessible( true );

		return (string) $method->invoke( $chart, 7 );
	}

	/**
	 * @since $ver$
	 */
	public function testTheCanvasCarriesAnAccessibleName(): void {
		$html = $this->markup();

		self::assertRegExp( '/<canvas[^>]*role="img"/', $html );
		self::assertRegExp( '/<canvas[^>]*aria-label="[^"]+"/', $html );
	}

	/**
	 * Every value in the chart must also exist as text, or a screen reader user
	 * gets a summary sentence and loses the shape the chart exists to show.
	 *
	 * @since $ver$
	 */
	public function testEveryDataPointHasATextEquivalent(): void {
		$html = $this->markup();

		self::assertRegExp( '/<table[^>]*screen-reader-text/', $html );
		self::assertRegExp( '/<caption>[^<]+<\/caption>/', $html );
		self::assertSame( 3, substr_count( $html, '<tr><th scope="row">' ) );
		self::assertRegExp( '/<th scope="col">/', $html );
	}

	/**
	 * A form is not permitted inside a paragraph. The parser closes the
	 * paragraph early and relocates the form, which produces markup nobody
	 * wrote and a stray empty element.
	 *
	 * @since $ver$
	 */
	public function testNoticesDoNotNestAFormInsideAParagraph(): void {
		foreach ( [ 'src/Upsell/DownloadMilestone.php', 'src/Analytics/ConsentCard.php' ] as $file ) {
			$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/' . $file );

			self::assertNotRegExp(
				'/<p>\s*(<\?php.*?\?>\s*)?<form/s',
				$source,
				sprintf( '%s nests a form inside a paragraph.', $file )
			);
		}
	}

	/**
	 * WordPress's is-dismissible X removes the notice from the DOM with no
	 * request, so it records nothing and the notice returns on the next page
	 * load. A dismiss control that does not dismiss is worse than none: it
	 * teaches the user that this plugin's buttons do not work.
	 *
	 * @since $ver$
	 */
	public function testTheMilestoneNoticeDoesNotClaimToBeDismissible(): void {
		$source = (string) file_get_contents( dirname( __DIR__, 2 ) . '/src/Upsell/DownloadMilestone.php' );

		self::assertStringNotContainsString(
			'is-dismissible',
			$source,
			'The X would remove the notice without recording the choice, so it returns on the next page load.'
		);
	}
}
