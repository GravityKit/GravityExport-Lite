<?php

namespace GFExcel\Tests\GravityForms\Field;

use GFExcel\GravityForms\Field\SortFields;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see SortFields}.
 * @since TBD
 */
class SortFieldsTest extends TestCase {
	/**
	 * Arabic plural rule: one form for 0, 1, 2, 3 to 10, 11 to 99, and 100 or more.
	 *
	 * @since TBD
	 *
	 * @param int $count The number.
	 *
	 * @return int The plural form index.
	 */
	private static function arabic_plural_form( int $count ): int {
		if ( 0 === $count ) {
			return 0;
		}

		if ( 1 === $count ) {
			return 1;
		}

		if ( 2 === $count ) {
			return 2;
		}

		if ( $count % 100 >= 3 && $count % 100 <= 10 ) {
			return 3;
		}

		return $count % 100 >= 11 ? 4 : 5;
	}

	/**
	 * {@inheritDoc}
	 * @since TBD
	 */
	public function setUp(): void {
		parent::setUp();

		$forms = [
			'[count] fields match your search.'       => [ '[count] (0)', '[count] حقل يطابق &quot;بحثك&quot;', '[count] حقلان يطابقان', '[count] حقول تطابق', '[count] حقلاً يطابق', '[count] حقل يطابق' ],
			'[count] fields moved to [destination].' => [ '[count] (0)', '[count] حقل نُقل إلى [destination]', '[count] حقلان نُقلا إلى [destination]', '[count] حقول نُقلت إلى [destination]', '[count] حقلاً نُقل إلى [destination]', '[count] حقل نُقل إلى [destination]' ],
		];

		\WP_Mock::userFunction( '_n', [
			'return' => function ( $single, $plural, $count, $domain ) use ( $forms ) {
				$this->assertSame( 'gk-gravityexport-lite', $domain );

				return $forms[ $plural ][ self::arabic_plural_form( $count ) ];
			},
		] );

		// Marked, so a test can tell the localised number from the raw one.
		\WP_Mock::userFunction( 'number_format_i18n', [
			'return' => function ( $number ) {
				return '«' . $number . '»';
			},
		] );

		\WP_Mock::userFunction( 'wp_json_encode', [
			'return' => function ( $data, $flags = 0 ) {
				return json_encode( $data, $flags );
			},
		] );

		\WP_Mock::userFunction( 'esc_attr', [
			'return' => function ( $text ) {
				// Like esc_attr(), an entity already in the text is not encoded again.
				return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8', false );
			},
		] );
	}

	/**
	 * Each count gets the sentence its language uses for that number, not one sentence for every count from 2 up.
	 * @since TBD
	 */
	public function testCountMessagesUseThePluralFormOfEachCount(): void {
		$messages = SortFields::get_count_messages( 13 );

		$this->assertSame( '«1» حقل يطابق &quot;بحثك&quot;', $messages['match'][1] );
		$this->assertSame( '«2» حقلان يطابقان', $messages['match'][2] );
		$this->assertSame( '«5» حقول تطابق', $messages['match'][5] );
		$this->assertSame( '«13» حقلاً يطابق', $messages['match'][13] );

		$this->assertSame( '«2» حقلان نُقلا إلى [destination]', $messages['moved'][2] );
		$this->assertSame( '«5» حقول نُقلت إلى [destination]', $messages['moved'][5] );
		$this->assertSame( '«13» حقلاً نُقل إلى [destination]', $messages['moved'][13] );
	}

	/**
	 * The attribute the browser decodes gives back every message unchanged, including an entity a translation contains.
	 * @since TBD
	 */
	public function testCountMessagesAttributeDecodesToTheMessages(): void {
		$attribute = SortFields::get_count_messages_attribute( 13 );

		// What the browser does before jQuery parses the JSON.
		$decoded = json_decode( html_entity_decode( $attribute, ENT_QUOTES, 'UTF-8' ), true );

		$this->assertSame( SortFields::get_count_messages( 13 ), $decoded );
		$this->assertStringContainsString( 'حقل', $attribute );
	}

	/**
	 * Every count the lists can show has a message, and none beyond it.
	 * @since TBD
	 */
	public function testCountMessagesCoverEveryCountUpToTheTotal(): void {
		$messages = SortFields::get_count_messages( 4 );

		$this->assertSame( [ 1, 2, 3, 4 ], array_keys( $messages['match'] ) );
		$this->assertSame( [ 1, 2, 3, 4 ], array_keys( $messages['moved'] ) );
		$this->assertSame( [ 'match' => [], 'moved' => [] ], SortFields::get_count_messages( 0 ) );
	}
}
