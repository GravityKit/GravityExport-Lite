<?php

namespace GFExcel;

use GFAddOn;

class GFExcelAdmin extends GFAddOn
{
    private static $file;

    protected $_version;

    protected $_min_gravityforms_version = "1.9";

    protected $_short_title;

    protected $_title;

    protected $_slug;

    public function __construct()
    {
        $this->_version = GFExcel::$version;
        $this->_title = GFExcel::$name;
        $this->_short_title = GFExcel::$shortname;
        $this->_slug = GFExcel::$slug;

        parent::__construct();
    }

    public function form_settings($form)
    {
        printf(
            '<h3>%s</h3>',
            esc_html__(GFExcel::$name, 'gfexcel')
        );

        printf('<p>%s</p>',
            esc_html__('Download url:', 'gfexcel')
        );

        $url = GFExcel::url($form);


        printf(
            "<p>
                <input style='width:80%%;' type='text' value='%s' readonly />
            </p>",
            $url
        );

        printf(
            "<p>
                <a class='button-primary' href='%s' target='_blank'>%s</a>
                " . __("Download count", "gfexcel") . ": %d
            </p>",
            $url,
            esc_html__('Download', 'gfexcel'),
            $this->download_count($form)
        );


    }

    /**
     * Returns the number of downloads
     * @param $form
     * @return int
     */
    private function download_count($form)
    {
        if (array_key_exists("gfexcel_download_count", $form)) {
            return (int) $form['gfexcel_download_count'];
        }

        return 0;
    }
}