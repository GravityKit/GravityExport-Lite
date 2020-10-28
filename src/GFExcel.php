<?php

namespace GFExcel;

use GFExcel\Action\FilterRequest;
use GFExcel\Notification\Manager\NotificationManager;
use GFExcel\Notification\Repository\NotificationRepository;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Shorttag\DownloadUrl;
use GFExcel\Transformer\Combiner;
use GFExcel\Transformer\CombinerInterface;

/**
 * The core of the plugin.
 * @since 1.0.0
 */
class GFExcel
{
    /**
     * Full name of the plugin
     * @since 1.0.0
     * @var string
     */
    public static $name = 'Gravity Forms Entries in Excel';

    /**
     * Short name of the plugin
     * @since 1.0.0
     * @var string
     */
    public static $shortname = 'Entries in Excel';

    /**
     * Current version of the plugin
     * @since 1.0.0
     * @var string
     */
    public static $version = '1.8.6';

    /**
     * The endpoint slug of the plugin.
     * @since 1.0.0
     * @var string
     */
    public static $slug = 'gf-entries-in-excel';

    public const KEY_HASH = 'gfexcel_hash';

    public const KEY_ACTION = 'gfexcel_action';

    public const KEY_ENABLED_NOTES = 'gfexcel_enabled_notes';

    public const KEY_CUSTOM_FILENAME = 'gfexcel_custom_filename';

    public const KEY_FILE_EXTENSION = 'gfexcel_file_extension';

    public const KEY_ATTACHMENT_NOTIFICATION = 'gfexcel_attachment_notification';

    private static $file_extension;

    /**
     * The notification manager singleton.
     * @since 1.8.0
     * @var NotificationManager|null
     */
    private static $notification_manager;

    /**
     * Instantiates the plugin.
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('init', [$this, 'addPermalinkRule']);
        add_action('request', [$this, 'request']);
        add_action('parse_request', [$this, 'downloadFile']);
        add_filter('query_vars', [$this, 'getQueryVars']);
        add_filter('robots_txt', [$this, 'robotsTxt']);

        $this->registerActions();
    }

    /** Return the url for the form
     * @since 1.0.0
     * @param int $form_id The id of the form.
     * @return string|null
     */
    public static function url($form_id)
    {
        $blogurl = get_bloginfo('url');
        $permalink = '/index.php?' . self::KEY_ACTION . '=%s&' . self::KEY_HASH . '=%s';

        $action = self::$slug;
        $hash = self::getHash($form_id);
        if (!$hash) {
            return null;
        }

        if (get_option('permalink_structure')) {
            $permalink = '/%s/%s';
        } else {
            $hash = urlencode($hash);
        }

        return $blogurl . sprintf($permalink, $action, $hash);
    }

    /**
     * Returns the download hash for a form.
     * @since 1.0.0
     * @param int $form_id the form id to get the hash for.
     * @return string|null the hash
     */
    private static function getHash($form_id)
    {
        if (!\GFAPI::form_id_exists($form_id)) {
            return null;
        }

        $meta = \GFFormsModel::get_form_meta($form_id);
        if (!isset($meta[static::KEY_HASH]) || empty($meta[static::KEY_HASH])) {
            return null;
        }

        return $meta[static::KEY_HASH];
    }

    /**
     * Save new hash to the form
     * @param int $form_id The form id.
     * @param null|string $hash predefined hash {@since 1.7.0}
     * @return mixed[] metadata form.
     */
    public static function setHash($form_id, $hash = null)
    {
        if ($hash === null) {
            $hash = self::generateHash();
        }

        $meta = \GFFormsModel::get_form_meta($form_id);
        $meta[self::KEY_HASH] = (string) $hash;
        \GFFormsModel::update_form_meta($form_id, $meta);

        return $meta;
    }

    /**
     * Generates a secure random string.
     * @since 1.0.0
     * @return string
     */
    private static function generateHash()
    {
        return bin2hex(openssl_random_pseudo_bytes(32));
    }

    /**
     * Return the custom filename if it has one.
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
     * @param array $form The form object.
     * @return string The file extension.
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

    /**
     * Helper method to retrieve the available file extensions for the plugin.
     * @since 1.8.0
     * @param bool $imploded Whether to return an imploded array for a regex pattern instead of an array.
     * @return string[]|string The extensions.
     */
    public static function getPluginFileExtensions(bool $imploded = false)
    {
        $extensions = (array) apply_filters('gfexcel_file_extensions', ['xlsx', 'csv']);
        if ($imploded) {
            $extensions = implode('|', array_map(static function (string $extension) {
                return preg_quote($extension, '/');
            }, $extensions));
        }

        return $extensions;
    }

    /**
     * Whether the current user can download the form.
     * @since 1.7.0
     * @param int $form_id The form id of the form to download.
     * @return bool Whether the current user can download the file.
     */
    private static function canDownloadForm(int $form_id)
    {
        // public urls can always be downloaded.
        if (!self::isFormSecured($form_id)) {
            return true;
        }

        // does the user have rights?
        return \GFCommon::current_user_can_any('gravityforms_export_entries');
    }

