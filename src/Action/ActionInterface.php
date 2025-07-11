<?php

namespace GFExcel\Action;

/**
 * The interface an action should adhere to.
 * @since $ver$
 */
interface ActionInterface
{
    /**
     * Should return a unique name for the action.
     * @since $ver$
     * @return string The name.
     */
    public function getName(): string;

    /**
     * Performs the action.
     * @since $ver$
     * @param \GFAddOn $addon The Add on instance.
     * @param array $form The form object.
     */
    public function fire(\GFAddOn $addon, array $form): void;
}
