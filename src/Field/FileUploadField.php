<?php

namespace GFExcel\Field;

use GFExcel\GFExcelAdmin;
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
     * @return BaseValue
     */
    public function getColumns()
    {
        if (!$this->showFileUploadsAsColumn()) {
            return []; // no columns
        }
        return parent::getColumns();

    }

    /**
     * Wether the uploads should be shown as a column
     * @return bool
     */
    private function showFileUploadsAsColumn()
    {
        return gf_apply_filters([
            'gfexcel_field_fileuploads_enabled',
            $this->field->formId
        ], !!GFExcelAdmin::get_instance()->get_plugin_setting('fileuploads_enabled'));
    }
}