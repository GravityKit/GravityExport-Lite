<?php

namespace GFExcel;

use GFExcel\Field\FieldInterface;
use GFExcel\Repository\FieldsRepository;
use GFExcel\Repository\FormsRepository;
use GFExcel\Renderer\RendererInterface;
use GFExcel\Transformer\CombinerInterface;
use GFExcel\Transformer\Transformer;
use GFExcel\Transformer\TransformerInterface;
use GFExcel\Values\BaseValue;

/**
 * The point where data is transformed, and is send to the renderer.
 * @since 1.0.0
 */
class GFExcelOutput
{
    /**
     * The transformer.
     * @since 1.0.0
     * @var TransformerInterface
     */
    private $transformer;

    /**
     * The renderer.
     * @since 1.0.0
     * @var RendererInterface
     */
    private $renderer;

    /**
     * The form id.
     * @var int
     */
    private $form_id;

    /**
     * The form object.
     * @var mixed[]
     */
    private $form;

    /**
     * The form entries.
     * @var mixed[]
     */
    private $entries = [];

    /**
     * The columns.
     * @var BaseValue[]
     */
    private $columns = [];

    /**
     * Micro cache for the field repository.
     * @var FieldsRepository
     */
    private $repository;

    /**
     * The combiner for the rows.
     * @since $ver$
     * @var CombinerInterface
     */
    private $combiner;

    /**
     * GFExcelOutput constructor.
     * @since 1.0.0
     * @param int $form_id The form id.
     * @param RendererInterface $renderer The renderer.
     * @param CombinerInterface $combiner The combiner. {@since $ver$}
     */
    public function __construct($form_id, RendererInterface $renderer, CombinerInterface $combiner)
    {
        $this->transformer = new Transformer();
        $this->renderer = $renderer;
        $this->form_id = $form_id;
        $this->combiner = $combiner;

        set_time_limit(0);
    }

    /**
     * Get the Gravity Forms fields for the form.
     * @since 1.0.0
     * @return \GF_Field[] The fields.
     */
    public function getFields(): array
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
    public function getRows(): array
    {
        return gf_apply_filters(
            [
                'gfexcel_output_rows',
                $this->form_id,
            ],
            iterator_to_array($this->combiner->getRows()),
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
            $this->form = \GFAPI::get_form($this->form_id);
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
     * @param \GF_Field $field
     * @return BaseValue[]
     */
    private function getFieldColumns(\GF_Field $field)
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
        foreach ($this->getEntries() as $entry) {
            $this->combiner->parseEntry(array_map(function (\GF_Field $field): FieldInterface {
                return $this->transformer->transform($field);
            }, $this->getFields()), $entry);
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
            $entries = [];

            // prevent a multi-k database query to build up the array.
            $loop = true;
            while ($loop) {
                $paging = [
                    'offset' => ($i * $page_size),
                    'page_size' => $page_size,
                ];

                $new_entries = \GFAPI::get_entries($this->form_id, $search_criteria, $sorting, $paging);
                $count = count($new_entries);
                if ($count > 0) {
                    $entries[] = $new_entries;
                }

                ++$i; // increase for the loop

                if ($count < $page_size) {
                    $loop = false; // stop looping
                }
            }
            $this->entries = array_merge([], ...$entries);
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

    private function getSorting($form_id)
    {
        $repository = new FormsRepository($form_id);
        return [
            'key' => $repository->getSortField(),
            'direction' => $repository->getSortOrder(),
        ];
    }
}
