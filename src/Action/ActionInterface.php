<?php

namespace GFExcel\Action;

/**
 * The interface an action should adhere to.
 * @since 2.4.0
 */
interface ActionInterface
{
    /**
     * Should return a unique name for the action.
     * @since 2.4.0
     * @return string The name.
     */
    public function getName(): string;

    /**
     * Performs the action.
     * @since 2.4.0
     * @param \GFAddOn $addon The Add on instance.
     * @param array $form The form object.
     */
    public function fire(\GFAddOn $addon, array $form): void;
}
