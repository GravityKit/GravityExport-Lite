<?php

namespace GFExcel\Field;

use GFExcel\GFExcelAdmin;

/**
 * Class SectionField
 * @since 1.1.0
 */
class SectionField extends BaseField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry)
    {
        if (!$this->showSectionAsColumn()) {
            return array(); // no cells
        }

        return parent::getCells($entry);
    }

    public function getColumns()
    {
        if (!$this->showSectionAsColumn()) {
            return array(); // no columns
        }

        return parent::getColumns();
    }

    private function showSectionAsColumn()
    {
        return gf_apply_filters(
            array(
                "gfexcel_field_section_enabled",
                $this->field->formId
            ),
            !!GFExcelAdmin::get_instance()->get_plugin_setting('sections_enabled'));
    }
}