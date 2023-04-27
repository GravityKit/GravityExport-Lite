<?php

namespace GFExcel\Values;

use GFExcel\Addon\GravityExportAddon;

/**
 * Value object that represents a string.
 * @since 1.3.0
 */
class StringValue extends BaseValue {
	/**
	 * @inheritDoc
	 * @since 1.3.0
	 */
	public function __construct( $value, \GF_Field $gf_field ) {
		parent::__construct( $value, $gf_field );

		$this->setUrlAsLink();
	}

	/**
	 * Check if the value is a URL, and set that URL as a link on the cell.
	 * @since 1.3.0
	 */
	protected function setUrlAsLink(): void {
		if (
			is_string( $this->value )
			&& $this->isUrl( $this->value )
			&& $this->hasHyperlinksEnabled()
		) {
			$this->setUrl( $this->value );
		}
	}

	/**
	 * Quick test if value is a URL.
	 *
	 * @param string $value The value.
	 *
	 * @return bool Whether the value is a URL.
	 */
	protected function isUrl( string $value ): bool {
		return (bool) preg_match(
			'%^(https?|ftps?)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?%i',
			$value
		);
	}

	/**
	 * Returns whether the `hyperlinks_enabled` setting is true.
	 * @since 1.3.0
	 * @return bool Whether the hyperlinks are enabled.
	 */
	private function hasHyperlinksEnabled(): bool {
		return (bool) ( GravityExportAddon::get_instance()->get_plugin_setting( 'hyperlinks_enabled' ) ?? true );
	}
}
