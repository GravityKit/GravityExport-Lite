<?php

namespace GFExcel\Field;

/**
 * Class SectionField
 * @since 1.1.0
 */
class SectionField extends BaseField
{
    private $section_enabled = false;

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
                "ßßgfexcel_field_section_enabled",
                $this->field->formId
            ),
            $this->section_enabled);
    }
}