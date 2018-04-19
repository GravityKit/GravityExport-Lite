<?php


namespace GFExcel\Field;


class ListField extends BaseField
{
    private $columns;

    public function getColumns()
    {
        if (!$this->columns) {
            $this->columns = array_map(function ($choice) {
                return $choice['value'];
            }, (array) $this->field['choices']);
        }

        return $this->columns;
    }

    public function getCells($entry)
    {
        $value = $this->field->get_value_export($entry);
        if (!$result = json_decode($value)) {
            return array_map(function () {
                return '';
            }, $this->getColumns());
        }

        $component = $this; //php 5.3 compatible
        $result = array_values(array_reduce($result, function ($carry, $row) use ($component) {
            foreach ($component->getColumns() as $column) {
                if (!array_key_exists($column, $carry)) {
                    $carry[$column] = array();
                }
                $carry[$column][] = $row->$column;
            }
            return $carry;
        }, array()));

        return array_map(function ($column) {
            return implode("\n", $column);
        }, $result);
    }

}