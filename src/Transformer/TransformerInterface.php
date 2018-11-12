<?php

namespace GFExcel\Transformer;

use GF_Field;
use GFExcel\Field\FieldInterface;

interface TransformerInterface
{
    /**
     * Transform GF_Field instance to a GFExel Field (FieldInterface)
     * @param GF_Field $field
     * @return FieldInterface
     */
    public function transform(GF_Field $field);
}
