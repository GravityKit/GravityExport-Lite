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

        $this->initialize();

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
            </p>",
            $url,
            esc_html__('Download', 'gfexcel')
        );

    }

    public function activate()
    {
        $this->add_permalink_rule();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function initialize()
    {
        if (!self::$file) {
            self::$file = realpath(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "gfexcel.php");
        }
        register_activation_hook(self::$file, array($this, "activate"));
        register_deactivation_hook(self::$file, array($this, "deactivate"));

        add_action("wp_loaded", array($this, "add_permalink_rule"));
    }

    /**
     * Adds the url "gfexcel/{encrypted_url}" to the permalinksystem
     */
    public function add_permalink_rule()
    {
        add_rewrite_rule("^" . $this->_slug . "/(.+)/?$",
            'index.php?gfexcel_action=' . $this->_slug . '&gfexcel_hash=$matches[1]', 'top');

    }
}