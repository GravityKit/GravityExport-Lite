<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

class ListField extends BaseField
{
    private $columns;

    /**
     * Array of needed column names for this field.
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if (!$this->columns) {
            if (!$this->field['enableColumns']) {
                //no columns, so we can just use the field name, and a single column
                $this->columns = parent::getColumns(); //micro caching
                return $this->columns;
            }

            //Multiple columns, so lets get their names, and return multiple columns.
            $this->columns = array_map(function ($choice) {
                return $choice['value'];
            }, (array) $this->field['choices']);
        }

        return $this->wrap($this->columns, true);
    }

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        if (!$this->field['enableColumns']) {
            // One column, so the input is a single string
            return parent::getCells($entry);
        }

        //Multiple columns, let's go
        $value = $this->getFieldValue($entry);
        if (!$result = json_decode($value)) {
            //the value isn't json, so it's empty, we'll map every column as empty
            return $this->wrap(array_map(static function () {
                return '';
            }, $this->getColumns()));
        }

        //We have an object with multiple columns. Map the values to their column
        $result = array_values(array_reduce($result, function (array $carry, $row) {
            foreach ($this->getColumns() as $column) {
                if ($column instanceof BaseValue) {
                    $column = $column->getValue();
                }
                if (!array_key_exists($column, $carry)) {
                    $carry[$column] = array();
                }
                $carry[$column][] = $row->$column ?? '';
            }
            return $carry;
        }, []));

        // Every value on it's own line for readability.
        // Should this have a filter? Not sure.
        return $this->wrap(array_map(static function (array $column) {
            return implode("\n", $column);
        }, $result));
    }
}
