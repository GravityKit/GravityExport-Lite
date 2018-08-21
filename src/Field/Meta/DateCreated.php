<?php


namespace GFExcel\Field\Meta;


use GFExcel\Field\BaseField;
use GFExcel\GFExcel;

class DateCreated extends BaseField
{

    public function getColumns()
    {
        if ($this->useSeperatedFields()) {
            return $this->wrap(array(__('Date', GFExcel::$slug), __('Time', GFExcel::$slug)), true);
        }

        return parent::getColumns();
    }

    public function getCells($entry)
    {
        if ($this->useSeperatedFields()) {
            $value = $this->getFieldValue($entry);

            if ($date = date_create_from_format("Y-m-d H:i:s", $value)) {
                return $this->wrap(array($date->format("Y-m-d"), $date->format("H:i:s")));
            }

            return array('', '',); //no date
        }

        return parent::getCells($entry);
    }

    private function useSeperatedFields()
    {
        return gf_apply_filters(
            array(
                "gfexcel_meta_date_created_seperated",
                $this->field->formId,
                $this->field->id
            ), false);
    }

}