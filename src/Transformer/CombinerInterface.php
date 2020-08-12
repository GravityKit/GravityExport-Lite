<?php

namespace GFExcel\Transformer;

use GFExcel\Field\FieldInterface;
use GFExcel\Field\RowsInterface;

/**
 * Interface that represents a combiner for the rows.
 * @since $ver$
 */
interface CombinerInterface extends RowsInterface
{
    /**
     * Parses an entry and keeps track of the rows.
     * @since $ver$
     * @param FieldInterface[] $fields The fields to combine.
     * @param array $entry The entry object to use during parsing.
     */
    public function parseEntry(array $fields, array $entry): void;
}
