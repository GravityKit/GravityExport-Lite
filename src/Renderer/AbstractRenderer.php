<?php

namespace GFExcel\Renderer;

use GFExcel\GFExcelConfigConstants;
use PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

/**
 * Helper to have handy reusable functions.
 * @since $ver$
 */
abstract class AbstractRenderer
{

    /**
     * @param array $form
     * @param $columns
     * @param $rows
     * @return mixed
     */
    protected function getMatrix(array $form, $columns, $rows)
    {
        array_unshift($rows, $columns);

        return gf_apply_filters([
            'gfexcel_renderer_matrix',
            $form['id'],
        ], $this->transpose($form, $rows));
    }

    /**
     * Transpose the matrix to flip rows and columns.
     * @param array $form
     * @param $matrix
     * @return array
     */
    protected function transpose(array $form, $matrix)
    {
        $transpose = false;
        if (array_key_exists(GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE, $form)) {
            $transpose = (bool) $form[GFExcelConfigConstants::GFEXCEL_RENDERER_TRANSPOSE];
        }

        if (!gf_apply_filters(['gfexcel_renderer_transpose', $form['id']], $transpose)) {
            return $matrix;
        }

        return LookupRef::TRANSPOSE($matrix);
    }
}
