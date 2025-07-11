<?php
/**
 * Template for filtering and downloading.
 * @var string $form THe target form id.
 * @var GFExcel\Addon\AddonInterface $this
 */
?>
<div class="download-block">
    <div class="date-field">
        <input form="<?= $form ?>" autocomplete="off" placeholder="YYYY-MM-DD" type="text" id="start_date" name="start_date"/>
        <label for="start_date"><?= esc_html__('Start', 'gravityforms'); ?></label>
    </div>

    <div class="date-field">
        <input form="<?= $form ?>" autocomplete="off" placeholder="YYYY-MM-DD" type="text" id="end_date" name="end_date"/>
        <label for="end_date"><?= esc_html__('End', 'gravityforms'); ?></label>
    </div>

    <div class="download-button">
        <?= $this->single_setting([
            'type' => 'button',
            'form' => $form,
            'class' => 'button-primary',
            'label' => esc_html__('Download'),
            'icon' => '<i class="fa fa-download"></i>',
        ]) ?>
    </div>
</div>
