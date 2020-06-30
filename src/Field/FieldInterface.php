<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

interface FieldInterface
{
    /**
     * FieldInterface constructor.
     * @since 1.0.0
     * @param \GF_Field $field
     */
    public function __construct(\GF_Field $field);

    /**
     * Array of needed column names for this field.
     * @since 1.0.0
     * @return BaseValue[]
     */
    public function getColumns();

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @since 1.0.0
     * @return BaseValue[]
     */
    public function getCells($entry);
}
