<?php

namespace GFExcel\Field;

use GFCommon;
use RGFormsModel;

/**
 * Class SectionField
 * @since 1.3.1
 */
class NotesField extends BaseField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry)
    {
        if (!$this->showNotesAsColumn()) {
            return array(); // no cells
        }
        $notes = RGFormsModel::get_lead_notes($entry['id']);

        $value = array_reduce($notes, function ($carry, $note) {
            if ($carry !== '') {
                $carry .= "\n";
            }

            $carry .= sprintf("%s: %s",
                esc_html(GFCommon::format_date($note->date_created, false)),
                $note->value
            );

            return $carry;
        }, '');

        $value = gf_apply_filters(
            array(
                "gfexcel_notes_value",
                $this->field->formId,
            ),
            $value, $notes);

        return $this->wrap([$value]);
    }

    public function getColumns()
    {
        if (!$this->showNotesAsColumn()) {
            return array(); // no columns
        }

        return parent::getColumns();
    }

    private function showNotesAsColumn()
    {
        return gf_apply_filters(
            array(
                "gfexcel_field_notes_enabled",
                $this->field->formId
            ),
            false);
    }
}