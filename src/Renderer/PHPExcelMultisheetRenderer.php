<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class PHPExcelMultisheetRenderer extends AbstractPHPExcelRenderer implements RendererInterface
{
    private $current_sheet_id = -1;

    /**
     * Renderer constructor.
     */
    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->setProperties();
    }

    /**
     * @param $form
     * @param $columns
     * @param $rows
     * @throws \PhpOffice\Exception
     */
    public function handle($form, $columns, $rows)
    {
        $this->current_sheet_id += 1;
        if ($this->current_sheet_id > 0) {
            $this->spreadsheet->createSheet();
        }
        $this->spreadsheet->setActiveSheetIndex($this->current_sheet_id);

        $worksheet = $this->spreadsheet->getActiveSheet();

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
        $this->spreadsheet->getProperties()->setTitle($title);

        return $this;
    }

    private function setSubject($title)
    {
        $title = gf_apply_filters(array("gfexcel_renderer_subject"), $title);
        $this->spreadsheet->getProperties()->setSubject($title);

        return $this;
    }

    private function setProperties()
    {
        $this->spreadsheet->getProperties()->setCreator(GFExcel::$name)->setLastModifiedBy(GFExcel::$name);

        $title = GFExcel::$name . ' downloaded forms';
        $this->setTitle($title)->setSubject($title)->setDescription('');

        return $this;
    }

    private function setDescription($description)
    {
        $description = gf_apply_filters(array("gfexcel_renderer_description"), $description);
        $this->spreadsheet->getProperties()->setDescription($description);

        return $this;
    }

}
