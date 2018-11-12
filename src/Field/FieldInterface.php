<?php

namespace GFExcel\Field;

use GF_Field;
use GFExcel\Values\BaseValue;

interface FieldInterface
{

    /**
     * FieldInterface constructor.
     * @param GF_Field $field
     */
    public function __construct(GF_Field $field);

    /**
     * Array of needed column names for this field.
     * @return BaseValue[]
     */
    public function getColumns();

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry);
}
