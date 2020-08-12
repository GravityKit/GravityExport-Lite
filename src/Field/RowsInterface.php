<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

/**
 * Interface that produces rows for a field.
 * @since $ver$
 */
interface RowsInterface
{
    /**
     * Should return the rows this field needs to render all cells.
     * @since $ver$
     * @param array|null $entry Optional entry object.
     * @return iterable|\Traversable|BaseValue[][] Rows containing cells.
     */
    public function getRows(?array $entry = null): iterable;
}
