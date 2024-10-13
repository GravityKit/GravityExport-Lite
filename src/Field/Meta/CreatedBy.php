<?php

namespace GFExcel\Field\Meta;

use GFExcel\Field\MetaField;
use GFExcel\Values\BaseValue;

class CreatedBy extends MetaField
{
    /** @var string */
    public const USER_ID = 'user_id';

    /** @var string */
    public const NICKNAME = 'nickname';

    /** @var string */
    public const DISPLAY_NAME = 'display_name';

    /**
     * {@inheritdoc}
     * Replace value username or something else.
     */
    protected function getFieldValue($entry, $input_id = '')
    {
        $property = $this->getPropertyName();
        $user_id = (int) parent::getFieldValue($entry, $input_id);

        if ($property === self::USER_ID) {
            return $user_id;
        }

        return $this->getUserName($user_id, $property);
    }

    /**
     * {@inheritdoc}
     * Set value type to string, when value is not a user id.
     */
    public function getValueType()
    {
        $property = $this->getPropertyName();
        if ($property === self::USER_ID) {
            return parent::getValueType();
        }

        return BaseValue::TYPE_STRING;
    }

    /**
     * @param int $user_id The user id.
     * @param string $property The property to use as a username.
     * @return string|int The returned value.
     * @throws \InvalidArgumentException
     */
    private function getUserName($user_id, $property = 'nickname')
    {
	    $user = get_userdata( $user_id );
	    if ( ! $user ) {
		    // no user id or no user, return default.
		    return $user_id;
	    }

        if (!isset($user->$property)) {
            throw new \InvalidArgumentException(sprintf('User object does not contain the property \'%s\'', $property));
        }

        return $user->$property;
    }

    /**
     * Get the property used for this field.
     * @return string
     */
    private function getPropertyName()
    {
        return gf_apply_filters([
            'gfexcel_meta_created_by_property',
            $this->field->formId,
        ], self::USER_ID, $this->field);
    }
}
