<?php

namespace GFExcel\Field;

/**
 * Field transformer for `checkbox` fields.
 * @since 1.8.8
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
     * @since 1.8.8
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
     * @since 1.8.8
     */
    public function getRows(?array $entry = null): iterable
    {
        foreach ($this->field->get_entry_inputs() as $input) {
            $index = (string) $input['id'];
            if (!rgempty($index, $entry)) {
                $value = $this->getFieldValue($entry, $index);

                $value = gf_apply_filters([
                    'gfexcel_field_value',
                    $this->field->get_input_type(),
                    $this->field->formId,
                    $this->field->id
                ], $value, $entry, $this->field);

                yield $this->wrap([$value]);
            }
        }
    }
}
