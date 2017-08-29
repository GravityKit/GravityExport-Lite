<?php

namespace GFExcel;

use GFAPI;
use GFCommon;

class GFExcel
{

    public static $name = 'Gravity Forms Results in Excel';
    public static $shortname = 'Results in Excel';
    public static $version = "1.0.0";
    public static $slug = "gfexcel";

    public function __construct()
    {
        add_action("request", array($this, "request"));
        add_filter("query_vars", array($this, "query_vars"));
    }

    /** Return the url for the form
     * @param $form
     * @return string
     */
    public static function url($form)
    {
        $blogurl = get_bloginfo("url");
        $permalink = "/index.php?gfexcel_action=%s&gfexcel_hash=%s";

        if (get_option('permalink_structure')) {
            $permalink = "/%s/%s";
        }

        $action = self::$slug;
        $hash = self::getHash($form['id']);

        return $blogurl . sprintf($permalink, $action, $hash);
    }

    private static function getHash($form_id)
    {

        if (!GFAPI::form_id_exists($form_id)) {
            return false;
        }

        $hash = GFCommon::encrypt($form_id);
        return $hash;
    }

    public function request($query_vars)
    {
        if (!array_key_exists("gfexcel_action", $query_vars) ||
            !array_key_exists("gfexcel_hash", $query_vars) ||
            $query_vars['gfexcel_action'] !== self::$slug) {

            return $query_vars;
        }

        $form_id = $this->getFormIdByHash($query_vars['gfexcel_hash']);
        if (!$form_id) {
            return $query_vars;
        }

        $output = new GFExcelOutput($form_id);
        return $output->render();

    }

    public function query_vars($vars)
    {
        $vars[] = "gfexcel_action";
        $vars[] = "gfexcel_hash";
        return $vars;
    }

    private function getFormIdByHash($hash)
    {
        $result = GFCommon::decrypt($hash);
        if (is_numeric($result)) {
            return $result;
        }

        return false;
    }

}