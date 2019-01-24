<?php

namespace GFExcel\Values;

use GFExcel\GFExcelAdmin;

class StringValue extends BaseValue
{
    public function __construct($value, \GF_Field $gf_field)
    {
        parent::__construct($value, $gf_field);
        $this->setUrlAsLink();
    }

    /**
     * Check if the value is a url, and set that url as a link on the cell
     */
    protected function setUrlAsLink()
    {
        if ($this->isUrl($this->value) && $this->hasHyperlinksEnabled()) {
            $this->setUrl($this->value);
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

    /**
     * @return bool
     */
    private function hasHyperlinksEnabled()
    {
        return (bool) GFExcelAdmin::get_instance()->get_plugin_setting('hyperlinks_enabled');
    }
}
