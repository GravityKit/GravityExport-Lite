<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;

class PHPExcelRenderer extends AbstractPHPExcelRenderer
{
    private $columns;
    private $rows;
    private $worksheet;
    private $extension;

    /**
     * Renderer constructor.
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     */
    public function __construct()
    {
        parent::__construct();
        $this->spreadsheet->setActiveSheetIndex(0);
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    /**
     * @inheritdoc
     * @return string The file path.
     */
    public function handle($form, $columns, $rows, $save = false)
    {
        $this->form = $form;
        $this->columns = $columns;
        $this->rows = $rows;

        $this->setProperties();

        $matrix = $this->getMatrix($form, $this->columns, $this->rows);
        $this->addCellsToWorksheet($this->worksheet, $matrix, (int) $form['id'])
            ->autoSizeColumns($this->worksheet, count($matrix[0] ?? []));

        return $this->renderOutput($this->extension, $save);
    }

    /**
     * @return string
     */
    protected function getFileName()
    {
        $filename = GFExcel::getFilename($this->form);

        return gf_apply_filters([
                'gfexcel_renderer_filename',
                $this->form['id'],
            ], $filename, $this->form) . '.' . $this->extension;
    }

    /**
     * Fluent setter for worksheet title.
     * @param string $title
     * @return PHPExcelRenderer
     */
    private function setTitle($title)
    {
        $title = gf_apply_filters(
            [
                'gfexcel_renderer_title',
                $this->form['id'],
            ],
            $title,
            $this->form
        );

        $this->setWorksheetTitle($this->worksheet, $this->form);
        $this->spreadsheet->getProperties()->setTitle($title);

        return $this;
    }

    /**
     * Fluent setter for file subject.
     * @param string $title
     * @return PHPExcelRenderer
     */
    private function setSubject($title)
    {
        $title = gf_apply_filters(
            [
                'gfexcel_renderer_subject',
                $this->form['id'],
            ],
            $title,
            $this->form
        );

        $this->spreadsheet->getProperties()->setSubject($title);
        return $this;
    }

    /**
     * Fluent setter for properties
     * @return PHPExcelRenderer
     */
    private function setProperties()
    {
        $this->spreadsheet->getProperties()
            ->setCreator(GFExcel::$name)
            ->setLastModifiedBy(GFExcel::$name);

        $this->setTitle($this->form['title'])
            ->setSubject($this->form['title'])
            ->setDescription($this->form['description']);

        $this->extension = GFExcel::getFileExtension($this->form);

        return $this;
    }

    /**
     * Fluent setter for file description.
     * @param string $description
     * @return PHPExcelRenderer
     */
    private function setDescription($description)
    {
        $description = gf_apply_filters(
            [
                'gfexcel_renderer_description',
                $this->form['id'],
            ],
            $description,
            $this->form
        );

        $this->spreadsheet->getProperties()
            ->setDescription($description);
        return $this;
    }
}
