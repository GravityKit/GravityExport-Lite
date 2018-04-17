<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PHPExcelRenderer extends AbstractPHPExcelRenderer implements RendererInterface
{

    private $columns;
    private $rows;
    private $form;
    private $worksheet;

    /**
     * Renderer constructor.
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
     */
    public function handle($form, $columns, $rows)
    {
        $this->form = $form;
        $this->columns = $columns;
        $this->rows = $rows;

        $this->setProperties();


        $this->addCellsToWorksheet($this->worksheet, $this->rows, $this->columns)
            ->autoSizeColumns($this->worksheet, $this->columns);

        return $this->renderOutput();
    }

    protected function getFileName()
    {
        $filename = sprintf("gfexcel-%d-%s-%s.xls",
            $this->form['id'],
            sanitize_title($this->form['title']),
            date("Ymd")
        );

        return gf_apply_filters(
            array(
                "gfexcel_renderer_filename",
                $this->form['id'],
            ),
            $filename, $this->form
        );
    }

    private function setTitle($title)
    {
        $title = gf_apply_filters(
            array(
                "gfexcel_renderer_title",
                $this->form['id'],
            ),
            $title, $this->form
        );

        $this->setWorksheetTitle($this->worksheet, $this->form);
        $this->spreadsheet->getProperties()->setTitle($title);

        return $this;
    }

    private function setSubject($title)
    {
        $title = gf_apply_filters(
            array(
                "gfexcel_renderer_subject",
                $this->form['id'],
            ),
            $title, $this->form
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

        return $this;
    }

    private function setDescription($description)
    {
        $description = gf_apply_filters(
            array(
                "gfexcel_renderer_description",
                $this->form['id'],
            ),
            $description, $this->form
        );

        $this->spreadsheet->getProperties()
            ->setDescription($description);
        return $this;
    }

}
