<?php

namespace GFExcel\GravityForms\Field;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;

/**
 * A Sortable settings field.
 * @since $ver$
 */
class Sortable extends Base
{
    /**
     * @inheritdoc
     * @since $ver$
     */
    public $type = 'sortable';

    /**
     * Wrapper to move the selected field to.
     * @since $ver$
     * @var string
     */
    public $move_to;

    /**
     * The side.
     * @since $ver$
     * @var string
     */
    public $side;

    /**
     * The provided choices for the field.
     * @since $ver$
     * @var mixed[]
     */
    public $choices;

    /**
     * @inheritDoc
     * @since $ver$
     */
    public function markup(): string
    {
        $html[] = sprintf(
            '<input type="hidden" name="%s_%s" value="%s">',
            $this->settings->get_input_name_prefix(),
            $this->name,
            esc_attr($this->get_value())
        );

        $html[] = sprintf(
            '<ul id="%s" %s data-send-to="%s">%s</ul>',
            $this->name,
            implode(' ', $this->get_attributes()),
            $this->move_to,
            implode("\n", array_map(\Closure::fromCallable([$this, 'choiceHtml']), $this->choices))
        );

        return implode("\n", $html);
    }

    /**
     * Returns the html for a choice.
     * @since $ver$
     * @param mixed[] $choice The choice object.
     * @return string The HTML for this choice.
     */
    protected function choiceHtml(array $choice): string
    {
        return sprintf(
            '<li data-value="%s">
                <div class="field"><i class="fa fa-bars"></i> %s</div>
                <div class="move">
                    <i class="fa fa-arrow-right"></i>
                    <i class="fa fa-close"></i>
                </div>
            </li>',
            $choice['value'], $choice['label']
        );
    }
}
