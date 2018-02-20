<?php

namespace GFExcel;

use GF_Field;
use GFAPI;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Renderer\RendererInterface;
use GFExcel\Transformer\Transformer;

class GFExcelOutput
{
    private $transformer;
    private $renderer;

    private $form_id;
    private $fields;
    private $entries;

    private $columns = array();
    private $rows = array();

    public function __construct($form_id, RendererInterface $renderer)
    {
        $this->transformer = new Transformer();
        $this->renderer = $renderer;
        $this->form_id = $form_id;
    }

    public static function getSortField($form_id)
    {
        $value = 'date_created';

        $form = \GFAPI::get_form($form_id);
        if (array_key_exists("gfexcel_output_sort_field", $form)) {
            $value = $form["gfexcel_output_sort_field"];
        }

        return gf_apply_filters(array('gfexcel_output_sort_field', $form['id']), $value);
    }

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
     * @return GF_Field[]
     */
    public function getFields()
    {
        if (empty($this->fields)) {
            $form = $this->getForm();
            $this->fields = $form['fields'];
        }


        return array_filter($this->fields, function (GF_Field $field) {
            return !gf_apply_filters(
                array(
                    "gfexcel_field_disable",
                    $field->get_input_type(),
                    $field->id,
                ), false, $field);
        });
    }

    public function render()
    {
        $this->setColumns();
        $this->setRows();

        $form = $this->getForm();
        $rows = $this->getRows();
        $columns = $this->getColumns();

        return $this->renderer->handle($form, $columns, $rows);
    }

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

    private function setColumns()
    {
        if ($this->useMetaData()) {
            $this->addColumns(array(
                __('ID', 'gfexcel'),
                __('Date', 'gfexcel'),
                __('IP address', 'gfexcel'),
            ));
        }

        $fields = $this->getFields();
        foreach ($fields as $field) {
            $this->addColumns($this->getFieldColumns($field));
        }
        return $this;
    }

    private function getForm()
    {
        return GFAPI::get_form($this->form_id);
    }

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

    private function setRows()
    {
        $entries = $this->getEntries();
        foreach ($entries as $entry) {
            $this->addRow($entry);
        }
        return $this;
    }

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

    private function getFieldCells($field, $entry)
    {
        $fieldClass = $this->transformer->transform($field);
        return $fieldClass->getCells($entry);
    }

    private function addRow($entry)
    {
        $row = array();
        if ($this->useMetaData()) {
            $row[] = $entry['id'];
            $row[] = $entry['date_created'];
            $row[] = $entry['ip'];
        }
        foreach ($this->getFields() as $field) {
            foreach ($this->getFieldCells($field, $entry) as $cell) {
                $row[] = $cell;
            }
        }
        $this->rows[] = $row;

        return $this;
    }

    /**
     *
     * @internal
     * @return boolean
     */
    private function useMetaData()
    {
        return gf_apply_filters(
            array(
                "gfexcel_output_meta_info",
                $this->form_id
            ),
            true
        );
    }

    private function get_sorting($form_id)
    {
        return array(
            "key" => self::getSortField($form_id),
            "direction" => self::getSortOrder($form_id)
        );
    }
}
