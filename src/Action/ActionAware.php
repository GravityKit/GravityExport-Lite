<?php

namespace GFExcel\Action;

trait ActionAware
{
    /**
     * Holds the actions for the class.
     * @since $ver$
     * @var ActionInterface[]
     */
    protected $actions = [];

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function setActions(array $actions): void
    {
        $this->actions = array_reduce($actions, static function (array $actions, ActionInterface $action): array {
            return array_merge($actions, [$action->getName() => $action]);
        }, []);
    }

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function getActions(): array
    {
        return $this->actions;
    }

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function hasAction(string $action): bool
    {
        return array_key_exists($action, $this->actions);
    }

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function getAction(string $action): ActionInterface
    {
        if (!$this->hasAction($action)) {
            throw new \RuntimeException(sprintf('Action "%s" is not implemented.', $action));
        }

        return $this->actions[$action];
    }
}
