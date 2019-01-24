<?php

namespace GFExcel\Field;

use GFCommon;
use GFExcel\Repository\FormsRepository;
use GFExcel\Values\BaseValue;
use RGFormsModel;

/**
 * @since 1.3.1
 */
class NotesField extends BaseField
{
    /** @var bool|null */
    private $show_notes; //microcache

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        if (!$this->showNotesAsColumn()) {
            return []; // no cells
        }
        $notes = RGFormsModel::get_lead_notes($entry['id']);

        $value = array_reduce($notes, function ($carry, $note) {
            if ($carry !== '') {
                $carry .= "\n";
            }

            $carry .= sprintf(
                '%s: %s',
                esc_html(GFCommon::format_date($note->date_created, false)),
                $note->value
            );

            return $carry;
        }, '');

        $value = gf_apply_filters([
            'gfexcel_notes_value',
            $this->field->formId,
        ], $value, $notes);

        return $this->wrap([$value]);
    }

    /**
     * {@inheritdoc}
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if (!$this->showNotesAsColumn()) {
            return []; // no columns
        }

        return parent::getColumns();
    }

    /**
     * Whether to show notes column
     * @return bool
     */
    private function showNotesAsColumn()
    {
        if ($this->show_notes === null) {
            $this->show_notes = (new FormsRepository($this->field->formId))->showNotes();
        }
        return $this->show_notes;
    }
}
