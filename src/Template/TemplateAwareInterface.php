<?php

namespace GFExcel\Template;

/**
 * Interface that makes a class Template aware. This makes it able to register template paths and render templates.
 * @since $ver$
 */
interface TemplateAwareInterface
{
    /**
     * Adds a folder path to the class.
     * @since $ver$
     * @param string $folder The path to the folder.
     */
    public function addTemplateFolder(string $folder): void;

    /**
     * Should return whether a template is available.
     * @since $ver$
     * @param string $name The name of the template.
     * @return bool Whether the template is available.
     */
    public function hasTemplate(string $name): bool;

    /**
     * Should return the path to a template if it exists.
     * @since $ver$
     * @param string $name The name of the template
     * @return string|null The path to the template.
     */
    public function getTemplate(string $name): ?string;

    /**
     * Should render and echo out the template.
     * @since $ver$
     * @param string $name The name of the template.
     * @param mixed[] $context The context available to the template.
     */
    public function renderTemplate(string $name, array $context = []): void;
}
