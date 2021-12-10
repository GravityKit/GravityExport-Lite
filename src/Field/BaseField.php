<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

/**
 * A Field that serves as the base Field for retrieving columns rows.
 * @since 1.0.0
 */
class BaseField extends AbstractField
{
    /**
     * Array of needed cell values for this field
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
	    $value = $this->filter_value( $this->getFieldValue( $entry ), $entry );

        return $this->wrap([$value]);
    }
}
