<?php


namespace GFExcel\Renderer;


use PHPExcel_Cell_DataType;
use PHPExcel_IOFactory;

abstract class AbstractPHPExcelRenderer
{
    /** @var \PHPExcel */
    protected $PHPExcel;

    public function renderOutput()
    {
        $this->PHPExcel->setActiveSheetIndex(0);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $this->getFileName() . '"');
        header('Cache-Control: max-age=1');

        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT'); // always modified
        header('Cache-Control: cache, must-revalidate'); // HTTP/1.1
        header('Pragma: public'); // HTTP/1.0

        $objWriter = PHPExcel_IOFactory::createWriter($this->PHPExcel, 'Excel5');
        $objWriter->save('php://output');

        exit; // stop rest
    }

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

    protected function autoSizeColumns(\PHPExcel_Worksheet $worksheet, $columns)
    {
        for ($i = 0; $i < count($columns); $i++) {
            $worksheet->getColumnDimension($this->getLetter($i))->setAutoSize(true);
        }
        return $this;
    }

    protected function addCellsToWorksheet(\PHPExcel_Worksheet $worksheet, $rows, $columns)
    {
        array_unshift($rows, $columns);

        foreach ($rows as $x => $row) {
            foreach ($row as $i => $value) {

                $cell = $worksheet->setCellValueExplicitByColumnAndRow($i, $x + 1, $value,
                    PHPExcel_Cell_DataType::TYPE_STRING,
                    true);

                if ($this->_isUrl($value) && !gf_apply_filters(array('gfexcel_renderer_disable_hyperlinks'), false)) {
                    $cell->getHyperlink()->setUrl(trim(strip_tags($value)));
                }
                $worksheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
            }
        }
        return $this;
    }

    abstract protected function getFileName();

    /**
     * Quick test if value is a url.
     * @param $value
     * @return bool
     */
    protected function _isUrl($value)
    {
        return !!preg_match('%^(https?|ftps?)://([A-Z0-9][A-Z0-9_-]*(?:.[A-Z0-9][A-Z0-9_-]*)+):?(d+)?/?%i', $value);
    }

    protected function setWorksheetTitle(\PHPExcel_Worksheet $worksheet, $form)
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

}