<?php

namespace GFExcel\Values;

class StringValue extends BaseValue
{
    public function __construct($value)
    {
        parent::__construct($value);

        if ($this->isUrl($value)) {
            $this->setUrl($value);
        }
    }

    /**
     * Quick test if value is a url.
     * @param $value
     * @return bool
     */
    protected function isUrl($value)
    {
        return !!preg_match('%^(https?|ftps?)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?%i', $value);
    }

}