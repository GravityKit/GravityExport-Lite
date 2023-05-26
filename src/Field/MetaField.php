<?php

namespace GFExcel\Field;

use GFExcel\Values\BaseValue;

class MetaField extends BaseField implements RowsInterface
{
    /**
     * List of internal subfields.
     * @var string[]
     */
    protected $subfields = [
        'created_by' => 'GFExcel\Field\Meta\CreatedBy',
        'date_created' => 'GFExcel\Field\Meta\DateCreated',
	    '/gpml_ids_\d+/is' => 'GFExcel\Field\Meta\GPMediaLibrary',
    ];

    /**
     * {@inheritdoc}
     * @return BaseValue[]
     */
    public function getColumns()
    {
        if ($subfield = $this->getSubField()) {
            return $subfield->getColumns();
        }

        return parent::getColumns();
    }

    /**
     * {@inheritdoc}
     * @param array $entry
     * @return BaseValue[]
     */
    public function getCells($entry)
    {
        if ($subfield = $this->getSubField()) {
            return $subfield->getCells($entry);
        }

        $value = $this->getFieldValue($entry);
        $value = gf_apply_filters([
            'gfexcel_meta_value',
            $this->field->id,
            $this->field->formId,
        ], $value, $entry, $this->field);

        return $this->wrap([$value]);
    }

    /**
     * {@inheritdoc}
     * @return string
     */
    public function getValueType()
    {
        if (in_array($this->field->id, [
            'id',
            'form_id',
            'created_by'
        ])) {
            return BaseValue::TYPE_NUMERIC;
        }
        //default
        return BaseValue::TYPE_STRING;
    }

    /**
     * Returns a list of classnames map for meta fields. 'field' => 'FQN'
     * @return string[]
     */
    private function getSubFieldsClasses()
    {
        return gf_apply_filters([
            'gfexcel_transformer_subfields',
        ], $this->subfields);
    }

    /**
     * Get a subfield instance if available.
     * @return FieldInterface|null
     */
	private function getSubField() {
		// prevent endless loop, and be able to extend MetaField.
		if ( get_class( $this ) !== self::class ) {
			return null;
		}

		$fields = $this->getSubFieldsClasses();
		if ( array_key_exists( $this->field->id, $fields ) ) {
			return new $fields[ $this->field->id ]( $this->field );
		}

		foreach ( $fields as $name => $field ) {
			// Check if one of the fields is actually a regex.
			if ( @ preg_match( $name, $this->field->id ) ) {
				return new $field( $this->field );
			}
		}

		return null;
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function getRows( ?array $entry = null ): iterable {
		$subfield = $this->getSubField();
		if ($subfield instanceof RowsInterface) {
			yield from $subfield->getRows($entry);
		} else {
			yield $this->getCells($entry);
		}
	}
}
