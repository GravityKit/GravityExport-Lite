<?php

namespace GFExcel\Tests\Repository;

use GFExcel\Repository\FieldsRepository;
use GFExcel\Tests\TestCase;

/**
 * Unit tests for {@see FieldsRepository}.
 * @since TBD
 */
final class FieldsRepositoryTest extends TestCase {
	/**
	 * Creates a Gravity Forms field double.
	 *
	 * Keep the method list identical to {@see \GFExcel\Tests\Transformer\TransformerTest}: PHPUnit
	 * reuses whichever `GF_Field` mock class is declared first.
	 *
	 * @since TBD
	 *
	 * @param array      $properties Public properties to set on the field.
	 * @param array|null $inputs     The entry inputs.
	 *
	 * @return \GF_Field The field double.
	 */
	private function gfField( array $properties, ?array $inputs = null ): \GF_Field {
		$field = $this->getMockBuilder( \stdClass::class )
		              ->setMockClassName( 'GF_Field' )
		              ->setMethods( [ 'get_input_type', 'get_entry_inputs' ] )
		              ->getMock();

		$field->method( 'get_input_type' )->willReturn( $properties['type'] );
		$field->method( 'get_entry_inputs' )->willReturn( $inputs );

		foreach ( $properties as $key => $value ) {
			$field->{$key} = $value;
		}

		return $field;
	}

	/**
	 * A repeater holds no value of its own, and its sub-fields have one value per row, so neither can sort entries.
	 * @since TBD
	 */
	public function testSortOptionsLeaveOutRepeaters(): void {
		\WP_Mock::userFunction( '__', [ 'return_arg' => 0 ] );

		$sub_field = $this->gfField( [ 'id' => 11, 'type' => 'text', 'label' => 'Role' ] );
		$form      = [
			'fields' => [
				$this->gfField( [ 'id' => 1, 'type' => 'text', 'label' => 'Title' ] ),
				$this->gfField( [ 'id' => 10, 'type' => 'repeater', 'label' => 'People', 'fields' => [ $sub_field ] ] ),
				$this->gfField( [ 'id' => 2, 'type' => 'text', 'label' => 'After' ] ),
			],
		];

		$values = array_column( ( new FieldsRepository( $form ) )->getSortFieldOptions(), 'value' );

		self::assertSame( [ 'date_created', 'date_updated', 'id', 1, 2 ], $values );
	}
}
