<?php

namespace GFExcel;

use GFAPI;
use GFExcel\Renderer\PHPExcelRenderer;
use GFFormsModel;

class GFExcel
{
    public static $name = 'Gravity Forms Entries in Excel';
    public static $shortname = 'Entries in Excel';
    public static $version = "1.6.1";
    public static $slug = "gf-entries-in-excel";

    const KEY_HASH = 'gfexcel_hash';
    const KEY_ACTION = 'gfexcel_action';
    const KEY_ENABLED_NOTES = 'gfexcel_enabled_notes';
    const KEY_CUSTOM_FILENAME = 'gfexcel_custom_filename';
    const KEY_FILE_EXTENSION = 'gfexcel_file_extension';
    const KEY_ATTACHMENT_NOTIFICATION = 'gfexcel_attachment_notification';

    private static $file_extension;

    public function __construct()
    {
        add_action("init", array($this, "addPermalinkRule"));
        add_action("request", array($this, "request"));
        add_filter("query_vars", array($this, "getQueryVars"));
    }

    /** Return the url for the form
     * @param $form_id
     * @return string
     */
    public static function url($form_id)
    {
        $blogurl = get_bloginfo('url');
        $permalink = '/index.php?' . self::KEY_ACTION . '=%s&' . self::KEY_HASH . '=%s';

        $action = self::$slug;
        $hash = self::getHash($form_id);

        if (get_option('permalink_structure')) {
            $permalink = '/%s/%s';
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
     * @param $form_id
     * @return array metadata form
     */
    public static function setHash($form_id)
    {
        $meta = GFFormsModel::get_form_meta($form_id);

        $meta[self::KEY_HASH] = self::generateHash();
        GFFormsModel::update_form_meta($form_id, $meta);

        return $meta;
    }

    /**
     * Generates a secure random string.
     * @return string
     */
    private static function generateHash()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Return the custom filename if it has one
     * @param array $form
     * @return bool|string
     */
    public static function getFilename($form)
    {
        if (!array_key_exists(static::KEY_CUSTOM_FILENAME, $form) || empty(trim($form[static::KEY_CUSTOM_FILENAME]))) {
            return sprintf(
                'gfexcel-%d-%s-%s',
                $form['id'],
                sanitize_title($form['title']),
                date('Ymd')
            );
        }

        return $form[static::KEY_CUSTOM_FILENAME];
    }

    /**
     * Return the file extension to use for renderer and output
     *
     * @param array $form
     * @return string
     */
    public static function getFileExtension($form)
    {
        if (!static::$file_extension) {
            if (!$form || !array_key_exists(static::KEY_FILE_EXTENSION, $form)) {
                static::$file_extension = 'xlsx'; //default
                return static::$file_extension;
            }

            static::$file_extension = $form[static::KEY_FILE_EXTENSION];
        }

        return static::$file_extension;
    }

    public function addPermalinkRule()
    {
        add_rewrite_rule(
            '^' . static::$slug . '/(.+)/?$',
            'index.php?' . self::KEY_ACTION . '=' . static::$slug . '&' . self::KEY_HASH . '=$matches[1]',
            'top'
        );

        $rules = get_option('rewrite_rules');
        if (!isset($rules['^' . static::$slug . '/(.+)/?$'])) {
            flush_rewrite_rules();
        }
    }

    /**
     * @param $query_vars
     * @return mixed
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function request($query_vars)
    {
        if (!array_key_exists(self::KEY_ACTION, $query_vars) ||
            !array_key_exists(self::KEY_HASH, $query_vars) ||
            $query_vars[self::KEY_ACTION] !== self::$slug) {
            return $query_vars;
        }

        $form_id = $this->getFormIdByHash($query_vars[self::KEY_HASH]);
        if (!$form_id) {
            return $query_vars;
        }

        add_filter('gfexcel_output_search_criteria', function ($search_criteria) {
            $search_criteria['start_date'] = rgar($_REQUEST, 'start_date', '');
            $search_criteria['end_date'] = rgar($_REQUEST, 'end_date', '');
            return array_filter($search_criteria);
        });

        $output = new GFExcelOutput($form_id, new PHPExcelRenderer());

        // trigger download event.
        do_action(GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, $form_id, $output);

        return $output->render();
    }

    /**
     * @param $vars
     * @return string[]
     */
    public function getQueryVars($vars)
    {
        return array_merge($vars, [
            self::KEY_ACTION,
            self::KEY_HASH,
        ]);
    }

    /**
     * @param $hash
     * @return bool|int
     */
    private function getFormIdByHash($hash)
    {
        global $wpdb;

        if (preg_match("/\.(xlsx|csv)$/is", $hash, $match)) {
            $hash = str_replace($match[0], '', $hash);
            static::$file_extension = $match[1];
        };

        $table_name = GFFormsModel::get_meta_table_name();
        $wildcard = '%';
        $like = $wildcard . $wpdb->esc_like(json_encode($hash)) . $wildcard;

        // Data is stored in a json_encoded string, so we can't match perfectly.
        if (!$form_row = $wpdb->get_row(
            $wpdb->prepare("SELECT form_id FROM {$table_name} WHERE display_meta LIKE %s", $like),
            ARRAY_A
        )) {
            // not even a partial match.
            return false;
        }

        // possible match on hash, so check against found form.
        if (GFExcel::getHash($form_row['form_id']) !== $hash) {
            //hash doesn't match, so it's probably a partial match
            return false;
        }

        //only now are we home save.
        return (int) $form_row['form_id'];
    }
}
