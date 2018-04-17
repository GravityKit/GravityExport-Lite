<?php

namespace GFExcel;

use GFAPI;
use GFCommon;
use GFExcel\Renderer\PHPExcelRenderer;
use GFFormsModel;

class GFExcel
{
    public static $name = 'Gravity Forms Results in Excel';
    public static $shortname = 'Results in Excel';
    public static $version = "1.2.4";
    public static $slug = "gf-entries-in-excel";

    public function __construct()
    {
        add_action("init", array($this, "add_permalink_rule"));
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

        $action = self::$slug;
        $hash = self::getHash($form['id']);

        if (get_option('permalink_structure')) {
            $permalink = "/%s/%s";
        } else {
            $hash = urlencode($hash);
        }

        return $blogurl . sprintf($permalink, $action, $hash);
    }


    private static function getHash($form_id)
    {
        if (!GFAPI::form_id_exists($form_id)) {
            return false;
        }

        $hash = @GFCommon::encrypt($form_id);

        return $hash;
    }

    public function add_permalink_rule()
    {
        add_rewrite_rule("^" . GFExcel::$slug . "/(.+)/?$",
            'index.php?gfexcel_action=' . GFExcel::$slug . '&gfexcel_hash=$matches[1]', 'top');

        $rules = get_option('rewrite_rules');
        if (!isset($rules["^" . GFExcel::$slug . "/(.+)/?$"])) {
            flush_rewrite_rules();
        }
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

        $output = new GFExcelOutput($form_id, new PHPExcelRenderer());
        $this->updateCounter($form_id);

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
        $result = @GFCommon::decrypt($hash);
        if (is_numeric($result)) {
            return $result;
        }

        return false;
    }

    /**
     * @param $form_id
     * @void
     */
    private function updateCounter($form_id)
    {
        $key = 'gfexcel_download_count';
        $form_meta = GFFormsModel::get_form_meta($form_id);
        if (!array_key_exists($key, $form_meta)) {
            $form_meta[$key] = 0;
        }
        $form_meta[$key] += 1;

        GFFormsModel::update_form_meta($form_id, $form_meta);
    }

}