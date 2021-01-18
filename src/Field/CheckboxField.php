<?php

namespace GFExcel\Field;

/**
 * Field transformer for `checkbox` fields.
 * @since $ver$
 */
class CheckboxField extends BaseField implements RowsInterface
{
    /**
     * @inheritdoc
     *
     * Overwritten for phpdoc
     *
     * @var \GF_Field_Checkbox
     */
    protected $field;

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function __construct(\GF_Field $field)
    {
        parent::__construct($field);

        // Normal glue
        add_filter('gfexcel_combiner_glue_checkbox', static function () {
            return ', ';
        });
    }

    /**
     * @inheritdoc
     * @since $ver$
     */
    public function getRows(?array $entry = null): iterable
    {
        foreach ($this->field->get_entry_inputs() as $input) {
            $index = (string) $input['id'];
            if (!rgempty($index, $entry)) {
                $value = \GFCommon::selection_display(rgar($entry, $index), $this->field, rgar($entry, 'currency'));

                yield $this->wrap([$value]);
            }
        }
    }
}
