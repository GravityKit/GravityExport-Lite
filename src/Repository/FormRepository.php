<?php

namespace GFExcel\Repository;

use GFExcel\Routing\Router;

/**
 * Repository to retrieve all information for a form.
 * @since 2.4.0
 */
class FormRepository implements FormRepositoryInterface {
	/**
	 * Gravity Forms Api.
	 * @since 2.4.0
	 * @var \GFAPI
	 */
	private $api;

	/**
	 * The Router.
	 *
	 * @since 2.4.0
	 *
	 * @var Router
	 */
	private $router;

	/**
	 * FormRepository constructor.
	 *
	 * @param \GFAPI $api A Gravity Forms API instance.
	 */
	public function __construct( \GFAPI $api, Router $router ) {
		$this->api    = $api;
		$this->router = $router;
	}

	/**
	 * {@inheritdoc}
	 * @since 2.4.0
	 */
	public function getEntries( int $form_id, array $search_criteria = [], array $sorting = [] ): iterable {
		$page_size = 100;
		$i         = 0;

		// prevent a multi-k database query to build up the array.
		$loop = true;
		while ( $loop ) {
			$paging = [
				'offset'    => ( $i * $page_size ),
				'page_size' => $page_size,
			];

			$new_entries = $this->api->get_entries( $form_id, $search_criteria, $sorting, $paging );
			$count       = count( $new_entries );
			if ( $count > 0 ) {
				foreach ( $new_entries as $entry ) {
					yield $entry;
				}
			}

			$i += 1; // increase for the loop

			if ( $count < $page_size ) {
				$loop = false; // stop looping
			}
		}
	}

	/**
	 * Returns the fields for a form.
	 * @since 2.4.0
	 *
	 * @param int $form_id The form id.
	 *
	 * @return \GF_Field[][] The form fields.
	 * @todo fix me, i don't like this.
	 */
	public function getFields( int $form_id ): array {
		if ( ! $form = $this->api->get_form( $form_id ) ) {
			return [];
		}

		$repository      = new FieldsRepository( $form );
		$disabled_fields = $repository->getDisabledFields();
		$all_fields      = $repository->getFields( true );

		$active_fields = $inactive_fields = [];
		foreach ( $all_fields as $field ) {
			$array_name      = in_array( $field->id, $disabled_fields, false ) ? 'inactive_fields' : 'active_fields';
			${$array_name}[] = $field;
		}

		return [
			'disabled' => $inactive_fields,
			'enabled'  => $repository->sortFields( $active_fields ),
		];
	}

	/**
	 * @inheritdoc
	 * @since 2.4.0
	 */
	public function getDownloadUrl( array $settings ): ?string {
		if ( ! $hash = $settings['hash'] ?? null ) {
			return null;
		}

		$blogurl = get_bloginfo( 'url' );
		if ( strpos( $hash, $blogurl ) !== false ) {
			return $hash;
		}

		return $this->router->get_url_for_hash( $hash );
	}
}
