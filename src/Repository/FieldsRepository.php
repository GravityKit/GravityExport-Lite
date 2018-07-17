<?php

namespace GFExcel\Repository;

use GFExport;
use GF_Field;
use GFExcel\GFExcel;

class FieldsRepository
{
    private $fields = [];
    private $form = [];
    private $meta_fields = [];

    public function __construct(array $form)
    {
        $this->form = $form;
    }

    /**
     * Get the fields to show in the excel. Fields can be disabled using the hook.
     * @param bool $unfiltered
     * @return GF_Field[]
     */
    public function getFields($unfiltered = false)
    {
        if (empty($this->fields)) {

            $fields = $this->form['fields'];

            $fields = array_merge($fields, [
                new GF_Field([
                    'formId' => $this->form['id'],
                    'type' => 'notes',
                    'id' => 'notes',
                    'label' => esc_html__('Notes', 'gravityforms'),
                ])
            ]);


            if ($this->useMetaData()) {
                $fields_map = ['first' => [], 'last' => []];
                foreach ($this->meta_fields as $key => $field) {
                    $fields_map[in_array($key, $this->getFirstMetaFields()) ? 'first' : 'last'][] = $field;
                }
                $fields = array_merge($fields_map['first'], $fields, $fields_map['last']);
            }

            if($unfiltered) {
                return $fields;
            }

            $disabled_fields = GFExcel::get_disabled_fields($this->form);
            $this->fields = array_filter($fields, function (GF_Field $field) use ($disabled_fields) {

                return !gf_apply_filters(
                    [
                        "gfexcel_field_disable",
                        $field->get_input_type(),
                        $field->formId,
                        $field->id,
                    ], in_array($field->id, $disabled_fields), $field);
            });
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

    private function getFirstMetaFields()
    {
        return ['id', 'date_created', 'ip'];
    }

}