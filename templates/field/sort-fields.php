<?php

use GFExcel\GFExcel;
use GFExcel\AddOn\AddonInterface;

/**
 * Sort fields template that renders the sorting lists.
 * @var \GF_Field[][] $choices The fields to sort.
 * @var string $name The field name.
 * @var AddonInterface|\GFAddOn $this The addon using this field.
 */

$value = $this->get_setting($name, []);
?>
<div class="gfexcel_field-sort-fields">
    <div>
        <p><strong><?php esc_html_e('Disabled fields', GFExcel::$slug) ?></strong></p>

        <input type="hidden" name="_gaddon_setting_<?= $name ?>[disabled]" value="<?= $value['disabled'] ?? '' ?>">
        <ul id="sort-fields-disabled" class="fields-select fields-select--disabled" data-send-to="sort-fields-enabled">
            <?php foreach ($choices['disabled'] as $field): ?>
                <li data-value="<?= $field->id ?>">
                    <div class="field">
                        <i class="fa fa-bars"></i>
                        <?= $field->get_field_label(true, '') ?>
                    </div>
                    <div class="move">
                        <i class="fa fa-arrow-right"></i>
                        <i class="fa fa-trash"></i>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
    <div>
        <p><strong><?php esc_html_e('Enable & sort the fields', GFExcel::$slug) ?></strong></p>

        <input type="hidden" name="_gaddon_setting_<?= $name ?>[enabled]" value="<?= $value['enabled'] ?? '' ?>">
        <ul id="sort-fields-enabled" class="fields-select fields-select--enabled" data-send-to="sort-fields-disabled">
            <?php foreach ($choices['enabled'] as $field): ?>
                <li data-value="<?= $field->id ?>">
                    <div class="field">
                        <i class="fa fa-bars"></i>
                        <?= $field->get_field_label(true, '') ?>
                    </div>
                    <div class="move">
                        <i class="fa fa-arrow-right"></i>
                        <i class="fa fa-trash"></i>
                    </div>
                </li>
            <?php endforeach ?>
        </ul>
    </div>
</div>
