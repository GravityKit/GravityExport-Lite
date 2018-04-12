<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use PHPExcel;

class PHPExcelMultisheetRenderer extends AbstractPHPExcelRenderer implements RendererInterface
{
    private $current_sheet_id = -1;

    /**
     * Renderer constructor.
     */
    public function __construct()
    {
        $this->PHPExcel = new PHPExcel();
        $this->setProperties();
    }

    /**
     * @param $form
     * @param $columns
     * @param $rows
     * @throws \PHPExcel_Exception
     */
    public function handle($form, $columns, $rows)
    {
        $this->current_sheet_id += 1;
        if ($this->current_sheet_id > 0) {
            $this->PHPExcel->createSheet();
        }
        $this->PHPExcel->setActiveSheetIndex($this->current_sheet_id);

        $worksheet = $this->PHPExcel->getActiveSheet();

        $this->addCellsToWorksheet($worksheet, $rows, $columns)
            ->autoSizeColumns($worksheet, $columns)
            ->setWorksheetTitle($worksheet, $form);
    }

    protected function getFileName()
    {
        $filename = sprintf("gfexcel-%s-%s.xls", sanitize_title("download"), date("Ymd"));

        return gf_apply_filters(array("gfexcel_renderer_filename"), $filename);
    }

    private function setTitle($title)
    {
        $title = gf_apply_filters(array("gfexcel_renderer_title"), $title);
        $this->PHPExcel->getProperties()->setTitle($title);

        return $this;
    }

    private function setSubject($title)
    {
        $title = gf_apply_filters(array("gfexcel_renderer_subject"), $title);
        $this->PHPExcel->getProperties()->setSubject($title);

        return $this;
    }

    private function setProperties()
    {
        $this->PHPExcel->getProperties()->setCreator(GFExcel::$name)->setLastModifiedBy(GFExcel::$name);

        $title = GFExcel::$name . ' downloaded forms';
        $this->setTitle($title)->setSubject($title)->setDescription('');

        return $this;
    }

    private function setDescription($description)
    {
        $description = gf_apply_filters(array("gfexcel_renderer_description"), $description);
        $this->PHPExcel->getProperties()->setDescription($description);

        return $this;
    }

}