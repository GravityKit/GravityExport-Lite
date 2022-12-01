<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;

/**
 * Class SectionField
 * @since 1.1.0
 */
class SectionField extends BaseField {
	/**
	 * @inheritdoc
	 * @return BaseValue[]
	 */
	public function getCells( $entry ) {
		if ( ! $this->showSectionAsColumn() ) {
			return []; // no cells
		}

		return parent::getCells( $entry );
	}

	/**
	 * @inheritdoc
	 * @return BaseValue[]
	 */
	public function getColumns() {
		if ( ! $this->showSectionAsColumn() ) {
			return []; // no columns
		}

		return parent::getColumns();
	}

	/**
	 * Whether to show sections as an (empty) column
	 * @return bool
	 */
	private function showSectionAsColumn(): bool {
		return gf_apply_filters( [
			'gfexcel_field_section_enabled',
			$this->field->formId,
		], (bool) GravityExportAddon::get_instance()->get_plugin_setting( 'sections_enabled' ) );
	}
}
