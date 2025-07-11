<?php

namespace GFExcel\Generator;

/**
 * Generates a random hash.
 * @since 2.4.0
 */
class HashGenerator implements HashGeneratorInterface
{
    /**
     * @inheritdoc
     * @since 2.4.0
     * @throws \Exception When it was not possible to gather sufficient entropy.
     */
    public function generate(): string
    {
        return bin2hex(random_bytes(16));
    }
}
