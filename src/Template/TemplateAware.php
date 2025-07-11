<?php

namespace GFExcel\Template;

/**
 * Trait to complement {@see \GFExcel\Template\TemplateAwareInterface}.
 * @since 2.4.0
 */
trait TemplateAware
{
    /**
     * Holds the folders that contain templates.
     * @since 2.4.0
     * @var string[]
     */
    protected $template_folders = [];

   /**`
    * {@inheritdoc}
    * @since 2.4.0
    */
    public function addTemplateFolder(string $folder): void
    {
        if (!is_dir($folder)) {
            throw new \InvalidArgumentException(sprintf('Template folder "%s" not found.', $folder));
        }

        $this->template_folders[] = $folder;
    }

    /**
     * {@inheritdoc}
     * @since 2.4.0
     */
    public function hasTemplate(string $name): bool
    {
        foreach ($this->template_folders as $folder) {
            $file = sprintf('%s/%s.php', $folder, $name);
            if (file_exists($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     * @since 2.4.0
     */
    public function getTemplate(string $name): ?string
    {
        foreach ($this->template_folders as $folder) {
            $file = sprintf('%s/%s.php', $folder, $name);
            if (file_exists($file)) {
                return $file;
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     * @since 2.4.0
     */
    public function renderTemplate(string $name, array $context = []): void
    {
        $file = $this->getTemplate($name);

        // Scope the template in a function to avoid variable leakage.
        $template = function (string $file, array $context): void {
            // set all provided arguments as variables.
            extract($context);
            require $file;
        };

        // call scope.
        $template($file, $context);
    }
}
