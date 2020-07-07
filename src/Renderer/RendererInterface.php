<?php

namespace GFExcel\Renderer;

interface RendererInterface
{
    /**
     * Handles the rendering.
     * @param mixed[] $form The form object.
     * @param mixed[] $columns The columns to export.
     * @param mixed[] $rows The rows to export.
     * @param bool $save Whether to save the file.
     */
    public function handle($form, $columns, $rows, $save = false);
}
