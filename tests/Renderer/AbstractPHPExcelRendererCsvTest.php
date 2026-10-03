<?php

namespace GFExcel\Tests\Renderer;

use GFExcel\Renderer\AbstractPHPExcelRenderer;
use GFExcel\Tests\TestCase;

/**
 * Checks that the CSV export never writes a cell a spreadsheet app would run as a formula.
 *
 * @since TBD
 */
class AbstractPHPExcelRendererCsvTest extends TestCase
{
    /**
     * The file the renderer saves the CSV to.
     *
     * @since TBD
     * @var string
     */
    private $file;

    /**
     * @inheritdoc
     * @since TBD
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->file = tempnam(sys_get_temp_dir(), 'gfexcel-csv-');

        \WP_Mock::userFunction('get_temp_dir', ['return' => dirname($this->file) . '/']);
        \WP_Mock::userFunction('gf_do_action');
    }

    /**
     * @inheritdoc
     * @since TBD
     */
    public function tearDown(): void
    {
        if (is_string($this->file) && file_exists($this->file)) {
            unlink($this->file);
        }

        parent::tearDown();
    }

    /**
     * Text that starts like a formula gets a quote in front; plain numbers and text stay as they are.
     *
     * @since TBD
     */
    public function testFormulaLikeTextIsPrefixedWithAQuote(): void
    {
        // Every filter returns its default value.
        \WP_Mock::userFunction('gf_apply_filters', ['return_arg' => 1]);

        $cases = [
            '=HYPERLINK("https://evil.example","Claim")' => "'=HYPERLINK(\"https://evil.example\",\"Claim\")",
            '@SUM(1+1)' => "'@SUM(1+1)",
            '+1+1' => "'+1+1",
            '-1+1' => "'-1+1",
            "\t=1+1" => "'\t=1+1",
            "\n=1+1" => "'\n=1+1",
            "\r=1+1" => "'\n=1+1",
            ' =1+1' => "' =1+1",
            "\0=1+1" => "'\0=1+1",
            "\0 \0=1+1" => "'\0 \0=1+1",
            '-5 ' => '-5 ',
            '-5' => '-5',
            '+31' => '+31',
            '-1.5' => '-1.5',
            'Jane' => 'Jane',
            '' => '',
        ];

        $cells = $this->exportColumn(array_keys($cases));

        $this->assertSame(array_merge(['Value'], array_values($cases)), $cells);
    }

    /**
     * The filter turns the prefix off for sites that need the raw values.
     *
     * @since TBD
     */
    public function testFilterCanTurnTheQuoteOff(): void
    {
        // Set up the specific filter first; Mockery uses the first expectation that matches.
        \WP_Mock::userFunction('gf_apply_filters', [
            'args' => [['gk/gravityexport/renderer/csv/escape-formulas', 7], true, ['id' => 7]],
            'return' => false,
        ]);
        \WP_Mock::userFunction('gf_apply_filters', ['return_arg' => 1]);

        $this->assertSame(['Value', '=1+1'], $this->exportColumn(['=1+1']));
    }

    /**
     * An empty enclosure is put back, so a delimiter inside a value cannot start a new, unchecked cell.
     *
     * @since TBD
     */
    public function testEmptyEnclosureIsRestored(): void
    {
        \WP_Mock::userFunction('gf_apply_filters', [
            'args' => [['gfexcel_renderer_csv_enclosure', 7], '"', 7],
            'return' => '',
        ]);
        \WP_Mock::userFunction('gf_apply_filters', ['return_arg' => 1]);

        $this->assertSame(['Value', 'x,=1+1'], $this->exportColumn(['x,=1+1']));
    }

    /**
     * Exports one column through the real renderer and CSV writer, and reads the saved file back.
     *
     * @since TBD
     *
     * @param string[] $values The cell values below the header.
     *
     * @return string[] The cells as read from the CSV file, header first.
     */
    private function exportColumn(array $values): array
    {
        $file_name = basename($this->file);

        $renderer = new class($file_name) extends AbstractPHPExcelRenderer {
            private $file_name;

            public function __construct(string $file_name)
            {
                parent::__construct();
                $this->file_name = $file_name;
            }

            public function handle($form, $columns, $rows, $save = false)
            {
                $this->form = $form;
                $this->addCellsToWorksheet($this->spreadsheet->getActiveSheet(), array_merge([$columns], $rows), (int) $form['id']);

                return $this->renderOutput('csv', $save);
            }

            protected function getFileName()
            {
                return $this->file_name;
            }
        };

        $rows = array_map(static function ($value) {
            return [$value];
        }, $values);

        $saved = $renderer->handle(['id' => 7], ['Value'], $rows, true);

        $this->assertSame($this->file, $saved);

        $handle = fopen($saved, 'rb');
        $cells = [];

        while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
            $cells[] = $row[0];
        }

        fclose($handle);

        return $cells;
    }
}
