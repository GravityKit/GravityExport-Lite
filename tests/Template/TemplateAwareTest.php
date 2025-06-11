<?php

namespace GFExcel\Tests\Template;

use GFExcel\Template\TemplateAware;
use PHPUnit\Framework\TestCase;

/**
 * Test class for {@see TemplateAware} trait.
 * @since $ver$
 */
class TemplateAwareTest extends TestCase
{
    /**
     * The trait under test.
     * @since $ver$
     * @var TemplateAware
     */
    private $trait;

    /**
     * {@inheritdoc}
     * @since $ver$
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = new class {
          use TemplateAware;
        };

        $this->trait->addTemplateFolder(__DIR__ . '/assets/folder1');
        $this->trait->addTemplateFolder(__DIR__ . '/assets/folder2');
    }

    /**
     * Test case for {@see TemplateAware::addTemplateFolder} With an invalid folder.
     * @since $ver$
     */
    public function testAddTemplateFolderException(): void
    {
        $folder = __DIR__ . '/assets/non-existent';
        $this->expectExceptionMessage(sprintf(
            'Template folder "%s" not found.',
            $folder
        ));
        $this->trait->addTemplateFolder($folder);
    }

    /**
     * Test case for {@see TemplateAware::hasTemplate}.
     * @since $ver$
     */
    public function testHasTemplate(): void
    {
        $this->assertTrue($this->trait->hasTemplate('file1'));
        $this->assertTrue($this->trait->hasTemplate('file2'));
        $this->assertFalse($this->trait->hasTemplate('non-existent'));
    }

    /**
     * Test case for {@see TemplateAware::getTemplate}.
     * @since $ver$
     */
    public function testGetTemplate(): void
    {
        $this->assertEquals(__DIR__ . '/assets/folder1/file1.php', $this->trait->getTemplate('file1'));
        $this->assertEquals(__DIR__ . '/assets/folder2/file2.php', $this->trait->getTemplate('file2'));
        $this->assertNull($this->trait->getTemplate('non-existent'));
    }

    /**
     * Test case for {@see TemplateAware::renderTemplate}.
     * @since $ver$
     * @return string[] The variables for each test.
     */
    public function dataProviderForTestRenderTemplate(): array
    {
        return [
            'file1' => ['file1', 'Content of file1.php'],
            'file 2' => ['file2', 'Content of file2.php'],
            'file_with_variables' => [
                'file_with_variables',
                'Four Five Six',
                [
                    'one' => 'Four',
                    'two' => 'Five',
                    'three' => 'Six',
                ]
            ],
        ];
    }

    /**
     * Test case for {@see TemplateAware::renderTemplate}.
     * @since $ver$
     * @param string $template The template to render.
     * @param string $expected_content The expected output of the render.
     * @param array $context The context provided to the template.
     * @dataProvider dataProviderForTestRenderTemplate The data provider.
     */
    public function testRenderTemplate(string $template, string $expected_content, array $context = []): void
    {
        $this->expectOutputString($expected_content);
        $this->trait->renderTemplate($template, $context);
    }
}
