<?php

namespace GFExcel\ServiceProvider;

use GFExcel\Action\ActionAwareInterface;
use GFExcel\Action\ActionInterface;
use GFExcel\Generator\HashGenerator;
use GFExcel\Generator\HashGeneratorInterface;
use GFExcel\Template\TemplateAwareInterface;
use GFExcel\Repository\FormRepository;
use GFExcel\Repository\FormRepositoryInterface;

/**
 * The service provider for the base of GFExcel.
 * @since $ver$
 */
class BaseServiceProvider extends AbstractServiceProvider
{
    /**
     * {@inheritdoc}
     * @since $ver$
     */
    protected $provides = [
        FormRepositoryInterface::class,
        HashGeneratorInterface::class,
    ];

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(
            FormRepositoryInterface::class,
            FormRepository::class
        )->addArgument(\GFAPI::class);

        $container->add(HashGeneratorInterface::class, HashGenerator::class);
    }

    /**
     * Retrieve all tagged actions from the container.
     * @since $ver$
     * @return ActionInterface[] The actions.
     */
    protected function getActions(): array
    {
        $container = $this->getContainer();
        if (!$container->has(ActionAwareInterface::ACTION_TAG)) {
            return [];
        }

        return $container->get(ActionAwareInterface::ACTION_TAG);
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function boot(): void
    {
        $container = $this->getContainer();

        $container
            ->inflector(ActionAwareInterface::class, function (ActionAwareInterface $instance) {
                $instance->setActions($this->getActions());
            });
        $container
            ->inflector(TemplateAwareInterface::class)
            ->invokeMethod('addTemplateFolder', [dirname(__FILE__, 3) . '/templates/']);
    }
}
