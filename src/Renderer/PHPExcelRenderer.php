<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;

class PHPExcelRenderer extends AbstractPHPExcelRenderer implements RendererInterface
{
    private $columns;
    private $rows;
    private $form;
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
     * @param $form
     * @param $columns
     * @param $rows
     * @param bool $save
     * @return string
     */
    public function handle($form, $columns, $rows, $save = false)
    {
        $this->form = $form;
        $this->columns = $columns;
        $this->rows = $rows;

        $this->setProperties();

        $matrix = $this->getMatrix($form, $this->columns, $this->rows);
        $this->addCellsToWorksheet($this->worksheet, $matrix)
            ->autoSizeColumns($this->worksheet, count($this->columns));

        return $this->renderOutput($this->extension, $save);
    }

    protected function getFileName()
    {
        $filename = GFExcel::getFilename($this->form['id']);

        return gf_apply_filters(array(
                "gfexcel_renderer_filename",
                $this->form['id'],
            ), $filename, $this->form) . "." . $this->extension;
    }

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

    private function setProperties()
    {
        $this->spreadsheet->getProperties()
            ->setCreator(GFExcel::$name)
            ->setLastModifiedBy(GFExcel::$name);

        $this->setTitle($this->form['title'])
            ->setSubject($this->form['title'])
            ->setDescription($this->form['description']);


        $this->extension = GFExcel::getFileExtension($this->form['id']);

        return $this;
    }

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
