<?php

namespace GFExcel\Field;

use GFExcel\Addon\GravityExportAddon;
use GFExcel\Values\BaseValue;

/**
 * Class FileUploadField
 *
 * @since 1.1.0
 */
class FileUploadField extends BaseField implements RowsInterface {
	/**
	 * @since 2.0.0
	 * @var \GF_Field_FileUpload $field
	 */
	protected $field;

	/**
	 * Array of needed cell values for this field
	 *
	 * @param array $entry
	 *
	 * @return BaseValue[]
	 */
	public function getCells( $entry ) {
		if ( ! $this->showFileUploadsAsColumn() ) {
			return [];
		}

		return parent::getCells( $entry );
	}

	/**
	 * @inheritdoc
	 * @return BaseValue[]
	 */
	public function getColumns() {
		if ( ! $this->showFileUploadsAsColumn() ) {
			return []; // no columns
		}

		return parent::getColumns();
	}

	/**
	 * Whether the uploads should be shown as a column.
	 *
	 * @since 1.1.0
	 *
	 * @return bool Whether the uploads should be shown as a column.
	 */
	private function showFileUploadsAsColumn() {
		// The default value of `true` will not be returned by get_plugin_setting(); it will return null by default.
		// So we apply the ?? operator and return true as the default value.
		$fileuploads_enabled = GravityExportAddon::get_instance()->get_plugin_setting( 'fileuploads_enabled' ) ?? true;

		return gf_apply_filters( [
			'gfexcel_field_fileuploads_enabled',
			$this->field->formId,
		], (bool) $fileuploads_enabled );
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	public function getRows( ?array $entry = null ): iterable {
		if ( ! $this->showFileUploadsAsColumn() ) {
			return [];
		}

		$value = $entry[ $this->field->id ] ?? '';
		if ( $this->field->multipleFiles ?? false ) {
			if ( ! empty( $this->getFieldValue( $entry ) ) ) {
				$files = json_decode( $value );
				if ( ! is_array( $files ) ) {
					return [];
				}

				foreach ( $files as $file ) {
					yield $this->wrap( [
						$this->field->get_download_url( $file, $this->should_force_download() ),
					] );
				}
			}
		} else {
			yield $this->getCells( $entry );
		}
	}

	/**
	 * @inheritDoc
	 * @since 2.0.0
	 */
	protected function getFieldValue( $entry, $input_id = '' ) {
		$value = parent::getFieldValue( $entry, $input_id );
		if ( $this->field->multipleField ?? false ) {
			return $value;
		}

		return $this->field->get_download_url( $value, $this->should_force_download() );
	}

	/**
	 * Returns whether to force a download URL.
	 *
	 * @since 2.3.3
	 *
	 * @return bool Whether to force a download URL.
	 */
	private function should_force_download(): bool {
		return (bool) gf_apply_filters(
			[
				'gfexcel_field_fileuploads_force_download',
				$this->field->formId,
			],
			true,
			$this->field
		);
	}
}
