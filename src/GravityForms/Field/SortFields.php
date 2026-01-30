<?php

namespace GFExcel\GravityForms\Field;

use Gravity_Forms\Gravity_Forms\Settings\Fields\Base;

/**
 * A Sortable settings field.
 * @since 2.0.0
 */
class SortFields extends Base {
	/**
	 * @inheritdoc
	 * @since 2.0.0
	 */
	public $type = 'sortable';

	/**
	 * The side.
	 * @since 2.0.0
	 * @var string
	 */
	public $side;

	/**
	 * The provided choices for the field.
	 * @since 2.0.0
	 * @var mixed[]
	 */
	public $choices;

	/**
	 * The sections with sortable fields.
	 *
	 * The keys are the section ids, the values are an array / tuple of heading + target section.
	 * Example: [ 'left' => [ 'Left title', 'right'] ], ['right' => [ 'Right title', 'left'] ]
	 *
	 * @since 2.0.0
	 * @var array<string, array<string>>
	 */
	public $sections = [];

	/**
	 * Whether the labels can be admin labels.
	 * @since 2.1.0
	 * @var bool
	 */
	public $use_admin_labels = false;

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function __construct( $props, $settings ) {
		add_filter(
			'gaddon_no_output_field_properties',
			\Closure::fromCallable( [ $this, 'no_output_field_properties' ] )
		);

		parent::__construct( $props, $settings );
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function markup(): string {
		$search_id          = $this->name . '-search';
		$search_placeholder = esc_attr__( 'Filter fields…', 'gk-gravityexport-lite' );
		$search_label       = esc_html__( 'Filter fields', 'gk-gravityexport-lite' );

		$status_id = $this->name . '-search-status';

		$html = [
			'<div class="gk-gravityexport-sort-fields">',
			sprintf(
				'<div class="gk-gravityexport-sort-fields__search">
					<label for="%s" class="screen-reader-text">%s</label>
					<div class="gk-gravityexport-sort-fields__search-input-wrapper">
						<input type="search" id="%s" class="gk-gravityexport-sort-fields__search-input" placeholder="%s" autocomplete="off" aria-controls="%s %s" aria-describedby="%s">
						<span class="gk-gravityexport-sort-fields__search-icon" aria-hidden="true">
							<i class="fa fa-search"></i>
						</span>
					</div>
					<div id="%s" class="gk-gravityexport-sort-fields__search-status" role="status" aria-live="polite">
						<span class="gk-gravityexport-sort-fields__search-status-text"></span>
						<a href="#" class="gk-gravityexport-sort-fields__clear-search">%s</a>
					</div>
				</div>',
				esc_attr( $search_id ),
				$search_label,
				esc_attr( $search_id ),
				$search_placeholder,
				esc_attr( $this->name . '-enabled' ),
				esc_attr( $this->name . '-disabled' ),
				esc_attr( $status_id ),
				esc_attr( $status_id ),
				esc_html__( 'Clear search', 'gk-gravityexport-lite' )
			),
		];

		foreach ( $this->sections as $section => [$heading, $target] ) {
			$html[] = sprintf( '<div><p><strong>%s</strong></p>', $heading );
			$value  = rgars( $this->settings->get_current_values(), sprintf( '%s/%s', $this->get_parsed_name(), $section ), '' );

			$html[] = sprintf(
				'<input type="hidden" name="%s_%s[%s]" value="%s">',
				$this->settings->get_input_name_prefix(),
				$this->name,
				$section,
				esc_attr( $value )
			);

			$html[] = sprintf(
				'<ul id="%s" %s data-send-to="%s" data-list-label="%s" class="fields-select" role="listbox" aria-label="%s">%s</ul>',
				$this->name . '-' . $section,
				implode( ' ', $this->get_attributes() ),
				$this->name . '-' . $target,
				esc_attr( wp_strip_all_tags( $heading ) ),
				esc_attr( wp_strip_all_tags( $heading ) ),
				implode( "\n", array_map( \Closure::fromCallable( [
					$this,
					'choiceHtml'
				] ), $this->choices[ $section ] ) )
			);
			$html[] = '</div>';
		}

		$html[] = '</div>';

		return implode( "\n", $html );
	}

	/**
	 * Returns the html for a choice.
	 * @since 2.0.0
	 *
	 * @param \GF_Field $choice The choice field.
	 *
	 * @return string The HTML for this choice.
	 */
	protected function choiceHtml( \GF_Field $choice ): string {
		$label = $choice->get_field_label( ! $this->use_admin_labels, '' );

		return sprintf(
			'<li data-value="%s" role="option" aria-label="%s">
                <div class="field"><i class="fa fa-bars" aria-hidden="true"></i> <span>%s</span></div>
                <div class="move" role="button" tabindex="0" aria-label="%s">
                    <i class="fa fa-arrow-right" aria-hidden="true"></i>
                    <i class="fa fa-close" aria-hidden="true"></i>
                </div>
            </li>',
			$choice->id,
			esc_attr( $label ),
			$label,
			/* translators: %s: field label */
			esc_attr( sprintf( __( 'Move %s', 'gk-gravityexport-lite' ), $label ) )
		);
	}


	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function scripts(): array {
		$ids = array_map( function ( string $section ) {
			return '#' . $this->name . '-' . $section;
		}, array_keys( $this->sections ) );

		// Register sortable script.
		wp_add_inline_script(
			'gravityexport_lite',
			sprintf(
				'(function($) { $(document).ready(function() { gfexcel_sortable(\'%s\', \'%s\'); }); })(jQuery);',
				implode( ', ', $ids ),
				'fields-select'
			)
		);

		return parent::scripts();
	}

	/**
	 * Exclude {@see SortFields::$sections} from the attributes list.
	 * @since 2.0.0
	 */
	private function no_output_field_properties( array $properties ): array {
		if ( ! in_array( 'sections', $properties ) ) {
			$properties[] = 'sections';
		}

		return $properties;
	}
}
