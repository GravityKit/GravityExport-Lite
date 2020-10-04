<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

/**
 * Field transformer that represents a List field
 * @since 1.3.0
 */
class ListField extends BaseField implements RowsInterface
{
    private $columns;

    /**
     * Array of needed column names for this field.
     * @return BaseValue[]|string[]
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
            $this->columns = array_map(static function ($choice) {
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
        $rows = iterator_to_array($this->getRows($entry));
        $result = array_reduce($rows, static function (array $combined, array $row): array {
            foreach ($row as $i => $cell) {
                $combined[$i][] = $cell->getValue();
            }

            return $combined;
        }, []);

        // Every value on it's own line for readability.
        return $this->wrap(array_map(static function (array $column) {
            return implode("\n", $column);
        }, $result));
    }

    /**
     * @inheritdoc
     * @since 1.8.0
     */
    public function getRows(?array $entry = null): iterable
    {
        if (!$this->field['enableColumns']) {
            // One column, so the input is a single string
            yield parent::getCells($entry);
        } else {
            $value = $this->getFieldValue($entry);
            if (!$result = json_decode($value, true)) {
                yield $this->wrap(array_map(static function () {
                    return '';
                }, $this->getColumns()));
            } else {
                foreach ($result as $row) {
                    $result = [];
                    foreach ($this->getColumns() as $column) {
                        $column = $column instanceof BaseValue ? $column->getValue() : $column;
                        $result[$column] = $row[$column] ?? null;
                    }
                    yield $this->wrap(array_values($result));
                }
            }
        }
    }
}
