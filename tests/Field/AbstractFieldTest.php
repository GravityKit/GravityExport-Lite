<?php
/**
 * Unit tests for AbstractField.
 *
 * @package GFExcel\Tests\Field
 * @since 2.4.2
 */

namespace GFExcel\Tests\Field;

use GFExcel\Field\AbstractField;
use GFExcel\Tests\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

// Load the GFCommon stub if Gravity Forms is not available.
require_once dirname( __DIR__ ) . '/Stubs/GFCommon.php';

/**
 * Unit tests for {@see AbstractField}.
 * @since 2.4.2
 */
class AbstractFieldTest extends TestCase
{
	/**
	 * A mocked field instance.
	 * @since 2.4.2
	 * @var \GF_Field|MockObject
	 */
	protected $gf_field;

	/**
	 * @inheritdoc
	 * @since 2.4.2
	 */
	public function setUp(): void
	{
		parent::setUp();
		$this->gf_field = $this->getMockBuilder( \stdClass::class )
			->setMockClassName( 'GF_Field' )
			->addMethods( [ 'get_input_type', 'get_field_label', 'get_value_export', 'set_context_property' ] )
			->getMock();

		$this->gf_field->id     = 1;
		$this->gf_field->formId = 1;

		// Reset mock state.
		\GFCommon::reset();
	}

	/**
	 * Test case for {@see AbstractField::getGFieldValue()} with empty payment_date.
	 *
	 * Verifies that an empty payment_date returns an empty string instead of the current date/time.
	 *
	 * @since 2.4.2
	 */
	public function testGetGFieldValueWithEmptyPaymentDateReturnsEmptyString(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'           => 1,
			'form_id'      => 1,
			'payment_date' => '',
		];

		$result = $field->getTestGFieldValue( $entry, 'payment_date' );

		$this->assertSame( '', $result, 'Empty payment_date should return empty string, not current date' );
	}

	/**
	 * Test case for {@see AbstractField::getGFieldValue()} with null payment_date.
	 *
	 * Verifies that a null payment_date returns an empty string instead of the current date/time.
	 *
	 * @since 2.4.2
	 */
	public function testGetGFieldValueWithNullPaymentDateReturnsEmptyString(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'           => 1,
			'form_id'      => 1,
			'payment_date' => null,
		];

		$result = $field->getTestGFieldValue( $entry, 'payment_date' );

		$this->assertSame( '', $result, 'Null payment_date should return empty string, not current date' );
	}

	/**
	 * Test case for {@see AbstractField::getGFieldValue()} with missing payment_date key.
	 *
	 * Verifies that a missing payment_date key returns an empty string instead of the current date/time.
	 *
	 * @since 2.4.2
	 */
	public function testGetGFieldValueWithMissingPaymentDateReturnsEmptyString(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'      => 1,
			'form_id' => 1,
			// payment_date key is not set at all.
		];

		$result = $field->getTestGFieldValue( $entry, 'payment_date' );

		$this->assertSame( '', $result, 'Missing payment_date should return empty string, not current date' );
	}

	/**
	 * Test case for {@see AbstractField::getGFieldValue()} with empty date_created.
	 *
	 * Verifies that an empty date_created returns an empty string instead of the current date/time.
	 *
	 * @since 2.4.2
	 */
	public function testGetGFieldValueWithEmptyDateCreatedReturnsEmptyString(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'           => 1,
			'form_id'      => 1,
			'date_created' => '',
		];

		$result = $field->getTestGFieldValue( $entry, 'date_created' );

		$this->assertSame( '', $result, 'Empty date_created should return empty string, not current date' );
	}

	/**
	 * Test case for {@see AbstractField::getGFieldValue()} with valid payment_date.
	 *
	 * Verifies that a valid payment_date is properly formatted.
	 *
	 * @since 2.4.2
	 */
	public function testGetGFieldValueWithValidPaymentDateReturnsFormattedDate(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'           => 1,
			'form_id'      => 1,
			'payment_date' => '2025-06-15 14:30:00',
		];

		$timestamp = 1718455800;

		\WP_Mock::userFunction( 'mysql2date', [
			'args'   => [ 'G', '2025-06-15 14:30:00' ],
			'return' => $timestamp,
		] );

		// Set the mock timestamp for GFCommon::get_local_timestamp.
		\GFCommon::$mock_timestamp = $timestamp;

		\WP_Mock::userFunction( 'date_i18n', [
			'args'   => [ 'Y-m-d H:i:s', $timestamp, true ],
			'return' => '2025-06-15 14:30:00',
		] );

		$result = $field->getTestGFieldValue( $entry, 'payment_date' );

		$this->assertSame( '2025-06-15 14:30:00', $result );
	}

	/**
	 * Test case to verify the fix prevents current date fallback.
	 *
	 * This test demonstrates the bug that was fixed: when payment_date is empty,
	 * the old code would return the current date/time instead of an empty string.
	 *
	 * @since 2.4.2
	 */
	public function testEmptyPaymentDateDoesNotReturnCurrentDate(): void
	{
		$field = new ConcreteAbstractField( $this->gf_field );

		$entry = [
			'id'           => 1,
			'form_id'      => 1,
			'payment_date' => '',
		];

		$result = $field->getTestGFieldValue( $entry, 'payment_date' );

		// The result should NOT be a date string (which would be 19 characters: YYYY-MM-DD HH:MM:SS).
		$this->assertNotEquals( 19, strlen( $result ), 'Empty payment_date should not return a formatted date string' );

		// The result should be an empty string.
		$this->assertEmpty( $result, 'Empty payment_date should return empty value' );
	}
}

/**
 * Concrete implementation of AbstractField for testing.
 * @since 2.4.2
 */
class ConcreteAbstractField extends AbstractField
{
	/**
	 * Constructor override to skip admin label setup for testing.
	 * @since 2.4.2
	 *
	 * @param \GF_Field $field The GF_Field instance.
	 */
	public function __construct( \GF_Field $field )
	{
		$this->field = $field;
	}

	/**
	 * @inheritdoc
	 */
	public function getCells( $entry )
	{
		return $this->wrap( [ $this->getFieldValue( $entry ) ] );
	}

	/**
	 * Expose getGFieldValue for testing.
	 * @since 2.4.2
	 *
	 * @param array  $entry    The entry.
	 * @param string $input_id The input ID.
	 *
	 * @return mixed The field value.
	 */
	public function getTestGFieldValue( array $entry, string $input_id )
	{
		return $this->getGFieldValue( $entry, $input_id );
	}
}
