<?php

namespace GFExcel\Generator;

interface HashGeneratorInterface
{
    /**
     * Should return a unique hash.
     * @since 2.4.0
     * @return string
     */
    public function generate(): string;
}
