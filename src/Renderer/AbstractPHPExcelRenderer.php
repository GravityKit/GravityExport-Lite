<?php

namespace GFExcel\Renderer;

use GFExcel\Values\BaseValue;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Exception;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

abstract class AbstractPHPExcelRenderer
{
    /** @var Spreadsheet */
    protected $spreadsheet;


    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function renderOutput()
    {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $this->getFileName() . '"');
        header('Cache-Control: max-age=1');

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        try {
            $this->spreadsheet->setActiveSheetIndex(0);
            $objWriter = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
            $objWriter->save('php://output');
        } catch (Exception $e) {
        }

        exit; // stop rest
    }

    /**
     * @todo there is a function on PHPSpreadsheet that does the same
     * @param $i
     * @return string
     */
    protected function getLetter($i)
    {
        $letters = range("a", "z");
        $count = count($letters);
        if ($i < $count) {
            return strtoupper($letters[$i]);
        }

        $rows = ($i + 1) / $count;
        $remainder = $i - (floor($rows) * $count);

        return strtoupper($letters[$rows - 1] . $letters[$remainder]);
    }

    protected function autoSizeColumns(Worksheet $worksheet, $columns)
    {
        for ($i = 0; $i < count($columns); $i++) {
            $worksheet->getColumnDimension($this->getLetter($i))->setAutoSize(true);
        }
        return $this;
    }

    protected function addCellsToWorksheet(Worksheet $worksheet, $rows, $columns)
    {
        array_unshift($rows, $columns);

        foreach ($rows as $x => $row) {
            foreach ($row as $i => $value) {

                $worksheet->setCellValueExplicitByColumnAndRow($i + 1, $x + 1, $this->getCellValue($value),
                    $this->getCellType($value));
                $cell = $worksheet->getCellByColumnAndRow($i + 1, $x + 1);

                $this->setCellUrl($cell, $value);

                try {
                    $worksheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
                } catch (Exception $e) {
                }
            }
        }
        return $this;
    }

    abstract protected function getFileName();

    protected function setWorksheetTitle(Worksheet $worksheet, $form)
    {
        $invalidCharacters = $worksheet::getInvalidCharacters();
        //First strip form title, so we still have 30 charachters.
        $form_title = str_replace($invalidCharacters, '', $form['title']);

        $worksheet_title = substr(gf_apply_filters(
            array(
                "gfexcel_renderer_worksheet_title",
                $form['id'],
            ),
            $form_title, $form
        ), 0, 30);

        // Protect users from accidental override with invalid characters.
        $worksheet_title = str_replace($invalidCharacters, '', $worksheet_title);
        $worksheet->setTitle($worksheet_title);
        return $this;
    }

    /**
     * Get Cell type based on value booleans
     * @param $value
     * @return string
     */
    private function getCellType($value)
    {
        if ($value instanceof BaseValue) {
            if ($value->isNumeric()) {
                return DataType::TYPE_NUMERIC;
            }
            if ($value->isBool()) {
                return DataType::TYPE_BOOL;
            }
        }

        return DataType::TYPE_STRING;
    }

    /**
     * Retrieve the correctly formatted value of the cell
     * @param $value
     * @return string
     */
    private function getCellValue($value)
    {
        if ($value instanceof BaseValue) {
            return $value->getValue();
        }

        return $value;
    }

    /**
     * Set url on the cell if value has a url.
     *
     * @param Cell $cell
     * @param $value
     * @return bool
     */
    private function setCellUrl(Cell $cell, $value)
    {
        if (
            !$value instanceof BaseValue or
            !$value->getUrl() or
            gf_apply_filters(array('gfexcel_renderer_disable_hyperlinks'), false)
        ) {
            return false;
        }

        try {
            $cell->getHyperlink()->setUrl($value->getUrl());
            return true;
        } catch (Exception $e) {
            return false;
        }

    }

}
