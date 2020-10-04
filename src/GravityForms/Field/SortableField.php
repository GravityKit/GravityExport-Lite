<?php

namespace GFExcel\GravityForms\Field;

use Rocketgenius\Gravity_Forms\Settings\Fields\Base;

/**
 * The sortable fields setting.
 * @since $ver$
 */
class SortableField extends Base
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
            '<input type="hidden" name="%s" value="%s">',
            esc_attr(implode('_', [$this->settings->get_input_name_prefix(), $this->name])),
            esc_attr($this->get_value())
        );

        $html[] = sprintf(
            '<ul id="%1$s" %2$s data-send-to="%4$s">%3$s</ul>',
            $this->name,
            implode(' ', $this->get_attributes()),
            implode("\n", array_map(\Closure::fromCallable([$this, 'choiceHtml']), $this->choices)),
            $this->move_to
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
