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
    public static $version = "1.4.1";
    public static $slug = "gf-entries-in-excel";

    const KEY_HASH = 'gfexcel_hash';
    const KEY_COUNT = 'gfexcel_download_count';
    const KEY_DISABLED_FIELDS = 'gfexcel_disabled_fields';
    const KEY_ENABLED_NOTES = 'gfexcel_enabled_notes';
    const KEY_CUSTOM_FILENAME = 'gfexcel_custom_filename';
    const KEY_FILE_EXTENSION = 'gfexcel_file_extension';


    private static $file_extension;

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

        $meta = GFFormsModel::get_form_meta($form_id);

        if (!array_key_exists(static::KEY_HASH, $meta)) {
            $meta = static::setHash($form_id);
        }

        return $meta[static::KEY_HASH];
    }

    /**
     * Save new hash to the form
     * @return array metadata form
     */
    public static function setHash($form_id)
    {
        $meta = GFFormsModel::get_form_meta($form_id);

        $meta[static::KEY_HASH] = static::generateHash($form_id);
        GFFormsModel::update_form_meta($form_id, $meta);

        return $meta;
    }

    private static function generateHash($form_id)
    {
        $meta = GFFormsModel::get_form_meta($form_id);
        if (!array_key_exists(static::KEY_COUNT, $meta) ||
            array_key_exists(static::KEY_HASH, $meta)
        ) {
            //never downloaded before, or recreating hash
            // so make a pretty new one
            return bin2hex(openssl_random_pseudo_bytes(32));
        }
        // Yay, we are someone from the first hour.. WHOOP, so we get to keep our old, maybe insecure string
        return @GFCommon::encrypt($form_id);
    }

    /**
     * Return the custom filename if it has one
     * @param $form_id
     * @return bool|string
     */
    public static function getFilename($form_id)
    {
        $form = GFFormsModel::get_form_meta($form_id);
        if (!array_key_exists(static::KEY_CUSTOM_FILENAME, $form) || empty(trim($form[static::KEY_CUSTOM_FILENAME]))) {
            return sprintf("gfexcel-%d-%s-%s",
                $form['id'],
                sanitize_title($form['title']),
                date("Ymd")
            );
        }

        return $form[static::KEY_CUSTOM_FILENAME];
    }

    /**
     * Return the file extension to use for renderer and output
     *
     * @param $form_id
     * @return string
     */
    public static function getFileExtension($form_id)
    {
        if (!static::$file_extension) {
            $form = GFFormsModel::get_form_meta($form_id);

            if (!$form || !array_key_exists(static::KEY_FILE_EXTENSION, $form)) {
                static::$file_extension = 'xlsx'; //default
                return static::$file_extension;
            }

            static::$file_extension = $form[static::KEY_FILE_EXTENSION];
        }

        return static::$file_extension;
    }

    public function add_permalink_rule()
    {
        add_rewrite_rule("^" . static::$slug . "/(.+)/?$",
            'index.php?gfexcel_action=' . static::$slug . '&gfexcel_hash=$matches[1]', 'top');

        $rules = get_option('rewrite_rules');
        if (!isset($rules["^" . static::$slug . "/(.+)/?$"])) {
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

    /**
     * @param $hash
     * @return bool|int
     */
    private function getFormIdByHash($hash)
    {
        global $wpdb;

        if (preg_match("/\.(xlsx?|csv)$/is", $hash, $match)) {
            $hash = str_replace($match[0], '', $hash);
            static::$file_extension = $match[1];
        };

        $table_name = GFFormsModel::get_meta_table_name();
        $wildcard = '%';
        $like = $wildcard . $wpdb->esc_like(json_encode($hash)) . $wildcard;

        if (!$form_row = $wpdb->get_row($wpdb->prepare("SELECT form_id FROM {$table_name} WHERE display_meta LIKE %s", $like), ARRAY_A)) {
            $result = @GFCommon::decrypt($hash);
            if (!is_numeric($result)) {
                return false;
            }
            if (!$form = GFFormsModel::get_form_meta($result)) {
                //this form does not exist, so nope
                return false;
            }
            if (array_key_exists(GFExcel::KEY_HASH, $form)) {
                //this form already has a hash. So if you knew the hash, you wouldn't be here. Shame!
                return false;
            }
            // Fallback to get the form id old fashion way. This should stop working asap.
            return (int) $result;
        }


        // possible match on hash, so check against found form.
        if (GFExcel::getHash($form_row['form_id']) !== $hash) {
            //hash doesn't match, so it's probably a partial match
            return false;
        }

        //only now are we home save.
        return (int) $form_row['form_id'];
    }

    /**
     * @param $form_id
     * @void
     */
    private function updateCounter($form_id)
    {
        $form_meta = GFFormsModel::get_form_meta($form_id);
        if (!array_key_exists(static::KEY_COUNT, $form_meta)) {
            $form_meta[static::KEY_COUNT] = 0;
        }
        $form_meta[static::KEY_COUNT] += 1;

        GFFormsModel::update_form_meta($form_id, $form_meta);
    }

    /**
     * Retrieve the disabled field id's in array
     *
     * @param $form
     * @return array
     */
    public static function get_disabled_fields($form)
    {
        $result = [];
        if (array_key_exists(static::KEY_DISABLED_FIELDS, $form)) {
            $result = explode(',', $form[static::KEY_DISABLED_FIELDS]);
        }

        return gf_apply_filters([
            "gfexcel_disabled_fields",
            $form['id'],
        ], $result);
    }

}