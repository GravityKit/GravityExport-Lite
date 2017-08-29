<?php

namespace GFExcel;

use GF_Field;
use GFAPI;
use GFExcel\Renderer\PHPExcelRenderer;
use GFExcel\Transformer\Transformer;

class GFExcelOutput
{
    private $transformer;
    private $renderer;

    private $form_id;
    private $fields;
    private $entries;

    private $columns = [];
    private $rows = [];

    public function __construct($form_id)
    {
        $this->transformer = new Transformer();
        $this->renderer = new PHPExcelRenderer();
        $this->form_id = $form_id;
    }

    public function getFields()
    {
        if (empty($this->fields)) {
            $form = $this->getForm();
            $this->fields = $form['fields'];
        }

        return $this->fields;
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
        return $this->rows;
    }

    public function getColumns()
    {
        return $this->columns;
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
            $sorting = array(
                "key" => "date_created",
                "direction" => "ASC"
            );
            $this->entries = GFAPI::get_entries($this->form_id, $search_criteria, $sorting);
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
}