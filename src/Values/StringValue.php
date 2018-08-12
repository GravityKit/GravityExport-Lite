<?php

namespace GFExcel\Values;

use GFExcel\GFExcelAdmin;

class StringValue extends BaseValue
{
    public function __construct($value)
    {
        parent::__construct($value);

        if ($this->isUrl($value) && !!GFExcelAdmin::get_instance()->get_plugin_setting('hyperlinks_enabled')) {
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