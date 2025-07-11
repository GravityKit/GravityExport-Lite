<?php

namespace GFExcel\Addon;

use GFExcel\Template\TemplateAwareInterface;

/**
 * Trait that provides some helper methods for an {@see AddonInterface}.
 * @since 2.4.0
 * @mixin \GFAddOn
 */
trait AddonHelperTrait
{
    /**
     * Adds a simple information settings field.
     * @since 2.4.0
     * @param array $field The field object.
     * @param bool $echo Whether to directly echo the information.
     * @return string The html for the info.
     */
    public function settings_info(array $field, bool $echo = true): string
    {
        $field['html'] = sprintf('<p>%s</p>', $field['info'] ?? '');

        return $this->settings_html($field, $echo);
    }

    /**
     * Adds a html settings field.
     * @since 2.4.0
     * @param array|object $field The field object.
     * @param bool $echo Whether to directly echo the html.
     * @return string The html.
     */
    public function settings_html($field, bool $echo = true): string
    {
        $html = $field['html'] ?? '';

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    /**
     * Render button row properly.
     * @since 2.4.0
     * @param array $field The field object.
     */
    public function single_setting_row_button(array $field): void
    {
        $this->single_setting_row_save($field);
    }

    /**
     * @inheritdoc
     *
     * Tries to locate a field template before falling back to the original function.
     * This makes it way easier to implement a custom field.
     *
     * @since 2.4.0
     */
    public function single_setting($field): void
    {
        if (!$this instanceof TemplateAwareInterface) {
            parent::single_setting($field);
        } else {
            $template = 'field/' . $field['type'];
            if ($this->hasTemplate($template)) {
                $field['attributes'] = $this->get_field_attributes($field);
                $this->renderTemplate($template, $field);
            } else {
                parent::single_setting($field);
            }
        }
    }

    /**
     * @inheritDoc
     *
     * Overwritten to disable the `th` column in `fullscreen` mode.
     *
     * @param mixed[] $field The field to be displayed.
     * @see \GFAddOn::single_setting_row()
     */
    public function single_setting_row($field)
    {
        $should_display = ($hidden = rgar($field, 'hidden')) || rgar($field, 'type') === 'hidden';
        if (is_callable($hidden)) {
            $should_display = (bool) $hidden($field);
        }
        $display = $should_display ? 'style="display:none;"' : '';

        // Prepare setting description.
        $description = rgar($field, 'description')
            ? '<span class="gf_settings_description">' . $field['description'] . '</span>'
            : null;

        $default_full_screen = ['sort-fields', 'html', 'info'];
        $full_screen = (bool) ($field['full_screen'] ?? in_array($field['type'] ?? '', $default_full_screen, true));
        ?>

        <tr id="gaddon-setting-row-<?php echo $field['name'] ?>" <?php echo $display; ?>>
            <?php if (!$full_screen): ?>
                <th>
                    <?php $this->single_setting_label($field); ?>
                </th>
            <?php endif ?>
            <td<?php echo $full_screen ? ' colspan="2"' : '' ?>>
                <?php
                if ($full_screen) {
                    $this->single_setting_label($field);
                }

                $this->single_setting($field);
                echo $description;
                ?>
            </td>
        </tr>

        <?php
    }

    /**
     * @inheritdoc
     *
     * Wraps a select-element in a div for more visual control.
     * @since 2.4.0
     */
    public function settings_select($field, $echo = true): string
    {
        $html = sprintf('<div class="gfexcel_select"><div class="gfexcel_select__arrow"><i class="fa fa-chevron-down"></i></div>%s</div>',
            parent::settings_select($field, false));

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    /**
     * Adds submit button with an action.
     *
     * Makes this a submit `button` instead of an `input`.
     *
     * @since 2.4.0
     * @param array $field THe field object.
     * @param bool $echo Whether to echo out the HTML.
     * @return string The HTML.
     */
    public function settings_button(array $field, bool $echo = true): string
    {
        $field['type'] = 'submit';

        if (!rgar($field, 'class')) {
            $field['class'] = 'button-primary';
        }

        if (!rgar($field, 'label')) {
            $field['label'] = esc_html__('Update Settings', 'gravityforms');
        }

        if (!rgar($field, 'name')) {
            $field['name'] = 'gfexcel-action';
        }

        $attributes = $this->get_field_attributes($field);

        $html = sprintf(
            '<button type="%s" name="%s" value="%s" %s>%s</button>',
            esc_attr($field['type']),
            esc_attr($field['name']),
            esc_attr($field['value'] ?? ''),
            implode(' ', $attributes),
            esc_attr($field['label'])
        );

        if ($echo) {
            echo $html;
        }

        return $html;
    }

    /**
     * @inheritdoc
     *
     * Makes this a submit `button` instead of an `input`.
     *
     * @since 2.4.0
     */
    public function settings_save($field, $echo = true): string
    {
        $field['name'] = 'gform-settings-save';
        $field['value'] = '1';

        return $this->settings_button($field, $echo);
    }

    /**
     * @inheritdoc
     * Makes sure the returned form object is fresh.
     * @since 2.4.0
     */
    public function get_form_settings($form)
    {
        return parent::get_form_settings($this->getFreshForm($form));
    }

    /**
     * Refreshes a form object if needed.
     * @since 2.4.0
     * @param array $form The form object.
     * @return array A fresh Form object.
     */
    public function getFreshForm(array $form): array
    {
        // Settings might have changed during a postback, so cannot trust $form.
        if ($this->is_postback() && ($current_form = $this->get_current_form())) {
            $form = (array) $current_form;
            $form_id = $form['id'];
            $form = gf_apply_filters(['gform_admin_pre_render', $form_id], $form);
        }

        return $form;
    }

    /**
     * @inheritdoc
     * @since 2.4.0
     */
    public function add_default_save_button($sections): array
    {
        // prevents adding unwanted default save button.
        return $sections;
    }

    /**
     * Helper function that adds (and translates) a message.
     * @since 2.4.0
     * @param string $message The message.
     */
    public function add_message(string $message): void
    {
        \GFCommon::add_message(__($message));
    }

    /**
     * Helper function that adds (and translates) an error message.
     * @since 2.4.0
     * @param string $message The error message.
     */
    public function add_error_message(string $message): void
    {
        \GFCommon::add_error_message(__($message));
    }

    /**
     * @inheritdoc
     *
     * Wrap settings with additional classes.
     *
     * @since 2.4.0
     */
    public function form_settings($form)
    {
        printf('<div class="gfexcel-addon gfexcel-addon-%s">', $this->get_slug());
        parent::form_settings($form);
        print('</div>');
    }

    /**
     * @inheritdoc
     *
     * Overwritten to inject action.
     *
     * @since 2.4.0
     */
    public function render_settings($sections): void
    {
        do_action('gfexcel_addon_pre_settings', $this);

        parent::render_settings($sections);
    }

	/**
	 * Returns whether the download is enabled for the current form.
	 *
	 * @return bool
	 */
	public static function is_download_enabled(): bool {

		if ( ! class_exists( '\GFExcel\GFExcel' ) ) {
			return false;
		}

		$download_url = \GFExcel\GFExcel::url( rgget( 'id' ) );

		return ! empty( $download_url );
	}

	/**
	 * Determine if the current view is the screen for editing a form's feed settings.
	 * Stub for GF 2.4.
	 *
     * @todo Remove once we drop GF 2.4 support
	 * @return bool
	 */
	public function is_feed_edit_page() {
		if ( $this->is_gravityforms_supported( '2.5-beta' ) ) {
			return parent::is_feed_edit_page();
		}

		return 'gf_edit_forms' === rgget( 'page' ) && $this->get_slug() === rgget( 'subview' );
	}
}
