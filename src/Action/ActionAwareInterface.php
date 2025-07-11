<?php

namespace GFExcel\Action;

/**
 * Interface that makes an addon action aware.
 * @since $ver$
 */
interface ActionAwareInterface
{
    /**
     * The string an Action must be tagged with.
     * @since $ver$
     * @var string
     */
    public const ACTION_TAG = 'gfexcel.action';

    /**
     * Should set all tagged actions on the class.
     * @since $ver$
     * @param ActionInterface[] $actions The actions.
     */
    public function setActions(array $actions): void;

    /**
     * Should return all tagged actions.
     * @since $ver$
     * @return ActionInterface[] The actions.
     */
    public function getActions(): array;

    /**
     * Should return whether this action is available.
     * @since $ver$
     * @param string $action The action name.
     * @return bool Whether this action is available.
     */
    public function hasAction(string $action): bool;

    /**
     * Should return the action.
     * @since $ver$
     * @param string $action The action name.
     * @return ActionInterface The action.
     * @throws \RuntimeException When action is not available.
     */
    public function getAction(string $action): ActionInterface;
}
