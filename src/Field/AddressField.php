<?php

namespace GFExcel\Field;

use GF_Field;

/**
 * @deprecated Use SeperableField instead.
 */
class AddressField extends SeparableField
{
    public function __construct(GF_Field $field)
    {
        trigger_error(__CLASS__ . ' is deprecated. Please use ' . SeparableField::class, E_USER_DEPRECATED);
        parent::__construct($field);
    }
}
