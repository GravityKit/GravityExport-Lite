<?php

namespace GFExcel\Field;

use GFExcel\Transformer\Transformer;
use GFExcel\Values\BaseValue;

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
     * Whether this repeater sits inside another repeater.
     * @since TBD
     * @var bool
     */
    private $is_nested = false;

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
     *
     * Every row holds exactly one cell per column. A sub-field that returns fewer cells (an empty nested
     * repeater, a hidden input) is padded, so the values after it stay under their own headers.
     *
     * @since 1.8.0
     * @since TBD Rows keep their column positions; row index gaps and non-array values are handled.
     */
    public function getRows(?array $entry = null): array
    {
        $items = $entry[$this->field->id] ?? [];
        if (!is_array($items)) {
            return [];
        }

        $fields = $this->getSubFields();
        $rows = [];

        // Row indexes are not always contiguous, so iterate the rows as they are.
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $row = [];
            foreach ($fields as $field) {
                $row[] = $this->fitCells($field->getCells($item), count($field->getColumns()));
            }

            $rows[] = array_merge([], ...$row);
        }

        return $rows;
    }

    /**
     * Returns the transformers for the repeater's sub-fields.
     *
     * @since TBD
     *
     * @return FieldInterface[] The sub-field transformers.
     */
    private function getSubFields(): array
    {
        return array_map(function (\GF_Field $gf_field): FieldInterface {
            $field = $this->transformer->transform($gf_field);
            if ($field instanceof self) {
                $field->is_nested = true;
            }

            return $field;
        }, (array) $this->field->fields);
    }

    /**
     * Pads or trims a sub-field's cells to its column count.
     *
     * @since TBD
     *
     * @param BaseValue[] $cells The cells the sub-field returned.
     * @param int $count The number of columns the sub-field has.
     *
     * @return BaseValue[] Exactly `$count` cells.
     */
    private function fitCells(array $cells, int $count): array
    {
        $cells = array_slice(array_values($cells), 0, $count);

        if (count($cells) < $count) {
            $cells = array_merge($cells, $this->wrap(array_fill(0, $count - count($cells), '')));
        }

        return $cells;
    }

    /**
     * @inheritdoc
     * Maps all subfields `getCells` calls to the repeater subfields with an amended $entry.
     * @since 1.7.0
     */
    public function getCells($entry)
    {
        // One list of values per column, so an empty repeater still fills its columns.
        $result = array_fill(0, count($this->getColumns()), []);
        foreach ($this->getRows($entry) as $row) {
            foreach ($row as $key => $value) {
                $result[$key][] = $value->getValue();
            }
        }

        // A nested repeater's items share one cell of their parent's row, so they get a lighter separator
        // than the "---" between rows.
        $glue = gf_apply_filters([
            'gfexcel_field_repeater_implode',
            $this->field->formId,
            $this->field->id,
        ], $this->is_nested ? ', ' : "\n---\n");

        $cells = array_map(static function (array $values) use ($glue): string {
            // Keep empty values so the Nth value in every column belongs to the same row.
            $has_values = array_filter($values, static function ($value): bool {
                return '' !== (string) $value;
            });

            return $has_values ? implode($glue, $values) : '';
        }, $result);

        // re-wrap values into cells.
        return $this->wrap($cells);
    }
}
