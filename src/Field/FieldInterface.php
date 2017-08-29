<?php

namespace GFExcel\Field;


use GF_Field;

Interface FieldInterface
{

    /**
     * FieldInterface constructor.
     * @param GF_Field $field
     */
    public function __construct(GF_Field $field);

    /**
     * Array of needed column names for this field.
     * @return array
     */
    public function getColumns();

    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return array
     */
    public function getCells($entry);
}