    /**
     * Registers the permalink structure for the download
     * @since 1.0.0
     */
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
     * Hooks into the request and outputs the file as the HHTP response.
     * @since 1.0.0
     * @param string[] $query_vars The original query vars.
     * @return string[] The new query vars.
     */
    public function request($query_vars)
    {
        if (!isset($query_vars[self::KEY_ACTION]) ||
            !isset($query_vars[self::KEY_HASH]) ||
            $query_vars[self::KEY_ACTION] !== self::$slug ||
            empty($query_vars[self::KEY_HASH])
        ) {
            return $query_vars;
        }

        $form_id = $this->getFormIdByHash($query_vars[self::KEY_HASH]);
        if ($form_id) {
            if (self::canDownloadForm($form_id)) {
                $query_vars['gfexcel_download_form'] = $form_id;
            } else {
                $query_vars['error'] = \WP_Http::FORBIDDEN;
            }
        } else {
            // Not found
            $query_vars['error'] = \WP_Http::NOT_FOUND;
        }

        return $query_vars;
    }

    /**
     * Actually triggers the download response.
     * @since 1.7.0
     * @param \WP $wp Wordpress request instance.
     * @return mixed The output will be the file.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function downloadFile(\WP $wp)
    {
        if (array_key_exists('gfexcel_download_form', $wp->query_vars)) {
            $form_id = $wp->query_vars['gfexcel_download_form'] ?? null;

            if ($form_id) {
                $renderer = gf_apply_filters([
                    GFExcelConfigConstants::GFEXCEL_DOWNLOAD_RENDERER,
                    $form_id
                ], new PHPExcelRenderer());

                $output = new GFExcelOutput($form_id, $renderer);

                // trigger download event.
                do_action(GFExcelConfigConstants::GFEXCEL_EVENT_DOWNLOAD, $form_id, $output);

                return $output->render();
            }
        }
    }

    /**
     * Adds the query vars for the permalink.
     * @since 1.0.0
     * @param string[] $vars The original query vars.
     * @return string[] The new query vars.
     */
    public function getQueryVars($vars)
    {
        return array_merge($vars, [
            self::KEY_ACTION,
            self::KEY_HASH,
        ]);
    }

    /**
     * Helper method to retrieve the form id from the hash.
     * @param string $hash The hash.
     * @return int|null The form id.
     */
    private function getFormIdByHash($hash)
    {
        global $wpdb;

        if (preg_match('/\\.(' . GFExcel::getPluginFileExtensions(true) . ')$/is', $hash, $match)) {
            $hash = str_replace($match[0], '', $hash);
            static::$file_extension = $match[1];
        }

        $table_name = \GFFormsModel::get_meta_table_name();
        $wildcard = '%';
        $like = $wildcard . $wpdb->esc_like(json_encode($hash)) . $wildcard;

        // Data is stored in a json_encoded string, so we can't match perfectly.
        if (!$form_row = $wpdb->get_row(
            $wpdb->prepare("SELECT form_id FROM {$table_name} WHERE display_meta LIKE %s", $like),
            ARRAY_A
        )) {
            // not even a partial match.
            return null;
        }

        // possible match on hash, so check against found form.
        if (GFExcel::getHash($form_row['form_id']) !== $hash) {
            //hash doesn't match, so it's probably a partial match
            return null;
        }

        //only now are we home save.
        return (int) $form_row['form_id'];
    }

    /**
     * Register native plugin actions
     * @since 1.6.1
     * @return void
     */
    private function registerActions()
    {
        $actions = [
            DownloadUrl::class,
            FilterRequest::class,
        ];

        foreach ($actions as $action) {
            if (class_exists($action)) {
                new $action;
            }
        }
    }

    /**
     * Add's a Disallow for the download URL's.
     * @since 1.7.0
     * @param string $output The robots.txt output
     * @return string the new output.
     */
    public function robotsTxt($output)
    {
        $site_url = parse_url(site_url());
        $path = (!empty($site_url['path'])) ? $site_url['path'] : '';
        $line = sprintf('Disallow: %s/%s/', $path, GFExcel::$slug);

        // there can be only one `user-agent: *` line, so we make sure it's just below.
        if (preg_match('/user-agent:\s*\*/is', $output, $matches)) {
            return str_replace($matches[0], $matches[0] . "\n" . $line, $output);
        }

        return trim(sprintf("%s\n%s\n%s", $output, 'User-agent: *', $line));
    }

    /**
     * Whether all forms should be secured.
     * @since 1.7.0
     * @return bool Whether the constant is set.
     */
    public static function isAllSecured()
    {
        return defined('GFEXCEL_SECURED_DOWNLOADS') && GFEXCEL_SECURED_DOWNLOADS;
    }

    /**
     * Whether the form is secured.
     * @since 1.7.0
     * @param int $form_id
     * @return bool Whether this form is secured.
     */
    public static function isFormSecured(int $form_id)
    {
        if (self::isAllSecured()) {
            return true;
        }

        $meta = \GFFormsModel::get_form_meta($form_id);

        return (bool) rgar($meta, GFExcelConfigConstants::GFEXCEL_DOWNLOAD_SECURED, false);
    }

    /**
     * Returns the combiner instance.
     * @since 1.8.0
     * @return CombinerInterface The combiner.
     */
    public static function getCombiner(): CombinerInterface
    {
        return apply_filters(GFExcelConfigConstants::GFEXCEL_DOWNLOAD_COMBINER, new Combiner());
    }

    /**
     * Returns the notification singleton.
     * @since 1.8.0
     * @return NotificationManager The notification manager.
     */
    public static function getNotificationManager(): NotificationManager
    {
        if (!self::$notification_manager) {
            $repository = apply_filters(
                GFExcelConfigConstants::GFEXCEL_NOTIFICATION_MANAGER,
                new NotificationRepository()
            );

            self::$notification_manager = apply_filters(
                GFExcelConfigConstants::GFEXCEL_NOTIFICATION_REPOSITORY,
                new NotificationManager($repository),
                $repository
            );
        }

        return self::$notification_manager;
    }
}
