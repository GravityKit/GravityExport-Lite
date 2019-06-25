<?php

namespace GFExcel;

use GF_Field;
use GFAPI;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Repository\FormsRepository;
use GFExcel\Renderer\RendererInterface;
use GFExcel\Transformer\Transformer;
use GFExcel\Values\BaseValue;

/**
 * The point where data is transformed, and is send to the renderer.
 */
class GFExcelOutput
{
    private $transformer;
    private $renderer;

    private $form_id;

    private $form;
    private $entries = [];

    /** @var BaseValue[] */
    private $columns = [];
    private $rows = [];

    private $repository;

    public function __construct($form_id, RendererInterface $renderer)
    {
        $this->transformer = new Transformer();
        $this->renderer = $renderer;
        $this->form_id = $form_id;
        set_time_limit(0);
    }

    public function getFields()
    {
        if (!$this->repository) {
            $this->repository = new FieldsRepository($this->getForm());
        }

        return $this->repository->getFields();
    }

    /**
     * The renderer is invoked and send all the data it needs to preform it's task.
     * It returns the actual Excel as a download
     * @param bool $save
     * @return mixed
     */
    public function render($save = false)
    {
        $this->setColumns();
        $this->setRows();

        $form = $this->getForm();
        $rows = $this->getRows();
        $columns = $this->getColumns();

        return $this->renderer->handle($form, $columns, $rows, $save);
    }

    /**
     * Retrieve the set rows, but it can be filtered.
     * @return array
     */
    public function getRows()
    {
        return gf_apply_filters(
            [
                'gfexcel_output_rows',
                $this->form_id,
            ],
            $this->rows,
            $this->form_id
        );
    }

    /**
     * Retrieve the set columns, but it can be filtered.
     * @return BaseValue[]
     */
    public function getColumns()
    {
        return gf_apply_filters(
            [
                'gfexcel_output_columns',
                $this->form_id,
            ],
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
     * @return BaseValue[]
     */
    private function getFieldColumns(GF_Field $field)
    {
        $fieldClass = $this->transformer->transform($field);
        return array_filter($fieldClass->getColumns(), function ($column) {
            return $column instanceof BaseValue;
        });
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
        $search_criteria = gf_apply_filters([
            'gfexcel_output_search_criteria',
            $this->form_id,
        ], [
            'status' => 'active',
        ], $this->form_id);

        if (empty($this->entries)) {
            $sorting = $this->getSorting($this->form_id);
            $page_size = 100;
            $i = 0;

            // prevent a multi-k database query to build up the array.
            $loop = true;
            while ($loop) {
                $paging = [
                    'offset' => ($i * $page_size),
                    'page_size' => $page_size,
                ];

                $new_entries = GFAPI::get_entries($this->form_id, $search_criteria, $sorting, $paging);
                $count = count($new_entries);
                if ($count > 0) {
                    $this->entries = array_merge($this->entries, $new_entries);
                }

                $i += 1; // increase for the loop

                if ($count < $page_size) {
                    $loop = false; // stop looping
                }
            }
        }

        return $this->entries;
    }

    /**
     * Actively set the entries for this output rendering
     * @param array $entries
     * @return $this
     */
    public function setEntries($entries = [])
    {
        $this->entries = $entries;
        return $this;
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

    private function getSorting($form_id)
    {
        $repository = new FormsRepository($form_id);
        return [
            "key" => $repository->getSortField(),
            "direction" => $repository->getSortOrder()
        ];
    }
}
