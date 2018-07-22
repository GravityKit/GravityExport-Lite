<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use GFExcel\Values\BaseValue;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use Exception;

abstract class AbstractPHPExcelRenderer
{
    /** @var Spreadsheet */
    protected $spreadsheet;


    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
    }

    public function renderOutput($extension = 'xlsx')
    {
        register_shutdown_function([$this, "fatal_handler"]);

        $exception = null;
        try {
            $this->spreadsheet->setActiveSheetIndex(0);
            $objWriter = IOFactory::createWriter($this->spreadsheet, ucfirst($extension));

            header('Content-Type: application/vnd.ms-excel');
            header('Content-Disposition: attachment;filename="' . $this->getFileName() . '"');
            header('Cache-Control: max-age=1');

            header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
            header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
            header('Pragma: public'); // HTTP/1.0

            $objWriter->save('php://output');
        } catch (\Throwable $e) {
            $exception = $e;
        } catch (\Exception $e) {
            //in case of php5.x
            $exception = $e;
        }

        if ($exception) {
            header('Content-Type: text/html');
            header_remove('Content-Disposition');
            $this->handleException($exception);
        }

        exit; // stop rest
    }

    /**
     * Handle fatal error during execution
     * @throws \Exception
     */
    public function fatal_handler()
    {
        $error = error_get_last();
        if ($error['type'] === E_ERROR) {
            $exception = new Exception($error['message']);
            $this->handleException($exception);
        }
    }

    protected function autoSizeColumns(Worksheet $worksheet, $columns)
    {
        for ($i = 1; $i <= count($columns); $i++) {
            $worksheet->getColumnDimensionByColumn($i)->setAutoSize(true);
        }
        return $this;
    }

    /**
     * @param Worksheet $worksheet
     * @param $rows
     * @param $columns
     * @return $this
     * @throws \GFExcel\Exception\Exception
     */
    protected function addCellsToWorksheet(Worksheet $worksheet, $rows, $columns)
    {
        array_unshift($rows, $columns);

        foreach ($rows as $x => $row) {
            foreach ($row as $i => $value) {

                $worksheet->setCellValueExplicitByColumnAndRow($i + 1, $x + 1, $this->getCellValue($value),
                    $this->getCellType($value));
                $cell = $worksheet->getCellByColumnAndRow($i + 1, $x + 1);
                
                try {
                    $this->setProperties($cell, $value);
                    $worksheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
                } catch (Exception $e) {
                    $this->handleException($e);
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

    /**
     * @param \Throwable|Exception $exception
     */
    private function handleException($exception)
    {
        global $wp_version;

        echo "<p><strong>Gravity Forms Entries in Excel: Whoops, unfortunantly something broke</strong></p>";
        echo "<p>Error message: " . $exception->getMessage() . " </p>";
        echo "<p>If you need support for this, please contact me via the <a target='_blank' href='https://wordpress.org/support/plugin/gf-entries-in-excel'>support forum</a> on the wordpress plugin.</p>";
        echo "<p>Check if someone else had the same error, before posting a new support question.<br/>And when opening a new question, ";
        echo "please use the error message (" . $exception->getMessage() . ") as the title,<br/> and include the following details in your message:</p>";
        echo "<ul>";
        echo "<li>Plugin Version: " . GFExcel::$version . "</li>";
        echo "<li>PHP Version: " . PHP_VERSION;
        if (version_compare(PHP_VERSION, '5.6.1', '<')) {
            echo " (this version is too low, please update to at least PHP 5.6)";
        }
        echo "</li>";
        echo "<li>Wordpress Version: " . $wp_version . "</li>";
        echo "<li>Error message: " . $exception->getMessage() . "</li>";
        echo "<li>Error stack trace:<br/><br/>" . nl2br($exception->getTraceAsString()) . "</li>";
        echo "</ul>";
        exit;
    }

    /**
     * @param Cell $cell
     * @param $value
     * @throws \GFExcel\Exception\Exception
     */
    private function setProperties(Cell $cell, $value)
    {
        $this->setCellUrl($cell, $value);
        $this->setFontStyle($cell, $value);
    }

    /**
     * @param Cell $cell
     * @param $value
     * @return bool
     * @throws \GFExcel\Exception\Exception
     */
    private function setFontStyle(Cell $cell, $value)
    {

        if (!$value instanceof BaseValue) {
            return false;
        }

        try {
            if ($value->isBold()) {
                $cell->getStyle()->getFont()->setBold(true);
            }
            if ($value->isItalic()) {
                $cell->getStyle()->getFont()->setItalic(true);
            }
            if ($color = $value->getColor()) {
                $color_field = $cell->getStyle()->getFont()->getColor();
                $color_field->setRGB($color);
                $cell->getStyle()->getFont()->setColor($color_field);
            }
            if ($color = $value->getBackgroundColor()) {
                $fill = $cell->getStyle()->getFill()->setFillType(Fill::FILL_SOLID);
                $color_field = $fill->getStartColor()->setRGB($color);
                $fill->setStartColor($color_field);
            }

            return true;
        } catch (\GFExcel\Exception\Exception $e) {
            throw $e;
        } catch (Exception $e) {
            return false;
        }
    }

}
