<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcel;
use PHPExcel;
use PHPExcel_Cell_DataType;
use PHPExcel_IOFactory;

class PHPExcelRenderer implements RendererInterface
{
    /**
     * @var PHPExcel
     */
    private $PHPExcel;
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


        $this->addCellsToWorksheet()
            ->autoSizeColumns();

        return $this->renderOutput();
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

        $this->PHPExcel->getProperties()->setTitle($title);
        $this->worksheet->setTitle($title);
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

    private function addCellsToWorksheet()
    {
        $rows = $this->rows;
        array_unshift($rows, $this->columns);

        foreach ($rows as $x => $row) {
            foreach ($row as $i => $value) {
                $cell = $this->worksheet->setCellValueExplicitByColumnAndRow($i, $x + 1, $value,
                    PHPExcel_Cell_DataType::TYPE_STRING,
                    true);
                $this->worksheet->getStyle($cell->getCoordinate())->getAlignment()->setWrapText(true);
            }
        }
        return $this;
    }

    // Returns the columnname based on the input; (A -> ZZ)
    private function getLetter($i)
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

    private function autoSizeColumns()
    {
        for ($i = 0; $i < count($this->columns); $i++) {
            $this->worksheet->getColumnDimension($this->getLetter($i))->setAutoSize(true);
        }
        return $this;
    }

    private function setProperties()
    {
        $this->PHPExcel->getProperties()
            ->setCompany("SQUID Media")
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

    private function getFileName()
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

    private function renderOutput()
    {
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
}