<?php
/**
 * Button template that renders a button.
 * @var GFExcel\Addon\AddonInterface $this
 * @var string[] $attributes Additional attributes.
 * @var string $label The button label.
 */
echo sprintf(
	'<button type="submit" name="%s" value="%s" %s>%s %s</button>',
	esc_attr( $name ?? 'gfexcel-action' ),
	esc_attr( $value ?? '' ),
	implode( ' ', $attributes ),
	$icon ?? '',
	esc_attr( $label )
);
