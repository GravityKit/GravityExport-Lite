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
     * How many repeaters this repeater sits inside: 0 for a repeater on the form itself.
     * @since 2.7.6
     * @var int
     */
    private $depth = 0;

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
     * @since 2.7.6 Rows keep their column positions; row index gaps and non-array values are handled.
     */
    public function getRows(?array $entry = null): array
    {
        return array_map(static function (array $parts): array {
            return array_column($parts, 'cell');
        }, $this->getRowParts($entry));
    }

    /**
     * Returns every repeater row as one part per column: the cell, and whether it holds a value.
     *
     * @since 2.7.6
     *
     * @param array|null $entry The entry, or a row of the parent repeater.
     *
     * @return array<int, array{cell: BaseValue, filled: bool}>[] The rows.
     */
    private function getRowParts(?array $entry): array
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
                // A nested repeater says which of its columns hold values, so an empty "[]" group is not one.
                if ($field instanceof self) {
                    foreach ($field->joinColumns($field->getRowParts($item)) as $part) {
                        $row[] = $part;
                    }

                    continue;
                }

                foreach ($this->fitCells($field->getCells($item), count($field->getColumns())) as $cell) {
                    $row[] = ['cell' => $cell, 'filled' => '' !== (string) $cell->getValue()];
                }
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Returns the transformers for the repeater's sub-fields.
     *
     * @since 2.7.6
     *
     * @return FieldInterface[] The sub-field transformers.
     */
    private function getSubFields(): array
    {
        return array_map(function (\GF_Field $gf_field): FieldInterface {
            $field = $this->transformer->transform($gf_field);
            if ($field instanceof self) {
                $field->depth = $this->depth + 1;
            }

            return $field;
        }, (array) $this->field->fields);
    }

    /**
     * Pads or trims a sub-field's cells to its column count.
     *
     * @since 2.7.6
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
     * Joins each column's values across the rows into one cell.
     *
     * Rows are separated by "---". A nested repeater's items share one cell of their parent's row and are
     * separated by ", "; from the second nested level on, each group is wrapped in brackets, so
     * `[a, b], []` tells which parent item each value belongs to.
     *
     * @since 2.7.6
     *
     * @param array<int, array{cell: BaseValue, filled: bool}>[] $rows The rows from {@see getRowParts()}.
     *
     * @return array{cell: BaseValue, filled: bool}[] One part per column.
     */
    private function joinColumns(array $rows): array
    {
        $columns = array_fill(0, count($this->getColumns()), []);
        foreach ($rows as $row) {
            foreach ($row as $key => $part) {
                $columns[$key][] = $part;
            }
        }

        /**
         * Modifies the separator between a Repeater field's values in one export cell.
         *
         * Used when an export puts all of a Repeater's rows in one cell (without GravityExport's "Multiple
         * Rows"). For a Repeater on the form it separates the rows, by default with a line holding `---`. For a
         * Repeater inside another Repeater it separates that nested Repeater's items inside one row of its
         * parent, by default with a comma and a space; before 2.7.6 that was also a line holding `---`.
         * The field ID in the hook name is the Repeater's own, so a nested Repeater can get its own separator.
         *
         * @filter gfexcel_field_repeater_implode
         *
         * @since 1.7.0
         *
         * @param string $glue The separator: `"\n---\n"` for a Repeater on the form, a comma and a space for a
         *                     Repeater inside another Repeater.
         */
        $glue = gf_apply_filters([
            'gfexcel_field_repeater_implode',
            $this->field->formId,
            $this->field->id,
        ], $this->depth > 0 ? ', ' : "\n---\n");

        return array_map(function (array $parts) use ($glue): array {
            $filled = in_array(true, array_column($parts, 'filled'), true);

            // Keep empty values so the Nth value in every column belongs to the same row.
            $value = $filled ? implode($glue, array_map(static function (array $part): string {
                return (string) $part['cell']->getValue();
            }, $parts)) : '';

            if ($this->depth > 1) {
                $value = '[' . $value . ']';
            }

            return ['cell' => $this->wrap([$value])[0], 'filled' => $filled];
        }, $columns);
    }

    /**
     * @inheritdoc
     * Maps all subfields `getCells` calls to the repeater subfields with an amended $entry.
     * @since 1.7.0
     * @since 2.7.6 A repeater with one row returns its sub-fields' own cells, so numbers and prices keep their type.
     */
    public function getCells($entry)
    {
        $rows = $this->getRowParts($entry);

        if (1 === count($rows)) {
            return array_column($rows[0], 'cell');
        }

        return array_column($this->joinColumns($rows), 'cell');
    }
}
