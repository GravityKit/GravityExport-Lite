<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;

/**
 * Class FileUploadField
 * @since 1.1.0
 */
class FileUploadField extends BaseField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        if (!$this->showFileUploadsAsColumn()) {
            return [];
        }
        return parent::getCells($entry);
    }

    /**
     * @inheritdoc
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if (!$this->showFileUploadsAsColumn()) {
            return []; // no columns
        }
        return parent::getColumns();
    }

	/**
	 * Whether the uploads should be shown as a column.
	 *
	 * @since 1.1.0
	 *
	 * @return bool Whether the uploads should be shown as a column.
	 */
	private function showFileUploadsAsColumn() {

		// The default value of `true` will not be returned by get_plugin_setting(); it will return null by default.
		// So we apply the ?? operator and return true as the default value.
		$fileuploads_enabled = GravityExportAddon::get_instance()->get_plugin_setting( 'fileuploads_enabled' ) ?? true;

		return gf_apply_filters( [
			'gfexcel_field_fileuploads_enabled',
			$this->field->formId,
		], (bool) $fileuploads_enabled );
	}
}
