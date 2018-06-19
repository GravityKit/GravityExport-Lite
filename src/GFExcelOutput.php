<?php

namespace GFExcel;

use GF_Field;
use GFAPI;
use GFExport;
use GFExcel\Renderer\RendererInterface;
use GFExcel\Transformer\Transformer;

/**
 * The point where data is tranformed, and is send to the renderer.
 */
class GFExcelOutput
{
    private $transformer;
    private $renderer;

    private $form_id;

    private $form;
    private $fields;
    private $entries;

    private $meta_fields = array();
    private $columns = array();
    private $rows = array();

    public function __construct($form_id, RendererInterface $renderer)
    {
        $this->transformer = new Transformer();
        $this->renderer = $renderer;
        $this->form_id = $form_id;
    }

    /**
     * Get field to sort the data by
     * @param $form_id
     * @return mixed
     */
    public static function getSortField($form_id)
    {
        $value = 'date_created';

        $form = \GFAPI::get_form($form_id);
        if (array_key_exists("gfexcel_output_sort_field", $form)) {
            $value = $form["gfexcel_output_sort_field"];
        }

        return gf_apply_filters(array('gfexcel_output_sort_field', $form['id']), $value);
    }

    /**
     * In what order should the data be sorted
     * @param $form_id
     * @return string
     */
    public static function getSortOrder($form_id)
    {
        $value = 'ASC'; //default
        $form = \GFAPI::get_form($form_id);

        if (array_key_exists("gfexcel_output_sort_order", $form)) {
            $value = $form["gfexcel_output_sort_order"];
        }
        $value = gf_apply_filters(array('gfexcel_output_sort_order', $form['id']), $value);
        //force either ASC or DESC
        return stripos($value, "ASC") !== false ? "ASC" : "DESC";
    }

    /**
     * Get the fields to show in the excel. Fields can be disabled using the hook.
     * @return GF_Field[]
     */
    public function getFields()
    {
        if (empty($this->fields)) {
            $form = $this->getForm();

            $fields = $form['fields'];

            $fields = array_merge($fields, [
                new GF_Field([
                    'formId' => $this->form_id,
                    'type' => 'notes',
                    'label' => esc_html__('Notes', 'gravityforms'),
                ])
            ]);

            if ($this->useMetaData()) {
                $fields_map = array('first' => array(), 'last' => array());
                foreach ($this->meta_fields as $key => $field) {
                    $fields_map[in_array($key, $this->getFirstMetaFields()) ? 'first' : 'last'][] = $field;
                }
                $fields = array_merge($fields_map['first'], $fields, $fields_map['last']);
            }

            $disabled_fields = GFExcel::get_disabled_fields($form);
            $this->fields = array_filter($fields, function (GF_Field $field) use ($disabled_fields) {
                return !gf_apply_filters(
                    array(
                        "gfexcel_field_disable",
                        $field->get_input_type(),
                        $field->formId,
                        $field->id,
                    ), in_array($field->id, $disabled_fields), $field);
            });
        }

        return $this->fields;
    }

    /**
     * The renderer is invoked and send all the data it needs to preform it's task.
     * It returns the actual Excel as a download
     * @return mixed
     */
    public function render()
    {
        $this->setColumns();
        $this->setRows();

        $form = $this->getForm();
        $rows = $this->getRows();
        $columns = $this->getColumns();

        return $this->renderer->handle($form, $columns, $rows);
    }

    /**
     * Retrieve the set rows, but it can be filtered.
     * @return array
     */
    public function getRows()
    {
        return gf_apply_filters(
            array(
                "gfexcel_output_rows",
                $this->form_id,
            ),
            $this->rows,
            $this->form_id
        );
    }

    /**
     * Retrieve the set columns, but it can be filtered.
     * @return array
     */
    public function getColumns()
    {
        return gf_apply_filters(
            array(
                "gfexcel_output_columns",
                $this->form_id
            ),
            $this->columns,
            $this->form_id
        );
    }

    /**
     * Add all columns a field needs to the array in order.
     * @return $this
     */
    private function setColumns()
    {
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $this->addColumns($this->getFieldColumns($field));
        }

        return $this;
    }

    /**
     * Retrieve form from GF Api
     * @return array|false
     */
    private function getForm()
    {
        if (!$this->form) {
            $this->form = GFAPI::get_form($this->form_id);
        }

        return $this->form;
    }

    /**
     * Add multiple columns at once
     * @param array $columns
     * @internal
     */
    private function addColumns(array $columns)
    {
        foreach ($columns as $column) {
            $this->columns[] = $column;
        }
    }

    /**
     * @param GF_Field $field
     * @return array
     */
    private function getFieldColumns(GF_Field $field)
    {
        $fieldClass = $this->transformer->transform($field);
        return $fieldClass->getColumns();
    }

    /**
     * Retrieves al rows for a form, and fills every column with the data.
     * @return $this
     */
    private function setRows()
    {
        $entries = $this->getEntries();
        foreach ($entries as $entry) {
            $this->addRow($entry);
        }
        return $this;
    }

    /**
     * Returns all entries for a form, based on the sort settings
     * @return array|\WP_Error
     */
    private function getEntries()
    {
        if (empty($this->entries)) {
            $search_criteria['status'] = 'active';
            $sorting = $this->get_sorting($this->form_id);
            $total_entries_count = GFAPI::count_entries($this->form_id, $search_criteria);
            $paging = array("offset" => 0, "page_size" => $total_entries_count);
            $this->entries = GFAPI::get_entries($this->form_id, $search_criteria, $sorting, $paging);
        }
        return $this->entries;
    }

    /**
     * Foreach field a transformer is found, and it returns the needed columns(s).
     * @param $field
     * @param $entry
     * @return array
     */
    private function getFieldCells($field, $entry)
    {
        $fieldClass = $this->transformer->transform($field);
        return $fieldClass->getCells($entry);
    }

    /**
     * Just a helper to add a row to the array.
     * @internal
     * @param $entry
     * @return $this
     */
    private function addRow($entry)
    {
        $row = array();

        foreach ($this->getFields() as $field) {
            foreach ($this->getFieldCells($field, $entry) as $cell) {
                $row[] = $cell;
            }
        }

        $this->rows[] = $row;

        return $this;
    }

    /**
     * Check if we want meta data, if so, add those fields and format them.
     * @internal
     * @return boolean
     */
    private function useMetaData()
    {
        $use_metadata = (bool) gf_apply_filters(
            array(
                "gfexcel_output_meta_info",
                $this->form_id
            ),
            true
        );

        if (!$use_metadata) {
            return false;
        }

        if (empty($this->meta_fields)) {
            $form = GFExport::add_default_export_fields(array('id' => $this->form_id, 'fields' => array()));
            $this->meta_fields = array_reduce($form['fields'], function ($carry, GF_Field $field) {
                $field->type = 'meta';
                $carry[$field->id] = $field;
                return $carry;
            });
        }

        return $use_metadata;
    }

    private function get_sorting($form_id)
    {
        return array(
            "key" => self::getSortField($form_id),
            "direction" => self::getSortOrder($form_id)
        );
    }

    private function getFirstMetaFields()
    {
        return array('id', 'date_created', 'ip');
    }
}
