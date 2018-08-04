<?php

namespace GFExcel\Field;

use GFExcel\GFExcelAdmin;

/**
 * Class FileUploadField
 * @since 1.1.0
 */
class FileUploadField extends BaseField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry)
    {
        if (!$this->showFileUploadsAsColumn()) {
            return array(); // no cells
        }
        return parent::getCells($entry);
    }

    public function getColumns()
    {
        if (!$this->showFileUploadsAsColumn()) {
            return array(); // no columns
        }
        return parent::getColumns();

    }

    private function showFileUploadsAsColumn()
    {
        return gf_apply_filters(
            array(
                "gfexcel_field_fileuploads_enabled",
                $this->field->formId
            ),
            !!GFExcelAdmin::get_instance()->get_plugin_setting('fileuploads_enabled'));
    }
}