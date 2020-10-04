<?php

namespace GFExcel\Repository;

use GFExcel\GFExcelAdmin;
use GFExport;
use GF_Field;

class FieldsRepository
{
    private $fields = [];
    private $form = [];
    private $meta_fields = [];

    const KEY_DISABLED_FIELDS = 'gfexcel_disabled_fields';
    const KEY_ENABLED_FIELDS = 'gfexcel_enabled_fields';
    /**
     * @var GFExcelAdmin
     */
    private $admin;

    public function __construct(array $form)
    {
        $this->form = $form;
        $this->admin = GFExcelAdmin::get_instance();
    }

    /**
     * Get the fields to show in the excel. Fields can be disabled using the hook.
     * @param bool $unfiltered
     * @return GF_Field[]
     */
    public function getFields($unfiltered = false)
    {
        if (empty($this->fields)) {
            $this->fields = $this->form['fields'];
            $this->addNotesField();

            if ($this->useMetaData()) {
                $fields_map = ['first' => [], 'last' => []];
                foreach ($this->meta_fields as $key => $field) {
                    $fields_map[in_array($key, $this->getFirstMetaFields()) ? 'first' : 'last'][] = $field;
                }
                $this->fields = array_merge($fields_map['first'], $this->fields, $fields_map['last']);
            }

            if ($unfiltered) {
                $fields = $this->fields;
                $this->fields = []; //reset
                return $fields;
            }

            $this->filterDisabledFields();
            $this->fields = $this->sortFields();
        }

        return $this->fields;
    }

    /**
     * Check if we want meta data, if so, add those fields and format them.
     * @internal
     * @return boolean
     */
    private function useMetaData()
    {
        $use_metadata = (bool) gf_apply_filters(
            [
                "gfexcel_output_meta_info",
                $this->form['id'],
            ],
            true
        );

        if (!$use_metadata) {
            return false;
        }

        if (empty($this->meta_fields)) {
            $form = GFExport::add_default_export_fields(['id' => $this->form['id'], 'fields' => []]);
            $this->meta_fields = array_reduce($form['fields'], function ($carry, GF_Field $field) {
                $field->type = 'meta';
                $carry[$field->id] = $field;
                return $carry;
            });
        }

        return $use_metadata;
    }

    /**
     * Get the id's of the meta fields we want before the rest of the fields
     * @return array
     */
    private function getFirstMetaFields()
    {
        return ['id', 'date_created', 'ip'];
    }

    /**
     * Add a notes field to the export.
     * This isn't a normal field, that's why we add it our self
     *
     * @return array
     */
    private function addNotesField()
    {
        $repository = new FormsRepository($this->form['id']);
        if ($repository->showNotes()) {
            $this->fields = array_merge($this->fields, [
                new GF_Field([
                    'formId' => $this->form['id'],
                    'type' => 'notes',
                    'id' => 'notes',
                    'label' => esc_html__('Notes', 'gravityforms'),
                ])
            ]);
        }

        return $this->fields;
    }

    /**
     * Removes fields in disabled_fields array, or fields that are disabled by the hook
     * @return array
     */
    private function filterDisabledFields()
    {
        $disabled_fields = $this->getDisabledFields();
        $this->fields = array_filter($this->fields, function (GF_Field $field) use ($disabled_fields) {
            return !gf_apply_filters([
                "gfexcel_field_disable",
                $field->get_input_type(),
                $field->formId,
                $field->id,
            ], in_array($field->id, $disabled_fields), $field);
        });

        return $this->fields;
    }

    /**
     * Retrieve the disabled field id's in array
     * @return array
     */
    public function getDisabledFields()
    {
        $result = []; //default
        if (($settings = $this->admin->get_plugin_settings()) && is_array($settings)) {
            foreach ($settings as $key => $value) {
                if (strpos($key, 'enabled_metafield_') === 0 && $value == 0) {
                    $result[] = str_replace('enabled_metafield_', '', $key);
                }
            }
        }

        if (array_key_exists(static::KEY_DISABLED_FIELDS, $this->form)) {
            $result = explode(',', $this->form[static::KEY_DISABLED_FIELDS]);
        }

        return gf_apply_filters([
            "gfexcel_disabled_fields",
            $this->form['id'],
        ], $result);
    }

    /**
     * Return sorted array of the keys of enabled fields
     * @return array
     */
    public function getEnabledFields()
    {
        $result = [];
        if (array_key_exists(static::KEY_ENABLED_FIELDS, $this->form)) {
            $result = explode(',', $this->form[static::KEY_ENABLED_FIELDS]);
        }

        return $result;
    }

    /**
     * Sort fields according to sorted keys
     * @param array $fields
     * @return GF_Field[]
     */
    public function sortFields($fields = [])
    {
        if (empty($fields)) {
            $fields = $this->fields;
        }

        $sorted_keys = $this->getEnabledFields();
        $fields = array_reduce($fields, function ($carry, GF_Field $field) {
            $carry[$field->id] = $field;
            return $carry;
        }, []);

        $fields = @array_values(array_filter(array_replace(array_flip($sorted_keys), $fields), 'get_class'));
        return $fields;
    }
}
