<?php

/**
 * A `GF_Field` stand-in with array access, for tests that run in a separate process.
 *
 * Other tests declare `GF_Field` as a PHPUnit double without array access, so only require this
 * file in a process where no `GF_Field` exists yet.
 *
 * @since 2.7.7
 */
class GF_Field extends stdClass implements ArrayAccess {
	/**
	 * The value {@see GF_Field::get_value_export()} returns.
	 *
	 * @since 2.7.7
	 *
	 * @var mixed
	 */
	public $export_value = '';

	/**
	 * Returns the field type.
	 *
	 * @since 2.7.7
	 *
	 * @return string
	 */
	public function get_input_type() {
		return $this->type ?? '';
	}

	/**
	 * Returns the configured export value.
	 *
	 * @since 2.7.7
	 *
	 * @return mixed
	 */
	public function get_value_export( $entry, $input_id = '', $use_text = false, $is_csv = false ) {
		return $this->export_value;
	}

	#[\ReturnTypeWillChange]
	public function offsetExists( $offset ) {
		return isset( $this->$offset );
	}

	#[\ReturnTypeWillChange]
	public function offsetGet( $offset ) {
		return $this->$offset ?? '';
	}

	#[\ReturnTypeWillChange]
	public function offsetSet( $offset, $value ) {
		$this->$offset = $value;
	}

	#[\ReturnTypeWillChange]
	public function offsetUnset( $offset ) {
		unset( $this->$offset );
	}
}
