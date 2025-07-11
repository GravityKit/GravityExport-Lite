<?php

namespace GFExcel\Generator;

interface HashGeneratorInterface
{
    /**
     * Should return a unique hash.
     * @since $ver$
     * @return string
     */
    public function generate(): string;
}
