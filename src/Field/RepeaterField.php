<?php

namespace GFExcel\Field;

use GFExcel\Transformer\Transformer;

/**
 * A Field for the Transformer for `repeater` fields.
 * @since 1.7.0
 */
class RepeaterField extends SeparableField implements RowsInterface
{
    /**
     * A Transformer instance.
     * @since 1.7.0
     * @var Transformer
     */
    private $transformer;

    /**
     * The GF_Field instance for the Repeater
     * @since 1.7.0
     * @var \GF_Field_Repeater
     */
    protected $field;

    /**
     * @inheritdoc
     * @since 1.7.0
     */
    public function __construct(\GF_Field $field)
    {
        parent::__construct($field);
        $this->transformer = new Transformer();
    }

    /**
     * @inheritdoc
     * Maps all subfields `getColumns` calls to the repeater subfields.
     * @since 1.7.0
     */
    public function getColumns()
    {
        return array_values(array_reduce($this->field->fields, function (array $columns, \GF_Field $field) {
            return array_merge($columns, $this->transformer->transform($field)->getColumns());
        }, []));
    }

    /**
     * @inheritDoc
     * @since 1.8.0
     */
    public function getRows(?array $entry = null): array
    {
        // get repeater entries.
        if (!$entry) {
            return [];
        }

        $entries = $entry[$this->field->id] ?? [];

        // Get the correct field values for every row.
        return array_reduce($entries, function (array $rows, array $entry) {
            $row = [];
            foreach (array_map(function (\GF_Field $gf_field): FieldInterface {
                return $this->transformer->transform($gf_field);
            }, $this->field->fields) as $field) {
                $row[] = $field->getCells($entry);
            }
            $rows[] = array_merge([], ...$row);

            return $rows;
        }, []);
    }

    /**
     * @inheritdoc
     * Maps all subfields `getCells` calls to the repeater subfields with an amended $entry.
     * @since 1.7.0
     */
    public function getCells($entry)
    {
        //flip the array
        $result = [];
        foreach ($this->getRows($entry) as $row) {
            foreach ($row as $key => $value) {
                $result[$key][] = $value->getValue();
            }
        }

        // implode the values into a new string
        $cells = array_map(function (array $values) {
            return implode(
                gf_apply_filters([
                    'gfexcel_field_repeater_implode',
                    $this->field->formId,
                    $this->field->id,
                ], "\n---\n"),
                $values
            );
        }, $result);

        // re-wrap values into cells.
        return $this->wrap($cells);
    }
}
