<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use PHPExcel;

class PHPExcelRenderer extends AbstractPHPExcelRenderer implements RendererInterface
{
    /**
     * @var PHPExcel
     */
    private $columns;
    private $rows;
    private $form;
    private $worksheet;

    /**
     * Renderer constructor.
     */
    public function __construct()
    {
        $this->PHPExcel = new PHPExcel();
        $this->PHPExcel->setActiveSheetIndex(0);
        $this->worksheet = $this->PHPExcel->getActiveSheet();
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

        $worksheet_title = substr(gf_apply_filters(
            array(
                "gfexcel_renderer_worksheet_title",
                $this->form['id'],
            ),
            $title, $this->form
        ), 0, 30);

        $this->PHPExcel->getProperties()->setTitle($title);
        $this->worksheet->setTitle($worksheet_title);
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
        $this->PHPExcel->getProperties()->setSubject($title);
        return $this;
    }

    private function setProperties()
    {
        $this->PHPExcel->getProperties()
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

        $this->PHPExcel->getProperties()
            ->setDescription($description);
        return $this;
    }

}