<?php

namespace GFExcel\Transformer;

use GFExcel\Field\RowsInterface;

interface CombinerInterface extends RowsInterface
{
    /**
     * Parses an entry and keeps track of the rows.
     * @since $ver$
     * @param RowsInterface[] $fields The fields to combine.
     * @param array $entry The entry object to use during parsing.
     */
    public function parseEntry(array $fields, array $entry): void;
}
