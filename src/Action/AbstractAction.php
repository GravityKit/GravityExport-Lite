<?php

namespace GFExcel\Action;

abstract class AbstractAction implements ActionInterface
{
    /**
     * A unique name representing the action.
     * @since $ver$
     * @var string
     */
    public static $name = '';

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function getName(): string
    {
        if (empty(static::$name)) {
            throw new \LogicException(sprintf(
                'Action "%s" should implement a $name variable.',
                static::class
            ));
        }

        return static::$name;
    }
}
