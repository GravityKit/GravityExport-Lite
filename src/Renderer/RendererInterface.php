<?php

namespace GFExcel\Renderer;


interface RendererInterface
{
    public function handle($form, $columns, $rows, $save = false);
}