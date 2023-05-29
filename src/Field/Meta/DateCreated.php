<?php

namespace GFExcel\Field\Meta;

use GFExcel\Field\MetaField;
use GFExcel\Values\BaseValue;

class DateCreated extends MetaField
{
    /**
     * {@inheritdoc}
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if ($this->useSeparatedFields()) {
            return $this->wrap([
                __('Date', 'gk-gravityexport-lite'),
                __('Time', 'gk-gravityexport-lite'),
            ], true);
        }

        return parent::getColumns();
    }

    /**
     * {@inheritdoc}
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        if ($this->useSeparatedFields()) {
            $value = $this->getFieldValue($entry);

            if ($date = date_create_from_format("Y-m-d H:i:s", $value)) {
                return $this->wrap([$date->format("Y-m-d"), $date->format("H:i:s")]);
            }

            return $this->wrap(['', '']); //no date
        }

        return parent::getCells($entry);
    }

    /**
     * Wether to use separated fields
     * @return bool
     */
    private function useSeparatedFields()
    {
        return gf_apply_filters([
            'gfexcel_meta_date_created_separated',
            $this->field->formId,
            $this->field->id
        ], false);
    }
}
