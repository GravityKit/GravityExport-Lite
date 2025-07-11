<?php

namespace GFExcel\Tests\Template;

use Com\Tecnick\Color\Model\Template;
use GFExcel\Template\TemplateAware;
use GFExcel\Template\TemplateAwareInterface;
use PHPUnit\Framework\TestCase;

/**
 * Test class for {@see TemplateAware} trait.
 * @since 2.4.0
 */
class TemplateAwareTest extends TestCase
{
    /**
     * The trait under test.
     * @since 2.4.0
     * @var TemplateAwareInterface
     */
    private $trait;

    /**
     * {@inheritdoc}
     * @since 2.4.0
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->trait = new class implements TemplateAwareInterface{
          use TemplateAware;
        };

        $this->trait->addTemplateFolder(__DIR__ . '/assets/folder1');
        $this->trait->addTemplateFolder(__DIR__ . '/assets/folder2');
    }

    /**
     * Test case for {@see TemplateAware::addTemplateFolder} With an invalid folder.
     * @since 2.4.0
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
     * @since 2.4.0
     */
    public function testHasTemplate(): void
    {
        $this->assertTrue($this->trait->hasTemplate('file1'));
        $this->assertTrue($this->trait->hasTemplate('file2'));
        $this->assertFalse($this->trait->hasTemplate('non-existent'));
    }

    /**
     * Test case for {@see TemplateAware::getTemplate}.
     * @since 2.4.0
     */
    public function testGetTemplate(): void
    {
        $this->assertEquals(__DIR__ . '/assets/folder1/file1.php', $this->trait->getTemplate('file1'));
        $this->assertEquals(__DIR__ . '/assets/folder2/file2.php', $this->trait->getTemplate('file2'));
        $this->assertNull($this->trait->getTemplate('non-existent'));
    }

    /**
     * Test case for {@see TemplateAware::renderTemplate}.
     * @since 2.4.0
     * @return array The variables for each test.
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
     * @since 2.4.0
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
