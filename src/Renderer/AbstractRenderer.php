<?php

namespace GFExcel\Renderer;

use GFExcel\Repository\FormsRepository;
use PhpOffice\PhpSpreadsheet\Calculation\LookupRef;

/**
 * Helper to have handy reusable functions.
 * @since 1.6.0
 */
abstract class AbstractRenderer
{
    /**
     * @param mixed[] $form The form object.
     * @param mixed[] $columns The columns to export.
     * @param mixed[] $rows THe rows to export.
     * @return mixed[] The matrix containing all rows.
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
	 *
	 * @param mixed[] $form The form object.
	 * @param mixed[] $matrix The matrix containing all rows and columns.
	 *
	 * @return mixed[] The transposed matrix.
	 */
	protected function transpose( array $form, $matrix ) {
		if ( ( new FormsRepository( $form['id'] ) )->isTransposed() ) {
			return LookupRef::TRANSPOSE( $matrix );
		}

		return $matrix;
	}
}
